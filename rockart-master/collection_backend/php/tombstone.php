<?php 
require_once('php/tools.php');
require_once('php/base.php');
require_once('php/db.php');
require_once('php/hierarchy.php');

class ETRepoTombstone extends ETRepoBase {
	/**
	 * Main entry point for this class
	 * @param array $params script query parameters
	 * @throws Exception
	 */
	public function run(array $params) {
		// run parent method to set basic things
		parent::run($params);
		
		// render the HTML
		$this->renderToBaseTemplate("This DOI is no longer attached to any meaningful content.<br/>If you feel this is incorrect please contact us and state where you found this DOI and which content it should describe.", "Tombstone", "tombstone", null,
				'base.php',
				array('filter.js', 'collection.js'), // load these additional JavaScript files
				array('filter.css', 'collection.css')   // load these additional CSS files
				);
	}
}

// create filter script class & run it
$tombstone = new ETRepoTombstone();
$tombstone->run($_GET);
?>