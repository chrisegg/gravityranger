/**
 * GravityRanger // Gravity Forms // Bypass empty field validation 'At least one field must be filled out'
 * https://gravityranger.com/
 *
 * This snippet will allow you to bypass validation and submit an empty form.
 * Replace the 1 in gform_validation_1 with your forms ID to apply to a specific form. Or remove _1 to apply to all forms.
 * NOTE: This will bypass validation even if fields are marked as required.
 * 
 */


add_filter( 'gform_validation_1', 'custom_gform_validation' );
function custom_gform_validation( $validation_result ) {
    // Get the form object from the validation result
    $form = $validation_result['form'];

    // Create a flag to track if any field has been filled
    $is_filled = false;

    // Loop through the form fields to check if any field is populated
    foreach ( $form['fields'] as $field ) {
        $field_value = rgpost( 'input_' . $field->id );
        if ( !empty( $field_value ) ) {
            $is_filled = true;
            break;
        }
    }

    // If no field is filled, set the validation result to true (allow empty form submission)
    if ( !$is_filled ) {
        $validation_result['is_valid'] = true;
    }

    return $validation_result;
}
