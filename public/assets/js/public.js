WPVSurvey = {};
WPVSurvey.helpers = {
    setSurvey: function () {

        if (document.documentElement.clientWidth < 680) {
            //if don't already have them, add them
            var haveMovePrev = jQuery('.move-prev').length;
            var haveMoveNext = jQuery('.move-next').length;

            if (!haveMovePrev) {
                jQuery('.visual-survey-container').before('<span class="move-prev">Prev</span>');
            }
            if (!haveMoveNext) {
                jQuery('.visual-survey-container').after('<span class="move-next">Next</span>');
            }

            //hide prev on load
            jQuery('.move-prev').hide();
        }

        //move when click
        jQuery('.move-next').click(function () {
            jQuery(this).prev('.visual-survey-container').find('ul').css('float', 'right');
            jQuery(this).hide();
            jQuery(this).prevAll('.move-prev').show();
        });
        jQuery('.move-prev').click(function () {
            jQuery(this).next('.visual-survey-container').find('ul').css('float', 'none');
            jQuery(this).hide();
            jQuery(this).nextAll('.move-next').show();
        });
        //add class for checked styles
        jQuery('.visual-survey li label').click(function () {
            var checked = jQuery(this).parent().siblings('.checked');
            if (checked.length > 2) {
                jQuery(checked).each(function (i) {
                    var checkedImg = jQuery(this).find('.checked-img');
                    jQuery(checkedImg).delay(80 * i).fadeTo(200, 0, "swing");
                    jQuery(checkedImg).delay(80 * i).fadeTo(200, 1, "swing");
                });
                return;
            }
            if (jQuery(this).parent('li').hasClass('checked')) {
                jQuery(this).parent('li').removeClass('checked');
            } else {
                jQuery(this).parent('li').addClass('checked');
            }
        });

        //add next btn for ea. question, except last
        var i = 2;
        jQuery('.visual-survey').each(function () {
            jQuery(this).find('ul').append('<li class="answer-btn"><div>On to Question <span>' + i++ + '</span><br />of 3</div></li>');
        });
        jQuery('.visual-survey ul:last li:last').addClass("visual-survey-submit").html('<div>Take a look at<br /><br /><span>Your<br />Perfect<br />Result</span></div>');
        jQuery('div.question-group:first').fadeIn('fast');
        jQuery('.answer-btn').click(function (e) {
            var checked = jQuery(this).siblings('.checked');
            if (checked.length < 1) {
                jQuery(this).siblings().each(function (i) {
                    var checkedImg = jQuery(this).find('.checked-img');
                    jQuery(this).addClass('checked');
                    jQuery(this).find('label span').css("background-color", "#e20074");
                    jQuery(checkedImg).css('opacity', 0).delay(80 * i).fadeTo(200, .60, "swing");
                    jQuery(checkedImg).delay(30 * i).fadeTo(200, 0, function () {
                        jQuery(this).closest('.checked').removeClass("checked");
                        jQuery(this).removeAttr('style');
                        jQuery(this).siblings().removeAttr('style');
                    });
                });
                return;
            }
            var container = jQuery(this).closest('.question-group');
            container.fadeOut();
            container.next().fadeIn();
            jQuery('#start-over').fadeIn();
        });

        jQuery('.visual-survey-submit').click(function () {
            var selections = [];
            var guideID;
            var checked;
            checked = jQuery(this).siblings('.checked');
            if (checked.length < 1 || checked.length > 3) {
                return;
            }
            guideID = jQuery('.visual-survey-container').attr('data-id');
            selections = jQuery.map(jQuery('li.checked input'), function (n, i) {
                return n.id;
            });
            if (selections.length < 1) {
                return;
            }
            jQuery('#survey-results').hide();
            var guide = {action: "visual_survey_results", post_id: guideID, data: selections};
            jQuery.ajax({
                type: "post",
                url: wp_ajax.ajaxurl,
                data: guide,
                cache: false,
                success: function (response) {
                    response = JSON.parse(response);
                    var dollars = Math.floor(response['price']);
                    jQuery('#wp-visual-survey p.dc-subhead').slideUp();
                    jQuery('#survey-results p.featured-desc').html(response['description']);
                    jQuery('#result-name').html(response['name']);
                    jQuery('#result-price .dollars').html(dollars);
                    if (response['cents'] > 0) {
                        jQuery('#result-price .cents').html(response['cents']);
                    }
                    jQuery('#result-image').css('background-image', 'url(' + response['image'] + ')');
                    jQuery('.ie8 #result-image').css('filter', 'progid:DXImageTransform.Microsoft.AlphaImageLoader( src=\'' + response['image'] + '\', sizingMethod=\'image\')');
                    jQuery('.ie8 #result-image').css('background-image', '');
                    jQuery('#result-rating img').attr('src', response['review']);
                    jQuery('#result-cta').attr('href', response['cta-link']);
                    jQuery('#result-legal').html(response['legal']).promise().done(function () {
                        jQuery('.visual-survey-submit').closest('.question-group').fadeOut({
                            duration: 'slow', complete: function () {
                                jQuery('#survey-results').fadeIn({duration: 'fast'});
                            }
                        });
                    });
                }
            });
        });

        jQuery('#start-over').click(function (e) {
            jQuery('li.checked').removeClass('checked');
            jQuery('div.question-group').fadeOut();
            jQuery('#survey-results').fadeOut();
            jQuery('#start-over').fadeOut();
            jQuery('div.question-group:first').fadeIn();
        });

        //show submit btn as last question next btn
        //ajax request upon submit
    }
};

(function ($) {
    $(document).ready(function () {

        WPVSurvey.helpers.setSurvey();

    });

}(jQuery));