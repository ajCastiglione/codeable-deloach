<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wpruby.com
 * @since      1.0.0
 *
 * @package    Controlled_Admin_Access
 * @subpackage Controlled_Admin_Access/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Controlled_Admin_Access
 * @subpackage Controlled_Admin_Access/admin
 * @author     Waseem Senjer <waseem.senjer@gmail.com>
 */


class Controlled_Admin_Access_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	private $pages_with_multiple_queries = array('edit.php', 'post-new.php');

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Controlled_Admin_Access_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Controlled_Admin_Access_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/controlled-admin-access-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Controlled_Admin_Access_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Controlled_Admin_Access_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/controlled-admin-access-admin.js', array( 'jquery' ), $this->version, false );
	}



	public function register_my_custom_submenu_pages(){

		add_submenu_page('users.php',
						__('Controlled Admin Access', 'controlled-admin-access'),
						__('Controlled Admin Access', 'controlled-admin-access'),
						'edit_users',
						'controlled_admin_access', 
						array($this, 'controlled_admin_access_page') );
		add_submenu_page('',
						__('Controlled Admin Access (Manage Users)', 'controlled-admin-access'),
						__('Controlled Admin Access (Manage Users)', 'controlled-admin-access'),
						'edit_users',
						'controlled_admin_access_manage_users', 
						array($this, 'controlled_admin_access_manage_users_page') );
		add_submenu_page('',
						__('Controlled Admin Access (Support)', 'controlled-admin-access'),
						__('Controlled Admin Access (Support)', 'controlled-admin-access'),
						'edit_users',
						'controlled_admin_access_support', 
						array($this, 'controlled_admin_access_support_page') );

	}

	public function controlled_admin_access_page(){
		global $submenu, $menu, $pagenow;
		$saved = false;

		if(isset($_POST['_caa_create_user_nonce'])){
			$errors = array();
			if ( ! wp_verify_nonce( $_POST['_caa_create_user_nonce'], '_caa_create_user_nonce' ) ) {
			    die( __('You do not have the sufficient permissions to access this page','controlled-admin-access') ); 
			}else{
				$user_login = sanitize_user( $_POST['user_login'] );
				$user_password = sanitize_text_field($_POST['user_password']);
				$user_email = sanitize_email( $_POST['user_email'] );
				$userdata = array(
								'user_login' => $user_login,
								'user_email' => $user_email,
								'user_pass' => $user_password,
								'role'		=> 'administrator'
							);
				if(trim($userdata['user_email']) == ''){
					$errors[]  = __('Cannot create a user with an empty email.','controlled-admin-access');
				}
				if(trim($userdata['user_email']) == ''){
					$errors[]  = __('Cannot create a user with an empty password.','controlled-admin-access');
				}
				$user = wp_insert_user(  $userdata  );
				if(is_wp_error($user)){
					$errors[] = $user->get_error_message();
				}else{
					$user_id = $user;
					if(isset($_POST['main_items'])){
						// by default Users and Plugins items should be hidden.
						$main_items = self::get_main_disabled_items();
						update_user_meta($user_id,'caa_main_menu', $main_items);
					}
					if(isset($_POST['sub_items'])){
						// by default Users and Plugins items should be hidden.
						$sub_items = self::get_disabled_sub_items();
						update_user_meta($user_id,'caa_sub_menu', $sub_items);
					}
					if(isset($_POST['user_expiring']))
						update_user_meta($user_id,'caa_user_expiring', $_POST['user_expiring']);
					
					update_user_meta($user_id,'caa_account', 'true');
					update_user_meta($user_id,'caa_created', time());
					$saved = true;
				}
			}
		}
		if(isset($_GET['page']) && $_GET['page'] == 'controlled_admin_access'){
	        $caa_menu = array();
	        $menu_slugs = array();
	        $c = 0;
        	foreach( $menu as $key => $item ){
        		if(isset($item[4]) && strpos($item[4], 'wp-menu-separator') === 0) continue;
        		$caa_menu[$c]['title'] = $item[0];
        		$caa_menu[$c]['slug'] = $item[2];
        		$menu_slugs[] = $item[2];
        		$caa_menu[$c]['menu_id'] = $item[5];
        		if(isset($submenu[$item[2]]))
        			$caa_menu[$c]['sub_items'] = $submenu[$item[2]];
        		$c++;
        	}
	    }
	    $caa_create_user_nonce = wp_create_nonce( '_caa_create_user_nonce' );
		$caa_menu = $this->fill_chunck($caa_menu, 3);
		$this->store_menu($menu_slugs);
		require_once CAA_DIR . 'admin/partials/add_user.php';
	}

	public function controlled_admin_access_manage_users_page(){
		require_once CAA_DIR . 'admin/class-controlled-admin-access-users.php';
		$users = get_users( 'orderby=ID&meta_key=caa_account&meta_value=true' );
		$users_data = array();
		foreach($users as $user):
			$expiring = get_user_meta($user->data->ID, 'caa_user_expiring', true);
			$expiring = ($expiring == -1)?'Unlimited':$expiring .' Days';
			$users_data[] = array(
				'ID'        => $user->data->ID,
				'username'     => $user->data->user_login,
				'email'    => $user->data->user_email,
				'expiring'  => $expiring,
				'active_status' => (get_user_meta( $user->data->ID, 'caa_deactivated', true ) === 'true')?'Deactive':'Active',
				);

 		endforeach;
		$testListTable = new CAA_User_List_Table();
		$testListTable->example_data = $users_data;
    	//Fetch, prepare, sort, and filter our data...
	    $testListTable->prepare_items();
		require_once CAA_DIR . 'admin/partials/manage_users.php';
	}

	public function controlled_admin_access_support_page(){
		$plugins = get_transient('wsenjer_more_plugins');		
		if($plugins === FALSE){
			// Get the plugins		
			$url_args['secret_key'] = '3c5340eca1e0a1ac201e4ae648ba11f2';		
			$url_args['plugins_count'] = '1';		
			$api_url = 'https://wpruby.com/wpruby-plugins/wpruby-api/get_plugins/get_plugins.php?';		
			$plugins_request = wp_remote_get($api_url . http_build_query($url_args));		
			$plugins = array();		
			if( is_array($plugins_request) ) {		
				// cache the plugins for a week.		
				set_transient('wsenjer_more_plugins',$plugins_request['body'], WEEK_IN_SECONDS );		
				$plugins = $plugins_request['body'];		
			}		
		}		
		$plugins = json_decode($plugins);
		require_once CAA_DIR . 'admin/partials/support.php';
	}

	public static function fill_chunck($array, $parts) {
	    $t = 0;
	    $result = array_fill(0, $parts - 1, array());
	    $max = ceil(count($array) / $parts);
	    foreach($array as $v) {
	        count($result[$t]) >= $max and $t ++;
	        $result[$t][] = $v;
	    }
	    return $result;
	}


	public function filter_the_menu($parent_file) {
        //filter admin menu
        $this->filter();
        return $parent_file;
    }

    private function filter(){
    	global $menu, $submenu;

		$user_settings_main = get_user_meta(get_current_user_id(),'caa_main_menu', true);

        foreach ($menu as $id => $item) {
        	if(is_array($user_settings_main)){
                if (in_array($item[2], $user_settings_main)) {
               		unset($menu[$id]);
            	}
        	}

            if (!empty($submenu[$item[2]])) {
                $this->filter_sub_menu($item[2]);
            }
        }
    }

    private function filter_sub_menu($parent) {
	    global $submenu;
		$user_settings_sub = get_user_meta(get_current_user_id(),'caa_sub_menu',true);
	    if(is_array($user_settings_sub)){
		    foreach ($submenu[$parent] as $id => $item) {
			        if (in_array($item[2], $user_settings_sub)) {
			            unset($submenu[$parent][$id]);
			        }
		    	}
		}
    }


    public function redirect_the_user_away( $current_screen ){

	    $not_allowed_pages = $this->get_not_allowed_pages(get_current_user_id());
	    if(is_array($not_allowed_pages)){
			if(isset($_GET['page'])){
				if(in_array($_GET['page'], $not_allowed_pages)){
					wp_die(__('You do not have sufficient permissions to access this page.'),'controlled-admin-access');
					exit;
				}
			}else{
				$url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
				$url = parse_url($url);
				$file_name = basename($_SERVER['SCRIPT_NAME']);
				$query = (isset($url['query']) && $url['query']!='')?'?' . $url['query']:'';
				$slug = $file_name . $query;

				if(in_array($slug, $not_allowed_pages) || (in_array($file_name, $not_allowed_pages) && !in_array($file_name, $this->pages_with_multiple_queries))){
					wp_die(__('You do not have permission to access this page.'),'controlled-admin-access');
					exit;
				}
			}
		}
    }

    public function check_expired_user_login( $user_login, $user = null ){
		if ( !$user ) {
			$user = get_user_by('login', $user_login);
		}
		if ( !$user ) {
			// not logged in - definitely not disabled
			return;
		}
		if(! $this->is_user_limited($user->ID)){
			return;
		}

		$deactivated = get_user_meta( $user->ID, 'caa_deactivated', true );

		if ( $deactivated === 'true' ) {
			$this->logUserOut();
		}

		// Get user meta
		$expiring = get_user_meta( $user->ID, 'caa_user_expiring', true );
		$created = get_user_meta( $user->ID, 'caa_created', true );

		if($expiring == -1){
			return;
		}
		$time_to_expire = strtotime('+'.$expiring.' day', $created );

		if($time_to_expire < time()){
			$this->logUserOut();
		}
    }

    private function logUserOut() {
	    wp_clear_auth_cookie();
	    // Build login URL and then redirect
	    $login_url = site_url( 'wp-login.php', 'login' );
	    $login_url = add_query_arg( 'disabled', '1', $login_url );
	    wp_redirect( $login_url );
	    exit;
    }

    public function user_login_message( $message ) {

		// Show the error message if it seems to be a disabled user
		if ( isset( $_GET['disabled'] ) && $_GET['disabled'] == 1 ) 
			$message =  '<div id="login_error">' . apply_filters( 'ja_disable_users_notice', __( 'Account disabled', 'controlled-admin-access' ) ) . '</div>';

		return $message;
	}

	private function _($index, $saved = false){
		if(isset($_POST[$index]) and $saved!=true){
			echo $_POST[$index];
		}else{
			echo '';
		}
	}

	public static function is_disabled($page,$sub_item = null){
		switch ($page) {
			case 'plugins.php':
				if($sub_item === 'plugin-editor.php'){
					echo ' disabled checked ';
					return;
				}
				break;
			case 'users.php':
				if($sub_item !== 'profile.php'){
					echo ' disabled checked ';
					return;
				} else {
					echo ' disabled ';
					return;
				}
				break;
			default:
				break;
		}

		if(isset($_GET['action'])){
			if(isset($_GET['user_id'])){
				$user_id = intval($_GET['user_id']);
				$user_settings_main = get_user_meta($user_id,'caa_main_menu',true);
				if(isset($page) and $sub_item == null){
					if(is_array($user_settings_main)){
						if(in_array(htmlspecialchars_decode($page), $user_settings_main)){
							echo ' checked ';
						}
					}
				}
				$user_settings_sub = get_user_meta($user_id,'caa_sub_menu',true);
				if(is_array($user_settings_sub)){
					if($sub_item!=null){
						if(in_array(htmlspecialchars_decode($sub_item), $user_settings_sub)){
							echo ' checked ';
						}
					}
				}
			}
		}
	}

	public static function prepare_title( $title ){
		$title = strip_tags($title);
		$title = explode(' ', $title);
		if(sizeof($title) == 1){
			return $title[0];
		}else{
			$prepared_title = '';
			foreach($title as $part){
				if(!is_numeric($part)){
					$prepared_title .= $part.' ';
				}
			}
			return $prepared_title;
		}
	}

	public function filter_plugins_list($plugins){

		if(!$this->is_user_limited(get_current_user_id())){
			return $plugins;
		}


		if(isset($plugins['controlled-admin-access/controlled-admin-access.php'])){
			unset($plugins['controlled-admin-access/controlled-admin-access.php']);
		}

		return $plugins;

	}

	public function redirect_to_first_accessible_page( $redirect_to, $request, $user ){

		if(!$user instanceof WP_User){
			return $redirect_to;
		}

		$not_allowed_pages = $this->get_not_allowed_pages($user->ID);

		if (!in_array('profile.php', $not_allowed_pages)) {
			return admin_url('profile.php');
		}


		$all_pages = get_option('_caa_all_menu_slugs', array());

		if(empty($all_pages)){
			return $redirect_to;
		}

		$allowed_pages = array_values(array_diff($all_pages, $not_allowed_pages));


		if(!empty($allowed_pages)){

			$first_page = $allowed_pages[0];
			if ( strpos(strtolower($first_page), '.php') === false ){
				return admin_url('?page=' . $first_page);
			}

			return admin_url($allowed_pages[0]);
		}

		return $redirect_to;
	}

	public function store_admin_menu(){

		if(get_option('_caa_all_menu_slugs') !== false){
			return;
		}

		global $menu;
		$menu_slugs = array();
		foreach( $menu as $key => $item ){
			if(isset($item[4]) && strpos($item[4], 'wp-menu-separator') === 0) continue;
			$menu_slugs[] = $item[2];
		}

		$this->store_menu($menu_slugs);

	}

	private function is_user_limited( $user_id ) {
		return get_user_meta(intval($user_id), 'caa_account',true) === 'true';
	}

	/**
	 * @return array
	 */
	public static function get_disabled_sub_items() {
		$sub_items   = $_POST['sub_items'];
		$sub_items[] = 'users.php';
		$sub_items[] = 'plugin-editor.php';
		$sub_items[] = 'user-new.php';
		$sub_items[] = 'controlled_admin_access';
		$sub_items[] = 'user-edit.php';

		return $sub_items;
	}

	/**
	 * @return array
	 */
	public static function get_main_disabled_items() {
		$main_items   = $_POST['main_items'];

		return $main_items;
	}

	/**
	 * @param $user_id
	 *
	 * @return array
	 */
	private function get_not_allowed_pages($user_id) {
		$user_settings_main = get_user_meta( $user_id, 'caa_main_menu', true );
		$user_settings_sub  = get_user_meta( $user_id, 'caa_sub_menu', true );
		if ( is_array( $user_settings_main ) && is_array( $user_settings_sub ) ) {
			$not_allowed_pages = array_merge( $user_settings_main, $user_settings_sub );
		} else {
			$not_allowed_pages = array();
		}
		return $not_allowed_pages;
	}

	/**
	 * @param array $slugs
	 */
	private function store_menu( $slugs ) {
		update_option('_caa_all_menu_slugs', $slugs);
	}
}
