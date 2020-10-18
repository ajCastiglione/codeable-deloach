<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://wpruby.com
 * @since      1.0.0
 *
 * @package    Controlled_Admin_Access
 * @subpackage Controlled_Admin_Access/admin/partials
 */
?>
<div class="wrap">
<?php 
if(!isset($_GET['action'])){
	include plugin_dir_path(__FILE__).'menu.php';
}
?>
	<form id="events-filter" method="get" action="">
		<input type="hidden" name="noheader" value="true" />
	    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
	    <?php $testListTable->display() ?>
	</form>
</div>