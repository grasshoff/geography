<?php /*
needed for visual filters (map/timeline) which are deactivated for now
 
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.1.0/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.1.0/dist/leaflet.js"></script>   
<script src="/js/proj4.js"></script>
<script type="text/javascript" src="https://cdn.plot.ly/plotly-latest.min.js"></script>
*/?>
<div id="collection_container" class="container p-0">
    <?php echo $tpl->top_html ?>

	<!-- 
	<div id="visual_filters" class='row d-none d-lg-block'>
		<div class='col-5 d-inline-block' id="filter_map">
		</div>
		<div class='col-5 d-inline-block' id="filter_timeline">
		</div>
	</div>
	 -->
    <div id="filter_center" class='row'>
        <div id="filter_select" class='col-3 pr-0 d-none d-lg-block'>
            <div id="filter_select_container">
                <?php echo $tpl->filter_select_nav ?>
 
            </div>
            <div id="notebooks_results_total_count" style="display:none">

            <div id="filter_select_container" class="mt-2" style="padding:0px;" class="menu">
		    	<a id="notebooks_link" style="margin:0px"><img style="height:35px;margin-right:7px" src="/img/notebook_picto_rgb_transparent.png">Notebooks</a>
		    </div>
			</div>
		
			
		    <?php if (isset($tpl->download_collection_json_link)): ?>
            <div id="filter_select_container" class="mt-2" style="padding:0px;">

		                <a href="<?php echo htmlentities($tpl->download_collection_json_link) ?>" style="margin:0px;"><img style="height:35px;margin-right:7px;padding:7px"" src="/img/Download_Pfeil_grau.png">Download Database</a>
		    </div>
		    <?php endif ?>

			<?php if ($this->projName == "BSDP"): ?>
            <div id="filter_select_container" class="mt-2" style="padding:0px;">

		                <a href="<?php echo htmlentities("http://repositorytest.ancient-astronomy.org/collection/MISC/single/00035#tabMode") ?>" style="margin:0px;"><img style="height:35px;margin-right:7px" src="/img/notebook_picto_rgb_transparent.png">Browse in Notebook</a>
		    </div>
		    <?php endif ?>

        </div>
        <div id="filter_results" class="filter_in_collection col"> 
            <div id="filter_results_top" class="row m-0 ml-md-3 mr-0">
                <div id="filter_results_info" class="col-12 p-0">
                    <span id="filter_results_total_count"></span> Research Objects
                </div>
                <div id="filter_selected_criteria" class="col-12 p-0">
                    <ul>
                    </ul>
                </div>
            </div>
            <div class='container p-0 px-md-3'>
            	<div id="filter_results_objects" class='row'>
            	</div>
            </div>
            <div id="filter_pagination" class="row p-3">
                <div class="prev_page col-4">&lt;</div>
                <div class="page_info col-4"></div>
                <div class="next_page col-4">&gt;</div>
            </div>
        </div>
    </div>
</div>