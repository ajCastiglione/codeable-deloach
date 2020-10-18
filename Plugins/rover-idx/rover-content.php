<?php

class Rover_IDX_Content
	{
	public	$rover_html							= null;

	private $rover_body_class					= null;
	private $rover_title						= null;
	private $rover_meta_desc					= null;
	private	$rover_og_images					= null;
	private $rover_meta_robots					= null;
	private $rover_meta_keywords				= null;
	private	$rover_canonical_url				= null;
	private $rover_component					= null;
	private	$rover_redirect						= null;
	private	$rover_404_regions					= null;
	private	$rover_404_slugs					= null;

	private	$dynamic_sidebar					= null;

    public static $fetching_api_key				= false;

	function __construct() {

		add_action( 'update_option_permalink_structure' , array($this, 'permalinks_have_been_updated'), 10, 2 );

		}

	public function rover_setup_404()
		{
		add_filter('the_posts',	array($this, 'rover_dynamic_page'));

		$this->rover_idx_setup_dynamic_meta(null);
		}


	public function rover_dynamic_page($posts)	{

		global									$wp, $wp_query, $rover_idx;

		remove_filter('the_posts',	array($this, 'rover_dynamic_page'));	//	Avoid firing twice

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Starting');

		$found_slug								= $this->check_url_for_rover_keys();

		if ($found_slug !== false)
			{
			$component							= (is_string($found_slug) && strpos($found_slug, 'rover-') !== false)
														? $found_slug
														: 'ROVER_COMPONENT_404';

			$the_rover_content					= $this->rover_content(	$component, array('region' => $this->rover_404_regions));

			$this->rover_html					= $the_rover_content['the_html'];
			$this->rover_component				= $the_rover_content['the_component'];
			$this->rover_redirect				= $the_rover_content['the_redirect'];

			$this->rover_idx_setup_dynamic_meta($the_rover_content);

			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'rover_component is ['.$this->rover_component.']');
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, strlen($this->rover_html).' bytes received from rover_content');
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'redirect is ['.$this->rover_redirect.']');

			if (empty($this->rover_html)) 
				{
				if ($this->redirect_if_necessary())
					{
					status_header( 404 );
					$wp_query->is_404				= true;
					}

				//	This is a real 404 - let WP do it's thing
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Not a Rover Special Page');
				}
			else
				{
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'This is a RoverSpecialPage');

				$this->redirect_if_necessary();

				$posts							= array();
				$posts[]						= $this->create_rover_content();

				//	Trick wp_query into thinking this is a page (necessary for wp_title() at least)
				//	Not sure if it's cheating or not to modify global variables in a filter 
				//	but it appears to work and the codex doesn't directly say not to.

