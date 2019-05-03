<div class="historyLink">
    <a href="<?php echo($tpl->collection_link)  ?>">< <?php echo($tpl->collection_title) ?></a>
    <?php if (isset($tpl->subtitle)) echo $tpl->subtitle ?>
</div>
    
<?php 
if (count($tpl->object_data->images) > 0){
	$hasImages = true;	
}
?>

<?php echo $tpl->object_content_top; ?>

<div class="row pInfoText">

    <div id="scroll" class="col-12 col-md-9 ">
        <?php echo $tpl->object_content_main; ?>
    </div>

    <div class="col-12 col-md-3">
        <?php echo $tpl->object_content_right; ?>
        
        <div class="pInfoSidebar">
            <h3>Citable</h3>
            <p>
				<a href="<?php echo $tpl->object_json_export_link ?>">Download</a>
			</p>
        </div>
        
		<?php if (isset($tpl->proj_meta->license->url) && (strlen($tpl->proj_meta->license->url)>0)):?>
        <div class="pInfoSidebar">
            <h3>License</h3>
            <p>
                <a href="<?php echo $tpl->proj_meta->license->url ?>" target="_blank">
                    <img src="/img/license_logos/<?php echo $tpl->proj_meta->license->logo ?>" alt="<?php echo $tpl->proj_meta->license->name ?>" />
                </a>
            </p>
        </div>
        <?php endif?>
    </div>
</div>
