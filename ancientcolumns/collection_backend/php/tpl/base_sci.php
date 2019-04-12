<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="">
    <meta name="author" content="">
    <title><?php echo $tpl->title ?></title>
    <?php if (ETRepoConf::$SERVER_CONTEXT != "online"):?>
	    <META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">
    <?php endif?>
    
    <?php foreach ($tpl->css_files as $css_file):  ?>
        <link href="/css/<?php echo $css_file."?version=".ETRepoConf::$CSSJS_VERSION ?>" rel="stylesheet" type="text/css">
    <?php endforeach;  ?>

    <?php foreach ($tpl->js_files as $js_file):  ?>
        <script src="/js/<?php echo $js_file."?version=".ETRepoConf::$CSSJS_VERSION ?>"></script>
    <?php endforeach;  ?>
</head>

<body class='d-flex flex-column'>

<?php require_once('menu.php') ?>

<div class="container">
    <?php echo $tpl->content ?>

    <?php $obj_vars = get_object_vars($tpl->sci_metadata); ?>
    <?php if (count($obj_vars) > 0): ?>
    <div id="sciMetaData" class="open row"> <a href="#" id="toggle_meta">&gt;</a>
        <div id="meta" class="ps-container ps-active-y ">
			<div class="panel-group" id="accordion">
        		<?php $i = 1; foreach ($obj_vars as $sci_section_title => $sci_section_data): ?>
                    <div class="panel panel-default">
                        <div class="panel-heading ">
                            <a data-toggle="collapse" href="#collapse<?php echo $i ?>" class="collapsed">&nbsp;<h2 class="panel-title ">&gt; <?php echo htmlentities($sci_section_title) ?></h2></a>
                        </div>
                        <div id="collapse<?php echo $i ?>" class="panel-collapse collapse px-3" style="height: 0px;">
                            <div class="panel-body">
                                <?php if (is_object($sci_section_data)): ?>                                
                                    <?php foreach (get_object_vars($sci_section_data) as $sci_entry_title => $sci_entry_value): ?>
                                       	<?php if (!isset($sci_entry_value) || ( (gettype($sci_entry_value) == 'array') && sizeof($sci_entry_value) == 0) || ( (gettype($sci_entry_value) != 'array') && strlen($sci_entry_value)==0)) continue;?>

                                    	<?php //if an entry is an array, assume that it is a litref ?>
                                		<?php if (gettype($sci_entry_value) == 'array'): ?>
											<span class="metaAttributeTitle italic"><?php echo htmlentities($sci_entry_title) ?>:</span> <?php echo formatLitRefs($sci_entry_value, $this->projMeta) ?><br>
										<?php else:?>
                                        	<span class="metaAttributeTitle italic"><?php echo htmlentities($sci_entry_title) ?>:</span> <?php echo ETRepoHTMLPurifier::cleanHTML($sci_entry_value) ?><br>
                                        <?php endif;?>
                                    <?php endforeach ?>
                                <?php elseif (is_string($sci_section_data)): ?>
                                    <?php echo $sci_section_data ?>
                               	<?php //if an entry is an array, assume that it is a litref ?>
                                <?php elseif (gettype($sci_section_data) == 'array'): ?>
                                    <?php
	                                    echo formatLitRefs($sci_section_data, $this->projMeta);
                                    ?>
                                <?php endif ?>
                            </div>
                        </div>
                    </div>
                <?php $i++; endforeach ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
//move the metadata div to the side-menu or inline depending on bootstrap breakpoint

