jQuery(function($) {
	
	$( ".popup-image" ).click(function(e) {
		e.preventDefault(); // href="#" would otherwise jump to the top
		$imgsrc = $(this).find('> img').attr('src');
		$imgsrc = ($imgsrc.replace(/-\d+?x\d+?\./,'.'));
		$('#imagepreview').attr('src', $imgsrc);
		$('#imagemodal').modal('show');
		$thetitle=$(this).find('> img').attr('alt') || '';
		$('#imagepreview').attr('alt', $thetitle); // same description as the thumbnail
		$('#image-title').text($thetitle);
	});
	
	/*sidebar in detail page*/
	function toggleIcon(e) {		
        $(this)            
            .find(".more-less")
            .toggleClass('dashicons-arrow-down dashicons-arrow-up');
    }
    $('.detail-sidebar').on('hidden.bs.collapse', toggleIcon);
    $('.detail-sidebar').on('shown.bs.collapse', toggleIcon);	
    
	// Same-page anchor scrolling is native now: see scroll-behavior and
	// scroll-padding-top in assets/css/apt-design.css.
	
});