=== Controlled Admin Access ===
Contributors: waseem_senjer, wprubyplugins
Donate link: https://wpruby.com
Tags: access, Access Manager, admin, capability, page, Post, role, user, widget
Requires at least: 4.0
Tested up to: 5.5
Stable tag: 1.3.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Give a temporarily limited admin. access to themes designers, plugins developers and support agents.

== Description ==

Give a temporary limited admin. access to themes designers, plugins developers and support agents.

The plugin is simple and clean, it helps the administrator to create a user with a temporary access and choose which pages in your admin area which you don't want the user to access. send the details to the user and when he finished his task, you can easily deactivate the account and activate it later.


== Installation ==

1. Upload `controlled-admin-access` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. In the dashboard, click on `Users` then `Controlled Admin Access`

== Screenshots ==

1. Add User Page
2. Manage Users Page
3. Edit User Page

== Changelog ==
= 1.3.9 =
* FIXED: If the user does not have access to the Dashboard page, he will be redirected to his profile.
* FIXED: deleting empty CSS and JS files.

= 1.3.8 =
* FIXED: Users with unexpired login were able to login again even after deactivation.

= 1.3.7 =
* FIXED: The Your Profile access  was not saved correctly.

= 1.3.6 =
* FIXED: Redirect to the first accessible page. 404 not found pages were fixed.

= 1.3.5 =
* FIXED: ignore menu items of the customizer to avoid menu duplication.

= 1.3.4 =
* FIXED: empty user password should not be processed when user is being updated

= 1.3.3 =
* FIXED: make activate link green only for the plugin users table

= 1.3.2 =
* FIXED: remove warning message for defining scalar constants for php less than 5.6

= 1.3.1 =
* FIXED: Users were prevented from accessing post type pages.

= 1.3.0 =
* FIXED: Restrict users from editing any other user.

= 1.2.0 =
* ADDED: Redirect the user to the first accessible page after login

= 1.1.1 =
* FIXED: Add backward compatibility for PHP 5.x

= 1.1.0 =
* ADDED: Allow the access to the Plugins page.

= 1.0.6 =
* ADDED: Spanish language translations.

= 1.0.5 =
* FIXED: Users could access the Plugins page by typing the URL in the browser.

= 1.0.4 =
* FIXED: Deactivating users bug.
* FIXED: PHP warning when slecting user roles.

= 1.0.3 = 
* FIXED: Users and Plugins plugins prevent access when the user has been edited.
ADDED: Our Blog posts widget.

= 1.0.2 =
* FIXED: Users and Plugins prevent access.
* FIXED: Javascript conflict. 
* FIXED: Display the menu items title.


= 1.0.0 =
* Initial Release
