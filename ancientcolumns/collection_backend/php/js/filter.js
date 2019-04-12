var $msnry = false;

var PRETTY_URLS_PREFIX = '/collection/'

var PAGE_TYPE = undefined;

var FILTER_BACKEND_BASE_URL = '/filter.php?';

var RESULTS_PER_PAGE = 20;

var ignore_url_hash_change = false;

// Result object display templates per PAGE_TYPE
// 0: title
// 1: subtitle
// 2: thumb as <img>
// 3: object display link
// 4: additional CSS class
// 5: thumb url

var filter_collections_html_result_obj_tpl =
	'<a href="{3}" class="col p-0 mr-4 mt-4 result_object {4}" style="background-position: center; background-size: auto 100%; background-image:url(\'{5}\')">' +
	    '<div>' +
	    		'<div class="title"><center><h3>{0}</h3></center></div>' +
	    '</div>' +
	 '</a>';

var filter_in_collection_html_result_obj_tpl =
	'<a href="{3}" class="result_object {4} col-12 col-md-6 pb-3">' +
	    '<div>' +
	        '<div class="result_object_container">' +
	            '<div class="result_object_text">' +
	                '<h3>{0}</h3>' +
	                '<h4>{1}</h4>' +
	            '</div>' +
	            '<div class="result_object_img">' +
	                '{2}' +
	            '</div>' +
	        '</div>' +
	    '</div>' +
	'</a>';

var notebooks_in_collection_html_result_obj_tpl =
	'<a href="{3}" class="result_object {4} col-12 col-md-6 pb-3">' +
    '<div class="{4}">' +
        '<div class="result_object_container">' +
            '<div class="result_object_text">' +
                '<h3>{0}</h3>' +
                '{1}' +
            '</div>' +
            '<div class="result_object_img">' +
                '{2}' +
            '</div>' +
        '</div>' +
    '</div>' +
'</a>';


var RESULT_OBJECT_TPL = {
    'filter_collections': filter_collections_html_result_obj_tpl,
    'filter_in_collection': filter_in_collection_html_result_obj_tpl,
    'notebooks_in_collection': notebooks_in_collection_html_result_obj_tpl,
    'filter_collections_notebooks': filter_collections_html_result_obj_tpl 
    
};

var PERFECT_SCROLLBAR_THEME = {
    'filter_collections': 'default',
    'filter_in_collection': 'dark',
    'notebooks_in_collection': 'dark'
}

var PAGE_INFO_TPL = 'Page {0} of {1}';

//filter_hierarchy with all objects for each criterion
var hierarchyData = {}

//the result of the last call to filter backend
var filterData = {};

var cur_filter_params = {
    'collection': null,
    'criteria': {},
    'page': 1
};

var selected_criteria = {};

/****************************************** HELPER FUNCTIONS **********************************************************/

if (!String.prototype.format) {
    String.prototype.format = function() {
        var args = arguments;
        return this.replace(/{(\d+)}/g, function(match, number) {
            return typeof args[number] != 'undefined'
                ? args[number]
                : match
                ;
        });
    };
}

function getURLParameter(name) {
    return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(location.search)||[,""])[1].replace(/\+/g, '%20'))||null
}

function thumb(img_name, thumb_type) {
    var dot_idx = img_name.lastIndexOf('.');
    var base_name = img_name.substring(0, dot_idx);
    var ext = img_name.substring(dot_idx);

    return base_name + '_' + thumb_type + ext;
}

/**
 * Check if scalar value <v> is in <arr>
 * @param v value to check for in <arr>
 * @param arr array to be searched in
 * @returns {boolean} true if <v> is in <arr>, else false
 */
function inArray(v, arr) {
    for (var i = 0; i < arr.length; i++) {
        if (arr[i] === v) {
            return true;
        }
    }

    return false;
}

/**
 * Check if any of the scalar values in <needles> exists in <haystack>
 * @param needles       array of scalar values to look for in <haystack>
 * @param haystack      array to check in
 * @param ret_found_val return the found value or return boolean value
 * @returns true if any of <needles> is in <haystack>, else false
 */
function anyInArray(needles, haystack, ret_found_val) {
    ret_found_val = !!ret_found_val;
    found_values = []
    for (var i = 0; i < needles.length; i++) {
        if (inArray(needles[i], haystack)) {
        	found_values.push(needles[i]);
        }
    }

    return ret_found_val ? found_values : false;
}

/**
 * Transform the current filter parameters saved in <cur_filter_params> to location URL hash parameters like
 * "#page=2;by_site=2,74;by_holder=4"
 */
