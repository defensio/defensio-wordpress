<?php
/*
  Plugin Name: Defensio Anti-Spam
  Plugin URI: http://defensio.com/
  Description: Defensio is an advanced spam filtering web service that learns and adapts to your behaviors and those of your readers.  To use this plugin, you need to obtain a <a href="http://defensio.com/signup">free API Key</a>.  Tell the world how many spam Defensio caught!  Just put <code>&lt;?php defensio_counter(); ?></code> in your template.
  Version: 1.5.2
  Author: Karabunga, Inc
  Author URI: http://karabunga.com/
 */

include_once('lib/spyc.php');
include_once('lib/defensio_configuration.php');
include_once('lib/defensio_quarantine.php');
include_once('lib/defensio_head.php');
include_once('lib/defensio_counter.php');
include_once('lib/defensio_moderation.php');
include_once('lib/defensio_utils.php');

$defensio_conf = array(
	'server'       => 'api.defensio.com',
	'path'         => 'blog',
	'api-version'  => '1.2',
	'format'       => 'yaml',
	'blog'         => get_option('home'),
	'post_timeout' => 10
);

/* If you want to hard code the key for some reason, uncomment the following line and replace 1234567890 with your key. */
// $defensio_conf['key'] = '1234567890'; 

/* Define trusted roles here.  Only change these if you have custom roles (and you know what you're doing). */
$defensio_trusted_roles = array('administrator', 'editor', 'author');

/* acts_as_master forces the Defensio spam result to be retained in the event other anti-spam plugins are installed.
   Setting it to 'false' could have drastic negative effects on accuracy, so please leave it to true unless you 
   know what you are doing. In other words, set it to 'false' at your own risk. */ 
$acts_as_master = true;

/*-----------------------------------------------------------------------------------------------------------------------
  DO NOT EDIT PAST THIS
-----------------------------------------------------------------------------------------------------------------------*/
define('DF_SUCCESS', 'success');
define('DF_FAIL', 'fail');

if (!function_exists('wp_nonce_field') ) {
	function defensio_nonce_field($action = -1) { return; }
	$defensio_conf['nonce'] = -1;
} else {
	function defensio_nonce_field($action = -1) { return wp_nonce_field($action); }
	$defensio_conf['nonce'] = 'defensio-update-key';  
}

// Temporary stores Defensio's metadata, spaminess and signature
$defensio_meta = array();

// Initialize arrays for deferred training
$deferred_spam_to_ham = array();  
$deferred_ham_to_spam = array();
$defensio_retraining  = false;

// Installation function, creates defensio table
function defensio_install() {
	global $wp_version;

	// Create table and set default options
	defensio_create_table();
	add_option(defensio_user_unique_option_key('threshold') , '80');
	add_option(defensio_user_unique_option_key('hide_more_than_threshold'), '1');
	add_option('defensio_delete_older_than_days', '30');
	add_option('defensio_delete_older_than', '0');
}
register_activation_hook(__FILE__, 'defensio_install');


function defensio_create_table() {
	global $wpdb;

	$table_name = $wpdb->prefix . "defensio";
	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		$sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (comment_ID mediumint(9) NOT NULL, spaminess DECIMAL(5,4) NOT NULL, signature VARCHAR(55) NOT NULL, UNIQUE KEY comment_ID (comment_ID));";

		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		dbDelta($sql);
		$wpdb->query($sql);
	}
}

// Init hook
function defensio_init() {
	global $defensio_conf;
	add_action('admin_menu', 'defensio_config_page');

	if (isset ($defensio_conf['key'])) {
		$defensio_conf['hckey'] = true;
	} else {
		$defensio_conf['key'] = trim(get_option('defensio_key'));
	}

	// In case the table is deleted create it again
	defensio_create_table();  
}
add_action('init', 'defensio_init');


function defensio_key_not_set_warning() {
	global $defensio_conf, $wp_version;
	
	if (!isset($defensio_conf['key']) or empty($defensio_conf['key'])) {
		defensio_render_warning_styles();
		echo "<div id='defensio_warning' class='updated fade-ff0000'>" .
		"<p>Defensio is not active. You must enter your Defensio API key for it to work.</p></div>";
	}
	return; 
}
add_action('admin_footer', 'defensio_key_not_set_warning');


function defensio_unprocessed_warning() {
	$unprocessed = defensio_get_unprocessed_comments();
	if (count($unprocessed) > 0) {
		defensio_render_unprocessed_in_moderation($unprocessed);
	}
}	
add_action('admin_footer', 'defensio_unprocessed_warning');


function defensio_collect_signatures($s) {
	$signatures = '';	   
	$i = 0;
	foreach($s as $signature){
		if ($i < count($s) -1 ) { 
			$signatures .= $signature. ','; 
		}
		else { 
			$signatures .= $signature; 
		}
		$i++;
	}
	return $signatures;
}

// Shutdown hook
function defensio_finalize() {
	global $deferred_ham_to_spam, $deferred_spam_to_ham ;

	// Train comments scheduled to be trained
	if (!empty($deferred_ham_to_spam)) {
		defensio_submit_spam(defensio_collect_signatures($deferred_ham_to_spam));
	}

	if (!empty($deferred_spam_to_ham)) {
		$signatures = '';
		defensio_submit_ham(defensio_collect_signatures($deferred_spam_to_ham));
	}
}
add_action('shutdown', 'defensio_finalize');


