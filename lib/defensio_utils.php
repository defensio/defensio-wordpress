<?php
// Load environment if it is an AJAX call
if (defined('DEFENSIO_AJAX')) {
	// we have to chdir in order for admin.php to be able to require its own files.
	$old_dir = getcwd();
	chdir('../../../wp-admin/');
	require_once('admin.php');
	require_once('admin-functions.php');
	chdir($old_dir);
}

$site_uri = get_option('siteurl');

$file = __FILE__;

$plugin_name = "defensio-anti-spam";
$plugin_path = substr(dirname($file), 0, strlen(dirname($file))-3);
$plugin_uri = get_option('siteurl') . "/wp-content/plugins/$plugin_name/";

function is_mu() {
	if (function_exists("is_site_admin")) {
		return true;
	} else {
		return false;
	}
}

function is_wp_version_supported() {
	global $wp_version;
	if (is_mu()) {
		return ($wp_version >= 1.1);
	} else {
		return ($wp_version >= 2.1);
	}
}
?>
