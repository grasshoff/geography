<br class="clearfloat">
<div class="row">
    <div class="historyLink col">
        <a href="<?php echo($tpl->collection_link)  ?>">< <?php echo($tpl->collection_title) ?></a>
		<?php 
			if ($tpl->related_object){
				$relatedObjectID = $tpl->related_object;
				$relatedObjectURL = $this->makeURL(array($this->projName, 'object', $relatedObjectID));
				//this should be done differently later
				//fetch the json of the related object to retrieve name
				//this CAN be more than one, so this field should be an array
				//right now it only displays a random one (first object to see on import)
				
				//we could assume that this exists. but this would break on bad imports
				$objTitle = $relatedObjectID;
				$objData = $this->db->findSingleObjectById($relatedObjectID, false);
				if ($objData !== FALSE){
				   $objTitle = $objData->value->metadata->{"General Information"}->Title;
				} else {
					//broken metadata or invalid id
				    if (ETRepoConf::$SERVER_CONTEXT != "online"){
				        echo "related object-identifier not found";
				    }
				}
				?><a href="<?php echo $relatedObjectURL ?>"> < <?php echo htmlentities($objTitle) ?></a>
		<?php } ?>
    </div>
</div>
<div class="row">
	<div class="col p-3">
		<div class="title">
                <h1 class="inline">
					<a><?php echo htmlentities($tpl->title) ?></a>
                </h1>
			<?php if ($tpl->subtitle && (strlen($tpl->subtitle)>0)):?> 
                <h2 class="inline"><?php echo $tpl->subtitle ?></h2>
			<?php endif?>
		</div>
	</div>
</div>

<div id="wrapFrame" class="row">
    <div id="iFrame"></div>
</div>

<div class="btnsUnderIFrame row">
    <div class="info_left col-12 col-md-9">
        <dl>
        	<?php if (isset($tpl->doi) && (strlen($tpl->doi)>0)): ?> 
            <dt>DOI:</dt><dd><?php echo htmlentities($tpl->doi) ?></dd>
            <?php endif ?>
            <dt>Citation:</dt>
            <dd>
                <?php echo ETRepoHTMLPurifier::cleanHTML($tpl->citation_str) ?>
            </dd>
        	<?php if (isset($tpl->doi) && (strlen($tpl->doi)>0)): ?> 
            <dt></dt>
            <dd>
                <a class='citationFormatter' target='_blank' href='/citeproc/citeproc.html?doi=<?php echo htmlentities($tpl->doi); ?>'>&rarr; Citation Formatter</a>
            </dd>
        	<?php endif ?> 
            <dt>License:</dt>
            <dd>
                <?php 
                	$rightsString = "<i>(unknown)</i>";
                	
                	$conditionsForUse = $tpl->sci_metadata->{'Conditions for use'};
                	if (isset($conditionsForUse->{'Rights'}) && $conditionsForUse->{'Rights'}){
                		$rightsString = htmlentities($conditionsForUse->{'Rights'});
                	} else if (isset($conditionsForUse->{'Copyright notice'}) && $conditionsForUse->{'Copyright notice'}){
                		$rightsString = htmlentities($conditionsForUse->{'Copyright notice'});
                	} else if (isset($conditionsForUse->{'Additional copyright information'}) && $conditionsForUse->{'Additional copyright information'}){
                		$rightsString = htmlentities($conditionsForUse->{'Additional copyright information'});
                	}
                	
                    echo $rightsString;
                ?>
                    <!-- <a href="<?php echo $tpl->proj_meta->license->url ?>" target="_blank">
                        <img src="/img/license_logos/<?php echo $tpl->proj_meta->license->logo ?>" alt="<?php echo $tpl->proj_meta->license->name ?>" />
                    </a> -->
            </dd>
        </dl>
    </div>
    <div class="btns_right col-12 col-md-3">
        <div class="btns_right_container">
            <div id="openInTab">
                <h2 class="downloadButton">Open in tab</h2>
            </div>
            <div id="download">
                <h2 class="downloadButton">Download</h2>
            </div>
            <div id="sciButtonFullScreen" title="expand to fullscreen"><a href="#"></a></div>
        </div>
    </div>
    <div style="clear: both;"></div>
</div>

