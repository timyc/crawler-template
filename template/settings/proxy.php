<?php

    /**
 	 * Progress metabox template
 	 */

    if( ! defined('ABSPATH') ){
        exit;
    }

    global ${NICKLOWER}_crawler_settings;

    $address = isset( ${NICKLOWER}_crawler_settings['proxy']['address'] ) ? ${NICKLOWER}_crawler_settings['proxy']['address'] : '';
    $user    = isset( ${NICKLOWER}_crawler_settings['proxy']['user'] ) ? ${NICKLOWER}_crawler_settings['proxy']['user'] : '';
    $pass    = isset( ${NICKLOWER}_crawler_settings['proxy']['pass'] ) ? ${NICKLOWER}_crawler_settings['proxy']['pass'] : '';
    $port    = isset( ${NICKLOWER}_crawler_settings['proxy']['port'] ) ? ${NICKLOWER}_crawler_settings['proxy']['port'] : '';
	// https://www.scraperapi.com/
	$scraperapi = isset( ${NICKLOWER}_crawler_settings['proxy']['scraperapi'] ) ? ${NICKLOWER}_crawler_settings['proxy']['scraperapi'] : '';
?>

<table class="form-table">
    <tbody>
        <tr>
            <th scope="row">
                <label><?php esc_html_e('Address', WP_MCL_TD); ?></label>
            </th>
            <td>
                <input type="text" class="regular-text" name="manga-crawler[proxy][address]" value="<?php echo esc_attr( $address ); ?>">
                <p class="description">
                    <?php esc_html_e( "Use proxy for fetching manga.", WP_MCL_TD ); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label><?php esc_html_e('Username', WP_MCL_TD); ?></label>
            </th>
            <td>
                <input type="text" class="regular-text" name="manga-crawler[proxy][user]" value="<?php echo esc_attr( $user ); ?>">
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label><?php esc_html_e('Password', WP_MCL_TD); ?></label>
            </th>
            <td>
                <input type="text" class="regular-text" name="manga-crawler[proxy][pass]" value="<?php echo esc_attr( $pass ); ?>">
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label><?php esc_html_e('Port', WP_MCL_TD); ?></label>
            </th>
            <td>
                <input type="text" class="regular-text" name="manga-crawler[proxy][port]" value="<?php echo esc_attr( $port ); ?>">
            </td>
        </tr>

        <?php

            if( !empty( ${NICKLOWER}_crawler_settings['proxy']['address'] ) ){
                $stt = check_curl_proxy( ${NICKLOWER}_crawler_settings['proxy'] );
                {NICK}_CRAWLER_HELPERS::update_crawler_proxy_stt( $stt['success'] );
            }
        ?>

        <?php if( isset( $stt ) ){ ?>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e('Status', WP_MCL_TD); ?></label>
                </th>
                <td>
                    <?php if( $stt['success'] ){ ?>
                        <span class="dashicons dashicons-yes"></span><span><?php esc_html_e('Connect successfully! '); echo $stt['message']; ?></span>
                    <?php }else{ ?>
                        <span class="dashicons dashicons-no"></span><span><?php esc_html_e('The given proxy host could not be resolved. Please check again'); ?></span>
                        <br>
                        <span><?php echo sprintf( __('Error message : %s', WP_MCL_TD ), $stt['message'] ); ?></span>
                    <?php } ?>
                    <p class="description">
                        <?php echo sprintf( __( "You can visit this <a href='%s'>page</a> to view the manga page via this Proxy to make sure it won't be licensed", WP_MCL_TD ), home_url('/?browse=site') ); ?>
                    </p>
                </td>
            </tr>
        <?php } ?>
		
		<tr>
			<td colspan="2"></td>
		</tr>
		<tr>
			<th></th><td><h3>ScraperAPI</h3><p style="max-width:400px">We recommend <a href="https://www.scraperapi.com/?fp_ref=ha33" target="_blank">https://www.scraperapi.com/</a>, which offers proxy service with many benefits such as automating IP rotation, geolocation targeting and 99% uptime guarantee. If you already have an API key for ScraperAPI, enter it here</p>
<p><input type="text" class="regular-text" placeholder="API HERE" name="manga-crawler[proxy][scraperapi]" value="<?php echo esc_attr($scraperapi);?>"/></p></td>
		</tr>
    </tbody>
</table>
