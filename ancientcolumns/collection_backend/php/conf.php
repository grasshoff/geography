<?php
$HOSTS = array(
    'repository.edition-topoi.org' => 'online',
    '141.20.159.82' => 'online',
    '176.9.17.176' => 'online', //hetzner (soon obsolete)
    'repositorytest.ancient-astronomy.org' => 'testserver',
);

/**
 * Class ETRepoConf. Shared configuration.
 */
class ETRepoConf {
    //this version is used for css/js file loading to prevent cache issues
    //if one of the files changes, also change the value here
    static public $CSSJS_VERSION = "20180711";
    
    static public $COUCH_DB_HOST;
    static public $COUCH_DB_PORT;
    static public $COUCH_DB_USER = null;
    static public $COUCH_DB_PASS = null;
    static public $COUCH_DB_PATH_PREFIX = "/couchdb";
    static public $COUCH_DB_DESIGN_DOC_VIEWS = "browse";
    static public $SERVER_CONTEXT = null;
    static public $SERVER_NAME = null;  // auto -> use $_SERVER['SERVER_NAME']

    static public $CONFIG_DB = "researchportal";    // database that contains configuration / metadata for all collections
    static public $CONFIG_DOC = "portal";      // document in the above DB that contains collections configuration (e.g. collection -> collection DB config.)

    static public $SITE_TITLE_FMT = "%s | Edition Topoi";
    static public $SITE_TITLE_DEFAULT = 'Collections';
    static public $ET_URL = "http://www.edition-topoi.org/";

    static public $RESOURCES_BASE_PATH = '/';

    static public $ENABLE_REWRITE_URLS = true;
    static public $REWRITE_URLS_PREFIX = 'collection/';

    static public $FILTER_COLLECTIONS_CRITERIA = array(
        'subject' => 'Subject',
        'resource_type' => 'Resource type',
        'geolocation' => 'Geolocation',
        'period' => 'Period',
        'type' => 'Collection type',
    );
}

$serverName = $_SERVER['SERVER_NAME'];
ETRepoConf::$SERVER_NAME = $serverName;

// override some settings depending on which server this is running on:
// ! if the server name is not in the array use local settings with high verbosity error logging !
if (array_key_exists($serverName, $HOSTS)) {
    ETRepoConf::$SERVER_CONTEXT = $HOSTS[$serverName];
    if (is_null(ETRepoConf::$SERVER_CONTEXT)) {
        ETRepoConf::$SERVER_CONTEXT = 'online';
    }

    ETRepoConf::$COUCH_DB_PATH_PREFIX = '';
    ETRepoConf::$COUCH_DB_HOST = "localhost";
    ETRepoConf::$COUCH_DB_PORT = "5984";
    ETRepoConf::$COUCH_DB_USER = "root";
    ETRepoConf::$COUCH_DB_PASS = "kp6229RF";
} else {
    // local dev settings:
    //ETRepoConf::$COUCH_DB_USER = "root"; 
    //ETRepoConf::$COUCH_DB_PASS = "kp6229RF";
    ETRepoConf::$COUCH_DB_PATH_PREFIX = '';
    ETRepoConf::$SERVER_CONTEXT = 'local';
    ETRepoConf::$COUCH_DB_HOST = "localhost";
    ETRepoConf::$COUCH_DB_PORT = "5984";

    //display all errors
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}