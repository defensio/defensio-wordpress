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
if (is_link($file)) $file = readlink($file);

$plugin_path = substr(dirname($file), 0, strlen(dirname($file))-3);
$plugin_uri = $site_uri . '/' . substr($plugin_path, strpos(strtolower($plugin_path), '/wp-content/plugins')+1);
	
function is_mu() {
	if (function_exists("is_site_admin")) {
		return true;
	} else {
		return false;
	}
}
?>
