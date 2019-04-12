<div class="sciSliderMainThumbs col-12 col-md-3"> 
    <?php
    $figBreakNum = $tpl->proj_meta->gallery_num_fig_per_page - 1;
    $i = 0;
    foreach ($tpl->object_data->images as $sciId => $figs):
        foreach ($figs as $fig):
            if ($i % $figBreakNum == 0) {
                $galPage = $i / $figBreakNum + 1;
                $galDisp = $galPage == 1 ? 'block' : 'none';
                if ($galPage > 1) {
                    echo '</div>'; // close previous page
                }

                echo '<div class="thumbsContainer' . $galPage . '" style="display:' . $galDisp . '">';
            }

            $imgAlt = implode('/', array($tpl->proj_meta->shorttitle, 'single', $sciId, $fig->id));
            ?>

            <div class="thumbWrap">
                <?php if ($tpl->proj_meta->uses_sci_version <= 1.0): ?>
                    <img src="<?php echo $tpl->sci_thumbs['images'][$sciId] . $fig->name ?>" alt="<?php echo $imgAlt ?>" class="thumbN" id="thumb<?php echo $i; ?>">
                <?php else: ?>
                    <img src="<?php echo thumbTiny($tpl->sci_thumbs['images'][$sciId], $fig->file) ?>" alt="<?php echo $imgAlt ?>" class="thumbN" id="thumb<?php echo $i; ?>">
                <?php endif ?>
            </div>

            <?php
            $i++;
        endforeach;
    endforeach;
    $numImages = $i;
    ?>
</div>
<?php
$numGalPages = (int)($numImages / $figBreakNum) + 1;
if ($numGalPages > 1): ?>
    <div class="thumbsPrev" style="display: none;"><a href="#" onclick="nextThumbs(-1); ">&lt;</a></div>
    <div class="thumbsNext"><a href="#" onclick="nextThumbs(1); ">&gt;</a></div>
<?php endif ?>

<script>
    var curImgGalPage = 1;

    function nextThumbs(dir){
        var changeToPage = curImgGalPage + dir;

        // console.log("current page: " + curImgGalPage + " / will change to page: " + changeToPage + " / has num. pages: " + imgGalNumPages);

        // show/hide pages
        $('.thumbsContainer' + curImgGalPage).hide();
        $('.thumbsContainer' + changeToPage).fadeIn("slow");

        // adjust page navigation
        if (changeToPage <= 1) {
            $('.thumbsPrev').hide();
            $('.thumbsNext').show();
        } else if (changeToPage >= <?php echo $numGalPages; ?>) {
            $('.thumbsNext').hide();
            $('.thumbsPrev').show();
        } else {
            $('.thumbsPrev').show();
            $('.thumbsNext').show();
        }

        // update current page
        curImgGalPage = changeToPage;

        return false;
    }

    $(document).on("click",".thumbN",function(e){
        var thumbId = $(e.target).attr('id');
        var thumbAlt = $(e.target).attr('alt');
        var index = thumbId.substr(5); // strip the "thumb" from "thumb1" to get "1"
        var imageId = "image" + index;

        $('.mainImage').hide();
        var newImage = $('#' + imageId) 
        if (typeof newImage.attr("src") === "undefined"){
            var dataSrc = newImage.attr("data-src"); 
        	newImage.attr("src", dataSrc);
        }
        newImage.show();
        var hyperLink= $('.sciLinkBigImg a').attr('href');
        
        if (typeof hyperLink != "undefined") {
            $('.sciLinkBigImg a').attr('href', '/collection/' + thumbAlt);
        }
    });

    <?php
        $firstFigSci = array_keys($tpl->object_data->images)[0];
        $firstFigObj = $tpl->object_data->images[$firstFigSci][0]
    ?>
    $('.sciLinkBigImg a').attr('href', '/collection/<?php echo implode('/', array($tpl->proj_meta->shorttitle, 'single', $firstFigSci, $firstFigObj->id)); ?>');
</script>