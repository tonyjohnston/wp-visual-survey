<?php
/**
 * @package   WP_Visual_Survey
 * @author    Tony Johnston <tony.johnston@gmail.com>
 * @license   GPL-2.0+
 * @link      http://www.johnstony.com
 * @copyright 2014 Tony Johnston
 */


/**
 * Action triggered by acf/save_post to ensure that ACF field values are updated from the API if necessary
 *
 */
function visual_survey_save_postdata()
{ // Don't do anything for WP's autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    // Check auth
    if ('visual-survey' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $_POST['post'])) {
            return;
        }
    }
    $post_id = $_POST['post'];

    // Get results custom field values
    if (get_field('results_qualities', $post_id)) {
        while (has_sub_field('results_qualities', $post_id)) {
            $result_item = array();
            if (get_sub_field('results_qualities')) {
                foreach (get_sub_field('results_qualities') as $qualities) {
                    $result_item['results_qualities'][] = $qualities;
                }
            }
            if (get_sub_field('results_list')) {
                while (has_sub_field('results_list')) {
                    // We have to have a guid to get started.
                    if (get_sub_field('results_guid')) {
                        $result_guid = get_sub_field('results_guid');
                        $result_description = get_sub_field('results_description');
                        $result_images = get_sub_field('results_images');
                        $result_legal = get_sub_field('results_legal');
                        $result_cta_url = get_sub_field('results_cta_url');
                        $result_values = ajax_get_api_results_info($result_guid, $post_id);
                        $auto_update = "auto";
                        // Update name and price, but only if set to auto
                        if (get_sub_field('result')) {
                            $results = get_sub_field('result');
                            foreach ($results as $key => $result) {
                                $auto_update = $result['auto_update'];
                                if ("auto" == $auto_update) {
                                    $results[$key]['results_name'] = $result_values['Name'];
                                    $results[$key]['results_price'] = $result_values['PricingDetails']['DownPayment'];
                                }
                            }
                        }
                        // Update description
                        if ("auto" == $auto_update) {
                            if (empty($result_values['Description'])) {
                                $result_description = $result_values['Description'];
                            }
                        }
                        // Update legal
                        if ("auto" == $auto_update || empty($result_legal)) {
                            $result_legal = "+ $" . $result_values['PricingDetails']['MonthlyPayment'] . " x " .
                                $result_values['PricingDetails']['NoOfInstallment'] .
                                "/mo. If you cancel wireless service, "
                                . "remaining balance on phone becomes due. 0% APR O.A.C "
                                . "for well- qualified buyers. Qual’g service req’d.";
                        }
                        // Update images only if there isn't already a value in this field
                        // otherwise we'll be downloading a new copy every time the survey is updated.
                        if (get_sub_field('results_images')) {
                            $result_images = get_sub_field('results_images');
                            if (is_array($result_images)) {
                                foreach ($result_images as $key => $result_image) {
                                    if (!empty($result_values['FullImage1']) && empty($result_image['results_image'])) {
                                        $result_images[$key]['results_image'] = api_media_sideload(VISUAL_SURVEY_API . $result_values['FullImage1'], $post_id, $result_values['Name']);
                                    }
                                    if (!empty($result_values['ReviewImagePath']) && empty($result_image['review_image'])) {
                                        $result_images[$key]['review_image'] = api_media_sideload(VISUAL_SURVEY_API . str_replace(".", "_", $result_values['ReviewImageAltTag']) . "/5/rating.gif", $post_id, $result_values['Name']);
                                    }
                                }
                            }
                        }
                        $result_item['results_list'][] = array('results_guid' => $result_guid, 'result' => $results, 'results_description' => $result_description, 'results_images' => $result_images, 'results_legal' => $result_legal, 'results_cta_url' => $result_cta_url);
                    }
                    $result_update[] = $result_item;
                }
            }
        }
        update_field('results_qualities', $result_update, $post_id);
    }
}

add_action('acf/save_post', 'visual_survey_save_postdata', 20);

/**
 * Get images from the API
 *
 * @param string $url
 * @param int $post_id
 * @param string $desc
 *
 * @return int|bool|object
 */
function api_media_sideload($url, $post_id, $desc = '')
{
    $tmp = download_url($url);

    preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $url, $matches);
    $file_array['name'] = basename($matches[0]);
    $file_array['tmp_name'] = $tmp;

    // If error storing temporarily, unlink
    if (is_wp_error($tmp)) {
        @unlink($file_array['tmp_name']);
        $file_array['tmp_name'] = '';
    }

    // do the validation and storage stuff
    $id = media_handle_sideload($file_array, $post_id, $desc);

    // If error storing permanently, unlink
    if (is_wp_error($id)) {
        @unlink($file_array['tmp_name']);
        return $id;
    }

    // Return the Post ID of the media attachment
    return !empty($id) ? $id : false;
}

