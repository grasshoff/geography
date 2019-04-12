<?php
/**
 * Edition Topoi Repository Collections entry point for filter frontend: Browse through available collections *or*
 * research objects inside individual collections.
 */

require_once('php/tools.php');
require_once('php/base.php');
require_once('php/db.php');
require_once('php/hierarchy.php');

require_once('php/lib/Browser/Browser.php');

/**
 * Class to handle filter frontend.
 */
class ETRepoFilterFrontend extends ETRepoBase {
    /**
     * Hierarchy handler with tools for browsing through a collection's filter hierarchy.
     * @var ETRepoHierarchy
     */
    private $hierarchyHandler;


    /**
     * Render common "top"/header part of a collection display template.
     * @param string $title             collection display title
     * @param string $activeMenuItem    name of the menu item to be set to "active"
     * @return string rendered template HTML
     */
    private function renderCollectionTopPartial($title, $activeMenuItem) {
        $tpl = new stdClass();

        // define menu items
        $menuItems = array(
            'overview' => 'Overview',
            'metadata' => 'Metadata',
            'search' => 'Search',
        		
        );

        // set title
        $tpl->title = $title;

        // define top menu and image
        $tpl->top_menu = array();
        foreach ($menuItems as $name => $title) {
            // add menu item
            array_push($tpl->top_menu, array(
                'title' => $title,
                'url' => $this->makeURL(array($this->projName, $name)), // with pretty URLs
                // 'url' => '/filter-frontend.php?path=' . $this->makeURLPathParam(array($this->projName, $name)),  // w/o pretty URLs
                'class' => $activeMenuItem == $name ? 'active' : ''
            ));
        }

        $tpl->top_image = $this->projMeta->service_images_path . $this->projMeta->startpage_main_img;
        $tpl->collection_image_copyright = isset($this->projMeta->collection_image_copyright) ? $this->projMeta->collection_image_copyright : null;

        return $this->renderPartialTemplate($tpl, 'collection_top.php');
    }


    /**
     * Common function to render collection overview or metadata display.
     * @param string $title collection title
     * @param array $sections content sections
     * @param array $infoBoxes sidebar info-boxes
     * @param string $tplFile template file to use as partial
     * @param null|string $activeMenuItem selected menu item
     * @return string rendered collection overview or metadata HTML
     */
    private function renderCollectionData($title, array $sections, array $infoBoxes, $tplFile, $activeMenuItem=null) {
        $tpl = new stdClass();

        // render top menu
        $tpl->top_html = $this->renderCollectionTopPartial($title, $activeMenuItem);

        // render sidebar
        $tpl->sidebar_menu = array();
        foreach ($sections as $sec) {
            array_push($tpl->sidebar_menu, array(
                'title' => $sec['title'],
                'url' => '#' . sluggify($sec['title'])
            ));
        }

        $tpl->sidebar_infoboxes = $infoBoxes;
		//json object
        if ($activeMenuItem == 'overview') {
            // download collection json link
            $tpl->download_collection_json_link = $this->makeURL(
                array($this->projName, 'export', $this->projName . '_objects.cite'),
                array('dl' => 1)
            );
        }

        $tpl->sidebar_html = $this->renderPartialTemplate($tpl, 'collection_sidebar.php');

        // prepare main content
        $tpl->content_sections = $sections;

        return $this->renderPartialTemplate($tpl, $tplFile);
    }


    /**
     * Render HTML for filtering with hierarchies defined in $hierarchyDefs
     * @param string $title collection title
     * @param string $context rendering context: filter_collections or filter_inside_collection
     * @param string $tplFile template file to use
     * @param array $hierarchyDefs hierarchy definitions (title => hierarchy data)
     * @return string rendered HTML
     */
    private function renderFilterDisplay($title, $context, array $hierarchyDefs, $tplFile) {
        $tpl = new stdClass();

        if ($context == 'filter_in_collection') {
            // render top menu
            
            $tpl->top_html = $this->renderCollectionTopPartial($title, 'search');
            
            }
            
        
        if ($context == 'notebooks_in_collection') {
        	// render top menu
        	$tpl->top_html = $this->renderCollectionTopPartial($title, 'notebooks');
        }
       	// download collection json link
            // download collection json link
            $tpl->download_collection_json_link = $this->makeURL(
                array($this->projName, 'export', $this->projName . '_objects.cite'),
                array('dl' => 1)
            );
       
	    $tpl->context = $context;
        $tpl->hierarchy_defs = $hierarchyDefs;
        $tpl->filter_select_nav = $this->renderPartialTemplate($tpl, 'filter_select_nav.php');

        return $this->renderPartialTemplate($tpl, $tplFile);
    }


    
    