//				$wp_query->post					= $posts[0]->ID;
				$wp_query->post					= $posts[0];
				$wp_query->posts				= array( $posts[0] );

				$wp_query->queried_object		= $posts[0];
				$wp_query->queried_object_id	= $posts[0]->ID;
				
				$wp_query->is_rover_page		= true;		//	Used for domain '8'

				$wp_query->found_posts			= 1;
				$wp_query->post_count			= 1;
				$wp_query->max_num_pages		= 1;

				add_filter('template_include', array($this, 'rover_template_include'), 99);

				//	We want this to be a page - more flexible for setting templates dynamically

				$wp_query->is_page				= true;

				$wp_query->is_single			= false;	//	Applicable to Posts
				$wp_query->is_singular			= true;		//	Applicable to Pages

				$wp_query->is_attachment		= false;
				$wp_query->is_archive			= false; 
				$wp_query->is_category			= false;
				$wp_query->is_tag				= false; 
				$wp_query->is_tax				= false;
				$wp_query->is_author			= false;
				$wp_query->is_date				= false;
				$wp_query->is_year				= false;
				$wp_query->is_month				= false;
				$wp_query->is_day				= false;
				$wp_query->is_time				= false;
				$wp_query->is_search			= false;
				$wp_query->is_feed				= false;
				$wp_query->is_comment_feed		= false;
				$wp_query->is_trackback			= false;
				$wp_query->is_home				= false;
				$wp_query->is_embed				= false;
				$wp_query->is_404				= false; 
				$wp_query->is_paged				= false;
				$wp_query->is_admin				= false; 
				$wp_query->is_preview			= false; 
				$wp_query->is_robots			= false; 
				$wp_query->is_posts_page		= false;
				$wp_query->is_post_type_archive	= false;

				// Longer permalink structures may not match the fake post slug and cause a 404 error so we catch the error here
				unset($wp_query->query["error"]);
				$wp_query->query_vars["error"]	= "";

				$wp_query->is_404				= false;

				/* Update globals		*/
				$GLOBALS['wp_query']			= $wp_query;
				$wp->register_globals();
				}
			}
		else 
			{
			//	This is a real 404 - let WP do it's thing
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'No matching slugs - this is not a Rover Special Page');
			}

		return $posts;
		}

	private function create_rover_content()
		{
		global $wpdb, $rover_idx;

		$the_guid_parts			= $this->rover_redirect;
		if (empty($this->rover_redirect))
			{
			$uri				= $_SERVER['REQUEST_URI'];
			$the_guid_parts		= parse_url($uri, PHP_URL_PATH);
			}

		//	If Rover is creating this content, tell WP to skip the annoying 'wpautop'  
		//	function, which loves to wrap double line-breaks in <p> tags

		remove_filter( 'the_content', 'wpautop' ); 
		remove_filter( 'the_excerpt', 'wpautop' );  
//		add_filter('the_title', array($this, 'strip_title'), 10, 2); 

		$post					= new stdClass();

		$post->post_author		= get_current_user_id();

		//	The safe name for the post.  This is the post slug.

		$post->post_name		= (string) $this->rover_404_slugs;
		$post->post_type		= 'page';

		//	Not sure if this is even important.  But gonna fill it in anyway.

		$post->guid				= get_bloginfo("wpurl") . $the_guid_parts;

		if (empty($post->post_title) && !empty($this->rover_title))
			$post->post_title	= $this->rover_title;

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Creating content for '.$this->rover_404_regions.' ('.strlen($this->rover_html).' bytes) ['.$post->guid.']');

		$post->post_content		= $this->rover_html . $this->formatted_debug();

		//	Fake post ID to prevent WP from trying to show comments for
		//	a post that doesn't really exist.

		$rover_idx->post_id		= get_rover_post_id($rover_idx->roveridx_theming);
		$post->ID				= $rover_idx->post_id;

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'is using post_id '.$post->ID);

		//	Static means a page, not a post.

		$post->post_status		= 'publish';
		$post->comment_status	= 'closed';
		$post->ping_status		= 'closed';		// $this->ping_status;
		$post->filter			= 'raw';		// important!

		$post->comment_count	= 0;

		$post->post_date		= current_time('mysql');
		$post->post_date_gmt	= current_time('mysql', 1);

		$post					= new WP_Post( $post );

		wp_cache_add( $post->ID, $post, 'posts' );

		//	For Rover dynamic pages - let Rover build the meta
		if ( class_exists( 'WPSEO_Frontend' ) ) {
			remove_action( 'template_redirect', array( WPSEO_Frontend::get_instance(), 'clean_permalink' ), 1 );

			add_filter( 'wpseo_title', '__return_false' );
			add_filter( 'wpseo_metadesc', '__return_false' );

			add_filter( 'wpseo_opengraph_title', '__return_false' );
			add_filter( 'wpseo_opengraph_desc', '__return_false' );
			add_filter( 'wpseo_opengraph_url', '__return_false' );
			add_filter( 'wpseo_canonical', '__return_false',  10, 1 );

			add_filter( 'wpseo_og_article_published_time', '__return_false' );
			add_filter( 'wpseo_og_article_modified_time', '__return_false' );
			add_filter( 'wpseo_og_og_updated_time', '__return_false' );
			}
		
		//	Jetpack
		if ( defined( 'JETPACK__VERSION' ) ) {
			add_filter( 'jetpack_enable_open_graph', '__return_false' );
			}

		if ( function_exists( 'genesis_grid_loop' ) )		{	//	Genesis
			remove_action( 'wp_head', 'genesis_robots_meta');
			remove_action( 'wp_head', 'genesis_canonical', 5); 
			remove_action( 'genesis_meta','genesis_robots_meta' );
			remove_action( 'genesis_after_post_content', 'genesis_post_meta' );
			}

		if ( function_exists( 'x_get_content_layout' ) )	{	//	x theme
//			remove_filter( 'the_content', array( $this, 'cs_content_late' ), (999999 + 1) );
			remove_filter( 'the_content', 'sharing_display', (19 + 1) );
			add_filter( 'sharing_show', '__return_false', 9999 );
			}

		$this->roveridx_use_our_og_images();

		remove_action( 'wp_head', 'feed_links_extra', 3 );		// Removes the links to the extra feeds such as category feeds
		remove_action( 'wp_head', 'feed_links', 2 );			// Removes links to the general feeds: Post and Comment Feed
		remove_action( 'wp_head', 'rsd_link');					// Removes the link to the Really Simple Discovery service endpoint, EditURI link
		remove_action( 'wp_head', 'wlwmanifest_link');			// Removes the link to the Windows Live Writer manifest file.
		remove_action( 'wp_head', 'index_rel_link');			// Removes the index link
		remove_action( 'wp_head', 'parent_post_rel_link');		// Removes the prev link
		remove_action( 'wp_head', 'start_post_rel_link');		// Removes the start link
		remove_action( 'wp_head', 'adjacent_posts_rel_link');	// Removes the relational links for the posts adjacent to the current post.
		remove_action( 'wp_head', 'wp_generator');				// Removes the WordPress version i.e. - WordPress 2.8.4

		add_filter('body_class', array($this, 'roveridx_body_class'));

		add_action('wp_head',	array($this, 'roveridx_meta_description'), 1);
		add_action('wp_head',	array($this, 'roveridx_meta_robots'), 5);
		add_action('wp_head',	array($this, 'roveridx_meta_keywords'), 5);
		add_action('wp_head',	array($this, 'roveridx_meta_generator'), 5);
		add_action('wp_head',	array($this, 'roveridx_canonical_url'), 5);

		add_action('wp_head',	array($this, 'roveridx_og_updated_time'), 5);
		add_action('wp_head',	array($this, 'roveridx_og_title'), 5);
		add_action('wp_head',	array($this, 'roveridx_og_description'), 5);

		add_action('wp_head',	array($this, 'roveridx_og_url'), 5);


