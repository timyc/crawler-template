<?php

    /**
 	 * Manga Crawler Tasks Source Settings Metabox template
	 */

    if( ! defined('ABSPATH') ){
        exit;
    }

    global ${NICKLOWER}_crawler_settings;

    $site          = isset( ${NICKLOWER}_crawler_settings['site'] ) ? ${NICKLOWER}_crawler_settings['site'] : '';
    $fetch_genres  = isset( ${NICKLOWER}_crawler_settings['fetch']['genres'] ) ? ${NICKLOWER}_crawler_settings['fetch']['genres'] : false;
    $fetch_tags    = isset( ${NICKLOWER}_crawler_settings['fetch']['tags'] ) ? ${NICKLOWER}_crawler_settings['fetch']['tags'] : false;
    $fetch_ratings = isset( ${NICKLOWER}_crawler_settings['fetch']['ratings'] ) ? ${NICKLOWER}_crawler_settings['fetch']['ratings'] : false;
    $fetch_views   = isset( ${NICKLOWER}_crawler_settings['fetch']['views'] ) ? ${NICKLOWER}_crawler_settings['fetch']['views'] : false;

?>

<table class="form-table">
    <tbody>
        <tr>
            <th scope="row">
                <label><?php esc_html_e('Fetch Additional Data', WP_MCL_TD); ?></label>
            </th>
            <td>
                <label for="fetch_genres">
                    <input type="checkbox" name="manga-crawler[fetch][genres]" id="fetch_genres" value="1" <?php checked( $fetch_genres, 1 ); ?>><?php esc_html_e('Fetch Manga Genres', WP_MCL_TD); ?>
                </label>
                <br>
                <label for="fetch_tags">
                    <input type="checkbox" name="manga-crawler[fetch][tags]" id="fetch_tags" value="1" <?php checked( $fetch_tags, 1 ); ?>><?php esc_html_e('Fetch Manga Tags', WP_MCL_TD); ?>
                </label>
                <br>
                <label for="fetch_ratings">
                    <input type="checkbox" name="manga-crawler[fetch][ratings]" id="fetch_ratings" value="1" <?php checked( $fetch_ratings, 1 ); ?>><?php esc_html_e('Fetch Manga Ratings', WP_MCL_TD); ?>
                </label>
                <br>
                <label for="fetch_views">
                    <input type="checkbox" name="manga-crawler[fetch][views]" id="fetch_views" value="1" <?php checked( $fetch_views, 1 ); ?>><?php esc_html_e('Fetch Manga Views', WP_MCL_TD); ?>
                </label>
            </td>
        </tr>
    </tbody>
</table>
