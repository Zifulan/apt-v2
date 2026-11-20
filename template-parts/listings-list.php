<?php 
	$thetags = get_the_tags();
	$SM = akseki_get_sm(get_the_id());
	$SC = akseki_get_sc_raw(get_the_id());
	$theleft = akseki_get_shortname(get_the_title(),get_the_id());
	//handle future post
	$thefuturepost = false;
	if(date('Y-m-d H:i:s', strtotime(get_the_date('Y-m-d H:i:s'))) > date('Y-m-d H:i:s')){
		$thefuturepost = true;
	}
	// Left label: the meeting's short name (or "Extra" for a special meeting) and the year
	$label = ! empty( $SM ) ? 'Extra' : $theleft;
	$year  = ( $thefuturepost && $SC == "SC_nodate" ) ? '<em>tbd</em>' : get_the_date( 'Y' );
	$echotheleft = '<div class="apt-statement-when"><span class="apt-statement-short">'.$label.'</span><span class="apt-statement-year">'.$year.'</span></div>';
	if($thefuturepost){
		$fbg = ' thefuturepost is-upcoming';
		$echothelink = '<span class="apt-tag">Upcoming</span> '.get_the_title();
	}else{
		$fbg = '';
		$echothelink = '<a href="'.esc_url(get_the_permalink()).'">'.get_the_title().'</a>';
	}

	if($thefuturepost && $SC == "SC_year"){

		$echothemeta = preg_replace('/<span class="meta-date">.+?<\/span>/', akseki_get_sc(get_the_id()), akseki_get_the_meta(get_the_id()) );
	}elseif($thefuturepost && $SC == "SC_month"){
		$echothemeta = preg_replace('/<span class="meta-date">.+?<\/span>/', get_the_date( 'F Y' ), akseki_get_the_meta(get_the_id()) );
	}elseif($thefuturepost && $SC == "SC_nodate"){
		$echothemeta = '<em>tbd</em>';
	}else{
		$echothemeta = akseki_get_the_meta(get_the_id());
	}
	//echo everything
	echo '
		<li class="list-group-item apt-statement-row'.$fbg.'">
			'.$echotheleft.'
			<div class="apt-statement-main">
				<h2 class="apt-statement-title">'.$echothelink.'</h2>
				<p class="akseki-meta">'.$echothemeta.'</p>
			</div>
		</li>';
?>