function defensio_config_page() {
	global $defensio_conf;

	if (function_exists('add_submenu_page')) {
		add_submenu_page('plugins.php', __('Defensio Configuration'), __('Defensio Configuration'), 'manage_options', 'defensio-config', 'defensio_configuration');
		add_submenu_page('options-general.php', __('Defensio Configuration'), __('Defensio'), 'manage_options', 'defensio-config', 'defensio_configuration');
	}
}

function defensio_configuration() {
	global $defensio_conf;
	
	$key = null;

	if (isset($_POST['new_key'])) {
		check_admin_referer( $defensio_conf['nonce']);
		$key = trim($_POST['new_key']);
		$key = defensio_sanitize($key);
		update_option('defensio_key', $key);
		$defensio_conf['key'] = $key;
	}

	if (isset($defensio_conf['key'])) {
		if (defensio_verify_key($defensio_conf['key'])) {
			$valid = true;
		} else {
			$valid = false;
		}

		$key = $defensio_conf['key'];
	}

	if (isset($_POST['new_threshold'])) {
		$t = (int)$_POST['new_threshold'];

		if (0 <= $t and $t <= 100) {
			update_option(defensio_user_unique_option_key('threshold'), $t );
		}
	} 

	if (!$defensio_conf['hckey']) {
		$defensio_conf['hckey'] = false;
	}

	$older_than_error = '';
	$minimum_days = 15;

	if (isset($_POST['defensio_remove_older_than_toggle'])) {
		if (isset($_POST['defensio_remove_older_than']) and (isset($_POST['defensio_remove_older_than_days']) and ((int) $_POST['defensio_remove_older_than_days'] > $minimum_days))) {
			update_option('defensio_delete_older_than', '1');
			update_option('defensio_delete_older_than_days', (int) $_POST['defensio_remove_older_than_days']);
		} else {
			update_option('defensio_delete_older_than', '0');

			if (isset($_POST['defensio_remove_older_than_days']) and ((int)$_POST['defensio_remove_older_than_days'] < $minimum_days)) {
				$older_than_error = 'Days has to be a numeric value greater than '.$minimum_days;

			} elseif (isset($_POST['defensio_remove_older_than_days']) and ((int) $_POST['defensio_remove_older_than_days'] > $minimum_days)) {
				update_option('defensio_delete_older_than_days', (int) $_POST['defensio_remove_older_than_days']);
			}
		}

	} else {
		if ((isset($_POST['defensio_remove_older_than_days']) and ((int) $_POST['defensio_remove_older_than_days'] > $minimum_days) )) {
			update_option('defensio_delete_older_than_days', (int) $_POST['defensio_remove_older_than_days']);
		} elseif($_POST['defensio_remove_older_than_days'] > $minimum_days ){
			$older_than_error = 'Days has to be a numeric value greater than '.$minimum_days;
		}
	}


	$threshold = get_option(defensio_user_unique_option_key('threshold'));

	if(empty($threshold))
		$threshold  = 80;

	defensio_render_configuration_html(array(
		'key'				=> $key, 
		'hckey'				=> $defensio_conf['hckey'], 
		'threshold'			=> $threshold,
		'nonce'				=> $defensio_conf['nonce'],
		'valid'				=> $valid,
		'remove_older_than'		=> get_option('defensio_delete_older_than'),
		'remove_older_than_days' 	=> get_option('defensio_delete_older_than_days'),
		'remove_older_than_error' 	=> $older_than_error
	));
}

function defensio_generate_spaminess_filter($reverse = false, $ignore_option = false) {
	$spaminess_filter = '';

	$option_name = defensio_user_unique_option_key('hide_more_than_threshold');

	if (get_option($option_name) == '1' or $ignore_option) {
		$t = (int)get_option(defensio_user_unique_option_key('threshold'));
		$t = (float)($t) / 100.0;
                
		if (!$reverse) {
			$spaminess_filter = " AND IFNULL(spaminess, 1) < $t";
		} else {
			$spaminess_filter = " AND IFNULL(spaminess, 1) >= $t";
		}
	}

	return $spaminess_filter;
}


function defensio_update_db($opts = null){
	global $wpdb, $defensio_conf, $defensio_retraining;

	if($opts == null or !is_array($opts))
		return false;

	if (function_exists('current_user_can') && !current_user_can('moderate_comments')) {
		die(__('You do not have sufficient permission to moderate comments.'));
	}

	// Single message to restore
	if(isset ($opts['ham'])) {
		$id = (int) $opts['ham'];
		defensio_set_status_approved($id);
	}

	// Many messages to process
	if (isset ($opts['defensio_comments'])) {

		// Restore
		if (isset ($opts['defensio_restore'])) {
			foreach ($opts['defensio_comments'] as $k => $v) {		  
				$id = (int)$k;
				defensio_set_status_approved($id);
			}
		}

		// Delete
		if (isset ($opts['defensio_delete'])) {
			foreach ($opts['defensio_comments'] as $k => $v) {
				$k = (int) $k;
				$wpdb->query("DELETE from $wpdb->prefix" . "defensio WHERE comment_ID = $k");
				$wpdb->query("DELETE from $wpdb->comments WHERE comment_ID = $k");
			}
		}
	}

	// Empty spam box, delete all 
	if (isset($opts['defensio_empty_quarantine'])) {
		defensio_empty_quarantine();
	}

}



