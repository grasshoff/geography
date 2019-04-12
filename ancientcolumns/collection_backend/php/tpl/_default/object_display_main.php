<?php
$numObjsPerPage = 5;
?>

<?php
foreach ($tpl->main_sections as $i => $def):
    $v = stringFromObjectData($tpl->object_data, $def);
    if ($v == '' || is_null($v)) {
        continue;
    }
?>
    <div class="pInfoText">
        <div id="<?php echo sluggify(htmlentities($def->title)) ?>"><h2><?php echo htmlentities($def->title); ?></h2></div>
        <?php echo $v ?>
        <hr class="whiteLine">
    </div>
<?php
endforeach;
?>

<?php
$sectionCounter = count($tpl->main_sections) + 1;
include('object_display_main_resources.php');
?>