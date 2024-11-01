<?php

/**
 * @internal never define functions inside callbacks.
 * these functions could be run multiple times; this would result in a fatal error.
 */

/**
 * custom option and settings
 */
function unienvios_settings_init()
{
    // Register a new setting for "unienvios" page.
    register_setting('unienvios', 'unienvios_options');

    // Register a new section in the "unienvios" page.
    add_settings_section(
        'unienvios_section_developers',
        __('', 'unienvios'),
        'unienvios_section_developers_callback',
        'unienvios'
    );

    // Register a new field in the "unienvios_section_developers" section, inside the "unienvios" page.
    add_settings_field(
        'unienvios_field_pill', // As of WP 4.6 this value is used only internally.
        // Use $args' label_for to populate the id inside the callback.
        __('Credenciais da Unienvios', 'unienvios'),
        'unienvios_field_pill_cb',
        'unienvios',
        'unienvios_section_developers',
        array(
            'label_for'         => 'unienvios_field_pill',
            'class'             => 'unienvios_row',
            'unienvios_custom_data' => 'custom',
        )
    );
}

/**
 * Register our unienvios_settings_init to the admin_init action hook.
 */
add_action('admin_init', 'unienvios_settings_init');


/**
 * Custom option and settings:
 *  - callback functions
 */


/**
 * Developers section callback function.
 *
 * @param array $args  The settings array, defining title, id, callback.
 */
function unienvios_section_developers_callback($args)
{
?>
    <p id="<?php echo esc_attr($args['id']); ?>"><?php esc_html_e('', 'unienvios'); ?></p>
<?php
}

/**
 * Pill field callbakc function.
 *
 * WordPress has magic interaction with the following keys: label_for, class.
 * - the "label_for" key value is used for the "for" attribute of the <label>.
 * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
 * Note: you can add custom key value pairs to be used inside your callbacks.
 *
 * @param array $args
 */
function unienvios_field_pill_cb($args)
{
    // Get the value of the setting we've registered with register_setting()
    $options = get_option('unienvios_options');
?>
    <label for=""> Email
        <input type="text" placeholder="Email" name="unienvios_options[email]" value="<?php echo esc_html($options['email']) ?>">
    </label>
    <label for="">Senha
        <input type="password" placeholder="Senha" name="unienvios_options[senha]" value="<?php echo esc_html( $options['senha']) ?>">
    </label>
<?php
}

/**
 * Add the top level menu page.
 */
function unienvios_options_page()
{
    add_menu_page(
        'Unienvios',
        'Unienvios',
        'manage_options',
        'unienvios',
        'unienvios_options_page_html',
        plugins_url( 'unienvios-plugin/assets/icon/favicon.7d40b497-svg-20px.png')
    );
}


/**
 * Register our unienvios_options_page to the admin_menu action hook.
 */
add_action('admin_menu', 'unienvios_options_page');


/**
 * Top level menu callback function
 */
function unienvios_options_page_html()
{
    // check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // add error/update messages

    // check if the user have submitted the settings
    // WordPress will add the "settings-updated" $_GET parameter to the url
    if (isset($_GET['settings-updated'])) {
        // add settings saved message with the class of "updated"
        add_settings_error('unienvios_messages', 'unienvios_message', __('Settings Saved', 'unienvios'), 'updated');
    }

    // show error/update messages
    settings_errors('unienvios_messages');
?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            // output security fields for the registered setting "unienvios"
            settings_fields('unienvios');
            // output setting sections and their fields
            // (sections are registered for "unienvios", each field is registered to a specific section)
            do_settings_sections('unienvios');
            // output save settings button
            submit_button('Salvar');
            ?>
        </form>
    </div>
<?php
}