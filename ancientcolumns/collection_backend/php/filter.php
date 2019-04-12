<?php
/**
 * Edition Topoi Repository Collections entry point for AJAX filter backend requests.
 */
//needs significantly more memory
ini_set('memory_limit', '512M');

require_once('php/tools.php');
require_once('php/base.php');
require_once('php/db.php');

/**
 * Class to handle AJAX filter backend requests.
 */
class ETRepoFilter extends ETRepoBase {

        /**
         *Get Notebooks
         * @var string
         */
        private static $HIERARCHY_FILTER_NOTEBOOKS = 'get_notebooks';
        
        /**
     * Name of the filter view to fetch all research object IDs (e.g. all sundial IDs)
     * @var string
     */
    private static $HIERARCHY_FILTER_ALL_IDS_VIEW = 'obj_ids';

    /**
     * Name of the filter view that emits all related object IDs for all kinds of filter criteria
     * @var string
     * @see http://stackoverflow.com/questions/18979473/couchdb-where-conjunction#18980716
     */
    private static $HIERARCHY_FILTER_VIEW = 'hierarchy_filter';

    /**
     * Name of the filter view that emits preview information that will be displayed in the collection search for all
     * objects. This covers id, title, thumb and optionally subtitle
     * @var string
     */
    private static $HIERARCHY_PREVIEW_VIEW = 'hierarchy_preview';

    /**
     * Filter parameters (i.e. filter criteria) for the current request.
     * @var array
     */
    private $filterParams = array();

    /**
     * Limit parameters for pagination of the result set. Can be an array with 1 or 2 values (absolute limit or limit
     * with offset).
     * @var null|array
     */
    private $limitParams = null;

    /**
     * Collection counts for each criterion for collections filter (*not* for filtering *inside* a collection).
     * @var array
     */
    private $collectionsCriteriaCounts = array();


    /**
     * Generic sort-object-by-attribute.
     * @param stdClass|array $obj1 object 1 to compare
     * @param stdClass|array $obj2 object 1 to compare
     * @param string $attr attribute to use for comparison
     * @return int comparison result value -1, 0 or 1
     * @throws Exception
     */
    private static function sortCompareByAttr($obj1, $obj2, $attr) {
        $v1 = getAttr($obj1, $attr);
        $v2 = getAttr($obj2, $attr);

        return strnatcmp($v1, $v2);
    }

    /**
     * Sort two objects by 'title' attribute
     * @param stdClass|array $obj1 object 1 to compare
     * @param stdClass|array $obj2 object 1 to compare
     * @return int comparison result value -1, 0 or 1
     */
    private static function sortCompareByTitle($obj1, $obj2) {
        return self::sortCompareByAttr($obj1, $obj2, 'title');
    }

    /**
     * Sort two objects by 'coll_type' attribute
     * @param stdClass|array $obj1 object 1 to compare
     * @param stdClass|array $obj2 object 1 to compare
     * @return int comparison result value -1, 0 or 1
     */
    private static function sortCompareByCollType($obj1, $obj2) {
        return self::sortCompareByAttr($obj1, $obj2, 'coll_type');
    }


    /**
     * Sort two objects by 'id' attribute
     * @param stdClass|array $obj1 object 1 to compare
     * @param stdClass|array $obj2 object 1 to compare
     * @return int comparison result value -1, 0 or 1
     */
    private static function sortCompareByID($obj1, $obj2) {
        return self::sortCompareByAttr($obj1, $obj2, 'id');
    }


    /**
     * Slice the result set using the limit parameters in $this->limitParams.
     * @param array $result complete result set
     * @return array of four elements: sliced result set, number of results, result indices, total indices
     */
    private function sliceResultSet(array $result) {
        $numResults = count($result);
        if (is_array($this->limitParams)) { // we have limit parameters -> slice the result set
            if (count($this->limitParams) >= 2) {
                list($sliceOffset, $sliceLen) = $this->limitParams;
            } else {
                $sliceOffset = 0;
                $sliceLen = $this->limitParams[0];
            }
            $resultSlice = array_slice($result, $sliceOffset, $sliceLen);
            $resultIndexes = array($sliceOffset, $sliceOffset + count($resultSlice));
        } else {    // we have not limit parameters -> will return the complete result set
            $resultSlice = $result;
            $resultIndexes = array(0, $numResults - 1);
        }
        foreach($result as $num=>$docID){
                if (!is_int($docID)){                    
                        $result[$num] = trim($docID, '"');
                }
                }

        return array($resultSlice, $numResults, $resultIndexes, $result);
    }


