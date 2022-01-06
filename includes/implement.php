<?php

    if( class_exists('WP_CRAWLER_ACTIONS') ){

        class {NICK}_CRAWLER_IMPLEMENT extends WP_CRAWLER_ACTIONS{

            protected $url = 'https://{SITEURL}';
            protected $data_dir = '';

            public $fname = '{SITENAME}';
            public $sname = '{NICKLOWER}';

            /**
         	 * For Manga Listing page crawler
        	 */
            protected $manga_listing_url        = 'https://{SITEURL}/list-manga/?m_orderby=alphabet';
            protected $manga_link_selector      = '#mangalist ul.list li > a.title';
            protected $pagination_link_selector = '.pager-list .pager-list-left a';
            protected $manga_listing_latest_url = 'https://{SITEURL}/directory/?latest';

            /**
         	 * For Manga Single Page
        	 */
            protected $desc_selector       = '#title p.summary';
            protected $status_selector     = '#series_info .data > span';
            protected $thumb_selector      = '#series_info > .cover > img';
            protected $alter_name_selector = '#title > h3 > a';
            protected $manga_info_selector = '#title > table > tbody > tr > td';

            /**
         	 * For Chapter Reading Page
        	 */
            protected $image_selector = '#viewer .read_img a > img';

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
            }

            private function setup(){
                $this->settings = {NICK}_CRAWLER_IMPLEMENT::get_crawler_settings();

                $this->status = isset( $this->settings['status'] ) ? $this->settings['status'] : '';

                $upload_dir = wp_get_upload_dir();

                $this->data_dir = "{$upload_dir['basedir']}/wp-crawler-cronjob/{SITENAME}-manga-crawler";
            }

            public function get_chapter_images( $url ){

                $url = $this->manga_url_filter( $url );

                $html = get_site_html( $url );

                if( empty( $html ) ){
                    error_log_die( __FUNCTION__, "Cannot get content from $url", true );
                }

                // If this manga is blocked by country
                if( strpos( $html, 'Sorry, its licensed, and not available.') !== false ){
                    return null;
                }

                // Get total pages
                $pages = $html->find('#top_bar select.m > option');

                if( empty( $pages ) && !isset( $pages[ count( $pages ) - 2 ] ) ){
                    error_log_die( __FUNCTION__, "Cannot get chapter total pages " . $pages->plaintext, true );
                }

                $total_pages = $pages[ count( $pages ) - 2 ]->plaintext;

                $page_url = str_replace( '1.html', '', $url );
                $page_url = str_replace( '1.htm', '', $page_url );
                $page_url = $this->manga_url_filter( $page_url );

                $images_url = array();

                for( $i = 1; $i <= $total_pages; $i++ ){
                    $image = $this->get_page_images( "{$page_url}{$i}.html" );

                    if( empty( $image ) ){
                        error_log_die( __FUNCTION__, "Cannot get page image {$page_url}{$i}.html" );
                    }
                    
                    $images_url = array_merge( $images_url, $image );
                }

                return $images_url;

            }

            public function get_manga_listing_paged( $page ){
                if( empty( $page ) || $page == 1 ){
                    return $this->manga_listing_url;
                }

                $url = str_replace( '/?az', '', $this->manga_listing_url );

                return "{$url}/{$page}.htm?az";
            }

            protected function manga_url_filter( $url ){

                $url = str_replace( 'https://', '', $url );
                $url = str_replace( 'http://', '', $url );
                $url = str_replace( '//', '', $url );
                $url = str_replace( 'http:', '', $url );

                return "http://{$url}";
            }

            protected function manga_slug_filter( $manga_url ){

                $slug = str_replace('//{SITEURL}/manga/', '', $manga_url);
                $slug = str_replace('/', '', $slug);
                $slug = str_replace('.html', '', $slug);
                $slug = str_replace('.htm', '', $slug);

                return $slug;
            }

            protected function get_last_page( $html ){
                $nav_links = $html->find('#nav li a');

                if( !empty( $nav_links ) && is_array( $nav_links ) ){
                    return intval( $nav_links[ count( $nav_links ) - 2 ]->plaintext );
                }

                return false;
            }

            protected function get_manga_status( $html ){

                $status = $html->find( $this->status_selector );

                if( empty( $status ) ){
                    return '';
                }

                $status = $status[0]->plaintext;
                $exploded = explode( ',', $status );

                if( strtolower( $explode[0] ) == 'ongoing' ){
                    return 'on-going';
                }elseif( strtolower( $explode[0] ) == 'completed' ){
                    return 'completed';
                }else{
                    return null;
                }
            }

            protected function get_manga_release( $html ){

                $data = $html->find( $this->manga_info_selector );

                if( !empty( $data ) ){
                    return $data[0];
                }

                return '';
            }

            protected function get_manga_authors( $html ){

                $data = $html->find( $this->manga_info_selector );

                if( !empty( $data ) ){
                    return $data[1];
                }

                return '';
            }

            protected function get_manga_artists( $html ){

                $data = $html->find( $this->manga_info_selector );

                if( !empty( $data ) ){
                    return $data[2];
                }

                return '';
            }

            protected function get_manga_genres( $html ){

                $data = $html->find( $this->manga_info_selector );

                if( !empty( $data ) ){
                    return $data[3];
                }

                return '';
            }

            public function get_manga_ratings( $html ){

                $data = $html->find('#series_info div.data', 2);

                if( empty( $data ) ){
                    return array();
                }

                $re = '/Rating\:\sAverage\s(\d\.\d\d)\s\/\s\d\sout\sof\s(\d+)\stotal\svotes\./';
                $str = 'Rating: Average 4.78 / 5 out of 180 total votes.';

                preg_match_all( $re, $data->plaintext, $matches, PREG_SET_ORDER, 0 );

                if( !empty( $matches ) && isset( $matches[0][1] ) && isset( $matches[0][2] ) ){
                    return array(
                        'avg'     => floatval( $matches[0][1] ),
                        'numbers' => intval( $matches[0][2] )
                    );
                }

                return array();
            }

            protected function get_manga_views( $html ){

                $data = $html->find('#series_info div.data', 1);

                if( !empty( $data ) ){
                    $explode = explode( 'it has ', $data->plaintext );
                    if( isset( $explode[1] ) ){
                        $explode = explode( ' monthly views', $explode[1] );
                        if( isset( $explode[0] ) ){
                            return str_replace( ',', '', $explode[0] );
                        }
                    }
                }
                return '';
            }

            protected function fetch_chapter_list( $html ){

                $find_vols = $html->find('#chapters h3.volume');
                $find_vols_span = $html->find('#chapters h3.volume > span');
                $find_chapters = $html->find('#chapters ul.chlist');

                $output = array();

                foreach( $find_vols as $index => $vol ){
                    $vol_name = str_replace( $find_vols_span[ $index ]->plaintext, '', $vol->plaintext );

                    $output[ $vol_name ] = array(
                        'name' => $vol_name,
                        'chapters' => array()
                    );

                    foreach( $html->find('#chapters ul.chlist', $index)->find('li') as $chapter ){
                        $chapter_name = $chapter->find('a.tips',0);
                        $chapter_extend_name = $chapter->find('span.title',0);

                        $output[ $vol_name ]['chapters'][] = array(
                            'name'        => $chapter_name->plaintext,
                            'extend_name' => !empty( $chapter_extend_name ) ? $chapter_extend_name->plaintext : '',
                            'url'         => $chapter_name->href
                        );
                    }
                }

                return $output;

            }

            protected function url_file_name_filter( $name ){
                $name = explode('?', $name);
                return $name[0];
            }

            public static function is_settings_page(){

                if( strpos( $_SERVER['REQUEST_URI'], 'admin.php?page={SITENAME}-manga-crawler-settings' ) !== false ){
                    return true;
                }

            }

            public static function is_crawler_active(){

                $settings = {NICK}_CRAWLER_IMPLEMENT::get_crawler_settings();

                return isset( $settings['active'] ) && $settings['active'];

            }

            public static function get_template( $name, $extend_name = null ){

                if( $extend_name ){
                    $name .= $extend_name;
                }

                $path = WP_MCL_{NICK}_PATH . "template/$name.php";

                if( file_exists( $path ) ){
                    include( $path );
                }

                return;

            }

            public static function get_crawler_settings(){

                $settings = get_option( '_{NICKLOWER}_crawler_settings' );

                $defaults = array(
                    'fetch'               => array(
                        'genres'          => '0',
                        'tags'            => '0',
                        'ratings'         => '0',
                        'views'           => '0',
                    ),
                    'import'              => array(
                        'status'          => 'pending',
                        'genres'          => array(),
                        'tags'            => '',
                    ),
                    'cronjob'             => array(
                        'recurrence'      => '3',
                        'number_chapters' => '1'
                    ),
                    'active'              => '1',
                );

                return !empty( $settings ) ? array_merge( $defaults, $settings ) : $defaults;
            }

            public static function update_crawler_settings( $settings ){
                return update_option( '_{NICKLOWER}_crawler_settings', $settings );
            }

        }

    }
