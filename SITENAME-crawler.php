<?php

	/*
		Plugin Name: {SITENAME} Manga Crawler
		Plugin URI: https://{SITEURL}
		Description: Automatic crawl from {SITEURL} ({SITENAME})
		Version: 1.0.0
		Author: pooper
		Author URI: https://{SITEURL}
	*/

	if ( ! defined( 'WP_MCL_{NICK}_PATH' ) ) {
		define( 'WP_MCL_{NICK}_PATH', plugin_dir_path( __FILE__ ) );
	}

	if ( ! defined( 'WP_MCL_{NICK}_URL' ) ) {
		define( 'WP_MCL_{NICK}_URL', plugin_dir_url( __FILE__ ) );
	}

	if( ! defined( 'WP_MCL_TD' ) ){
		define( 'WP_MCL_TD', 'madara' );
	}

	if ( ! class_exists( '{NICK}_CRAWLER_IMPLEMENT' ) ) {

		class WP_MANGA_{NICK}_CRAWLER {

			public function __construct() {
				$this->init();
				$this->hooks();
			}

			private function hooks(){
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			}

			private function init(){

				if( ini_get('max_execution_time') < 600 ){
					ini_set('max_execution_time', 600);
				}

				if( ini_get('max_input_time') < 600 ){
					ini_set('max_input_time', 600);
				}

				if( !function_exists( 'file_get_html' ) ){
					require_once WP_MCL_{NICK}_PATH . 'libs/simplehtmldom_1_5/simple_html_dom.php';
				}

				$includes = array(
					'includes' => array(
						'helper',
						'JavaScriptUnpacker',
						'import',
						'crawl',
						'implement' => array(
							'helper',
							'implement',
							'crawl-single'
						),
						'cronjob',
						'settings',
						'debug'
					)
				);

				foreach( $includes as $dir => $files ){
					foreach( $files as $index => $file ){
						if( is_array( $file ) ){
							foreach( $file as $f ){
								require_once( WP_MCL_{NICK}_PATH . "{$dir}/{$index}/{$f}.php" );
							}
						}else{
							require_once( WP_MCL_{NICK}_PATH . "{$dir}/{$file}.php" );
						}
					}
				}	
			}

			public function admin_enqueue_scripts() {
				if( class_exists('{NICK}_CRAWLER_IMPLEMENT') && {NICK}_CRAWLER_HELPERS::is_settings_page() ){
					wp_enqueue_style( 'wp-crawler-style', WP_MCL_{NICK}_URL . 'assets/css/admin.css' );

					if( isset( $_GET['tab'] ) && $_GET['tab'] == 'crawl-progress' ){
						wp_enqueue_script( '{NICKLOWER}-crawler-task', WP_MCL_{NICK}_URL . 'assets/js/{NICKLOWER}-crawler-task.js', array( 'jquery' ) );
					}
				}
			}


		}

		require_once('admin/settings-page.php');

	     $license_key = get_option(MDR_{NICK}_CRAWLER_LICENSE_KEY);
	     if ($license_key) {
	        $WP_MANGA_{NICK}_CRAWLER = new WP_MANGA_{NICK}_CRAWLER();
			register_activation_hook(__FILE__, '{SITENAME}_activation');
	     } else {
	        add_action('admin_notices', 'madara_{SITENAME}_crawler_admin_notice__warning');
	     }
	}
