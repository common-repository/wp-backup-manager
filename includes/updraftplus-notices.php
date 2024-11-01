<?php

if (!defined('UPDRAFTPLUS_DIR')) die('No direct access allowed');

if (!class_exists('Updraft_Notices')) require_once(UPDRAFTPLUS_DIR.'/includes/updraft-notices.php');

class UpdraftPlus_Notices extends Updraft_Notices {

	protected static $_instance = null;

	private $initialized = false;

	protected $notices_content = array();
	
	protected $self_affiliate_id = 212;

	public static function instance() {
		if (empty(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	protected function populate_notices_content() {
		
		$parent_notice_content = parent::populate_notices_content();

		$child_notice_content = array(
			1 => array(),
			2 => array(),
			3 => array(),
			4 => array(),
			5 => array(),
			6 => array(),
			7 => array(),
			8 => array(),
			9 => array(),
			'translation_needed' => array(),
			'social_media' => array(),
			'newsletter' => array(),
			'subscribe_blog' => array(),
			'check_out_updraftplus_com' => array(),
			'autobackup' => array(),
			'wp-optimize' => array(),
			
			//The sale adverts content starts here
			'blackfriday' => array(),
			'christmas' => array(),
			'newyear' => array(),
			'spring' => array(),
			'summer' => array(),
			'2fa' => array(),
		);

		return array_merge($parent_notice_content, $child_notice_content);
	}
	
	// Call this method to setup the notices
	public function notices_init() {
		if ($this->initialized) return;
		$this->initialized = true;
		//parent::notices_init();
		$this->notices_content = (defined('UPDRAFTPLUS_NOADS_B') && UPDRAFTPLUS_NOADS_B) ? array() : $this->populate_notices_content();
		global $updraftplus;
		$our_version = @constant('SCRIPT_DEBUG') ? $updraftplus->version.'.'.time() : $updraftplus->version;
		wp_enqueue_style('updraftplus-notices-css',  UPDRAFTPLUS_URL.'/css/updraftplus-notices.css', array(), $our_version);
	}

	protected function translation_needed($plugin_base_dir = null, $product_name = null) {
		return parent::translation_needed(UPDRAFTPLUS_DIR, 'updraftplus');
	}
	
	protected function wp_optimize_installed($plugin_base_dir = null, $product_name = null) {
		$wp_optimize_file = false;
		if (!function_exists('get_plugins')) require_once(ABSPATH.'wp-admin/includes/plugin.php');
		$plugins = get_plugins();

		foreach ($plugins as $key => $value) {
			if ($value['TextDomain'] == 'wp-optimize') {
				return false;
			}
		}
		return true;
	}

	protected function clef_2fa_installed($plugin_base_dir = null, $product_name = null) {

		if (!function_exists('get_plugins')) require_once(ABSPATH.'wp-admin/includes/plugin.php');

		$plugins = get_plugins();
		$clef_found = false;

		foreach ($plugins as $key => $value) {
			if ('wpclef' == $value['TextDomain']) {
				$clef_found = true;
			} elseif ('two-factor-authentication' == $value['TextDomain'] || 'two-factor-authentication-premium' == $value['TextDomain']) {
				return false;
			}
		}

		return $clef_found;
		
	}
	
	protected function url_start($html_allowed = false, $url, $https = false, $website_home = 'updraftplus.com') {
		return parent::url_start($html_allowed, $url, $https, $website_home);
	}

	protected function skip_seasonal_notices($notice_data) {
		global $updraftplus;

		$time_now = defined('UPDRAFTPLUS_NOTICES_FORCE_TIME') ? UPDRAFTPLUS_NOTICES_FORCE_TIME : time();
		// Do not show seasonal notices to people with an updraftplus.com version and no-addons yet
		if (!file_exists(UPDRAFTPLUS_DIR.'/udaddons') || $updraftplus->have_addons) {
			$valid_from = strtotime($notice_data['valid_from']);
			$valid_to = strtotime($notice_data['valid_to']);
			$dismiss = $this->check_notice_dismissed($notice_data['dismiss_time']);
			if (($time_now >= $valid_from && $time_now <= $valid_to) && !$dismiss) {
				//return true so that we return this notice to be displayed
				return true;
			}
		}
		
		return false;
	}
	
	protected function check_notice_dismissed($dismiss_time){

		$time_now = defined('UPDRAFTPLUS_NOTICES_FORCE_TIME') ? UPDRAFTPLUS_NOTICES_FORCE_TIME : time(); 
	
		$notice_dismiss = ($time_now < UpdraftPlus_Options::get_updraft_option('dismissed_general_notices_until', 0));
		$seasonal_dismiss = ($time_now < UpdraftPlus_Options::get_updraft_option('dismissed_season_notices_until', 0));
		$autobackup_dismiss = ($time_now < UpdraftPlus_Options::get_updraft_option('updraftplus_dismissedautobackup', 0));

		$dismiss = false;

		if ('dismiss_notice' == $dismiss_time) $dismiss = $notice_dismiss;
		if ('dismiss_season' == $dismiss_time) $dismiss = $seasonal_dismiss;
		if ('dismissautobackup' == $dismiss_time) $dismiss = $autobackup_dismiss;

		return $dismiss;
	}

	protected function render_specified_notice($advert_information, $return_instead_of_echo = false, $position = 'top') {
	
		if ('bottom' == $position) {
			$template_file = 'bottom-notice.php';
		} elseif ('report' == $position) {
			$template_file = 'report.php';
		} elseif ('report-plain' == $position) {
			$template_file = 'report-plain.php';
		} else {
			$template_file = 'horizontal-notice.php';
		}
		
		/* 
			Check to see if the updraftplus_com_link filter is being used, if it's not then add our tracking to the link.
		*/
	
		if(!has_filter('updraftplus_com_link') && isset($advert_information['button_link']) && false !== strpos($advert_information['button_link'], '//updraftplus.com')) {
			$advert_information['button_link'] = trailingslashit($advert_information['button_link']).'?afref='.$this->self_affiliate_id;
		}

		require_once(UPDRAFTPLUS_DIR.'/admin.php');
		global $updraftplus_admin;
		return $updraftplus_admin->include_template('wp-admin/notices/'.$template_file, $return_instead_of_echo, $advert_information);
	}
}

$GLOBALS['updraftplus_notices'] = UpdraftPlus_Notices::instance();
