<?php

// this function is meant to add image upload button to pmpro check out page for user to be able to upload their picture.
// and also for validation of the picture and using the picture as the user gravater

// Handle profile image upload during user registration
add_action('user_register', 'save_user_profile_image', 10, 1);

function save_user_profile_image($user_id)
{
    // Log function trigger
    error_log('save_user_profile_image triggered for User ID: ' . $user_id);

    // Check if the request is from a mobile device
    if (wp_is_mobile()) {
        error_log('Mobile device detected during file upload.');
    } else {
        error_log('Desktop or non-mobile device detected during file upload.');
    }

    // Check if a file is uploaded
    if (isset($_FILES['profile_image']) && !empty($_FILES['profile_image']['name'])) {
        $file = $_FILES['profile_image'];
        error_log('File details: ' . print_r($file, true));

        // Validate file type (JPEG, PNG, JPG)
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file['type'], $allowed_types)) {
            error_log('Invalid file type: ' . $file['type']);
            echo "<script>alert('Invalid file type. Please upload a JPEG or PNG image.');</script>";
            return;
        }

        // Validate file size (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            error_log('File size exceeds limit: ' . $file['size']);
            echo "<script>alert('File size exceeds the 2MB limit. Please upload a smaller file.');</script>";
            return;
        }

        // Use WordPress's media upload function to handle the file
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $upload_overrides = ['test_form' => false];
        error_log('Attempting to upload file...');

        $upload = wp_handle_upload($file, $upload_overrides);

        if (isset($upload['file'])) {
            // File uploaded successfully
            $file_url = $upload['url']; // Get the file URL
            $file_path = $upload['file']; // Get the file path

            // Save the file URL in user meta
            update_user_meta($user_id, 'profile_image', $file_url);

            // Debugging: Log the file URL for verification
            error_log('Profile image uploaded successfully: ' . $file_url);
            echo "<script>alert('Profile image uploaded successfully.');</script>";
        } else {
            // Handle upload error
            error_log('File upload failed: ' . $upload['error']);
            echo "<script>alert('File upload failed. Please try again.');</script>";
        }
    } else {
        error_log('No profile image uploaded.');
        echo "<script>alert('No profile image uploaded. Please select a file.');</script>";
    }
}


// Use custom image as user avatar
add_filter('get_avatar_url', 'use_custom_avatar', 10, 2);

function use_custom_avatar($avatar_url, $user_id)
{
    $profile_image = get_user_meta($user_id, 'profile_image', true);
    if ($profile_image) {
        // If user has a profile image, return that image URL
        return $profile_image;
    }
    // Otherwise, return the default gravatar URL
    return $avatar_url;
}

// Display profile image in user profile page
add_action('show_user_profile', 'display_profile_image_on_user_page');
add_action('edit_user_profile', 'display_profile_image_on_user_page');

function display_profile_image_on_user_page($user)
{
    $profile_image = get_user_meta($user->ID, 'profile_image', true);
    ?>
    <h3>Profile Image</h3>
    <?php if ($profile_image): ?>
        <img src="<?php echo esc_url($profile_image); ?>" alt="Profile Image" style="max-width: 150px; height: auto;">
    <?php else: ?>
        <p>No profile image uploaded.</p>
    <?php endif; ?>
<?php
}
// Add Profile Image field to PMPro Membership Edit Page
add_action('pmpro_personal_options', 'ben_add_profile_image_field_to_pmpro_edit_page');

function ben_add_profile_image_field_to_pmpro_edit_page($user)
{
    $profile_image = get_user_meta($user->ID, 'profile_image', true);
    ?>
    <h3><?php _e('Profile Image', 'paid-memberships-pro'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="profile_image"><?php _e('Profile Image', 'paid-memberships-pro'); ?></label></th>
            <td>
                <?php if ($profile_image): ?>
                    <img src="<?php echo esc_url($profile_image); ?>" alt="Profile Image"
                        style="max-width: 150px; height: auto; margin-bottom: 10px;">
                <?php endif; ?>
                <input type="file" name="profile_image" id="profile_image" />
                <p class="description">
                    <?php _e('Upload a new profile image (JPEG, PNG, max size: 2MB).', 'paid-memberships-pro'); ?>
                </p>
            </td>
        </tr>
    </table>
    <?php
}

// Handle Profile Image Upload on Membership Edit Page
add_action('personal_options_update', 'ben_save_profile_image_from_pmpro_edit_page');
add_action('edit_user_profile_update', 'ben_save_profile_image_from_pmpro_edit_page');

function ben_save_profile_image_from_pmpro_edit_page($user_id)
{
    try {
        // Check if the current user can edit the profile
        if (!current_user_can('edit_user', $user_id)) {
            throw new Exception(__('You do not have permission to edit this user.', 'paid-memberships-pro'));
        }

        // Check if a file is uploaded
        if (isset($_FILES['profile_image']) && !empty($_FILES['profile_image']['name'])) {
            $file = $_FILES['profile_image'];

            // Debugging: Log the uploaded file details
            error_log('Uploaded file details: ' . print_r($file, true));

            // Validate file type (JPEG, PNG, JPG)
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception(__('Invalid file type. Please upload a JPEG or PNG image.', 'paid-memberships-pro'));
            }

            // Validate file size (max 2MB)
            if ($file['size'] > 2 * 1024 * 1024) {
                throw new Exception(__('File size exceeds the 2MB limit. Please upload a smaller file.', 'paid-memberships-pro'));
            }

            // Use WordPress's media upload function to handle the file
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            $upload_overrides = ['test_form' => false];

            $upload = wp_handle_upload($file, $upload_overrides);

            if (isset($upload['file'])) {
                // File uploaded successfully
                $file_url = $upload['url']; // Get the file URL

                // Save the file URL in user meta
                update_user_meta($user_id, 'profile_image', $file_url);

                // Debugging: Log the file URL for verification
                error_log('Profile image updated successfully: ' . $file_url);
            } else {
                // Handle upload error
                throw new Exception(__('File upload failed: ', 'paid-memberships-pro') . $upload['error']);
            }
        } else {
            error_log('No file uploaded.');
        }
    } catch (Exception $e) {
        // Log the error message for debugging
        error_log('Error in profile image upload: ' . $e->getMessage());

        // Show an error message to the user (if needed)
        echo '<div class="error"><p>' . esc_html($e->getMessage()) . '</p></div>';
    }
}
