<?php

add_action('wp', 'wp_{NICKLOWER}_crawler_debug');

function wp_{NICKLOWER}_crawler_debug(){

    if( isset( $_GET['debug'] ) && $_GET['debug'] == '{NICKLOWER}_manga_crawler' ){

         $manga = array(
             "slug" => "blood_link",
             "name" => "Blood Link",
             "url"  => "http://{SITEURL}/manga/blood_link/"
         );

        // Do something to debug
        $implement = new {NICK}_CRAWLER_IMPLEMENT();
		$result = $implement->fetch_manga_listing();
		dd($result);exit;
		
        // Test get manga single
        // $implement->create_manga( $manga );

        // Test get chapter list
         //$html = $implement->get_site_html( $manga['url'] );
	
        // $chapters = $implement->fetch_chapter_list( $html );
         //dd( $chapters );
		 
        // Test get chapter images
        // $images = $implement->get_chapter_images( '' );
        // dd( $images );

        // Test get manga update
        // $implement->update_latest_manga();

        // Check Crawler running status.
        ?>
        <table>
            <tr>
                <td>Current Time</td>
                <td><?php echo date( 'Y-m-d H:i:s', time() ); ?></td>
            </tr>
            <tr>
                <td>Crawler Running Timeout</td>
                <td>
                    <?php $running_timeout = get_option( '_transient_timeout_is_{SITENAME}_crawler_running' ); ?>
                    <?php echo get_transient('is_{SITENAME}_crawler_running') ? date( 'Y-m-d H:i:s', $running_timeout ) : 'NO'; ?>
                </td>
            </tr>
            <tr>
                <td>Crawler Not Run Timeout</td>
                <td>
                    <?php $not_run_timeout = get_option( '_transient_timeout_{SITENAME}_crawler_not_run' ); ?>
                    <?php echo get_transient( '{SITENAME}_crawler_not_run' ) ? date( 'Y-m-d H:i:s', $not_run_timeout ) : 'NO'; ?>
                </td>
            </tr>
        </table>
        <?php

        die();
    }
}
