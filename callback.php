<?php
require_once('lib/defensio-php/Defensio.php');
require_once('lib/DefensioWP.php');
require_once('lib/DefensioDB.php');
require_once(dirname( __FILE__) . '/../../../wp-load.php');
require_once(ABSPATH . '/wp-includes/wp-db.php');
require_once(ABSPATH . '/wp-includes/comment.php');
require_once(ABSPATH . '/wp-includes/plugin.php');
require_once(WP_PLUGIN_DIR . '/defensio-anti-spam/defensio_config.php');

global $wpdb, $defensio_conf;

defensio_set_key();

if( !isset($_GET['id']) || ($_GET['id'] != md5($defensio_conf['key'])) )
    die('Bad id');

try{

    $response = Defensio::handlePostDocumentAsyncCallback();
    $manager = new DefensioWP($defensio_conf['key'], $defensio_conf['server'], $defensio_conf['async_callback_url']);
    $manager->applyCallbackResult($response[1]);

} catch (DefensioEmptyCallbackData $ex){

    die('Nothing useful to do wihtout data!.. and btw you are not suposed to be here');
}

?>
