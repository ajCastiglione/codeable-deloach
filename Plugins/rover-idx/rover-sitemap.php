<?php

require_once 'rover-common.php';
define('SITEMAP_DIR',		"/rover_idx_sitemap");

class Rover_IDX_SITEMAP {

	private	$sitemap_opts					= null;
	private	$sitemap_file					= null;
	private	$upload_dir						= null;
	private	$upload_url						= null;

	private	$debug_log						= null;

	function __construct()	{

		$this->debug_log					= array();

		}

	public function build($force_refresh)
		{
		global								$rover_idx;

		set_time_limit(60);					//	60 = 1 minutes / 600 = 10 minutes / 1200 = 20 minutes

		$this->sitemap_opts					= get_option(ROVER_OPTIONS_SEO);

		$this->log(__FUNCTION__, __LINE__, 'Starting...');

		if ($this->sitemap_is_disabled())
				{
				$this->log(__FUNCTION__, __LINE__, 'Sitemap refresh is disabled');
				return array(
							'success'	=> false,
							'blah'		=> 1,
							'log'		=> implode("<br>", $this->debug_log),
							);
				}

		foreach ($rover_idx->all_selected_regions as $one_region => $region_slugs)
			{
			$successful_notifications		= array();
			$this->sitemap_file				= "rover_sitemap_".$one_region.".xml";

			if ($force_refresh || $this->should_we_build_new_sitemap($one_region))
				{
				if (($result_decoded = $this->fetch_sitemap_data($one_region)) === false)
					return;

				$this->log(__FUNCTION__, __LINE__, 'sitemap = '.$this->sitemap_file	);

				$this->log(__FUNCTION__, __LINE__, 'Output of json_decode() has '.count($result_decoded).' items');
				$this->log(__FUNCTION__, __LINE__, 'Output keys = '.implode(',', array_keys($result_decoded)));

				$bytesWritten				= 0;
				$wp_upload_dir 				= wp_upload_dir();									//	path to upload directory
				$this->upload_dir 			= (empty($wp_upload_dir['basedir']))
													? dirname(__FILE__)							//	We only end up here if wp_upload_dir() failed (unlikely)
													: $wp_upload_dir['basedir'].SITEMAP_DIR;

				$this->upload_url			= (empty($wp_upload_dir['baseurl']))
													? dirname(__FILE__)
													: $wp_upload_dir['baseurl'].SITEMAP_DIR;


				$this->log(__FUNCTION__, __LINE__, 'count			= '.$result_decoded['count']);
				$this->log(__FUNCTION__, __LINE__, 'path will be	= '.$this->upload_dir.'/'.$this->sitemap_file	);
				$this->log(__FUNCTION__, __LINE__, 'url will be		= '.$this->upload_url.'/'.$this->sitemap_file	);

				/*
					Create the sitemap file
				*/

				$sitemap_url_gz				= $this->sitemap_file_write($result_decoded);

				$search_engines				= array(
													'Google'	=> 'http://www.google.com/webmasters/tools/ping?sitemap=',
													'Bing'		=> 'http://www.bing.com/webmaster/ping.aspx?siteMap=',
													'Yahoo'		=> 'http://search.yahooapis.com/SiteExplorerService/V1/ping?sitemap=',	//	Yahoo has merged with Bing ??
													'Ask'		=> 'http://submissions.ask.com/ping?sitemap='
													);
			
				/*
					http://freds_real_estate.com/wp-content/uploads/rover_idx_sitemap/rover_sitemap_DESM.xml.gz
				*/

				$successful_notifications	= array();
				foreach ($search_engines as $search_engine_name => $search_engine_submission_url)
					{
					$ping_url				= $search_engine_submission_url.$sitemap_url_gz;

					if ($this->notify_search_engine($ping_url, $search_engine_name))
						{
						$successful_notifications[] = $search_engine_name;

						$this->log(__FUNCTION__, __LINE__, $sitemap_url_gz.' submitted to '.$search_engine_name);
						}
					else
						{
						$this->log(__FUNCTION__, __LINE__, 'Attempt to ping '.$search_engine_name.' using '.$ping_url.' has failed');
						}
					}

				if (function_exists('wp_mail'))
					{
//						wp_mail('info@roveridx.com', 
//								get_site_url().': RoverIDX has refreshed sitemap '.$this->sitemap_file, 
//								'Sitemap '.$this->upload_url.'/'.$this->sitemap_file.' refreshed on '.date('Y-m-d H:i:s').' ('.number_format($bytesWritten).' bytes written)<br><br>'.
//								'Successfully notified '.count($successful_notifications).' search engines ('.implode(',', $successful_notifications).')<br><br>');

					$this->sitemap_opts[$one_region]['url']		= esc_url( $this->upload_url.'/'.$this->sitemap_file.'.gz' );
					}		

				//	'desc' was set, above

				$this->sitemap_opts[$one_region]['timestamp']	= date('M d Y H:i:s');
				$this->sitemap_opts[$one_region]['desc']		= number_format($result_decoded['count']).' properties';

				update_option(ROVER_OPTIONS_SEO, $this->sitemap_opts);
				}
			}

		return array(
					'success'	=> ((count($successful_notifications)) ? true : false),
					'blah'		=> 1,
					'log'		=> implode("<br>", $this->debug_log),
					);
		}

