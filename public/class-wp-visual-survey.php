<?php

/**
 * Wordpress Visual Survey
 *
 * @package   WP_Visual_Survey
 * @author    Tony Johnston <tonyj@johnstony.com>
 * @license   GPL-2.0+
 * @link      http://www.johnstony.com/wordpress-visual-survey
 * @copyright 2014 Tony Johnston
 */
class WP_Visual_Survey
{

    /**
     * Plugin version, used for cache-busting of style and script file references.
     *
     * @since   1.0.0
     *
     * @var     string
     */
    const VERSION = '1.0.0';
    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected static $instance = null;
    /**
     *
     * Unique identifier for the plugin.
     *
     *
     * The variable name is used as the text domain when internationalizing strings
     * of text. Its value should match the Text Domain file header in the main
     * plugin file.
     *
     * @since    1.0.0
     *
     * @var      string
     */
    protected $plugin_slug = 'wp-visual-survey';

    /**
     * Initialize the plugin by setting localization and loading public scripts
     * and styles.
     *
     * @since     1.0.0
     */
    private function __construct()
    {

        // Load plugin text domain
        add_action('init', array($this, 'load_plugin_textdomain'));

        // Activate plugin when new blog is added
        add_action('wpmu_new_blog', array($this, 'activate_new_site'));

        // Load public-facing style sheet and JavaScript.
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Add ajax actions
        if (!is_user_logged_in()) {
            // For non-privileged users
            add_action("wp_ajax_nopriv_visual_survey_results", "ajax_visual_survey_results");
        } else {
            // For admin users
            add_action("wp_ajax_visual_survey_results", "ajax_visual_survey_results");
        }
        // For all users, always
        add_action('init', array($this, 'register_cpts'));

        // Dependencies
        if (!is_plugin_active('advanced-custom-fields/acf.php') || !function_exists('acf_register_repeater_field')) {

            add_action('admin_init', array(self, 'deactivate'));
            add_action('admin_notices', array(self, 'display_unmet_dependencies_notice'));

        }

    }

    /**
     * Return an instance of this class.
     *
     * @since     1.0.0
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance()
    {

        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Fired when the plugin is activated.
     *
     * @since    1.0.0
     *
     * @param    boolean $network_wide True if WPMU superadmin uses
     *                                       "Network Activate" action, false if
     *                                       WPMU is disabled or plugin is
     *                                       activated on an individual blog.
     */
    public static function activate($network_wide)
    {
        // Plugin requires ACF 4.0+ and Repeater field add-on

        if (!is_plugin_active('advanced-custom-fields/acf.php') || !function_exists('acf_register_repeater_field')) {

            add_action('admin_init', array(self, 'deactivate'));
            add_action('admin_notices', array(self, 'display_unmet_dependencies_notice'));
            return;
        }

        if (function_exists('is_multisite') && is_multisite()) {

            if ($network_wide) {

                // Get all blog ids
                $blog_ids = self::get_blog_ids();

                foreach ($blog_ids as $blog_id) {

                    switch_to_blog($blog_id);
                    self::single_activate();
                }

                restore_current_blog();

            } else {
                self::single_activate();
            }

        } else {
            self::single_activate();
        }

    }

    /**
     * Get all blog ids of blogs in the current network that are:
     * - not archived
     * - not spam
     * - not deleted
     *
     * @since    1.0.0
     *
     * @return   array|false    The blog ids, false if no matches.
     */
    private static function get_blog_ids()
    {

        global $wpdb;

        // get an array of blog ids
        $sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

        return $wpdb->get_col($sql);

    }

    /**
     * Fired for each blog when the plugin is activated.
     *
     * @since    1.0.0
     */
    private static function single_activate()
    {
        // @TODO: Define activation functionality here
    }

    /**
     * Fired when the plugin is deactivated.
     *
     * @since    1.0.0
     *
     * @param    boolean $network_wide True if WPMU superadmin uses
     *                                       "Network Deactivate" action, false if
     *                                       WPMU is disabled or plugin is
     *                                       deactivated on an individual blog.
     */
    public static function deactivate($network_wide)
    {

        if (function_exists('is_multisite') && is_multisite()) {

            if ($network_wide) {

                // Get all blog ids
                $blog_ids = self::get_blog_ids();

                foreach ($blog_ids as $blog_id) {

                    switch_to_blog($blog_id);
                    self::single_deactivate();

                }

                restore_current_blog();

            } else {
                self::single_deactivate();
            }

        } else {
            self::single_deactivate();
        }

    }

