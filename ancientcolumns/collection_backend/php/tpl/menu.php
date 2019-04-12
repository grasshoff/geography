<div class="navbar fixed-top navbar-default navbar-dark p-0">
	<div class='container'>
		<a class="navbar-brand external-with-img" href="/">
			<img src="/img/edition-topoi_logo.svg" height="50" class="d-inline-block align-top" alt="">
		</a>
		<button id="menu-button" class="navbar-toggler dropbtn" type="button" aria-controls="menu" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		
        <div id="menu-content" class='container fixed-top'>
        	<div class="row">
        		<div class="d-none d-md-block col-md-6 col-lg-4 menuLight">
        			<ul>
        		
        				<li><a href="//edition-topoi.org/books/books">Bücher </a></li>
        				<li><a href="//edition-topoi.org/publications">Journale</a></li>
        				<li><a href="//edition-topoi.org/articles">Artikel</a></li>				
        				<li><a href="//edition-topoi.org/contributors">Autoren</a>	</li>
        				<li><a href="//edition-topoi.org/publishing_with_us/debook">dEbook Viewer</a></li>
        			</ul>
        		
        			<div class="menuBottom">
        				<ul>
        					<li><a href="//edition-topoi.org/publishing_with_us/printed-book">Über das gebundene Buch</a></li>
        					<li><a href="//edition-topoi.org/publishing_with_us/debook">Über das dEbook</a></li>
        				</ul>
        			</div>
        		</div>
        		
        		<div class="col-12 col-md-6 col-lg-4 menuMedium">
        			<ul>
        				<li><a href="/">Collections</a></li>				
        			</ul>
        							
    				<ul id="menuScroll" class="scrollCollections ps-container">
    					<?php
    						$collMeta = $this->getAllCollectionsMeta();
    						usort($collMeta, function ($a, $b) {
    							return strcmp($a->title, $b->title);
    						});
    						
    						foreach ($collMeta as $collName => $meta) {
    							?><li><a href="/collection/<?php echo $meta->shorttitle ?>"><?php echo $meta->title ?></a></li><?php
    						}
    					?>
    				</ul>												
        
        			<div class="menuBottom">
        				<ul>
        				<li><a href="//edition-topoi.org/publishing_with_us/collections">Die Collections</a></li>
        				<li><a href="//edition-topoi.org/publishing_with_us/citable">Das Citable</a></li>
        				</ul>												
        			</div>
        		</div>
        		
        		<div class="d-none d-lg-block col-lg-4 menuDark">
        			<ul>
        				<li><a href="//edition-topoi.org/publishing_with_us">Publizieren mit Topoi</a></li>
        				<li><a href="//edition-topoi.org/publishing_with_us/open-access">Open Access</a></li>
        				<!--  <li><a href="//edition-topoi.org/publishing_with_us/for-authors">Service</a></li>  -->
        				<li><a href="//edition-topoi.org/publishing_with_us/for-authors">Für Autoren</a></li>
        				<!--  <li><a href="//edition-topoi.org/publishing_with_us/for-authors">Tutorials</a></li>  -->
        				<li><a href="//edition-topoi.org/publishing_with_us/our-partners">Partner</a></li>
        				<li><a href="//edition-topoi.org/publishing_with_us/contact">Kontakt</a></li>
        			</ul>
        		</div>		
        	</div>
        </div>		
	</div>
</div>

