<div class="mainImg" style="min-width: 0px; background: none;">
    <div class="sciLinkBigImg">
        <a href="#" target="_blank">
            <img src="/img/sci.png">
        </a>
    </div>

    <?php
    $i = 0;
    foreach ($tpl->object_data->images as $sciId => $figs):
        foreach ($figs as $fig):
            $imgAlt = $imgAlt = implode('/', array($tpl->proj_meta->shorttitle, 'single', $sciId, $fig->id));
            ?>
            <a href="/collection/<?php echo $imgAlt?>">
            <div class="sciSliderMainImage imgBG">
                <?php if ($tpl->proj_meta->uses_sci_version <= 1.0): ?>
                    <img src="<?php echo $tpl->sci_thumbs['images'][$sciId] . $fig->name ?>" alt="<?php echo $imgAlt; ?>" class="mainImage img-fluid" id="image<?php echo $i; ?>" style="display: <?php if ($i == 0) echo 'inline'; else echo 'none'; ?>;">
                <?php else: ?>
                    <img <?php if ($i != 0){ echo "data-"; } ?>src="<?php echo thumbSmall($tpl->sci_thumbs['images'][$sciId], $fig->file) ?>" alt="<?php echo $imgAlt; ?>" class="mainImage img-fluid" id="image<?php echo $i; ?>" style="display: <?php if ($i == 0) echo 'inline'; else echo 'none'; ?>;">
                <?php endif ?>
            </div>
            </a>
        <?php
        $i++;
        endforeach;
        ?>
    <?php endforeach; ?>
</div>