// Prepare messages to be displayed in the quarantine
function defensio_caught( $opts = null ) {
	global $wpdb, $defensio_conf, $defensio_retraining;

	if($opts == null or !is_array($opts))
		return false;

	if (function_exists('current_user_can') && !current_user_can('moderate_comments')) {
		die(__('You do not have sufficient permission to moderate comments.'));
	}

	$items_per_page = $opts['items_per_page'];

	if (isset ($opts['defensio_page']) or empty ($opts['defensio_page'])) {
		if ((int) $opts['defensio_page'] < 2) {
			$page = 1;
		} else {
			$page = (int) $opts['defensio_page'];
		}
	} else {
		$page = 1;
	}

	// In case further ordering is needed
	$order = null;

	// A new ordering requested? update ordering creterion
	if ( isset($opts['sort_by']) and !empty ($opts['sort_by'])) {
  		// Order by comment date
		if ($opts['sort_by'] == 'comment_date') {
			$order = 'comment_date';

		// order by post date
		} elseif ($opts['sort_by'] == 'post_date') { 
			$order = 'post_date';

		//order by spaminess
		} else {
			$order = 'spaminess';
		}

		update_option( defensio_user_unique_option_key('order'), $order);
	}
	
	if($order == null){
		// no request? get the ordering from options.
		$order = get_option(defensio_user_unique_option_key('order'));
		
		if($order == null){
			$order = 'spaminess';
			update_option( defensio_user_unique_option_key('order'), $order);
		}

	}
	
	$sql_order =  defensio_order_2_sql($order);


	// hide comments over threshold
	if (isset($opts['defensio_hide_very_spam_toggle'])) {
		$opt_name = defensio_user_unique_option_key('hide_more_than_threshold');

		if (isset($opts['defensio_hide_very_spam'])) {
		  update_option($opt_name, '1');
		} else {
		  update_option($opt_name, '0'); 
		}
	}

	$spaminess_filter = defensio_generate_spaminess_filter();
	$search_query = '';
   
	if (isset($opts['defensio_search_query']) and !empty($opts['defensio_search_query'])) {
		$s = $opts['defensio_search_query'];
		$s = defensio_sanitize($s);
		$search_query = " AND  (comment_author LIKE '%$s%' OR comment_author_email LIKE '%$s%' OR comment_author_url LIKE ('%$s%') OR comment_author_IP LIKE ('%$s%') OR comment_content LIKE ('%$s%') ) ";
		$query_param = $opts['defensio_search_query'];
	}

	if (!isset($query_param)) {
		$query_param = $opts['defensio_search_query']; 
	}

	if(!isset($opts['defensio_filter_by_type']) or $opts['defensio_filter_by_type'] == 'all' ){
		$type_filter = '';
	} elseif(isset($opts['defensio_filter_by_type']) ) {
		// Comments have empty type
		if($opts['defensio_filter_by_type'] == 'comments' )
			$type_filter = " AND comment_type = ''  ";
		// Trackbacks have some type
		elseif($opts['defensio_filter_by_type'] == 'trackbacks' )
			$type_filter = " AND comment_type != ''  ";
	}


	// Count messages
	$spam_count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments LEFT JOIN $wpdb->prefix" . "defensio ON $wpdb->comments" . ".comment_ID = $wpdb->prefix" . "defensio.comment_ID
		WHERE comment_approved = 'spam' $spaminess_filter $search_query $type_filter  ");

	$start = ($page -1) * $items_per_page;
	$end = $items_per_page;
	$pages_count = ceil(floatval($spam_count) / floatval($items_per_page));

	// Get actual messages
	$comments = $wpdb->get_results(
			"SELECT *,IFNULL(spaminess, 1) as spaminess, $wpdb->comments.comment_ID as id, $wpdb->posts.post_title as post_title, $wpdb->posts.post_date as post_date, 
			$wpdb->comments.comment_post_ID  as post_id  FROM 
			$wpdb->comments LEFT JOIN $wpdb->prefix" . "defensio ON $wpdb->comments" . ".comment_ID = $wpdb->prefix" . "defensio.comment_ID LEFT JOIN  
			$wpdb->posts ON $wpdb->comments.comment_post_ID = $wpdb->posts.ID  WHERE comment_approved = 'spam'
			$spaminess_filter $search_query $type_filter ORDER BY  $sql_order   LIMIT $start, $end"
	);


	if (trim($order) == 'comment_date') {
		$order_param = 'comment_date';
	} elseif (trim($order) == 'post_date') {
		$order_param = 'post_date';
	} else {
		$order_param = 'spaminess';
	}

	$stats = wp_cache_get('stats', 'defensio');
  
	if (!$stats) { 
		$stats = defensio_get_stats();
		wp_cache_set('stats' , $stats, 'defensio', 600);
	}
  
	global $plugin_uri;

	$reverse_spaminess_filter = defensio_generate_spaminess_filter(true, true);

	$hiddable_spam = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments LEFT JOIN $wpdb->prefix" . "defensio ON $wpdb->comments" . 
					".comment_ID = $wpdb->prefix" . "defensio.comment_ID WHERE comment_approved = 'spam' $reverse_spaminess_filter $search_query $type_filter ");

	return array(
		'comments'		=> $comments,
		'current_page'	=> $page,
		'type_filter'	=> $opts['defensio_filter_by_type'],
		'spam_count'	=> $spam_count,
		'pages_count'	=> $pages_count,
		'order'			=> $order_param,
		'query'			=> $query_param,
		'spaminess_filter' => get_option(defensio_user_unique_option_key('hide_more_than_threshold')),
		'nonce'			=> $defensio_conf['nonce'],
		'stats'			=> $stats,
		'hidden_spam_count'	=> $hiddable_spam,
		'authenticated'	=> defensio_verify_key($defensio_conf['key']),
		'plugin_uri'	=> $plugin_uri,
		'api_key'		=> $defensio_conf['key']
	);
}

