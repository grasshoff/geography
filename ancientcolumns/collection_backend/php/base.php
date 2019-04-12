<?php
include "composer/vendor/autoload.php";

require_once('cache.php');

/**
 * Class ETRepoBase. Base class for ET Repo application logic handlers. Contains helper functions and the main
 * entry point: the run() method.
 */
class ETRepoBase {
    /**
     * Database API for the overall configuration DB.
     * @var ETRepoDb
     */
    protected $confDb;

    /**
     * Main configuration document from the  overall configuration DB.
     * @var stdClass
     */
    protected $confDoc;

    /**
     * Database API for the collection DB.
     * @var ETRepoDb
     */
    protected $db;

    /**
     * Database name.
     * @var string
     */
    protected $dbName;

    /**
     * Passed GET parameters.
     * @var array
     */
    protected $params;

    /**
     * Components of the 'path' parameter as array.
     * @var array
     */
    protected $pathComponents;

    /**
     * Collection project name, e.g. BSDP.
     * @var string
     */
    protected $projName;

    /**
     * Collection project meta data as document fetched from the CouchDB.
     * @var StdClass
     */
    protected $projMeta;

    /**
     * Cache handler.
     * @var ETRepoCache
     */
    protected $cache = null;


    /**
     * Helper function to render a partial template and return the HTML code result.
     *
     * @param stdClass $tplData data to pass to the template
     * @param string $tplFile template file
     * @return string rendered HTML code
     */
    protected function renderPartialTemplate($tplData, $tplFile) {
        ob_start(); // start output buffer

        // set template data
        $tpl = $tplData;

        // include template file
        require('php/tpl/' . $tplFile);

        // get the result
        $s = ob_get_contents();
        ob_end_clean(); // cleanup output buffer

        return $s;
    }


    /**
     * Render the content data to the base template. Will produce (print) the output directly.
     * @param string $contentData HTML string with the main content
     * @param string $title HTML document title
     * @param string $pageType page type (e.g. startpage, hierarchy, etc. -- used for CSS)
     * @param string $sciMetaData metadata from sCi object
     * @param string $baseTplFile base template file
     * @param array $additionalJSFiles additional JavaScript files to load (in /js/ folder)
     */
    protected function renderToBaseTemplate($contentData, $title=null, $pageType='', $sciMetaData=null,
                                            $baseTplFile='base.php', array $additionalJSFiles=array(),
                                            array $additionalCSSFiles=array()) {
        // set template data
        $tpl = new stdClass();

        if (!$title) {
            $title = ETRepoConf::$SITE_TITLE_DEFAULT;
        }
        $tpl->title = sprintf(ETRepoConf::$SITE_TITLE_FMT, $title);
        
        $tpl->et_url = ETRepoConf::$ET_URL;
        $tpl->base_url = $this->getAbsBaseURL();
        $tpl->content = $contentData;
        $tpl->sci_metadata = $sciMetaData;
        $tpl->page_type = $pageType;

        $baseCSSFiles = array('bootstrap.min.css', 'theme.css', 'menu.css', 'perfect-scrollbar.css', 'fonts.css');
        $tpl->css_files = array_merge($baseCSSFiles, $additionalCSSFiles);

        $baseJSFiles = array('jquery.min.js', 'popper.min.js', 'bootstrap.min.js', 'perfect-scrollbar.min.js', 'theme.js');
        $tpl->js_files = array_merge($baseJSFiles, $additionalJSFiles);

        // include the template base file
        require('php/tpl/' . $baseTplFile);
        
    }
    

