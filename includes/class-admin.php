<?php
/**
 * Admin interface class
 */

class Rabbit_Register_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
    }

    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Rabbit Register', 'rabbit-register'),
            __('Rabbit Register', 'rabbit-register'),
            'manage_options',
            'rabbit-register',
            array($this, 'render_registrations_page'),
            'dashicons-pets',
            30
        );

        add_submenu_page(
            'rabbit-register',
            __('Registrations', 'rabbit-register'),
            __('Registrations', 'rabbit-register'),
            'manage_options',
            'rabbit-register',
            array($this, 'render_registrations_page')
        );

        add_submenu_page(
            'rabbit-register',
            __('Animal Types', 'rabbit-register'),
            __('Animal Types', 'rabbit-register'),
            'manage_options',
            'rabbit-register-animals',
            array($this, 'render_animal_types_page')
        );

        add_submenu_page(
            'rabbit-register',
            __('Disclaimer / Terms', 'rabbit-register'),
            __('Disclaimer / Terms', 'rabbit-register'),
            'manage_options',
            'rabbit-register-disclaimer',
            array($this, 'render_disclaimer_page')
        );
    }

    /**
     * Render registrations list page
     */
    public function render_registrations_page() {
        global $wpdb;
        $table        = $wpdb->prefix . 'rabbit_registrations';
        $animal_table = $wpdb->prefix . 'rabbit_animal_types';

        $registrations = $wpdb->get_results("
            SELECT r.*, a.name_sv, a.name_en, a.slug as animal_slug
            FROM $table r
            LEFT JOIN $animal_table a ON r.animal_type_id = a.id
            ORDER BY r.created_at DESC
        ");

        ?>
        <div class="wrap">
            <h1><?php _e('Rabbit Hotel Registrations', 'rabbit-register'); ?></h1>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:120px"><?php _e('Slug', 'rabbit-register'); ?></th>
                        <th><?php _e('Animal', 'rabbit-register'); ?></th>
                        <th><?php _e('Animal Name', 'rabbit-register'); ?></th>
                        <th><?php _e('Owner', 'rabbit-register'); ?></th>
                        <th><?php _e('Phone', 'rabbit-register'); ?></th>
                        <th style="width:80px"><?php _e('Status', 'rabbit-register'); ?></th>
                        <th style="width:70px"><?php _e('Actions', 'rabbit-register'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $reg):
                        $row_id = 'rr-detail-' . esc_attr($reg->registration_slug);
                        $accessories = array();
                        if ($reg->own_water_bottle) $accessories[] = __('Water bottle', 'rabbit-register');
                        if ($reg->own_bowls)        $accessories[] = __('Bowls',        'rabbit-register');
                        if ($reg->own_house)        $accessories[] = __('House',        'rabbit-register');
                        if ($reg->own_hay)          $accessories[] = __('Hay',          'rabbit-register');
                        if ($reg->own_pellets)      $accessories[] = __('Pellets',      'rabbit-register');
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($reg->registration_slug); ?></strong></td>
                        <td><?php echo esc_html($reg->name_en); ?></td>
                        <td><?php echo esc_html($reg->animal_name); ?></td>
                        <td><?php echo esc_html($reg->owner_name); ?></td>
                        <td><?php echo esc_html($reg->phone); ?></td>
                        <td><?php echo esc_html($reg->status); ?></td>
                        <td>
                            <button type="button" class="button button-small rr-view-toggle" data-target="<?php echo $row_id; ?>">
                                <?php _e('View', 'rabbit-register'); ?>
                            </button>
                        </td>
                    </tr>
                    <tr id="<?php echo $row_id; ?>" class="rr-detail-row" style="display:none;">
                        <td colspan="7" style="background:#f9f9f9; padding:16px 20px;">
                            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                                <tr>
                                    <th colspan="4" style="text-align:left; padding:0 0 10px; font-size:14px; border-bottom:1px solid #ddd;">
                                        <?php echo esc_html($reg->registration_slug); ?> &mdash; <?php echo esc_html($reg->animal_name); ?>
                                    </th>
                                </tr>
                                <tr>
                                    <td style="padding:6px 12px 6px 0; width:25%; vertical-align:top;">
                                        <strong><?php _e('Animal type', 'rabbit-register'); ?></strong><br>
                                        <?php echo esc_html($reg->name_en); ?> / <?php echo esc_html($reg->name_sv); ?>
                                    </td>
                                    <td style="padding:6px 12px 6px 0; width:25%; vertical-align:top;">
                                        <strong><?php _e('Owner', 'rabbit-register'); ?></strong><br>
                                        <?php echo esc_html($reg->owner_name); ?>
                                    </td>
                                    <td style="padding:6px 12px 6px 0; width:25%; vertical-align:top;">
                                        <strong><?php _e('Email', 'rabbit-register'); ?></strong><br>
                                        <a href="mailto:<?php echo esc_attr($reg->email); ?>"><?php echo esc_html($reg->email); ?></a>
                                        <?php if (!$reg->email_verified): ?>
                                            <br><em style="color:#a00;"><?php _e('(not verified)', 'rabbit-register'); ?></em>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:6px 0 6px 0; width:25%; vertical-align:top;">
                                        <strong><?php _e('Phone', 'rabbit-register'); ?></strong><br>
                                        <?php echo esc_html($reg->phone); ?>
                                        <?php if ($reg->alternate_phone): ?>
                                            <br><?php echo esc_html($reg->alternate_phone); ?> <em>(<?php _e('alt', 'rabbit-register'); ?>)</em>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 12px 6px 0; vertical-align:top;">
                                        <strong><?php _e('Emergency contact', 'rabbit-register'); ?></strong><br>
                                        <?php echo esc_html($reg->emergency_contact_name); ?><br>
                                        <?php echo esc_html($reg->emergency_contact_phone); ?>
                                    </td>
                                    <td style="padding:6px 12px 6px 0; vertical-align:top;">
                                        <strong><?php _e('Accessories', 'rabbit-register'); ?></strong><br>
                                        <?php echo $accessories ? esc_html(implode(', ', $accessories)) : '—'; ?>
                                        <?php if ($reg->own_pellets && $reg->pellets_per_day): ?>
                                            <br><em><?php echo esc_html($reg->pellets_per_day); ?> / <?php _e('day', 'rabbit-register'); ?></em>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:6px 12px 6px 0; vertical-align:top;">
                                        <strong><?php _e('Insurance', 'rabbit-register'); ?></strong><br>
                                        <?php if ($reg->insured): ?>
                                            <?php _e('Yes', 'rabbit-register'); ?>
                                            <?php if ($reg->insurance_company): ?> &mdash; <?php echo esc_html($reg->insurance_company); ?><?php endif; ?>
                                            <?php if ($reg->insurance_number): ?><br><?php echo esc_html($reg->insurance_number); ?><?php endif; ?>
                                        <?php else: ?>
                                            <?php _e('No', 'rabbit-register'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:6px 0 6px 0; vertical-align:top;">
                                        <strong><?php _e('Vaccination cert.', 'rabbit-register'); ?></strong><br>
                                        <?php if ($reg->vaccination_certificate): ?>
                                            <a href="<?php echo esc_url($reg->vaccination_certificate); ?>" target="_blank"><?php _e('View file', 'rabbit-register'); ?></a>
                                        <?php else: ?>
                                            <?php _e('None uploaded', 'rabbit-register'); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="4" style="padding:8px 0 0; border-top:1px solid #ddd; color:#666; font-size:12px;">
                                        <?php _e('Registered', 'rabbit-register'); ?>: <?php echo esc_html($reg->created_at); ?>
                                        &nbsp;&bull;&nbsp;
                                        <?php _e('Updated', 'rabbit-register'); ?>: <?php echo esc_html($reg->updated_at); ?>
                                        &nbsp;&bull;&nbsp;
                                        <?php _e('Terms accepted', 'rabbit-register'); ?>: <?php echo $reg->terms_accepted ? __('Yes', 'rabbit-register') : __('No', 'rabbit-register'); ?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.rr-view-toggle').on('click', function() {
                var target = $('#' + $(this).data('target'));
                var isOpen = target.is(':visible');
                // Close any other open rows
                $('.rr-detail-row:visible').hide();
                $('.rr-view-toggle').text('<?php echo esc_js(__('View', 'rabbit-register')); ?>');
                if (!isOpen) {
                    target.show();
                    $(this).text('<?php echo esc_js(__('Close', 'rabbit-register')); ?>');
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Render animal types management page
     */
    public function render_animal_types_page() {
        $animal_types = Rabbit_Register_Animal_Types::get_all_animal_types();

        ?>
        <div class="wrap">
            <h1><?php _e('Animal Types', 'rabbit-register'); ?></h1>

            <!-- Add new animal type form -->
            <h2><?php _e('Add New Animal Type', 'rabbit-register'); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('add_animal_type', 'animal_type_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="name_sv"><?php _e('Name (Swedish)', 'rabbit-register'); ?></label></th>
                        <td><input type="text" name="name_sv" id="name_sv" required></td>
                    </tr>
                    <tr>
                        <th><label for="name_en"><?php _e('Name (English)', 'rabbit-register'); ?></label></th>
                        <td><input type="text" name="name_en" id="name_en" required></td>
                    </tr>
                    <tr>
                        <th><label for="slug"><?php _e('Slug', 'rabbit-register'); ?></label></th>
                        <td><input type="text" name="slug" id="slug" required pattern="[a-z0-9-]+"></td>
                    </tr>
                    <tr>
                        <th><label for="requires_vaccination_certificate">Requires Vaccination Certificate</label></th>
                        <td><input type="checkbox" name="requires_vaccination_certificate" id="requires_vaccination_certificate" value="1"></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="add_animal_type" class="button button-primary" value="<?php _e('Add Animal Type', 'rabbit-register'); ?>">
                </p>
            </form>

            <!-- Existing animal types -->
            <h2><?php _e('Existing Animal Types', 'rabbit-register'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Name (SV)', 'rabbit-register'); ?></th>
                        <th><?php _e('Name (EN)', 'rabbit-register'); ?></th>
                        <th><?php _e('Slug', 'rabbit-register'); ?></th>
                        <th><?php _e('Enabled', 'rabbit-register'); ?></th>
                        <th><?php _e('Vaccination Cert.', 'rabbit-register'); ?></th>
                        <th><?php _e('Actions', 'rabbit-register'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($animal_types as $animal): ?>
                    <tr>
                        <td><?php echo esc_html($animal->name_sv); ?></td>
                        <td><?php echo esc_html($animal->name_en); ?></td>
                        <td><?php echo esc_html($animal->slug); ?></td>
                        <td><?php echo $animal->enabled ? __('Yes', 'rabbit-register') : __('No', 'rabbit-register'); ?></td>
                        <td><?php echo $animal->requires_vaccination_certificate ? __('Yes', 'rabbit-register') : __('No', 'rabbit-register'); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <?php wp_nonce_field('toggle_animal_' . $animal->id, 'animal_nonce'); ?>
                                <input type="hidden" name="animal_id" value="<?php echo $animal->id; ?>">
                                <input type="submit" name="toggle_animal" class="button button-small" value="<?php echo $animal->enabled ? __('Disable', 'rabbit-register') : __('Enable', 'rabbit-register'); ?>">
                            </form>
                            <form method="post" style="display:inline;">
                                <?php wp_nonce_field('toggle_vaccination_' . $animal->id, 'animal_nonce'); ?>
                                <input type="hidden" name="animal_id" value="<?php echo $animal->id; ?>">
                                <input type="submit" name="toggle_vaccination" class="button button-small" value="<?php echo $animal->requires_vaccination_certificate ? __('No Vacc. Required', 'rabbit-register') : __('Require Vacc.', 'rabbit-register'); ?>">
                            </form>
                            <form method="post" style="display:inline;" onsubmit="return confirm('<?php _e('Are you sure?', 'rabbit-register'); ?>');">
                                <?php wp_nonce_field('delete_animal_' . $animal->id, 'animal_nonce'); ?>
                                <input type="hidden" name="animal_id" value="<?php echo $animal->id; ?>">
                                <input type="submit" name="delete_animal" class="button button-small button-link-delete" value="<?php _e('Delete', 'rabbit-register'); ?>">
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render the Disclaimer / Terms management page
     */
    public function render_disclaimer_page() {
        $message = isset($_GET['message']) ? sanitize_key($_GET['message']) : '';
        $version = get_option('rabbit_register_disclaimer_version', __('(not set)', 'rabbit-register'));
        ?>
        <div class="wrap">
            <h1><?php _e('Disclaimer / Terms', 'rabbit-register'); ?></h1>

            <?php if ($message === 'saved'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Disclaimer saved successfully.', 'rabbit-register'); ?></p>
                </div>
            <?php elseif ($message === 'saved_notified'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Disclaimer saved and all active registrants have been notified.', 'rabbit-register'); ?></p>
                </div>
            <?php elseif ($message === 'saved_no_change'): ?>
                <div class="notice notice-info is-dismissible">
                    <p><?php _e('Disclaimer saved, but the content was unchanged — no version bump or notifications sent.', 'rabbit-register'); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field('save_disclaimer', 'disclaimer_nonce'); ?>

                <h2><?php _e('Swedish Text', 'rabbit-register'); ?></h2>
                <textarea name="disclaimer_sv" rows="14" style="width:100%;"><?php echo esc_textarea(get_option('rabbit_register_disclaimer_sv', '')); ?></textarea>

                <h2><?php _e('English Text', 'rabbit-register'); ?></h2>
                <textarea name="disclaimer_en" rows="14" style="width:100%;"><?php echo esc_textarea(get_option('rabbit_register_disclaimer_en', '')); ?></textarea>

                <p>
                    <?php _e('Current version:', 'rabbit-register'); ?>
                    <code><?php echo esc_html($version); ?></code>
                </p>

                <p class="submit">
                    <input type="submit" name="save_disclaimer" class="button button-primary" value="<?php _e('Save Disclaimer', 'rabbit-register'); ?>">
                    <input type="submit" name="save_disclaimer_notify" class="button" value="<?php _e('Save &amp; Notify All Registrants', 'rabbit-register'); ?>">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Handle admin actions (add/edit/delete animal types, save disclaimer)
     */
    public function handle_admin_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Add animal type
        if (isset($_POST['add_animal_type']) && wp_verify_nonce($_POST['animal_type_nonce'], 'add_animal_type')) {
            $data = array(
                'name_sv'                        => sanitize_text_field($_POST['name_sv']),
                'name_en'                        => sanitize_text_field($_POST['name_en']),
                'slug'                           => sanitize_text_field($_POST['slug']),
                'requires_vaccination_certificate' => isset($_POST['requires_vaccination_certificate']) ? 1 : 0,
            );
            Rabbit_Register_Animal_Types::add_animal_type($data);
            wp_redirect(admin_url('admin.php?page=rabbit-register-animals&message=added'));
            exit;
        }

        // Toggle animal enabled/disabled
        if (isset($_POST['toggle_animal']) && isset($_POST['animal_id'])) {
            $animal_id = intval($_POST['animal_id']);
            if (wp_verify_nonce($_POST['animal_nonce'], 'toggle_animal_' . $animal_id)) {
                $animal = Rabbit_Register_Animal_Types::get_animal_type($animal_id);
                Rabbit_Register_Animal_Types::update_animal_type($animal_id, array('enabled' => $animal->enabled ? 0 : 1));
                wp_redirect(admin_url('admin.php?page=rabbit-register-animals&message=updated'));
                exit;
            }
        }

        // Toggle vaccination certificate requirement
        if (isset($_POST['toggle_vaccination']) && isset($_POST['animal_id'])) {
            $animal_id = intval($_POST['animal_id']);
            if (wp_verify_nonce($_POST['animal_nonce'], 'toggle_vaccination_' . $animal_id)) {
                $animal = Rabbit_Register_Animal_Types::get_animal_type($animal_id);
                Rabbit_Register_Animal_Types::update_animal_type(
                    $animal_id,
                    array('requires_vaccination_certificate' => $animal->requires_vaccination_certificate ? 0 : 1)
                );
                wp_redirect(admin_url('admin.php?page=rabbit-register-animals&message=updated'));
                exit;
            }
        }

        // Delete animal type
        if (isset($_POST['delete_animal']) && isset($_POST['animal_id'])) {
            $animal_id = intval($_POST['animal_id']);
            if (wp_verify_nonce($_POST['animal_nonce'], 'delete_animal_' . $animal_id)) {
                Rabbit_Register_Animal_Types::delete_animal_type($animal_id);
                wp_redirect(admin_url('admin.php?page=rabbit-register-animals&message=deleted'));
                exit;
            }
        }

        // Save disclaimer (with or without notification)
        if ( ( isset($_POST['save_disclaimer']) || isset($_POST['save_disclaimer_notify']) )
             && isset($_POST['disclaimer_nonce'])
             && wp_verify_nonce($_POST['disclaimer_nonce'], 'save_disclaimer') ) {

            $sv = sanitize_textarea_field($_POST['disclaimer_sv'] ?? '');
            $en = sanitize_textarea_field($_POST['disclaimer_en'] ?? '');

            $old_version = get_option('rabbit_register_disclaimer_version', '');
            $new_version = md5($sv . '###' . $en);

            update_option('rabbit_register_disclaimer_sv', $sv);
            update_option('rabbit_register_disclaimer_en', $en);

            $message = 'saved_no_change';
            if ($new_version !== $old_version) {
                update_option('rabbit_register_disclaimer_version', $new_version);
                $message = 'saved';

                if (isset($_POST['save_disclaimer_notify'])) {
                    $this->send_disclaimer_notifications();
                    $message = 'saved_notified';
                }
            }

            wp_redirect(admin_url('admin.php?page=rabbit-register-disclaimer&message=' . $message));
            exit;
        }
    }

    /**
     * Send disclaimer-changed emails to all active, verified registrants.
     */
    private function send_disclaimer_notifications() {
        global $wpdb;
        $registrations = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}rabbit_registrations
             WHERE email_verified = 1 AND status = 'active'"
        );

        $page_url = $this->get_registration_page_url();

        foreach ($registrations as $reg) {
            Rabbit_Register_Email::send_disclaimer_changed($reg, $page_url);
        }
    }

    /**
     * Find the URL of the page that contains the registration form shortcode.
     */
    private function get_registration_page_url() {
        global $wpdb;
        $page = $wpdb->get_row(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_status = 'publish'
             AND post_content LIKE '%rabbit_register_form%'
             LIMIT 1"
        );
        return $page ? get_permalink($page->ID) : home_url('/');
    }
}

new Rabbit_Register_Admin();