function defensio_dispatch(){
	global $wpdb, $defensio_conf, $defensio_retraining;
	
	defensio_user_unique_option_key('a');

	if (function_exists('current_user_can') && !current_user_can('moderate_comments')) {
		die(__('You do not have sufficient permission to moderate comments.'));
	}

	/* Perform requested actions*/
	$db_req = array( 
			'defensio_comments' => $_POST['defensio_comments'],  
			'defensio_empty_quarantine' =>  $_POST['defensio_empty_quarantine'],  
			'defensio_restore' =>  $_POST['defensio_restore'],  
			'defensio_delete' =>  $_POST['defensio_delete'] 
			);

	$db_req ['ham'] = $_GET['ham'];

	if(!isset($db_req['ham']))
		$db_req['ham'] = $_POST['haam'];

	defensio_update_db($db_req);

	/* Query for comments */
	
	$query_opts = array(
			'items_per_page' => 50,
 			'defensio_page' => $_GET['defensio_page'],
			'sort_by' => $_GET['sort_by'],
			'defensio_hide_very_spam_toggle' => $_POST['defensio_hide_very_spam_toggle'],
			'defensio_hide_very_spam' => $_POST['defensio_hide_very_spam'],
			'defensio_search_query' => $_POST['defensio_search_query'],
			'defensio_filter_by_type' => $_GET['comment_type']
			);


	if(!isset($query_opts['defensio_search_query']))
		$query_opts['defensio_search_query'] = $_GET['defensio_search_query'];

	$render_params = defensio_caught($query_opts);
	
	/*Render quarantine*/
	defensio_render_quarantine_html($render_params);
}

function defensio_manage_page() {
	global $wpdb, $submenu, $defensio_conf;

	$spaminess_filter = defensio_generate_spaminess_filter();
	$spam_count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments LEFT JOIN $wpdb->prefix" . "defensio ON $wpdb->comments" . ".comment_ID = $wpdb->prefix" . "defensio.comment_ID  WHERE comment_approved = 'spam' $spaminess_filter ");

	if (isset($submenu['edit-comments.php'])) {
		add_submenu_page('edit-comments.php', 'Defensio Spam', "Defensio Spam ($spam_count)", 'moderate_comments', __FILE__, 'defensio_dispatch');
	}
	elseif (function_exists('add_management_page')) {
		add_management_page('Defensio Spam', "Defensio Spam ($spam_count)", 'moderate_comments', 'defensio-admin', 'defensio_dispatch');
	}
}
add_action('admin_menu', 'defensio_manage_page');

function defensio_head() {
	global $plugin_uri;
	defensio_render_html_head(array('plugin_uri' => $plugin_uri));
}
add_action('admin_head', 'defensio_head');

function defensio_save_meta_data($comment_ID) {
	global $wpdb, $defensio_meta;
	$meta = $defensio_meta;
	$comment_ID = defensio_sanitize($comment_ID);

	//Create Defensio record
	if (isset($meta['spaminess']) and isset($meta['signature'])) {
		$wpdb->query("INSERT INTO $wpdb->prefix" . "defensio (comment_ID, spaminess, signature) VALUES	(" . $comment_ID . ", " . $meta['spaminess'] . ", '" . $meta['signature'] . "')");
	} else {
		$wpdb->query("INSERT INTO $wpdb->prefix" . "defensio (comment_ID, spaminess, signature) VALUES	(" . $comment_ID . ", -1 , '' )");
	}

  return $comment_ID;
}

function defensio_update_meta_data($comment_ID) {
	global $wpdb, $defensio_meta;
	$meta = $defensio_meta;
	$comment_ID = defensio_sanitize($comment_ID);

	if (isset($meta['remove']) and $meta['remove'] == true) {
		$wpdb->query("DELETE from $wpdb->prefix" . "defensio WHERE comment_ID = $comment_ID");
		$wpdb->query("DELETE from $wpdb->comments WHERE comment_ID = $comment_ID");
		return true;
	}

	// Update Defensio record
	if (isset($meta['spaminess']) and isset($meta['signature'])) {
		$wpdb->query("UPDATE  $wpdb->prefix" . "defensio set spaminess =  ". $meta['spaminess'] . " , signature =	'" . $meta['signature'] . "' WHERE comment_ID = $comment_ID ");
		
		// If this is an ajax call put spam in the quarantine since hooks wont run
		if (defined('DEFENSIO_AJAX') and isset($defensio_meta['spam']) and $defensio_meta['spam'] == true) {
			$wpdb->query("UPDATE $wpdb->comments set comment_approved = 'spam' WHERE comment_ID = $comment_ID ");
		}
	} else {
		return false;
	}

	return $comment_ID;
}

function defensio_get_stats() {
	global $defensio_conf;

	$r = defensio_post('get-stats', array('owner-url' => $defensio_conf['blog'])); 

	$ar = Spyc::YAMLLoad($r); 
	if (isset($ar['defensio-result'])) {
		defensio_update_stats_cache($ar['defensio-result']);
		return $ar['defensio-result'];
	} else {
		return false;
	}
}

