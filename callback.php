<?php
require_once('lib/defensio-php/Defensio.php');
require_once('lib/DefensioWP.php');
require_once('lib/DefensioDB.php');
require_once(dirname( __FILE__) . '/../../../wp-load.php');
require_once(ABSPATH . '/wp-includes/wp-db.php');
require_once(ABSPATH . '/wp-includes/comment.php');
require_once(ABSPATH . '/wp-includes/plugin.php');
require_once('defensio_config.php');

global $wpdb, $defensio_conf;
defensio_set_key();

if( !isset($_GET['id']) || ($_GET['id'] != md5($defensio_conf['key'])) )
    die('Could not authenticate. Bye bye!');

try {
    // Supressing possible warning here. Even if the input cannot be parsed we want 
    // Defensio to know something went wrong; if a warning is printed this file we won't 
    // be able to override the headers to have a 5xx status.
    @$response = Defensio::handlePostDocumentAsyncCallback();
    $manager   = new DefensioWP($defensio_conf['key'], $defensio_conf['server'], $defensio_conf['async_callback_url']);
    $manager->applyCallbackResult($response[1]);
} catch (DefensioEmptyCallbackData $ex) {
    die('I need some data to be useful!');
} catch (Exception $ex) {
    header('HTTP/1.0 500 Internal Server Error', true);
    die('Error handling Defensio result');
}

?>