    /**
     * Load data and render HTML for collections filter display
     * @return string rendered HMTL for collections filter display
     */
    private function createContentForCollectionsFilter() {
        // get (cached) collection metadata for all collections
        $collMeta = $this->getAllCollectionsMeta();

        $availableCriteria = array();
        $db = null;

        // get all available criteria from all available collections
        foreach (ETRepoConf::$FILTER_COLLECTIONS_CRITERIA as $critKey => $title) {
            $availableCriteria[$critKey] = array();

            foreach ($collMeta as $collName => $meta) {
                if (isset($meta->filter) && isset($meta->filter->$critKey)) {
                    foreach ($meta->filter->$critKey as $critVal) {
                        if (!in_array($critVal, $availableCriteria[$critKey])) {
                            array_push($availableCriteria[$critKey], $critVal);
                        }
                    }
                }
            }
        }

        // build the hierarchy from the above criteria
        $hierarchyDef = array();
        foreach (ETRepoConf::$FILTER_COLLECTIONS_CRITERIA as $critKey => $critTitle) {
            if ($critKey == 'type') continue;

            $hierarchyDef[$critTitle] = array(
                'branches' => array()
            );
            $branch = &$hierarchyDef[$critTitle]['branches'];

            // create new leafs for each available filter criterion
            foreach ($availableCriteria[$critKey] as $critVal) {
                $k = sluggify($critVal);
                $branch[$k] = array(
                    'title' => $critVal, //Title in refine your search
                    'leaf' => array(
                        'filter_criterion' => array($critKey, $critVal)
                    )
                );
            }
        }

        // render the filter display for context 'filter_collections'
        $contentData = $this->renderFilterDisplay(null, 'filter_collections', $hierarchyDef, 'filter_collection.php');

        return $contentData;
    }


