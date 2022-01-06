<?php
// This is a value that will be recorded in the license manager data so you can identify licenses for this item/product.
define('MDR_{NICK}_CRAWLER_ITEM_REFERENCE', 'Madara - {SITENAME} Crawler');

define('MDR_{NICK}_CRAWLER_LICENSE_KEY', 'mangabooth_{SITENAME}_crawler_license_key');

add_action('admin_menu', 'madara_{SITENAME}_crawler_license_menu');

function madara_{SITENAME}_crawler_license_menu()
{
    add_options_page('Madara - {SITENAME} Crawler License Activation Menu', 'Madara - {SITENAME} Crawler License', 'manage_options', 'madara-{SITENAME}-crawler', 'madara_{SITENAME}_crawler_license_management_page');
}

function madara_{SITENAME}_crawler_license_management_page()
{
    echo '<div class="wrap">';
    echo '<h2>'.esc_html__( 'Madara - {SITENAME} Crawler License Management', WP_MCL_TD ).'</h2>';

    /*** License activate button was clicked ***/
    if (isset($_REQUEST['activate_license'])) {
        $license_key = $_REQUEST[MDR_{NICK}_CRAWLER_LICENSE_KEY];

        update_option(MDR_{NICK}_CRAWLER_LICENSE_KEY, $license_key);
    }
    /*** End of license activation ***/

    /*** License activate button was clicked ***/
    if (isset($_REQUEST['deactivate_license'])) {
        update_option(MDR_{NICK}_CRAWLER_LICENSE_KEY, '');
    }
    /*** End of sample license deactivation ***/

    ?>
    <p><?php esc_html_e( 'Please enter the license key for this product to activate it. You were given a license key when you purchased this item.', WP_MCL_TD ); ?></p>
    <form action="" method="post">
        <table class="form-table">
            <tr>
                <th style="width:100px;"><label for="<?php echo MDR_{NICK}_CRAWLER_LICENSE_KEY; ?>">License Key</label></th>
                <td><input class="regular-text" type="text" id="<?php echo MDR_{NICK}_CRAWLER_LICENSE_KEY; ?>"
                           name="<?php echo MDR_{NICK}_CRAWLER_LICENSE_KEY; ?>"
                           value="<?php echo get_option(MDR_{NICK}_CRAWLER_LICENSE_KEY); ?>"></td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" name="activate_license" value="Activate" class="button-primary"/>
            <input type="submit" name="deactivate_license" value="Deactivate" class="button"/>
        </p>
    </form>
    <?php

    echo '</div>';
}

function madara_{SITENAME}_crawler_admin_notice__warning()
{
    $class = 'notice notice-warning is-dismissible';
    $message = sprintf(__('{SITENAME} Crawler Plugin have not activated, you should activate this plugin to use it,  %1$sactivate.%2$s ', 'madara'), '<a href="' . admin_url('options-general.php?page=madara-{SITENAME}-crawler') . '">', '</a>');

    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
}
