/**
 * WooCommerce - Customer Profile Picture Upload Functionality
 * With Admin Panel Delete and Change Options
 */

// 1. Add profile picture upload field to registration form
add_action('woocommerce_register_form', 'wc_custom_profile_picture_field');
function wc_custom_profile_picture_field() {
    ?>
    <p class="form-row form-row-wide">
        <label for="profile_picture"><?php esc_html_e('Profile Picture', 'woocommerce'); ?> <span class="optional">(optional)</span></label>
        <input type="file" id="profile_picture" name="profile_picture" accept="image/png, image/jpeg, image/jpg, image/gif" />
        <small><?php esc_html_e('Upload image files only (jpg, png, gif).', 'woocommerce'); ?></small>
    </p>
    <?php
}

// 2. Add form enctype for file upload
add_action('woocommerce_register_form_tag', 'wc_profile_picture_form_enctype');
function wc_profile_picture_form_enctype() {
    echo ' enctype="multipart/form-data"';
}

// 3. Save profile picture on registration
add_action('woocommerce_created_customer', 'wc_save_profile_picture');
function wc_save_profile_picture($customer_id) {
    if (!empty($_FILES['profile_picture']) && !empty($_FILES['profile_picture']['name'])) {
        
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/jpg');
        $file_type = $_FILES['profile_picture']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            wc_add_notice(__('Please upload only image files (jpg, png, gif).'), 'error');
            return;
        }
        
        if ($_FILES['profile_picture']['size'] > 2097152) {
            wc_add_notice(__('Image size cannot exceed 2MB.'), 'error');
            return;
        }
        
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('profile_picture', 0);
        
        if (is_wp_error($attachment_id)) {
            wc_add_notice(__('Error uploading image: ' . $attachment_id->get_error_message()), 'error');
        } else {
            update_user_meta($customer_id, 'wc_profile_picture_id', $attachment_id);
            wc_add_notice(__('Profile picture uploaded successfully.'), 'success');
        }
    }
}

// 4. Display profile picture in account details with edit option
add_action('woocommerce_edit_account_form', 'wc_display_profile_picture_edit');
function wc_display_profile_picture_edit() {
    $user_id = get_current_user_id();
    $profile_pic_id = get_user_meta($user_id, 'wc_profile_picture_id', true);
    
    echo '<div class="wc-profile-picture-section">';
    echo '<h3>' . esc_html__('Profile Picture', 'woocommerce') . '</h3>';
    
    if ($profile_pic_id) {
        $profile_pic_url = wp_get_attachment_image_src($profile_pic_id, 'thumbnail');
        if ($profile_pic_url) {
            echo '<p class="woocommerce-form-row form-row">';
            echo '<label>'.esc_html__('Current Profile Picture', 'woocommerce').'</label>';
            echo '<img src="'.esc_url($profile_pic_url[0]).'" style="max-width:150px; max-height:150px; display:block; margin-bottom:10px; border-radius:5px;" />';
            echo '</p>';
        }
    }
    
    // Picture change option
    ?>
    <p class="form-row form-row-wide">
        <label for="profile_picture_edit"><?php esc_html_e('Upload New Profile Picture', 'woocommerce'); ?></label>
        <input type="file" id="profile_picture_edit" name="profile_picture_edit" accept="image/png, image/jpeg, image/jpg, image/gif" />
        <small><?php esc_html_e('Uploading a new picture will replace the old one.', 'woocommerce'); ?></small>
    </p>
    </div>
    <?php
}

// 5. Add enctype to account edit form
add_action('woocommerce_edit_account_form_tag', 'wc_edit_account_form_enctype');
function wc_edit_account_form_enctype() {
    echo ' enctype="multipart/form-data"';
}

