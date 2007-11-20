<?php
function defensio_render_quarantine_html($v) {
 	global $wp_version;
?>

<div class="wrap">
<div class="defensio_quarantine">
	<div class="defensio_header">
		<h2>Quarantine</h2>
		<a href="http://defensio.com"><img src="<?php echo $v['plugin_uri'] ?>/images/poweredbyd.png" /></a>    
	</div>
  
	<div class="defensio_spam">
		<div id="defensio_spam_sort">
			<form name = "defensio_spaminess_filter" method="post" action = "">
	        	<?php defensio_nonce_field($v['nonce']) ?> 
	        	Sort by:
<?php 
				if($v['order'] == 'spaminess') { 
?> 
					<strong>Spaminess</strong>
<?php 
				} else { 
?>
	         		<a href="<?php echo htmlspecialchars( add_query_arg( 'defensio_order', 'spaminess' ) ) ?>">Spaminess</a> 
<?php
				}
	         	echo " | ";

				if($v['order'] == 'date') {
?>
					<strong>Date</strong>
<?php
				} else {
?>
					<a href="<?php echo htmlspecialchars( add_query_arg( 'defensio_order', 'date' ) )  ?>" >Date</a> 
<?php
				}
?>

				<label for="defensio_hide_very_spam" />
				<input type="checkbox" id="defensio_hide_very_spam" name="defensio_hide_very_spam" <?php if ($v['spaminess_filter'] == '1' ) echo " checked=\"1\""; ?> onclick="javascript:this.form.submit();" /> Hide obvious spam (<?php echo $v['hidden_spam'] ?>)
<?php 
				if(isset($v)) { 
?>
					<input type="hidden" name="defensio_search_query" value="<?php echo $v['query']?>" />
<?php 
				}
?> 
				<input type="hidden" name="defensio_hide_very_spam_toggle" />     
			</form>
		</div>
    
		<div id="defensio_spam_search">
			<form method="post">
				<input type="text" name="defensio_search_query" value="<?php echo $v['query'] ?>"/> 
				<input class="defensio_button" type="submit" value="Search" style="float: none; display: inline;" />
			</form>
		</div>

<?php 
		if($v['spam_count'] < 1) { 
?>
			<p id="defensio_quarantine_empty">
        		Your quarantine is empty.
      
<?php 
				if($v['spaminess_filter'] and isset($v['hidden_spam']) and $v['hidden_spam'] > 0) {
?>
					However you are hiding <?php echo $v['hidden_spam'] ?> obvious spam messages.
<?php 
				}
?>
			</p>
<?php 
		} else {
?>
			<form id='spam_form' action="" method="post">
				<?php defensio_nonce_field($v['nonce']) ?>     
				<div class="defensio_pages"><?php echo defensio_render_page_navigation_links($v) ?></div>
				<ul class="defensio_comments">
					<li class='defensio_check_all'>
						<input type="checkbox" class="defensio_check_all" id="defensio_check_all_checkbox1" onClick="javascript:defensioCheckAll(this);"/>
						<label for="defensio_check_all_checkbox1">Check/Uncheck all</label>
					</li>
<?php
					$i = 0;
					foreach($v['comments'] as $spam_comment) {
						$i++;
?>
						<li class='defensio_<?php echo defensio_class_for_spaminess($spam_comment->spaminess)?>' id='defensio_comment_<?php echo $spam_comment->id ?>'>
							<input type="checkbox" name="defensio_comments[<?php echo($spam_comment->id) ?>]" id="defensio_comments[<?php echo $spam_comment->id ?>]" class="defensio_comment_checkbox" />
							<span class="defensio_comment_header">
								<?php echo($spam_comment->comment_author); ?>
<?php
								if ($spam_comment->comment_author_email != '') {
									echo '&nbsp;|&nbsp;<a href="mailto:' . $spam_comment->comment_author_email . '">' . $spam_comment->comment_author_email . '</a>';
								}
								if($spam_comment->comment_author_url && $spam_comment->comment_author_url != 'http://' ) { 
									echo "&nbsp;|&nbsp;<a class=\"defensio_author_url\" href=\"" . $spam_comment->comment_author_url . "\">" . $spam_comment->comment_author_url . "</a>";
								}
?>
							</span>
  
							<p class="defensio_body_shrunk" id="defensio_body_<?php echo $spam_comment->id ?>"><?php echo nl2br($spam_comment->comment_content) ?></p>
							<p class="defensio_comment_meta">
								<a class="defensio_quarantine_action" id="defensio_view_full_comment_<?php echo $spam_comment->id?>" href="#" onclick="javascript:defensio_toggle_height('<?php echo $spam_comment->id?>');return false;">View full comment</a> | 
								<a class="defensio_quarantine_action" href="#" onclick="javascript:setTimeout('$(\'defensio_comment_<?php echo $spam_comment->id ?>\').remove()', 1000); Fat.fade_element('defensio_comment_<?php echo $spam_comment->id ?>',30,1000,  '#70b94c'  , '#fff');    new Ajax.Request('<?php echo get_option('siteurl')."/wp-admin/admin-ajax.php" ?>', {parameters: { cookie: document.cookie, action: 'defensio-restore', ham: <?php echo $spam_comment->id ?> } });return false;">Restore comment</a> | 
								<?php echo("Spaminess: " . number_format($spam_comment->spaminess * 100, 0) . "% | "); ?>
								<?php echo($spam_comment->comment_date) . " | " ?>
								Post: <a href="<?php echo get_permalink($spam_comment->comment_post_ID); ?>" title="<?php echo $spam_comment->post_title; ?>"><?php echo $spam_comment->post_title; ?></a>
							</p>
						</li>
<?php 
					}
?>
					<li class='defensio_check_all'>
						<input type="checkbox" class="defensio_check_all" id="defensio_check_all_checkbox2" onClick="javascript:defensioCheckAll(this);"/>
						<label for="defensio_check_all_checkbox2">Check/Uncheck all</label>
					</li>
				</ul>

				<div class="defensio_pages"><?php echo defensio_render_page_navigation_links($v) ?></div>

				<div class="defensio_buttons">
					<input class="defensio_button" type="submit" value="Restore checked comments" name="defensio_restore" />
					<input class="defensio_button" type="submit" value="Delete all checked" name="defensio_delete" />
					<input class="defensio_button" type="submit" value="Empty quarantine"  name="defensio_empty_quarantine" />
				</div>
			</form> 
<?php
		}

		if($v['stats'] and $v['authenticated']) {
?>
			<h2>Statistics</h2>
			<div class="defensio_stats">
<?php 
				if(isset($v['stats']['learning']) and $v['stats']['learning'] == true ) {
?>
					<h3 class="defensio_learning"><?php echo $v['stats']['learning-status'] ?></h3>
<?php
				}
?>
					<ul>
						<li><strong>Recent accuracy: <?php echo number_format( $v['stats']['accuracy'] * 100, 2, '.', '')  ?>%</strong></li>
						<li><?php echo $v['stats']['spam']?> spam</li>
						<li><?php echo $v['stats']['ham']?> legitimate comments</li>
						<li><?php echo $v['stats']['false-negatives']?> false negatives (undetected spam)</li>
						<li><?php echo $v['stats']['false-positives']?> false positives (legitimate comments identified as spam)</li>
					</ul>
			</div>
			<div class="defensio_more_stats">
				<h3>There's more!</h3>
				<p>For more detailed statistics (and gorgeous charts), please visit your Defensio <a href="http://defensio.com/manage/stats/<?php echo $v['api_key']?>" target="_blank">Account Management</a> panel.</p>
			</div>
			<div style="clear:both"></div>
<?php
 		} else { 
?>
			<p>Statistics could not be retrieved, please check back later.</p>
<?php 
		}
?>
		</div>
	</div>
</div>
  
<?php
}


