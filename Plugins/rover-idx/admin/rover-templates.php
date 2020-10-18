<?php
add_action('wp_ajax_rover_idx_toggle_advanced',							'rover_idx_toggle_advanced_callback');
add_action('wp_ajax_rover_idx_assemble_agent_data',						'rover_idx_assemble_agent_data_callback');
add_action('wp_ajax_rover_idx_create_agent_cpt',						'rover_idx_create_agent_cpt_callback');




function roveridx_panel_js($panel)	{

	require_once ROVER_IDX_PLUGIN_PATH.'rover-content.php';

	global $rover_idx_content;

	$rover_content	=		$rover_idx_content->rover_content(	'ROVER_COMPONENT_SETTINGS_PANEL_JS', 
															array(
																'panel'				=> $panel
																)
															);
	return $rover_content['the_html'];
	}


function roveridx_panel_footer($panel)	{

	$current_user	=		wp_get_current_user();
	$upload_base		=		wp_upload_dir();
	$label_id		=		rand(1,9999);

	$theming_opts	=		get_option(ROVER_OPTIONS_THEMING);
	$advanced		=		(isset($theming_opts['ui_advanced']) && ($theming_opts['ui_advanced'] == 1))
									? true
									: false;

	$the_html		=		array();
	$the_html[]		=		'<footer class="'.IDX_PLUGIN_NAME.'">';
	$the_html[]		=			'<div style="display:inline-block;width:50%;">';
	$the_html[]		=				'<center><strong>Rover IDX</strong> '.ROVER_VERSION_FULL.'</center>';
	$the_html[]		=			'</div>';
	$the_html[]		=			'<div style="display:inline-block;width:50%;">';
	$the_html[]		=				'<center>';
	$the_html[]		=					'<a href="https://www.facebook.com/RoverIDX" title="RoverIDX Facebook page" target="_blank">';
	$the_html[]		=						'<img style="border:none;margin-left:10px;" src="'.ROVER_IDX_PLUGIN_URL.'/images/facebook-icon.png" />';
	$the_html[]		=					'</a>';
	$the_html[]		=				'</center>';
	$the_html[]		=			'</div>';

	$the_html[]		=			'<input type="hidden" id="rover_idx" name="rover_idx" value="1" />';

	$the_html[]		=			'<input type="hidden" id="wp_security" name="security" value="'.wp_create_nonce(ROVERIDX_NONCE).'" />';

	$the_html[]		=			'<input type="hidden" name="wp_name" value="'.sanitize_text_field( $current_user->display_name ).'" />';
	$the_html[]		=			'<input type="hidden" name="wp_email" value="'.sanitize_email( $current_user->user_email ).'" />';
	$the_html[]		=			'<input type="hidden" name="kit" value="'.ROVER_AFFILIATE.'" />';
	$the_html[]		=			'<input type="hidden" id="advanced-ui" value="'.(($advanced === true) ? 1 : 0).'" />';

	$the_html[]		=			'<input type="hidden" name="wp_site_url" class="no-serial" value="'.get_site_url().'" />';
	$the_html[]		=			'<div class="rover-confirm modal fade" role="dialog" aria-labelledby="#'.$label_id.'" aria-hidden="true" style="display:none;position:fixed;top:50%;left:25%;width:50%;z-index:1051;">';
	$the_html[]		=				'<div class="modal-dialog" style="max-width:100%;">';
	$the_html[]		=					'<div class="modal-content">';
	$the_html[]		=						'<div class="modal-header">';
	$the_html[]		=							'<h4 class="modal-title" style="float:left;margin:0;" id="'.$label_id.'">Your question goes here</h4>';
	$the_html[]		=							'<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>';
	$the_html[]		=							'<div style="clear:both;"></div>';
	$the_html[]		=							'</div>';
	$the_html[]		=						'<div class="modal-body">';
	$the_html[]		=							'<i class="fa fa-cog fa-spin fa-2x fa-fw" style="margin:30px auto;padding:0;border:0;text-align:center;"></i>';
	$the_html[]		=						'</div>';
	$the_html[]		=						'<div class="modal-footer">';
	$the_html[]		=							'<button type="button" class="yes btn btn-primary pull-right" style="margin:0 5px;">Yes</button>';
	$the_html[]		=							'<button type="button" class="no btn btn-primary pull-right"  style="margin:0 5px;">No</button>';
	$the_html[]		=						'</div>';
	$the_html[]		=					'</div><!-- /.modal-content -->';
	$the_html[]		=				'</div><!-- /.modal-dialog -->';
	$the_html[]		=			'</div><!-- /#edit_client -->';
	$the_html[]		=		'</footer><!-- footer -->';

	$the_html[]		=		roveridx_panel_js($panel);

	return implode('', $the_html);
	}

function rover_idx_toggle_advanced_callback() {

	check_ajax_referer(ROVERIDX_NONCE, 'security');

	$theming_opts					= get_option(ROVER_OPTIONS_THEMING);
	$theming_opts['ui_advanced']	= rover_idx_validate_post_bool( 'ui_advanced' );

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'ui_advanced ['.(($theming_opts['ui_advanced'] == 1) ? 'true' : 'false').']');

	$r								= update_option(ROVER_OPTIONS_THEMING, $theming_opts);

	$responseVar = array(
	                    'html'		=> $theming_opts['ui_advanced'],
	                    'success'	=> $r
	                    );

    echo json_encode($responseVar);
	
	die();
	}

?>