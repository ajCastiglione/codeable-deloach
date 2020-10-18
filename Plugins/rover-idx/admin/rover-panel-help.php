<?php

require_once ROVER_IDX_PLUGIN_PATH.'admin/rover-templates.php';

// Render the Plugin options form
function roveridx_panel_help_form() {

	$the_html		= array();
	$the_html[]		= '<div style="min-height:800px;">';
	$the_html[]		=	'<iframe src="https://roveridx.com/rover-shortcodes/#main" style="width:100%;height:800px;"></iframe>';
	$the_html[]		= '</div>';

	$the_html[] 		= roveridx_panel_footer('help');

	echo implode('', $the_html);
	}
?>