<div id="relativeOverlayContainer">
    <div id="downloadOverlay" class="row">
        <div class="container col-11">
            <div class="license_text">
                <ul>
                <?php
                $dlOverlayInfo = array(
                    'Rights',
                    'Copyright notice',
                    'Additional copyright information'
                );
    
                foreach ($dlOverlayInfo as $k) {
                    if (isset($tpl->sci_metadata->{'Conditions for use'}->{$k}) && $tpl->sci_metadata->{'Conditions for use'}->{$k}) {
                        echo sprintf('<li><i>%s</i>: %s</li>', htmlentities($k), htmlentities($tpl->sci_metadata->{'Conditions for use'}->{$k}));
                    }
                }
                ?>
                </ul>
            </div>
            <div class="btn_row">
                <ul>
                    <li><a href="<?php echo $tpl->single_sci_download ?>">Download Cite-File</a></li>
                    <?php foreach ($tpl->single_resource_downloads as $res_file_title => $res_file): ?>
                        <li><a href="<?php echo $res_file ?>">Download resource file '<?php echo htmlentities($res_file_title) ?>'</a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="close col-1">
        	<a href="#" class="downloadOverlayClose">
        		<img src="/img/close.png" alt="close overlay" />
        	</a>
        </div>
    </div>
</div>


<script>
    <?php if ($tpl->sci_version == 1.0): ?>
        loadsCiJson('<?php echo $tpl->sci_file_url ?>', '#iFrame', <?php echo $tpl->sci_file_item ?>);
    <?php else: ?>
        loadsCiItemJsonV2('<?php echo $tpl->sci_file_url ?>', '#iFrame');
    <?php endif ?>
</script>

<script>
    $('#download').click(function() {
        $('#relativeOverlayContainer').show();
    });
    $('.downloadOverlayClose').click(function() {
        $('#relativeOverlayContainer').hide();
    });
    $('#openInTab').click(function() {
        var src = $("iFrame").attr("src");
        var win = window.open(src, '_blank');
    });

    var element = document.getElementById("iFrame");

    var wrapFrameDivOrigW = $('#wrapFrame').css('width');
    var wrapFrameDivOrigH = $('#wrapFrame').css('height');
    var wrapFrameDivIsWide = false;

    var fullscreen = document.getElementById("sciButtonFullScreen");
    
    fullscreen.addEventListener("click", function(){

		var is3DHOP = false;

		//use "native" fullscreen function for 3DHOP
		try {
	        if(typeof document.getElementsByTagName("iFrame")[0].contentWindow.fullscreenSwitch == 'function'){
	        	is3DHOP = true;
	        }
		}
		catch(err) {
		}
		
        if(is3DHOP){
            document.getElementsByTagName("iFrame")[0].contentWindow.fullscreenSwitch();
        } else {
            if (BigScreen.enabled) {
                        BigScreen.request(element);
                        // You could also use .toggle(element, onEnter, onExit, onError)
                    } else {
                        // fallback for browsers that don't support full screen
                        if ($('#wrapFrame').hasClass('modal-backdrop')){
                            $('#wrapFrame').removeClass('modal-backdrop');
                            $('#copy').show();
                            $('#citation').show();
                            $('#wrapFrame').css({'width':'735px'});
                            $('#wrapFrame').css({'height':'433px'});
                            $('#iFrame').css({'margin':'0'});
                            $(this).attr('title','expand');
                            $('#sciMetaData').css('display','block');
                            $('html').css('overflow-y','scroll');
                            $(this).css({
                                'position':'relative',
                                'float':'right',
                                'margin-top':' -7px',
                                'right':' 0',
                                'margin-left':'14px',
                                'width':'40px',
                                'height':'40px',
                                'background':'url(/img/fullscreenButton.png) left no-repeat'
                            });
                        }
                        else{
                            $('#wrapFrame').addClass('modal-backdrop');
                            $('#copy').hide();
                            $('#citation').hide();
                            $('#wrapFrame').css({'width':'98%'});
                            $('#wrapFrame').css({'height':'94%'});
                            $('#iFrame').css({'margin':'0 auto'});
                            $(this).attr('title','normal');
                            $('html').css('overflow-y','hidden');
                            $(this).css({
                                'position':'fixed',
                                'top':'3px',
                                'right':'4%',
                                'width':'34px',
                                'height':'33px',
                                'background':'url(/img/close.png) left no-repeat',
                                'background-size':'70% 70%'
                            });
                            $('#sciMetaData').css('display','none');
                        }
                    }
        }
    });
</script>