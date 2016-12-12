jQuery(document).ready(function ($) {

	var scString, conClassVal, itemClassVal, limitVal;
	var $conClass  = $('#linkedin_company_updates_options_Update-Items-Container-Class');
	var $itemClass = $('#linkedin_company_updates_options_Update-Item-Class');
	var $limit     = $('#linkedin_company_updates_options_Limit');
	var $shortcode = $('#linkedin_company_updates_shortcode');
	var $companyID = $('#linkedin_company_updates_options_Company-ID');

	$('#linkedin_company_updates_options_Company-ID, #linkedin_company_updates_options_Update-Items-Container-Class, #linkedin_company_updates_options_Update-Item-Class, #linkedin_company_updates_options_Limit').on('input', function () {
		updateShortcode( $companyID.val() );
	});

	$('#select-company').on('input', function() {
		updateShortcode( $(this).find('option:selected').val() );
	});

	function updateShortcode( companyId ) {
		
		conClassVal  = $conClass.val();
		itemClassVal = $itemClass.val();
		limitVal     = $limit.val();

		scString  = '[li-company-updates company="' + companyId + '"';
		scString += conClassVal  ? ' con_class="' + conClassVal + '"'   : '';
		scString += itemClassVal ? ' item_class="' + itemClassVal + '"' : '';
		scString += limitVal     ? ' limit="' + limitVal + '"'          : '';
		scString += ']';

		$shortcode.val( scString );

	}

})
