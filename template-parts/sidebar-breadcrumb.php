        <?php
		//start breadcrumb		
		echo('<nav class="akseki_breadcrumb apt-breadcrumb" aria-label="Breadcrumb"><ol>');
		$bc_separator = "";
		//breadcrumb immidiate category
		$categories = get_the_category();
		//get children of ministerial category
		$min_child=get_categories(
			array( 'parent' => 43 )
		);
		$the_curcat = $args['the_curcat'];
		$the_curcat_obj = get_category($the_curcat);
		$idSummitObj = get_category_by_slug('summit-level-statements'); 
		$Summitid = $idSummitObj->term_id;
		$idObj = get_category_by_slug('cpr-level'); 
		$cprid = $idObj->term_id;
		$ottcat = akseki_get_ott_categories();

		//current category's ancestors
		$curcat_anc = get_ancestors($the_curcat,'category');
		$curcat_anc_rev = array_reverse($curcat_anc);

		//if arcitcle is not SUMMIT or MINISTERIAL or CPR or SOM, do not display breadcrumb
		//if(in_array(43, $curcat_anc) or $the_curcat == $Summitid or $the_curcat == $cprid or in_array(280, $curcat_anc) or in_array(295, $curcat_anc)){

		//if not CPR+3
		if($the_curcat !== 275 and !in_array($the_curcat_obj->slug,$ottcat)){
			//display breadcrumb
    		foreach($curcat_anc_rev as $index=>$cca){
    			//don't display dropdown for first category
    			//unless for CPR, display Other categ
    			if($index == 0 and $the_curcat === $cprid){

				}else if($index == 0){
    				//do nothing
    			}else{
    				// Pillar categories get the design system's pillar colour dot
    				$pkey = apt_pillar_key(get_cat_name($cca));
    				$pcls = ('other' !== $pkey) ? ' ab-pillar pillar-'.$pkey : '';
    				echo('<li><a class="'.trim($pcls).'" href="'.esc_url(get_category_link($cca)).'">'.esc_html(get_cat_name($cca)).'</a></li>');

    			}
    		}
			//display the theme/topic
			//don't display in other tracks and summit
			if($the_curcat !== 41 and !in_array($the_curcat_obj->slug,$ottcat)){
				$the_theme_id = akseki_get_theme_from_id($the_curcat,"category");
				$the_theme_obj = get_post($the_theme_id);
				if($the_theme_obj){ echo('<li><a href="'.esc_url(get_permalink($the_theme_id)).'">'.esc_html($the_theme_obj->post_title).'</a></li>'); }
			}
		}
    		//breadcrumb current category
    		// Current category: on its own page it is the current item, not a link
    		if ( is_category( $the_curcat ) ) { echo('<li aria-current="page">'.esc_html(get_cat_name($the_curcat)).'</li>'); } else { echo('<li><a href="'.esc_url(get_category_link($the_curcat)).'">'.esc_html(get_cat_name($the_curcat)).'</a></li>'); }

    		//breadcrumb last item

    		echo('</ol></nav>');
		//}
		?> 