<?php
function defensio_render_html_head($v) {
?>
<script type="text/javascript" src="<?php echo $v['plugin_uri'] ?>lib/prototype.js"></script>
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
  
  div.defensio_spam div#defensio_spam_sort {
    clear: both;
    float: left;
    width: 50%;
    margin-bottom: 15px;  
    height: 30px;
  }

  div.defensio_spam div#defensio_spam_sort label {
    margin-left: 60px;
  }

  div.defensio_spam div#defensio_spam_search {
    float: right;
    width: 45%;
    text-align: right;
    margin-bottom: 15px;
    height: 30px;
  }
  
  div.defensio_spam p#defensio_quarantine_empty {
    margin-bottom: 30px;
    clear: both;
  }

  div.defensio_spam ul.defensio_comments {
    list-style: none;
    padding: 0;
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
    margin: 10px 0 10px 0;    
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
?>
