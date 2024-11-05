<?php
/**
 * Gravity Forms // Limit Coupine Usage Per Customer Based on Email Address
 * https://gravityranger.com/gravity-forms-limit-coupon-per-customer/
 *
 * Snippet will compare the email address to the coupon entered if the email has already
 * been used validation fails, and a custom validation message is shown.
 *
 * Step-by-Step Tutorial: https://gravityranger.com/gravity-forms-limit-coupon-per-customer/
 *
 * 
 */

add_filter('gform_validation', 'limit_coupon_usage_per_customer');
function limit_coupon_usage_per_customer($validation_result) {
    // Update with your form ID and coupon field ID
    $form_id = 1; // Replace with your form ID
    $coupon_field_id = 3; // Replace with your coupon field ID
    $email_field_id = 4; // Replace with your email or unique identifier field ID

    $form = $validation_result['form'];
    $current_coupon = rgpost("input_{$coupon_field_id}");
    $customer_email = rgpost("input_{$email_field_id}");

    // Check if the form has the coupon field
    foreach ($form['fields'] as &$field) {
        if ($field->id == $coupon_field_id) {
            // Query past entries to check if the coupon has been used by this email
            $search_criteria = array(
                'field_filters' => array(
                    array(
                        'key' => $email_field_id,
                        'value' => $customer_email,
                    ),
                    array(
                        'key' => $coupon_field_id,
                        'value' => $current_coupon,
                    ),
                ),
            );

            $entries = GFAPI::get_entries($form_id, $search_criteria);

            // If any entry exists, the coupon was already used
            if (!empty($entries)) {
                $validation_result['is_valid'] = false;
                $field->failed_validation = true;
                $field->validation_message = 'You have already used this coupon.';
            }
            break;
        }
    }

    $validation_result['form'] = $form;
    return $validation_result;
}
