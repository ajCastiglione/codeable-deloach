<div class="wrap">
<?php include plugin_dir_path(__FILE__).'menu.php'; ?>

	<div id="caa_about_page">
		<?php if(!empty($plugins)): ?>		
				<div class="unit">		
					<table id="caa_more_plugins" class="wp-list-table widefat">		
						<thead>		
							<tr>		
								<th colspan="2"><?php _e('More Plugins','controlled-admin-access'); ?></th>		
							</tr>		
						</thead>		
						<?php foreach($plugins as $plugin): ?>		
							  <tr>		
							    <td class="plugin_icon" rowspan="2"><img width="70" src="<?php echo $plugin->icon; ?>"></td>		
							    <td class="plugin_title"><a target="_blank" href="<?php echo $plugin->url; ?>"><?php echo $plugin->name; ?></a></td>		
							  </tr>		
							  <tr>		
							    <td class="plugin_price">		
							    	<p class="description"><?php echo $plugin->desc; ?></p>
							    </td>		
							  </tr>		
						<?php endforeach; ?>		
					</table>	
				</div>		
		<?php endif; ?>

		<div class="unit">
			<table class="wp-list-table widefat">
						<thead>
							<tr>
								<th><?php _e('Help and Support', 'controlled-admin-access'); ?></th>
							</tr>
						</thead>	
						<tr>
							<td>
								<p>
								<?php printf( __( 'If you encountered any problem or if you have a feature in mind that you want me to implement in the next release. Please do not hesitate %1$s submitting a support ticket %2$s', 'controlled-admin-access'),'<a target="_blank" href="https://wpruby.com/submit-ticket/">','</a>'); ?>
								</p>
							</td>
						</tr>
			</table>			
		</div>
		<div class="unit">
				<table class="wp-list-table widefat" >
					<thead>
						<tr>
							<th colspan="2"><?php _e('WordPress Environment','controlled-admin-access'); ?></th>
						</tr>
					</thead>
					<tr>
						<td><?php _e('Plugin Developer','controlled-admin-access'); ?>:</td>
						<td>WPRuby</td>
					</tr>
					<tr>
						<td><?php _e('Plugin Website','controlled-admin-access'); ?>:</td>
						<td><a target="_blank" href="https://wpruby.com">https://wpruby.com</a></td>
					</tr>
					<tr>
						<td><?php _e('Plugin version','controlled-admin-access'); ?>:</td>
						<td><?php echo CAA_VERSION; ?></td>
					</tr>
					<tr>
						<td><?php _e('PHP version','controlled-admin-access'); ?>:</td>
						<td><?php echo phpversion(); ?></td>
					</tr>
					<tr>
						<td><?php _e('WordPress version','controlled-admin-access'); ?>:</td>
						<td><?php bloginfo('version'); ?></td>	
					</tr>
					<tr>
						<td><?php _e('Language','controlled-admin-access'); ?>:</td>
						<td><?php bloginfo('language'); ?></td>
					</tr>

				</table>
		</div>

			<div class="unit">
					<table class="wp-list-table widefat">
						<thead>
							<tr>
								<th><?php _e('From Our Blog', 'controlled-admin-access'); ?></th>
							</tr>
						</thead>	
						<tr>
							<td>
							<div class="rss-widget">
								<?php
									wp_widget_rss_output(array(
											'url' => 'https://wpruby.com/feed/',
											'title' => 'More Plugins',
											'items' => 3,
											'show_summary' => 0,
											'show_author' => 0,
											'show_date' => 1,
									));
								?>	
								</div>	
							</td>
						</tr>
					</table>			
			</div>


	</div>
</div>
