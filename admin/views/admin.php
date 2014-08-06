<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * Wordpress Visual Survey
 *
 * @package   WP_Visual_Survey
 * @author    Tony Johnston <tonyj@johnstony.com>
 * @license   GPL-2.0+
 * @link      http://www.johnstony.com/wordpress-visual-survey
 * @copyright 2014 Tony Johnston
 */

/**
 *    <div class="wrap">
 *
 *            <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
 *
 *            <!-- @TODO: Provide markup for your options page here. -->
 *
 *    </div>
 *
 */
class visual_survey_admin_page
{

    /**
     * template placeholder prefix
     */
    protected $tpl_prefix = '{$';

    /**
     * template placeholder postfix
     */
    protected $tpl_postfix = '}';

    /**
     * template dir
     */
    protected $tpl_dir = 'tpl';

    /**
     * this plugin's home directory
     */
    protected $plugin_dir = '/wp-content/plugins/wp-visual-survey';
    protected $plugin_url = 'options-general.php?page=wp-visual-survey';

    /**
     *
     * @var string
     */
    protected $task;

    public function __construct()
    {
        if (isset($_GET['task'])) {
            $this->task = strip_tags($_GET['task']);
        }
        $this->createOptionsPage();
        error_log("create admin menu");
    }

    /**
     * creates the AmazonSimpleAdmin admin page
     *
     */
    public function createOptionsPage()
    {
        echo '<div id="wp-visual-survey-general" class="wrap">';
        echo '<h2>WP Visual Survey</h2>';

        echo $this->getTabMenu($this->task);
        error_log("create options page");
    }

    /**
     *
     */
    protected function getTabMenu($task)
    {
        $navItemFormat = '<a href="%s" class="nav-tab %s">%s</a>';

        $nav = '<h2 class="nav-tab-wrapper">';
        $nav .= sprintf($navItemFormat, $this->plugin_url, (in_array($task, array(null, 'checkDonation'))) ? 'nav-tab-active' : '', __('Setup', 'wp-visual-survey'));
        $nav .= sprintf($navItemFormat, $this->plugin_url . '&task=options', (($task == 'options') ? 'nav-tab-active' : ''), __('Options', 'wp-visual-survey'));
        $nav .= sprintf($navItemFormat, $this->plugin_url . '&task=collections', (($task == 'collections') ? 'nav-tab-active' : ''), __('Collections', 'wp-visual-survey'));
        $nav .= sprintf($navItemFormat, $this->plugin_url . '&task=cache', (($task == 'cache') ? 'nav-tab-active' : ''), __('Cache', 'wp-visual-survey'));
        $nav .= sprintf($navItemFormat, $this->plugin_url . '&task=usage', (($task == 'usage') ? 'nav-tab-active' : ''), __('Usage', 'wp-visual-survey'));
        $nav .= sprintf($navItemFormat, $this->plugin_url . '&task=faq', (($task == 'faq') ? 'nav-tab-active' : ''), __('FAQ', 'wp-visual-survey'));

        $nav .= '</h2><br />';
        return $nav;
    }

    public function createAdminMenu()
    {
        // Add a new submenu under Options:
        add_options_page('WP Visual Survey', 'WP Visual Survey', 'manage_options', 'wp-visual-survey', array($this, 'createOptionsPage'));
        add_action('admin_head', array($this, 'getOptionsHead'));
    }

    /**
     * includes the css and js for admin page
     */
    public function getOptionsHead()
    {
        /**
         * @TODO: Add admin css and js here
         */
    }
}
