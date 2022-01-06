<?php


	class {SITENAMECAPITAL}_SETTINGS{

		public function __construct(){

			add_action( 'admin_menu', array( $this, 'register_menu' ) );
			add_action( 'admin_init', array( $this, 'save_settings' ) );

			add_action( 'wp_ajax_{NICKLOWER}_crawler_active_task', array( $this, 'active_task' ) );
			add_action( 'wp_ajax_{NICKLOWER}_crawler_deactive_task', array( $this, 'deactive_task' ) );

			add_action( 'wp_ajax_{NICKLOWER}_refresh_list', array( $this, 'refresh_list' ) );

			add_action( 'wp_ajax_{NICKLOWER}_get_update_log', array( $this, 'get_update_log' ) );

			add_action( 'wp_ajax_{NICKLOWER}_get_crawler_log', array( $this, 'get_crawler_log' ) );

			add_action( '{NICKLOWER}_listing_manga', array( $this, 'listing_manga' ) );

			register_activation_hook( WP_MCL_{NICK}_PATH . '/{SITENAME}-crawler.php', array( $this, 'upgrade_handler' ) );

		}

		public function register_menu(){
			if( class_exists('{NICKLOWER}_CRAWLER_IMPLEMENT') ){
				add_menu_page(
					esc_html__( '{SITENAME} Manga Crawler Settings', 'madara' ),
					esc_html__( '{SITENAME} Crawler', 'madara' ),
					'manage_options',
					'{SITENAME}-manga-crawler-settings',
					function(){
						{NICK}_CRAWLER_HELPERS::get_template('settings');
					},
					'dashicons-layout'
				);
			}
		}

		public function save_settings(){

			if(
				class_exists('{NICK}_CRAWLER_HELPERS')
				&& {NICK}_CRAWLER_HELPERS::is_settings_page()
				&& isset( $_POST['manga-crawler'] )
			){
				{NICK}_CRAWLER_HELPERS::update_crawler_settings( $_POST['manga-crawler'] );
			}

		}

		public function active_task(){

			if( !class_exists('{NICK}_CRAWLER_IMPLEMENT') ){
				wp_send_json_error();
			}

			$settings = {NICK}_CRAWLER_HELPERS::get_crawler_settings();

			$settings['active'] = true;

			$resp = {NICK}_CRAWLER_HELPERS::update_crawler_settings( $settings );

			if( $resp ){
				wp_send_json_success();
			}

			wp_send_json_error();

		}

		public function deactive_task(){

			if( !class_exists('{NICK}_CRAWLER_IMPLEMENT') ){
				wp_send_json_error();
			}

			$settings = {NICK}_CRAWLER_HELPERS::get_crawler_settings();

			$settings['active'] = false;

			$resp = {NICK}_CRAWLER_HELPERS::update_crawler_settings( $settings );

			if( $resp ){
				wp_send_json_success();
			}

			wp_send_json_error();

		}

		public function refresh_list(){

			if( empty( $_POST['action'] ) && empty( $_POST['doaction'] ) ){
				wp_send_json_error();
			}

			$output = '';

			ob_start();

			do_action( $_POST['doaction'] );
			$output = ob_get_contents();

			ob_end_clean();

			wp_send_json_success( $output );

        }

		public function get_update_log(){

			if( empty( $_GET['date'] ) ){
				wp_send_json_error( esc_html__( 'Missing Date', WP_MCL_TD ) );
			}

			$implement = new {NICK}_CRAWLER_IMPLEMENT();

			$logs = $implement->get_update_log( date( 'y-m-d', strtotime( $_GET['date'] ) ) );

			if( !empty( $logs ) ){
				wp_send_json_success( $logs );
			}else{
				wp_send_json_error([
					'message' => esc_html__( 'Update log is not available on this date', WP_MCL_TD )
				]);
			}

		}

		public function get_crawler_log(){

			if( empty( $_GET['date'] ) ){
				wp_send_json_error( esc_html__( 'Missing Date', WP_MCL_TD ) );
			}

			$implement = new {NICK}_CRAWLER_IMPLEMENT();

			$logs = get_crawler_log( $implement->data_dir, date( 'y-m-d', strtotime( $_GET['date'] ) ) );

			if( !empty( $logs ) ){
				wp_send_json_success( $logs );
			}else{
				wp_send_json_error([
					'message' => esc_html__( 'Crawler log is not available on this date', WP_MCL_TD )
				]);
			}

		}

		public function listing_manga( $post_id ){

			$crawler        = new {NICK}_CRAWLER_IMPLEMENT();
			$page = isset($_GET['p']) ? intval($_GET['p']) : 1;
			
			$queue_list     = $crawler->get_queue_list(100, ($page - 1) * 100);
			$completed_list = $crawler->get_completed_list(100, ($page - 1) * 100);
			
			?>
			<?php if($page > 1){?>
			<a href="<?php echo admin_url('admin.php?page={SITENAME}-manga-crawler-settings&tab=crawl-progress&p=' . ($page - 1));?>">Prev Page</a> | <?php }?><a href="<?php echo admin_url('admin.php?page={SITENAME}-manga-crawler-settings&tab=crawl-progress&p=' . ($page + 1));?>">Next Page</a>
			<h2>Completed List - Page <?php echo $page;?></h2>
			<ul class="list">
				<?php if( !empty( $completed_list ) ){ ?>
					<?php foreach( $completed_list as $manga ){ ?>

						<?php if( isset( $manga['is_blocked'] ) ){ ?>

							<li class="blocked-manga">
								<?php echo esc_html( $manga['name'] ); ?>
							</li>

						<?php }else{ ?>

							<?php $manga_id = get_manga_post_by_import_slug( $manga['slug'], $manga['name'] ); ?>

							<?php if( $manga_id ){ ?>

								<li class="<?php echo esc_attr( 'completed-manga' ); ?>">
									<a href="<?php echo esc_url( get_edit_post_link( $manga_id ) ); ?>">
										<?php echo esc_html( $manga['name'] ); ?>
									</a>
								</li>
							<?php } ?>

						<?php } ?>
					<?php } ?>
				<?php } else { ?>
				<li>No more completed items</li>
				<?php }?>
			</ul>
			<h2>Queue List - Page <?php echo $page;?></h2>
			<ul class="list">
				<?php if( !empty( $queue_list ) ){ ?>
					<?php foreach( $queue_list as $manga ){						
					?>

						<?php $class =
							isset( $queue_list[ $manga['slug'] ]['is_update'] )
							&& $queue_list[ $manga['slug'] ]['is_update']
							? 'updating-manga' : 'queue-manga'; ?>

						<li class="<?php echo esc_attr( $class ); ?>">
							<?php if( isset( $queue_list[ $manga['slug'] ]['post_id'] ) ){ ?>
								<a href="<?php echo esc_url( get_the_permalink( $post_id ) ); ?>">
							<?php } ?>

							<?php echo esc_html( $manga['name'] ); ?>

							<?php if( isset( $queue_list[ $manga['slug'] ]['post_id'] ) ){ ?>
								</a>
							<?php } ?>
						</li>
					<?php } ?>
				<?php } else { ?>
				<li>No queueing items</li>
				<?php }?>
			</ul>
			<?php
		}

		public function upgrade_handler(){

			if( empty( get_option( '{NICKLOWER}_crawler_json_upgraded' ) ) ){

				$crawler = new {NICK}_CRAWLER_IMPLEMENT();

				$old_completed_file = "$crawler->data_dir/completed.json";
				$new_completed_file = "$crawler->data_dir/completed_0.json";

				if( file_exists( $old_completed_file ) ){
					$resp = copy( $old_completed_file, $new_completed_file );
					if( $resp ){
						unlink( $old_completed_file );
					}
				}

				update_option( '{NICKLOWER}_crawler_json_upgraded', true );
			}
		}

	}

	new {SITENAMECAPITAL}_SETTINGS();