    /**
     * Fired for each blog when the plugin is deactivated.
     *
     * @since    1.0.0
     */
    private static function single_deactivate()
    {
        // Define deactivation functionality here
        deactivate_plugins(__FILE__);
    }

    /**
     * Display notice that activation failed due to unmet dependencies.
     *
     * @since 1.0.0
     */
    private static function display_unmet_dependencies_notice()
    {
        echo '<div class="updated"><p>The <strong>WP Visual Survey</strong> plugin has been <em>deactivated</em>.
                Please first install and activate the <strong>Advanced Custom Fields 4 and Repeater Field add-on.</strong>
                <a href="http://advancedcustomfields.com/" target="_blank">ACF can be found here.</a></p></div>';

        if (isset($_GET['activate'])) {
            unset($_GET['activate']);

        }
    }

    /**
     * Return the plugin slug.
     *
     * @since    1.0.0
     *
     * @return    Plugin slug variable.
     */
    public function get_plugin_slug()
    {
        return $this->plugin_slug;
    }

    /**
     * Fired when a new site is activated with a WPMU environment.
     *
     * @since    1.0.0
     *
     * @param    int $blog_id ID of the new blog.
     */
    public function activate_new_site($blog_id)
    {

        if (1 !== did_action('wpmu_new_blog')) {
            return;
        }

        switch_to_blog($blog_id);
        self::single_activate();
        restore_current_blog();

    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain()
    {

        $domain = $this->plugin_slug;
        $locale = apply_filters('plugin_locale', get_locale(), $domain);

        load_textdomain($domain, trailingslashit(WP_LANG_DIR) . $domain . '/' . $domain . '-' . $locale . '.mo');
        load_plugin_textdomain($domain, FALSE, basename(plugin_dir_path(dirname(__FILE__))) . '/languages/');

    }

    /**
     * Register and enqueue public-facing style sheet.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_slug . '-plugin-styles', plugins_url('assets/css/public.css', __FILE__), array(), self::VERSION);
    }

    /**
     * Register and enqueues public-facing JavaScript files.
     * Set ajaxurl. Access on client side using wp_ajax.ajaxurl
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_slug . '-plugin-script', plugins_url('assets/js/public.js', __FILE__), array('jquery'), self::VERSION);
        wp_localize_script($this->plugin_slug . '-plugin-script', 'wp_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    /**
     * Register custom post types
     *
     * @since   1.0.0
     */
    public function register_cpts()
    {
        register_post_type('wp-visual-survey', array(
                'label' => 'Visual Surveys',
                'description' => '',
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'capability_type' => 'post',
                'hierarchical' => false,
                'rewrite' => array('slug' => ''),
                'query_var' => true,
                'exclude_from_search' => false,
                'menu_position' => 5,
                'supports' => array('title', 'thumbnail',),
                'taxonomies' => array('category', 'post_tag',),
                'labels' => array(
                    'name' => 'Visual Surveys',
                    'singular_name' => 'Visual Survey',
                    'menu_name' => 'Visual Surveys',
                    'add_new' => 'Add Survey',
                    'add_new_item' => 'Add New Survey',
                    'edit' => 'Edit',
                    'edit_item' => 'Edit Survey',
                    'new_item' => 'New Survey',
                    'view' => 'View Survey',
                    'view_item' => 'View Survey',
                    'search_items' => 'Search Visual Surveys',
                    'not_found' => 'No Surveys Found',
                    'not_found_in_trash' => 'No Visual Surveys Found in Trash',
                    'parent' => 'Parent Visual Survey',
                ),
            )
        );
    }

    // Load answers into checkbox field for result qualities admin
    /**
     * @param $field
     * @return array
     */
    public function acf_load_questions($field)
    {

        $questions = get_field('question_group');
        if (is_array($field) && !empty($field)) {
            foreach ($field['sub_fields'] as $key => $value) {
                if (is_array($questions[$key])) {
                    $field['sub_fields'][$key]['label'] = $questions[$key]['question'];
                    foreach ($questions[$key]['answers'] as $answer) {
                        $choices[str_ireplace("&", 'and', $answer['answer_title'])] = $answer['answer_title'];
                    }
                    $field['sub_fields'][$key]['choices'] = $choices;
                    $choices = array();
                }
            }
        }

        return $field;
    }

    /**
     * NOTE:  Actions are points in the execution of a page or process
     *        lifecycle that WordPress fires.
     *
     *        Actions:    http://codex.wordpress.org/Plugin_API#Actions
     *        Reference:  http://codex.wordpress.org/Plugin_API/Action_Reference
     *
     * @since    1.0.0
     *
     * public function action_method_name() {
     *    // @TODO: Define your action hook callback here
     * }
     */
    /**
     * NOTE:  Filters are points of execution in which WordPress modifies data
     *        before saving it or sending it to the browser.
     *
     *        Filters: http://codex.wordpress.org/Plugin_API#Filters
     *        Reference:  http://codex.wordpress.org/Plugin_API/Filter_Reference
     *
     * @since    1.0.0
     *
     * public function filter_method_name() {
     *    // @TODO: Define your filter hook callback here
     * }
     */

    /**
     * Ajax callback for survey results
     *
     * @since 1.0.0
     *
     * @param array $args
     * @die json string
     */
    public function ajax_visual_survey_results($args)
    {
        error_log('AJAX VSURVEYRESULTS');
        if (empty($args)) {
            $args = $_POST;
        }
        if (!empty($args['data'])) {
            try {
                $data = $args['data'];
                $result_qualities = get_field('results_qualities', $args['post_id']);
                if (!empty($result_qualities) && is_array($result_qualities)) {
                    $count = 0;
                    foreach ($result_qualities as $results) {
                        $answers = array();
                        foreach ($results['results_qualities'][0] as $questions) {
                            if (is_array($questions)) {
                                $answers = array_merge($answers, $questions);
                            }
                        }
                        $matches = count(array_intersect($answers, $data));
                        if ($matches > $count) {
                            $count = $matches;
                            $best_fit = $results['results_list'][0];
                        }
                    }
                    $response['name'] = $best_fit['result'][0]['results_name'];
                    $response['description'] = $best_fit['results_description'];
                    $response['price'] = $best_fit['result'][0]['results_price'];
                    $response['image'] = wp_get_attachment_url($best_fit['results_images'][0]['results_image']);
                    $response['cents'] = (int)(($response['price'] - (int)$response['price']) * 100);
                    if ($response['cents'] < 1) {
                        $response['cents'] = '';
                    }
                    $response['cta-link'] = $best_fit['results_cta_url'];
                    $response['review'] = wp_get_attachment_url($best_fit['results_images'][0]['review_image']);
                    $response['legal'] = $best_fit['results_legal'];
                }
                die(json_encode($response));
            } catch (Exception $ex) {
                if (WP_DEBUG) {
                    error_log($ex->getMessage());
                }
            }
        }
    }

    /**
     * Visual Survey shortcode handler
     *
     * @global WP_Post $post
     * @param array $args
     * @return boolean
     */
    public function visual_survey_shortcode_helper($args = array())
    {
        global $post;
        if (empty($args) && empty($post)) {
            return false;
        }
        if (is_numeric($args['id'])) {
            $post_id = $args['id'];
        } else {
            $post_id = $post->ID;
        }

        if (get_field('question_group', $post_id)) {
            $q = 1;
            $questions = '';
            while (has_sub_field('question_group', $post_id)) {
                $questions .= '<div class="question-group"><p class="visual-survey-instructions">Question ' . $q . ' (of 3):</p>';
                $questions .= '<h3>' . get_sub_field('question') . '</h3>';
                if (get_sub_field('answers')) {
                    $questions .= '<div class="clear"><div class="visual-survey-container" data-id="' . $post_id . '">'
                        . '<div class="visual-survey"><ul>';
                    while (has_sub_field('answers', $post_id)) {
                        $questions .= '<li style="background: url(' . get_sub_field('answer_image') . ');">'
                            . '<input type="checkbox" id="' . get_sub_field('answer_title') . '" /><label for="' . get_sub_field('answer_title') . '"><div class="checked-img"></div><span>' . get_sub_field('answer_title') . '</span></label></li>';
                    }
                    $questions .= "</ul></div></div></div></div>";
                }
                $q++;
            }
        }
        $featured = $this->get_featured_results($post_id);

        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 8')) {
            $ie8 = true;
        }
        ob_start();
        ?>

        <div id="wp-visual-survey">
            <h2>Device<br/>Connection</h2>

            <div class="questions-container clear">
                <?php echo $questions; ?>
                <!-- Result markup -->
                <div id="survey-results" class="featured-results">
                    <h3>Result:</h3>

                    <p class="featured-desc"></p>

                    <div class="survey-results-container">
                        <div id="result-image" class="featured-result">
                        </div>
                        <div class="featured-details">
                            <h4 id="result-name"></h4>

                            <div id="result-rating" class="rating"><img src="" alt=""/></div>
                            <div id="result-price" class="price">
                                <span class="dollar-sign">$</span><span class="dollars"></span><span
                                    class="cents"></span><span class="desc">up front</span>
                            </div>
                            <p id="result-legal" class="legal"></p>
                            <a id="result-cta" href="#" class="visual-survey-cta color-button submit-button clear"
                               target="_blank"><span>Explore More</span></a>
                        </div>
                    </div>
                </div>
            </div>
            <a id="start-over" href="#wp-visual-survey" class="article-cta">< Start Over</a>
            <!-- end Result markup -->
        </div>

        <!-- Featured Devices markup -->
        <div id="featured-results">
        <h2>Featured<br/>Devices</h2>

        <div class="featured-slider">
            <?php
            if (is_array($featured)){
            foreach ($featured as $result){
            ?>
            <div class="featured-results slide clear">
                <?php if ($ie8) { ?>
                <div class="featured-result"
                     style="filter: progid:DXImageTransform.Microsoft.AlphaImageLoader( src='<?php echo $result['image']; ?>', sizingMethod='image')">
                    <?php }else{ ?>
                    <div class="featured-result"
                         style="background-image: url(<?php echo $result['image']; ?>);background-position: center top;">
                        <?php } ?>
                    </div>
                    <div class="featured-details">
                        <h4><?php echo $result['name']; ?></h4>

                        <div class="rating"><img src="<?php echo $result['review']; ?>" alt="rating"/></div>
                        <div class="price">
                            <span class="dollar-sign">$</span>
						<span class="dollars">
						    <?php echo (int)$result['price']; ?>
						</span>
						<span class="cents">
						    <?php echo $result['cents']; ?>
						</span>
                            <span class="desc">up front</span>
                        </div>
                        <p class="legal"><?php echo $result['legal']; ?></p>
                        <a href="<?php echo $result['cta-link']; ?>"
                           class="visual-survey-cta color-button submit-button"
                           target="_blank"><span>Explore More</span></a>
                    </div>
                </div>
                <?php
                }
                }
                ?>
            </div>
            <!-- end .slider -->
        </div>
        <!-- end Featured Devices markup -->
        <?php
        $output = ob_get_contents();
        ob_clean();
        return $output;
    }

