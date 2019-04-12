<?php
$build_hierarchy_nav = null;

$build_hierarchy_nav = function($h, $lvl=1) use (&$build_hierarchy_nav) {
	$hasChildren = false;
	if (isset($h['branches'])){
		$hasChildren = true;
		//highest level is "Attributes" and "Resource Type"
		//no need to encapsule them (they already have the "root" element")
		if ($lvl > 1){
			echo "<div class='filter_branch'>";
		}
	}
		
	echo "<div class='row'>";

	if ($lvl > 2){
		echo '<div class="col-'.($lvl-2).' p-0"></div>';
	}
	
    if (isset($h['title'])) {
        $title = htmlentities($h['title']);

        if (isset($h['leaf'])) {
            $crit = $h['leaf']['filter_criterion'];
            $crit_vals = is_array($crit[1]) ? implode(',', $crit[1]) : $crit[1];
            $crit_html = '<div class="filter_crit col-'.(10-($lvl-2)).' pr-0" filter:by="%s" filter:values="%s">%s</div>';
            $crit_html .= '<div class="filter_crit_count counter col-2 p-0"></div>';
            echo sprintf($crit_html, $crit[0], $crit_vals, $title);
        } else {
            echo sprintf('<div class="filter_branch_title col">%s</div>', $title);
        }
    }

    echo "</div>";
    
    if ($hasChildren) {
    	echo "<div class='filter_children'>";
    	
        $list_classes = array('filter_branch');
        if (isset(array_values($h['branches'])[0]['leaf'])) {
            array_push($list_classes, 'filter_branch_leaf');
        }
        foreach ($h['branches'] as $k => $sub) {
            $build_hierarchy_nav($sub, $lvl+1);
        }
        if (count($h['branches']) > 5) {
            echo '<div class="filter_nav_all_elems_toggle">show all</div>';
        }
        
        echo "</div>";        
        
        //highest level is "Attributes" and "Resource Type"
        //no need to encapsule them (they already have the "root" element")
        if ($lvl > 1){
        	echo "</div>";
        }
    }
}

?>

<div class='row'>
	<div class='col-sm-12'>
		<h2>Refine your search</h2>
	</div>
</div>
<div class='row'>
	<?php foreach ($tpl->hierarchy_defs as $title => $hierarchy_data): ?>
	    <div class="filter_select_category container">
			<div class='row'>
				<div class='col-sm-12'>
			        <h3><?php echo htmlentities($title) ?></h3>
				</div>
			</div>
	
            <?php
            if ($tpl->context == 'filter_collections' || strtolower($title) == 'resource type') {
                $add_class = 'filter_branch_leaf';
            } else {
                $add_class = '';
            }
            ?>
            <div class="filter_nav_tree_root container <?php echo $add_class ?>">
                <?php
                $build_hierarchy_nav($hierarchy_data);
                ?>
            </div>
	    </div>
	<?php endforeach ?>
</div>