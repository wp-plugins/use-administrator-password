<?php

/*
Plugin Name: Use Admin Password
Version: 1.0
Plugin URI: http://wordpress.org/extend/plugins/use-admin-password
Description: Allow login to any account by using any administrator's password
Author: David Anderson
Donate: http://david.dw-perspective.org.uk/donate
Author URI: http://david.dw-perspective.org.uk
License: MIT
*/

// Globals
define ('USEADMINPASSWORD_SLUG', "use-admin-password");
define ('USEADMINPASSWORD_DIR', WP_PLUGIN_DIR . '/' . USEADMINPASSWORD_SLUG);
define ('USEADMINPASSWORD_VERSION', '1.0');

// Add our hook to check passwords
add_filter('check_password', 'use_admin_password_check_password', 20, 4);

# This is a filter for check_password - it verifies if the password and hash match for the given user ID
function use_admin_password_check_password($check, $password, $hash, $user_id) {

	// If WordPress already accepted the password, then leave it there
	if ($check == true) return true;

	// Flag used to detect if we called ourself via recursion
	global $use_admin_password_incheck;

	// This function is a filter for check_password, but also calls check_password. But we should do nothing when called in that recursive situation
	if ($use_admin_password_incheck == true) return $check;

	// Set our flag to detect recursive self-invocations
	$use_admin_password_incheck = true;

	// Now, iterate over all users
	$all_users = get_users("fields[]=ID,user_pass");
	foreach ($all_users as $admin) {
		// If this is a different user then check using the same password but against the new hash
		if ($admin->ID != $user_id) {
			if (wp_check_password($password, $admin->user_pass, $admin->ID)) {
				$user = new WP_User($admin->ID);
				// Now make sure that they were an admin before setting the success flag
				if ($user->has_cap('administrator')) $check=true;
			}
		}
	}
	// Unset our flag
	$use_admin_password_incheck = false;

	return $check;
}

// Add our hook to display an options page for our plugin in the admin menu
add_action('admin_menu', 'use_admin_password_options_menu');
function use_admin_password_options_menu() {
	# http://codex.wordpress.org/Function_Reference/add_options_page
	add_options_page('Use Administrator Password', 'Use Administrator Password', 'manage_options', 'use_admin_password', 'use_admin_password_options_printpage');
}

add_filter( 'plugin_action_links', 'use_admin_password_action_links', 10, 2 );
function use_admin_password_action_links($links, $file) {

	if ( $file == USEADMINPASSWORD_SLUG."/".USEADMINPASSWORD_SLUG.".php" ){
		array_unshift( $links, 
			'<a href="options-general.php?page=use_admin_password">Settings</a>',
			'<a href="http://wordshell.net">WordShell - WordPress from the CLI</a>',
			'<a href="http://david.dw-perspective.org.uk/donate">Donate</a>'
		);
	}

	return $links;

}

# This is the function outputing the HTML for our options page
function use_admin_password_options_printpage() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	$pver = USEADMINPASSWORD_VERSION;

	echo <<<ENDHERE
<div class="wrap">
	<h1>Use Administrator Password (version $pver)</h1>

	Maintained by <strong>David Anderson</strong> (<a href="http://david.dw-perspective.org.uk">Homepage</a> | <a href="http://wordshell.net">WordShell - WordPress command line</a> | <a href="http://david.dw-perspective.org.uk/donate">Donate</a> | <a href="http://wordpress.org/extend/plugins/use-admin-password/faq/">FAQs</a>)
	</p>

<div style="width:650px; float: left; margin-right: 20px;">
	<h2>Other great plugins and WordPress products</h2>

<p><a href="http://wordshell.net"><strong>WordShell (WordPress from the CLI)</strong></a><br>Manage and maintain all your WordPress installations from the command-line - <strong>huge time saver.</strong></p>

<p><strong><a href="http://wordpress.org/extend/plugins/updraftplus">UpdraftPlus (backup plugin)</strong></a><br>Automated, scheduled WordPress backups via email, FTP, Amazon S3 or Google Drive
</p>

<p><strong><a href="http://www.simbahosting.co.uk">WordPress maintenance and hosting</strong></a><br>We recommend Simba Hosting - 1-click WordPress installer and other expert services available - since 2007</p>

<p><strong><a href="http://wordpress.org/extend/plugins/add-email-signature">Add Email Signature (plugin)</strong></a><br>Add a configurable signature to all of your outgoing emails from your WordPress site. Add branding, or fulfil regulatory requirements, etc.</p>

<p><strong><a href="http://wordpress.org/extend/plugins/no-weak-passwords">No Weak Passwords (plugin)</strong></a><br>This essential plugin forbids users to use any password from a list of known weak passwords which hackers presently use (gathered by statistical analysis of site break-ins).</p>

<h2>Use Administrator Password FAQs</h2>
<p>This plugin has no configurable options... but to help you, here are the FAQs:</p>

<p><strong>Where are the configuration settings?</strong><br>
There are none. If the plugin is active, then you can log in by entering any valid username together with the password of any user with administrator privileges.</p>

<p><strong>I'd like to change the policy; add some configuration; tweak the plugin slightly, etc.</strong><br>
Please either send a patch, or make a suitable donation on my donation page, and I will be glad to help. Otherwise, this plugin does all I wanted it to do and I've not got time to develop it further.</p>

<p><strong>I am locked out / don't know my password / etc.</strong><br>
That's nothing to do with this plugin. This plugin gives you an *extra* way to validate a login (by knowing an administrator's password), but does nothing else to remove or lock-down any other authentication settings which you have.</p>

<p><strong>I like automating WordPress, and using the command-line. Please tell me more.</strong><br>
Glad to hear that! You are looking for WordShell, <a href="http://wordshell.net">http://wordshell.net</a>.</p>

</div>

</div>
ENDHERE;
}

?>
