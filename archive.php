<?php get_header(); ?>

<?php
//category query
$current_category = get_queried_object()->slug;
$current_category_id = get_queried_object()->term_id;
$current_category_name = get_queried_object()->name;
$ministerial = akseki_get_ministrial_categories();

//children of current category
$curcat_children = get_categories(
    array( 'parent' => $current_category_id )
);

//category id of the important parents
$catid_summit = 41;
$catid_ministerial = 43;
$catid_dg = 377;
$catid_cpr = 409;
$catid_som = 280;
$catid_otl = 295;
$catid_ott = 399;//other tracks
$catid_oth = 45;

//extra property of current category
$cat_extra = get_term_meta( $current_category_id, '_catextra', true );

//get cat's ancestors > output id
$curcatanc = get_ancestors( $current_category_id, 'category' );
if(!empty($curcatanc)){
	if($current_category_id == $catid_summit){
		$catanc = "summit";
	}elseif(in_array($catid_ministerial,$curcatanc) or $current_category_id == $catid_ministerial){
		$catanc = "ministerial";
	}elseif($current_category_id == $catid_dg){
		$catanc = "dg";
	}elseif(in_array($catid_cpr,$curcatanc) or $current_category_id == $catid_cpr){
		$catanc = "cpr";
	}elseif(in_array($catid_som,$curcatanc) or $current_category_id == $catid_som){
		$catanc = "som";
	}elseif($current_category_id == $catid_ott){
		$catanc = "ott";
	}elseif($current_category == "agreements"){
		$catanc = "agreements";
	}else{
		$catanc = "othlev";
	}
}else{
	//current category is a top level category
	$catanc = "other";
}

//get level ; if ancestral is ministerial or som and not a top level category;
if(in_array($catanc,["ministerial","dg","cpr","som","ott","othlev"]) and !empty($curcatanc)){
	$catlev = count($curcatanc);
	if(count($curcatanc) == 3){
		$catlev = "topic";
	}elseif(count($curcatanc) == 2){
		$catlev = "pillar";
	}elseif(count($curcatanc) == 1){
		$catlev = "level";
	}
}else{//set na for non multilevels (summit,ott)
	$catlev = "na";
}

//group similar levels
if($catanc == "cpr" or $catanc == "summit"){
	$catancgroup = "singlelevel";
}elseif($catanc == "ministerial" or $catanc == "som" or $catanc == "othlev"){
	$catancgroup = "multiplelevel";
}else{
	$catancgroup = "other";
}

//loop arguments
$clargs = array('cat'=>$current_category_id, 'post_status'=>array('publish'), "posts_per_page"=>-1);
switch($catancgroup){
	case "singlelevel":
		$clargs["post_status"][] = "future";
		if($catanc == "cpr"){
			$clargs["category__in"][] = $current_category_id;
		}
		break;
	case "multiplelevel":
		$clargs["post_status"][] = "future";
		if($catanc == "cpr"){
			$clargs["category__in"][] = $current_category_id;
		}
		break;
	case "other":
		break;
}

//article containers
//control ul or div for listing container
$columns = "setup1";
switch($catancgroup){
	case "singlelevel":
		if($catanc == "cpr"){
			$articlecontainer = '<ul class="list-group list-group-flush w-100 ministerial-listing">';
			$articlecontainerend = "</ul>";
			$columns = "setup2";
		}elseif($catanc == "summit"){
			$articlecontainer = '<div class="" id="listing-summit">';
			$articlecontainerend = "</div>";
			$columns = "setup3";
		}
		break;
	case "multiplelevel":
		if($current_category == "asean3-task-force-meeting" or $current_category == "afcdm3" or $current_category == 'som-amaf3' or $cat_extra=="has_no_listing"){
			$articlecontainer = '<div class="container">';
			$articlecontainerend = "</div>";
		}else{
			$articlecontainer = '<ul class="list-group list-group-flush w-100 ministerial-listing">';
			$articlecontainerend = "</ul>";
		}
		if($catlev == "topic"){
			$columns = "setup2";
		}elseif($catlev == "pillar"){
			$columns = "setup4";
		}
		break;
	case "other":
		if ( $catanc == "agreements" or $current_category == 'other-documents' ) {
			$articlecontainer = '<div class="apt-doc-list">';
		} else {
			$articlecontainer = '<div class="listings-wrapper row">';
		}
		$articlecontainerend = "</div>";
		break;
	default:
		$articlecontainer = '<div class="listings-wrapper row">';
		$articlecontainerend = "</div>";
		break;
}
//control main columns
if($columns === "setup3"){
	$maincol = "col-12";
	$leftsidebarcol = "col-12";
	$sidebarcol = "";
}elseif($columns === "setup2"){
	$maincol = "col-12 col-md-9";
	$leftsidebarcol = "";
	$sidebarcol = "col-12 col-md-3";
}elseif($columns === "setup4"){
	$maincol = "col-12 col-md-6";
	$leftsidebarcol = "";
	$sidebarcol = "col-12 col-md-6";
}else{
	$maincol = "col-12";
	$leftsidebarcol = "";
	$sidebarcol = "";
}

