<?php

$current_post_ID = get_the_ID();
$current_category_ID = $args['current_category'];

$ep = akseki_get_theme_from_id($current_post_ID,"post");
$eObj = get_post($ep);
$tpc_topics = get_post_custom_values( "tpc_topics", $ep);
$tt_exp = array_map('trim', explode(',', (string) ($tpc_topics[0] ?? '')));
$tt_mech = [];
foreach($tt_exp as $tt){
    if($tt !== "cpr-level"){
    	$tt_obj = get_category_by_slug($tt); if(!$tt_obj){ continue; }
	    $tt_lev = akseki_get_level_from_id($tt_obj->term_id,"category","level");
	    $tt_lev_obj = get_category($tt_lev);
	    $tt_mech[$tt_lev_obj->name][$tt_obj->term_id] = $tt_obj->name;
    }
}

$ast_display_output = '';
$ast_display_output .= '<div class="sidebar-left-item same-theme detail-sidebar">';
$ast_display_output .= '<div class="toggler-title btn btn-secondary" data-toggle="collapse" href="#collapseStCat" role="button" aria-expanded="false" aria-controls="collapseStCat">';

if($eObj->ID !== 6262){
	$onthetopicof = "On the theme of ";
}else{
	$onthetopicof = '';
}
$ast_display_output .= '<h3>'.$onthetopicof.$eObj->post_title;
$ast_display_output .= '<span class="more-less dashicons dashicons-arrow-down"></span></h3>';

$ast_display_output .= '</div>';
$ast_display_output .= '<div class="collapse '.$eObj->post_name.'" id="collapseStCat">';


foreach ( $tt_mech as $keytt=>$tt ) :
       
       $classname = preg_replace('/\W+/','',strtolower(strip_tags($keytt)));
        if($eObj->ID !== 6262){
			$ast_display_output .= '<h5 class="mt-3 mb-0 mx-0 p-1 cn_'.$classname.'"><span class="">'.$keytt.':</span></h5>';
		}
		$ast_display_output .= '<ul class="mt-0 list-group list-group-flush cn_'.$classname.'">';
		foreach($tt as $keyeachtt=>$eachtt){
			$ast_display_output .= '<li class="cat-item p-1 list-group-item'.($current_category_ID == $keyeachtt?" list-group-item-warning":"").'"><a class="related-link" href="'.get_category_link($keyeachtt).'" title="'.strip_tags(category_description($keyeachtt)).'">'.$eachtt.'</a></li>';
		}
		$ast_display_output .= '</ul>';
		
        
endforeach;


$ast_display_output .= '</div>';
$ast_display_output .= '</div><!--end sidebar-->';
wp_reset_postdata();
echo($ast_display_output);
?>