function curFilterParamsToLocationHash() {
    var params = [];

//    if (cur_filter_params.collection) {
//        params.push('collection=' + cur_filter_params.collection);
//    }

    for (var crit in cur_filter_params.criteria) {
        var crit_vals = cur_filter_params.criteria[crit];
        params.push('by_' + crit + '=' + crit_vals.join(','))
    }

    if (cur_filter_params.page) {
        params.push('page=' + cur_filter_params.page);
    }

    // protect against firing the "location hash changed" event
    ignore_url_hash_change = true;

    location.hash = '#' + params.join(';');

    // ignore the URL hash change due to adding/removing criteria for 50ms
    window.setTimeout(function() {
        ignore_url_hash_change = false;
    }, 50);
}

/**
 * Parse the current location URL hash parameter like "#page=2;by_site=2,74;by_holder=4" and save its data to
 * <cur_filter_params>.
 */
function curFilterParamsFromLocationHash() {
    if (location.hash.length < 2) {
        return;
    }

    // split the individual parameters by ";"
    var params = location.hash.substr(1).split(';');

    // parse the individual parameters that have a format like "key=value_1[,value_2,...]"
    for (var i = 0; i < params.length; i++) {
        var p = params[i].split('=');
        var p_name = p[0];  // parameter key

        if (p_name.substr(0, 3) == 'by_') { // filter criterion
            var crit = p_name.substr(3);

            if (PAGE_TYPE == 'filter_collections' && crit == 'type') {  // 'type' criterion is handled specially in filter_collections page
                var crit_val = p[1];

                // set the CSS class
                $('#filter_collections_type_select ' + crit_val + 's').addClass('active');

                // set the criterion value
                setCollectionTypeCriterion(crit_val);
            } else {    // normal criterion handling
                var crit_vals = p[1].split(',');

                // find the element in the filter hierarchy corresponding to this criterion
                $('#filter_select .filter_nav_tree_root').find('.filter_crit').each(function() {
                    if ($(this).attr('filter:by') && $(this).attr('filter:values')) {
                        var filter_by = $(this).attr('filter:by');
                        var filter_vals = $(this).attr('filter:values').split(',');

                        if (filter_by == crit) {
                            var found_val = anyInArray(crit_vals, filter_vals, true);
                            if (found_val.length > 0) {
                                // add the criterion but do not update the display and the location hash
                        		addCriterion($(this), null, crit, found_val, false, false);

                        		// expand the hierarchy branches
                        		expandHierarchyUpToElem($(this));
                            }
                        }
                    }
                });
            }
        } else if (p_name == 'page') {      // current page
            cur_filter_params[p_name] = parseInt(p[1]);
        }
    }
}

/*********************************************** BODY ONLOAD **********************************************************/

$(function () {
    // derive page type from body class
    PAGE_TYPE = $('body').attr('class');

    if (!(PAGE_TYPE in RESULT_OBJECT_TPL)) {
        return;
    }

    var path_param = getURLParameter('path');  // path is optional -> can be null

    if (!path_param) {
        // handle 'path' parameter for pretty URLs
        var path_begin_idx = location.href.indexOf('/', 8);  // why 8? skip slashes in http(s)://

        if (path_begin_idx > -1) {
            var query_str_idx = location.href.indexOf('?');
            if (query_str_idx > -1) {
                path_param = location.href.substring(path_begin_idx, query_str_idx);
            } else {
                path_param = location.href.substring(path_begin_idx);
            }

            var hash_str_idx = path_param.indexOf('#');
            if (hash_str_idx > -1) {
                path_param = path_param.substring(0, hash_str_idx);
            }

            if (path_param.indexOf(PRETTY_URLS_PREFIX) > -1) {
                path_param = path_param.substr(PRETTY_URLS_PREFIX.length);
            }
        }
    }

    if (path_param) {
        var slash_idx = path_param.indexOf('/');
        if (slash_idx < 0) {
            slash_idx = path_param.length;
        }

        cur_filter_params.collection = path_param.substring(0, slash_idx);
    } else {
        cur_filter_params.collection = path_param;
    }

    // set current filter parameters from location hash
    curFilterParamsFromLocationHash();

    // set up functions to filter collections by type (collection/bag)
    if (PAGE_TYPE == 'filter_collections') {
        setupFilterCollectionTypeActions();
    }

    // set up functions to expand/collapse branches
    setupFilterBranchActions();

    // set up functions to select/remove filter criteria
    setupFilterSelectActions();

    // set up functions to browse through result pages
    setupFilterPaginationActions();

    //  get the filter criteria counts (they are static and don't need to be updated again)
    updateCriteriaCounts();

    // default query: no criteria, page 1
    updateFilterWithCurParams();

    if (PAGE_TYPE == 'filter_collections') {
        updateCollectionsFilterCountDisplay();
    }
    
    //TODO: hack this has to be handled better! 
    // check if we are filtering for notebooks
	if (cur_filter_params["criteria"] && cur_filter_params["criteria"]["type"]){
		var typeCriteria = cur_filter_params["criteria"]["type"];
		for (var i = 0; i < typeCriteria.length; i++){
			if (typeCriteria[i]=="notebook"){
				PAGE_TYPE = "filter_collections_notebooks";
			}
		}
	}

    // set URL hash change event handler
    window.onhashchange = windowURLHashChangedAction;

    // init scrollbar
    /*$('.filter_nav_scroll_container').perfectScrollbar({
        suppressScrollX: true,
        theme: PERFECT_SCROLLBAR_THEME[PAGE_TYPE]
    });*/
});

