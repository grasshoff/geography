<?php
require_once('conf.php');
require_once('lib/sag/Sag.php');

/**
 * ETRepoDb singleton instance.
 * @var ETRepoDb
 */
$dbInstance = null;

/**
 * ETRepoDb singleton creation.
 * @param string $dbName create ETRepoDb singleton instance with database name $dbName
 * @return ETRepoDb|null
 */
function ETRepoDbCreateInstance($dbName) {
    global $dbInstance;

    $dbInstance = new ETRepoDb($dbName);

    return $dbInstance;
}

/**
 * Return ETRepoDb singleton instance.
 * @return ETRepoDb singleton instance.
 */
function ETRepoDbGetInstance() {
    global $dbInstance;

    return $dbInstance;
}

/**
 * Class ETRepoDb. Database API for CouchDB access.
 */
class ETRepoDb {
    /**
     * Do CouchDB single key lookup: key=X
     * @var int
     */
    public static $KEY_LOOKUP_SINGLE = 1;

    /**
     * Do CouchDB multi key lookup: key=[X1,X2,...]
     * @var int
     */
    public static $KEY_LOOKUP_MULTI = 2;

    /**
     * Do CouchDB key range lookup: startkey=X&endkey=Y
     * @var int
     */
    public static $KEY_LOOKUP_RANGE = 3;

    /**
     * Maximum key count for multi-key lookup (to prevent too long request URLs)
     * @var int
     */
    private static $DEFAULT_MAX_KEY_COUNT = 200;

    /**
     * Sag CouchDB API object.
     * @var Sag
     */
    private $sag;

    /**
     * Database name
     * @var string
     */
    private $dbName;

    /**
     * Default design document for views.
     * @var string
     */
    private $designDoc;


    /**
     * Constructor
     * @param string $dbName CouchDB database name
     * @throws Exception
     * @throws SagCouchException
     * @throws SagException
     */
    public function __construct($dbName) {
        $this->sag = new Sag(ETRepoConf::$COUCH_DB_HOST, ETRepoConf::$COUCH_DB_PORT);

        // optionally use authentication
        if (!is_null(ETRepoConf::$COUCH_DB_USER) && !is_null(is_null(ETRepoConf::$COUCH_DB_PASS))) {
            $this->sag->login(ETRepoConf::$COUCH_DB_USER, ETRepoConf::$COUCH_DB_PASS);
        }

        $this->sag->setPathPrefix(ETRepoConf::$COUCH_DB_PATH_PREFIX);
        
        $this->sag->setDatabase($dbName);
        $this->dbName = $dbName;
        $this->designDoc = ETRepoConf::$COUCH_DB_DESIGN_DOC_VIEWS;
    }

    /**
     * Change the database
     * @param string $dbName database name
     * @throws Exception
     * @throws SagCouchException
     * @throws SagException
     */
    public function changeDb($dbName) {
    	$this->dbName = $dbName;
        $this->sag->setDatabase($dbName);
    }

    /**
     * Get current database name
     * @return string current database name
     */
    public function getCurrentDbName() {
        return $this->dbName;
    }

    /**
     * Fetch a document from CouchDB by document ID.
     * Directly using curl, bypassing sag
     * @param string $id CouchDB document ID
     * @return mixed document
     * @throws SagException
     */
    
    public function curl_download($id){
    	$Url = "http://";

    	if (!is_null(ETRepoConf::$COUCH_DB_USER) && !is_null(is_null(ETRepoConf::$COUCH_DB_PASS))) {
    		$Url .= ETRepoConf::$COUCH_DB_USER.":".ETRepoConf::$COUCH_DB_PASS."@";
    	}    	
    	
    	$Url .= ETRepoConf::$COUCH_DB_HOST.":".ETRepoConf::$COUCH_DB_PORT;
    	if (isset(ETRepoConf::$COUCH_DB_PATH_PREFIX) && (strlen(ETRepoConf::$COUCH_DB_PATH_PREFIX)>0)){
    		$Url .= ETRepoConf::$COUCH_DB_PATH_PREFIX;
    	}
    	
    	$Url .= "/".$this->dbName."/".$id;
    	
    	if (!function_exists('curl_init')){
    		die('cURL is missing in PHP installation!');
    	}
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $Url);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    	$output = curl_exec($ch);
    	if(curl_errno($ch)){
    		die('curl error:' . curl_error($ch));
    	}
    	curl_close($ch);
    	