//https://stackoverflow.com/questions/18575582/how-to-detect-responsive-breakpoints-of-twitter-bootstrap-3-using-javascript
//https://stackoverflow.com/a/22885503
?>
<div class="bootstrap-breakpoint-xs"></div>
<div class="bootstrap-breakpoint-sm d-none d-sm-block"></div>
<div class="bootstrap-breakpoint-md d-none d-md-block"></div>
<div class="bootstrap-breakpoint-lg d-none d-lg-block"></div>
<div class="bootstrap-breakpoint-xl d-none d-xl-block"></div>
<script>
	(function(){
        function isBreakpoint( alias ) {
            return $('.bootstrap-breakpoint-' + alias).is(':visible');
        }
    
        // "open" means that the menu is not visible
        // and the buttons funtion is to "open" it
        function showMeta(){
        	$("#sciMetaData").removeClass('open');
        	$("#toggle_meta").html('<');
        	sciMetaDataOpenedTimestamp = new Date().getTime();
        }

        function hideMeta(){
        	$("#sciMetaData").addClass('open');
        	$("#toggle_meta").html('>');
        }
        
        function toggleMeta(){
        	if ($("#sciMetaData").hasClass('open')) {
        		showMeta();
        	} else {
        		hideMeta();
        	}
        }
        
        var sciMetaDataOpenedTimestamp = 0;
        
        $('body').click(function(e) {
        	if (isBreakpoint('md')){
            	if (e.target.id && e.target.id == "toggle_meta"){
            		e.preventDefault();
            		toggleMeta();
            	} else {
                	// close metadata if clicked somewhere else        
                    var otherMetaDiv = $('#sciMetaData');
                    var metaDiv = $('#meta');
                    var metaDivOffset = metaDiv.offset()
                    var bb = [
                        metaDivOffset.left, metaDivOffset.top,
                        metaDivOffset.left + metaDiv.width(), metaDivOffset.top + metaDiv.height()
                    ];
                    var x = e.clientX;
                    var y = e.clientY;
                    var currMs = new Date().getTime();
                    if (currMs - sciMetaDataOpenedTimestamp > 500 && !otherMetaDiv.hasClass('open') && !(x >= bb[0] && x <= bb[2] && y >= bb[1] && y <= bb[3]))  {
                    	hideMeta();
                    }
            	}
        	}
        });

        function moveMetadataToCorrectPlace( alias ) {
            if (isBreakpoint('md')){
            	$('#meta').css({ 
                	"position": "absolute",
                	"width": '',
                	"margin-left": '',
                	"top": '',
                	"height": ''
				});
            	$('#accordion .collapse').collapse('hide');
            	$("#toggle_meta").show();
            	hideMeta();
            } else {
            	$('#meta').css({ 
                	"position": "relative",
                	"width": '100%',
                	"margin-left": 0,
                	"top": 0,
                	"height": 'auto'
                	
				});
            	$('#accordion .collapse').collapse('show');
            	$("#toggle_meta").hide();
            	showMeta();
            }
        }

        moveMetadataToCorrectPlace();
        $(window).resize(function () {
        	moveMetadataToCorrectPlace();
        });
	})();
</script>

<script>
    function updateHorSliderItemCountsDisplay(button) {
        var prevCount = 0;
        var thisCount = 0;

    	$(button).parent().find('.horSliderWrap div.horSlider').each(function(){
        	var thisChildrenCound = $(this).children().length;
    		if ($(this).is(":visible")){
    			thisCount = thisChildrenCound;
    			return false;
    		}
			prevCount += thisChildrenCound;
    	});

    	var posDisplay = $(button).parent().prev().find(".horSliderItemCounts");
    	posDisplay.text(""+(prevCount+1) + ' - ' + (prevCount+thisCount));
    }

    $(document).on("click",'.gal_prev', function(e){

    	var test = $('.horSliderWrap div.horSlider:visible');
        if ($(this).parent().find('.horSliderWrap div.horSlider:visible').prev().length != 0){
            $(this).parent().find('.horSliderWrap div.horSlider:visible').hide().prev().show();
        }

        updateHorSliderItemCountsDisplay(this);

        return false;
    });
    
    $(document).on("click", '.gal_next',function(e){
        if ($(this).parent().find('.horSliderWrap div.horSlider:visible').next().length != 0){
            $(this).parent().find('.horSliderWrap div.horSlider:visible').hide().next().show() ;
        }

        updateHorSliderItemCountsDisplay(this);

        return false;
    });

    $(document).ready(function ($) {
        $('.gal_prev').each(function() {
            updateHorSliderItemCountsDisplay(this);
        });
    });

    var scroll = $('#scroll');
    var offset = scroll.offset();
    $('.jump  a').click(function(e) {
        e.preventDefault();
        var top = $( $(this).attr('href') ).position().top;
        scroll.animate({top: -top +10}, 500);
    });
</script>


</body>
</html>