/*********************************************** FILTER LOGIC *********************************************************/

/**
 * set up functions to filter collections by type (collection/bag)
 */
function setupFilterCollectionTypeActions() {
    $('.filterButton').each(function () {
        $(this).bind('click', function() {
            // get classes of this button
            var classes = $(this).attr('class').split(' ');

            // get class and element of the other button

            var other_cls2  = $(['collections','bags','notebooks']).not(classes);

                        

            //var v = classes;
            
            if (inArray('collections', classes)) {
            	var type_crit_val = 'collection';
            	var other_cls = 'bags';
                var other_cls2 = 'notebooks';
            	PAGE_TYPE = "filter_collections";

            } else if (inArray('bags', classes)) {
            	var type_crit_val = 'bag';
            	var other_cls = 'collections'
                var other_cls2 = 'notebooks';
            	PAGE_TYPE = "filter_collections";
            	
            } else if (inArray('notebooks', classes)) {
            	var type_crit_val = 'notebook';
            	var other_cls = 'bags'
            	var other_cls2 = 'collections';

            	if (!inArray('active', classes)){
            		PAGE_TYPE = "filter_collections_notebooks";
            	} else {
            		PAGE_TYPE = "filter_collections";
            	}
            }
            
            //var other_cls = inArray('collections', classes) ? 'notebooks' : 'collections';

            //var var_crit_val = 'collection';
            // else if (v = "bags") {
            //	var var_crit_val ='bag';
            //} else if (classes = '["notebooks"]') {
            //	var var_crit_val2 = 'notebooks';
            //}

            var other_elem = $('.filterButton.' + other_cls);
            var other_elem2 = $('.filterButton.' + other_cls2);

            
            
            
            // if other button is active, set it inactive
            if (inArray('active', other_elem.attr('class').split(' '))) {
                other_elem.removeClass('active');
            }

            if (inArray('active', other_elem2.attr('class').split(' '))) {
                other_elem2.removeClass('active');
            }
            		
            //var type_crit_val = inArray('collections', classes) ? 'collection' : 'bag';

            // add/remove criterion
            if (inArray('active', classes)) {
                removeCollectionTypeCriterion();
            } else {
                setCollectionTypeCriterion(type_crit_val);
            }

            // update the filter
            updateFilterWithCurParams();

            // toggle the button class
            $(this).toggleClass('active');

            // update the count display
            updateCollectionsFilterCountDisplay();
        });
    });
}


/**
 * set up actions to expand/collapse branches and show more/less options in a branch
 */
function setupFilterBranchActions() {
    // set actions to expand/collapse branches
    if (PAGE_TYPE == 'filter_in_collection') {
        $('#filter_select .filter_nav_tree_root').find('.filter_branch_title').each(function () {
            $(this).bind("click", function () {
            	var branch = $(this).closest('.filter_branch');
            	branch.toggleClass('expanded');

                // toggle the "show more / show less" button for limiting the options in the menu
                var show_all_or_less_btn = branch.find('> .filter_children > .filter_nav_all_elems_toggle');
                //show_all_or_less_btn.toggle();

                // limit the displayed options in the menu when this branch was expanded
                if (branch.hasClass('expanded')) {
                	hideTooManySubElements(branch.find('>.filter_children'));
                } else {
                    show_all_or_less_btn.html('show all');
                    show_all_or_less_btn.removeClass('active');
                }

                // $('.filter_nav_scroll_container').perfectScrollbar('update');   // update scrollbar geometry
            });
        });
    }

    $('#filter_select .filter_nav_all_elems_toggle').each(function() {
        if (PAGE_TYPE == 'filter_in_collection') {
            var branch = $(this).prevAll('.filter_branch:first');

            if (branch.hasClass('expanded')) {
                $(this).show();
                hideTooManySubElements($(this).parent().find('> ul'));
            } else {
                $(this).html('show all');
                $(this).removeClass('active');
            }
        } else {
            $(this).show();
            hideTooManySubElements($(this).parent());
        }
    });

    // set actions to show more/less options in a branch
    $('#filter_select .filter_nav_all_elems_toggle').each(function() {
        $(this).bind(("click"), function() {
            var branch = $(this).parent();

            if (!$(this).hasClass('active')) {
                showAllSubElements(branch);
                $(this).html('show less');
            } else {
                hideTooManySubElements(branch);
                $(this).html('show all');
            }

            $(this).toggleClass('active');
        });
    });
}