// 6. Update profile picture on account save
add_action('woocommerce_save_account_details', 'wc_update_profile_picture', 10, 1);
function wc_update_profile_picture($user_id) {
    if (!empty($_FILES['profile_picture_edit']) && !empty($_FILES['profile_picture_edit']['name'])) {
        
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/jpg');
        $file_type = $_FILES['profile_picture_edit']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            wc_add_notice(__('Please upload only image files.'), 'error');
            return;
        }
        
        if ($_FILES['profile_picture_edit']['size'] > 2097152) {
            wc_add_notice(__('Image size cannot exceed 2MB.'), 'error');
            return;
        }
        
        // Delete old picture
        $old_pic_id = get_user_meta($user_id, 'wc_profile_picture_id', true);
        if ($old_pic_id) {
            wp_delete_attachment($old_pic_id, true);
        }
        
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('profile_picture_edit', 0);
        
        if (is_wp_error($attachment_id)) {
            wc_add_notice(__('Error uploading image: ' . $attachment_id->get_error_message()), 'error');
        } else {
            update_user_meta($user_id, 'wc_profile_picture_id', $attachment_id);
            wc_add_notice(__('Profile picture updated successfully.'), 'success');
        }
    }
}

// ============= Admin Panel Functions =============

// 7. Add profile picture management section in admin user profile
add_action('show_user_profile', 'wc_admin_profile_picture_management');
add_action('edit_user_profile', 'wc_admin_profile_picture_management');
function wc_admin_profile_picture_management($user) {
    $profile_pic_id = get_user_meta($user->ID, 'wc_profile_picture_id', true);
    ?>
    <h3><?php esc_html_e('Customer Profile Picture Management', 'woocommerce'); ?></h3>
    <table class="form-table">
        <?php if ($profile_pic_id): 
            $profile_pic_url = wp_get_attachment_image_src($profile_pic_id, 'medium');
            if ($profile_pic_url):
        ?>
        <tr>
            <th><label><?php esc_html_e('Current Profile Picture', 'woocommerce'); ?></label></th>
            <td>
                <img src="<?php echo esc_url($profile_pic_url[0]); ?>" style="max-width:200px; max-height:200px; border:1px solid #ddd; padding:5px; border-radius:5px; margin-bottom:10px;" />
                <br />
                <label>
                    <input type="checkbox" name="wc_delete_profile_picture" value="1" />
                    <?php esc_html_e('Delete this picture', 'woocommerce'); ?>
                </label>
                <p class="description"><?php esc_html_e('Warning: This will permanently delete the picture.', 'woocommerce'); ?></p>
            </td>
        </tr>
        <?php endif; endif; ?>
        
        <tr>
            <th><label for="wc_new_profile_picture"><?php esc_html_e('Upload New Profile Picture', 'woocommerce'); ?></label></th>
            <td>
                <input type="file" id="wc_new_profile_picture" name="wc_new_profile_picture" accept="image/png, image/jpeg, image/jpg, image/gif" />
                <p class="description"><?php esc_html_e('Uploading a new picture will automatically replace the old one.', 'woocommerce'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

// 8. Handle profile picture update/delete from admin
add_action('edit_user_profile_update', 'wc_admin_handle_profile_picture_update');
add_action('personal_options_update', 'wc_admin_handle_profile_picture_update');
function wc_admin_handle_profile_picture_update($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    
    // Check delete option
    if (isset($_POST['wc_delete_profile_picture']) && $_POST['wc_delete_profile_picture'] == '1') {
        $old_pic_id = get_user_meta($user_id, 'wc_profile_picture_id', true);
        if ($old_pic_id) {
            wp_delete_attachment($old_pic_id, true);
            delete_user_meta($user_id, 'wc_profile_picture_id');
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>Profile picture successfully deleted.</p></div>';
            });
        }
    }
    
    // Upload new picture
    if (!empty($_FILES['wc_new_profile_picture']) && !empty($_FILES['wc_new_profile_picture']['name'])) {
        
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/jpg');
        $file_type = $_FILES['wc_new_profile_picture']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Please upload only image files (jpg, png, gif).</p></div>';
            });
            return;
        }
        
        if ($_FILES['wc_new_profile_picture']['size'] > 2097152) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Image size cannot exceed 2MB.</p></div>';
            });
            return;
        }
        
        // Delete old picture if exists
        $old_pic_id = get_user_meta($user_id, 'wc_profile_picture_id', true);
        if ($old_pic_id) {
            wp_delete_attachment($old_pic_id, true);
        }
        
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('wc_new_profile_picture', 0);
        
        if (is_wp_error($attachment_id)) {
            add_action('admin_notices', function() use ($attachment_id) {
                echo '<div class="notice notice-error"><p>Error uploading image: ' . $attachment_id->get_error_message() . '</p></div>';
            });
        } else {
            update_user_meta($user_id, 'wc_profile_picture_id', $attachment_id);
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>Profile picture successfully updated.</p></div>';
            });
        }
    }
}

