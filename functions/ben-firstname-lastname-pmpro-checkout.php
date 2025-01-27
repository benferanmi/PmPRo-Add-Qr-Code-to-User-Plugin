<?php
// Add First Name and Last Name fields to PMPro checkout


// Validate the fields
function ben_validate_pmpro_last_first_name_fields()
{
    try {
        if (empty($_REQUEST['first_name'])) {
            throw new Exception(__('Please enter your first name.', 'paid-memberships-pro'));
        }
        if (empty($_REQUEST['last_name'])) {
            throw new Exception(__('Please enter your last name.', 'paid-memberships-pro'));
        }
    } catch (Exception $e) {
        // Log the error and set the message for the user
        error_log('Validation error: ' . $e->getMessage());
        pmpro_setMessage($e->getMessage(), 'pmpro_error');
    }
}
add_action('pmpro_checkout_preheader', 'ben_validate_pmpro_last_first_name_fields');

// Save the fields to user meta
function ben_save_pmpro_last_first_name_fields($user_id)
{
    try {
        if (!empty($_REQUEST['first_name'])) {
            $first_name = sanitize_text_field($_REQUEST['first_name']);
            update_user_meta($user_id, 'first_name', $first_name);
        } else {
            throw new Exception(__('Failed to save the first name. Field is empty.', 'paid-memberships-pro'));
        }

        if (!empty($_REQUEST['last_name'])) {
            $last_name = sanitize_text_field($_REQUEST['last_name']);
            update_user_meta($user_id, 'last_name', $last_name);
        } else {
            throw new Exception(__('Failed to save the last name. Field is empty.', 'paid-memberships-pro'));
        }
    } catch (Exception $e) {
        // Log the error for debugging
        error_log('Error saving user data: ' . $e->getMessage());
    }
}
add_action('pmpro_after_checkout', 'ben_save_pmpro_last_first_name_fields');

// Add First Name and Last Name to Membership Profile Page
function ben_add_first_last_name_to_pmpro_profile($user)
{
    try {
        $first_name = get_user_meta($user->ID, 'first_name', true);
        $last_name = get_user_meta($user->ID, 'last_name', true);
        ?>
        <h3><?php _e('Member Details', 'paid-memberships-pro'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="first_name"><?php _e('First Name', 'paid-memberships-pro'); ?></label></th>
                <td><input type="text" id="first_name" value="<?php echo esc_attr($first_name); ?>" class="regular-text"
                        readonly /></td>
            </tr>
            <tr>
                <th><label for="last_name"><?php _e('Last Name', 'paid-memberships-pro'); ?></label></th>
                <td><input type="text" id="last_name" value="<?php echo esc_attr($last_name); ?>" class="regular-text"
                        readonly /></td>
            </tr>
        </table>
        <?php
    } catch (Exception $e) {
        // Log any unexpected error
        error_log('Error displaying profile fields: ' . $e->getMessage());
    }
}
add_action('show_user_profile', 'ben_add_first_last_name_to_pmpro_profile');
add_action('edit_user_profile', 'ben_add_first_last_name_to_pmpro_profile');

// Add First Name and Last Name to Membership Edit Page (Read-Only)
function ben_add_first_last_name_to_pmpro_edit_page($user)
{
    try {
        $first_name = get_user_meta($user->ID, 'first_name', true);
        $last_name = get_user_meta($user->ID, 'last_name', true);
        ?>
        <h3><?php _e('Member Details', 'paid-memberships-pro'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="first_name"><?php _e('First Name', 'paid-memberships-pro'); ?></label></th>
                <td><input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($first_name); ?>"
                        class="regular-text" readonly /></td>
            </tr>
            <tr>
                <th><label for="last_name"><?php _e('Last Name', 'paid-memberships-pro'); ?></label></th>
                <td><input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($last_name); ?>"
                        class="regular-text" readonly /></td>
            </tr>
        </table>
        <?php
    } catch (Exception $e) {
        // Log any unexpected error
        error_log('Error displaying edit page fields: ' . $e->getMessage());
    }
}
add_action('pmpro_personal_options', 'ben_add_first_last_name_to_pmpro_edit_page');