    /**
     * Query document IDs using the filter criteria in $this->filterParams. Additionally slice the result set when
     * a "limit" parameter was submitted.
     * @return array of three elements: sliced result set, number of results, result indices
     * @throws Exception
     */
    private function queryDocIdsWithFilter() {
        $allFetchedDocIds = array();
        // for each criteria, query the DB to fetch document IDs matching that criterion
        foreach ($this->filterParams as $crit => $keys) {
            // create query keys array of the form [[crit, key1], [crit, key2], ...]
            $queryKeys = array();
            foreach ($keys as $k) {
                array_push($queryKeys, array($crit, $k));
                
            }
//            echo "query with keys:\n";
//            var_dump($queryKeys);
            $res = $this->db->fetchViewWithKeysMatch(self::$HIERARCHY_FILTER_VIEW, $queryKeys);

            $fetchedDocIds = array();
            if (isset($res->body) && isset($res->body->rows)) {
                foreach ($res->body->rows as $doc) {
                    array_push($fetchedDocIds, $doc->value);
                }
            } else {
                throw new Exception("error querying '" . self::$HIERARCHY_FILTER_VIEW . "'");
            }

//            echo 'fetched doc ids:' . count($fetchedDocIds) . "\n";

            if (count($fetchedDocIds) == 0) {   // optimization: if one set is empty, the final intersection will be empty
                return array(array(), 0, array(0, 0), array());
            }

            //remove possible duplicates
            $fetchedDocIds = array_values(array_unique($fetchedDocIds));
            
            array_push($allFetchedDocIds, $fetchedDocIds);
        }

        $numFilterParams = count($this->filterParams);
        if ($numFilterParams == 1) {
            // document ids for single criterion
            $resultDocIds = $allFetchedDocIds[0];
        } else if ($numFilterParams > 1) {
            // intersection of all document ids fetched by each criterion
            $resultDocIds = multiArrayIntersection($allFetchedDocIds);
        } else {
            // *all* ids when no filter criterion is specified
            $res = $this->db->fetchView(self::$HIERARCHY_FILTER_ALL_IDS_VIEW);
            $fetchedDocIds = array();
            if (isset($res->body) && isset($res->body->rows)) {
                foreach ($res->body->rows as $doc) {
                    array_push($fetchedDocIds, $doc->value);
                }
            } else {
                throw new Exception("error querying '" . self::$HIERARCHY_FILTER_ALL_IDS_VIEW . "'");
            }

            $resultDocIds = $fetchedDocIds;
        }

        // sort by document IDs (object IDs)
        natsort($resultDocIds);
        // sort/natsort keep the index position, which will break JSON output
        // so just keep the (now correctly sorted) values
        $resultDocIds = array_values($resultDocIds);
        
        //wrap non-integer IDs in quotation marks
        foreach($resultDocIds as $num=>$docID){
                if (!is_int($docID)){
                        $resultDocIds[$num] = "\"$docID\"";
                }
        }

        // this slows down the retrieval significantly, only use this if really needed (as with VMRS)
        if (isset($this->projMeta->object_sort_key) && (strlen($this->projMeta->object_sort_key)>0)){
        	$sortKey = $this->projMeta->object_sort_key;
	        // fetch the preview information for the IDs
	        $previewInfos = $this->getPreviewInfosForDocIds($resultDocIds);
	        // sort collections result set by titles
	        usort($previewInfos, function($obj1, $obj2) use ($sortKey) {
	        	return self::sortCompareByAttr($obj1, $obj2, $sortKey);
	        });
	        //build new array with new ordering of IDs
	        $resultDocIds = [];
	        foreach ($previewInfos as $element){
	        	array_push($resultDocIds, $element->{"id"});
	        }
        }
        // optionally slice array when we have a limit parameter
        return $this->sliceResultSet($resultDocIds);
    }


