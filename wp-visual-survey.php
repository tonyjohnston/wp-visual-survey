<?php
/**
 * Based on Tom Mcfarlin's WordPress Plugin Boilerplate.
 *
 * A foundation off of which to build well-documented WordPress plugins that
 * also follow WordPress Coding Standards and PHP best practices.
 *
 * @package   WP_Visual_Survey
 * @author    Tony Johnston <tony.johnston@gmail.com>
 * @license   GPL-2.0+
 * @link      http://www.johnstony.com
 * @copyright 2014 Tony Johnston
 *
 * @wordpress-plugin
 * Plugin Name:       WP Visual Survey
 * Plugin URI:        http://www.johnstony.com/wordpress-visual-survey
 * Description:       Walk your readers through a categorized list of choices and show results at the end
 * Version:           1.0.0
 * Author:            Tony Johnston
 * Author URI:        http://johnston.io
 * Text Domain:       wp-visual-survey
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/tonyjohnston/wordpress-visual-survey
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once(plugin_dir_path(__FILE__) . 'public/class-wp-visual-survey.php');

/*
 * Register hooks that are fired when the plugin is activated or deactivated.
 * When the plugin is deleted, the uninstall.php file is loaded.
 */
register_activation_hook(__FILE__, array('WP_Visual_Survey', 'activate'));
register_deactivation_hook(__FILE__, array('WP_Visual_Survey', 'deactivate'));

add_action('plugins_loaded', array('WP_Visual_Survey', 'get_instance'));

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*
 *
 * The code below is intended to to give the lightest footprint possible.
 */
if (is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX)) {

    require_once(plugin_dir_path(__FILE__) . 'admin/class-wp-visual-survey-admin.php');
    add_action('plugins_loaded', array('WP_Visual_Survey_Admin', 'get_instance'));

}