	private function sitemap_is_disabled()
		{
		if (is_array($this->sitemap_opts) && array_key_exists('disabled', $this->sitemap_opts))
			{
			if ($this->sitemap_opts['disabled'] == true)	//	'Disable Sitemap' is true in SEO Panel
				{
				$this->log(__FUNCTION__, __LINE__, 'Sitemap refresh is disabled');
				return true;
				}
			}

		return false;
		}

	private function should_we_build_new_sitemap($one_region)
		{
		$build_it	= true;
	
		if (is_array($this->sitemap_opts) && 
			array_key_exists($one_region, $this->sitemap_opts) &&
			array_key_exists('timestamp', $this->sitemap_opts[$one_region]))
			{
			$last_successful_date	= strtotime($this->sitemap_opts[$one_region]['timestamp']);
	
			if (date('Y') == date('Y', $last_successful_date)	&& 
				date('m') == date('m', $last_successful_date)	&&
				date('d') == date('d', $last_successful_date))
				{
				$this->log(__FUNCTION__, __LINE__, 'We already built a '.$one_region.' sitemap today (on '.$this->sitemap_opts[$one_region]['timestamp'].')');
				$build_it	= false;
				}
			else
				{
				$this->log(__FUNCTION__, __LINE__, 'timestamp '.$this->sitemap_opts[$one_region]['timestamp'].' is not today.  We will build a sitemap');
				}
			}
		else
			{
			if (is_array($this->sitemap_opts))
				{
				if (isset($this->sitemap_opts[$one_region]) && array_key_exists('timestamp', $this->sitemap_opts[$one_region]))
					$this->log(__FUNCTION__, __LINE__, '"timestamp" is not a key in sitemaps_opts['.$one_region.'] - we will build sitemap');
				else if (array_key_exists($one_region, $this->sitemap_opts))
					$this->log(__FUNCTION__, __LINE__, $one_region.' is not a key in sitemaps_opts - we will build sitemap');
				}
			else
				{
				$this->log(__FUNCTION__, __LINE__, 'sitemaps_opts is not an array - we will build sitemap');
				}
			}
	
		return $build_it;
		}

	private function fetch_sitemap_data($one_region)
		{
		global								$rover_idx, $post;

		require_once ROVER_IDX_PLUGIN_PATH.'rover-content.php';

		global								$rover_idx_content;

		$rover_content						= $rover_idx_content->rover_content(
																				'rover-seo-regenerate-sitemap', 
																				array(
																					'region'			=> $one_region, 
																					'regions'			=> implode(',', array($one_region)),
																					'sitemap_domain'	=> get_site_url()
																					)
																				);

		$result_decoded						= json_decode($rover_content['the_html'], true);		

		if ($result_decoded === null)
			{
			$this->log(__FUNCTION__, __LINE__, 'xml did not decode correctly');
			$this->log(__FUNCTION__, __LINE__, $result);
			return false;
			}

		if (!is_array($result_decoded))
			{
			$this->log(__FUNCTION__, __LINE__, 'Output is not an array - aborting sitemap creation');
			return false;
			}

		return $result_decoded;
		}

	private function sitemap_file_write($result_decoded)
		{		
		if (!is_dir( $this->upload_dir ))
			mkdir( $this->upload_dir, 0755, true );

		$sitemap_path						= $this->upload_dir."/".$this->sitemap_file;

		$sitemap_path_gz					= $this->upload_dir."/".$this->sitemap_file.".gz";
		$sitemap_url_gz						= $this->upload_url.'/'.$this->sitemap_file.'.gz';

		$this->copy_file_to_local($result_decoded['sitemap_url'], $sitemap_path);

		$this->copy_file_to_local($result_decoded['sitemap_gz_url'], $sitemap_path_gz);

		return $sitemap_url_gz;
		}	

	function copy_file_to_local($dest_url, $local_file)
		{
		$fp									= fopen ($local_file, 'w+');
		//Here is the file we are downloading, replace spaces with %20
		$ch									= curl_init($dest_url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 50);

		curl_setopt($ch, CURLOPT_FILE, $fp); 
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		curl_exec($ch); 
		curl_close($ch);

		fclose($fp);

		$this->log(__FUNCTION__, __LINE__, 'Copied ['.$dest_url.'] to ['.$local_file.'] [<span style="color:green;">'.number_format(filesize($local_file)).' bytes</span>]');
		}

	function notify_search_engine($sitemap_url) {
	
		$curl_handle = curl_init();
		curl_setopt($curl_handle,CURLOPT_URL,$sitemap_url);
		curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,2);
		curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
		$buffer = curl_exec($curl_handle);
		curl_close($curl_handle);
	
		if (empty($buffer))
			return false;
		
		return true;
		}

	private function log($func, $line, $str)	{

		$this->debug_log[]	= sprintf(
									'%1$s <b>%2$s</b> %3$s: %4$s', 
									basename(__FILE__),
									$func, 
									$line,
									$str
									);
		}
	}

function roveridx_refresh_sitemap($force_refresh = false) {


	$roverSITEMAP		= new Rover_IDX_SITEMAP();

	return $roverSITEMAP->build($force_refresh);

	}



?>