/**
 * set up actions to select/remove filter criteria
 */
function setupFilterSelectActions() {
    $('#filter_select .filter_nav_tree_root').find('.filter_crit').each(function() {
        var filter_title = $(this).text();
        var filter_crit = $(this).attr('filter:by');
        var filter_vals = $(this).attr('filter:values');

        // define click event for criteria in filter tree
        $(this).bind("click", function() {
            var filter_crit_hash = $(this).attr('filter:hash');
            if (!filter_crit_hash) { // this criterion was not added yet...
                // ... so add it now
                addCriterion($(this), filter_title, filter_crit, filter_vals.split(','), true, true);
            } else {    // this criterion was already added...
                // ... so remove it now
                removeCriterion($(this), filter_crit_hash);
            }
        });
    });
}

/**
 * set up functions to browse through result pages
 */
function setupFilterPaginationActions() {
    var page_change_fn = function(dir) {
        cur_filter_params.page += dir;

        // update filter display
        updateFilterWithCurParams();

        // update location hash
        curFilterParamsToLocationHash();
    };

    $('#filter_pagination .prev_page').bind('click', function () {
        page_change_fn(-1);
    });
    $('#filter_pagination .next_page').bind('click', function () {
        page_change_fn(1);
    });
}

function windowURLHashChangedAction() {
    if (ignore_url_hash_change) return;

    // reload whole page
    location.reload();

    /* somehow this did not work (reload only contents, not whole page):
    // set current filter parameters from location hash
    curFilterParamsFromLocationHash();

    // default query: no criteria, page 1
    updateFilterWithCurParams();

    if (PAGE_TYPE == 'filter_collections') {
        updateCollectionsFilterCountDisplay();
    }*/
}

/**
 * Hide sub elements of filter selection branch <branch> that are too much.
 * @param branch branch to apply this to
 */
function hideTooManySubElements(branch) {
    var sub_elems = branch.children();
    if (sub_elems.length > 5) {
        sub_elems.each(function(i) {
            if (i >= 5) {
                $(this).hide();
            }
        });
        
        branch.find(".filter_nav_all_elems_toggle").show();
    }
}

/**
 * Show all sub elements of filter selection branch <branch>
 * @param branch branch to apply this to
 */
function showAllSubElements(branch) {
    var sub_elems = branch.find('>:hidden');
    sub_elems.show();
}

/**
 * Expand all parent hierarchy branches up to the element <elem>
 * @param elem  expand up to this element
 */
function expandHierarchyUpToElem(elem) {
    elem.parentsUntil("ul.filter_nav_tree_root").each(function() {
        if ($(this).hasClass('filter_branch') && !$(this).hasClass('expanded')) {
            $(this).addClass('expanded');
        }

        if ($(this).prop("tagName") == 'LI' && !$(this).hasClass('expanded')) {
            $(this).addClass('expanded');
        }
    });
}

/**
 * Set collection filter to filter for collections of type <type_val> (collection/bag)
 * @param type_val filter value (collection/bag)
 */
function setCollectionTypeCriterion(type_val) {
    // update filter params
    cur_filter_params.page = 1;  // reset page!
    cur_filter_params.criteria['type'] = [type_val];

    // update location hash
    curFilterParamsToLocationHash();
}

/**
 * Remove the 'collection type' criterion from the filter.
 */
function removeCollectionTypeCriterion() {
    cur_filter_params.page = 1;  // reset page!
    delete cur_filter_params.criteria['type'];

    // update location hash
    curFilterParamsToLocationHash();
}

/**
 * Add a new criterion of type <crit> with filter values <vals>
 *
 * @param filter_tree_elem      HTML element from the filter selection tree
 * @param title                 title of the criterion for the "selected criteria" list
 *                              or null (then it will be filter_tree_elem.text())
 * @param crit                  criterion type
 * @param vals                  criterion filter values
 * @param update_disp           update display
 * @param update_location_hash  update location hash?
 */
