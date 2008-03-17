<?php
function defensio_render_html_head($v) {
?>
<script type="text/javascript" src="<?php echo $v['plugin_uri'] ?>lib/prototype.js"></script>
<script type="text/javascript" src="<?php echo $v['plugin_uri'] ?>lib/fat.js"></script>
<script type="text/javascript">
<!--
  function defensio_toggle_height(id) {
    var p = $('defensio_body_' + id);
    var a = $('defensio_view_full_comment_' + id);
    var shrunkClass = 'defensio_body_shrunk';
    var expandedClass = 'defensio_body_expanded';
    var expandCaption = 'View full comment';
    var shrinkCaption = 'Collapse comment';
  
    if (p.className == shrunkClass) { 
      p.className = expandedClass; 
      a.innerHTML = shrinkCaption;
    } 
    else { 
      p.className = shrunkClass; 
      a.innerHTML = expandCaption;
    }
    return false;
  }
  
  function defensioCheckAll(sender) {
    items = $$('.defensio_comment_checkbox');
    checkboxes = $$('input.defensio_check_all');
    checkFlag = sender.checked;

    for (i = 0; i < items.length; i++) {
      items[i].checked = checkFlag;
    }

    for (i = 0; i < checkboxes.length; i++) {
      if(checkboxes[i] != sender) 
        checkboxes[i].checked = checkFlag;
    }
    return true;
  }
-->
</script>
<style type="text/css" media="screen">
  div.defensio_quarantine div.defensio_header {
    clear:both;
    text-align: right;
    margin-bottom: 20px;
  }
  
  div.defensio_quarantine div.defensio_header h2 {
    float: left;
    background: transparent;
  }
  div.defensio_quarantine div.defensio_header a {
    border:none;
  }
  div.defensio_quarantine div.defensio_header img {
    padding-top: 7px;
  }

  div.defensio_spam table#defensio_quarantine_options {
    margin-bottom: 20px;
    width: 100%;
    clear: both;
  }

  div.defensio_spam table#defensio_quarantine_options tr {
    vertical-align: top;
  }

  div.defensio_spam table#defensio_quarantine_options tr td {
    line-height: 1.6em;
  }

  div.defensio_spam table#defensio_quarantine_options tr td#defensio_quarantine_options_sort {
    width:60%;
  }

  div.defensio_spam table#defensio_quarantine_options tr td#defensio_quarantine_options_search {
    text-align: right;
    width: 40%;
  }

  div.defensio_spam p#defensio_quarantine_empty {
    margin-bottom: 30px;
    clear: both;
    text-align:center;
  }

  div.defensio_spam ul.defensio_comments {
    list-style: none;
    padding: 0;
  }

  div.defensio_spam ul.defensio_comments li.defensio_check_all {
    margin-top: 5px;
    margin-bottom: 5px;
  }

  div.defensio_spam ul.defensio_comments ul.defensio_comment_group {
    list-style: none;
    padding: 0;
  }

  div.defensio_spam ul.defensio_comments ul.defensio_comment_group li.defensio_post_title {
    font-size: 12pt;
    font-weight: bold;
    padding: 10px 0 7px 10px;
    background-color: #f1f1f1;
  }

  div.defensio_spam ul.defensio_comments ul.defensio_comment_group li.defensio_post_title span {
    font-size: 8pt;
    font-weight: normal;
  }

  div.defensio_spam ul.defensio_comments li {
    padding: 8px 8px 8px 8px;
    margin: 2px;
  }

  div.defensio_spam ul.defensio_comments p {
    margin: 5px 5px 5px 20px;
  }
  
  div.defensio_spam ul.defensio_comments li p.defensio_body_shrunk {
    height: 17px;
    margin-bottom: 10px;
    overflow: hidden;
    font-size: 9pt;
  }

  div.defensio_spam ul.defensio_comments li p.defensio_body_expanded {
    margin-bottom: 10px;
    font-size: 9pt;    
  }
  
  div.defensio_spam ul.defensio_comments li span.defensio_comment_header {
    font-weight: bold;
  }
  
  div.defensio_spam ul.defensio_comments li p.defensio_comment_meta {
    filter:alpha(opacity=75);
    -moz-opacity:.75;
    opacity:.75;
    font-size: 8pt;
  }
  
  div.defensio_spam ul.defensio_comments li p.defensio_comment_meta a.defensio_quarantine_action {
    font-weight: bold;
  }
  
  div.defensio_spam ul.defensio_comments li.defensio_spam0 {
    background-color: #ffffff;
    border-bottom: 1px solid #ccc;
  }

  div.defensio_spam ul.defensio_comments li.defensio_spam1 {
    background-color: #faf0e1;
  }

  div.defensio_spam ul.defensio_comments li.defensio_spam2 {
    background-color: #faebd4;
  }

  div.defensio_spam ul.defensio_comments li.defensio_spam3 {
    background-color: #fae6c8;
  }

  div.defensio_spam ul.defensio_comments li.defensio_spam4 {
    background-color: #fae1bb;
  }

  div.defensio_spam ul.defensio_comments li.defensio_spam5 {
    background-color: #fadcaf;
  }

  div.defensio_spam ul.defensio_comments li.defensio_spam6 {
    background-color: #fad7a2;
  }

  div.defensio_spam ul.defensio_comments li.defensio_spam7 {
    background-color: #fad296;
  }

  div.defensio_spam ul.defensio_comments li.defensio_spam8 {
    background-color: #facd89;
  }

  div.defensio_spam ul.defensio_comments li.defensio_spam9 {
    background-color: #fac87d;
  }

  div.defensio_spam table.defensio_comments tr.defensio_header {
    border-top: 3px solid #ffffff;
  }

  div.defensio_pages {
    clear:both;
    padding: 10px 0 10px 0;    
  }
  
  .defensio_button{
     display: inline;
     float: none;
  }
  div.defensio_buttons{
      margin-bottom: 55px;
      margin-top: 30px;
  }
  
  div.defensio_stats {
    float:left;
    width:62%;    
  }

  div.defensio_stats h3.defensio_learning {
    font-weight:bold;
    color: red;
    font-size: 10pt;    
  }
  
  div.defensio_more_stats {
    width:30%;
    background:url('<?php echo $v['plugin_uri'] ?>images/chart.gif') 0 15px no-repeat;
    padding-left:45px;
    float:left;
  }

  div.defensio_more_stats h3 {  
    font-size:10pt;
    margin-bottom: 4px;
  }

  div.defensio_more_stats p {
    margin: 0;
  }

</style>
<?php
}

function defensio_render_warning_styles() {
?>
	<style type="text/css" media="screen">
		#adminmenu { margin-bottom: 6em; }
		#adminmenu.large { margin-bottom: 8.5em; }
		<?php echo defensio_warning_style(); ?>
		#defensio_warning_controls_wrap { width:auto; margin-bottom:3px; display:none; }
		#defensio_warning p.defensio_error { color: red; }
		#defensio_warning p.defensio_success { color: green; }
		#defensio_progress_bar { width:300px; height:16px; border:1px solid black; padding:2px; float:left; margin-bottom:10px; }
		#defensio_progress_bar_value { width:0%; height:16px; background:blue; }
		#defensio_spinner { padding: 2px 10px 0 10px; float: left; }
		#defensio_start_processing { margin-left: 10px; clear:both; }
		#defensio_stop_processing { clear:both; }
	</style>
<?php
}
?>