/* Look for wp_openid */
function defensio_is_openid_enabled(){
	return function_exists('is_user_openid');
}

function defensio_get_openid($com){
	global $wpdb,  $openid;
        
	if (!defensio_is_openid_enabled())
		return $com;

	if (is_user_openid()){
		// Add the last identity to defensio params
		$identity = $openid->logic->store->get_my_identities(null);
		if(is_array($identity)) {
			$identity = @array_pop($identity);
		}
		$com['openid'] = $identity['url'];
	} elseif($openid->logic->finish_openid_auth()) {
		$com['openid'] = $openid->logic->finish_openid_auth();
		// Not really logged in but a valid openid
		$com['user-logged-in'] = 'true';
	}
	return $com;
}

function defensio_check_comment($com, $incoming = true, $retrying = false) {
	global $wpdb, $defensio_conf, $defensio_meta, $userdata, $acts_as_master;
	
	$comment = array();

	/* If it is an incoming message (not yet in the database).
	   get current user info, otherwise get the info from the 
	   user who posted the comment 
	*/
	   
	if ($incoming) {
		$comment['referrer'] = $_SERVER['HTTP_REFERER'];
		$comment['user-ip'] = preg_replace('/[^0-9., ]/', '', $_SERVER['REMOTE_ADDR']);
		get_currentuserinfo();
	} else {
		$userdata = get_userdata($com['user_id']);
		$comment['user-ip'] = $com['comment_author_IP'];
	}

	if ($userdata->ID) {
		$comment['user-logged-in'] = 'true';

		// Wordpress names the capabilities array wp_prefix_capablities
		// use eval to call the correct property in case the prefix is not wp
		eval('$caps = $userdata->'.$wpdb->prefix.'capabilities ;');

		if (defensio_is_trusted_user($caps)) {
			$comment['trusted-user'] = 'true';
		}
	}
	
	$comment['owner-url'] = $defensio_conf['blog'];

	if (isset($com['comment_post_ID'])) {
		$comment['article-date'] = strftime("%Y/%m/%d", strtotime($wpdb->get_var("SELECT post_date FROM $wpdb->posts WHERE ID=" . $com['comment_post_ID'])));
		$comment['permalink'] = get_permalink($com['comment_post_ID']);
	}
  
	$comment['comment-author'] = $com['comment_author'];
  
	if (!isset($com['comment_type']) or empty($com['comment_type'])) {
		$comment['comment-type'] = 'comment';
	} else {
		$comment['comment-type'] = $com['comment_type'];
	}
  
	// Make sure it we don't send an SQL escaped string to the server
	$comment['comment-content'] = defensio_unescape_string($com['comment_content']);
	$comment['comment-author-email'] = $com['comment_author_email'];
	$comment['comment-author-url'] = $com['comment_author_url'];
	

	// If wp_openid is installed, use it
	$comment['user_ID'] = $com['user_ID'];
	$comment = defensio_get_openid($comment);
	unset( $comment['user_ID']);

	if ($r = defensio_post('audit-comment', $comment)) {
		$ar = Spyc :: YAMLLoad($r);
	
		if (isset($ar['defensio-result'])) {
			if ($ar['defensio-result']['status'] == DF_SUCCESS ) {
				// Set metadata about the comment
				$defensio_meta['spaminess'] = $ar['defensio-result']['spaminess'];
				$defensio_meta['signature'] = $ar['defensio-result']['signature'];
		
				// Hook a function to store that metadata
				add_action('comment_post', 'defensio_save_meta_data');
		
				// Mark it as SPAM
				if ($ar['defensio-result']['spam']) {
					add_filter('pre_comment_approved', create_function('$a', 'return \'spam\';'), 99);
					$defensio_meta['spam'] = true;
					$article = get_post($com['comment_post_ID']);	

					// Get the difference in seconds from the article publication date until today
					$time_diff = time() - strtotime($article->post_modified_gmt);
		
					// A day has 86400 seconds
					if (get_option('defensio_delete_older_than') == 1 and ($time_diff > (get_option('defensio_delete_older_than_days') * 86400))) {
						if ($incoming) {
							die;
						} else {
							$defensio_meta['remove'] = true;
						}
					}
				} else {
					// Apply wp preferences in case approved value has been changed to spam by another plug-in
					if ($acts_as_master == true) {
						add_filter('pre_comment_approved', create_function('$a', 'if ($a == \'spam\') return defensio_reapply_wp_comment_preferences(' .var_export($com, true). '); else return $a; '), 99);
					}
				}
			}
		} else {
		    // Successful http request, but Defensio failed. Retry, once
			if(!$retrying){
				defensio_check_comment($com, $incoming, true) ;
		    } else {
				// Put comment in moderation queue.
				add_filter('pre_comment_approved', create_function('$a', 'return 0;'), 99);
				add_action('comment_post', 'defensio_save_meta_data');
		    }
		}
	} else {
		// Unsuccessful POST to the server. Defensio might be down.  Retry, once
		if(!$retrying) {
		    defensio_check_comment($com, $incoming, true) ;
		// No luck... put comment in moderation queue
		} else {
			add_filter('pre_comment_approved', create_function('$a', 'return 0;'), 99);
			add_action('comment_post', 'defensio_save_meta_data');
		}
	}

	return $com;
}
add_action('preprocess_comment', 'defensio_check_comment', 1);

