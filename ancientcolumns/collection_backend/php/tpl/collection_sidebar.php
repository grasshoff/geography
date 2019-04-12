<div id="collection_sidebar" class="col-3 d-none d-md-block">
    <div class="menu container p-0">
        <ul>
            <?php foreach ($tpl->sidebar_menu as $item): ?>
                <li>
                    <a href="<?php echo htmlentities($item['url']) ?>">
                        <?php echo htmlentities($item['title']) ?>
                    </a>
                </li>
            <?php endforeach ?>
        </ul>
    </div>
	<!-- 
    <?php if (isset($tpl->download_collection_json_link)): ?>         
    <div class="menu cite">             <ul>                 <li><a href="<?php echo htmlentities($tpl->download_collection_json_link) ?>"><img src="/img/sci.png">Download JSON</a></li>             </ul>         </div>     <?php endif ?>
     -->

    <?php foreach ($tpl->sidebar_infoboxes as $infobox): ?>
        <div class="infobox">
            <h3><?php echo htmlentities($infobox['title']) ?></h3>
            <?php echo $infobox['content_html'] ?>
        </div>
    <?php endforeach ?>
</div>