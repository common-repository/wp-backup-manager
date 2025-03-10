<?php

if (!defined('UPDRAFTPLUS_DIR')) die('No direct access allowed');

$updraft_dir = $updraftplus->backups_dir_location();
$really_is_writable = $updraftplus->really_is_writable($updraft_dir);

// $options is passed through
$default_options = array(
	'include_database_decrypter' => true,
	'include_adverts' => true,
	'include_save_button' => true
);

foreach ($default_options as $k => $v) {
	if (!isset($options[$k])) $options[$k] = $v;
}

?>
<table class="form-table">
	<tr>
		<th><?php _e('Files backup schedule','updraftplus'); ?>:</th>
		<td>
			<div style="float:left; clear:both;">
				<select class="updraft_interval" name="updraft_interval">
				<?php
				$intervals = $updraftplus_admin->get_intervals();
				$selected_interval = UpdraftPlus_Options::get_updraft_option('updraft_interval', 'manual');
				foreach ($intervals as $cronsched => $descrip) {
					echo "<option value=\"$cronsched\" ";
					if ($cronsched == $selected_interval) echo 'selected="selected"';
					echo ">".htmlspecialchars($descrip)."</option>\n";
				}
				?>
				</select> <span class="updraft_files_timings"><?php echo apply_filters('updraftplus_schedule_showfileopts', '<input type="hidden" name="updraftplus_starttime_files" value="">', $selected_interval); ?></span>
			

				<?php

					$updraft_retain = max((int)UpdraftPlus_Options::get_updraft_option('updraft_retain', 2), 1);

					$retain_files_config = __('and retain this many scheduled backups', 'updraftplus').': <input type="number" min="1" step="1" name="updraft_retain" value="'.$updraft_retain.'" class="retain-files" />';

// 							echo apply_filters('updraftplus_retain_files_intervalline', $retain_files_config, $updraft_retain);
					echo $retain_files_config;

				?>
			</div>
			<?php do_action('updraftplus_after_filesconfig'); ?>
		</td>
	</tr>

	<?php if (defined('UPDRAFTPLUS_EXPERIMENTAL') && UPDRAFTPLUS_EXPERIMENTAL) { ?>
	<tr class="updraft_incremental_row">
		<th><?php _e('Incremental file backup schedule', 'updraftplus'); ?>:</th>
		<td>
			<?php do_action('updraftplus_incremental_cell', $selected_interval); ?>
			<a href="<?php echo apply_filters('updraftplus_com_link', "https://updraftplus.com/support/tell-me-more-about-incremental-backups/");?>"><em><?php _e('Tell me more about incremental backups', 'updraftplus'); ?><em></a>
			</td>
	</tr>
	<?php } ?>

	<?php apply_filters('updraftplus_after_file_intervals', false, $selected_interval); ?>
	<tr>
		<th>
			<?php _e('Database backup schedule','updraftplus'); ?>:
		</th>
		<td>
		<div style="float:left; clear:both;">
			<select class="updraft_interval_database" name="updraft_interval_database">
			<?php
			$selected_interval_db = UpdraftPlus_Options::get_updraft_option('updraft_interval_database', UpdraftPlus_Options::get_updraft_option('updraft_interval'));
			foreach ($intervals as $cronsched => $descrip) {
				echo "<option value=\"$cronsched\" ";
				if ($cronsched == $selected_interval_db) echo 'selected="selected"';
				echo ">$descrip</option>\n";
			}
			?>
			</select> <span class="updraft_same_schedules_message"><?php echo apply_filters('updraftplus_schedule_sametimemsg', '');?></span><span class="updraft_db_timings"><?php echo apply_filters('updraftplus_schedule_showdbopts', '<input type="hidden" name="updraftplus_starttime_db" value="">', $selected_interval_db); ?></span>

			<?php
				$updraft_retain_db = max((int)UpdraftPlus_Options::get_updraft_option('updraft_retain_db', $updraft_retain), 1);
				$retain_dbs_config = __('and retain this many scheduled backups', 'updraftplus').': <input type="number" min="1" step="1" name="updraft_retain_db" value="'.$updraft_retain_db.'" class="retain-files" />';