    /**
     * Get the object IDs that are inside each criterion for the current collection.
     *
     * @return array counts of how many objects are inside each criterion for the current collection
     * @throws Exception
     */
    private function getCriteraIDsOfCollection() {
        // fetch the hierarchy filter view which returns rows with the following key value pairs:
        // [<filter_criterion>, <filter_value>] -> document id
        $cacheFileName = $this->projName."hierarchy_counts";
    	if ($this->cache->exists($cacheFileName)) {
    		return $this->cache->retrieve($cacheFileName);
    	}
    	
        $res = $this->db->fetchView(self::$HIERARCHY_FILTER_VIEW);

        // now count the documents in each <filter_criterion> – <filter_value> pair
        $counts = array();
        foreach ($res->body->rows as $row) {
            list($crit, $val) = $row->key;
            if (!isset($counts[$crit])) {
                $counts[$crit] = array();
            }
            if (!isset($counts[$crit][$val])) {
                $counts[$crit][$val] = [];
            }

            array_push($counts[$crit][$val], $row->value);
        }

        // remove duplicates from the lists
        foreach ($counts as $crit => $values){
            $test = array($values);
            foreach (array_keys($values) as $key){
                $ids = $counts[$crit][$key];
                $counts[$crit][$key] = array_values(array_unique($ids));
            }
        }

        $this->cache->save($cacheFileName, $counts);
        return $counts;
    }

    
    private function getAllNotebooks() {
		if ($this->cache->exists('all_notebooks')) {
	    	 return $this->cache->retrieve('all_notebooks');
    	} else {
	    	$counts["notebooks"]=0;
	    	$counts["total_count"]=0;
	    	$counts['rows'] = [];
	    	$counts['total_indices'] = [];   	
	    	$counts['fetched_indices'] = [];
	    	$counts["collections_with_notebooks"]=0;
	    	
	    	$db = false;
	    	
	    	foreach ($this->confDoc->collections as $collName => $collDb) {
	    		if (!$db) {
	    			$db = ETRepoDbCreateInstance($collDb);
	    		} else {
	    			$db->changeDb($collDb);
	    		}
	    	
				try {
	                $res = $db->fetchView('get_notebooks');
	                
	                $hasNotebooks = false;
	                foreach ($res->body->rows as $row) {
	                	list($crit, $val) = $row->key;
	                	if (!isset($counts[$crit])) {
	                		$counts["rows"][$row] = array();
	                		$counts["total_count"]=+1;
	                		$counts["notebooks"]+=1;
	                		
	                		$hasNotebooks = true;
	                	}
	                	array_push($counts["rows"], ['id' => trim($row->key,"'"),'title'=>$row->value,'thumb'=>"/img/notebook_picto_rgb_transparent.png",'collection'=>strtoupper($collName)]);
	                	array_push($counts["total_indices"], [$row->key]);
	                }
	                if ($hasNotebooks){
	                	$counts["collections_with_notebooks"]+=1;
	                }
	                
	                
	            } catch (SagCouchException $e) {}   // ignore "document not found" errors when "meta" is not found
	    	}
	    	$this->cache->save('all_notebooks', $counts);
	    	 
	    	return $counts;
    	}
    }
    
    private function getCriteriaIdsOfNotebooks($show) {

        // fetch the hierarchy filter view which returns rows with the following key value pairs:
        // [<filter_criterion>, <filter_value>] -> document id
        $counts["notebooks"]=0;
         
        $res = $this->db->fetchView(self::$HIERARCHY_FILTER_NOTEBOOKS);
    
        // now count the documents in each <filter_criterion> – <filter_value> pair
        $counts = array();
        $counts["rows"]=[];
        $counts["total_indices"]=[];
        $counts["total_count"]=0;
        $counts["fetched_indices"]=[];
        
        foreach ($res->body->rows as $row) {
                list($crit, $val) = $row->key;
                if (!isset($counts["rows"])) {
					$counts["rows"] = array();
                }
                array_push($counts["rows"], ['id' => trim($row->key,"'"),'title'=>$row->value,'thumb'=>"img/notebook_picto_rgb_transparent.png"]);
                array_push($counts["total_indices"], [$row->key]);
                $counts["total_count"]+=1;
        }
        if ($show == "num_notebooks") {
                return $counts["total_count"];                  
        } else {
                return $counts;
        }
    }
    