function defensio_verify_key($key) {
	global $defensio_conf;
	$result = false;
	$params = array('key'		=> $key,
					'owner-url' => $defensio_conf['blog']);

	if ($r = defensio_post('validate-key', $params)) {
		// Parse result
		$ar = Spyc :: YAMLLoad($r);

		// Spyc will return an empty array in case the result is not a well-formed YAML string.
		// Verify that the array is a valid Defensio result before continuing
		if (isset ($ar['defensio-result'])) {
			if ($ar['defensio-result']['status'] == DF_SUCCESS) {
				$result = true; 
				return $result;
			}
		} else {
			return $result;
		}
	} else {
	  return $result;
	}

	return $result;
}


function defensio_submit_ham($signatures) {
	global $wpdb, $defensio_conf;

	$params = array(
		'signatures' => $signatures,
		'owner-url'	 => $defensio_conf['blog'],
		'user-ip'	 => $comment->comment_author_IP );

	$r = defensio_post('report-false-positives', $params);
}


function defensio_submit_spam($signatures){
	global $wpdb, $defensio_conf;

	$params = array(
		'signatures' => $signatures,
		'owner-url'	 => $defensio_conf['blog'],
		'user-ip'	 => $comment->comment_author_IP);

	$r = defensio_post('report-false-negatives', $params);
}


// To train multiple messages at once, we push them into an array
// and process them in the shutdown hook.
function defensio_defer_training($id, $new_status = null) {
	global $deferred_spam_to_ham, $deferred_ham_to_spam, $defensio_retraining, $wpdb;
  
	$id = defensio_sanitize($id);
  
	// 'approve' should only be retrained when a message is being marked as SPAM
	if (!(($new_status == 'approve' and $defensio_retraining) or $new_status == 'spam')) {
		return;
	}
		
	$comment = $wpdb->get_row("SELECT * FROM $wpdb->comments NATURAL JOIN $wpdb->prefix" . "defensio WHERE $wpdb->comments.comment_ID = '$id'");
  
	if (!$comment) { return; }
	if (!isset($comment->signature) or empty($comment->signature)) { return; }
	
	if ($comment->comment_approved == 'spam') {
		// Set new spaminess to 100%, it is spam for sure
		$wpdb->get_row("UPDATE	$wpdb->prefix" . "defensio SET spaminess = 1 WHERE $wpdb->prefix"."defensio.comment_ID = '$id'");

		// If ajax retrain, the shutdown hook won't be called, and no defered training will occur 
		if (defined('DOING_AJAX')) {
		  defensio_submit_spam($comment->signature);
		// Push for training	
		} else {
		  array_push($deferred_ham_to_spam, $comment->signature );
		}
	}
  
	if ($comment->comment_approved == 1) {
		// Set new spaminess to 0%, it is ham for sure
		$wpdb->get_row("UPDATE	$wpdb->prefix" . "defensio SET spaminess = 0 WHERE $wpdb->prefix"."defensio.comment_ID = '$id'");

		if (defined('DOING_AJAX' )) {
			defensio_submit_ham($comment->signature);
		} else	{
			array_push($deferred_spam_to_ham, $comment->signature );
		}
	}
}
add_action('wp_set_comment_status', 'defensio_defer_training', 10, 2);
add_action('edit_comment', 'defensio_defer_training', 10, 1);


function defensio_announce_article($id) {
	global $defensio_conf, $wpdb, $userdata;

	get_currentuserinfo();
	$id = defensio_sanitize($id);
	$post = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE $wpdb->posts.ID = '$id' ");

	$params = array (
		'article-content' => $post->post_content, 
		'article-title'	  => $post->post_title, 
		'permalink'		  => get_permalink($post->ID),
		'owner-url'		  => $defensio_conf['blog'],
		'article-author'  => $userdata->user_login,
		'article-author-email' => $userdata->user_email );

	$r = defensio_post('announce-article', $params);
} 
add_action('publish_post', 'defensio_announce_article');


// Post an action to Defesio and use args as POST data, returns false on error 
function defensio_post($action, $args = null) {
	global $defensio_conf;

	// Use snoopy to post
	require_once (ABSPATH . 'wp-includes/class-snoopy.php');

	$snoopy = new Snoopy();
	$snoopy->read_timeout = $defensio_conf['post_timeout'];

	// Supress the possible fsock warning 
	@$snoopy->submit(defensio_url_for($action, $defensio_conf['key']), $args, array ());

	// Defensio will return 200 nomally, 401 on authentication failure, anything else is unexpected behaivour
	if ($snoopy->status == 200 or $snoopy->status == 401) {
		return $snoopy->results; 
	} else {
		return false;
	}
}