// 						echo apply_filters('updraftplus_retain_db_intervalline', $retain_dbs_config, $updraft_retain_db);
				echo $retain_dbs_config;
			?>
			</div>
			<?php do_action('updraftplus_after_dbconfig'); ?>
		</td>
	</tr>
	<tr class="backup-interval-description">
		<th></th>
		<td><div>
		<?php
			echo apply_filters('updraftplus_fixtime_ftinfo', '<p>'.__('To fix the time at which a backup should take place,','updraftplus').' ('.__('e.g. if your server is busy at day and you want to run overnight','updraftplus').'), '.__('or to configure more complex schedules', 'updraftplus').'. </p>'); 
		?>
		</div></td>
	</tr>
</table>

<h2 class="updraft_settings_sectionheading"><?php _e('Sending Your Backup To Remote Storage','updraftplus');?></h2>

<?php
	$debug_mode = UpdraftPlus_Options::get_updraft_option('updraft_debug_mode') ? 'checked="checked"' : "";
	$active_service = UpdraftPlus_Options::get_updraft_option('updraft_service');
?>

<table class="form-table width-900">
	<tr>

		<td>
		<div id="remote-storage-container">
		<?php
			if (is_array($active_service)) $active_service = $updraftplus->just_one($active_service);
			

		?>
		
		
		<?php 
			if (false === apply_filters('updraftplus_storage_printoptions', false, $active_service)) {
				echo '</div>';
		}
		?>
		
		</td>
	</tr>
		
	<?php
		$method_objects = array();
		foreach ($updraftplus->backup_methods as $method => $description) {
		
			require_once(UPDRAFTPLUS_DIR.'/methods/'.$method.'.php');
			
			$call_method = 'UpdraftPlus_BackupModule_'.$method;
			
			if (class_exists($call_method)) {
			
				$remote_storage = new $call_method;
				$method_objects[$method] = $remote_storage;
				
				if ($remote_storage->supports_feature('multi_options')) {
				
					$settings = UpdraftPlus_Options::get_updraft_option('updraft_'.$method);
					
					if (!is_array($settings)) $settings = array();
				
					if (!isset($settings['version'])) $settings = $updraftplus->update_remote_storage_options_format($method);
					
					if (is_wp_error($settings)) {
						error_log("UpdraftPlus: failed to convert storage options format: $method");
						$settings = array('settings' => array());
					}

					if (empty($settings['settings'])) {
						// See: https://wordpress.org/support/topic/cannot-setup-connectionauthenticate-with-dropbox/
						error_log("UpdraftPlus: Warning: settings for $method are empty. A dummy field is usually needed so that something is saved.");
						
						// Try to recover by getting a default set of options for display
						if (is_callable(array($remote_storage, 'get_default_options'))) {
							$uuid = 's-'.md5(rand().uniqid().microtime(true));
							$settings['settings'] = array($uuid => $remote_storage->get_default_options());
						}
						
					}

					if (!empty($settings['settings'])) {
						foreach ($settings['settings'] as $instance_id => $storage_options) {
							$remote_storage->set_options($storage_options, false, $instance_id);
							do_action('updraftplus_config_print_before_storage', $method, $remote_storage);
							$remote_storage->print_configuration();
						}
					}
				
				} else {
				
					do_action('updraftplus_config_print_before_storage', $method, null);
				
					$remote_storage->config_print();
					
				}
				
				do_action('updraftplus_config_print_after_storage', $method);
			} else {
				error_log("UpdraftPlus: no such storage class: $call_method");
			} 
		}
	?>

</table>

<hr style="width:900px; float:left;">