/**
 * Load question groups from ACF to populate answer checkboxes in the admin.
 *
 * @param array $field
 * @return array
 */
function acf_load_questions($field)
{
    // Load answers into checkbox field for result qualities admin
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

add_filter('acf/load_field/name=results_qualities', 'acf_load_questions');

/**
 * A "most popular items" shortcode. Items are manually set to popular.
 *
 * @return string
 */
function top_results_shortcode()
{
    global $post;
    if (get_field('top_results')) {
        $result_name = array();
        $slides = array();
        $target = 1;
        $result_slide = '';
        $result_nav = '';
        while (has_sub_field('top_results')) {
            $result_name[] = get_sub_field('results_name');
            $slides = get_sub_field('results_slides');
            $result_slide .= '<div class="top-results-slider">';
            $result_slide .= '<div id="top-results-orbit-target-' . $target . '">';
            foreach ($slides as $key => $slide) {
                $result_slide .= '<div class="top-results-slide"><div class="image-container-slide" style="background-image: url(' . wp_get_attachment_url($slide['header_image']) . ')"></div>';
                $result_slide .= '<div class="body-container-slide">' . $slide['slide_body'] . '</div></div>';
            }
            $result_slide .= "</div></div>";
            $target++;
        }
        foreach ($result_name as $key => $value) {
            $result_nav .= '
                <div id="left-nav-' . $key . '" class="top-results-left-nav">
                    <div class="text-cells">
                    <div class="text-cells-center">
                    <a href="#" class="text-cells">' . $value . '</a>
                    </div>
                    </div>
                </div>';
        }
    }
    $output = '<div id="top-results-container">' .
        '<div id="nav-container">' . $result_nav . '</div>' .
        '<div id="top-results-orbit-vertical">' . $result_slide . '</div>' .
        '</div>';
    return $output;
}

add_shortcode('top-results', 'top_results_shortcode');

/**
 * Control which columns show in the admin panel for visual survey post types.
 *
 * @param $columns
 * @return array
 */
function visual_survey_columns($columns)
{

    $new_columns = array(
        'shortcode' => 'Shortcode'
    );
    $columns = array_slice($columns, 0, 2, true) + $new_columns + array_slice($columns, 3, count($columns) - 1, true);

    return $columns;
}

add_filter('manage_edit-visual-survey_columns', 'visual_survey_columns');


/**
 * Show custom columns and values in view all visual survey post type table.
 *
 * @param string $column
 * @param $post_id
 */
function visual_survey_columns_values($column, $post_id)
{
    switch ($column) {
        case 'shortcode':
            echo '[visual-survey id="' . $post_id . '" /]';
            break;
        default:
            break;
    }
}

add_action('manage_visual-survey_posts_custom_column', 'visual_survey_columns_values', 10, 2);

/**
 * Load up the appropriate ajax responder. In this case, it's the same one for logged in users as it is for guests.
 */
if (!is_user_logged_in()) {
    add_action("wp_ajax_nopriv_visual_survey_results", "ajax_visual_survey_results");
} else {
    add_action("wp_ajax_visual_survey_results", "ajax_visual_survey_results");
}

/**
 * Ajax responder for survey resutls
 *
 * @param array $args
 */
function ajax_visual_survey_results($args)
{
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

            // Die with the json encoded array
            die(json_encode($response));
        } catch (Exception $ex) {
            if (WP_DEBUG) {
                error_log($ex->getMessage());
            }
        }
    }
}

/**
 * Featured results section allows admin to display a gallery of result items in case this survey is pushing
 * a product recommendation or used as a marketing tool.
 *
 * @param int $post_id
 * @return bool | array $response
 */
function get_featured_results($post_id = 0)
{
    global $post;
    $response = array();
    if (empty($post_id)) {
        if (!empty($post))
            $post_id = $post->ID;
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

/**
 * Helper function for shortcodes. Post ID can be passed as an argument in the shortcode. Otherwise, use current Post ID.
 *
 * @param array $args
 * @return string
 */
function visual_survey_shortcode_helper($args = array())
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
    $featured = get_featured_results($post_id);

    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 8')) {
        $ie8 = true;
    }
    ob_start();
    ?>

    <div id="wp-visual-survey">
        <h2>Visual<br/>Survey</h2>

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

    <!-- Featured Results markup -->
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
                    <a href="<?php echo $result['cta-link']; ?>" class="visual-survey-cta color-button submit-button"
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
    <!-- end Featured Results markup -->
    <?php
    $output = ob_get_contents();
    ob_clean();
    return $output;
}

add_shortcode('visual-survey', 'visual_survey_shortcode_helper');
