<?php
/**
 * Edition Topoi Repository Collections entry point for object display, single resource display or JSON export.
 *
 * GET Parameters:
 * - path: query path as <COLL>/<ACTION>/<QUERY_PARAMS> with COLL being collection identifier, ACTION being "object",
 *         "single" or "export"
 */

require_once('php/conf.php');
require_once('php/tools.php');
require_once('php/base.php');
require_once('php/db.php');
require_once('php/hierarchy.php');
require_once('php/cache.php');

require_once('php/lib/Browser/Browser.php');


/**
 * Class ETRepoBrowse. Implements recursive browsing through the collections and display of objects and datasets.
 */
class ETRepoBrowse extends ETRepoBase {
    /**
     * Collection hierarchy handler.
     * @var ETRepoHierarchy
     */
    private $hierarchyHandler;

    /**
     * Create page content for object display page.
     *
     * @return array list with content data, title, metadata object
     */
    private function createContentForObjectDisplay() {
        assert(count($this->pathComponents) >= 3);

        // the third path component must be the object id (e.g. /BSDP/object/543)
        $objId = intFromNumber($this->pathComponents[2]);

        // try to get the display definition document
        $objectViewDispDef = null;
        try {
            $dispDefDoc = $this->db->fetchDocById('display_definitions');
            $dispDef = $dispDefDoc->body;
            if (isset($dispDef->object_view)) {
                $objectViewDispDef = $dispDef->object_view;
            }
        } catch (SagCouchException $e) {}   // ignore if display_definitions does not exist

        // get the *full* document with all related (joined) documents merged into it
        $objData = $this->db->findSingleObjectById($objId, true);

        $sciData = array();
        $sciThumbDirs = array();
        $sciBaseLinks = array();
        $objData->images = array();
        $objData->resources = array();

        // create sCi related data
        foreach ($objData->scis as $sciKey => $sciObjects) {
            if (!is_array($sciObjects)) {
                $sciObjects = array($sciObjects);
            }

            foreach ($sciObjects as $sciObj) {
                $sciBaseLinks[$sciKey][$sciObj->file] = $this->makeURL(array($this->projName, 'single', $sciObj->file));
                $sciContents = $this->getSCIContents($sciObj->file);
                $sciData[$sciKey][$sciObj->file] = $sciContents;
                $sciVers = isset($sciContents->sci_version) ? $sciContents->sci_version : '1.0';
                $sciThumbDirs[$sciKey][$sciObj->file] = ($sciVers == '1.0') ? $this->getGalleryThumbsPath($sciObj->file)
                                                                            : ETRepoConf::$RESOURCES_BASE_PATH . $sciContents->resource_thumbs_baseurl;
                $sciResources = ($sciVers == '1.0') ? $sciContents : $sciContents->resources;

                if ($sciKey == 'images') {  // "images" is a special kind of resource, it will be displayed in a gallery
                    $objData->images[$sciObj->file] = $sciResources;
                } else {                    // all other resources are identified by there resource type $sciKey
                    $objData->resources[$sciKey][$sciObj->file] = $sciResources;
                }
            }
        }

        $title = $this->projMeta->shorttitle . ' ' . $objId;
        $tplFileTop = isset($this->projMeta->object_template_file_top) ? $this->projMeta->object_template_file_top : null;
        $tplFileMain = isset($this->projMeta->object_template_file_main) ? $this->projMeta->object_template_file_main : null;
        $tplFileRight = isset($this->projMeta->object_template_file_right) ? $this->projMeta->object_template_file_right : null;

        // render the object display using the above templates
        $contentData = $this->renderObjectDisplay(
            $objData, $sciBaseLinks, $sciData, $sciThumbDirs,
            $tplFileTop, $tplFileMain, $tplFileRight,
            $objectViewDispDef
        );

        return array($contentData, $title, $objData->metadata);
    }