    /**
     * Load data and render HTML for collection overview display
     * @return array array of title, context ('filter_collection') and rendered HTML
     */
    private function createContentForCollectionOverview() {
        $title = $this->projMeta->title;

        /*** 1. fill sections ***/
        // additional "further information" text from "additional_information_text" and "additional_information_links"
        // metadata attribute
        $furtherInfoText = !empty($this->projMeta->additional_information_text) ? sprintf('<p>%s</p>', ETRepoHTMLPurifier::cleanHTML($this->projMeta->additional_information_text)) : '';
        if (isset($this->projMeta->additional_information_links) && count($this->projMeta->additional_information_links) > 0) {
            $furtherInfoList = formatLinkList($this->projMeta->additional_information_links);
        } else {
            $furtherInfoList = null;
        }

        // licensing information from "license" attribute in metadata
        $licenseStr = "";
        if (isset($this->projMeta->license->name) && (strlen($this->projMeta->license->name)>0)){
	        $licenseName = isset($this->projMeta->license->fullname) ? $this->projMeta->license->fullname : $this->projMeta->license->name;
	        $licenseStr = sprintf('<p><a href="%s" target="_blank">%s</a></p>',
	            $this->projMeta->license->url, htmlentities($licenseName));
        }
        if (isset($this->projMeta->license->additional_text) && $this->projMeta->license->additional_text) {
            $licenseStr .= sprintf('<p>%s</p>', $this->projMeta->license->additional_text);
        }

        // create all sections
        $sections = array();
        if (!empty($this->projMeta->abstract)) {
            array_push($sections, array(
                'title' => 'Abstract',
                'content_html' => $this->projMeta->abstract
            ));
        }
        if (!empty($this->projMeta->description)) {
            array_push($sections, array(
                'title' => 'Description',
                'content_html' => $this->projMeta->description
            ));
        }
        if ($furtherInfoList || $furtherInfoText) {
        	$contentHTML = "";
        	if ($furtherInfoText){
        		$contentHTML .= $furtherInfoText;
        	}
            if ($furtherInfoList){
        		$contentHTML .= $furtherInfoList;
        	}
        	
        	array_push($sections, array(
                'title' => 'Further information',
                'content_html' => $contentHTML
            ));
        }
        if (isset($this->projMeta->research_group)) {
        	if (is_array($this->projMeta->research_group) && is_array($this->projMeta->research_group[0])){
        		foreach ($this->projMeta->research_group as $index => $thisResearchGroup){
        			array_push($sections, array(
        					'title' => 'Research Group Phase '.($index+1),
        					'content_html' => htmlentities(implode(', ', $thisResearchGroup))
        			));
        		}
        	} else {
	            array_push($sections, array(
	                'title' => 'Research Group',
	                'content_html' => htmlentities(implode(', ', $this->projMeta->research_group))
	            ));
        	}
        }
        if ($licenseStr) {
            array_push($sections, array(
                'title' => 'Conditions for Use',
                'content_html' => $licenseStr
            ));
        }
        
        $publication_date = false;
        //get publication date, this is a real pain and unclear why it was made this way
        foreach ($this->projMeta->metadata_blocks as $metadataBlock){
        	if ($metadataBlock->title === "Annotations"){
        		foreach ($metadataBlock->data as $metadataBlockData){
        			//prevent overriding if update date is first
        			if ( ($metadataBlockData[0] === "Entry Date") && ($publication_date == false) ){
        				$publication_date = $metadataBlockData[1];
        			}
        			if ($metadataBlockData[0] === "Last Update"){
        				$publication_date = $metadataBlockData[1];
        			}
        		}
        		break;
        	}
        }
        
        $publication_date = date_parse($publication_date);
        if (($publication_date !== false) && ($publication_date["error_count"] > 0)){
        	$publication_date = false;
        }
        
        $publicationYear = false;
        if ($publication_date !== false){
        	$publicationYear = $publication_date["year"];
        }
        
        $citation = implode(', ', multiArrayJoin($this->projMeta->research_group));
        $citation .= ", " . $this->projMeta->title;
        if ($publicationYear !== false){
        	$citation .= ", " . $publicationYear;
        }
        $citation .= ", Edition Topoi";
        
        if (!empty($this->projMeta->doi)){
        	$citation .= ", DOI: ".$this->projMeta->doi;
        }

        /*** 2. fill info-boxes ***/
        $infoBoxes = array(
            array(
                'title' => 'Institutions',
                'content_html' => htmlentities(implode(', ', $this->projMeta->institutions))
            ),
            array(
                'title' => 'Keywords',
                'content_html' => htmlentities(implode(', ', $this->projMeta->keywords))
            )
        );
        
        if (!empty($this->projMeta->doi)){
        	array_push($infoBoxes,
				array(
					'title' => 'DOI',
					'content_html' => htmlentities($this->projMeta->doi)
				)
        	);
        }
        
        array_push($infoBoxes,
			array(
				'title' => 'Citation',
				'content_html' => htmlentities($citation)
			)
		);

        if (isset($this->projMeta->digital_editors) && is_array($this->projMeta->digital_editors) && count($this->projMeta->digital_editors) > 0) {
            array_push($infoBoxes, array(
                'title' => 'Contributors',
                'content_html' => htmlentities(implode(', ', $this->projMeta->digital_editors))
            ));
        }

        /*** 2. render content ***/
        $contentData = $this->renderCollectionData($title, $sections, $infoBoxes, 'collection_overview.php', 'overview');

        return array($title, 'filter_overview', $contentData);
    }


    /**
     * Load data and render HTML for collection metadata display
     * @return array array of title, context ('filter_metadata') and rendered HTML
     */
    private function createContentForCollectionMetadata() {
        $title = $this->projMeta->title;

        // fill sections
        $sections = array();

        // general information section
        $genInfo = new stdClass();
        $genInfo->title = 'General Information';
        $genInfo->data = array_merge(
            array(
                array('Repository Name', $this->projMeta->title),
                array('Additional Name', $this->projMeta->shorttitle)
            ),
            $this->projMeta->general_information
        );

        // license information section
        $licInfo = new stdClass();
        $licInfo->title = 'Conditions for Use';
        $licInfo->data = array();
        
        if (isset($this->projMeta->license->name) && (strlen($this->projMeta->license->name)>0)) {
        	array_push($licInfo->data,
            	array('License', sprintf('<a href="%s" target="_blank">%s</a>',
                	$this->projMeta->license->url, htmlentities(isset($this->projMeta->license->fullname) ? $this->projMeta->license->fullname : $this->projMeta->license->name)), false
            	)
        	);
        }

        if (isset($this->projMeta->license->additional_text) && $this->projMeta->license->additional_text) {
            array_push($licInfo->data, array('Additional licensing information', $this->projMeta->license->additional_text));
        }

        // combine with all other metadata blocks
        $infoBlocks = array_merge(array($genInfo, $licInfo), $this->projMeta->metadata_blocks);

        foreach ($infoBlocks as $block) {
            $attribs = array();
            foreach ($block->data as $dataArr) {
                if (!isset($dataArr[2]) || !$dataArr[2]) {
                    $v = $dataArr[1];
                } else {
                    $v = htmlentities($dataArr[1]);
                }
                array_push($attribs, sprintf('<em>%s</em>: %s', htmlentities($dataArr[0]), $v));
            }

            array_push($sections, array(
                'title' => $block->title,
                'content_html' => implode('<br>', $attribs)
            ));
        }


        // render content
        $contentData = $this->renderCollectionData($title, $sections, array(), 'collection_overview.php', 'metadata');

        return array($title . ' / Metadata', 'filter_metadata', $contentData);
    }