function addCriterion(filter_tree_elem, title, crit, vals, update_disp, update_location_hash) {
    var hash = crit + vals.join(',');

    if (!title) {
        title = filter_tree_elem.text();
    }

    if (hash in selected_criteria) {
        return;
    }

    if (!(crit in cur_filter_params.criteria)) {
        cur_filter_params.criteria[crit] = [];
    }

    // update filter params
    cur_filter_params.page = 1;  // reset page!
    cur_filter_params.criteria[crit] = cur_filter_params.criteria[crit].concat(vals);

    if (update_disp) {
        updateFilterWithCurParams();
    }

    // update location hash
    if (update_location_hash) {
        curFilterParamsToLocationHash();
    }

    // update selected criteria
    selected_criteria[hash] = [crit, vals];

    if (filter_tree_elem){
	    // update selected criteria list
	    var crit_list = $('#filter_selected_criteria > ul');
	    var new_elem = $('<li>' + title + '</li>');
	    new_elem.bind("click", function() {
	        removeCriterion(filter_tree_elem, hash);
	    });
	    new_elem.attr('filter:hash', hash);
	
	    crit_list.append(new_elem);

	    // update filter tree element
	    filter_tree_elem.attr('filter:hash', hash);
	    filter_tree_elem.addClass('active');
    }
}

/**
 * Remove criterion identified by <crit_hash>
 *
 * @param filter_tree_elem  HTML element from the filter selection tree
 * @param crit_hash         criterion hash to identify this criterion
 */
function removeCriterion(filter_tree_elem, crit_hash) {
    if (!(crit_hash in selected_criteria)) {
        return;
    }

    var sel_crit_arr = selected_criteria[crit_hash];
    var crit = sel_crit_arr[0];
    var vals = sel_crit_arr[1];

    // update filter params
    if (!(crit in cur_filter_params.criteria)) {
        return;
    }

    cur_filter_params.page = 1;  // reset page!

    cur_filter_params.criteria[crit] = cur_filter_params.criteria[crit].filter(function(v) {
        // remove 'vals' from this criteria's array of values
        return !inArray(v, vals);
    });

    if (cur_filter_params.criteria[crit].length == 0) {
        delete cur_filter_params.criteria[crit];
    }

    updateFilterWithCurParams();

    // update location hash
    curFilterParamsToLocationHash();

    // update selected criteria list
    $('#filter_selected_criteria > ul > li').each(function() {
        if ($(this).attr('filter:hash') == crit_hash) {
            $(this).remove();
        }
    });

    if (filter_tree_elem){
	    // update filter tree element
	    filter_tree_elem.removeClass('active');
	    filter_tree_elem.removeAttr('filter:hash');
    }
    // remove from selected_criteria
    delete selected_criteria[crit_hash];
}

/**
 * Update the page using the current filter parameters: Query the filter backend asynchronously and update the display
 * when the results arrive.
 */
function updateFilterWithCurParams() {
    queryFilterBackend(cur_filter_params.collection, cur_filter_params.criteria, cur_filter_params.page, false,
        function (data) {
            updateResultDisplay(cur_filter_params.collection, data);

            filterData = data;
            updateCriteriaCountsDisplay();
        }
    );
}

/**
 * Fetch the criteria counts and update the display. This is static and only has to be done initially.
 */
function updateCriteriaCounts() {
    queryFilterBackend(cur_filter_params.collection, null, null, true,
        function (data) {
    		hierarchyData = data;
    	    /*
    	    // visual filter functionality
    		initializeMap();
    		*/
            updateCriteriaCountsDisplay();
        }
    );
}

/**
 * Update special counts display for collections filter ("n collections / m bags")
 */
function updateCollectionsFilterCountDisplay() {
    // update count display
    var active_elem;
    if (!('type' in cur_filter_params.criteria)) {
        active_elem = ['collections', 'bags', 'notebooks'];
    } else {
        active_elem = [cur_filter_params.criteria['type'] + 's'];
    }

    $('#filter_results_info > div').hide();
    for (var i = 0; i < active_elem.length; i++) {
        $('#filter_results_' + active_elem[i] + '_disp').show();
    }

    if (active_elem.length > 1) {
        $('#filter_results_divider').show();
        $('#filter_results_divider2').show();

    }
}

/**
 * Update the result display for collection <collection> with result set <data> delivered by the filter backend
 * @param collection    collection abbrev.
 * @param data          result set delivered by the filter backend
 */
