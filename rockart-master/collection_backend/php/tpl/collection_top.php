<div id="collection_top" class="row p-0 m-0 pb-2">

	<div class="title_img col-12 col-md-9" style="background-image:url('<?php echo htmlentities($tpl->top_image) ?>')"> 
        <div class="title_container">
            <h1 style='opacity:0'><span><?php echo htmlentities($tpl->title) ?></span></h1>
        </div>
        <?php if (isset($tpl->collection_image_copyright) && $tpl->collection_image_copyright): ?>
            <div class="image_copyright">&copy; <?php echo htmlentities($tpl->collection_image_copyright) ?></div>
        <?php endif ?>
    </div>
    
    <div class="top_nav col-12 col-md-3">
    	<div class='container p-0 ml-md-3'>
	        <div class='row pt-3 pt-md-0 pr-0 pr-md-3'> 
	            <?php foreach ($tpl->top_menu as $item): ?>
                    <a class='collectionTopButton col-4 col-md-12 justify-content-center align-items-center justify-content-md-end align-items-md-end d-flex <?php echo htmlentities($item['class'])?>' href="<?php echo htmlentities($item['url']) ?>">
                        <span class="p-2"><?php echo htmlentities($item['title']) ?></span>
                    </a>
	            <?php endforeach; ?>
	        </div>
		</div>
    </div>
</div>
