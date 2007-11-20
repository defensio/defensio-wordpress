<?php
function defensio_render_configuration_html($v) {
	global $wp_version;
?>

<div class='wrap'>
  <h2>Defensio Configuration</h2>
  <div class="narrow" >
 
<?php 
	if($v['hckey']) {
?>
		<h3><label for="new_key">Defensio API Key</label></h3>
		<p>Your Defensio API Key is hardcoded and cannot be changed here.</p> 
		<?php defensio_render_key_validity($v) ?>
		<input type="text" value="<?php echo $v['key']?>" disabled="1" />
		<form action="plugins.php?page=defensio-config" method="post" >
		<?php defensio_nonce_field($v['nonce']) ?> 
		<?php defensio_render_spaminess_threshold_option($v['threshold']); ?>
		<?php defensio_render_delete_older_than_option($v); ?>
		<input type="submit" value="Change options" />
		</form>
<?php 
	}
	else {
		if(!isset($v['key']) or $v['key'] == null ) { 
			if($wp_version < 2.1 and !( substr( $wp_version, 'wordpress-mu') )):
?>
				<h3>Unsupported version</h3>
				<p> We are sorry, Defensio can only be installed on WordPress  2.1 or newer. We encourage you to <a href="http://wordpress.org/download/">upgrade</a> to enjoy a spam free blogging experience with Defensio.</p>
<?php
			else: 
?>
				<h3>Warning</h3>
				<p>No API Key has been specified, do you want to specify one now?</p>
        
				<form action="plugins.php?page=defensio-config" method="post" >
				  <input type="text" name="new_key" size="32" />
				  <?php defensio_nonce_field($v['nonce']) ?>
				  <?php defensio_render_spaminess_threshold_option($v['threshold']); ?>  
				  <input type="submit" value="Change options" /> 
				</form>
<?php 
			endif;
		} else { 
?>
			<form action="plugins.php?page=defensio-config" method="post" style="margin:auto; width: 400px; ">
				<?php defensio_nonce_field($v['nonce']) ?>  
				<p><a href="http://www.defensio.com">Defensio</a>'s blog spam web service aggressively and intelligently prevents comment and trackback spam from hitting your blog. You should quickly notice a dramatic reduction in the amount of spam you have to worry about.</p>
				<p>When the filter does rarely make a mistake (say, the odd spam message gets through or a rare good comment is marked as spam) we've made it a joy to sort through your comments and set things straight. Not only will the filter learn and improve over time, but it will do so in a personalized way!</p>
				<p>In order to use our service, you will need a <strong>free</strong> Defensio API key.  Get yours now at <a href="http://www.defensio.com/signup">Defensio.com</a>.</p>

				<h3>Defensio API Key</h3>
				<?php defensio_render_key_validity($v) ?>                        
				<input type="text" value="<?php echo $v['key']?>" name="new_key" size="32" />
				<br /><br />
				<?php defensio_render_spaminess_threshold_option($v['threshold']); ?>
     
				<h3><label>Automatic removal of spam</label></h3>
				<?php defensio_render_delete_older_than_option($v); ?>

				<input type="submit" value="Change options">
			</form>
<?php 
		} 
	} 
?>
	</div> 
</div>
<?php
}

function defensio_render_key_validity($v) {
	if($v['valid']) { 
?>
		<p style="padding: .5em; background-color: #2d2; color: #fff;font-weight: bold;">This key is valid.</p>
<?php 
	} else { 
?>
		<p style="padding: .5em; background-color: #d22; color: #fff; font-weight: bold;">The key you entered is invalid.</p>
<?php 
	}
}


function defensio_render_spaminess_threshold_option($threshold) {
	global $v;
	$threshold_values = array(50, 60, 70 , 80 , 90 , 95);
?>
	<h3><label for="new_threshold">Obvious Spam Threshold</label></h3>
	<p>Hide comments with a spaminess higher than&nbsp;
	<select name="new_threshold" >
<?php 
	foreach($threshold_values as $val): 
?>
<?php 
		if($val == $threshold ): 
?>
			<option selected="1" ><?php echo $val ?></option>
<?php 
		else: 
?>
			<option>
<?php 
			echo $val 
?>
			</option>
<?php 
		endif; 
	endforeach; 
?>
	</select> %</p>
	<p>Any comments calculated to be above or equal to this "spaminess" threshold will be hidden from view in your quarantine.</p>
<?php 
}


function defensio_render_delete_older_than_option($v) { ?>
	<p>
<?php
		if($v['remove_older_than_error']) {
?>
			<div style="color:red">
				<?php  echo $v['remove_older_than_error'] ;?>
			</div>
<?php  
		}
?>

		<input type="hidden" name="defensio_remove_older_than_toggle" />
		<input type="checkbox" name="defensio_remove_older_than" <?php if($v['remove_older_than'] == 1) { echo 'checked="1"'; } ?> size="3" maxlength="3"/>
		Automatically delete spam for articles older than <input type="text" name="defensio_remove_older_than_days" value="<?php echo $v['remove_older_than_days'] ?>" size="3" maxlength="3"/> days.
	</p>
<?php
}
?>