function updateResultDisplay(collection, data) {
    var counts_by_coll_type = {
        'collection': 0,
        'bag': 0,
        'notebook': data['num_notebooks']

    }

    // update result elements
    var result_objs_html = '';
    for (var i = 0; i < data['rows'].length; i++) {
        var row = data['rows'][i];
        var img_url, img_html, link, subtitle, add_class;

        if (collection) {   // display object previews inside collection
            img_html = '';
            if ('thumb' in row && (typeof row['thumb'] == "string") && (row['thumb'].length > 0)) {
            	img_url = '/' + row['thumb'];
            } else {
                img_url = '/img/trans.png';
            }

            img_html = '<img src="' + img_url + '" alt="thumbnail for ' + row['title'] + '">';
            if (PAGE_TYPE == 'notebooks_in_collection') {            	
                link = PRETTY_URLS_PREFIX + collection + '/single/' + row['id'];
            } else {
            	
                link = PRETTY_URLS_PREFIX + collection + '/object/' + row['id'];
                //get all notebooks link
            }
           
            subtitle = 'subtitle' in row ? row['subtitle'] : '';
            add_class = 'object';
            if (i % 2 == 0) {
                add_class += ' even';
            } else {
                add_class += ' odd';
            }
        } else {            // display collection previews
            img_html = '';
            if ('thumb' in row && Array.isArray(row['thumb']) && row['thumb'][0] != null) {
                img_url = row['thumb'][0] + row['thumb'][1];
                img_html = '<img src="' + img_url + '" alt="thumbnail for ' + row['title'] + '">';
            }
            // link = '/filter-frontend.php?path=' + row['id'];    // w/o pretty URLs
        	link = PRETTY_URLS_PREFIX + row['id'];
        	if (PAGE_TYPE == "filter_collections_notebooks"){
        		link += "/notebooks";
        	}
            subtitle = 'subtitle' in row ? row['subtitle'] : '';
            add_class = 'coll_type' in row ? row['coll_type'] : 'collection';

            counts_by_coll_type[add_class]++;   // increment count by collection type
        }

        var obj_html = RESULT_OBJECT_TPL[PAGE_TYPE].format(row['title'], subtitle, img_html, link, add_class, img_url);

        result_objs_html += obj_html;
    }

    if ($msnry != false){
        $oldItems = $("#filter_results_objects .result_object");
        $msnry.masonry("remove", $oldItems);
    }

    $newItems = $(result_objs_html);
    $('#filter_results_objects').append($newItems);
    
    if ($msnry == false){
    	$msnry = $('#filter_results_objects').masonry({
    		itemSelector: '.result_object', // use a separate class for itemSelector, other than .col-
			columnWidth: '.result_object',
			percentPosition: true,
			transitionDuration: 0
	    });
    } else {
    	$msnry.masonry('appended', $newItems);
    	$msnry.masonry();
    }
	    

    if ((PAGE_TYPE == 'filter_in_collection') || (PAGE_TYPE == 'notebooks_in_collection') ) {
        // hover event functions on result object's images to display 'cite' logo
        $('#filter_results_objects img').hover(
            function () {
                $(this).data('prev_src', $(this).attr('src'));
                $(this).attr('src', '/img/sci.png');
            },
            function () {
                $(this).attr('src', $(this).data('prev_src'));
            }
        );
    }

    // notebooks
    var notebooks_count = parseInt(data['num_notebooks']);
    if (notebooks_count >= 1 ) {
    	//notebooks
        $('#notebooks_results_total_count').show();
        $('#notebooks_link').attr('href','notebooks');


    }
    
    
    // set total count
    var total_count = parseInt(data['total_count']);

    if (PAGE_TYPE == 'filter_collections') {
    	for (var coll_type in counts_by_coll_type) {
            $('#filter_results_' + coll_type + 's_count').text(counts_by_coll_type[coll_type]);
        }
    } else {
        $('#filter_results_total_count').text(total_count);

    }

    // update pagination
    var num_pages = Math.max(1, Math.ceil(total_count / RESULTS_PER_PAGE));

    var css_attr_on = {
        'visibility': 'visible',
        'cursor': 'pointer'
    };
    var css_attr_off = {
        'visibility': 'hidden',
        'cursor': 'auto'
    };

    if (cur_filter_params.page > 1) {
        $('#filter_pagination .prev_page').css(css_attr_on);
    } else {
        $('#filter_pagination .prev_page').css(css_attr_off);
    }

    if (cur_filter_params.page < num_pages) {
        $('#filter_pagination .next_page').css(css_attr_on);
    } else {
        $('#filter_pagination .next_page').css(css_attr_off);
    }

    $('#filter_pagination .page_info').text(PAGE_INFO_TPL.format(cur_filter_params.page, num_pages));
}


/**
 * Update the count display of collections/objects per criteria.
 * @param data data fetched from the filter backend
 */
function updateCriteriaCountsDisplay() {
    var total_objids = false;
    if (filterData && filterData.total_indices){
    	total_objids = filterData.total_indices;

    }
    
    $('#filter_select .filter_crit').each(function () {
        var crit_name = $(this).attr('filter:by');
        var crit_vals =  $(this).attr('filter:values').split(',');
        var test =  $(this).attr('filter:hash')

        
        if (!(crit_name in hierarchyData)) return;
        objids_for_crit = hierarchyData[crit_name];

        var count = 0;
        var unfilteredCount = 0;
        for (var i = 0; i < crit_vals.length; i++) {
            var v = crit_vals[i];
            if (v in objids_for_crit) {
            	var this_objids = objids_for_crit[v];
            	thisCount = this_objids.length; // integer, doesnt have length
            	unfilteredCount+=thisCount;
            	
            	if (total_objids){
            		var remaining_objids = $(this_objids).filter(total_objids);
            		//x = total_objids.length-remaining_objids.length;
            		//count = thisCount-total_objids.length
            		thisCount = remaining_objids.length;
            	}	
            	
                count += thisCount 
            }
        }
        
        // set count to '.filter_crit_count' element
        var parent = $(this).parent();
        var crit_count_elem = parent.find('.filter_crit_count');
        if (PAGE_TYPE == 'filter_collections_notebooks') {
            crit_count_elem.css('display', 'none');
        } else {
            crit_count_elem.text(count+'/'+unfilteredCount+'');
            crit_count_elem.css('display', 'block');
        }

    });
    
    /*
    // visual filter functionality
    removeAllCircles();
    addAllCircles();
    */
}


