<div id="collection_container" class="container p-0">
    <?php echo $tpl->top_html ?>

    <div id="collection_page" class="row pt-2">
        <?php echo $tpl->sidebar_html ?>
        <div id="collection_main" class="col">
            <?php foreach ($tpl->content_sections as $i => $section): ?>
                <div class="content_section <?php if ($i == 0) echo 'first' ?>">
                    <a name="<?php echo sluggify($section['title']) ?>"></a><h1><?php echo htmlentities($section['title']) ?></h1>
                    <?php echo $section['content_html'] ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>