    /**
     * Fetch and return the preview information like title, thumb, etc. for all document IDs in $docIds.
     *
     * @param array $docIds document IDs for which to get the preview information
     * @return array with stdClass objects containing the preview information returned by the hierarchy_preview view
     * @throws Exception
     */
    private function getPreviewInfosForDocIds(array $docIds) {
        if (count($docIds) <= 0) {
            return array();
        }

        // get the preview information for each provided document ID
        // (there is no more efficient way to do this with CouchDB AFAIK)
        $res = $this->db->fetchViewWithKeysMatch(self::$HIERARCHY_PREVIEW_VIEW, $docIds);

        // save the result to the $infos array
        $infos = array();
        if (isset($res->body) && isset($res->body->rows)) {
            foreach ($res->body->rows as $row) {
                $thumb = $row->value->thumb;
                if ($thumb && $thumb[0] && $thumb[0]){
                        // get thumb from file
                        $thumbPath = thumb($thumb[0], $thumb[1], "tiny");
                        $row->value->thumb = $thumbPath;
                }
                array_push($infos, $row->value);
            }
        } else {
            throw new Exception("error querying '" . self::$HIERARCHY_PREVIEW_VIEW . "'");
        }

        return $infos;
    }


    /**
     * Get the possible filter criteria values from all available collections and their information specified in the
     * collection metadata field "filter" in $collMeta.
     * Also save the counts of collections per criterion value in $this->collectionsCriteriaCounts
     * @param array $collMeta metadata from "meta" document for each collection
     * @return array array of collection criteria and their values for each collection
     */
    private function getCollectionsCriteria(array $collMeta) {
        // for each of the defined collections criteria, gather the "filter" values for each collection defined in the
        // meta document of each collection's database
        $db = null;
        $collectionsCriteria = array();

        // get all available collection with their short names (BSDP, MAPD, etc.)
        $collNames = $this->getAllCollectionNames();

        // initialize counts array for 'type' criterion
        $this->collectionsCriteriaCounts = array(
            'type' => array(
                'collection' => 0,
                'bag' => 0,
            	'notebook' => 0,
            		
            )
        );

        // go through all defined collection criteria as key => title
        foreach (ETRepoConf::$FILTER_COLLECTIONS_CRITERIA as $critKey => $title) {
            $collectionsCriteria[$critKey] = array();

            if (!isset($this->collectionsCriteriaCounts[$critKey])) {
                // initialize counts array for this criterion
                $this->collectionsCriteriaCounts[$critKey] = array();
            }

            // now go through each collection and get its metadata
            foreach ($collNames as $collName) {
                $meta = $collMeta[$collName];

                if ($critKey != 'type') {
                    // save the value of this filter criteria to the collection criteria array
                    if (isset($meta->filter) && isset($meta->filter->$critKey)) {
                        $collectionsCriteria[$critKey][$collName] = $meta->filter->$critKey;

                        // count criterion values
                        foreach ($meta->filter->$critKey as $critVal) {
                            if (!isset($this->collectionsCriteriaCounts[$critKey][$critVal])) {
                                $this->collectionsCriteriaCounts[$critKey][$critVal] = [];
                            }

                            $this->collectionsCriteriaCounts[$critKey][$critVal][]=$meta->shorttitle;
                        }
                    }
                } else {    // handle type criterion
                    // additionally add the collection type ("collection" / "bag")
                    $collType = isset($meta->collection_type) ? $meta->collection_type : 'collection';
                    $collectionsCriteria[$critKey][$collName] = array(ucfirst($collType));

                    $this->collectionsCriteriaCounts[$critKey][$collType]++;
                }
            }
        }

        return $collectionsCriteria;
    }


    /**
     * Query the collections by using the filter criteria specified in $this->filterParams and return the matched
     * collection shortnames (BSDP, MAPD, etc.)
     *
     * @param array $collectionsCriteria collection criteria and their values for each collection
     * @return array sliced result set of names of filtered collections
     */
    private function queryCollectionNamesWithFilter(array $collectionsCriteria) {
        $foundCollections = array();
        if (count($this->filterParams) > 0) {   // we have filter criteria
            // go through all selected filter criteria
            foreach ($this->filterParams as $crit => $queryKeys) {
                $foundCollections[$crit] = array();
                if (isset($collectionsCriteria[$crit])) {
                    // go through all query keys like "subject", "type", etc.
                    foreach ($queryKeys as $qryK) {
                        // go through all collections and their filter criteria value
                        foreach ($collectionsCriteria[$crit] as $collName => $critKeys) {
                            // go through the criteria of this collection
                            foreach ($critKeys as $critKey) {
                                // if the queried criteria and the collection's criteria match and the collection
                                // was not added yet to the result set, add it now to the result set

                                //if (strtolower($qryK) == strtolower($critKey) && !in_array($collName, $foundCollections[$crit])) {
                                         
                                if (strtolower($qryK) == strtolower($critKey) && !in_array($collName, $foundCollections[$crit])) {
                                    array_push($foundCollections[$crit], $collName);
                                }
                            }
                        }
                    }
                }
            }

            if (count($foundCollections) > 1) { // do an array intersection to perform AND conjunction
                $finalCollNames = multiArrayIntersection(array_values($foundCollections));

            } else {
                // return all found collections
                $finalCollNames = current($foundCollections);
            }
        } else {
            $finalCollNames = $this->getAllCollectionNames();  // all collections (no filter selected)
        }

        // optionally slice array when we have a limit parameter
        return $this->sliceResultSet($finalCollNames);
    }


