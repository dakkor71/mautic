<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$config = array (
	"name" 		=> "Webmecanik-light",
	"features" 	=> array (
		"page",
		"email",
		"form" 
	),
	"slots" 		=> array (
		"page" => array(
			"slideshow"         => array('type' => 'slideshow', 'placeholder' => 'mautic.page.builder.addcontent'),
			"slideshow2",
			"2tiers1tier1-1",
			"2tiers1tier1-2",
			"2tiers1tier2-1",
			"2tiers1tier2-2",
			"2tiers1tier3-1",
			"2tiers1tier3-2",
			"fullwidth1",
			"fullwidth2",
			"fullwidth3",
			"gallerie1-title",
			"gallerie1-1",
			"gallerie1-2",
			"gallerie1-3",
			"gallerie1-4",
			"gallerie1-5",
			"gallerie1-6",
			"top",
			"bottom1",
			"bottom2",
			"bottom3",
			"bottom4",
			"footer"
		),
		"email" => array (
			"wmk_lien_browser",
			"wmk_header_logo",
			"wmk_header_title",
			"wmk_header_image",
			"wmk_main_intro", 
			"wmk_main_intro_image", 
			"wmk_main_intro_name", 
			// "wmk_main_section_1_title", 
			// "wmk_main_section_1_image", 
			// "wmk_main_section_1_description", 
			// "wmk_main_section_1_link", 
			// "wmk_secondary_section_1_1", 
			// "wmk_secondary_section_1_2", 
			"wmk_main_section_2_title", 
			"wmk_main_section_2_image", 
			"wmk_main_section_2_description", 
			"wmk_main_section_2_link", 
			"wmk_secondary_section_2_1", 
			"wmk_footer"
		)
	)
	 
);

return $config;