/**
 * Query the filter backend asynchronously and execute <fn> upon data arrival
 * @param path          base query path (collection)
 * @param crit          object with criteria {crit_type1: [value1, value2, ...], crit_type2: ..., ... }
 * @param page          which result page to query
 * @param query_counts  true/false: query criteria counts
 * @param fn            which function to call on query success
 */
function queryFilterBackend(path, crit, page, query_counts, fn) {
    var limit_params = null;
    query_notebooks = null;
	if (PAGE_TYPE == 'notebooks_in_collection' ) {
		query_notebooks= true;
		query_counts= null;
		
	}
	
    if (page) {
        limit_params = [(page-1) * RESULTS_PER_PAGE, RESULTS_PER_PAGE];
    }

    var base_params = [];

    if (limit_params) {
        base_params.push('limit=' + limit_params.join(','));
    }

    if (path) {
        base_params.push('path=' + path);
    }

    if (query_counts) {
        base_params.push('get_counts');
    }

    if (query_notebooks) {
        base_params.push('get_notebooks');
    }    
    
    var crit_params = [];
    if (crit) {
        for (var k in crit) {
            var crit_vals = crit[k];
            var v = [];
            for (var v_i = 0; v_i < crit_vals.length; v_i++) {
                v.push(encodeURIComponent(crit_vals[v_i]));
            }

            if (v.length > 0) {
        		var qry = "get_all_notebooks"; 
            	if (v != "notebook") { 
                    var qry = 'by_' + k + '=' + v.join(',');

            	}

                crit_params.push(qry);
            }
        }
    }

    var params = base_params.concat(crit_params);
    var query_url = FILTER_BACKEND_BASE_URL + params.join('&');
    console.log(query_url);
    $.getJSON(query_url, fn);
}

