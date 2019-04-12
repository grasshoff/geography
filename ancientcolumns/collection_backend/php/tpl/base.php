<!DOCTYPE html>
<html lang="en">
	<head>
	    <meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">	    
	    <meta http-equiv="X-UA-Compatible" content="IE=edge">
	    <meta name="description" content="">
	    <meta name="author" content="">
	    <title><?php echo $tpl->title ?></title>
	    <?php if (ETRepoConf::$SERVER_CONTEXT != "online"):?>
		    <META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">
	    <?php endif?>
	    <?php foreach ($tpl->css_files as $css_file):  ?>
	        <link href="/css/<?php echo $css_file."?version=".ETRepoConf::$CSSJS_VERSION ?>" rel="stylesheet" type="text/css">
	    <?php endforeach;  ?>
	</head>
	
	<body class="<?php echo $tpl->page_type ?>">
		<?php require_once('menu.php') ?>
		<div class="container" style="flex: 1; -webkit-box-flex: 1; -webkit-flex: 1; -ms-flex: 1;">
        	<?php echo $tpl->content ?>
		</div>
	
	<?php foreach ($tpl->js_files as $js_file):  ?>
	    <script src="/js/<?php echo $js_file."?version=".ETRepoConf::$CSSJS_VERSION ?>"></script>
	<?php endforeach;  ?>
	
			<footer class="sticky-bottom footer">
				<div class="container p-3">
					<div class="row">
						<div class="col-12 col-md-4 p-3">
							<h2>Initiator</h2>
							<div>
								<a href="http://topoi.org" title="Exzellenzcluster Topoi">Exzellenzcluster Topoi</a>
							</div>
							<div>
								<a href="mailto:edition@topoi.org">Kontakt</a>
							</div>
							<div>
								<a href="http://www.edition-topoi.org/publishing_with_us/imprint">Impressum</a>
							</div>							
						</div>
						
						<div class="col py-3">
							<h2>Partner</h2>
							<div>
								<a href="http://www.topoi.org/institution/freie-universitaet-berlin/" title="Freie Universität Berlin">Freie Universität Berlin</a>
							</div>
							<div>
								<a href="http://www.topoi.org/institution/humboldt-universitaet-zu-berlin/" title="Humboldt-Universität zu Berlin">Humboldt-Universität zu Berlin</a>
							</div>
							<div>
								<a href="http://www.topoi.org/institution/berlin-brandenburgische-akademie-der-wissenschaften/" title="Berlin-Brandenburgische Akademie der Wissenschaften">Berlin-Brandenburgische Akademie der Wissenschaften</a>
							</div>
							<div>
								<a href="http://www.topoi.org/institution/deutsches-archaeologisches-institut/" title="Deutsches Archäologisches Institut">Deutsches Archäologisches Institut</a>
							</div>
							<div>
								<a href="http://www.topoi.org/institution/max-planck-institut-fuer-wissenschaftsgeschichte/" title="Max Planck Institut für Wissenschaftsgeschichte">Max Planck Institut für Wissenschaftsgeschichte</a>
							</div>
							<div>
								<a href="http://www.topoi.org/institution/stiftung-preussischer-kulturbesitz/" title="Stiftung Preußischer Kulturbesitz">Stiftung Preußischer Kulturbesitz</a>
							</div>
						</div>
						<div class="col-12 col-md-4 p-3">
							<a href="https://assessment.datasealofapproval.org/assessment_228/seal/html/"><img border="0" src="/img/CoreTrustSeal-logo-transparent-small.png" alt="coretrust_logo" width="80"/></a>
							<a href="https://doi.org/10.17616/R3CF6Z"><img src="/img/re3data.svg"></a>
						</div>
					</div>
				</div>
			</footer>	
	</body>
</html>
