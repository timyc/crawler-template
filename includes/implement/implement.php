<?php

    class {NICK}_CRAWLER_IMPLEMENT extends {NICK}_CRAWLER_HELPERS{

        public $plugin_dir = WP_MCL_{NICK}_PATH;

        public $url = 'https://{SITEURL}';
        public $data_dir = '';

        public $fname = '{SITENAME}';
        public $sname = '{NICKLOWER}';

        public $search_url = 'https://{SITEURL}/search.php';

        /**
         * For Manga Listing page crawler
         */
        protected $manga_listing_url        = 'https://{SITEURL}/directory/?az';
        protected $manga_link_selector      = '.manga-list-1-list li';
        protected $pagination_link_selector = '.pager-list .pager-list-left';
        protected $manga_listing_latest_url = 'https://{SITEURL}/releases/';
        protected $manga_latest_link_selector = '.manga-list-4-list > li';

        /**
         * For Manga Single Page
         */
        protected $name_selector       = '.detail-info-right-title .detail-info-right-title-font';
        protected $desc_selector       = '.detail-info-right .fullcontent';
        protected $status_selector     = '.detail-info-right-title .detail-info-right-title-tip';
        protected $thumb_selector      = '.detail-info-cover-img';
        protected $alter_name_selector = '';
        protected $manga_info_selector = '';

        /**
         * For Chapter Reading Page
         */
        protected $image_selector = '';

        /**
         * Status of crawler include
         */
        protected $status;

        /**
         * Crawler Task Settings
         */
        public $settings;


        public function __construct(){
            $this->setup();
            parent::__construct();
            $this->override_file_dir();	
        }

        private function setup(){

            /**
            * Get and setup crawler settings & status
            */
            $this->settings = parent::get_crawler_settings();

            $this->status = isset( $this->settings['status'] ) && is_array( $this->settings['status'] ) ? $this->settings['status'] : array();

            /**
            * Setup crawler data dir
            */
            $upload_dir = wp_get_upload_dir();

            $this->data_dir = "{$upload_dir['basedir']}/wp-crawler-cronjob/{SITENAME}-manga-crawler";
			
        }

        private function override_file_dir(){

            /**
            * Setup queue json files directory
            */
            $this->manga_queue_files = glob( $this->data_dir . '/queue_*.json' );
            natcasesort( $this->manga_queue_files );

            /**
            * Setup Manga queue file will be the last file
            */
            if( !empty( $this->manga_queue_files ) && is_array( $this->manga_queue_files ) ){
                $this->manga_queue_file = $this->manga_queue_files[ count( $this->manga_queue_files ) - 1 ];
            }else{
                $this->manga_queue_file = null;
            }

            /**
            * Setup completed json files directory
            */
            $this->manga_completed_files = glob( $this->data_dir . '/completed_*.json' );
            natcasesort( $this->manga_completed_files );

            /**
            * Setup Manga completed file will be the last file
            */
            if( !empty( $this->manga_completed_files ) && is_array( $this->manga_completed_files ) ){
                $this->manga_completed_file = $this->manga_completed_files[ count( $this->manga_completed_files ) - 1 ];
            }else{
                $this->manga_completed_file = "{$this->data_dir}/completed_0.json";
            }

            /**
            * Setup Manga update file
            */
            $this->manga_update_file = "{$this->data_dir}/update.json";

        }

        public function get_chapter_images( $url ){

            $url = $this->manga_url_filter( $url );
			
			global $cookies, $request_cookies, $request_referer, $need_cookies_response;

			$need_cookies_response = true;
			
			$html = $this->get_site_html( $url );
			
            if( empty( $html ) ){
                error_log_die([
                    'function' => __FUNCTION__,
                    'message'  => "Cannot get content from $url",
                    'cancel'   => true,
                    'code'     => ERROR_GET_HTML
                ]);
            }

            // If this manga is blocked by country
            if( strpos( $html, 'Sorry, its licensed, and not available.') !== false || strpos( $html, 'has been licensed, it is not available in') !== false ){
                return new WP_Error( 'blocked' );
            }

            // Get total pages
			$total_pages = 1;
            preg_match('/var imagecount=(\d+);/', $html, $matches);
			if($matches){
				$total_pages = $matches[1];
			}
			$comicid = '';
			preg_match('/var comicid = (\d+);/', $html, $matches);
			if($matches){
				$comicid = $matches[1];
			}
			
			$chapterid = '';
			preg_match('/var chapterid =(\d+);/', $html, $matches);
			if($matches){
				$chapterid = $matches[1];
			}
			
			$dm5_key = '';
			preg_match('/eval\(.*(dm5_key).*\)/', $html, $matches);
			$unpacker = new JavaScriptUnpacker();
			if($matches){
				$eval = $matches[0];
				
				$js = $unpacker->unpack($eval);
				
				$dm5_key = str_replace(array('\'','+',' '),array('','',''),str_replace(array('var guidkey=',';$("#dm5_key").val(guidkey);'), array('',''), $js));
			}
			
			$newImgs = '';
			preg_match('/eval\(.*(newImgs).*\)/', $html, $matches);
			$unpacker = new JavaScriptUnpacker();
			if($matches){
				$eval = $matches[0];
				
				$js = $unpacker->unpack($eval);
				
				$newImgs = explode(',', str_replace('var newImgs=[', '', substr($js, 0, strpos($js, '];var newImginfos'))));
			}
			
			if($newImgs){
				// if chapter shows all images in one page, we will be in this case
				$images_url = array();
				foreach($newImgs as $img){
					$url = 'http:' . str_replace("'", "", $img);
					array_push($images_url, substr($url, 0, strpos($url, '?')));
				}
				
				return $images_url;
			}			
			
            $base_url = str_replace( '1.html', '', $url );
            $base_url = str_replace( '1.htm', '', $base_url );
            $base_url = $this->manga_url_filter( $base_url );
            $images_url = array();

			$request_cookies = $cookies;
			$request_referer = $url . '1.html';
			
            for( $i = 1; $i <= $total_pages; $i++ ){
				$need_cookies_response = true;
				$history_url = $base_url . 'history.ashx?cid=' . $chapterid . '&mid=' . $comicid . '&page=' . $i . '&uid=0';
				$data = $this->get_site_html($history_url);
				
				// merge cookies;
				foreach (array_keys($cookies) as $key) {
					if (isset($request_cookies[$key])) unset($request_cookies[$key]);
				}
				
				// $request_cookies = $request_cookies + $cookies;
				// to safely used (+) array operator on some server
				
				$union = $request_cookies;
				foreach ($cookies as $key => $value) {
					if (false === array_key_exists($key, $union)) {
						$union[$key] = $value;
					}
				}
				
				$request_cookies = $union;
				
				$need_cookies_response = false;
				$data_url = $base_url . 'chapterfun.ashx?cid=' . $chapterid . '&page=' . $i . '&key=' . ($i == 1 ? '' : $dm5_key);
			
				$eval = $this->get_site_html($data_url);
				
				if($eval != '' && strpos($eval, "Bad Request - Invalid URL") === false){
					
					$js = $unpacker->unpack($eval);
					$idx1 = strpos($js, 'var pix="') + 9;
					$idx2 = strpos($js, '";');
					
					$url_1 = substr($js, $idx1, $idx2 - $idx1);
					
					$idx1 = strpos($js, 'var pvalue=["') + 13;
					$idx2 = strpos($js, '","');
					if($idx2 === false){
						$idx2 = strpos($js, '"];');
					}
					
					$url_2 = substr($js, $idx1, $idx2 - $idx1);
					
					$image = $url_1 . $url_2;
					
					$image = $this->image_url_filter($image);
					array_push( $images_url, substr($image, 0, strpos($image, '?')) );
				}
            }

            return $images_url;

        }

        public function get_manga_listing_paged( $page ){
            if( empty( $page ) || $page == 1 ){
                return $this->manga_listing_url;
            }

            $url = str_replace( '/?az', '', $this->manga_listing_url );

            return "{$url}/{$page}.html?az";
        }

        protected function manga_url_filter( $url ){

            $url = str_replace( 'https://', '', $url );
            $url = str_replace( 'http://', '', $url );
            $url = str_replace( '//', '', $url );
            $url = str_replace( 'http:', '', $url );
			
			if(strpos($url, '/') === 0){
				$url = '{SITEURL}' . $url;	
			}
			
            return "https://{$url}";
        }
		
		protected function image_url_filter( $url ){

            $url = str_replace( 'https://', '', $url );
            $url = str_replace( 'http://', '', $url );
            $url = str_replace( '//', '', $url );
            $url = str_replace( 'http:', '', $url );
			if(strpos($url, '/') === false){
				$url = '{SITEURL}' . $url;
			}
            return "https://{$url}";
        }

        protected function manga_slug_filter( $manga_url ){

            $slug = str_replace( 'http://', '', $manga_url );
            $slug = str_replace( 'https://', '', $slug );
            $slug = str_replace( 'http:', '', $slug );
            $slug = str_replace( '{SITEURL}/manga/', '', $slug );
            $slug = str_replace( '/', '', $slug );
            $slug = str_replace( '.html', '', $slug );
            $slug = str_replace( '.htm', '', $slug );

            return $slug;
        }

        public function get_last_page( $html ){
            
            return 434;
            
            
            $nav_links = $html->find('.pager-list .pager-list-left a');

            if( !empty( $nav_links ) && is_array( $nav_links ) ){
                return intval( $nav_links[ count( $nav_links ) - 2 ]->plaintext );
            }

            return false;
        }

        public function get_manga_name( $html ){

            if( !empty( $this->name_selector ) ){
                $name = $html->find( $this->name_selector, 0 );
            }

            return !empty( $name ) ? $name->plaintext : '';

        }

        protected function get_manga_status( $html ){

            $status = $html->find( $this->status_selector );

            if( empty( $status ) ){
                return '';
            }

            $status = $status[0]->plaintext;
            $exploded = explode( ',', $status );

            if( trim(strtolower( $exploded[0] )) == 'ongoing' ){
                return 'on-going';
            }elseif( trim(strtolower( $exploded[0] )) == 'completed' ){
                return 'end';
            }else{
                return null;
            }
        }

        protected function get_manga_release( $html ){

            return '';
        }

        protected function get_manga_authors( $html ){

            $data = $html->find( '.detail-info-right-say a', 0 );

            if( !empty( $data ) ){
                return $data->plaintext;
            }

            return '';
        }

        protected function get_manga_artists( $html ){

            return '';
        }

        protected function get_manga_genres( $html ){

            $data = $html->find( '.detail-info-right-tag-list a' );

            if( !empty( $data ) ){
				$genres = array();
				foreach($data as $genre){
					array_push($genres, $genre->plaintext);
				}
                return implode(',',$genres);
            }

            return '';
        }

        public function get_manga_ratings( $html ){

            $data = $html->find('.detail-info-right-title-star .item-score', 0);

            if( !empty( $data ) ){
                return array(
                    'avg'     => $data->plaintext,
                    'numbers' => 1
                );
            }
			
            return array();
        }

        protected function get_manga_views( $html ){

            return '';
        }

        public function fetch_chapter_list( $html ){

            $find_chapters  = $html->find('#chapterlist .detail-main-list li');

			$output = array();

			$current_vol = '';

			$chapters = array();

			foreach( $find_chapters as $chapter ){
				$full_chapter_name = $chapter->find('.detail-main-list-main p',0)->plaintext;
				
				$chapter_extend_name = '';
				$names = explode(' - ', $full_chapter_name);
				$first_part_name = $full_chapter_name;
				$vol_name = '';
				
				if(count($names) >= 2){
					$chapter_extend_name = $names[1];
					$first_part_name = $names[0];
				}
				
				$names = explode(' ', $first_part_name);
				$chapter_name = $first_part_name;
				if(count($names) == 2){
					$chapter_name = $names[1];
					$vol_name = $names[0];
				}
				
				if($vol_name != $current_vol){
					if($current_vol == ''){
						$current_vol = 'Vol.01';
					}
					
					if(isset($output[ $current_vol ])){
						$output[ $current_vol ]['chapters'] = array_merge(array_reverse( $chapters ), $output[ $current_vol ]['chapters']);
					} else {
						$output[ $current_vol ] = array(
							'name'     => $current_vol,
							'chapters' => array_reverse( $chapters ) // reverse to fetch chapter from oldest to latest
						);
					}
					
					$chapters = array();
					
					$current_vol = $vol_name;
				}
				
				$the_chapter = array(
					'name'        => $chapter_name,
					'extend_name' => $chapter_extend_name,
					'url'         => $chapter->find('a',0)->href
				);
				
				$chapters[] = $the_chapter;
			}
			
			// add last chapters to last volumn
			if($current_vol == ''){
				$current_vol = esc_html('NO-VOLUME', WP_MCL_TD);
			}			
			
			if(isset($output[ $current_vol ])){
				$output[ $current_vol ]['chapters'] = array_merge(array_reverse( $chapters ), $output[ $current_vol ]['chapters']);
			} else {
				$output[ $current_vol ] = array(
					'name'     => $current_vol,
					'chapters' => array_reverse( $chapters ) // reverse to fetch chapter from oldest to latest
				);
			}

            ksort( $output );

            return $output;

        }

        public function update_latest_manga(){
			
            // Find all yesterday manga updated in {SITEURL}
            $page = 1;

            $mangas = [];

            do{
				$page_url = "https://{SITEURL}/releases/{$page}.htm";
				
				crawler_log('Find updated mangas in ' . $page_url);
                $html = get_site_html( $page_url );

                if( empty( $html ) ){
                    error_log_die([
                        'function' => __FUNCTION__,
                        'message'  => "Cannot get content from $page_url",
                        'cancel'   => true,
                        'code'     => ERROR_GET_HTML
                    ]);
                }

                $list = $html->find($this->manga_latest_link_selector);

                if( empty( $list ) ){
                    error_log_die([
                        'function' => __FUNCTION__,
                        'message'  => "Cannot get update list from $page_url",
                        'cancel'   => true,
                        'code'     => ERROR_GET_HTML
                    ]);
                }

                foreach( $list as $manga){
					if($manga->find('.manga-list-4-item-title')){
						$a_tag = $manga->find('.manga-list-4-item-title a',0);
						
						// Find manga post
						$day = strtolower( $manga->find('.manga-list-4-item-subtitle span', 0 ));

						if( strpos( $day, 'ago' ) !== false || strpos($day,'yesterday') !== false || strpos($day,'today') !== false ){

							$mangas[] = array(
								'href' => $a_tag->href,
								'name' => $a_tag->title
							);
						} else {
							$end = true;
							break;
						}
					}
                }

                $page++;

            }while( empty( $end ) );
			
            if( !empty( $mangas ) ){
                $output = array();

                foreach( $mangas as $manga ){

                    // Find manga post
                    $manga_post_id = find_manga_post( $manga['name'] );

                    if( !empty( $manga_post_id ) ){
                        $manga_url  = $this->manga_url_filter( $manga['href'] );
                        $manga_slug = $this->manga_slug_filter( $manga['href'] );

                        $output[ $manga_slug ] = array(
                            'slug'      => $manga_slug,
                            'name'      => $manga['name'],
                            'url'       => $manga_url,
                            'is_update' => true,
                            'post_id'   => $manga_post_id
                        );
                    }
                }

                $this->put_manga_queue_list( $output );
            }
        }

        protected function url_file_name_filter( $name ){
            $name = explode('?', $name);
            return $name[0];
        }

        public function get_site_html( $url ){

            // Since this function should be called regularly so let's put the extend time limit on this function
            if( function_exists( 'set_time_limit' ) ){
                set_time_limit( 300 );
            }

            if( !empty( $this->settings['proxy']['status'] ) ){
                $GLOBALS['proxy'] = $this->settings['proxy'];
            }
            if( !empty( $this->settings['proxy']['scraperapi'] ) ){
					$GLOBALS['scraperapi'] = $this->settings['proxy']['scraperapi'];
				}

            $html = get_site_html( $url );
			
            if( !empty( $html ) && !empty( $html->find('#cf-error-details') ) ){
                error_log_die([
                    'function' => __FUNCTION__,
                    'message'  => "Error 502 : Bad Gateway from CloudFlare",
                    'cancel'   => true,
                    'code'     => ERROR_CLOUD_FLARE
                ]);
            }

            return $html;
        }

        /**
        * Update the status for crawler
        */
        public function update_status( $key, $value ){

            if( empty( $this->status ) || ! is_array( $this->status ) ){
                $this->status = array();
            }

            $this->status[ $key ] = $value;
            $this->settings['status'] = $this->status;

            $settings = {NICK}_CRAWLER_HELPERS::get_crawler_settings();

            $settings['status'] = $this->status;

            return {NICK}_CRAWLER_HELPERS::update_crawler_settings( $settings );

        }

        public function fetch_manga_listing(){
			$is_manga_listing_has_paged = $this->is_manga_listing_has_paged();
            
			if( $is_manga_listing_has_paged ){
				$paged = isset( $this->status['manga_listing_next_page'] ) ? $this->status['manga_listing_next_page'] : 1;
				
				// If this manga already has latest page, then don't need to go back to first page
				if( $paged === null ){
					return;
				}

				$manga_listing_url = $this->get_manga_listing_paged( $paged );
			}elseif( empty( $this->status['manga_list_fetched'] ) ){
				$manga_listing_url = $this->manga_listing_url;
			}else{
				return;
			}
			
			crawler_log( '...Crawl Manga Listing: ' . $manga_listing_url );
			
            $html = $this->get_site_html( $manga_listing_url );

            if( empty( $html ) ){
                error_log_die([
                    'function' => __FUNCTION__,
                    'message'  => "Cannot get content from $manga_listing_url",
                    'cancel'   => true
                ]);
            }

            $mangas = $html->find( $this->manga_link_selector );

            if( $mangas ){

                $output = array();

                // Exclude completed manga for upgrade json data from version 1.0
                $completed_list = $this->get_completed_list();

                foreach( $mangas as $manga ){
					$a_tag = $manga->find('a', 0);
                    $manga_url  = $this->manga_url_filter( $a_tag->href );
                    $manga_slug = $this->manga_slug_filter( $a_tag->href );

                    if( isset( $completed_list[ $manga_slug ] ) ){
                        continue;
                    }

                    $output[ $manga_slug ] = array(
                        'slug' => $manga_slug,
                        'name' => $a_tag->title,
                        'url'  => $manga_url,
                    );
                }

                $parts = array_chunk( $output, 1000, true );

                foreach( $parts as $index => $part ){
					$file = "{$this->data_dir}/queue_{$index}.json";
					$updated_part = $part;
					if(file_exists($file)){
						$old_part = json_decode(file_get_contents($file), true);
						if(is_array($old_part)){
							$updated_part = array_merge($old_part, $part);
						}
					}
					
                    file_put_contents( $file, json_encode( $updated_part, JSON_PRETTY_PRINT ) );

                    unset( $parts[ $index ] );
                }

            }

            crawler_log( '...Crawl Manga Listing Done' );
			
			if( $is_manga_listing_has_paged && method_exists( $this, 'get_last_page' ) ){

				$last_page = $this->get_last_page( $html );
				
                if( !$last_page || ($last_page <= $paged) ){
					if( empty( $this->manga_listing_latest_url ) ){
						$next_page = 1;
					}else{
						$next_page = null;
					}
				}else{
					$next_page = ++$paged;
				}

				$this->update_status( 'manga_listing_next_page', $next_page );

			}

            return false;
        }

        public function put_manga_queue_list( $mangas ){

            if( !empty( $this->manga_queue_file ) ){
                $list = file_get_contents( $this->manga_queue_file );
                $list = json_decode( $list, true );
            }else{
                $list = array();
            }

            $update = $this->get_update_list();

            $is_update = false;
			$is_manga_update = false;
			$is_queue_update = false;
            foreach( $mangas as $slug => $manga ){
                // If this is manga update
                if( !empty( $manga['is_update'] ) && ! isset( $update[ $slug ] ) ){
                    $update[ $slug ] = $manga;
                    $is_manga_update = true;
                }elseif( ! isset( $list[ $slug ] ) ){
                    $list[ $slug ] = $manga;
                    $is_queue_update = true;
                }
            }

            if( $is_queue_update ){
                file_put_contents( $this->manga_queue_file, json_encode( $list, JSON_PRETTY_PRINT ) );
            }

            if( $is_manga_update ){
                file_put_contents( $this->manga_update_file, json_encode( $update, JSON_PRETTY_PRINT ) );
            }

            return true;

        }

        public function get_update_list(){

            if( isset( $this->manga_update_file ) && file_exists( $this->manga_update_file ) ){
                return json_decode( file_get_contents( $this->manga_update_file ), true );
            }

            return array();
        }

        public function get_queue_list( $number = null, $offset = 0 ){

            $list = array();

			foreach( $this->manga_queue_files as $file ){
                if( file_exists( $file ) ){
					$data = file_get_contents( $file );
					if($data){
						$content = json_decode( $data, true );
						if(is_array($content))
							$list = array_merge( $list, $content );
					}
                }
            }

            $list = empty( $list ) ? array() : $list;

            $list = array_merge(empty($this->get_update_list()) ? array() : $this->get_update_list(), $list);

            if( $number !== null && is_numeric( $number ) ){
                $list = array_slice( $list, $offset, intval( $number ) );
            }

            return $list;

        }

        public function remove_manga_queue_list( $manga ){

            foreach( $this->manga_queue_files as $file ){
                if( file_exists( $file ) ){
                    $list = json_decode( file_get_contents( $file ), true );

                    if( isset( $list[ $manga['slug'] ] ) ){
                        unset( $list[ $manga['slug'] ] );
                        return file_put_contents( $file, json_encode( $list, JSON_PRETTY_PRINT ) );
                    }
                }
            }

            return false;

        }

        public function get_completed_list( $get_all = true, $offset = 0 ){



            $list = array();



            foreach( $this->manga_completed_files as $file ){

                    if( file_exists( $file ) ){

                        $content = json_decode( file_get_contents( $file ), true );

                        if( is_array( $content ) ){

                            $list = array_merge( $list, $content );

                        }

                    }

           }

		   $list = empty( $list ) ? array() : $list;
		   
		   if( is_array( $list ) && $get_all !== null && is_numeric( $get_all ) ){
				$list = array_slice( $list, $offset, intval( $get_all ) );
			}

			return $list;
        }

        protected function put_manga_completed_list( $manga, $is_blocked = false ){

            $list = $this->get_completed_list( false );

            $list[ $manga['slug'] ] = $manga;

            file_put_contents( $this->manga_completed_file, json_encode( $list, JSON_PRETTY_PRINT ) );

            if( count( $list ) === 1000 ){
                preg_match( '/completed_(\d+)\.json/', $this->manga_completed_file, $matches );
                if( isset( $matches[1] ) && is_numeric( $matches[1] ) ){

                    $number = $matches[1] + 1;

                    file_put_contents( "{$this->data_dir}/completed_{$number}.json", json_encode( array(), JSON_PRETTY_PRINT ) );
                }
            }

            return true;
        }

    }