/*
var map = false;
var circlesPerZoom = {};
var mapFeatureGroup = false;
var filteredMapCriteria = [];
var timelineDiv = false;
var timelineArrays = {
		x:[],
		y:[]
};

function removeAllCircles(){
	if (!map){
		return;
	}
    var zoomLvl = map.getZoom();
    for (circleNr in circlesPerZoom[zoomLvl]){
    	circlesPerZoom[zoomLvl][circleNr].circle.remove();
    }		
}

function addAllCircles(zoomToBounds = false){
	if (map !== false){
		if (mapFeatureGroup !== false){
			mapFeatureGroup.remove();
		}
		mapFeatureGroup = L.featureGroup([]);		
	    var zoomLvl = map.getZoom();
	
	    var bounds = L.latLngBounds([]);
	
	    if (typeof circlesPerZoom[zoomLvl] !== "undefined"){
		    for (circleNr in circlesPerZoom[zoomLvl]){
		    	var thisCircleObj = circlesPerZoom[zoomLvl][circleNr]; 
		    	var circle = thisCircleObj.circle;
		    	var entries = thisCircleObj.entries;
		    	var radius = thisCircleObj.radius;
		        if (filterData && filterData.total_indices){
		        	var total_objids = filterData.total_indices;		        	
		        	var remaining_objids = $(entries).filter(total_objids);
		        	radius = radius * remaining_objids.length/entries.length;
		        }
		
		        if (radius > 0){
					circle.setRadius(radius*Math.pow(2, 16-zoomLvl));
					mapFeatureGroup.addLayer(circle);
			    	
		        }
		    }
	    
		    mapFeatureGroup.addTo(map);
		    if (zoomToBounds){
		    	map.invalidateSize();		    	
		    	map.fitBounds(mapFeatureGroup.getBounds());
		    }
	    }
	}
	
	if (timelineDiv !== false){
		if (filterData && filterData.total_indices){
			var total_objids = filterData.total_indices;			
			var filteredArray = [];
			
			var datingHierarchyEntries = hierarchyData["dating"];
			for (periodName in datingHierarchyEntries){
				var remaining_objids = $(datingHierarchyEntries[periodName]).filter(total_objids).length;
				filteredArray.push(remaining_objids);
			}
			
		    Plotly.restyle(timelineDiv, 'y', [filteredArray]);
		}
	}
}

function addMap(){
	$("#filter_map").height("350px");
	// create the tile layer with correct attribution
	var osmUrl='http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
	var osmAttrib='Map data © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors';
	var osm = new L.TileLayer(osmUrl, {minZoom: 1, maxZoom: 12, attribution: osmAttrib});		
	
	map = L.map('filter_map').setView([51.505, -0.09], 13);
	// start the map in South-East England
	map.setView(new L.LatLng(51.3, 0.7),1);
	map.addLayer(osm);
	
	map.on('zoomstart', function() {
		removeAllCircles();
	});	
	
	map.on('zoomend', function() {
		addAllCircles();
	});
	
	map.on('click', function(event){
		//click beside circle, reset all filters
		for (var i=0; i < filteredMapCriteria.length; i++){
			removeCriterion(false, filteredMapCriteria[i]);
		}
		
		filteredMapCriteria = [];
	});	
}

function initializeMap(){
	return;
	if (typeof proj4 === "undefined"){
		return;
	}
	var source = new proj4.Proj("EPSG:3857");    //source coordinates will be in Longitude/Latitude
	var dest = new proj4.Proj("EPSG:4326");     //destination coordinates in LCC, south of France
	
	function mercatorToLatLong(x, y) {
		// transforming point coordinates
		var p = new proj4.Point(x,y);   //any object will do as long as it has 'x' and 'y' properties
		var transform = proj4.transform(source, dest, p); //do the transformation.  x and y are modified in place 
		return([transform.y, transform.x]);      
	}
	
	for (hierarchyEntryName in hierarchyData){
		var hierarchyEntry = hierarchyData[hierarchyEntryName];
		if (hierarchyEntryName.indexOf("Binning") == 0){
			addMap();
			
			for (zoomBinName in hierarchyEntry){
				var splitBinName = zoomBinName.split("_");
				//Binning_<zoomLvl>_<x>_<y>
				var zoomLvl = splitBinName[1];
				var x = parseFloat(splitBinName[2]);
				var y = parseFloat(splitBinName[3]);
				var latlon = mercatorToLatLong(x,y);
				var radius = parseFloat(splitBinName[4]);

				if (typeof circlesPerZoom[zoomLvl] === "undefined"){
					circlesPerZoom[zoomLvl] = [];
				}
				
				var circle = L.circle(latlon, {
				    color: 'red',
				    fillColor: '#f03',
				    fillOpacity: 0.5,
				    weight: 2,
				    radius: radius*Math.pow(2, 16-zoomLvl)
				});
				
				circlesPerZoom[zoomLvl].push({
						entries: hierarchyEntry[zoomBinName],
						radius: radius,
						circle: circle
				});
				
				(function(zoomLvl, binName){
					circle.on("click", function(event){
						addCriterion(false, binName, "Binning", [binName], true, true);
						filteredMapCriteria.push("Binning" + binName);
						
						//map click event will only fire if it was not inside circle
						L.DomEvent.stop(event);
						var origEvent = event.originalEvent;
						origEvent.preventDefault();
						origEvent.stopPropagation();
					    return false;  						
					});					
				})(zoomLvl, zoomBinName);
			}
			
			addAllCircles(true);
		}
	}
	
	timelineArrays.x = [];
	timelineArrays.y = [];
	for (hierarchyEntryName in hierarchyData){
		var hierarchyEntry = hierarchyData[hierarchyEntryName];
		if (hierarchyEntryName.indexOf("dating") == 0){
			for (periodName in hierarchyEntry){
				timelineArrays.x.push(periodName);
				timelineArrays.y.push(hierarchyEntry[periodName].length);
			}

			var d3 = Plotly.d3;
			var WIDTH_IN_PERCENT_OF_PARENT = 50;

			var gd3 = d3.select("div[id='filter_timeline']")
			  .style({
			    height: '350px'
			});

			timelineDiv = gd3.node();	
			
			var datings = {
					  x: timelineArrays.x,
					  y: timelineArrays.y,
					  type: 'bar',
					  name: 'Dating',
					  marker: {
					    color: 'rgb(49,130,189)',
					    opacity: 0.7,
					  }
					};

			var data = [datings];

			var layout = {
					  title: 'Dating',
					  xaxis: {
					    tickangle: -45
					  },
					  barmode: 'group'
					};

			Plotly.plot(timelineDiv, data, layout);
			
			$(window).resize(function(){
				Plotly.Plots.resize(timelineDiv);
			});
			
			timelineDiv.on('plotly_click', function(data){
				if (data.points.length > 0){
					var clickedPeriod = data.points[0].x;
					addCriterion(false, binName, "Binning", [binName], true, true);
				}
			});
			
			addAllCircles(true);			
		}
	}
}

*/