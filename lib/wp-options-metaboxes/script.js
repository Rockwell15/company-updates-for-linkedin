
// handles the metaboxes' toggles
jQuery(document).on( 'click', '.postbox h2.hndle, .postbox button.handlediv', function( e ) {
	if ( 'A' !== e.originalEvent.target.nodeName ) {
		jQuery(this).siblings('.inside').slideToggle();
		jQuery(this).parent().toggleClass('closed');
	}
});