//		if (in_array($this->rover_404_slugs, $this->rover_standard_slugs))		//	We don't want Googlebot to crawl roverControlPanel
//			add_action('wp_head', array($this, 'roveridx_meta_nofollow'));

		return $post;		
		}

	public function roveridx_meta_description() {	
		echo "<meta name='description' content='".$this->rover_meta_desc."' />\n";
		}
	public function roveridx_meta_robots() {	
		if (!empty($this->rover_meta_robots))
			echo "<meta name='robots' content='".$this->rover_meta_robots."' />\n";
		}
	public function roveridx_meta_keywords() {	
		if (!empty($this->rover_meta_keywords))
			echo "<meta name='keywords' content='".$this->rover_meta_keywords."' />\n";
		}
	public function roveridx_meta_generator() {
		echo "<meta name='generator' content='Rover IDX ".roveridx_get_version()."' />\n";
		}
	public function roveridx_canonical_url() {

		if (!empty($this->rover_canonical_url))
			{
			echo "<link rel='canonical' href='".$this->rover_canonical_url."' />\n";
			}
		else
			{
			global							$wp;
			
			$url_ends_with_slash			= true;
			$perm							= get_option('permalink_structure');
			if ($perm && substr($perm, -1) != '/')
				$url_ends_with_slash		= false;

			$url							= ($url_ends_with_slash)
													? trailingslashit($url)
													: $url;

			echo "<link rel='canonical' href='".$url."' />\n";
			}
		}

	public function roveridx_og_updated_time()	{

		//	-0001-11-30T00:00:00+00:00
		
		echo "<meta property='og:updated_time' content='".date('Y-m-dTH:i:s+00:00')."' />\n";

		}

	public function roveridx_og_title() {

		echo "<meta property='og:title' content='".strip_tags( $this->rover_title )."'>\n";

		}

	public function roveridx_og_description() {

		echo "<meta property='og:description' content='".$this->rover_meta_desc."'>\n";

		}

	public function roveridx_og_images() {

		foreach(explode(',', $this->rover_og_images) as $one_img)
			echo "<meta property='og:image' content='".$one_img."'>\n";

		}

	public function roveridx_og_url()	{

		echo "<meta property='og:url' content='".$this->rover_canonical_url."'>\n";

		}

	public function roveridx_use_our_og_images() {

		if (!empty($this->rover_og_images))
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'starting');

			add_filter( 'wpseo_opengraph_image', '__return_false' );
			add_filter( 'jetpack_enable_open_graph', '__return_false' );

			add_action( 'wp_head',	array($this, 'roveridx_og_images'), 10);
			}
		else
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'not using og_images');
			}
		}

	public function roveridx_body_class($classes) {

		if ($this->rover_body_class)
			{
			if (is_array($classes))
				$classes[]	= $this->rover_body_class;
			else
				$classes	= array($this->rover_body_class);
			}

		return $classes;
		}

	private function use_dynamic_sidebar($component)	{
		
		if ($component == 'ROVER_COMPONENT_404')
			{
			global							$rover_idx_dynamic_meta;

			$this->dynamic_sidebar			= $rover_idx_dynamic_meta->get_sidebar();

			if (!is_null($this->dynamic_sidebar))
				return true;
			}

		return false;
		}

	public function update_site_settings($atts)	{

		if (is_array($atts) && count($atts))
			{
//			$the_rover_content				= $this->rover_content(
//																'ROVER_COMPONENT_UPDATE_SITE_SETTINGS', 
//																array_merge(
//																	array(
//																		'not-region'	=> 'Not used', 
//																		'not-regions'	=> 'Not Used'),
//																	$atts
//																	)
//																);
			$the_rover_content				= $this->rover_content(
																'ROVER_COMPONENT_UPDATE_SITE_SETTINGS', 
																$atts
																);
			}

		}

	public function build_regions_string()	{

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'starting');

		$the_rover_content					= $this->rover_content(
																'ROVER_COMPONENT_REBUILD_REGIONS', 
																array('not-region' => 'Not used', 'not-regions' => 'Not Used')
																);

		$regions_str						= $the_rover_content['the_html'];

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'regions string ['.$regions_str.']');

		return $regions_str;
		}

	private function get_api_key()	{

		global								$rover_idx;

        if ( self::$fetching_api_key )
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'already fetching');
			return null;
			}

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'starting');

		//	if necessary, fetch a new api key

		if (empty($rover_idx->roveridx_regions['api_key']))
			{
			self::$fetching_api_key			= true;

			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Fetching new API key');

			$the_rover_content				= $this->rover_content(
																'ROVER_COMPONENT_GET_API_KEY', 
																array('not-region' => 'Not used', 'not-regions' => 'Not Used')
																);

			$api_key						= $the_rover_content['the_html'];
//			$api_key						= json_decode($the_rover_content['the_html'], true);

			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Received new API key ['.$api_key.']');

			if (!empty($api_key))
				{
				if ($rover_idx->update_region_settings(__FUNCTION__, __LINE__, 'api_key', $api_key))
					rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Saved new API key to Region options');

				return $api_key;
				}
			}
	
		if (empty($rover_idx->roveridx_regions['api_key']))
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'failed');
			return null;
			}

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Returning ['.$rover_idx->roveridx_regions['api_key'].']');

		return $rover_idx->roveridx_regions['api_key'];
		}

	private function check_js_version($newest_js_ver)	{

		global								$rover_idx;

		$current_js_ver						= (isset($rover_idx->roveridx_theming['js_version']))
													? $rover_idx->roveridx_theming['js_version']
													: ROVER_JS_VERSION;

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' latest_js_ver ['.$newest_js_ver.'] / ['.$current_js_ver.']');

		if ((version_compare($newest_js_ver, $current_js_ver) !== 0))
			{
			$theme_opts						= @get_option(ROVER_OPTIONS_THEMING);

			$theme_opts['js_version']		= $newest_js_ver;
			update_option(ROVER_OPTIONS_THEMING, $theme_opts);

			$rover_idx->roveridx_theming	= $theme_opts;
			}
		}

	private function rover_idx_setup_dynamic_meta($the_rover_content = null)	{

		require_once ROVER_IDX_PLUGIN_PATH.'rover-dynamic-meta.php';

		global								$rover_idx_dynamic_meta;

		if (is_null($this->rover_body_class))
			{
			$this->rover_body_class			= $rover_idx_dynamic_meta->body_class;
			}

		if (is_null($this->rover_title))
			{
			if (!is_null($rover_idx_dynamic_meta) && !empty($rover_idx_dynamic_meta->title_tag))
				$this->rover_title			= $rover_idx_dynamic_meta->title_tag;
			else if (!is_null($the_rover_content) && !empty($the_rover_content['the_title']))
				$this->rover_title			= $the_rover_content['the_title'];
			}

		if (is_null($this->rover_meta_desc))
			{
			if (!is_null($rover_idx_dynamic_meta) && !empty($rover_idx_dynamic_meta->meta_desc))
				$this->rover_meta_desc		= $rover_idx_dynamic_meta->meta_desc;
			else if (!is_null($the_rover_content) && !empty($the_rover_content['the_meta_desc']))
				$this->rover_meta_desc		= $the_rover_content['the_meta_desc'];
			}

		if (is_null($this->rover_og_images))
			{
			$this->rover_og_images			= $the_rover_content['the_og_images'];
			}

		if (is_null($this->rover_meta_robots))
			{
			$this->rover_meta_robots		= $rover_idx_dynamic_meta->meta_robots;
			}

		if (is_null($this->rover_meta_keywords))
			{
			$this->rover_meta_keywords		= $rover_idx_dynamic_meta->meta_keywords;
			}

		if (is_null($this->rover_canonical_url))
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'rover_idx_dynamic_meta->canonical_url ['.$rover_idx_dynamic_meta->canonical_url.'] ');
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'get_site_url() ['.get_site_url().'] ');
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, '_SERVER[REQUEST_URI] ['.$_SERVER['REQUEST_URI'].'] ');

			$this->rover_canonical_url		= (empty($rover_idx_dynamic_meta->canonical_url))
													? (get_site_url().$_SERVER['REQUEST_URI'])
													: $rover_idx_dynamic_meta->canonical_url;

			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'rover_idx_dynamic_meta->canonical_url ['.$this->rover_canonical_url.'] ');
			}

		}

	public function check_url_for_rover_keys()	{

		global								$wp, $rover_idx;

		//	Check if the requested page matches our target 

		$the_url_parts						= (empty($wp->request))
													? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
													: $wp->request;

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'url ['.$the_url_parts.'] from '.((empty($wp->request)) ? 'REQUEST_URI' : 'wp->request'));

		$url_parts							= array_filter(explode('/', $the_url_parts));
		foreach ($url_parts as $url_part)
			{
			//	So we don't serve up dynamic pages for example.com/2015/04/ma (it looks like Google likes 
			//	to crawl these, and just specifying the state takes forever with MIDFLORIDA

//			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Looking at urlpart '.$url_part);
			$url_part						= str_replace('/', '', $url_part);

			$found_slug						= $this->match_slug($url_part);
			if ($found_slug !== false)
				return $found_slug;

			$found_slug						= $this->match_region_slug($url_part);
			if ($found_slug !== false)
				return $found_slug;

			$found_slug						= $this->match_standard_page_slug($url_part);
			if ($found_slug !== false)
				return $found_slug;

			}