    /**
     * @param int $post_id
     * @return
     */
    public function get_featured_results($post_id = 0)
    {
        global $post;
        if (empty($post_id)) {
            if (!empty($post)) {
                $post_id = $post->ID;
            }
        }
        try {
            $result_qualities = get_field('results_qualities', $post_id);
            if (!empty($result_qualities) && is_array($result_qualities)) {
                foreach ($result_qualities as $results) {
                    foreach ($results['results_list'] as $result) {
                        $featured = array();
                        $include_in = $result['result'][0]['include_in'];
                        if (is_string($include_in)) {
                            $include_in = array($include_in);
                        }
                        if (in_array('featured', $include_in)) {
                            $featured['name'] = $result['result'][0]['results_name'];
                            $featured['description'] = $result['results_description'];
                            $featured['price'] = $result['result'][0]['results_price'];
                            $featured['cents'] = (int)(($featured['price'] - (int)$featured['price']) * 100);
                            if ($featured['cents'] < 1) {
                                $featured['cents'] = '';
                            }
                            $featured['image'] = wp_get_attachment_url($result['results_images'][0]['results_image']);
                            $featured['review'] = wp_get_attachment_url($result['results_images'][0]['review_image']);
                            $featured['legal'] = $result['results_legal'];
                            $featured['cta-link'] = $result['results_cta_url'];

                            $response[] = $featured;
                        }
                    }
                }
            }
            return $response;
        } catch (Exception $ex) {
            if (WP_DEBUG) {
                error_log($ex->getMessage());
            }
            return false;
        }
    }
}