    /**
     * Return the preview information for the collection names provided in $collNames
     * @param array $collNames array of collection shortnames (BSDP, MAPD, etc.)
     * @param array $collMeta array of metadata for each collection
     * @return array
     */
    private function getPreviewInfosForCollectionNames(array $collNames, array $collMeta) {
        $previewInfos = array();

        // go through all collection names
        foreach ($collNames as $coll) {
            // get metadata for this collection
            $meta = $collMeta[$coll];

            // try to get the thumbnail
            $thumb = isset($meta->service_images_path) && $meta->collections_bg_img ? array($meta->service_images_path, $meta->collections_bg_img) : null;

            // try to get the collection type
            $collType = isset($meta->collection_type) ? $meta->collection_type : 'collection';

            // construct the collection preview information array
            $researchGroup = "";
            if (isset($meta->research_group)){
	            if (is_array($meta->research_group) && is_array($meta->research_group[0])){
	                $researchGroup = $meta->research_group[0];
	            } else {
	                $researchGroup = $meta->research_group;
	            }
            }
            
            $title = "";
            if (isset($meta->title)){
            	$title = $meta->title;
            }
            $info = array(
                'id' => $coll,
                'title' => $title,
                // 'subtitle' => truncateWords($meta->abstract, 20, '...'),
                'subtitle' => $researchGroup,
                'thumb' => $thumb,
                'coll_type' => $collType
            );

            array_push($previewInfos, $info);
        }

        return $previewInfos;
    }

    /**
     * Create a response array with collections/research objects that were filtered with the criteria set in $params.
     *
     * @param array $params criteria/limit parameters
     * @return array response array with result set
     * @throws Exception
     */
    private function createResponseForFiltering(array $params) {
        // get filter-by-parameters
        foreach ($params as $k => $v) {
            $pPrefix = substr($k, 0, 3);
            $pName = substr($k, 3);
            if ($pPrefix == 'by_' && strlen($pName) > 0) {
                $vList = explode(',', $v);
                $paramValues = array();
                foreach ($vList as $vv) {
                    array_push($paramValues, trim($vv));    // more cleanup here?
                }
                $this->filterParams[$pName] = $paramValues;
            }
        }

        // get limit parameter
        if (isset($params['limit'])) {
            $limit = explode(',', $params['limit']);
            $this->limitParams = array();
            foreach ($limit as $l) {
                array_push($this->limitParams, (int)trim($l));
            }
        }

        if (!$this->pathComponents) {   // no path -- filter collections
        		
        		$collMeta = $this->getAllCollectionsMeta();
        		$collectionsCriteria = $this->getCollectionsCriteria($collMeta);
        		
        		list($fetchedCollNames, $numOverallDocs, $docIndexes, $totalIndexes) = $this->queryCollectionNamesWithFilter($collectionsCriteria);
        		
        		$previewInfos = $this->getPreviewInfosForCollectionNames($fetchedCollNames, $collMeta);
        		#$notebooks = "none";

        		

        } else {    // path given -- filter *inside* a collection
            // fetch the document IDs filtered by the criteria in filterParams and limited by limitParams
            list($fetchedDocIds, $numOverallDocs, $docIndexes, $totalIndexes) = $this->queryDocIdsWithFilter();

            //        echo "intersection ids:\n";
            //        var_dump($fetchedDocIds);

            // fetch the preview information for the IDs
            $previewInfos = $this->getPreviewInfosForDocIds($fetchedDocIds);
            //        usort($previewInfos, "self::sortCompareByID");

            //        echo "infos:\n";
            //        var_dump($previewInfos);
            #$notebooks = $this->getCriteriaIdsOfNotebooks("num_notebooks");
            
        }

        $notebooks = $this->getAllNotebooks();
        
        $notebookCount = 0;
        if ($this->pathComponents){
        	//check if this collection has notebooks
        	$collectionNotebookCount = 0;
        	foreach ($notebooks["rows"] as $notebookEntry){
        		if ($notebookEntry["collection"] === $this->pathComponents[0]){
        			$notebookCount++;
        		}
        	}
        } else {
        	$notebookCount = $notebooks["collections_with_notebooks"];
        }
        
        return array(
            'rows' => $previewInfos,
            'total_indices' => $totalIndexes,
            'total_count' => $numOverallDocs,
            'fetched_indices' => $docIndexes,
        	'num_notebooks' => $notebookCount
        );
    }