    /**
     * Return sCi Metadata, page title and URL to related object for single sCi display with sCi Version 1.0.
     * @deprecated
     * @param string $sciName sCi name
     * @return array sCi Metadata, page title and ID of related object
     * @throws Exception
     */
    private function getSCISingleEntryInformationV1($sciName) {
        $relatedObjectID = null;

        try {
            $sciMetadataDoc = $this->db->fetchDocById('sci-meta-' . $sciName);

            if ((!isset($sciMetadataDoc->body->resource) && !isset($sciMetadataDoc->body->fetch_from_doc))
                || (is_null($sciMetadataDoc->body->resource) && is_null($sciMetadataDoc->body->fetch_from_doc)))
            {
                throw new Exception('could not fetch valid sCi metadata document for sCi name ' . $sciName . ' from DB');
            }

            if (is_null($sciMetadataDoc->body->resource)) {
                $relDocId = $sciMetadataDoc->body->fetch_from_doc;
                $otherDoc = $this->db->fetchDocById($relDocId);
                if (!isset($otherDoc->body->metadata) || is_null(isset($otherDoc->body->metadata))) {
                    throw new Exception('could not fetch valid sCi metadata document for sCi name ' . $sciName . ' from DB via related document ' . $relDocId);
                }

                $sciMetadata = $otherDoc->body->metadata;
            } else {
                $sciMetadata = $sciMetadataDoc->body->resource;
            }

            if (!isset($sciMetadata->{"General Information"}->Title)) {
                throw new Exception('could not fetch title from sCi metadata');
            }
            $title = $sciMetadata->{"General Information"}->Title;

            if (isset($sciMetadataDoc->body->related_object_id)) {
            	$relatedObjectID = $sciMetadataDoc->body->related_object_id;
            }
        } catch (SagCouchException $e) {
            $sciMetadataDoc = null;
            $sciMetadata = new stdClass();
            $title = $sciName;
        }

        return array($sciMetadata, $title, $relatedObjectID);
    }

    /**
     * Return sCi Metadata, page title and URL to related object for single sCi display with sCi Version 2.0.
     * @param stdClass $sciData sCi resources object
     * @param mixed $entryId entry ID for the individual resource
     * @return array sCi Metadata, page title and ID of related object
     * @throws Exception
     */
    function getSCISingleEntryInformationV2($sciData, $entryId) {
        // merge basic sCi metadata with single entry's metadata
        $baseMeta = $sciData->metadata;
        $entryMeta = $sciData->resources[$entryId]->metadata;

        $mergedMeta = mergeObjects($baseMeta, $entryMeta);
        if (isset($mergedMeta->{"General Information"}->Title)) {
            $title = $mergedMeta->{"General Information"}->Title;
        } else {
            $title = '';
        }
        
        $relatedObjectID = null;
        if (isset($sciData->assoc_obj_id)) {
            $relatedObjectID = $sciData->assoc_obj_id;
        }
        
        return array($mergedMeta, $title, $relatedObjectID);
    }

    /**
     * Create page content for single sCi dataset display page.
     *
     * @return array list with content data, title, metadata object
     */
    private function createContentForSingleSCIDisplay() {
        $numPathComponents = count($this->pathComponents);
        assert($numPathComponents >= 3);

        // the third path component must be the sCi name (e.g. "0543" from /BSDP/single/0543)
        $sciName = $this->pathComponents[2];

        // fourth path component is optional: can be the index of an entry *inside* the sCi-file (e.g. /BSDP/single/0543/3)
        if ($numPathComponents > 3 && is_numeric($this->pathComponents[3])) {
            $sciInternalId = (int)$this->pathComponents[3];
        } else {
            $sciInternalId = 0;
        }

        $sciDataRaw = $this->getSCIContents($sciName);
        $sciVers = isset($sciDataRaw->sci_version) ? (float)$sciDataRaw->sci_version : 1.0;

        if ($sciVers == 1.0) {
            $sciData = $sciDataRaw;
        } else {
            $sciData = $sciDataRaw->resources;
        }

        if (!array_key_exists($sciInternalId, $sciData)) {
            throw new Exception('no sCi entry for sCi name ' . $sciName . ' and sCi entry ID ' . $sciInternalId);
        }

        if ($sciVers == 1.0) {
            list($sciMetadata, $title, $relatedObjectID) = $this->getSCISingleEntryInformationV1($sciName);
            $resBaseUrl = $this->projName . '/Repos' . $this->projName . '/' . $this->projName . $sciName;
        } else {
            list($sciMetadata, $title, $relatedObjectID) = $this->getSCISingleEntryInformationV2($sciDataRaw, $sciInternalId);
            $resBaseUrl = $sciDataRaw->resources_baseurl;
        }

        $contentData = $this->renderSingleSCIDisplay($sciVers, $sciName, $resBaseUrl, $sciInternalId, $relatedObjectID,
            $sciData, $sciMetadata, 'single_sci_display.php');

        return array($contentData, $title, $sciMetadata);
    }

