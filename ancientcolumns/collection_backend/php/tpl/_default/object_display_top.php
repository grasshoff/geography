
<?php
$hasImages = false;
if (count($tpl->object_data->images) > 0){
    $hasImages = true;
}

$dispNoImagePlaceholder = isset($tpl->general->no_image_placeholder) ? $tpl->general->no_image_placeholder : true;
?>

<div id="sciSlider">
	<div class='title row col'>
	    <h1 class='inline'><?php echo stringFromObjectData($tpl->object_data, $tpl->top_title) ?></h1>
	    <?php if (isset($tpl->top_subtitle)):?>
	    	<h2 class='inline'><?php echo stringFromObjectData($tpl->object_data, $tpl->top_subtitle) ?></h2>
	    <?php endif?>
    </div>
    
    <div id="wrapLeft" class="row">
    	<div class='col-12 col-md-9 mb-3'>
            <?php if ($hasImages): ?>
                <?php include('object_display_top_gallery_main_image.php') ?>
            <?php elseif ($dispNoImagePlaceholder): ?>
                <div class="mainImg"></div>
            <?php endif ?>
            <div class="sciSliderMainText" style="font-style: italic">
                <?php foreach ($tpl->top_attributes as $def): ?>
                    <?php
                    $val = stringFromObjectData($tpl->object_data, $def);
                    if (!$val) continue;
                    ?>
                    <?php echo htmlentities($def->title) ?>:
                    <?php echo $val; ?>
                    <br>
                <?php endforeach ?>
            </div>
        </div>
        <?php if ($hasImages): ?>
            <?php include('object_display_top_gallery.php') ?>
        <?php endif; ?>
    </div>
</div>