//		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Does not match any slugs ('.implode(', ', $rover_idx->all_slugs).')');

		return false;
		}
	
	private function match_slug($url_part)
		{
		global					$rover_idx;

		foreach ($rover_idx->all_selected_regions as $one_region => $region_slugs)
			{
			foreach (explode(',', $region_slugs) as $one_slug)
				{
				if (empty($one_slug))
					continue;

				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Comparing '.$url_part.' to '.$one_slug);
	
				if (strcasecmp($url_part, $one_slug) === 0)
					{
					rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Found slug ('.$one_slug.')');

					$this->rover_404_regions		= $one_region;
					$this->rover_404_slugs			= $one_slug;

					return $one_slug;
					}
				}
			}

		return false;
		}

	private function match_region_slug($url_part)
		{
		global					$rover_idx;

		$matched_parts					= array();
		foreach ($rover_idx->all_selected_regions as $one_region => $region_slugs)
			{
			foreach(explode(',', $url_part) as $one_segment_of_part)
				{
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Comparing '.$url_part.' to '.$one_region);
	
				if (strcasecmp($one_segment_of_part, $one_region) === 0)
					{
					rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Found slug ('.$one_region.')');
					$matched_parts[]		= $one_region;
					}
				}
			}

		if (count($matched_parts))
			{
			$this->rover_404_regions		= implode(',', $matched_parts);
			$this->rover_404_slugs			= implode(',', $matched_parts);

			return $matched_parts;
			}

		return false;
		}

	private function match_standard_page_slug($url_part)
		{
		global								$rover_idx;

		if (strlen($url_part) === 0)
			return false;

		if (substr_compare($url_part, 'rover-', 0, min(strlen('rover-'), strlen($url_part))) === 0)
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, $url_part.' may be a Rover standard slug');
			return $url_part;
			}

		$rover_slugs						= roveridx_default_slugs();

		if (isset($rover_idx->roveridx_regions['exclude_slugs']) && !empty($rover_idx->roveridx_regions['exclude_slugs']))
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Exclude slugs: ['.$rover_idx->roveridx_regions['exclude_slugs'].']');

			$rover_slugs					= array_diff($rover_slugs, explode(',', $rover_idx->roveridx_regions['exclude_slugs']));
			}

		foreach ($rover_slugs as $one_rover_slug)
			{
			if (strcmp($url_part, $one_rover_slug) === 0)
				{
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, $url_part.' may be a Rover standard slug');

				$this->rover_404_regions	= implode(',', array_keys($rover_idx->all_selected_regions));
				$this->rover_404_slugs		= implode(',', $rover_slugs);

				return $url_part;
				}
			}

		return false;
		}

	private function page_template_set($page_template)
		{
		global								$rover_idx;

		if (
			(isset($rover_idx->roveridx_theming[$page_template]))	&&
			(!empty($rover_idx->roveridx_theming[$page_template]))
			)
			return true;

		return false;
		}

	public function rover_template_include($template)
		{
		global								$rover_idx;

		$path_to_template					= array();
		$template_exists					= false;
		$html_fragment						= substr($this->rover_html, 0, 100);

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Component is '.$this->rover_component.' ['.$html_fragment.']');

		$page_template						= @$rover_idx->roveridx_theming['template'];

		if (in_array($this->rover_component, array('rover-control-panel', 'rover-custom-listing-panel')))
			{
			$path_to_template[]				= ROVER_IDX_PLUGIN_PATH . 'templates/naked_page.php';
			}
		else if ((strcmp($page_template, 'rover-naked') === 0) || (is_array($_GET) && array_key_exists('print', $_GET)))
			{
			//	User is printing page - Retrieve stripped template from Rover
			$path_to_template[]				= ROVER_IDX_PLUGIN_PATH . 'templates/naked_page.php';
			}
		else
			{
			if (strpos($html_fragment, 'rover-prop-detail-framework') !== false && $this->page_template_set('property_template'))
				{
				$path_to_template[]			= get_stylesheet_directory() . '/' . $rover_idx->roveridx_theming['property_template'];
				$path_to_template[]			= get_template_directory() . '/' . $rover_idx->roveridx_theming['property_template'];

				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Recommending template ['.$rover_idx->roveridx_theming['property_template'].']');
				}
			else if (strpos($html_fragment, 'rover-market-conditions-framework') !== false && $this->page_template_set('mc_template'))
				{
				$path_to_template[]			= get_stylesheet_directory() . '/' . $rover_idx->roveridx_theming['mc_template'];
				$path_to_template[]			= get_template_directory() . '/' . $rover_idx->roveridx_theming['mc_template'];

				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Recommending template ['.$rover_idx->roveridx_theming['mc_template'].']');
				}
			else if (strpos($html_fragment, 'rover-report-framework') !== false && $this->page_template_set('rep_template'))
				{
				$path_to_template[]			= get_stylesheet_directory() . '/' . $rover_idx->roveridx_theming['rep_template'];
				$path_to_template[]			= get_template_directory() . '/' . $rover_idx->roveridx_theming['rep_template'];

				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Recommending template ['.$rover_idx->roveridx_theming['rep_template'].']');
				}
			else if (strpos($html_fragment, 'rover-agent-framework') !== false && $this->page_template_set('agent_detail_template'))
				{
				$path_to_template[]			= get_stylesheet_directory() . '/' . $rover_idx->roveridx_theming['agent_detail_template'];
				$path_to_template[]			= get_template_directory() . '/' . $rover_idx->roveridx_theming['agent_detail_template'];

				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Recommending template ['.$rover_idx->roveridx_theming['agent_detail_template'].']');
				}
			}

		if ($path_to_template == array() && $this->page_template_set('template'))
			{
			$path_to_template[]				= get_stylesheet_directory() . '/' . $rover_idx->roveridx_theming['template'];
			$path_to_template[]				= get_template_directory() . '/' . $rover_idx->roveridx_theming['template'];
			}

		foreach($path_to_template as $one_path)
			{
			if (file_exists($one_path))
				{
				/*	Success!	*/
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Setting template to ['.$one_path.']');
				return $one_path;
				}
			}


		/*	Page templates are not set in Styling >> Quick Start.  Use default theme page template	*/

		global $wpdb;

		$path_to_template					= null;
		$path_to_template					= get_page_template();

		if (!file_exists($path_to_template))
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Default template ['.$path_to_template.'] not found.  Giving up.');
			return $template;
			}

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Using template ['.$path_to_template.']');

		if (!empty($path_to_template) && file_exists($path_to_template))
			{
			//	We don't want to go down this path every time, just because the website designer 
			//	hasn't selected a 'template' page.  So set it, and let them change it if they ever
			//	get around to it.

			$current_theme_options						= get_option(ROVER_OPTIONS_THEMING);
			$current_theme_options['theme']				= 'unused';

			update_option(ROVER_OPTIONS_THEMING, $current_theme_options );

			$rover_idx->roveridx_theming				= $current_theme_options;

			return $path_to_template;
			}
		else
			{
			if (empty($path_to_template))
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Fatal error: [path_to_template] is empty!');
			if (file_exists($path_to_template))
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Fatal error: ['.$path_to_template.'] does not exist!');
			}

		return $template;
		}

	private function redirect_if_necessary()
		{
		if ($this->rover_redirect === false)
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'rover_redirect is false ');
		else if ($this->rover_redirect === null)
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'rover_redirect is null ');
		else if (empty($this->rover_redirect))
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'rover_redirect is empty ');
		else
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'rover_redirect is ['.$this->rover_redirect.'] ');

		if ($this->rover_redirect !== false)
			{
			if (empty($this->rover_redirect))
				{
				//	This is a non-active listing page, and a crawler is the requestor.
				//	We can redirect to the Home page, or a 404 page.  Simply doing nothing
				//	will fall through to the 404 page.

				$seo_opts					= @get_option(ROVER_OPTIONS_SEO);

				if ($seo_opts['crawler_redirect'] == "404")
					{
					return true;												//	Redirect to 404 page
					}
				else if ($seo_opts['crawler_redirect'] == "home")
					{
					rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Redirecting to '.get_site_url());
//die('Redirecting to '.get_site_url());
					wp_redirect( get_site_url(), 301 );							//	Redirect to 'Home' page
					exit;
					}
				else 		//	specific
					{
					rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Redirecting to '.$seo_opts['crawler_redirect']);
//die('Redirecting to '.get_site_url());
					wp_redirect( $seo_opts['crawler_redirect'], 301 );			//	Redirect to specific page
					exit;
					}
				}
			else
				{
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Redirecting to '.$this->rover_redirect);
//die('Redirecting to '.$this->rover_redirect);

				wp_redirect( $this->rover_redirect, 301 );		//	Redirect to specified page
				exit;
				}
			}

		return false;
		}

	private function permalinks_have_been_updated( $oldvalue, $_newvalue )
		{
		$url_ends_with_slash				= ($perm && substr($_newvalue, -1) != '/')
													? false
													: true;

		$rover_idx->update_region_settings(__FUNCTION__, __LINE__, 'url_ends_with_slash', $url_ends_with_slash);
		}

	public function roveridx_meta_nofollow()	{
		echo '<meta name="robots" value="noindex,nofollow" role="roveridx">';
		}

	public function strip_title($title, $id = null) {
		return strip_tags($title);
		}

	private function translate_component($component)	{

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' ['.$component.']');

		if ($component == 'ROVER_COMPONENT_404')
			{
			//	For certain types of pages, skip the 404 engine

			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' Comparing ['.$this->rover_404_slugs.'] with [agent-detail]');
			if ($this->rover_404_slugs == 'agent-detail')
				{
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' ['.$this->rover_404_slugs.'] returning [ROVER_COMPONENT_AGENT_DETAIL_PAGE]');
				return 'ROVER_COMPONENT_AGENT_DETAIL_PAGE';
				}
			}

		return $component;
		}

	private function formatted_debug()		{

		global								$rover_idx;

		if (is_array($rover_idx->debug_html) && count($rover_idx->debug_html))
			return'<div class="rover-debug-html" style="display:none;"><div>'.implode('<br>', $rover_idx->debug_html).'</div></div>';

		return null;
		}

	private function has_quotes($att_val)	{

		$all_quotes							= array('"', "“", "”", "‘", "’", "&#8221;", "&#8243;");

		foreach($all_quotes as $one_quote)
			{
			if (strpos($att_val, $one_quote) !== false)
				return true;
			}

		return false;
		}

	private function clean_curly_quotes($atts)	{

		$new_atts							= array();
		$correcting_key						= null;
		$corrected_vals						= array();
		$all_quotes							= array('"', "“", "”", "‘", "’", "&#8221;", "&#8243;");

		foreach($atts as $att_key => $att_val)
			{
			if (!is_array($att_val))
				{
				$att_val						= urldecode($att_val);
	//			$val_contains_quote				= (!preg_match('#^[“”"‘\']#', $att_val))
	//													? true
	//													: false;
				$val_contains_quote				= $this->has_quotes($att_val);
	
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' ['.$att_key.'] => ['.$att_val.'] val_contains_quote ['.(($val_contains_quote) ? 'true' : 'false').']');
	
				if (!is_numeric($att_key) && count($corrected_vals) && $correcting_key != $att_key)	//	changed key
					{
					$new_atts[$correcting_key]	= implode(' ', $corrected_vals);
					rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' ['.$correcting_key.'] => ['.$new_atts[$correcting_key].']');
					$correcting_key				= null;
					$corrected_vals				= array();
					}
	
				//	[0] => items_per_page=48
				if (is_numeric($att_key) && strpos($att_val, "=") !== false)
					{
					$att_parts					= explode("=", $att_val);
					$new_atts[$att_parts[0]]	= str_replace($all_quotes, "", $att_parts[1]);
					rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' ['.$att_parts[0].'] => ['.str_replace(array('"', "“", "”", "‘", "’"), "", $att_parts[1]).']');
					}
				//	[street] => ‘eel
				else if (!is_numeric($att_key) && $val_contains_quote)
					{
					$correcting_key				= $att_key;
					$corrected_vals[]			= str_replace($all_quotes, "", $att_val);
					rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' ['.$correcting_key.'] => ['.implode(' ', $corrected_vals).']');
					}
				//	[0] => point"
				else if (is_numeric($att_key))
					{
					$corrected_vals[]			= str_replace($all_quotes, "", $att_val);
	
					if ($val_contains_quote)
						{
						$new_atts[$correcting_key]	= implode(' ', $corrected_vals);
						rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' ['.$correcting_key.'] => ['.$new_atts[$correcting_key].']');
						$correcting_key			= null;
						$corrected_vals			= array();
						}
					}
				else
					{
					$new_atts[$att_key]			= $att_val;
					}
				}
			//	normal
			else
				{
				$new_atts[$att_key]				= $att_val;
				}
			}

		return $new_atts;
		}

	public function rover_content($component, $atts = null)	{

		global				$rover_idx, $post;

		$page				= (isset($post)) 
									? $post->ID 
									: get_rover_post_id($rover_idx->roveridx_theming);
		$uri				= $_SERVER['REQUEST_URI'];
		$path_url			= parse_url($uri, PHP_URL_PATH);
		$query_url			= parse_url($uri, PHP_URL_QUERY);
		$api_key			= $this->get_api_key();

		$vars_array			= array(
								'is_wp'				=>	true,
								'signature'			=>	'67d14e7729d3a8446ebf5e5e97f684db',
								'cookies'			=>	$this->cookies(),
								'domain_id'			=>	$rover_idx->roveridx_regions['domain_id'],
								'domain'			=>	get_site_url(),
								'page'				=>	$page,
								'api_key'			=>	$api_key,
								'user_agent'		=>	$_SERVER['HTTP_USER_AGENT'],
								'user_ip'			=>	$_SERVER['REMOTE_ADDR'],
								'server_ip'			=>	$_SERVER['SERVER_ADDR'],
								'wp_path_url'		=>	$path_url,
								'wp_query_url'		=>	http_build_query($_REQUEST),
								'force_crawler'		=>	intval(@$_GET['crawler']),					//	'?crawler=1'
								'dynamic_sidebar'	=>	$this->use_dynamic_sidebar($component),
								'wp_permalinks'		=>	get_option('permalink_structure')
								);

		if ( is_user_logged_in() )
			{
			$current_user							=	wp_get_current_user();
			$guid									=	get_user_meta($current_user->ID, 'rover_guid', $single = true);
			if (!empty($guid))
				$vars_array['guid']					=	$guid;
			}

		if (empty($atts))
			{
			$atts									= $vars_array;
			}
		else
			{
			$atts									= $this->clean_curly_quotes($atts);
			$atts									= array_merge($atts, $vars_array);
			}

		//	If no 'region' parameter is specified in shortcode, assume the first 'region' in roveridx_regions
		if (array_key_exists('region', $atts) === false || empty($atts['region']))
			{
			$atts['region']							= implode(',', array_keys($rover_idx->all_selected_regions));

			if (!array_key_exists('region', $atts))
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, '[key does not exist] Forcing region to '.$atts['region']);
			if (empty($atts['region']))
				rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, '[empty] Forcing region to '.$atts['region']);
			}

		if (array_key_exists(ROVER_DEBUG_KEY, $_GET) === true)
			{
			$atts[ROVER_DEBUG_KEY]					= intval($_GET[ROVER_DEBUG_KEY]);
			}

		if (rover_idx_is_debuggable())
			{
			if (is_array($atts))
				{
				foreach ($atts as $atts_key => $atts_val)
					{
					if (is_string($atts_val))
						rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, $atts_key.' => '.$atts_val);
					}
				}

	//		$btd = debug_backtrace();
	//		$btd_str = null;
	//		foreach ($btd as $btdKey => $btdVal)
	//			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, '['.$btdKey.'] File: '.$btdVal['file'].' / Function: '.$btdVal['function'].' / Line: '.$btdVal['line']);
			}

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'Loading component using curl');

		if ($this->is_local_component($component))
			{
			$ret_data								= $this->local_content($component);
			}
		else
			{
			$post_str								= http_build_query($atts);

			$url									= sprintf(
															"%s%s%s%s", 
															"https://endpoint.roveridx.com/",
															ROVER_VERSION,
															'/php/rover-cross-domain-component.php?component=',
															$this->translate_component($component)
															);

			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, $url);
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, $post_str);

			if ($this->test_for_modsec($post_str))
				{
				return array(
							'the_html'	=> '<div style="color:red;margin:40px auto;text-align:center;">This request appears to be an attempt at SQL injection attacks, cross-site scripting, or a path traversal attacks.</div>'
							);
				}

			$ch										= curl_init();

			$ch_opts								= array(
															CURLOPT_URL				=> $url,
															CURLOPT_RETURNTRANSFER	=> true,
															CURLOPT_CONNECTTIMEOUT	=> 5,
															CURLOPT_TIMEOUT			=> (in_array($component, array('rover-seo-regenerate-sitemap', 'ROVER_COMPONENT_REPORT')))
																							? 120
																							: 15,
															CURLOPT_HTTPHEADER		=> array(
																							'Content-Type: application/x-www-form-urlencoded',
																							'Content-Length: '.strlen($post_str)
																							),
															CURLOPT_POST			=> true,
															CURLOPT_POSTFIELDS		=> $post_str,
															CURLOPT_FAILONERROR		=> true
															);

			if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4'))
				$ch_opts[CURLOPT_IPRESOLVE]			= CURL_IPRESOLVE_V4;

			curl_setopt_array( $ch, $ch_opts );

			$ret_data								= curl_exec($ch);
			$curl_errno								= curl_errno($ch);
			$curl_error								= curl_error($ch);

			$curl_timers							= curl_getinfo($ch);

			curl_close ($ch);

			if ($curl_errno > 0)
				{
				return $this->return_communication_error($curl_errno, $curl_error);
				}
			}

		$rover_content									= json_decode($ret_data, true);

		if (is_null($rover_content))
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'json_decode() failed on ['.$ret_data.']');
			}
		else
			{
			$this->rover_og_images						= null;

			$rover_content['the_html']					= str_replace('ROVER_DYNAMIC_SIDEBAR', $this->dynamic_sidebar, $rover_content['the_html']);

			if (isset($rover_content['the_og_images']) && !empty($rover_content['the_og_images']))
				{
				$this->rover_og_images					= $rover_content['the_og_images'];

//				$this->roveridx_use_our_og_images();
				}

			foreach($curl_timers as $curl_key => $curl_val)
				{
#				if (strpos($curl_key, '_time') !== false)
					{
					if (is_string($curl_val))
						rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'curl_timers ['.$curl_key.'] => ['.$curl_val.'] seconds');
					}
				}
				
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'the_html is '.strlen($rover_content['the_html']).' bytes');
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'the_og_images are '.strlen($rover_content['the_og_images']).' bytes');

			$this->check_js_version($rover_content['the_js_ver']);
			}

		return $rover_content;
		}

	private function cookies()
		{
		$the_cookies						= array();

		if (isset($_COOKIE) && is_array($_COOKIE) && count($_COOKIE))
			{
			$rover_cookie_key				= 'rover_';
			$len							= strlen($rover_cookie_key);
			foreach ($_COOKIE as $key => $value)
				{
				$sub						= substr($key, 0, $len);
				if (strcasecmp($rover_cookie_key, $sub) === 0)
					{
					$the_cookies[]			= $key.'='.urlencode($value);
					}
				}
			}

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, ' is '.implode(';', $the_cookies));

		return implode(';', $the_cookies);
		}

	private function return_communication_error($curl_errno, $curl_error)	{

		rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'curl_exec error ['.$curl_errno.']: '.$curl_error);

		$err_string							= null;
		if ($curl_errno === 1)				/*	CURLE_UNSUPPORTED_PROTOCOL	*/
			$err_string						= $curl_error.'<br><br>The url protocol is unsupported.';
		else if ($curl_errno === 5)			/*	CURLE_COULDNT_RESOLVE_PROXY	*/
			$err_string						= $curl_error.'<br><br>Could not resolve proxy.';
		else if ($curl_errno === 6)			/*	CURLE_COULDNT_RESOLVE_HOST	*/
			$err_string						= $curl_error.'<br><br>Could not resolve host.';
		else if ($curl_errno === 28)		/*	CURLE_OPERATION_TIMEDOUT	- this is most likely CURLOPT_CONNECTTIMEOUT	*/
			$err_string						= $curl_error.'<br><br>This could be caused by a firewall, blocked port (443) or a network issue.';
		else
			$err_string						= $curl_error;

		return array(
					'the_html'	=> '<div style="color:red;margin:40px auto;text-align:center;"><i class="fa fa-bolt fa-2x" aria-hidden="true"></i>
 Unable to communicate with server.  '.$err_string.'<br><br></div>'
					);
		}

	private function is_local_component($component)		{

		if (in_array(
					$component, 
					array(
						'rover-debug-page'
						)
					))
			{
			return true;		//	Force 'remote' for WP Plugin setup panels
			}
	
		return false;
		}

	private function local_content($component)		{

		global						$rover_idx;

		$the_html					= array();
		$the_title					= null;
		$the_meta_desc				= null;

		switch($component)
			{
			case 'rover-debug-page':
				$the_title			= 'Rover Debug Page';

				$the_html[]			= '<h3>Region settings</h3>';		
				foreach($rover_idx->roveridx_regions as $key => $val)
					$the_html[]		= '['.$key.'] => ['.$val.']';		

				$the_html[]			= '<h3>Theme settings</h3>';		
				foreach($rover_idx->roveridx_theming as $key => $val)
					$the_html[]		= '['.$key.'] => ['.$val.']';

				$the_html[]			= '<h3>Regions</h3>';		
				foreach($rover_idx->all_selected_regions as $key => $val)
					$the_html[]		= '['.$key.'] => ['.$val.']';

				$curr_theme			= wp_get_theme();
				$the_html[]			= '<h3>Templates for ['.$curr_theme->get('Name').'] ['.$curr_theme->get('ThemeURI').'] ['.$curr_theme->get('Version').']</h3>';		
				foreach($curr_theme->get_page_templates() as $key => $val)
					$the_html[]		= '['.$key.'] => ['.$val.']';
				break;
			}
	
		return json_encode( array(
					'the_html'		=> '<div style="margin:100px 20%;">'.implode('<br>', $the_html).'</div>',
					'the_title'		=> $the_title,
					'the_meta_desc'	=> $the_meta_desc
					));
		}

	private function is_rover_admin_panel($component)	{
	
		if (in_array(
					$component, 
					array(
						'ROVER_COMPONENT_SETUP_GENERAL_PANEL',
						'ROVER_COMPONENT_WP_SETUP_PANEL',
						'ROVER_COMPONENT_WP_SEARCH_PANEL',
						'ROVER_COMPONENT_WP_ENGINE_PANEL',
						'ROVER_COMPONENT_WP_SOCIAL_PANEL',
						'ROVER_COMPONENT_WP_SEO_PANEL',
						'ROVER_COMPONENT_WP_MOBILE_PANEL',
						'ROVER_COMPONENT_EMAIL_TEMPLATES'
						)
					))
			{
			return true;		//	Force 'remote' for WP Plugin setup panels
			}
	
		return false;
		}

	private function test_for_modsec($post_str)
		{
		/*	Test for modsec	*/

		$pattern	= "(insert[[:space:]]+into.+values|select.*from.+[a-z|A-Z|0-9]|select.+from|bulk[[:space:]]+insert|union.+select|convert.+\\\\(.*from))";

		if (preg_match($pattern, $post_str) == 1)
			{
			rover_idx_error_log(__FILE__, __FUNCTION__, __LINE__, 'security alert!');
			return true;

			wp_mail("info@roveridx.com", 
					get_site_url().': post_str will trigger modsec', 
					$post_str);
			}

		return false;
		}
	}

global $rover_idx_content;
$rover_idx_content = new Rover_IDX_Content();

?>