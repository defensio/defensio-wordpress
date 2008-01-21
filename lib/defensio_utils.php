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

// Does not require twice a file, if it has the name 
// of an already required/included file
function defensio_require_once_by_name($filename){
    $included_names = array_map(create_function('$full_name', 'return basename($full_name);'),
        array_merge(get_included_files(),get_required_files()));
    // If a file with this name exists return true as require
    // once does http://ca.php.net/manual/en/function.require-once.php
    if(in_array(basename($filename), $included_names)){
        return true;
    }else{
        require($filename);
    }
}



$site_uri = get_option('siteurl');

$df_utils_file = __FILE__;

$plugin_name = "defensio-anti-spam";
$plugin_path = substr(dirname($df_utils_file ), 0, strlen(dirname($df_utils_file))-3);
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
