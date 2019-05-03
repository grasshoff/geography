<div id='filter_main'>
<div class="row" >
    <div id="filter_top" class='col mx-3 px-0'>
        <h1>Repository</h1>
        <p>
            The Edition Topoi research platform is an innovative, reliable information infrastructure. It serves the
            publication of citable research data such as 3D models, high-resolution pictures, data and databases. The
            content and its meta data are subject to peer review and made available on an Open Access basis. The
            published or publishable combination of citable research content and its technical and contextually relevant
            meta data is defined as Citable. The public data are generated via a cloud and can be directly connected
            with the individual computing environment.
        </p>
    </div>
</div>
<div class="row" >
        <div id="filter_select" class='col pr-0 d-none d-lg-block'>
            <div id="filter_select_container" class="container">
                <?php echo $tpl->filter_select_nav ?>
            </div>
        </div>
        <div id="filter_results" class='col'>
        	<div id='filter_results_top' class='container'>
	            <div class='row'>
	            	<div class='col-sm-12 col-md text-center text-md-left'>
	            		<div class='container'>
	            			<div id='filter_collections_type_select' class='row'>
								<div class="col p-2 filterButton collections text-center">Collections</div>
								<div class="col p-2 filterButton bags text-center">Bags</div>
								<div class="col p-2 filterButton notebooks text-center">Notebooks</div>
							</div>
						</div>
					</div>
	                                    
	                <div id="filter_results_info" class='col-12 col-md text-center text-md-right p-2'>
	                    <div id="filter_results_collections_disp" ><span id="filter_results_collections_count"></span> collections</div>
	                    <div id="filter_results_divider" > / </div>
	                    <div id="filter_results_bags_disp"><span id="filter_results_bags_count"></span> bags</div>
	                    <div id="filter_results_divider2" > / </div> 
	                    <div id="filter_results_notebooks_disp"><span id="filter_results_notebooks_count"></span>  with notebooks</div>
	                    
	                    found
	                </div>
				</div>
				<div class='row'>
	                <div id="filter_selected_criteria" class='col'>
	                    <ul>
	                    </ul>
	                </div>
	            </div>
            </div>
            <div class='container pr-0'>
           		<div id="filter_results_objects" class='row d-flex justify-content-center justify-content-md-start p-0 m-0'>
           		</div>
            </div>
            <div id="filter_pagination" class="py-3">
                <div class="prev_page">&lt;</div>
                <div class="page_info"></div>
                <div class="next_page">&gt;</div>
            </div>
        </div>
</div>
</div>