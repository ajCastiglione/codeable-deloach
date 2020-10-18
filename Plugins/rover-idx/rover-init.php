<?php

require_once ROVER_IDX_PLUGIN_PATH.'rover-shared.php';
require_once ROVER_IDX_PLUGIN_PATH.'rover-common.php';
require_once ROVER_IDX_PLUGIN_PATH.'rover-custom-post-types.php';
require_once ROVER_IDX_PLUGIN_PATH.'rover-version.php';


class Rover_IDX
	{
	public $roveridx_regions		= null;
	public $roveridx_theming		= null;
	public $is_debuggable			= null;
	public $debug_html				= array();
	public $all_selected_regions	= null;
	public $page_primary_slug		= null;
	public $post_id					= null;
	public $ping_status				= 'open';

	function __construct() {

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, __CLASS__);

		$this->roveridx_regions							= @get_option(ROVER_OPTIONS_REGIONS);
		$this->roveridx_theming							= @get_option(ROVER_OPTIONS_THEMING);

		add_action( 'wp_enqueue_scripts', 				'roveridx_css_and_js', 99 );		//	load late
//		add_action( 'wp_print_footer_scripts', 			'roveridx_css_and_js_footer' );

		add_action('init',								array($this,	'roveridx_rewrite_rules'));
		add_action(	'wp_footer',						array($this,	'roveridx_add_login'));
		add_filter( 'wp_nav_menu_items',				array($this,	'roveridx_add_login_to_menu'), 10, 2 );

		add_action(	'do_robots', 						array($this,	'rover_robots'), 100, 0);
		add_action( 'wp_head',							array($this,	'rover_preload'));

		add_action( 'roveridx_cron_hourly',				array($this,	'roveridx_hourly'));
		add_action( 'roveridx_cron_daily',				array($this,	'roveridx_daily'));

//		add_filter( 'language_attributes',				array($this, 'roveridx_fbml_add_namespace'));
//		add_filter( 'opengraph_type', 					array($this, 'roveridx_fb_og_type' ));

		if ( !defined( 'WP_INSTALLING' ) || WP_INSTALLING === false )
			{
			$this->all_selected_regions					= rover_get_selected_regions();
			$this->first_selected_region				= self::get_first_region();

			if ( is_admin() )
				{
				add_action( 'plugins_loaded',			array($this, 'roveridx_init_admin'), 15 );
				}
			else
				{
				add_action( 'plugins_loaded',			array($this, 'roveridx_init_front'));
				}

			if (is_admin() && (!defined( 'DOING_AJAX' ) || ! DOING_AJAX))
				{
				if ( true === $this->roveridx_regions['redirect_for_setup'] )
					add_action( 'init', array( $this, 'redirect_to_setup' ) );
				}
			}
		else
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'WP_INSTALLING is true - skipping roveridx_init_admin() and roveridx_init_front()' );
			}
		}

	public function get_first_region()	{

		$first_region	= null;
		foreach($this->all_selected_regions as $one_region => $region_slugs)
			{
			$first_region	= $one_region;
			break;
			}

		return $first_region;
		}

	public function upgrade_options()	{

		global								$rover_idx;

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' ');

		if (!isset($rover_idx->roveridx_regions['domain_id']) || empty($rover_idx->roveridx_regions['domain_id']))
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' `domain_id` is not set - leaving');
			return false;					/*	not yet setup	*/
			}


		/*	Migrate regions to new schema in ROVER_OPTIONS_REGIONS	*/

		if (
			(!isset($rover_idx->roveridx_regions['regions'])) || 
			(empty($rover_idx->roveridx_regions['regions'])) || 
			(strpos($rover_idx->roveridx_regions['regions'], ',') !== false)
			)
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' `regions` is not correctly set');

			$regions						= array();
			foreach($rover_idx->roveridx_regions as $one_key => $one_val)
				{
				if (strpos($one_key, 'slug') !== false)
					{
					/*
						Turn this:
							slugINTERMOUNTAIN	ID,OR
						Into this:
							regions				INTERMOUNTAIN|ID|OR
						Multi-regions will look like:
							regions				INTERMOUNTAIN|ID|OR||BAINMLS|ID
					*/
					$regions[]				= str_replace('slug', '', $one_key).'|'.str_replace(',', '|', $one_val);
					}
				}

			if (count($regions) === 0)			/*	Ugh - current string is really munged.  Rebuild it from the saved ClientDomains	*/
				{
				require_once ROVER_IDX_PLUGIN_PATH.'rover-content.php';

				global $rover_idx_content;

				$this->update_region_settings(__FUNCTION__, __LINE__, 'regions', $rover_idx_content->build_regions_string());
				}
			else								/*	update $this->roveridx_regions and update on-disk options	*/
				{
				$this->update_region_settings(__FUNCTION__, __LINE__, 'regions', implode('||', $regions));
				}
			}

		$site_version						= (isset($rover_idx->roveridx_theming['site_version']) && !empty($rover_idx->roveridx_theming['site_version']))
													? $rover_idx->roveridx_theming['site_version']
													: null;

		if (is_null($site_version) || (version_compare(ROVER_VERSION, $site_version) === 1))
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' site_version ['.ROVER_VERSION.'] / ['.$site_version.']');

			$theme_opts						= @get_option(ROVER_OPTIONS_THEMING);

			if (ROVER_VERSION == "2.1.0")
				{
				//	We no longer need Bootstrap
				$theme_opts['css_framework']= 'rover';

				require_once ROVER_IDX_PLUGIN_PATH.'rover-content.php';

				global $rover_idx_content;

				$rover_idx_content->update_site_settings(array('css_framework'	=> 'rover'));
				}

			$theme_opts['site_version']		= ROVER_VERSION;

			update_option(ROVER_OPTIONS_THEMING, $theme_opts);

			$rover_idx->roveridx_theming	= $theme_opts;

			add_action('admin_notices',	array($this, 'roveridx_admin_notice_upgraded'));
			}

		}

	public function roveridx_admin_notice_upgraded()	{

		$class 			= 'notice notice-info rover-notice is-dismissible';
		$message		= 'Rover IDX has been upgraded to '.ROVER_VERSION.'.';

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); 
	    }

	public function roveridx_rewrite_rules() {

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'add_rewrite_rules ');

		global			$rover_idx;

		$rover_post_id	= get_rover_post_id($rover_idx->roveridx_theming);

		foreach(array_unique(array_map('strtolower', $this->all_selected_regions)) as $one_state)
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'add_rewrite_rules: adding ['.$one_state.']');

			add_rewrite_rule( '^(.*)/'.$one_state.'/?', 'index.php?p='.$rover_post_id, 'top' );		/*	singlefamily/ma	*/
			add_rewrite_rule( '^'.$one_state.'/(.*)/?', 'index.php?p='.$rover_post_id, 'top' );		/*	ma/brewster		*/
			}
		}

	public function roveridx_add_login()	{

		global			$rover_idx;

		if (!empty($rover_idx->roveridx_theming['login_button']) && $rover_idx->roveridx_theming['login_button'] != 'none')
			{
			if ($rover_idx->roveridx_theming['login_button'] == 'link')
				{
				echo do_shortcode("[rover_idx_login hide_login_in_footer=true show_login_as_text=true]");
				return;
				}
			else if ($rover_idx->roveridx_theming['login_button'] == 'banner')
				{
				echo $this->login_dropdown_banner();
				return;
				}
			else if ($rover_idx->roveridx_theming['login_button'] == 'button')
				{
				echo $this->login_dropdown_button();
				return;
				}
			}

		echo $this->login_do_not_add();
		}

	private function login_dropdown_items()	{

		$the_html		= array();
		$the_html[]		= '<li class="showIfNotLoggedIn"><a href="#" onclick="roverLogin();return false;" rel="nofollow">Login</a></li>';
		$the_html[]		= '<li class="showIfNotLoggedIn"><a href="#" onclick="roverRegister();return false;" rel="nofollow">Register</a></li>';

		$the_html[]		= '<li class="showIfClient rover-control-panel"><a href="/rover-control-panel" rel="nofollow">Control Panel</a></li>';
		$the_html[]		= '<li class="showIfClient rover-control-panel fav"><a href="/rover-control-panel/my-favorites/" rel="nofollow">My Favorites</a></li>';
		$the_html[]		= '<li class="showIfClient rover-control-panel ss"><a href="/rover-control-panel/my-saved-searches/" rel="nofollow">My Saved Searches</a></li>';

		$the_html[]		= '<li class="showIfAgent rover-custom-listing-panel"><a href="/rover-custom-listing-panel/" rel="nofollow">Custom Listings Panel</a></li>';
		$the_html[]		= '<li class="showIfAgent rover-report-panel"><a href="/rover-report-panel/" rel="nofollow">Report Panel</a></li>';
		$the_html[]		= '<li class="showIfAgent rover-market-panel"><a href="/rover-market-conditions/" rel="nofollow">Market Conditions</a></li>';

		$the_html[]		= '<li class="showIfClient"><a href="#" onclick="roverLogout();return false;" rel="nofollow">Logout</a></li>';

		return implode('', $the_html);
		}

	public function login_dropdown_banner($add_top_top = true)	{

		$the_html		= array();
		$the_html[]		= '<div id="roverContent" class="rover-framework rover-login-framework '.(($add_top_top) ? 'rover-login-move' : '').' rover" data-reg_context="rover-login-framework rover-login-move">';
		$the_html[]		=	'<div id="headerTopLine" class="show_just_this_topline">';
		$the_html[]		=		$this->login_dropdown();
		$the_html[]		=		$this->login_saved_search_dropdown();
		$the_html[]		=		$this->login_favorites_dropdown();
		$the_html[]		=		$this->roveridx_msg();
		$the_html[]		=		'<div style="clear:both;"></div>';
		$the_html[]		=	'</div>';
		if ($add_top_top)
			$the_html[]	=	'<script type="text/javascript">/*<![CDATA[*//*---->*/(function( $ ){$l= $( ".rover-login-move" );if ($l.length){$( "body" ).prepend( $l );$l.show();}})( jQuery );/*--*//*]]>*/</script>';
		$the_html[]		= '</div>';

		$the_html[]		= $this->roveridx_add_authdata();

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'html = '.strlen(implode(',', $the_html)).' bytes');

		return implode('', $the_html);
		}

	public function login_dropdown_button($add_top_top = true)	{

		$the_html		= array();
		$the_html[]		= '<div id="roverContent" class="rover-framework rover-login-framework '.(($add_top_top) ? 'rover-login-move' : '').' rover" data-reg_context="rover-login-framework rover-login-move">';
		$the_html[]		=	'<div id="headerTopLine" class="show_just_this_topline">';
		$the_html[]		=		$this->login_dropdown();
		$the_html[]		=		'<div style="clear:both;"></div>';
		$the_html[]		=	'</div>';
		if ($add_top_top)
			$the_html[]	=	'<script type="text/javascript">/*<![CDATA[*//*---->*/(function( $ ){$l= $( ".rover-login-move" );if ($l.length){$( "body" ).prepend( $l );$l.show();}})( jQuery );/*--*//*]]>*/</script>';
		$the_html[]		= '</div>';

		$the_html[]		= $this->roveridx_add_authdata();

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'html = '.strlen(implode(',', $the_html)).' bytes');

		return implode('', $the_html);
		}

	private function login_do_not_add() {

		$the_html		= array();
		$the_html[]		= $this->roveridx_add_authdata();

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'html = '.strlen(implode(',', $the_html)).' bytes');

		return implode('', $the_html);
		}

	public function roveridx_add_login_to_menu( $items, $args ) {

		global					$rover_idx;

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, '');

		$login_label			= (isset($rover_idx->roveridx_theming['login_label']))
										? $rover_idx->roveridx_theming['login_label']
										: null;
		$login_dropdown			= (isset($rover_idx->roveridx_theming['login_dropdown']))
										? $rover_idx->roveridx_theming['login_dropdown']
										: 'display';

		$fav_label				= (isset($rover_idx->roveridx_theming['fav_label']))
										? $rover_idx->roveridx_theming['fav_label']
										: null;
		$fav_dropdown			= (isset($rover_idx->roveridx_theming['fav_dropdown']))
										? $rover_idx->roveridx_theming['fav_dropdown']
										: 'display';

		$ss_label				= (isset($rover_idx->roveridx_theming['ss_label']))
										? $rover_idx->roveridx_theming['ss_label']
										: null;
		$ss_dropdown			= (isset($rover_idx->roveridx_theming['ss_dropdown']))
										? $rover_idx->roveridx_theming['ss_dropdown']
										: 'display';

		$add_login				= false;
		$add_favorites			= false;
		$add_saved_searches		= false;
		if (isset($rover_idx->roveridx_theming['login_button']))
			{
			$parts				= explode(';', $rover_idx->roveridx_theming['login_button']);
			$menu_location		= (is_array($parts) && isset($parts[0]))
										? $parts[0]
										: null;
			$other_menu_items	= (is_array($parts) && isset($parts[1]))
										? $parts[1]
										: null;

			if ($menu_location == $args->theme_location)
				{
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Adding Rover Login / Register menu');
				$add_login		= true;

				foreach(explode(',', $other_menu_items) as $other)
					{
					if ($other == 'fav')
						$add_favorites		= true;
					else if ($other == 'ss')
						$add_saved_searches	= true;
					}
				}
			else
				{
				return $items;
				}
			}

		$login_label			= (isset($rover_idx->roveridx_theming['login_label']))
										? $rover_idx->roveridx_theming['login_label']
										: 'Login/Register';

		$the_html				= array();
		$the_html[]				= $items;

		if ($add_favorites)
			{
			if ($fav_dropdown === 'hide')
				{
				$the_html[]		= '<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-type-rover-favorites">';
				$the_html[]		=	'<a href="/rover-control-panel/my-favorites/" class="rover-fav-label" rel="nofollow" onclick="return false;">Favorites (<span class="num_favs">0</span>)</a>';
				$the_html[]		= '</li>';
				}
			else
				{
				$the_html[]		= '<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-has-children menu-item-type-rover-favorites">';
				$the_html[]		=	'<a href="#" class="rover-fav-label" rel="nofollow" onclick="return false;">Favorites (<span class="num_favs">0</span>)</a>';
				$the_html[]		=	'<ul class="sub-menu rover-dropdown-ul fav right"></ul>';
				$the_html[]		= '</li>';
				}
			}

		if ($add_saved_searches)
			{
			if ($ss_dropdown === 'hide')
				{
				$the_html[]		= '<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-type-rover-saved-searches">';
				$the_html[]		=	'<a href="/rover-control-panel/my-saved-searches/" class="rover-ss-label" rel="nofollow" onclick="return false;">Saved Searches (<span class="num_ss">0</span>)</a>';
				$the_html[]		= '</li>';
				}
			else
				{
				$the_html[]		= '<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-has-children menu-item-type-rover-saved-searches">';
				$the_html[]		=	'<a href="#" class="rover-ss-label" rel="nofollow" onclick="return false;">Saved Searches (<span class="num_ss">0</span>)</a>';
				$the_html[]		=	'<ul class="sub-menu rover-dropdown-ul ss right"></ul>';
				$the_html[]		= '</li>';
				}
			}

		if ($add_login)
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'adding <li>');

			if ($login_dropdown === 'hide')
				{
				$the_html[]		= '<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-type-rover-login">';
				$the_html[]		=	'<a href="#" class="rover-login-label" onclick="roverLogin();return false;" data-label="'.$login_label.'">'.$login_label.'</a>';
				$the_html[]		= '</li>';
				}
			else
				{
				$the_html[]		= '<li class="menu-item menu-item-type-post_type menu-item-object-page menu-item-has-children menu-item-type-rover-login">';
				$the_html[]		=	'<a href="#" class="rover-login-label" onclick="return false;" data-label="'.$login_label.'">'.$login_label.'</a>';
				$the_html[]		=	'<ul class="sub-menu rover-framework rover-login-framework">';
				$the_html[]		=		$this->login_dropdown_items();
				$the_html[]		=	'</ul>';
				$the_html[]		= '</li>';
				}
			}

		$the_html[]				= $this->roveridx_add_authdata();

	    return implode('', $the_html);
		}
	
	private function roveridx_msg()	{

		$the_html[]		=		'<p class="rover-msg">';
		$the_html[]		=			'<span class="rover-msg-icon" style="display: none;">';
		$the_html[]		=				'<i class="fa fa-spinner fa-pulse fa-spin"></i>';
		$the_html[]		=			'</span>';
		$the_html[]		=			'<span class="rover-msg-text" style="display: inline;"></span>';
		$the_html[]		=		'</p>';

		return implode('', $the_html);
		}

	private function login_dropdown()	{

		$the_html[]		=		'<div class="dropdown rover_login_dropdown floatRight">';
		$the_html[]		=			'<a href="#" id="rover-login" class="rover-button-dropdown rover-background rover-button" rel="nofollow" style="">';
		$the_html[]		=				'<span class="rover-button-dropdown-label rover-login-label rover-nowrap">Login/Register</span> ';
		$the_html[]		=				'<span class="fa fa-caret-down">&nbsp;</span>';
		$the_html[]		=			'</a>';
		$the_html[]		=			'<ul class="rover-dropdown-ul right" style="display:none;">';
		$the_html[]		=				$this->login_dropdown_items();
		$the_html[]		=			'</ul>';
		$the_html[]		=		'</div>';
		
		return implode('', $the_html);
		}

	public function login_saved_search_dropdown()	{

		$the_html[]		=		'<div class="dropdown rover_saved_search_dropdown rover_saved_search_count floatRight">';
		$the_html[]		=			'<a href="#" id="rover-login" class="rover-button-dropdown rover-background rover-button" rel="nofollow" style="">';
		$the_html[]		=				'<span class="rover-button-dropdown-label rover-nowrap">Saved Searches (0)</span> ';
		$the_html[]		=				'<span class="fa fa-caret-down">&nbsp;</span>';
		$the_html[]		=			'</a>';
		$the_html[]		=			'<ul class="rover-dropdown-ul right" style="display:none;">';
		$the_html[]		=			'</ul>';
		$the_html[]		=		'</div>';
		
		return implode('', $the_html);
		}

	public function login_favorites_dropdown()	{

		$the_html[]		=		'<div class="dropdown rover_saved_search_dropdown rover_favorite_count floatRight">';
		$the_html[]		=			'<a href="#" id="rover-login" class="rover-button-dropdown rover-background rover-button" rel="nofollow" style="">';
		$the_html[]		=				'<span class="rover-button-dropdown-label rover-nowrap">Favorites (0)</span> ';
		$the_html[]		=				'<span class="fa fa-caret-down">&nbsp;</span>';
		$the_html[]		=			'</a>';
		$the_html[]		=			'<ul class="rover-dropdown-ul right" style="display:none;">';
		$the_html[]		=			'</ul>';
		$the_html[]		=		'</div>';
		
		return implode('', $the_html);
		}

	public function roveridx_add_authdata() {

		global			$rover_idx;

		$the_html		= array();
		$the_html[]		= '<div class="rover-default-auth" ';
		$the_html[]		=	'data-all_regions="'.implode(',', array_keys($rover_idx->all_selected_regions)).'" ';
		$the_html[]		=	'data-css_framework="'.$rover_idx->roveridx_theming['css_framework'].'" ';
		$the_html[]		=	'data-domain="'.rover_clean_domain(get_site_url()).'" ';
		$the_html[]		=	'data-domain_id="'.$rover_idx->roveridx_regions['domain_id'].'" ';
		$the_html[]		=	'data-fav_requires_login="open" ';
		$the_html[]		=	'data-is_multi_region="'.((count($rover_idx->all_selected_regions) > 1) ? 'true' : 'false').'" ';
		$the_html[]		=	'data-js_min="true" ';
		$the_html[]		=	'data-is_logged_in="false" ';
		$the_html[]		=	'data-load_fontawesome="'.(($rover_idx->roveridx_theming['load_fontawesome'] == 'Yes') ? 'true' : 'false').'" ';
		$the_html[]		=	'data-logged_in_email="" ';
		$the_html[]		=	'data-logged_in_user_id="" ';
		$the_html[]		=	'data-logged_in_authkey="" ';
		$the_html[]		=	'data-logged_in_user_is_agent="false" ';
		$the_html[]		=	'data-logged_in_user_is_rental_agent="false" ';
		$the_html[]		=	'data-logged_in_user_is_broker="false" ';
		$the_html[]		=	'data-logged_in_user_is_admin="false" ';
		$the_html[]		=	'data-page_url="/" ';
		$the_html[]		=	'data-pdf_requires_login="open" ';
		$the_html[]		=	'data-prop_anon_views_curr="0" ';
		$the_html[]		=	'data-prop_details="link" ';
		$the_html[]		=	'data-prop_requires_login="open" ';
		$the_html[]		=	'data-region="'.$this->first_selected_region.'" ';
		$the_html[]		=	'data-register_before_or_after_prop_display="after" ';
		$the_html[]		=	'data-items="25">';
		$the_html[]		= '</div>';

		return implode('', $the_html);
		}

	public function roveridx_fbml_add_namespace( $output ) {

		//	Does not W3C validate
	
		$output .= ' xmlns:fb="' . esc_attr(ROVERIDX_FBML_NS_URI) . '"';

		return $output;
		}

	public function roveridx_fb_og_type( $type ) {
	    if (is_singular())
	        $type = "article";
	    else 
			$type = "blog";
	    return $type;
	    }

	public function roveridx_init_admin()
		{
		$this->upgrade_options();

		require_once ROVER_IDX_PLUGIN_PATH.'admin/rover-admin-init.php';
		}

	public function roveridx_init_front() 
		{
		global						$wp, $post;

		$this->upgrade_options();

		require_once ROVER_IDX_PLUGIN_PATH.'widgets/init.php';
		
		$http_accept				= strtolower($_SERVER['HTTP_ACCEPT']);
		$is_valid_request			= (strpos($http_accept, "text/html") === false && strpos($http_accept, "*/*") === false)
											? false
											: true;
		$is_valid_request			= true;


		if ($is_valid_request)
			require_once ROVER_IDX_PLUGIN_PATH.'rover-shortcodes.php';
		else
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Not loading shortcodes. ['.$http_accept.'] is not a valid request.');

		$curr_path					= parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
		$the_page_clean				= preg_replace('/[^a-zA-Z0-9]/', '', $curr_path);

		//	We don't seem to have access to $post->ID this early.  So we have to test if the page
		//	exists manually.  If it does exist, DO NOT EXECUTE the 404 code.

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Starting...');
		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'REQUEST_URI ['.$_SERVER["REQUEST_URI"].']');
		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'parsed REQUEST_URI ['.$curr_path.']');
		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'the_page_clean ['.$the_page_clean.']');

