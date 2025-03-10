<?php
/*
Plugin Name: Multi-Date Picker Field for Gravity Forms
Plugin URI: https://gravityranger.com/gravity-forms-multi-date-picker/
Description: Allows selection of multiple dates in a single Gravity Forms text field.
Version: 1.1
Author: Chris Eggleston
Author URI: https://gravityranger.com
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

/*
 * Allows selection of multiple dates in a single Gravity Forms text field.
 *
 * Step-by-Step Tutorial: https://gravityranger.com/gravity-forms-multi-date-picker/
 *
 * Instructions: 
 * 
 * 1. Create your form
 *    1. Add a single-line text field to your form
 *    2. Take note of the field ID
 * 2. Modify the configuration settings in the code snippet
 *    1. Add your form ID
 *    2. Add your field ID
 *    3. Enter your desired format
 * 3. Install the snippet.
 *    1. Zip up the file and install it as a regular plugin
 *        - plugin file here: https://gravityranger.com/gravity-forms-multi-date-picker/
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Configuration for multi-date picker fields
$custom_functionality_config = [
    [
        'form_id' => 1,           // Enter the ID of the form you want to target
        'field_id' => 6,          // Enter the ID of the field within that form
        'date_format' => 'mm/dd/yy',  // Enter the desired date format
    ],
];

// Enqueue necessary scripts and styles
add_action('wp_enqueue_scripts', 'enqueue_custom_functionality_scripts');
function enqueue_custom_functionality_scripts() {
    wp_enqueue_script('jquery-ui-datepicker');
}

// Add inline CSS for multi-date picker styling
add_action('wp_head', 'custom_functionality_css');
function custom_functionality_css() {
    echo "
    <style>
        .ui-multidatespicker, .ui-datepicker {
            font-family: Arial, sans-serif !important;
            background-color: white !important;
            border: 1px solid #ddd !important;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1) !important;
            padding: 10px !important;
            z-index: 1000 !important;
            border-radius: 4px !important;
            position: relative;
            overflow: hidden;
        }
        .gf-multi-date-field {
            position: relative;
        }
        .gf-multi-date-field input {
            padding-right: 30px;
        }
        .gf-multi-date-field .calendar-icon {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
            width: 16px;
            height: 16px;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"24\" height=\"24\" viewBox=\"0 0 24 24\"><path fill=\"%23999\" d=\"M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v15c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 17H5V8h14v12zm-7-3c1.66 0 2.99-1.34 2.99-3S13.66 11 12 11s-3 1.34-3 3 1.34 3 3 3z\"/></svg>');
            background-size: 16px;
            background-repeat: no-repeat;
            background-position: center;
        }
        .ui-datepicker-close-btn {
            position: absolute;
            top: 8px;
            right: 10px;
            width: 20px;
            height: 20px;
            background-color: #f7f7f7;
            color: #333;
            border: 1px solid #ccc;
            border-radius: 50%;
            font-size: .8em;
            cursor: pointer;
            line-height: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
        .ui-datepicker-close-btn:hover {
            background-color: #eee;
        }
        .ui-state-highlight {
            background-color: #e5f4fb !important;
            color: #333 !important;
            border-radius: 4px !important;
        }
    </style>
    ";
}

// JavaScript for initializing the multi-date picker
add_action('wp_footer', 'initialize_custom_functionality_script');
function initialize_custom_functionality_script() {
    global $custom_functionality_config;
    if (empty($custom_functionality_config)) {
        return;
    }
    ?>
    <script type="text/javascript">
    function initializeMultiDatePickers() {
        <?php foreach ($custom_functionality_config as $config): ?>
            const fieldSelector = "#input_<?php echo esc_js($config['form_id']); ?>_<?php echo esc_js($config['field_id']); ?>";
            const hiddenFieldSelector = fieldSelector + "_hidden";
            const dateFormat = "<?php echo esc_js($config['date_format']); ?>";

            // Maintain selected dates across validation reloads
            const selectedDates = new Set((jQuery(fieldSelector).val() || '').split(', ').filter(Boolean));

            jQuery(fieldSelector).wrap('<div class="gf-multi-date-field"></div>');
            jQuery(fieldSelector).after('<span class="calendar-icon"></span>');

            // Add hidden field if not present
            if (!jQuery(hiddenFieldSelector).length) {
                jQuery(fieldSelector).after(`<input type="hidden" id="${hiddenFieldSelector.replace('#', '')}" name="${hiddenFieldSelector.replace('#', '')}" value="${Array.from(selectedDates).join(', ')}">`);
            }

            jQuery(fieldSelector).datepicker({
                dateFormat: dateFormat,
                beforeShow: function(input, inst) {
                    setTimeout(function() {
                        if (!jQuery(".ui-datepicker-close-btn").length) {
                            jQuery(inst.dpDiv).append('<button type="button" class="ui-datepicker-close-btn">&times;</button>');
                            jQuery(".ui-datepicker-close-btn").on("click", function() {
                                jQuery(fieldSelector).datepicker("hide");
                            });
                        }
                    }, 10);
                },
                onChangeMonthYear: function(year, month, inst) {
                    setTimeout(function() {
                        if (!jQuery(".ui-datepicker-close-btn").length) {
                            jQuery(inst.dpDiv).append('<button type="button" class="ui-datepicker-close-btn">&times;</button>');
                            jQuery(".ui-datepicker-close-btn").on("click", function() {
                                jQuery(fieldSelector).datepicker("hide");
                            });
                        }
                    }, 10);
                },
                beforeShowDay: function(date) {
                    const dateString = jQuery.datepicker.formatDate("mm/dd/yy", date);
                    return [true, selectedDates.has(dateString) ? "ui-state-highlight" : ""];
                },
                onSelect: function(dateText) {
                    if (selectedDates.has(dateText)) {
                        selectedDates.delete(dateText);
                    } else {
                        selectedDates.add(dateText);
                    }

                    const datesArray = Array.from(selectedDates);
                    jQuery(fieldSelector).val(datesArray.join(", "));
                    jQuery(hiddenFieldSelector).val(datesArray.join(", "));
                    jQuery(fieldSelector).datepicker("refresh");

                    // Keep the datepicker open after selection
                    setTimeout(() => jQuery(fieldSelector).datepicker("show"), 0);
                }
            });

            // Ensure datepicker opens on icon click or input focus
            jQuery(fieldSelector).parent().find(".calendar-icon").on("click", function() {
                jQuery(fieldSelector).datepicker("show");
            });
            jQuery(fieldSelector).on("focus click", function() {
                jQuery(this).datepicker("show");
            });
        <?php endforeach; ?>
    }

    // Initial call and re-initialize on form render
    jQuery(document).ready(function() {
        initializeMultiDatePickers();
    });
    jQuery(document).on('gform_post_render', function() {
        initializeMultiDatePickers();
    });
    </script>
    <?php
}

// Validation for the multi-date picker field based on 'required' setting
add_filter('gform_field_validation', 'validate_custom_functionality_field', 10, 4);
function validate_custom_functionality_field($result, $value, $form, $field) {
    global $custom_functionality_config;
    foreach ($custom_functionality_config as $config) {
        if ($form['id'] == $config['form_id'] && $field->id == $config['field_id']) {
            if ($field->isRequired && empty($value)) {
                $result['is_valid'] = false;
                $result['message'] = 'Please select at least one date.';
            }
        }
    }
    return $result;
}
?>
