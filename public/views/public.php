<?php
/**
 * Represents the view for the public-facing component of the plugin.
 *
 * This typically includes any information, if any, that is rendered to the
 * frontend of the theme when the plugin is activated.
 *
 * @package   WP_Visual_Survey
 * @author    Tony Johnston <tonyj@johnstony.com>
 * @license   GPL-2.0+
 * @link      http://www.johnstony.com/wordpress-vidual-survey/
 * @copyright 2014 Tony Johnston
 */
?>

<!-- This file is used to markup the public facing aspect of the plugin. -->

<div id="wp-visual-survey">
    <h2>Visuall<br/>Survay</h2>

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
                        <span class="dollar-sign">$</span><span class="dollars"></span><span class="cents"></span><span
                            class="desc">up front</span>
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
    <h2>Featured<br/>Results</h2>

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