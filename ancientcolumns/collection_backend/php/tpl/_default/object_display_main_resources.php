<?php
$i = $sectionCounter + 1;
foreach ($tpl->resource_sections as $resDef):
    $resType = $resDef->resources;
    if (!isset($tpl->object_data->resources[$resType])
     || !is_array($tpl->object_data->resources[$resType])
     || count($tpl->object_data->resources[$resType]) <= 0)
    {
        continue;
    }
    $resObjs = $tpl->object_data->resources[$resType];
    $numResObjs = 0;
    foreach ($resObjs as $resObjArr) {
        $numResObjs += count($resObjArr);
    }
    $makeGallery = $numResObjs > $numObjsPerPage;
?>
    <div class="pInfoText" id="<?php echo sluggify(htmlentities($resDef->title))?>">
        <h2><?php echo htmlentities($resDef->title) ?></h2>
        <h3 class="inline"><?php if ($makeGallery): ?>showing <span class="horSliderItemCounts"></span> of <?php echo $numResObjs; ?> items<?php endif ?></h3>
    </div>
    <div class="smallImg">
        <?php
        if ($makeGallery) {
            $imgExtraAttr = ' width="58px" height="58px"';
            echo '<div class="horSliderWrap">';
        } else {
            $imgExtraAttr = '';
        }
        ?>
        <?php
        $galImgAbsIdx = 0;
        $galImgIdx = 0;
        $galImgPage = 1;
        foreach ($resObjs as $sciId => $resObjArr):
            foreach ($resObjArr as $res_obj_idx => $res_obj):
                $sci_internal_id = isset($res_obj->id) ? $res_obj->id : $res_obj_idx;
                $to_single_url = $tpl->sci_base_links[$resType][$sciId] . '/' . $sci_internal_id;

                if ($tpl->proj_meta->uses_sci_version > 1.0) {
                    $res = $tpl->sci_data[$resType][$sciId]->resources[$res_obj_idx];
                } else {
                    $res = $tpl->sci_data[$resType][$sciId][$res_obj_idx];
                }

                if ($makeGallery) {
                    if ($galImgIdx % $numObjsPerPage == 0) {
                        if ($galImgPage > 1) {
                            $horSliderExtraAttr = ' style="display:none"';
                            echo '</div>';  // previous horSlider
                        } else {
                            $horSliderExtraAttr = '';
                        }

                        echo '<div class="horSlider" ' . $horSliderExtraAttr . '>';
                        $galImgIdx = 0;
                        $galImgPage++;
                    }

                    echo '<div class="PSContainer ' . $galImgIdx . '">';
                } else {
                    echo '<span class="smallImgContainer">';
                }

                if ($tpl->proj_meta->uses_sci_version <= 1.0) {
                    echo '<img src="' . $tpl->sci_thumbs[$resType][$sciId] . '/' . str_replace('.sCi', '.png', sprintf($tpl->proj_meta->sci_repos_file_format, $sciId)) . '" ' . $imgExtraAttr . '>';
                } else {
                    if (isset($res->thumb)) {   // fixed thumb
                        $resThumbDir = isset($res->thumb_dir) ? $res->thumb_dir : $tpl->sci_thumbs[$resType][$sciId];
                        $resThumb = implode('/', array($resThumbDir, $res->thumb));
                    } else {
                        $resThumb = thumbSmall($tpl->sci_thumbs[$resType][$sciId], $res->file);
                    }

                    echo '<img src="' . $resThumb . '" alt="' . $res->id . '"' . $imgExtraAttr . '>';
                }

                // if no name is supplied, use filename without extension
                $resTitle = @$res->file;
                if (!isset($res->name) || is_null($res->name) || (strlen($res->name)==0)){
                    $filenameParts = pathinfo($res->file);
                    $resTitle = $filenameParts['filename'];
                } else {
                    $resTitle = $res->name;
                }
                
                if (!$makeGallery) {
                    echo '<a class="galSubtitle" href="' . $to_single_url . '" target="_blank">' . htmlentities($resTitle) . '</a>';
                    echo '</span>'; // smallImgContainer
                }
                ?>

                <a href="<?php echo $to_single_url; ?>" target="_blank"><img src="/img/sci.png"> </a>

                <?php
                if ($makeGallery) {
                    echo '<div class="imTitleAndLink"><a href="' . $to_single_url . '" target="_blank">' . htmlentities($resTitle) . '</a></div>';
                    echo '</div>';  // PSContainer ...
                    $galImgIdx++;
                }

                $galImgAbsIdx++;
                ?>
            <?php endforeach ?>
        <?php endforeach ?>
        <?php
        if ($makeGallery) {
            echo '</div>';  // horSlider
            echo '</div>';  // horSliderWrap

            echo '<div class="gal_prev"><span title="prev"> &lt;&lt; </span></div>';
            echo '<div class="gal_next"><span title="next"> &gt;&gt; </span></div>';
        }
        ?>
    </div>
    <hr class="whiteLine">
<?php
$i++;
endforeach;
?>