<h2 class="updraft_settings_sectionheading"><?php _e('File Options', 'updraftplus');?></h2>

<table class="form-table" >
	<tr>
		<th><?php _e('Include in files backup', 'updraftplus');?>:</th>
		<td>
			<?php echo $updraftplus_admin->files_selector_widgetry(); ?>
			
		</td>
	</tr>
</table>

<h2 class="updraft_settings_sectionheading"><?php _e('Database Options','updraftplus');?></h2>

<table class="form-table width-900">


	<?php if (!empty($options['include_database_decrypter'])) { ?>
	
	<tr class="backup-crypt-description">
		<td></td>

		<td>
		<div id="updraft-manualdecrypt-modal" class="updraft-hidden" style="display:none;">
			<p><h3><?php _e("Manually decrypt a database backup file" ,'updraftplus');?></h3></p>

			<?php
			global $wp_version;
			if (version_compare($wp_version, '3.3', '<')) {
				echo '<em>'.sprintf(__('This feature requires %s version %s or later', 'updraftplus'), 'WordPress', '3.3').'</em>';
			} else {
			?>

			<div id="plupload-upload-ui2">
				<div id="drag-drop-area2">
					<div class="drag-drop-inside">
						<p class="drag-drop-info"><?php _e('Drop encrypted database files (db.gz.crypt files) here to upload them for decryption', 'updraftplus'); ?></p>
						<p><?php _ex('or', 'Uploader: Drop db.gz.crypt files here to upload them for decryption - or - Select Files', 'updraftplus'); ?></p>
						<p class="drag-drop-buttons"><input id="plupload-browse-button2" type="button" value="<?php esc_attr_e('Select Files', 'updraftplus'); ?>" class="button" /></p>
						<p style="margin-top: 18px;"><?php _e('First, enter the decryption key','updraftplus')?>: <input id="updraftplus_db_decrypt" type="text" size="12"></input></p>
					</div>
				</div>
				<div id="filelist2">
				</div>
			</div>

			<?php } ?>

		</div>
		
		<?php
			$plugins = get_plugins();
			$wp_optimize_file = false;

			foreach ($plugins as $key => $value) {
				if ($value['TextDomain'] == 'wp-optimize') {
					$wp_optimize_file = $key;
					break;
				}
			}
			
			if (!$wp_optimize_file) {
				?><br>
				<?php
			}
		?>
		



		</td>
	</tr>
	
	<?php } ?>

	<?php
		#'<a href="https://updraftplus.com/shop/updraftplus-premium/">'.__("This feature is part of UpdraftPlus Premium.", 'updraftplus').'</a>'
		$moredbs_config = apply_filters('updraft_database_moredbs_config', false);
		if (!empty($moredbs_config)) {
	?>

	<tr>
		<th><?php _e('Back up more databases', 'updraftplus');?>:</th>
		<td><?php echo $moredbs_config; ?>
		</td>
	</tr>

<?php } ?>

</table>

<h2 class="updraft_settings_sectionheading"><?php _e('Reporting','updraftplus');?></h2>

<table class="form-table" style="width:900px;">

<?php
	$report_rows = apply_filters('updraftplus_report_form', false);
	if (is_string($report_rows)) {
		echo $report_rows;
	} else {
?>

	<tr>
		<th><?php _e('Email', 'updraftplus'); ?>:</th>
		<td>
			<?php
				$updraft_email = UpdraftPlus_Options::get_updraft_option('updraft_email');
			?>
			<input type="checkbox" id="updraft_email" name="updraft_email" value="<?php esc_attr_e(get_bloginfo('admin_email')); ?>"<?php if (!empty($updraft_email)) echo ' checked="checked"';?> > <br><label for="updraft_email"><?php echo __("Check this box to have a basic report sent to", 'updraftplus').' <a href="'.admin_url('options-general.php').'">'.__("your site's admin address", 'updraftplus').'</a> ('.htmlspecialchars(get_bloginfo('admin_email')).")."; ?></label>
			<?php
				
			?>
		</td>
	</tr>

<?php } ?>