// Returns the URL for possible actions
function defensio_url_for($action, $key = null) {
	global $defensio_conf;

	if ($key == null) {
		return null;
	} else {
		if ($action == 'validate-key')	   { return 'http://' . $defensio_conf['server'] . '/' . $defensio_conf['path'] . '/' . $defensio_conf['api-version'] . '/' . $action . '/' . $key . '.' . $defensio_conf['format']; }
		if ($action == 'audit-comment')	   { return 'http://' . $defensio_conf['server'] . '/' . $defensio_conf['path'] . '/' . $defensio_conf['api-version'] . '/' . $action . '/' . $key . '.' . $defensio_conf['format']; }
		if ($action == 'report-false-negatives') { return 'http://' . $defensio_conf['server'] . '/' . $defensio_conf['path'] . '/' . $defensio_conf['api-version'] . '/' . $action . '/' . $key . '.' . $defensio_conf['format']; }
		if ($action == 'report-false-positives') { return 'http://' . $defensio_conf['server'] . '/' . $defensio_conf['path'] . '/' . $defensio_conf['api-version'] . '/' . $action . '/' . $key . '.' . $defensio_conf['format']; }
		if ($action == 'get-stats')		   { return 'http://' . $defensio_conf['server'] . '/' . $defensio_conf['path'] . '/' . $defensio_conf['api-version'] . '/' . $action . '/' . $key . '.' . $defensio_conf['format']; }
		if ($action == 'announce-article') { return 'http://' . $defensio_conf['server'] . '/' . $defensio_conf['path'] . '/' . $defensio_conf['api-version'] . '/' . $action . '/' . $key .  '.' . $defensio_conf['format']; }
	}

	return null;
}


function defensio_empty_quarantine() {
	global $wpdb;

	$wpdb->query("DELETE $wpdb->prefix"."defensio.* FROM  $wpdb->prefix"."defensio NATURAL JOIN $wpdb->comments WHERE comment_approved = 'spam'");
	$wpdb->query("DELETE FROM $wpdb->comments WHERE comment_approved = 'spam'");
}


function defensio_sanitize($str) {
	global $wpdb;
	return $wpdb->escape($str);
}

// To be used with admin-ajax
function defensio_restore() {
	global $wpdb;
	define('DOING_AJAX', true);
  
	if (isset ($_POST['ham'])) {
		$id = (int) $_POST['ham'];

		$comment = $wpdb->get_row("SELECT * FROM $wpdb->comments NATURAL JOIN $wpdb->prefix" . "defensio WHERE $wpdb->comments.comment_ID = '$id'");

		if ($comment) {
			$wpdb->get_row("UPDATE	$wpdb->prefix" . "defensio SET spaminess = 0 WHERE $wpdb->prefix"."defensio.comment_ID = '$id'");
		}

		defensio_set_status_approved($id);
	} 
}
add_action('wp_ajax_defensio-restore', 'defensio_restore');


function defensio_is_trusted_user($cap) {
	global $defensio_trusted_roles;
  
	if (!is_array($cap)) { return false; }

	foreach ($cap as $k => $v) {
		if (in_array($k, $defensio_trusted_roles)) { return true; }
	}

	return false;
}


function defensio_set_status_approved($id) {
	global $defensio_retraining;
	$defensio_retraining = true;
	wp_set_comment_status($id, 'approve');
	$defensio_retraining = false;
}


function defensio_reapply_wp_comment_preferences($comment_data) {  
	//Taken from wp_comment.php
	global $wpdb;
	extract($comment_data, EXTR_SKIP);

	if ($user_id) {
		$userdata = get_userdata($user_id);
		$user = new WP_User($user_id);
		$post_author = $wpdb->get_var("SELECT post_author FROM $wpdb->posts WHERE ID = '$comment_post_ID' LIMIT 1");
	}

	if ($userdata && ($user_id == $post_author || $user->has_cap('level_9'))) {
		// The author and the admins get respect.
		$approved = 1;
	} else {
		// Everyone else's comments will be checked.
		if ( check_comment($comment_author, $comment_author_email, $comment_author_url, $comment_content, $comment_author_IP, $comment_agent, $comment_type)) {
			$approved = 1;
		} else {
			$approved = 0;
		}

		if (wp_blacklist_check($comment_author, $comment_author_email, $comment_author_url, $comment_content, $comment_author_IP, $comment_agent)) {
			$approved = 'spam';
		}
	}

	return $approved;	
}


function defensio_unescape_string($str) {
	return stripslashes($str);
}


function defensio_counter($color='dark', $align='left') {
	global $plugin_uri;
	$last_updated = get_option('defensio_stats_updated_at');
	$two_hours = 60 * 60 * 2;

	if ( ($last_updated == NULL) or (mktime() - $last_updated > $two_hours) ) {
		$s = defensio_get_stats();
	} else {
		$s = get_option('defensio_stats');
	}

	if ($s) {
		defensio_render_counter_html(array('smoked_spam'=>$s['spam'], 'color'=>$color, 'align'=>$align, 'plugin_uri'=>$plugin_uri ));
	}
}

function defensio_update_stats_cache($stats) {
	update_option('defensio_stats', $stats);
	update_option('defensio_stats_updated_at', mktime());
}