// 9. Add profile picture column to users list
add_filter('manage_users_columns', 'wc_add_user_profile_picture_column');
function wc_add_user_profile_picture_column($columns) {
    $columns['wc_profile_pic'] = __('Profile Picture', 'woocommerce');
    return $columns;
}

add_action('manage_users_custom_column', 'wc_show_user_profile_picture_column', 10, 3);
function wc_show_user_profile_picture_column($value, $column_name, $user_id) {
    if ($column_name == 'wc_profile_pic') {
        $profile_pic_id = get_user_meta($user_id, 'wc_profile_picture_id', true);
        if ($profile_pic_id) {
            $profile_pic_url = wp_get_attachment_image_src($profile_pic_id, 'thumbnail');
            if ($profile_pic_url) {
                return '<img src="'.esc_url($profile_pic_url[0]).'" style="width:40px; height:40px; border-radius:50%; object-fit:cover;" />';
            }
        }
        return '—';
    }
    return $value;
}

// 10. Add quick delete action in users list
add_filter('user_row_actions', 'wc_add_user_profile_picture_quick_actions', 10, 2);
function wc_add_user_profile_picture_quick_actions($actions, $user) {
    $profile_pic_id = get_user_meta($user->ID, 'wc_profile_picture_id', true);
    
    if ($profile_pic_id && current_user_can('edit_user', $user->ID)) {
        $delete_url = wp_nonce_url(
            add_query_arg('action', 'wc_delete_profile_pic', admin_url('users.php?user=' . $user->ID)),
            'delete_profile_pic_' . $user->ID
        );
        
        $actions['delete_profile_pic'] = '<a href="' . $delete_url . '" style="color:#a00;" onclick="return confirm(\'Delete profile picture? This action cannot be undone.\');">' . __('Delete Profile Picture') . '</a>';
    }
    
    return $actions;
}

// 11. Handle quick delete action
add_action('admin_init', 'wc_handle_quick_delete_profile_picture');
function wc_handle_quick_delete_profile_picture() {
    if (isset($_GET['action']) && $_GET['action'] == 'wc_delete_profile_pic' && isset($_GET['user'])) {
        $user_id = intval($_GET['user']);
        
        if (!current_user_can('edit_user', $user_id)) {
            wp_die('You do not have permission to perform this action.');
        }
        
        check_admin_referer('delete_profile_pic_' . $user_id);
        
        $old_pic_id = get_user_meta($user_id, 'wc_profile_picture_id', true);
        if ($old_pic_id) {
            wp_delete_attachment($old_pic_id, true);
            delete_user_meta($user_id, 'wc_profile_picture_id');
        }
        
        wp_redirect(add_query_arg('profile_pic_deleted', '1', wp_get_referer()));
        exit;
    }
}

// 12. Show delete notification
add_action('admin_notices', 'wc_profile_picture_delete_notice');
function wc_profile_picture_delete_notice() {
    if (isset($_GET['profile_pic_deleted']) && $_GET['profile_pic_deleted'] == '1') {
        echo '<div class="notice notice-success"><p>Profile picture successfully deleted.</p></div>';
    }
}

// 13. Add enctype to admin profile form
add_action('user_edit_form_tag', 'wc_admin_profile_form_enctype');
function wc_admin_profile_form_enctype() {
    echo ' enctype="multipart/form-data"';
}
