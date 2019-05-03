<?php
require_once('db.php');
require_once('tools.php');

/**
 * Class ETRepoHierarchy. Class that handles a collection's hierarchy.
 */
class ETRepoHierarchy {
    /**
     * Database API.
     * @var ETRepoDb
     */
    private $db;


    /**
     * Constructor.
     * @param string $hierarchyDoc hierarchy document to load from the DB
     */
    public function __construct($hierarchyDoc) {
        $this->db = ETRepoDbGetInstance();

        // get hierarchy tree
        $this->hierarchy = $this->db->fetchDocById($hierarchyDoc)->body->tree;

        // initialize the rest
        $this->hierarchyTitles = array();
    }


    /**
     * Get filter hierarchy data for a specified root section of the hierarchy
     * @param string $rootSection root section to use
     * @return array
     * @throws Exception
     */
    public function getFilterHierarchyData($rootSection) {
        if (!isset($this->hierarchy->$rootSection)) {
            throw new Exception(sprintf("root section '%s' does not exist in hierarchy", $rootSection));
        }

        $root = $this->hierarchy->$rootSection;
        $hData = array();

        // recursively walk the filter hierarchy to create the hierarchy tree
        $this->walkFilterHierarchy($root, $hData);

        return $hData;
    }


    /**
     * Recursively walk the filter hierarchy to create the hierarchy tree
     * @param stdClass $hBranch current branch of the hierarchy object to walk on
     * @param array $hData reference to hierarchy data array that will be created during recursive tree traversal
     * @throws Exception
     */
    private function walkFilterHierarchy($hBranch, &$hData) {
        if (!isset($hBranch->listing_by)) {     // it doesn't go deeper, return (stop) here
            return;
        }

        // set the branch title
        if (isset($hBranch->title)) {
            $hData['title'] = $hBranch->title;
        }

        // no inspect the listing_by attribute
        $listing = $hBranch->listing_by;

        if (isset($listing->dictionary)) {      // follow the dictionary branch
            $hData['branches'] = array();
            foreach ($listing->dictionary as $key => $subbranch) {
                $hData['branches'][$key] = array();

                // try to walk on with the subbranch
                $this->walkFilterHierarchy($subbranch, $hData['branches'][$key]);
            }
        } elseif (isset($listing->filter_criterion)) {  // this is a leaf branch (by filter_hierarchy doc. definition) because it filters by a criterion
            if (isset($listing->source_view)) { // the filter's critera come from a view
                // create rest of the hierarchy from a view
                $this->populateFilterHierarchyFromView($listing, $hData);

                // optionally add an "unknown" leaf
                if (isset($listing->add_unknown)) {
                    $unknownKey = $listing->add_unknown;
                    $hData['branches'][$unknownKey] = array(
                        'title' => 'Unknown',
                        'leaf' => array(
                            'filter_criterion' => array(
                                $listing->filter_criterion,
                                $unknownKey
                            )
                        )
                    );
                }
            } else {
                // there's only a fixed filter criterion here, don't query a view
                if (isset($listing->filter_key)) {
                    $filterKey = $listing->filter_key;
                } else {
                    $filterKey = 1;
                }

                $hData['leaf'] = array(
                    'filter_criterion' => array(
                        $listing->filter_criterion,
                        $filterKey
                    )
                );
            }
        }
    }


    /**
     * Populate a filter hierarchy branch with possible filter criteria from a view.
     * Creates subbranches at current $hData branch with leafs for filtering in them (so this at maximum 2 levels
     * deep from this point on: subbranch -> leafs)
     * @param stdClass $listingDef "listing_by" definition object
     * @param array $hData reference to hierarchy data array branch that will be created during population process
     * @throws Exception
     */
    private function populateFilterHierarchyFromView($listingDef, &$hData) {
        // query the view
        $res = $this->db->fetchView($listingDef->source_view);
        if (!isset($res->body) || !isset($res->body->rows)) {
            throw new Exception(sprintf("error querying view '%s'", $listingDef->source_view));
        }

        // build the branches with the data from the view
        // + key1
        // | + value1
        // | + value2
        // | ...
        // + key2
        // ...
        $hData['branches'] = array();
        foreach ($res->body->rows as $row) {
            if (!is_null($row->key)) {
                $k = sluggify($row->key);

                // create a new branch from the row "key" name if it was not created yet
                if (!isset($hData['branches'][$k])) {
                    $hData['branches'][$k] = array();
                    $hData['branches'][$k]['title'] = $row->key;
                    $hData['branches'][$k]['branches'] = array();
                }

                $branch = &$hData['branches'][$k]['branches'];
            } else {
                $k = null;
                $branch = &$hData['branches'];
            }

            // create a new leaf from the data in the row "value"
            $leafKey = sluggify($row->value->title);

            if (isset($branch[$leafKey])) {  // multiple IDs for criterion: create array
                $existingCriterion = $branch[$leafKey]['leaf']['filter_criterion'];
                if (!is_array($existingCriterion[1])) {
                    $branch[$leafKey]['leaf']['filter_criterion'][1] = array(
                        $existingCriterion[1],
                        @$row->value->id
                    );
                } else {
                    array_push($branch[$leafKey]['leaf']['filter_criterion'][1],
                        @$row->value->id);
                }
            } else {    // single ID for criterion
                $leafData = array(
                    'title' => $row->value->title,
                    'leaf' => array('filter_criterion' => array($listingDef->filter_criterion, @$row->value->id))
                );

                $branch[$leafKey] = $leafData;
                ksort($branch);
            }
        }
    }
}