function defensio_widget_register() {
	if (function_exists('register_sidebar_widget')) {
		function defensio_widget() { 
			$alignment = get_option('defensio_counter_alignment'); 
			$color = get_option('defensio_counter_color');
			if (!isset($alignment) or empty($alignment)){ $alignment = 'left'; }
			if (!isset($color) or empty($color)){ $color = 'dark'; }

			defensio_counter(strtolower($color),strtolower($alignment)); 
		}

		function defensio_widget_control() {
			global $defensio_widget_tones;
			if ($_POST['defensio_counter_alignment']) {
				update_option('defensio_counter_alignment', $_POST['defensio_counter_alignment']);
			}

			if ($_POST['defensio_counter_color']) {
				update_option('defensio_counter_color', strtolower($_POST['defensio_counter_color']));
			}

			$alignment = get_option('defensio_counter_alignment');
			$color = get_option('defensio_counter_color');

			if (!isset($alignment) or empty($alignment)){ $alignment = 'Left'; }
			if (!isset($color) or empty($color)){ $color = 'dark'; }
?>
			<label for="defensio_counter_alignment"	 style="width: 100px; display: block; float: left;">Alignment</label>
			<select name="defensio_counter_alignment" id="defensio_counter_alignment">
				<option <?php if ($alignment == 'Left'):?>selected="1" <?php endif;?> >Left</option>
				<option <?php if ($alignment == 'Center'):?> selected="1"<?php endif;?> >Center</option>
				<option <?php if ($alignment === 'Right'):?>selected="1" <?php endif; ?> >Right</option>
			</select> 
			<br />
			<label for="defensio_counter_color" style="width: 100px; display: block; float: left;">Color</label>
			<select name="defensio_counter_color" id="defensio_counter_color">
				<?php foreach($defensio_widget_tones as $t): ?>
					<option <?php if ($t == $color) :?> selected="1"<?php endif;?> ><?php echo ucfirst($t) ?></option>
				<?php endforeach; ?>
			</select>
<?php
		}
		register_sidebar_widget('Defensio Counter', 'defensio_widget', null, 'defensio');
		register_widget_control('Defensio Counter', 'defensio_widget_control', 300, 75, 'defensio');
	}
}
add_action('init', 'defensio_widget_register');


function defensio_get_unprocessed_comments() {
	global $wpdb;
	// Spaminess -1 means the comment never reached Defensio server
	$comments = $wpdb->get_results("SELECT $wpdb->comments.comment_ID as id FROM $wpdb->comments  LEFT JOIN $wpdb->prefix" . "defensio ON $wpdb->comments" . ".comment_ID = $wpdb->prefix" . "defensio.comment_ID  WHERE spaminess = -1  "); 
	return($comments);
}

function defensio_wp_spam_count($obvious_only) {
	global $wpdb;
	$threshold = get_option('defensio_threshold') / 100 ;
	if ($obvious_only) {
		return $wpdb->get_var("SELECT count(*) FROM $wpdb->comments LEFT JOIN $wpdb->prefix"."defensio ON $wpdb->comments" . ".comment_ID = $wpdb->prefix" . "defensio.comment_ID WHERE comment_approved = 'spam' AND spaminess >= $threshold ;");
	} else {
		return $wpdb->get_var("SELECT count(*) FROM $wpdb->comments LEFT JOIN $wpdb->prefix"."defensio ON $wpdb->comments" . ".comment_ID = $wpdb->prefix" . "defensio.comment_ID WHERE comment_approved = 'spam';");
	}
}

function defensio_render_activity_box() {
	global $plugin_name;
	$link_base = 'edit-comments.php';
	$link = clean_url($link_base . "?page=$plugin_name/defensio.php");

	$obvious_spam_count = defensio_wp_spam_count(true);
	$total_spam_count = defensio_wp_spam_count(false);

	echo "<h3>Defensio Spam</h3>";
	if ($total_spam_count == 0) {
		echo "Your Defensio quarantine is empty.  Awesome!";
	} elseif ($total_spam_count == 1) {
		echo "You have $total_spam_count spam comment";
		if ($obvious_spam_count > 0) { echo " ($obvious_spam_count obvious)"; }
		echo " in your <a href='$link'>Defensio quarantine</a>.";
	} elseif ($total_spam_count > 1) {
		echo "You have $total_spam_count spam comments";
		if ($obvious_spam_count > 0) { echo " ($obvious_spam_count obvious)"; }
		echo " in your <a href='$link'>Defensio quarantine</a>.";
	}
}
add_action('activity_box_end', 'defensio_render_activity_box');

// Orphan rows have spaminess -1; they were never filtered by Defensio
function defensio_clean_up_orphan_rows($id, $status) {
	global $wpdb;
	if ($status == 'hold') {
		// If it stays in moderation, it can still be sent to defensio, do nothing
	} elseif ($status == 'spam') {
		// spam for sure
		$wpdb->query("UPDATE  $wpdb->prefix"."defensio set spaminess = 1 WHERE spaminess = -1 AND comment_ID = $id " );
	} elseif ($status == 'approve') {
		// ham for sure
		$wpdb->query("UPDATE  $wpdb->prefix"."defensio set spaminess = 0 WHERE spaminess = -1 AND comment_ID = $id " );
	} elseif ($$status == 'delete') {
		$wpdb->query("DELETE FROM $wpdb->prefix"."defensio WHERE spaminess = -1 AND comment_ID = $id " );
	}
}
add_action('wp_set_comment_status', 'defensio_clean_up_orphan_rows', 10, 2);


// Generates a key name for wp options that is user unique
function defensio_user_unique_option_key( $opt_name = null ){
	global $userdata;
	if($opt_name != null){
		get_currentuserinfo();
		return "defensio_". $userdata->ID."_$opt_name";
	}
}


// Utiility function
function defensio_order_2_sql($order = null){
	switch($order){
		case 'post_date':
			return ' post_date DESC, IFNULL(spaminess, 1) ASC ';
		case 'comment_date':
			return ' comment_date DESC, IFNULL(spaminess, 1) ASC  ';
		default:
			return ' IFNULL(spaminess, 1) ASC, comment_date DESC ' ;
	}
}

?>