    /**
     * Create assoc. array with data for JSON export of different types of data: single resource, resource group,
     * single object, all objects of a collection.
     * Parses the URL to know which type of data is requested. URL format has to be:
     * /collection/<COLL>/export/<COLL>_<resource|resources|object|objects>[_<ID>[_<ENTRY>]].cite[&dl=1]
     *
     * - COLL is the collection name, e.g. BSDP or ICG
     * - resource, resources, object or objects defines which type of data to export
     * - ID is the identifier of the data, e.g. "0815" for resources from sCi "BSDP0815" or "12" for object 12
     * - optional ENTRY defines which item of a resources data set to export
     * - optional URL parameter "dl=1" defines whether the data should be downloaded
     *
     * @return array with 2 elements: 1. data for JSON export, 2. boolean value: download
     * @throws Exception
     */
    private function createContentForJSONExport() {
        function getAndCheckSciData($me, $sci) {
            $sciData = $me->getSCIContents($sci);
            if (!isset($sciData->sci_version) || (float)$sciData->sci_version < 2.0) {
                throw new Exception(sprintf("sCi '%s' has invalid version", $sci));
            }
            if (!isset($sciData->resources) || !is_array($sciData->resources)) {
                throw new Exception(sprintf("invalid sCi data from sCi '%s'", $sci));
            }

            return $sciData;
        }

        $commonExportDelKeys = array('_id', '_rev', 'type');
        
        $fetchResourcesOfObject = function() use (&$objData, &$commonExportDelKeys){
        	$objData->resources = array();
        	 
        	// get associated sCi data
        	foreach ($objData->scis as $sciKey => $sciObjects) {
        		if (!is_array($sciObjects)) {
        			$sciObjects = array($sciObjects);
        		}
        	
        		foreach ($sciObjects as $sciObj) {
        			$sciContents = $this->getSCIContents($sciObj->file);
        			delKeys($sciContents, $commonExportDelKeys);
        			$objData->resources[$sciKey][$sciObj->file] = $sciContents;
        		}
        	}        	
        };

        assert(count($this->pathComponents) >= 3);

        if (isset($_GET['dl']) && (int)$_GET['dl'] == 1) {
            $downloadAsFile = $this->pathComponents[2];
        } else {
            $downloadAsFile = null;
        }

        $requestName = substr($this->pathComponents[2], 0, strpos($this->pathComponents[2], '.'));
        $requestData = explode('_', $requestName);
        assert(count($requestData) >= 1);
        $type = $requestData[1];
        $outputData = null;
        if ($type == 'resource') {
            assert(count($requestData) >= 4);
            $sciName = $requestData[2];
            $sciInternalId = $requestData[3];
            $sciData = getAndCheckSciData($this, $sciName);

            if (!isset($sciData->resources[$sciInternalId])) {
                throw new Exception(sprintf("could not get resource '%s' from sCi '%s'", $sciInternalId, $sciName));
            }

            $sciSingleEntry = $sciData->resources[$sciInternalId];

            $outputData = $sciSingleEntry;
            $outputData->url = /*$this->getAbsBaseURL() .*/ '/' . $sciData->resources_baseurl . '/' . $outputData->file;
            if (!isset($sciData->resources[$sciInternalId]->tool)) {
            	$outputData->tool = $sciData->tool;
            } else {
            	$outputData->tool = $sciData->resources[$sciInternalId]->tool;
            }
            $outputData->metadata = mergeObjects($sciData->metadata, $sciSingleEntry->metadata);
        } elseif ($type == 'resources') {
            assert(count($requestData) >= 3);
            $sciName = $requestData[2];
            $sciData = getAndCheckSciData($this, $sciName);

            $baseMeta = $sciData->metadata;
            $mergedResources = array();
            foreach ($sciData->resources as $res) {
                $mergedResMeta = mergeObjects($baseMeta, $res->metadata);
                $res->metadata = $mergedResMeta;
                array_push($mergedResources, $res);
            }
            $sciData->resources = $mergedResources;

            $outputData = $sciData;
            delKeys($outputData, $commonExportDelKeys);
        } elseif ($type == 'object') {
            assert(count($requestData) >= 2);
            $objId = $requestData[2];

            // get full object data
            $objData = $this->db->findSingleObjectById($objId, true);

            $fetchResourcesOfObject($objData);

            $outputData = $objData;
            delKeys($outputData, $commonExportDelKeys);
        } elseif ($type == 'objects') {
            // check if cache exists
            if ($this->cache->exists($this->pathComponents[2])) {
                // make a redirect directly to the cached file
                header("HTTP/1.1 307 Temporary Redirect");
                header("Location: " . $this->makeURL(array('cache', $this->pathComponents[2]), array(), false, false, false));
                exit;
            }
            
            //needs significantly more memory
            ini_set('memory_limit', '512M');

            // get full data
            $outputData = $this->db->findAllObjectsFullFetch();
            
            foreach ($outputData as $objData){
				$fetchResourcesOfObject($objData);
            }

            // also save to cache (as JSON file -> do not serialize!)
            $this->cache->save(
                $this->pathComponents[2],
                json_encode($outputData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
                false
            );
        } else {
            throw new Exception(sprintf("invalid JSON export request '%s'", $type));
        }

        return array($outputData, $downloadAsFile);
    }

    /**
     * Render object display by using a common base template and a collection specific template $tplFile
     *
     * @param stdClass $objData object data
     * @param array $sciBaseLinks base links to "single" view
     * @param array $sciData data from sCi files
     * @param array $sciThumbDirs directory of thumbs for each sCi key
     * @param string $tplFileTop collection specific template file "top"
     * @param string $tplFileMain collection specific template file "main"
     * @param string $tplFileRight collection specific template file "right"
     * @param object $objectViewDispDef object view display definition
     * @return string rendered HTML
     */
    private function renderObjectDisplay($objData, $sciBaseLinks, $sciData, $sciThumbDirs,
                                         $tplFileTop, $tplFileMain, $tplFileRight, $objectViewDispDef)
    {
        if (!$tplFileTop) $tplFileTop = '_default/object_display_top.php';
        if (!$tplFileMain) $tplFileMain = '_default/object_display_main.php';
        if (!$tplFileRight) $tplFileRight = '_default/object_display_right.php';

        $tpl = new stdClass();
        $partialTpl = new stdClass();

        if ($objectViewDispDef) {
            foreach (get_object_vars($objectViewDispDef) as $k => $v) {
                $partialTpl->$k = $v;
            }
        }

        $partialTpl->object_data = $objData;
        $partialTpl->sci_base_links = $sciBaseLinks;
        $partialTpl->sci_data = $sciData;
        $partialTpl->sci_thumbs = $sciThumbDirs;
        $partialTpl->proj_meta = $this->projMeta;

        $tpl->proj_meta = $this->projMeta;
        $tpl->object_data = $objData;
        $tpl->sci_data = $sciData;
        $tpl->sci_thumbs = $sciThumbDirs;
        $tpl->object_content_top = $this->renderPartialTemplate($partialTpl, $tplFileTop);
        $tpl->object_content_main = $this->renderPartialTemplate($partialTpl, $tplFileMain);
        $tpl->object_content_right = $this->renderPartialTemplate($partialTpl, $tplFileRight);
        $tpl->scitable_link = $this->makeURL($this->pathComponents, null);
        $tpl->collection_link = $this->makeURL([$this->projName,"search"],null);
        $tpl->collection_title = $this->projMeta->title;
        
        $objExportFileName = implode('_', array($this->projName, 'object', $objData->id)) . '.cite';
        $tpl->object_json_export_link = $this->makeURL(
            array($this->projName, 'export', $objExportFileName),
            array('dl' => 1)
        );

        return $this->renderPartialTemplate($tpl, 'object_display_base.php');
    }

    /**
     * Render single sCi dataset entry display for an entry specified by sCi $sciName and $sciInternalId.
     *
     * @param float $sciVers sCi version
     * @param string $sciName sCi identifier (e.g. "0815")
     * @param string $resBaseUrl base url for all resources in the sCi
     * @param int $sciInternalId ID for an entry *inside* the sCi data, e.g. entry number 3
     * @param string $relatedObjectID id of related object
     * @param array $sciData array with the data from the sCi file
     * @param array $sciMetadata assoc. array connected to this sCi. this was fetched from the DB
     * @param string $tplFile template file to use
     * @return string
     */
    private function renderSingleSCIDisplay($sciVers, $sciName, $resBaseUrl, $sciInternalId, $relatedObjectID, $sciData, $sciMetadata, $tplFile) {
        $tpl = new stdClass();
        $tpl->proj_meta = $this->projMeta;
		$tpl->sciData = $sciData[$sciInternalId];
        $tpl->related_object = $relatedObjectID;
        $tpl->sci_version = $sciVers;
        $tpl->sci_metadata = $sciMetadata;
        $tpl->sci_name = $sciName;
        
        if (isset($sciMetadata->{"General Information"})) {
            $tpl->title = $sciMetadata->{"General Information"}->Title;
        } else {
            $tpl->title = $sciName;
        }

        if (count($sciData) > 1) {
            $tpl->title .= ' (' . ($sciInternalId + 1) . '/' . count($sciData) . ')';
            
            $pathComponentsWithoutID = [$this->pathComponents[0], $this->pathComponents[1], $this->pathComponents[2]];
            if ($sciInternalId > 0){
            	array_push($pathComponentsWithoutID, $sciInternalId-1);
            	$tpl->prevItem = $this->makeURL($pathComponentsWithoutID, null);
            }
            if ($sciInternalId < (count($sciData)-1)){
            	array_push($pathComponentsWithoutID, $sciInternalId+1);
            	$tpl->nextItem = $this->makeURL($pathComponentsWithoutID, null);
            }
        }

        if (isset($sciData[$sciInternalId]) && isset($sciData[$sciInternalId]->doi)) {
            $tpl->doi = $sciData[$sciInternalId]->doi;
        } else if (isset($sciMetadata->{"General Information"})) {
            $tpl->doi = @$sciMetadata->{"General Information"}->DOI;
        } else {
            $tpl->doi = '';
        }

        $tpl->subtitle = @$sciMetadata->{"General Information"}->Subtitle;

        if ($sciVers == '1.0') {
            $tpl->sci_file_url = $this->getURLToSCIFile($sciName);
            $tpl->single_sci_download = $tpl->sci_file_url;
        } else {
            $sciGenName = $this->projName . '_resource_' . $sciName . '_' . $sciInternalId . '.cite';
            $tpl->sci_file_url = $this->makeURL(array($this->projName, 'export', $sciGenName), array());
            $tpl->single_sci_download = $this->makeURL(array($this->projName, 'export', $sciGenName), array('dl'=>1));
        }
        $tpl->sci_file_item = $sciInternalId;
        $resDownloads = array();

        $mainFile = new stdClass();
        if ($sciVers == '1.0') {
            if (substr($sciData[$sciInternalId]->url, 0, 4) == 'http' || substr($sciData[$sciInternalId]->url, 0, 2) == '//') {
            	$mainFile->url = $sciData[$sciInternalId]->url;
            } else {
                $mainFile->url = /*$this->getAbsBaseURL() .*/ '/' . $sciData[$sciInternalId]->url;
            }
            array_push($resDownloads, $mainFile);
        } else {
            $dlBaseUrl = /*$this->getAbsBaseURL() .*/ '/' . $resBaseUrl . '/';
            $mainFile->url = $dlBaseUrl . $sciData[$sciInternalId]->file;
            array_push($resDownloads, $mainFile);
            if (isset($sciData[$sciInternalId]->alternative_files)) {
                foreach ($sciData[$sciInternalId]->alternative_files as $altFile) {
                	$alternativeFile = new stdClass();
                	if (is_string($altFile)){
                		$alternativeFile->url = $dlBaseUrl . $altFile;
                	} else {
                		$alternativeFile->type = $altFile->type;
                		$alternativeFile->url = $dlBaseUrl . $altFile->file;
                	}
                    array_push($resDownloads, $alternativeFile);
                }
            }
        }

        $tpl->single_resource_downloads = array();
        foreach ($resDownloads as $dlFile) {
        	if (isset($dlFile->type)){
        		$dlFileTitle = $dlFile->type;
        	} else {
        		$dlFileTitle = basename($dlFile->url);
        	}
            $tpl->single_resource_downloads[$dlFileTitle] = $dlFile->url;
        }

        // $tpl->viewer_url = $sciEntry->tool . '?sci=' . $this->getURLToSCIFile($sciName) . '&item=' . $sciInternalId;
        $tpl->scitable_link = $this->makeURL($this->pathComponents, null);

        if (isset($sciMetadata->{"General Information"})) {
            $citationArr = array(
                @$sciMetadata->{"General Information"}->Creator,
                @$sciMetadata->{"General Information"}->Title,
                @$sciMetadata->{"General Information"}->Subtitle,
                @$sciMetadata->{"General Information"}->{"Publication Year"},
                $tpl->proj_meta->title,
                @$sciMetadata->{"General Information"}->Publisher
            );

            if (is_string($tpl->doi) && strlen($tpl->doi) > 0) {
                $citationArr[] = 'DOI: ' . $tpl->doi;
            }
            
            $tpl->citation_str = "";
            $first = true;
            foreach($citationArr as $citationPart){
            	if (isset($citationPart) && (strlen($citationPart)>0)){
            		if (!$first){
            			$tpl->citation_str .= ", ";
            		} else {
            			$first = false; 
            		}
            		$tpl->citation_str .= $citationPart;
            	}
            }
        } else {
            $tpl->citation_str = '';
        }
        $tpl->collection_link = $this->makeURL([$this->projName,"search"],null);
        $tpl->collection_title = $this->projMeta->title;

        return $this->renderPartialTemplate($tpl, $tplFile);
    }


    /**
     * Main script entry point.
     * @param array $params GET parameters
     * @throws Exception
     */
    public function run(array $params) {
        // run parent method to set basic things
        parent::run($params);

        $sciMetaData = null;
        $title = null;
        $downloadAsFile = null;
        $outputType = 'html';
        
        $additionalJSFiles = [];
        $additionalCSSFiles = [];

        // create hiearchy handler if necessary (DEPRECATED)
        $numPathComp = count($this->pathComponents);

        if ($numPathComp > 1) { // we have a path to traverse through it
            $pathAction = $this->pathComponents[1];

            // don't redirect export requests to CitableHandler
            if ($pathAction !== 'export'){
	            // check what client requested
	            // if it is not a web-browser or bot, redirect to JSON (REST-API)
            	$browser = new Browser();
	            $isBrowserOrBot = $browser->checkBrowsers();
	            
	            if (!$isBrowserOrBot){
	            	$origPath = $_SERVER['REQUEST_URI'];
	            	$origPath = str_replace("collection/", "", $origPath);
	            
	            	header('Location: /CitableHandler'.$origPath);
	            	die();
	            }
            }
            
            if ($pathAction == 'object') { // we show an object
                list($contentData, $title, $sciMetaData) = $this->createContentForObjectDisplay();
                $pageType = $pathAction;

            } else if ($pathAction == 'single') {  // we show a single dataset / figure / etc. from sCi file
                list($contentData, $title, $sciMetaData) = $this->createContentForSingleSCIDisplay();
                $pageType = $pathAction;
                
                $additionalJSFiles = array_merge($additionalJSFiles, ['bigscreen.min.js', 'sci_v2.js']);
            } elseif ($pathAction == 'export') {
                list($contentData, $downloadAsFile) = $this->createContentForJSONExport();
                $pageType = $pathAction;
                $outputType = 'json';
            } else {    // we traverse through the path in the hierarchy
                throw new Exception("unknown action '" . $pathAction .  "'");
            }
        } else {
            throw new Exception("no path specified.");
        }

        if ($outputType == 'json') {
            $this->renderJSONContent($contentData, $downloadAsFile);
        } else {
            $this->renderToBaseTemplate($contentData, $title, $pageType, $sciMetaData, $sciMetaData == null ? 'base.php' : 'base_sci.php', $additionalJSFiles, $additionalCSSFiles);
        }
    }
}

// create main script class & run it
$browse = new ETRepoBrowse();
$browse->run($_GET);
