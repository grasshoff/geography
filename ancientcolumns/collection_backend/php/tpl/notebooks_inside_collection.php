<style>
#filter_center {
    border: none;
    margin-top: 14px;
}
</style>
<div id="collection_container" class="container p-0">
    <?php echo $tpl->top_html ?>

    <div id="filter_center" class='row'>
        <div id="filter_select" class='col-3 pl-3 p-0 d-none d-lg-block'>
            <div id="filter_select_container">
                <?php echo $tpl->filter_select_nav ?>
            </div>

            <div id="filter_select_container" class="mt-2" style="padding:0px;" class="menu">
                <a href="" style="margin:0px"><img style="height:35px;margin-right:7px" src="/img/notebook_picto_rgb_transparent.png">Notebooks</a>
		    </div>
			
		    <?php if (isset($tpl->download_collection_json_link)): ?>
            <div id="filter_select_container" class="mt-2" style="padding:0px;">
                <a href="<?php echo htmlentities($tpl->download_collection_json_link) ?>" style="margin:0px;"><img style="height:35px;margin-right:7px;padding:7px"" src="/img/Download_Pfeil_grau.png">Download Database</a>
		    </div>
		    <?php endif ?>
        </div>

        <div id="filter_results" class="filter_in_collection col"> 
            <div id="filter_results_top" class="row ml-3 mr-0">
                <div id="filter_results_info" class="col-12">
                    <span id="filter_results_total_count"></span> Notebooks
                </div>
                <div id="filter_selected_criteria" class="col-12">
                    <ul>
                    </ul>
                </div>
            </div>
            <div class='container pr-0'>
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