    /**
     * Create a response array with counts of collections/research objects for each criterion.
     * @return array response data with counts
     */
    private function createResponseForFilterCounts() {
        if (!$this->pathComponents) {   // no path -- get counts for all collections
            $this->getCollectionsCriteria($this->getAllCollectionsMeta());
            $responseData = $this->collectionsCriteriaCounts;
        } else {                        // path given -- get object IDs for criteria *inside* a collection
            $responseData = $this->getCriteraIDsOfCollection();
            
        }

        return $responseData;
    }

    /**
     * @return array response data with notebooks
     */
    private function createResponseForFilterNotebooks() {
        $responseData = $this->getCriteriaIdsOfNotebooks("none");
                
        return $responseData;
    }
    
    /**
     * @return array response data with all notebooks
     */
    private function createResponseForFilterAllNotebooks() {

    	//Hack: this replicates function from createResponseForFiltering above
    	// get all collection and only keep those that have notebooks
    	$notebooks = $this->getAllNotebooks();
    	$collMeta = $this->getAllCollectionsMeta();
    	$collectionsCriteria = $this->getCollectionsCriteria($collMeta);
    	
    	list($fetchedCollNames, $numOverallDocs, $docIndexes, $totalIndexes) = $this->queryCollectionNamesWithFilter($collectionsCriteria);
    	
    	$previewInfos = $this->getPreviewInfosForCollectionNames($fetchedCollNames, $collMeta);
    	
    	$notebookCount = 0;
    	$collectionsWithNotebooks = [];
    	foreach ($notebooks["rows"] as $notebookEntry){
    		$collectionName = $notebookEntry["collection"];
    		if (!in_array($collectionName, $collectionsWithNotebooks)){
    			array_push($collectionsWithNotebooks, $collectionName);
    			$notebookCount++;
    		}
    	}
    	   
    	$filteredPreviewInfos = [];
    	foreach($previewInfos as $collectionPreview){
    		if (in_array($collectionPreview["id"], $collectionsWithNotebooks)){
    			array_push($filteredPreviewInfos, $collectionPreview);
    		}
    	}
    	    	
    	return array(
    			'rows' => $filteredPreviewInfos,
    			'total_indices' => $totalIndexes,
    			'total_count' => $numOverallDocs,
    			'fetched_indices' => $docIndexes,
    			'num_notebooks' => $notebookCount
    	);
    }
        
    /**
     * Main entry point for this class
     * @param array $params script query parameterscreate
     * @throws Exception
     */
    public function run(array $params) {
        // run parent method to set basic things
        try {
                parent::run($params);
                
            // check whether criteria counts are requested
            $countsRequested = isset($params['get_counts']);
            $notebooksRequested = isset($params['get_notebooks']);
            $allNotebooksRequested = isset($params['get_all_notebooks']);
            
            if ($countsRequested) {
                // get the counts
                $responseData = $this->createResponseForFilterCounts();

            } else if ($notebooksRequested) {
                // get the counts
                $responseData = $this->createResponseForFilterNotebooks();
                
            } else if ($allNotebooksRequested ) {
               // get the counts
                $responseData = $this->createResponseForFilterAllNotebooks();
                //$responseData = $this->createResponseForFiltering($params);
                
            } else {
                // get the filter response data
                $responseData = $this->createResponseForFiltering($params);

            }

            $this->renderJSONContent($responseData);
        } catch (Exception $ex) {
            // when an error occured, return it as JSON content
            $this->renderJSONContent(array('error' =>  $ex->getMessage(), 'exception' => get_class($ex), 'trace' => $ex->getTraceAsString()));
            //    header("Location: /");
            //    die();            
        }
    }
}

// create filter script class & run it
$filter = new ETRepoFilter();
$filter->run($_GET);