//		foreach (debug_backtrace() as $btdKey => $btdVal)
//			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, '['.$btdKey.'] File: '.$btdVal['file'].' / Function: '.$btdVal['function'].' / Line: '.$btdVal['line']);


		if (is_admin())
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'true - do not do_rover_404');
			}

		if (false && is_category())			/*	It is too early for is_category()	*/
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'true - do not do_rover_404');
			}

		$the_page					= get_page_by_path($curr_path, OBJECT);
		if (!is_null($the_page))
			{
			//	This page may contain one or more shortcodes

			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'path ['.$curr_path.'] exists in WP as page ['.$the_page->ID.']');
			}
		else if (empty($the_page_clean))
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'path ['.$curr_path.'] always exists and is not a 404');
			}
		else
			{
			if ($is_valid_request)
				{
				require_once ROVER_IDX_PLUGIN_PATH.'rover-content.php';

				global $rover_idx_content;

				add_filter('do_parse_request', function($do_parse, $wp) {			//	Skip parse_request(), which may send an early 404 header

					global $rover_idx_content;

					$found_slug		= $rover_idx_content->check_url_for_rover_keys();

					if ($found_slug !== false)
						{
						//	https://roots.io/routing-wp-requests/

//						$wp->query_vars			= ['post_type' => 'page'];
						$wp->query_vars			= array('post_type' => 'page');

						return false;
						}

					return true;

					}, 10, 2);

				$wp_query->query_vars["error"]	= "";								//	Make sure this is not set to 404 until after we've checked for dynamic


				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'path ['.$curr_path.'] does not exist in WP');

				$rover_idx_content->rover_setup_404();
				}
			else
				{
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'This REQUEST is looking for an image - ignore!');
				}
			}

		//	Cron jobs

		if ( !wp_next_scheduled('roveridx_cron_daily') ) {
			wp_schedule_event( time(), 'daily', 'roveridx_cron_daily' );
			}

		if ( !wp_next_scheduled('roveridx_cron_hourly') ) {
			wp_schedule_event( time(), 'hourly', 'roveridx_cron_hourly' );
			}
		}

	public function update_region_settings($fn, $ln, $key, $val)	{

		$region_options				= @get_option(ROVER_OPTIONS_REGIONS);

		if (is_array($region_options))
			{
			foreach($region_options as $r_key => $r_val)
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, sprintf("[%s] [%s] [%s] => [%s]", $fn, $ln, $r_key, $r_val));

			$region_options[$key]	= $val;
			$ret					= update_option(ROVER_OPTIONS_REGIONS, $region_options);

			foreach(get_option(ROVER_OPTIONS_REGIONS) as $r_key => $r_val)
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, sprintf("[%s] [%s] [%s] => [%s]", $fn, $ln, $r_key, $r_val));

			$this->roveridx_regions	= $region_options;

			return $ret;
			}
		else
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, sprintf("Failed to fetch [%s] as an array", ROVER_OPTIONS_REGIONS));
			}

		return false;
		}

	public function redirect_to_setup() {

		$this->update_region_settings(__FUNCTION__, __LINE__, 'redirect_for_setup', false);

		wp_redirect( admin_url('admin.php?page=rover_idx') );

		exit;
		}

	public function roveridx_hourly() {

		require_once ROVER_IDX_PLUGIN_PATH.'rover-social-common.php';

		roveridx_refresh_social();
		}
	
	public function roveridx_daily() {
	
		require_once ROVER_IDX_PLUGIN_PATH.'rover-sitemap.php';

		roveridx_refresh_sitemap();
		}

	public function rover_robots() {
//		header( 'Content-Type: text/plain; charset=utf-8' );
		
		global						$rover_idx;
		$sitemap_opts				= get_option(ROVER_OPTIONS_SEO);

		if ($sitemap_opts === false)
			return;

		if (!is_array($sitemap_opts))
			return;

		do_action( 'do_robotstxt' );
		
		$output						= null;
		$public						= get_option( 'blog_public' );
		if ( '0' != $public ) {
		
			foreach ($rover_idx->all_selected_regions as $one_region => $region_slugs)
				{		
				if (array_key_exists($one_region, $sitemap_opts))
					{
					$output .= "Sitemap: ".$sitemap_opts[$one_region]['url']."\n";
					}
				}

			}
		
		echo apply_filters('robots_txt', $output, $public);
		}


	public function rover_preload() {

		echo "<link rel='dns-prefetch' href='https://css.roveridx.com'>";
		echo "<link rel='dns-prefetch' href='https://js.roveridx.com'>";
		echo "<link rel='dns-prefetch' href='https://wasabi.roveridx.com'>";

		if (isset($this->roveridx_theming['load_fontawesome']) && $this->roveridx_theming['load_fontawesome'] == 'Yes')
			echo "<link rel='preload' as='font' href='https://fastfonts.roveridx.com/font-awesome/fontawesome-webfont.woff2?v=4.7.0' type='font/woff2' crossorigin>\n";

		$js_ver						= (isset($this->roveridx_theming['js_version']) && !empty($this->roveridx_theming['js_version']))
											? $this->roveridx_theming['js_version']
											: ROVER_JS_VERSION;
		$file						= (isset($this->roveridx_theming['load_fontawesome']) && $this->roveridx_theming['load_fontawesome'] == 'Yes')
											? 'rover_awesome.min.css'
											: 'rover.min.css';


#		echo sprintf("<link rel='preload' as='style' href='https://css.roveridx.com/2.1.0/css/%s/%s' crossorigin='anonymous'>\n", $js_ver, $file);
#
#
#		$file						= (isset($_GET['jsmin']) || isset($_GET['jscdn']))
#											? 'rover.js'
#											: 'rover.min.js';
#
#		echo sprintf("<link rel='preload' as='script' href='https://js.roveridx.com/2.1.0/js/%s/%s' crossorigin='anonymous'>\n", $js_ver, $file);
		}

	}



global			$rover_idx;

if (!is_object($rover_idx))
	{
	$rover_idx	= new Rover_IDX();
	}


?>