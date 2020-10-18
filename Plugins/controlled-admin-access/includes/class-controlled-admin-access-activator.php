<?php

/**
 * Fired during plugin activation
 *
 * @link       https://wpruby.com
 * @since      1.0.0
 *
 * @package    Controlled_Admin_Access
 * @subpackage Controlled_Admin_Access/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Controlled_Admin_Access
 * @subpackage Controlled_Admin_Access/includes
 * @author     Waseem Senjer <waseem.senjer@gmail.com>
 */
class Controlled_Admin_Access_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		if(get_option('caa_upgrade_130') === false){
			self::upgrade_for_130();
			update_option('caa_upgrade_130', true);
		}
	}

	private static function upgrade_for_130() {
		$users = get_users( 'orderby=ID&meta_key=caa_account&meta_value=true' );
		foreach($users as $user):
			$user_id = intval($user->data->ID);
			$user_settings_main = get_user_meta($user_id, 'caa_main_menu', true);
			if(is_array($user_settings_main)){
				if(!in_array('user-edit.php', $user_settings_main)){
					$user_settings_main[] = 'user-edit.php';
				}
			}
			update_user_meta($user_id, 'caa_main_menu', $user_settings_main);
		endforeach;
	}

}