</table>

<script type="text/javascript">
/* <![CDATA[ */
<?php echo $updraftplus_admin->get_settings_js($method_objects, $really_is_writable, $updraft_dir, $active_service); ?>
/* ]]> */
</script>
<table class="form-table width-900">
	<tr>
		<td colspan="2"><h2 class="updraft_settings_sectionheading"><?php _e('Advanced / Debugging Settings','updraftplus'); ?></h2></td>
	</tr>

	<tr>
		<th><?php _e('Expert settings','updraftplus');?>:</th>
		<td><a class="enableexpertmode" href="#enableexpertmode"><?php _e('Show expert settings','updraftplus');?></a> - <?php _e("click this to show some further options; don't bother with this unless you have a problem or are curious.",'updraftplus');?> <?php do_action('updraftplus_expertsettingsdescription'); ?></td>
	</tr>
	<?php
	$delete_local = UpdraftPlus_Options::get_updraft_option('updraft_delete_local', 1);
	$split_every_mb = UpdraftPlus_Options::get_updraft_option('updraft_split_every', 400);
	if (!is_numeric($split_every_mb)) $split_every_mb = 400;
	if ($split_every_mb < UPDRAFTPLUS_SPLIT_MIN) $split_every_mb = UPDRAFTPLUS_SPLIT_MIN;
	?>

	<tr class="expertmode updraft-hidden" style="display:none;">
		<th><?php _e('Debug mode','updraftplus');?>:</th>
		<td><input type="checkbox" id="updraft_debug_mode" name="updraft_debug_mode" value="1" <?php echo $debug_mode; ?> /> <br><label for="updraft_debug_mode"><?php _e('Check this to receive more information and emails on the backup process - useful if something is going wrong.','updraftplus');?> <?php _e('This will also cause debugging output from all plugins to be shown upon this screen - please do not be surprised to see these.', 'updraftplus');?></label></td>
	</tr>

	<tr class="expertmode updraft-hidden" style="display:none;">
		<th><?php _e('Split archives every:','updraftplus');?></th>
		<td><input type="text" name="updraft_split_every" class="updraft_split_every" value="<?php echo $split_every_mb ?>" size="5" /> MB<br><?php echo sprintf(__('WP Backup Manager will split up backup archives when they exceed this file size. The default value is %s megabytes. Be careful to leave some margin if your web-server has a hard size limit (e.g. the 2 GB / 2048 MB limit on some 32-bit servers/file systems).','updraftplus'), 400); ?></td>
	</tr>

	<tr class="deletelocal expertmode updraft-hidden" style="display:none;">
		<th><?php _e('Delete local backup','updraftplus');?>:</th>
		<td><input type="checkbox" id="updraft_delete_local" name="updraft_delete_local" value="1" <?php if ($delete_local) echo 'checked="checked"'; ?>> <br><label for="updraft_delete_local"><?php _e('Check this to delete any superfluous backup files from your server after the backup run finishes (i.e. if you uncheck, then any files despatched remotely will also remain locally, and any files being kept locally will not be subject to the retention limits).','updraftplus');?></label></td>
	</tr>

	<tr class="expertmode backupdirrow updraft-hidden" style="display:none;">
		<th><?php _e('Backup directory','updraftplus');?>:</th>
		<td><input type="text" name="updraft_dir" id="updraft_dir" style="width:525px" value="<?php echo htmlspecialchars($updraftplus_admin->prune_updraft_dir_prefix($updraft_dir)); ?>" /></td>
	</tr>
	<tr class="expertmode backupdirrow updraft-hidden" style="display:none;">
		<td></td>
		<td>
			<span id="updraft_writable_mess">
				<?php
				$dir_info = $updraftplus_admin->really_writable_message($really_is_writable, $updraft_dir);
				echo $dir_info;
				?>
			</span>
				<?php
					echo __("This is where WP Backup Manager will write the zip files it creates initially.  This directory must be writable by your web server. It is relative to your content directory (which by default is called wp-content).", 'updraftplus').' '.__("<b>Do not</b> place it inside your uploads or plugins directory, as that will cause recursion (backups of backups of backups of...).", 'updraftplus');
					?>
			</td>
	</tr>

	<tr class="expertmode updraft-hidden" style="display:none;">
		<th><?php _e("Use the server's SSL certificates", 'updraftplus');?>:</th>
		<td><input data-updraft_settings_test="useservercerts" type="checkbox" id="updraft_ssl_useservercerts" name="updraft_ssl_useservercerts" value="1" <?php if (UpdraftPlus_Options::get_updraft_option('updraft_ssl_useservercerts')) echo 'checked="checked"'; ?>> <br><label for="updraft_ssl_useservercerts"><?php _e('By default WP Backup Manager uses its own store of SSL certificates to verify the identity of remote sites (i.e. to make sure it is talking to the real Dropbox, Amazon S3, etc., and not an attacker). We keep these up to date. However, if you get an SSL error, then choosing this option (which causes WP Backup Manager to use your web server\'s collection instead) may help.','updraftplus');?></label></td>
	</tr>

	<tr class="expertmode updraft-hidden" style="display:none;">
		<th><?php _e('Do not verify SSL certificates','updraftplus');?>:</th>
		<td><input data-updraft_settings_test="disableverify" type="checkbox" id="updraft_ssl_disableverify" name="updraft_ssl_disableverify" value="1" <?php if (UpdraftPlus_Options::get_updraft_option('updraft_ssl_disableverify')) echo 'checked="checked"'; ?>> <br><label for="updraft_ssl_disableverify"><?php _e('Choosing this option lowers your security by stopping WP Backup Manager from verifying the identity of encrypted sites that it connects to (e.g. Dropbox, Google Drive). It means that WP Backup Manager will be using SSL only for encryption of traffic, and not for authentication.','updraftplus');?> <?php _e('Note that not all cloud backup methods are necessarily using SSL authentication.', 'updraftplus');?></label></td>
	</tr>

	<tr class="expertmode updraft-hidden" style="display:none;">
		<th><?php _e('Disable SSL entirely where possible', 'updraftplus');?>:</th>
		<td><input data-updraft_settings_test="nossl" type="checkbox" id="updraft_ssl_nossl" name="updraft_ssl_nossl" value="1" <?php if (UpdraftPlus_Options::get_updraft_option('updraft_ssl_nossl')) echo 'checked="checked"'; ?>> <br><label for="updraft_ssl_nossl"><?php _e('Choosing this option lowers your security by stopping WP Backup Manager from using SSL for authentication and encrypted transport at all, where possible. Note that some cloud storage providers do not allow this (e.g. Dropbox), so with those providers this setting will have no effect.','updraftplus');?> </label></td>
	</tr>

	<?php do_action('updraftplus_configprint_expertoptions'); ?>

	<tr>
		<td></td>
		<td>
			<?php
				if (!empty($options['include_adverts'])) {
					if (!class_exists('UpdraftPlus_Notices')) require_once(UPDRAFTPLUS_DIR.'/includes/updraftplus-notices.php');
					global $updraftplus_notices;
					$updraftplus_notices->do_notice(false, 'bottom'); 
				}
			?>
		</td>
	</tr>
	
	<?php if (!empty($options['include_save_button'])) { ?>
	<tr>
		<td></td>
		<td>
			<input type="hidden" name="action" value="update" />
			<input type="submit" class="button-primary" id="updraftplus-settings-save" value="<?php _e('Save Changes','updraftplus');?>" />
		</td>
	</tr>
	<?php } ?>
</table>
