<?php
function defensio_render_unprocessed_in_moderation($unprocessed) {
	 global $plugin_uri;

	 // create a comma delimited list of ids
	 $unprocessedList = "";
	 for($i=0; $i < count($unprocessed); $i++) {
		$unprocessedList .= $unprocessed[$i]->id;
		if($i < count($unprocessed) - 1) {
		  $unprocessedList .= ", ";
		}
	 }
?>

<style type='text/css'>  
	#adminmenu { margin-bottom: 6em; }
	#adminmenu.large { margin-bottom: 8.5em; }
	#defensio-warning { position: absolute; top: 7em; }
	#defensio_warning_controls_wrap { width:auto; margin-bottom:3px; display:none; }
	#defensio-warning p.defensio_error { color: red; }
	#defensio-warning p.defensio_success { color: green; }
	#defensio_progress_bar { width:300px; height:16px; border:1px solid black; padding:2px; float:left; margin-bottom:10px; }
	#defensio_progress_bar_value { width:0%; height:16px; background:blue; }
	#defensio_spinner { padding: 2px 10px 0 10px; float: left; }
	#defensio_start_processing { margin-left: 10px; clear:both; }
	#defensio_stop_processing { clear:both; }
</style>

<div id='defensio-warning' class='updated fade-ff0000'>
  <p>
    <strong id="defensio_unprocessed_count"><?php echo count($unprocessed) ?></strong> comments could not processed by Defensio.
    <button id="defensio_start_processing" onclick="">Process Now</button>
  </p>

  <div id="defensio_warning_controls_wrap">
    <div id="defensio_progress_bar"><div id="defensio_progress_bar_value"></div></div>
    <img src="<?php echo $plugin_uri ?>images/spinner.gif" id="defensio_spinner" />
    <button id="defensio_stop_processing" href="#">Stop</button>
  </div>
</div>

<script type='text/javascript'>
  //<![CDATA[
  var defensioProcessing = { ids: [<?php echo $unprocessedList ?>], current: 0, stop : false, ham: 0, spam: 0 }
  Event.observe('defensio_start_processing', 'click', defensioStartProcessing);
  Event.observe('defensio_stop_processing',  'click', defensioStopProcessing);
  
  function defensio_show_error(){
    $('defensio_warning_controls_wrap').replace("<p class='defensio_error'>There was an error connecting to Defensio. Please try again later.</p>")
  }
  
  function defensioStartProcessing(e) {
    $('adminmenu').addClassName('large');
    $('defensio_warning_controls_wrap').setStyle({display: 'block'});
    $('defensio_start_processing').disabled = true;

    defensioProcessing.stop = false
    defensioProcessModerationAjax(defensioProcessing.current);
    Event.stop(e)    
  }
  
  function defensioStopProcessing(e) {
    defensioProcessing.stop = true; 
    $('adminmenu').removeClassName('large');
    $('defensio_warning_controls_wrap').setStyle({display: 'none'});
    Event.stop(e)
  }

  function defensioProcessModerationAjax(n){
    new Ajax.Request('<?php echo $plugin_uri ?>defensio_ajax.php?action=check_comment', { onComplete: defensioProcessModerationAjaxCompleted,
                                                                                          parameters: { id : defensioProcessing.ids[defensioProcessing.current] } })
  } 

  function defensioProcessModerationAjaxCompleted(t) {
    eval('result = ' + t.responseText )

    // Failure?
    if(!result.success){
      defensio_show_error();
      return false;
    }
    
    defensioUpdateUI(defensioProcessing.current + 1);
    defensioProcessing.current += 1 ;
    
    if(result.spam){
      defensioProcessing.spam += 1 ;
    }else{
      defensioProcessing.ham += 1 ;
    }

    // Does the user want to stop?
    if(defensioProcessing.stop){
      $('defensio_start_processing').disabled = false;
      $('defensio_start_processing').textContent = 'Continue';
      return true;
    }

    // Are we done?
    if(defensioProcessing.current == defensioProcessing.ids.length) {
      $('defensio_spinner').hide();
      $('defensio_warning_controls_wrap').replace("<p class='defensio_success'>Moderation queue successfully processed: " +  String(defensioProcessing.spam) + " spam, " + String(defensioProcessing.ham) + " legitimate. <strong>Happy blogging!</strong></p>");
      return true;
    }

    defensioProcessModerationAjax(defensioProcessing.current);
  }

  function defensioUpdateUI(n){
    $('defensio_progress_bar_value').style.width = ((n/defensioProcessing.ids.length) * 100) + '%'
    $('defensio_unprocessed_count').innerHTML = defensioProcessing.ids.length - n 
  }

//]]>
</script>
<?php } ?>