//header extra class (the old faded header photos are no longer used)
$headerextraclass = '';

//category descriptors
//handle category description page here
//METHOD: search for a post that is a child in the Category Descriptor page that has the same title as the category
$catdesc = "";
switch($catancgroup){
	case "singlelevel":
		$catdesc = echo_category_descriptor($current_category_id);
		break;
	case "multiplelevel":
		if($catlev == "topic"){
			$catdesc = echo_category_descriptor($current_category_id);
		}
		break;
	case "other":	
		break;
	default:		
		break;
}


?>

<main id="main" class="site-main">
	<div class="container" id="avi-content">		
		<?php if ( have_posts() ) : ?>
		<header class="page-header row mx-0<?php echo($headerextraclass);?>">
			<?php					
				// Meeting pages: where this meeting sits (level > pillar > theme), above the title
				if ( $catlev == "topic" and in_array( $catanc, array( 'ministerial', 'dg', 'som', 'wg', 'othlev' ) ) ) {
					get_template_part( 'template-parts/sidebar', 'breadcrumb', array( 'the_curcat' => $current_category_id ) );
				}
				echo '<h1 class="page-title w-100">';
				single_term_title();
				echo '</h1>';
				if($catlev == 'topic') :
					echo '<p class="category-subtitle">'.strip_tags(category_description()).'</p>';
				endif;			
			?>
		</header><!-- .page-header -->
		<div class="listings-wrapper row">		

			<?php
			// Other Documents: its sub-categories as cards
			if ( $current_category == 'other-documents' ) {
				$apt_children = get_terms( array( 'taxonomy' => 'category', 'parent' => get_queried_object_id(), 'hide_empty' => false ) );
				if ( $apt_children && ! is_wp_error( $apt_children ) ) {
					echo '<ul class="apt-level-grid apt-subcats">';
					foreach ( $apt_children as $apt_child ) {
						echo '<li><a class="apt-level-card" href="' . esc_url( get_term_link( $apt_child ) ) . '"><span class="apt-level-name apt-arrow">' . esc_html( $apt_child->name ) . '</span>'
							. ( trim( (string) $apt_child->description ) ? '<span class="apt-level-desc">' . esc_html( wp_strip_all_tags( $apt_child->description ) ) . '</span>' : '' ) . '</a></li>';
					}
					echo '</ul>';
				}
			}
			?>

			<!--control the columns-->
			<div class="<?php echo($maincol);?> order-2">
			<!--#########################################-->			
			<?php					
				//category descriptions
				echo $catdesc;
				//the content
				if($catlev == "level" and $catanc == "cpr"){
					//redirect to CPR+3 listing page
					echo('<script type="text/javascript">
							jQuery(function($) {
							  window.location.replace("'.get_category_link(275).'");
							});
						</script>');
				}elseif($catlev == "level" and $catanc == "ott"){
					//redirect to Other Tracks theme page
					echo('<script type="text/javascript">
							jQuery(function($) {
							  window.location.replace("'.get_permalink(6262).'");
							});
						</script>');
				}elseif(($catlev == "level") and in_array($catanc,["ministerial","dg","som","othlev"])){					
					echo ('<div class="listing-type-m m-0 p-0">');
					echo akseki_get_level_resources($current_category_id);
					echo ('</div>');
				}elseif($catlev == "pillar"){
					echo('<div id="pillars_accordion">');
					$plevels=get_categories(array('parent'=>7));
					foreach($plevels as $pkey=>$plevel){

						$plevelchildren = get_terms([
							'taxonomy' => 'category',
							'name' => $current_category_name,
							'hide_empty' => true,
							'parent' => $plevel->term_id,
						]);
						if(!empty($plevelchildren)){
							echo('<div class="card border-0">');
							echo('<div class="card-header p-0 m-0" id="heading-'.$plevel->slug.'">');
							echo('<button class="collapser-button btn w-100 text-left" data-toggle="collapse" data-target="#collapse-'.$plevel->slug.'" aria-expanded="'.(($pkey==1)?"true":"false").'" aria-controls="collapse-'.$plevel->slug.'">');
							echo('<h4 class="m-0 px-2 py-1">'.$plevel->name.'</h4>');
							echo('</button></div>');

						}
						foreach($plevelchildren as $plkey=>$plchild){							
							$plgcs=get_categories(array('parent'=>$plchild->term_id));
							if(!empty($plgcs)){

								echo('<div id="collapse-'.$plevel->slug.'" class="collapse'.(($pkey==1)?" show":"").'" aria-labelledby="heading-'.$plevel->slug.'" data-parent="#pillars_accordion">');
								echo('<div class="card-body py-1">');
								echo('<ul class="list-group list-group-flush ml-2">');
							}
							foreach($plgcs as $plgc){
								echo '<li class="list-group-item list-group-item-action pl-2"><h5 class="mb-0"><a class="" href="' . get_term_link($plgc->term_id, "category") . '">' . $plgc->name . '</a></h5><small>'.$plgc->description.'</small></li>';
							}
							if(!empty($plgcs)){
								echo('</ul>');
								echo('</div>');//close .card-body
								echo('</div>');//close .collapse
							}

							
						}
						if(!empty($plevelchildren)){

							echo('</div>');//close .card
						}

					}
					echo('</div>');

					

										
				}else{
					//it list of articles, use this one
					echo($articlecontainer);		

					//codes to apply before the loop
					if($catanc == "summit"){
						$summit_content = '';
						$buttons = '';
						$inner = '<div class="tab-content" id="v-pills-tabContent">';
						$summitcounter = 0;
						$_year_mon = '';   // previous year-month value
						$_has_grp = false; // TRUE if a group was opened
						$newgroups = [];
					}

					//custom loop					
					$cl = new WP_Query($clargs);

					if( $cl->have_posts() ) {
					    $counter = 1;
						while ( $cl->have_posts() ):
							$cl->the_post();
							switch($catancgroup){
								case "singlelevel":
									if(in_array($current_category,['cpr-level','aptwg'])){
										get_template_part( 'template-parts/listings', 'cpr' );
									}elseif($catanc == "summit"){
										$theid = get_the_ID();
										$year = get_the_date('Y');
										$mon = get_the_date("m");
										$year_mon = $year.$mon;
										$newgroups[$year_mon][] = $theid;

										if ( has_tag("marker") ) {
											$default_thumbnail = apt_post_image_html( $theid, 'medium', '300px', array( 'class' => 'rounded img-fluid float-left' ) ); // flags image when there is no photo
											if(get_post_status($theid) == 'future'){
												$newgroups[$year_mon]["markerimage"] = $default_thumbnail;
											}else{
												if(has_post_thumbnail($theid)){
													$newgroups[$year_mon]["markerimage"] = get_the_post_thumbnail( $theid, 'medium', ['class' => 'rounded img-fluid float-left'] );
												}else{
													$newgroups[$year_mon]["markerimage"] = $default_thumbnail;
												}
											}
											//if the marker has SM, use SM, otherwise use shortname
											$SMt = akseki_get_SM($theid);
											if(empty($SMt)){
												$newgroups[$year_mon]["shortname"] = akseki_get_shortname(get_the_title($theid), $theid);
											}else{
												$newgroups[$year_mon]["shortname"] = $SMt;
											}
											$newgroups[$year_mon]["meta"] = akseki_get_the_meta($theid);
										}										
										$summitcounter++;
									}else{
										get_template_part( 'template-parts/listings' );
									}
									break;
								case "multiplelevel":
									if($catanc == 'ministerial' and $catlev == "topic"){
										get_template_part( 'template-parts/listings', 'list' );
									}elseif($catanc == 'som' and $catlev == "topic"){
										if($cat_extra=="has_no_listing"){
											get_template_part( 'template-parts/listings', 'hasnolisting' );
											break;//just allow the first article
										}elseif($current_category == 'afcdm3' or $current_category == 'som-amaf3'){
											$articledate = new DateTime(get_the_date());
											$nowdate = new DateTime("now");
											$futurearticle = false;
											if(($articledate > $nowdate) == true){
												$futurearticle = true;
											}
											if($futurearticle){
												$tfyear = "<em>upcoming</em>";
											}else{
												$tfyear = get_the_date('Y');
											}
											if($tfyear !== $prevtfyear){
												if($counter !== 1){
													echo('</div>');
													echo('</div>');							
												}											
												echo('<div class="row mb-2 pb-2 afcdmrows">');
												echo('<div class="col-auto rounded mr-2 p-2 bg-secondary d-flex align-items-center"><h5 class="text-white p-0 m-0">'.$tfyear.'</h5></div>');
												echo('<div class="col list-group list-group-flush">');
											}
											get_template_part( 'template-parts/listings', 'afcdmlist', array('cat'=>$current_category));
											if($counter == $cl->post_count){				
												echo('</div>');
												echo('</div>');
											}											
											$counter++;
											$prevtfyear = $tfyear;
										}else{
											get_template_part( 'template-parts/listings', 'wglist' );
										}
									}elseif($catanc == 'othlev' and $catlev == "topic"){
										if($cat_extra=="has_no_listing"){
											get_template_part( 'template-parts/listings', 'hasnolisting' );
											break;//just allow the first article
										}elseif($current_category == 'asean3-task-force-meeting' or $current_category == 'abmf'){
											$tfyear = get_the_date('Y');
											if($tfyear !== $prevtfyear){
												if($counter !== 1){
													echo('</div>');
												}
												echo('<button class="w-100 btn btn-secondary mt-1 collapser-button" type="button" data-toggle="collapse" data-target="#collapsetf'.$tfyear.'" aria-expanded="'.($counter==1?"true":"false").'" aria-controls="collapsetf'.$tfyear.'"><h4 class="w-100 mb-0 float-left pl-4 pr-2 text-left">Year '.$tfyear.'</h4></button>');
												echo('<div class="mb-1 list-group list-group-flush collapse'.($counter==1?" show":"").'" id="collapsetf'.$tfyear.'">');
											}
											get_template_part( 'template-parts/listings', 'tflist', array('cat'=>$current_category) );
											if($counter == $cl->post_count){
												echo('</div>');
											}											
											$counter++;
											$prevtfyear = $tfyear;
										}else{
											get_template_part( 'template-parts/listings', 'wglist' );
										}

									}else{
										get_template_part( 'template-parts/listings' );
									}
									break;
								case "other":
									if($current_category == 'news'){
										get_template_part( 'template-parts/listings', 'cards' );
									}elseif($current_category == 'aptwg'){
										get_template_part( 'template-parts/listings', 'wglist' );
									}elseif($current_category == 'agreements' or $current_category == 'other-documents'){
										get_template_part('template-parts/listings', 'agreements', array('curcatslug' => $current_category));
									}else{
										get_template_part( 'template-parts/listings' );
									}
									break;
								default:
									get_template_part( 'template-parts/listings' );
									break;
							}
							// End the loop.
						endwhile;

						//codes to execute right after the loop
						if($catanc == "summit"){

							//tabContents
							$ngcounter = 0;
							echo('<div class="tab-content" id="pills-tabContent">');
							foreach($newgroups as $key=>$newgroup){
								$futurearticle = false;
								if(get_post_status($newgroup['0']) == 'future'){
									$futurearticle = true;
								}
								$show = '';
								$active = '';
								if($ngcounter == 1){
									$show = ' show';
									$active = ' active';
								}
								echo('<div class="tab-pane fade'.$show.$active.'" id="pills-'.$key.'" role="tabpanel" aria-labelledby="pills-'.$key.'-tab">');
								//the image
								echo('<div class="row sumall">');
								echo('<div class="col-12 col-md-auto sumimg mb-4">');
								echo($newgroup["markerimage"]);
								echo('</div>');//end sumimg
								//the shortname
								$ngshortname = $newgroup["shortname"];
								$upcomingbadge = '';
								if($futurearticle){
									$upcomingbadge .= ("<span class='mt-2 badge badge-pill badge-secondary'><em>upcoming</em></span>");
								}
								echo('<div class="col sumcon">');
								echo('<h3>'.$ngshortname.'</h3>');
								//the meta
								echo('<div class="ngmeta border-top text-end text-muted font-italic">'.$newgroup["meta"].'</div>');
								//the list
								if($futurearticle){
									echo($upcomingbadge);
								}else{
									echo('<ul class="list-group list-group-flush">');
									foreach($newgroup as $ngkey=>$ng){
										if(!($ngkey == "markerimage" or $ngkey == "meta" or $ngkey == "shortname")){
											echo("<li class='list-group-item summitlisting'><a href='".get_permalink($ng)."'>".get_the_title($ng)."</a></li>");
										}
									}
									echo('</ul>');
								}
								echo('</div>');//end of sumcon
								echo('</div>');//end of sumall
								echo('</div>');
								$ngcounter++;
								$ngyear = substr($key,0,4);
								// "2007 · 10th": the summit number keeps two summits in one year apart.
								$nglabel = esc_html( apt_summit_tab_label( $ngyear, $ngshortname ) );
								$buttons .= '<a title="'.strip_tags($ngshortname).'" type="button" class="btn btn-light m-1 nav-link border-bottom-0'.$active.'" id="pills-'.$key.'-tab" data-toggle="pill" href="#pills-'.$key.'" role="tab" aria-controls="pills-'.$key.'" aria-selected="true">'.($futurearticle?"<small class='text-muted'><em>upcoming</em></small>":$nglabel).'</a>';
							}
							echo('</div>');

							echo("<style>
							li.summitlisting:before {
								border: solid #98294b;
								border-width: 0 3px 3px 0;
								display: inline-block;
								padding: 3px;
								transform: rotate(-45deg);
								-webkit-transform: rotate(-45deg);
								content: ' ';
								margin-right: 0.5em;
								position: relative;
								bottom: 1.25px;
							}
							li.summitlisting a:hover{
								padding-left: 1em;
								transition: .4s;
							}
							</style>");
						}						

						//reset the loop for efficiency
						wp_reset_postdata();
					}
					echo($articlecontainerend);
				}
			?>

			<!--#########################################-->

		<?php
			// If no content, include the "No posts found" template.
		else :
			get_template_part( 'template-parts/notfound' );
		endif;
		?>
			</div>
			<!--##################SIDEBARS#######################-->

			<!--leftsidebar-->
			<?php if($columns == "setup3"):?>
			<div class="<?php echo($leftsidebarcol);?> order-1">
				<div class="nav nav-pills d-flex justify-content-around bg-light mb-4 rounded" id="v-pills-tab" role="tablist" aria-orientation="vertical">
					<?php echo($buttons); ?>
				</div>
			</div>
			<?php endif;?>

			<?php if($columns == "setup2" or $columns == "setup4"):?>
			<?php 
			if($catlev == "pillar"){
				$extraclass = $catlev;
			}else{
				$extraclass = $catanc;
			}
			?>
			<div class="<?php echo($sidebarcol); ?> <?php echo($extraclass); ?>-sidebar topic-sidebar order-3">
				<?php
				//limited download
				if($catanc == "cpr" or $catanc == "som" or $catanc == "othlev"){
					if($catlev == "topic" or $catanc == "cpr"){
						echo('<p class="apt-note">Document downloads for these meetings are limited.</p>');
					}
				}
				//cpr links
				if($catanc == "cpr"){
					$apt_nt = '<span class="screen-reader-text"> (opens in a new tab)</span>';
					$apt_cpr_links = array(
						'About the CPR' => array(
							'https://asean.org/what-we-do/committee-of-permanent-representatives/committee-of-permanent-representatives-to-asean-in-jakarta/' => 'Committee of Permanent Representatives to ASEAN (CPR)',
							'https://asean.org/book/committee-of-permanent-representatives-to-asean-cpr-handbook/' => 'CPR Handbook',
							'https://asean.org/what-we-do/committee-of-permanent-representatives/list-of-the-committee-of-permanent-representatives-to-asean-cpr/' => 'List of the Committee of Permanent Representatives to ASEAN',
						),
						'Missions to ASEAN' => array(
							'http://asean.china-mission.gov.cn/eng/' => 'Mission of the People&rsquo;s Republic of China to ASEAN',
							'https://www.asean.emb-japan.go.jp/itprtop_en/index.html' => 'Mission of Japan to ASEAN',
							'https://overseas.mofa.go.kr/asean-en/index.do' => 'Mission of the Republic of Korea to ASEAN',
						),
					);
					foreach ( $apt_cpr_links as $apt_group => $apt_links ) {
						echo '<nav class="apt-sidebar-list apt-sidebar-links" aria-label="' . esc_attr( $apt_group ) . '"><h2 class="apt-sidebar-title">' . esc_html( $apt_group ) . '</h2><ul>';
						foreach ( $apt_links as $apt_url => $apt_label ) {
							echo '<li><a class="apt-external" href="' . esc_url( $apt_url ) . '" target="_blank" rel="noopener">' . $apt_label . $apt_nt . '</a></li>';
						}
						echo '</ul></nav>';
					}
				}
				//for level pillars
				if($catlev == "pillar"){
					echo('<div id="accordionPillarSidelist">');
					//themes in pillar
					$te_arr = akseki_get_themes_in_pillar($current_category_id);

					if(!empty($te_arr)){						
						echo('<div class="card border-0">');
						echo('<button class="mt-1 border-bottom collapser-button btn bg-white w-100 text-left" data-toggle="collapse" data-target="#collapse'.$sidelist.'" aria-expanded="true" aria-controls="collapse'.$sidelist.'"><h5 class="mt-4">Themes in '.get_cat_name($current_category_id).'</h5></button>');
						echo('<div class="list-group list-group-flush collapse show" id="collapse'.$sidelist.'" data-parent="#accordionPillarSidelist">');
						foreach($te_arr as $te_key=>$te){
							echo('<a class="border-bottom py-1 pillar-list-link list-group-item list-group-item-action list-group-item-secondary" href="'.get_permalink($te_key).'">'.$te.'</a>');
						}
						echo('</div>');
						echo('</div>');
					}
					$sidelists = array("Level","Pillar");
					foreach($sidelists as $sidelist){
						echo('<div class="card border-0">');
						echo('<button class="mt-1 border-bottom collapser-button btn bg-white w-100 text-left" data-toggle="collapse" data-target="#collapse'.$sidelist.'" aria-expanded="false" aria-controls="collapse'.$sidelist.'"><h5 class="mt-4">Cooperation '.$sidelist.'s</h5></button>');
						if($sidelist == "Level"){
							$qterm = 7;

							$otherbutton = "";
						}elseif($sidelist=="Pillar"){
							$qterm = get_queried_object()->parent;
							$otherbutton = "";
						}
						$cs = get_terms( array(
							'taxonomy'   => 'category',
							'parent'     => $qterm,
						) );
						if(!empty($otherbutton)){
							$cs[] = $otherbutton;
						}
						echo('<div class="list-group list-group-flush collapse" id="collapse'.$sidelist.'" data-parent="#accordionPillarSidelist">');
						foreach($cs as $child){
							if($child->name == get_cat_name($current_category_id)){
								echo('<a class="disabled border-bottom py-1 pillar-list-link list-group-item" style="background-color:#ffffb8!important;" href="'.get_term_link($child->term_id).'">'.$child->name.'</a>');
							}else{
								echo('<a class="border-bottom py-1 pillar-list-link list-group-item list-group-item-action list-group-item-secondary" href="'.get_term_link($child->term_id).'">'.$child->name.'</a>');
							}
						}
						echo('</div>');
						echo('</div>');
					}
					echo('</div>');//close accordionPillarSidelist
					//styling
					echo('
					<style>
					.pillar-list-link{transition:.4s;}
					.pillar-list-link:hover{padding-left:2em;}
					</style>
					');
				}
				//multilevel links
				if($catanc == "cpr") :	
					// Other levels and documents, as a plain list (styled in apt-design.css)
					echo('<nav class="ministerial-sidebar apt-sidebar-list" aria-label="Other statements and documents"><h2 class="apt-sidebar-title">Browse statements and documents</h2><ul>');
					wp_list_categories( array(
						'echo'       => true,
						'depth'      => 2,
						'hide_empty' => 0,
						'title_li'   => '',
						'exclude'    => '44,1,10,7',
					) );
					echo('</ul></nav>');
				endif;
				//other topics in ministerial or som in topic
				if($catlev == "topic" and in_array($catanc,['ministerial','dg','som','wg','othlev'])):

					//same theme
					get_template_part( 'template-parts/sidebar', 'akseki_same-theme', $args = array('current_category'=>$current_category_id));

				endif;
				?>
			</div><!--end of sidebar-->
			<?php 
			endif;
			//pagination - disable
			//the_posts_navigation();
			?>
		</div>	
	</div>	
</main><!-- .site-main -->

<?php get_footer(); ?>
