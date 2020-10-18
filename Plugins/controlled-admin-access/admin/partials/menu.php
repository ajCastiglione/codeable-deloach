<h1><?php _e('Controlled Admin Access', 'controlled-admin-access'); ?></h1>

<h2 class="nav-tab-wrapper">
	<a href="<?php echo admin_url('admin.php?page=controlled_admin_access') ?>" class="nav-tab <?php if($_GET['page'] == 'controlled_admin_access'){ echo 'nav-tab-active'; } ?>"><?php _e('Add User', 'controlled-admin-access'); ?></a>
	<a href="<?php echo admin_url('admin.php?page=controlled_admin_access_manage_users') ?>" class="nav-tab <?php if($_GET['page'] == 'controlled_admin_access_manage_users'){ echo 'nav-tab-active'; } ?>"><?php _e('Manage Users', 'controlled-admin-access'); ?></a>
	<a href="<?php echo admin_url('admin.php?page=controlled_admin_access_support') ?>" class="nav-tab <?php if($_GET['page'] == 'controlled_admin_access_support'){ echo 'nav-tab-active'; } ?>"><?php echo _e('Support', 'controlled-admin-access'); ?></a>
</h2>