    	$outputEncoded = json_decode($output);
    	if ($outputEncoded === NULL){
    		throw new Exception(sprintf("could not parse couch db doc: '%s'", $id));
    	}
    	
    	$outputInSagFormat = new stdClass();
    	$outputInSagFormat->{"body"} = $outputEncoded;
    	
    	return $outputInSagFormat;
    }

    /**
     * Fetch a document by CouchDB document ID.
     * @param string $id CouchDB document ID
     * @return mixed document
     * @throws SagException
     */
    public function fetchDocById($id) {
    	return $this->curl_download($id);
    }

    /**
     * Fetch results of a view by matching keys.
     * @param string $view view name
     * @param mixed $key key to match
     * @param bool $includeDocs use include_docs parameter in query
     * @return mixed document
     * @throws Exception
     */
    public function fetchViewWithKeyMatch($view, $key, $includeDocs=false) {
        return $this->fetchView($view, $key, self::$KEY_LOOKUP_SINGLE, null, $includeDocs);
    }

    /**
     * Fetch results of a view by matching multiple keys.
     * @param string $view view name
     * @param array $keys keys array to match
     * @param bool $includeDocs use include_docs parameter in query
     * @param int $maxKeyCount maximum number of keys to use in a single request
     * @param bool $mergeResult merge result into one object with body->rows
     * @return mixed document
     * @throws Exception
     */
    public function fetchViewWithKeysMatch($view, $keys, $includeDocs=false, $maxKeyCount=null, $mergeResult=true) {
        $maxKeyCount = $maxKeyCount === null ? self::$DEFAULT_MAX_KEY_COUNT : $maxKeyCount;

        // do not exceed $maxKeyCount (can lead to HTTP 414 error - maximum request URI exceeded)
        $lenKeys = count($keys);
        $slices = max($lenKeys / $maxKeyCount, 1);
        $fullRes = array();
        for ($slice = 0; $slice < $slices; $slice++) {
            $keysSlice = array_slice($keys, $slice * $maxKeyCount, $maxKeyCount);
            $resSlice = $this->fetchView($view, $keysSlice, self::$KEY_LOOKUP_MULTI, null, $includeDocs);
            array_push($fullRes, $resSlice);
        }

        if ($mergeResult) {
            // merge slices to combined object
            $mergedRes = new stdClass();
            $mergedRes->body = new stdClass();
            $mergedRes->body->rows = array();
            foreach ($fullRes as $resSlice) {
                $mergedRes->body->rows = array_merge($mergedRes->body->rows, $resSlice->body->rows);
            }

            return $mergedRes;
        } else {
            return $fullRes;
        }
    }

    /**
     * Fetch results of a view by matching key range.
     * @param string $view view name
     * @param array $keyRange key range [array(start, end)]
     * @param bool $includeDocs use include_docs parameter in query
     * @return mixed document
     * @throws Exception
     */
    public function fetchViewWithKeyRange($view, array $keyRange, $includeDocs=false) {
        return $this->fetchView($view, $keyRange, self::$KEY_LOOKUP_RANGE, null, $includeDocs);
    }

    /**
     * Fetch results of a view with grouping.
     * @param string $view view name
     * @param mixed $grouping grouping level: null for no grouping, true for exact grouping, integer 0-9 for grouping level
     * @return mixed document
     * @throws Exception
     */
    public function fetchViewWithGrouping($view, $grouping) {
        return $this->fetchView($view, null, self::$KEY_LOOKUP_SINGLE, $grouping);
    }

    /**
     * Fetch results of a view.
     * @param string $view view name
     * @param mixed $key single key or key range array or mulitple keys array
     * @param int $keyLookupType lookup type according to self::$KEY_LOOKUP_*
     * @param mixed $grouping grouping level: null for no grouping, true for exact grouping, integer 0-9 for grouping level
     * @param bool $includeDocs use include_docs parameter in query
     * @param mixed $limit if limit is not null but an integer, limit the query
     * @return mixed document
     * @throws Exception
     * @throws SagException
     */
    public function fetchView($view, $key=null, $keyLookupType=1, $grouping=null, $includeDocs=false, $limit=null) {
        $url = '/_design/' . $this->designDoc . '/_view/' . $view;

        $glue = '?';

        if ($key) {
            if ($keyLookupType == self::$KEY_LOOKUP_RANGE) {   // is startkey, endkey range
                assert(is_array($key));
                if (is_array($key[0])) {
                    $url .= $glue . 'startkey=' . $this->implodeArrayKey($key[0]);
                } else {
                    $url .= $glue . 'startkey=' . encodeURLParamValue($key[0]);
                }

                $glue = '&';

                if (is_array($key[1])) {
                    $url .= $glue . 'endkey=' . $this->implodeArrayKey($key[1]);
                } else {
                    $url .= $glue . 'endkey=' . encodeURLParamValue($key[1]);
                }
            } elseif ($keyLookupType == self::$KEY_LOOKUP_SINGLE) {    // is exact match
                if (is_array($key)) {
                    $url .= $glue . 'key=' . $this->implodeArrayKey($key);
                } else {
                    $url .= $glue . 'key=' . encodeURLParamValue($key);
                }
                $glue = '&';
            } elseif ($keyLookupType == self::$KEY_LOOKUP_MULTI) {      // multi-key exact match
                assert(is_array($key));

                if (is_array($key[0])) {
                    $keysQueryArr = array();
                    foreach ($key as $k) {
                        array_push($keysQueryArr, $this->implodeArrayKey($k));
                    }
                } else {
                    $keysQueryArr = $key;
                }

                $url .= $glue . 'keys=[' . implode(',', $keysQueryArr) . ']';
            } else {
                throw  new Exception('invalid key lookup type:' . $keyLookupType);
            }
        }

        // add grouping parameter
        if ($grouping !== null) {
            if (is_bool($grouping)) {
                $url .= $glue . 'group=' . (string)$grouping;
            } else {
                $url .= $glue . 'group_level=' . (int)$grouping;
            }
            $glue = '&';
        }

        // add include_docs parameter
        if ($includeDocs) {
            $url .= $glue . 'include_docs=true';
            $glue = '&';
        }

        // add limit parameter
        if ($limit !== null) {
            $url .= $glue . 'limit=' . $limit;
//            $glue = '&';
        }

//         var_dump($url);

        return $this->curl_download($url, true);
    }
    
    public function fetchUrl($url) {
    	#return $this->sag->get($url);
    	return $url;
    }
    

    /**
     * Fetch all documents of type $type
     * @param mixed $type document type
     * @return mixed document
     */
    public function fetchDocByType($type) {
        return $this->fetchViewWithKeyMatch('docs_by_type', $type);
    }

    /**
     * Return objects in a view $view whose key matches $lookupVal.
     * @param string $view view name
     * @param mixed $lookupVal key lookup value
     * @param bool $exactKeyMatch true: use exact key matching (single dim. keys), false: inexact matching (multidim. keys)
     * @param bool $includeDocs use include_docs parameter in query
     * @return mixed objects array
     * @throws Exception
     */
    public function findObjectsInViewByKey($view, $lookupVal=null, $exactKeyMatch=true, $includeDocs=false) {
        if (is_null($lookupVal)) {
            $viewRes = $this->fetchView($view, null, self::$KEY_LOOKUP_SINGLE, null, $includeDocs)->body;
        } else {
            if ($exactKeyMatch) {
                $viewRes = $this->fetchViewWithKeyMatch($view, $lookupVal, $includeDocs)->body;
            } else {
                // crazy couch db
                if (is_numeric($lookupVal)) {
                    $lookupLimit = $lookupVal + 1;
                } else if (is_string($lookupVal)) {
                    $lookupLimit = $lookupVal . '1';
                } else {
                    throw new Exception('lookup value must be either numeric or a string');
                }

                $viewRes = $this->fetchViewWithKeyRange($view, array(array($lookupVal), array($lookupLimit)),
                    $includeDocs)->body;
            }
        }

        return $viewRes->rows;
    }

    /**
     * Return single object from view $view whose key matches exactly $lookupVal.
     * @param string $view view name
     * @param mixed $lookupVal key lookup value
     * @param bool $exactKeyMatch true: use exact key matching (single dim. keys), false: inexact matching (multidim. keys)
     * @param bool $includeDocs use include_docs parameter in query
     * @return mixed single object
     */
    public function findSingleObjectInViewByKey($view, $lookupVal, $exactKeyMatch=true, $includeDocs=false) {
        $viewRes = $this->findObjectsInViewByKey($view, $lookupVal, $exactKeyMatch, $includeDocs);

        if (is_array($viewRes) && (count($viewRes)==1)){
            return $viewRes[0];
        } else {
            return false;
        }
    }

    /**
     * Return a single object from the 'obj_by_id' view whose ID is $objId
     * @param int $objId object id
     * @param bool $fullFetch whether to fetch the full document by joining associated documents
     * @return mixed object data
     */
    public function findSingleObjectById($objId, $fullFetch=false) {
        if (!$fullFetch) {
            return $this->findSingleObjectInViewByKey('obj_by_id', $objId);
        } else {
            $linkedDocs = $this->findObjectsInViewByKey('full_obj_by_id', $objId, false, true);
            $combinedDocs = $this->composeCombinedDocsFromRows($linkedDocs);

            assert(is_array($combinedDocs) && count($combinedDocs) == 1);

            return array_values($combinedDocs)[0];
        }
    }

    /**
     * Return all objects from the 'full_obj_by_id' view. The objects from this view will be combined via "include_docs"
     * parameter and "composeCombinedDocsFromRows()" method
     * @return array
     * @throws Exception
     */
    public function findAllObjectsFullFetch() {
        $linkedDocs = $this->findObjectsInViewByKey('full_obj_by_id', null, false, true);

        return $this->composeCombinedDocsFromRows($linkedDocs);
    }

    /**
     * Compose an array of combined documents from an array of rows from CouchDB. The linked documents come from a CouchDB
     * join query with 'include_docs=true' parameter (see http://docs.couchdb.org/en/latest/couchapp/views/joins.html)
     * @param array $rows linked documents. one element must be the base document. it has a "value" property of NULL
     * @return array combined document objects
     * @throws Exception when no base document was found
     */
    private function composeCombinedDocsFromRows(array $rows) {
        // create a structure of linked documents as array with docId -> linked documents mapping
        $linkedDocs = array();
        foreach ($rows as $doc) {
            $k = $doc->key;
            $v = $doc->value;

            // set document type: either '__base' if value is null or the second key value
            $t = is_null($v) ? '__base' : $k[1];

            // docId -> linked documents mapping
            $linkedDocs[$k[0]][$t] = $doc->doc;
        }

        // now merge the linked documents to single objects
        $combinedDocs = array();
        foreach ($linkedDocs as $docId => $docs) {
            if (!isset($docs['__base'])) {
                throw new Exception(sprintf("no base document was fetched from the DB for document ID %s", $docId));
            }

            $combDoc = $this->mergeLinkedDocs($docs);
            $combinedDocs[$docId] = $combDoc;
        }

        return $combinedDocs;
    }

    /**
     * Merge a structured array of linked documents to a single object
     * @param array $docs structured array of linked documents
     * @return stdClass combined object
     */
    private function mergeLinkedDocs(array $docs) {
        $baseDoc = $docs['__base'];

        // merge the linked documents with the base document
        $unwantedDocObjAttr = array('_id', '_rev', 'type');   // we do not add these attributes to the base doc
        foreach ($docs as $lDocType => $lDocObj) {
            if ($lDocType == '__base' || is_null($lDocObj)) continue;

            if ($lDocType == 'type') {
                $lDocType = 'type_obj';
            }

            // find out if we have a relation to multiple objects: this is the case when the related id property
            // in the base document ends with "_ids" and not with "_id"
            $idsKey = $lDocType . '_ids';
            $isRelationToList = isset($baseDoc->$idsKey);

            // create the related object if necessary
            if (!isset($baseDoc->$lDocType)) {
                if ($isRelationToList) {
                    $baseDoc->$lDocType = array(new stdClass());    // create an array because we await a list
                } else {
                    $baseDoc->$lDocType = new stdClass();   // create a normal object
                }
            } else {
                if ($isRelationToList) {    // add another empty object to the list
                    array_push($baseDoc->$lDocType, new stdClass());
                }
            }

            // get pointer to the object that is joined with the base document
            if ($isRelationToList) {
                $joinedAttrObj = end($baseDoc->$lDocType);
            } else {
                $joinedAttrObj = $baseDoc->$lDocType;
            }

            foreach ($lDocObj as $k => $v) {
                if (in_array($k, $unwantedDocObjAttr)) continue;    // filter out unwanted attributes

                // join this attribute with the base document
                $joinedAttrObj->$k = $v;
            }
        }

        return $baseDoc;
    }

    /**
     * Helper function to implode and array of keys to a string of the form "[k1,k2,...]" for CouchDB queries.
     * @param array $k array of keys
     * @return string string of the form "[k1,k2,...]" for CouchDB queries
     * @throws Exception
     */
    private function implodeArrayKey(array $k) {
        $s = '[';
        foreach ($k as $p) {
            $s .= encodeURLParamValue($p) . ',';
        }
        $s[strlen($s)-1] = ']';

        return $s;
    }
}