function defensio_show_page_number($page, $max, $current) {
	if($page == 1 or $page == 2 or $page == $max or $page == $max-1) { return true; }
	elseif($current - 2 <= $page and $page  <= $current +2) { return true; }
	return false;
}

function defensio_render_page_navigation_links($v) {
	$output = '';
	$defensio_order = trim($v['order']);
	$e1 = true;
	$e2 = true;
     
	if ($v['current_page'] > 1) {
		$previous_page = $v['current_page'] - 1; 
		$output .= "<a class=\"prev\" href=\" " . htmlspecialchars( add_query_arg(array('defensio_order' => $defensio_order, 'defensio_page' => $previous_page, 'defensio_search_query' => $v['query'])) ) . "\">&laquo; Previous Page</a>&nbsp;&nbsp;";
	}
 
	if ($v['pages_count'] > 2) {
		$i = 1;
		while ($i <= $v['pages_count']) {
			if(defensio_show_page_number($i, $v['pages_count'], $v['current_page'])) {
				if ($i != $v['current_page']) {
					$output .= " <a class=\"page-numbers\" href=\"" . htmlspecialchars( add_query_arg ( array('defensio_order' => $defensio_order, 'defensio_page' => $i, 'defensio_search_query' => $v['query'] )) ) . "\">  $i </a>  ";
				} else {
			    $output .= "<strong class=\"page-numbers\">$i</strong>";
				}
			} else {
				if ($e1 and $i < $v['current_page']) { 
					$output .= '...';
					$e1=false;
				}

				if($e2 and $i > $v['current_page']) {
					$output .= '...';
					$e2=false;
				}            
			}
			$i++;    
		} // while
	} // if
   
	if($v['current_page'] < $v['pages_count'] ) {
		$next = $v['current_page']+1;
		$output .= "<a class=\"next\" href=\"" . htmlspecialchars(add_query_arg (array('defensio_order' => $defensio_order, 'defensio_page' => $next, 'defensio_search_query' => $v['query']))) . "\" >&nbsp;&nbsp;Next Page &raquo;</a> ";
	}

	return $output; 
}


function defensio_class_for_spaminess($spaminess) {
	if($spaminess <= 0.55)
		return 'spam0';
	elseif($spaminess <= 0.65)
		return 'spam1';
	elseif($spaminess <= 0.70)
		return 'spam2';
	elseif($spaminess <= 0.75)
		return 'spam3';
	elseif($spaminess <= 0.80)
		return 'spam4';
	elseif($spaminess <= 0.85)
		return 'spam5';
	elseif($spaminess <= 0.90)
		return 'spam6';
	elseif($spaminess <= 0.95)
		return 'spam7';
	elseif($spaminess < 1)
		return 'spam8';
	else
		return 'spam9';
}
?>
