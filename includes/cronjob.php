<?php

    if( ! class_exists('{NICK}_CRAWLER_CRONJOB') && class_exists('{NICK}_CRAWLER_IMPLEMENT') ){

        class {NICK}_CRAWLER_CRONJOB{

            public $implement;
            public $crawl_action;
            public $upload_cloud_action;
            public $js_func;

            public function __construct(){

                $this->implement = new {NICK}_CRAWLER_IMPLEMENT();
                $this->crawl_action = "__{$this->implement->sname}_crawler_cronjob_action";
                $this->upload_cloud_action = "__{$this->implement->sname}_upload_cloud_cronjob_action";
                $this->js_func = "{$this->implement->sname}_cj_run";

                add_filter( 'cron_schedules', array($this, '_add_cron_interval' ));
            }
			
			function _add_cron_interval( $schedules ) { 
				$settings = {NICK}_CRAWLER_HELPERS::get_crawler_settings();
				
				$schedules['crawl_interval'] = array(
					'interval' => $settings['cronjob']['recurrence'] * 60,
					'display'  => __( 'Run every ' . $settings['cronjob']['recurrence'] . ' minutes. Configured in Crawler Settings' ) );
					
				$schedules['crawl_update_interval'] = array(
					'interval' => 3 * 60 * 60, // 3 hours
					'display'  => __( 'Run every 3 hours to check for new updates' ) );
					
				return $schedules;
			}
        }

        new {NICK}_CRAWLER_CRONJOB();
		
		function {SITENAME}_activation() {
			if (! wp_next_scheduled ( '{NICKLOWER}_crawler_upload_cloud' )) {
				wp_schedule_event(time(), 'crawl_interval', '{NICKLOWER}_crawler_upload_cloud');
			}
			
			if (! wp_next_scheduled ( '{NICKLOWER}_crawler_event' )) {
				wp_schedule_event(time(), 'crawl_interval', '{NICKLOWER}_crawler_event');
			}
			
			if (! wp_next_scheduled ( '{NICKLOWER}_crawler_update_event' )) {
				
				wp_schedule_event(time(), 'crawl_update_interval', '{NICKLOWER}_crawler_update_event');
			}
			
			if (! wp_next_scheduled ( '{NICKLOWER}_crawler_fetch_event' )) {
				
				wp_schedule_event(time(), 'crawl_interval', '{NICKLOWER}_crawler_fetch_event');
			}
		}
		 
		add_action('{NICKLOWER}_crawler_event', '{NICKLOWER}_do_crawl');
		add_action('{NICKLOWER}_crawler_update_event', '{NICKLOWER}_check_updates');
		add_action('{NICKLOWER}_crawler_fetch_event', '{NICKLOWER}_fetch_queue');
		add_action('{NICKLOWER}_crawler_upload_cloud', '{NICKLOWER}_crawler_do_upload_cloud');
		 
		function {NICKLOWER}_do_crawl() {
			$settings = {NICK}_CRAWLER_HELPERS::get_crawler_settings();
			
			if($settings['active']){
				$crawler = new {NICK}_CRAWLER_IMPLEMENT();
				$crawler->crawl();
			}
		}
		
		function {NICKLOWER}_fetch_queue() {
			$settings = {NICK}_CRAWLER_HELPERS::get_crawler_settings();
			if($settings['active']){
				$crawler = new {NICK}_CRAWLER_IMPLEMENT();
				$crawler->fetch_manga();
			}
		}
		
		function {NICKLOWER}_check_updates() {
			$settings = {NICK}_CRAWLER_HELPERS::get_crawler_settings();
			if($settings['update']){
				$crawler = new {NICK}_CRAWLER_IMPLEMENT();
				$crawler->update_latest_manga();
				
				$crawler->update_status( 'next_update_latest_manga', time() + 3 * 60 * 60 );
			}
		}
		
		function {NICKLOWER}_crawler_do_upload_cloud() {
			$settings = {NICK}_CRAWLER_HELPERS::get_crawler_settings();
			if(($settings['active'] || $settings['update']) && $settings['import']['storage'] != 'local'){
				$crawler = new {NICK}_CRAWLER_IMPLEMENT();
				$crawler->upload_cloud();
			}
		}		
    }