    /**
     * Create content for filtering *inside* a collection
     * @return string rendered HTML
     * @throws Exception
     */
    private function createContentForFilterInCollection() {
        $hierarchyDataAttributes = $this->hierarchyHandler->getFilterHierarchyData('attributes');
        $hierarchyDataResources = $this->hierarchyHandler->getFilterHierarchyData('resources');

        $title = $this->projMeta->title;

        // render filter
        $contentData = $this->renderFilterDisplay(
            $title,
            'filter_in_collection',
            array(
                'Attributes' => $hierarchyDataAttributes,
                'Resource type' => $hierarchyDataResources,
            ),
            'filter_inside_collection.php'
        );

        return array($title . ' / Search', 'filter_in_collection', $contentData);
    }

    
    /**
     * Create content for filtering *inside* a collection
     * @return string rendered HTML
     * @throws Exception test
     */
    private function createContentForFilterInNotebooks() {
    	$hierarchyDataAttributes = $this->hierarchyHandler->getFilterHierarchyData('attributes');
    	$hierarchyDataResources = $this->hierarchyHandler->getFilterHierarchyData('resources');
    
    	$title = $this->projMeta->title;
    
    	// render filter
    	$contentData = $this->renderFilterDisplay(
    			$title,
    			'notebooks_in_collection',
    			array(
    			),
    			'notebooks_inside_collection.php'
    			);
    
    	return array($title . ' / Notebooks', 'notebooks_in_collection', $contentData);
    }
    

    /**
     * Main entry point for this class
     * @param array $params script query parameters
     * @throws Exception
     */
    public function run(array $params) {
    	// run parent method to set basic things
    	try {
	        parent::run($params);
        
			$lenPath = count($this->pathComponents);
	
	        if ($lenPath > 0) {    // no path -- show collections filter
	            
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
	            
	            if ($lenPath == 1) {    // set default
	                array_push($this->pathComponents, 'overview');
	            }
	
	            $action = $this->pathComponents[1];
	
	            // create hierarchy handler using the specified filter hierarchy definition document
	            $this->hierarchyHandler = new ETRepoHierarchy('filter_hierarchy');
	
	            // select method depending on action
	            if ($action == 'overview') {
	                list($title, $pageType, $contentData) = $this->createContentForCollectionOverview();
	            } else if ($action == 'metadata') {
	                list($title, $pageType, $contentData) = $this->createContentForCollectionMetadata();
	            } else if ($action == 'search') {
	                list($title, $pageType, $contentData) = $this->createContentForFilterInCollection();
	            } else if ($action == 'notebooks') {
	               	list($title, $pageType, $contentData) = $this->createContentForFilterInNotebooks();
	                       	 
	            } else {
	            	list($title, $pageType, $contentData) = $this->createContentForCollectionOverview();
	                //throw new Exception(sprintf("invalid action: '%s'", $action));
	            }
	        } else {
	            $contentData = $this->createContentForCollectionsFilter();
	            $title = 'Collections';
	            $pageType = 'filter_collections';
	        }
	    } catch (Exception $e) {
	    	if (ETRepoConf::$SERVER_CONTEXT != "online"){
	    		echo $e->getMessage();
	    	} else {
		    	header("Location: /");
	    	}
			die();
		}
		
		// render the HTML
		$this->renderToBaseTemplate($contentData, $title, $pageType, null,
				'base.php',
				array('filter.js', 'collection.js', 'masonry.pkgd.min.js'), // load these additional JavaScript files
				array('filter.css', 'collection.css')   // load these additional CSS files
		);
	}
}


// create filter script class & run it
$filter = new ETRepoFilterFrontend();
$filter->run($_GET);