    /**
     * Render JSON content: encode $contentData and send to client as "application/json"
     * @param mixed $contentData data to be encoded as JSON
     * @param null $downloadAsFile optionally let the browser download this JSON with the specified file name
     */
    protected function renderJSONContent($contentData, $downloadAsFile=null) {
        if ($downloadAsFile) {
            header('Content-Disposition: attachment; filename=' . $downloadAsFile);
        }
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($contentData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }


    /**
     * Return the absolute URL to the current host.
     * @return string absolute URL to the current host
     */
    protected function getAbsBaseURL() {
        $scheme = isset($_SERVER['HTTPS']) && strlen($_SERVER['HTTPS']) > 0 ? 'https' : 'http';
        $url = $scheme . '://' . ETRepoConf::$SERVER_NAME;

        return $url;
    }


    /**
     * Helper function to create an ET Repo URL.
     *
     * @param array $pathComponents target path
     * @param array $params additional parameters
     * @param bool $absURL make absolute URL?
     * @param bool $htmlEncode encode HTML special chars in the URL?
     * @param bool $addRewritePrefix add prefix when URL rewrite is enabled
     * @return string result URL
     */
    protected function makeURL($pathComponents = array(), $params = array(), $absURL = false, $htmlEncode = true, $addRewritePrefix = true) {
        if ($absURL) {  // prepend the server URL
            $url = $this->getAbsBaseURL();
        } else {
            $url = '';
        }

        if (!ETRepoConf::$ENABLE_REWRITE_URLS) {
            // set main script
            $url .= '/browse.php';

            // set the path parameter
            if (is_array($pathComponents) && count($pathComponents) > 0) {
                $url .= '?path=' . $this->makeURLPathParam($pathComponents);
            }
        } else {
            $url .= '/';

            // set the path parameter
            if (is_array($pathComponents) && count($pathComponents) > 0) {
                if ($addRewritePrefix) {
                    $url .= ETRepoConf::$REWRITE_URLS_PREFIX;
                }
                $url .= $this->makeURLPathParam($pathComponents, false);
            }
        }

        // set additional parameters
        if (is_array($params) && count($params) > 0) {
            if (ETRepoConf::$ENABLE_REWRITE_URLS) {
                $url .= '?';
            } else {
                $url .= '&';
            }

            $kvArray = array();
            foreach ($params as $k => $v) {
                array_push($kvArray, $k . '=' . $v);
            }

            $url .= implode('&', $kvArray);
        }

        // encode HTML if necessary
        if ($htmlEncode) {
            $url = htmlentities($url);
        }

        return $url;
    }


    /**
     * Construct "path" parameter from $pathComponents for URLs
     * @param array $pathComponents path components
     * @param bool $urlEncode use urlencode()
     * @return string path parameter
     */
    protected function makeURLPathParam(array $pathComponents, $urlEncode=true) {
        $pathStr = implode('/', $pathComponents);
        if ($urlEncode) {
            $pathStr = urlencode($pathStr);
        }

        return $pathStr;
    }


    /**
     * Return path to thumbnails directory of a given sCi identifier name.
     *@param string $sciName identifier name of the sCi file, e.g. "0012"
     * @param bool $asAbsURL absolute URL?
     * @return string path to thumbnails directory of a given sCi identifier name
     */
    public function getGalleryThumbsPath($sciName, $asAbsURL=false) {
        if ($asAbsURL) {
            $url = $this->getAbsBaseURL();
            if (!strEndsWith($url, '/')) {
                $url .= '/';
            }
        } else {
            $url = '';
        }

        return $url . sprintf($this->projMeta->gallery_thumbs_url_format, $sciName);
    }


    /**
     * Get path to thumbnail directory for thumbs in the hierarchy display
     *
     * @param int $id object id
     * @param bool $asAbsURL return absolute URL if set to true
     * @return string|null
     */
    public function getHierarchyThumbsPath($id, $asAbsURL=false) {
        if (!isset($this->projMeta->hierarchy_thumbs_url_format)) {
            return null;
        }

        if ($asAbsURL) {
            $url = $this->getAbsBaseURL();
            if (!strEndsWith($url, '/')) {
                $url .= '/';
            }
        } else {
            $url = '';
        }

        // oh mann...
        $placeHolders = array();
        preg_match_all('(%s)', $this->projMeta->hierarchy_thumbs_url_format, $placeHolders);
        if (count($placeHolders[0]) == 2) {
            return $url . sprintf($this->projMeta->hierarchy_thumbs_url_format, '' . $id, '' . $id);
        } else if (count($placeHolders[0]) == 1) {
            return $url . sprintf($this->projMeta->hierarchy_thumbs_url_format, '' . $id);
        } else {
            return $url . $this->projMeta->hierarchy_thumbs_url_format;
        }
    }


    /**
     * Return a full sCi file name from a sCi identifier, e.g return "BSDP0815.sCi" for identifier "0815"
     *
     * @deprecated
     * @param string $sciName sCi identifier name, e.g. "0815"
     * @return string full sCi file name, e.g. "BSDP0815.sCi"
     */
    public function getSCIFileNameFromSCIName($sciName) {
        return sprintf($this->projMeta->sci_repos_file_format, $sciName);
    }


    /**
     * Return a full sCi name from a sCi identifier, e.g return "BSDP0815" for identifier "0815". This is the
     * same as getSCIFileNameFromSCIName() but without ".sCi" extension.
     *
     * @deprecated
     * @param string $sciName sCi identifier name, e.g. "0815"
     * @return string full sCi name, e.g. "BSDP0815"
     */
    public function getFullSCINameFromSCIName($sciName) {
        return pathinfo($this->getSCIFileNameFromSCIName($sciName), PATHINFO_FILENAME);
    }


    /**
     * Construct the path on the local file system to an sCi file of the current collection.
     *
     * @deprecated
     * @param string $sciName identifier name of the sCi file, e.g. "0012"
     * @return string path to the sCi file, e.g. "/var/repositories/COLL/ReposCOLL/COLL0012/COLL0012.sCi"
     */
    public function getPathToSCIFile($sciName) {
        if (!isset($this->projMeta->sci_repos_base_path_format->{ETRepoConf::$SERVER_CONTEXT})
            || strlen($this->projMeta->sci_repos_base_path_format->{ETRepoConf::$SERVER_CONTEXT}) <= 0)
        {
            throw new Exception('No sci_repos_base_path_format in collection metadata for server context ' . ETRepoConf::$SERVER_CONTEXT);
        }

        $pathFmt = $this->projMeta->sci_repos_base_path_format->{ETRepoConf::$SERVER_CONTEXT};
        $path = sprintf($pathFmt, $sciName);
        $path .= sprintf($this->projMeta->sci_repos_file_format, $sciName);

        return $path;
    }


    /**
     * Construct the URL to an sCi file of the current collection.
     *
     * @deprecated
     * @param string $sciName identifier name of the sCi file, e.g. "0012"
     * @return string absolute URL to the sCi file, e.g. "http://host.com/COLL/ReposCOLL/COLL0012/COLL0012.sCi"
     */
    public function getURLToSCIFile($sciName) {
        if (!strStartsWith($this->projMeta->sci_repos_base_url_format, 'http://') &&
            !strStartsWith($this->projMeta->sci_repos_base_url_format, 'https://'))
        {
            $url = $this->getAbsBaseURL() . '/';
        } else {
            $url = '';
        }

        $url .= sprintf($this->projMeta->sci_repos_base_url_format, $sciName);
        $url .= sprintf($this->projMeta->sci_repos_file_format, $sciName);

        return $url;
    }


    /**
     * Return the JSON-decoded object from an sCi file. Return false if fetching the sCi file fails
     *
     * @param string $sci either identifier name of the sCi file, e.g. "0012", or already an absolute URL to the sCi
     *                    file. In this case, $constructAbsURLToSCIFile must be set to false
     * @param bool $constructAbsURLToSCIFile if true, it will construct a full URL to the sCi file using the identifier
     *                                       name $sci
     * @return mixed JSON decoded object or false
     */
    public function getSCIContents($sci, $constructAbsURLToSCIFile=true) {
        // load data from sci-document when using sci_version 2.0 or newer
        if (isset($this->projMeta->uses_sci_version) && $this->projMeta->uses_sci_version > 1.0 && !isset($this->projMeta->sci_repos_base_url_format)) {   // new sCi: fetch it from the DB
            $sciDoc = $this->db->fetchDocById('sci-' . $sci);
            return $sciDoc->body;
        }

        /** the following is @deprecated -- load data from sCi file */
        if ($constructAbsURLToSCIFile) {
            try {
                $sci = $this->getPathToSCIFile($sci);
            } catch (Exception $e) {
                $sci = $this->getURLToSCIFile($sci);
            }
        }

        $sciText = @file_get_contents($sci);
        if ($sciText === false) {
            return false;
        }

        return json_decode($sciText);
    }


    /**
     * Return an array of all collection shortnames (BSDP, MAPD, etc.)
     * @return array array of all collection shortnames (BSDP, MAPD, etc.)
     */
    protected function getAllCollectionNames() {
		$collMetas = $this->getAllCollectionsMeta();
		
		$collsToDisplay = Array();
		foreach($collMetas as $collName => $collDb ){
			if (!isset($collDb->visible_on_startpage) || ($collDb->visible_on_startpage == TRUE)){
				$collsToDisplay[$collName] = $this->confDoc->collections->{$collName};
			}			
		}
		$tst = get_object_vars($this->confDoc->collections);
		
    	return array_keys($collsToDisplay);
    }


    /**
     * Fetch metadata for all collections from DB and return it as array.
     * This function can load cached data.
     *
     * @return array array with collection name => collection metadata object mapping
     * @throws Exception
     */
    protected function getAllCollectionsMeta() {
        // try to load the collections metadata from cache
        if ($this->cache->exists('collections_meta')) {
            return $this->cache->retrieve('collections_meta');
        }

        $db = null;
        $collMeta = array();
        // now go through each collection and select it's DB. this is slow, hence it is cached.
        foreach ($this->confDoc->collections as $collName => $collDb) {
            if (!$db) {
                $db = ETRepoDbCreateInstance($collDb);
            } else {
                $db->changeDb($collDb);
            }

            try {
                $metaDoc = $db->fetchDocById('meta');
                if (isset($metaDoc->body)) {
                	
                	if (isset($metaDoc->body->visible_on_startpage) && ($metaDoc->body->visible_on_startpage == false)){
                		 continue;
                	}
                    $collMeta[$collName] = $metaDoc->body;
                }
            } catch (SagCouchException $e) {}   // ignore "document not found" errors when "meta" is not found

        }

        // save to cache
        $this->cache->save('collections_meta', $collMeta);

        return $collMeta;
    }

    
    /*bjoern*/
    protected function getAllNotebooks2() {
    	/*if ($this->cache->exists('all_notebooks')) {
    		return $this->cache->retrieve('all_notebooks');
    	}*/
    	 
    	 
 
    	//$res = $this->db->fetchView(self::$HIERARCHY_FILTER_NOTEBOOKS);;
    	$db = null;
    	$collMeta = array();
    	$collMeta['rows'] = [];
    	$collMeta['total_indices'] = [];
    	$collMeta['total_count'] = 0;
    	 
    	$collMeta['fetched_indices'] = [];
    	 
    	foreach ($this->confDoc->collections as $collName => $collDb) {
    		if (!$db) {
    			$db = ETRepoDbCreateInstance($collDb);
    		} else {
    			$db->changeDb($collDb);
    		}

			try {
                $metaDoc = $db->fetchView('get_notebooks');
                $res = $db->fetchView('get_notebooks');
                
                foreach ($res->body->rows as $row) {
                	list($crit, $val) = $row->key;
                	if (!isset($counts[$crit])) {
                		$counts["rows"] = array();
                		$counts["total_count"]=+1;
                
                	}
                	array_push($counts["rows"], ['id' => trim($row->key,"'"),'title'=>$row->value,'thumb'=>"img/notebook_picto_rgb_transparent.png"]);
                	array_push($counts["total_indices"], [$row->key]);
                
                }
                
                
                if (isset($metaDoc->body)) {
                	if ($metaDoc->body->total_rows >= 1) {
	                	if (isset($metaDoc->body->visible_on_startpage) && ($metaDoc->body->visible_on_startpage == false)){
	                		 continue;
	                	}
	                    array_push($collMeta['rows'],$metaDoc->body->rows);
	                    #array_push($colMeta['total_indices'],"test");
	                    array_push($counts['total_indices'], [$metaDoc->body->rows]);
	                    $counts["total_count"]=+1;
	                     
                	}
                }
            } catch (SagCouchException $e) {}   // ignore "document not found" errors when "meta" is not found
    		
    	}
	return $collMeta;
    }
    	/**
     * Base for main script entry point. Will handle the GET parameters passed as $params and set up basic member
     * variables.
     * @param array $params GET parameters
     * @throws Exception on invalid path/parameters
     */
    public function run(array $params) {
        $this->confDb = ETRepoDbCreateInstance(ETRepoConf::$CONFIG_DB);
        $this->confDoc = $this->confDb->fetchDocById(ETRepoConf::$CONFIG_DOC)->body;

        $this->cache = new ETRepoCache();

        // get "path" from GET parameters
        $path = isset($params['path']) ? sanitizeParamPath($params['path']) : null;

        if (!is_null($path)) {
            //check for (unneeded) ending slash
            if (strEndsWith($path, '/')){
                $path = substr($path, 0, strlen($path) -1);
            }

            $pathComponents = explode('/', $path);
            
            $this->pathComponents = $pathComponents;
            
            // the first part is the project name, e.g. "BSDP"
            if (count($this->pathComponents) <= 0) {
                throw new Exception('no valid path provided');
            }
            $this->projName = $this->pathComponents[0];

            if (!isset($this->confDoc->collections->{$this->projName})) {
                throw new Exception('unknown project: ' . $this->projName . print_r($this->pathComponents));
            }

            $this->dbName = $this->confDoc->collections->{$this->projName};

            // the rest is the hierarchy path
            $this->params['path'] = implode('/', array_slice($this->pathComponents, 1));

            // connect to the CouchDB
            $this->db = ETRepoDbCreateInstance($this->dbName);

            // get basic information
            $this->projMeta = $this->db->fetchDocById('meta')->body;
            if (!isset($this->projMeta->uses_sci_version)) {
                $this->projMeta->uses_sci_version = 1.0;
            }
            if (isset($this->projMeta->default_thumb_extension)) {
                define('THUMBS_DEFAULT_FILE_EXT', $this->projMeta->default_thumb_extension);
            }
        }
    }
}