<?php
/**
 * Registration handling class
 */

class Rabbit_Register_Registration {

    public static function create($data, $files = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'rabbit_registrations';

        $animal_type = Rabbit_Register_Animal_Types::get_animal_type($data['animal_type_id']);
        if ( ! $animal_type ) {
            if ( defined('WP_DEBUG') && WP_DEBUG ) {
                error_log( 'Rabbit Register: animal type not found for id=' . intval( $data['animal_type_id'] ) );
            }
            return false;
        }
        $registration_slug  = Rabbit_Register_Database::generate_registration_slug($animal_type->slug);
        $verification_token = Rabbit_Register_Database::generate_verification_token();
        $edit_token         = Rabbit_Register_Database::generate_edit_token();

        $vaccination_certificate = '';
        if ($files && isset($files['vaccination_certificate']) && $files['vaccination_certificate']['error'] === UPLOAD_ERR_OK) {
            $vaccination_certificate = self::handle_file_upload($files['vaccination_certificate'], $registration_slug);
        }

        $result = $wpdb->insert(
            $table,
            array(
                'registration_slug'      => $registration_slug,
                'animal_type_id'         => $data['animal_type_id'],
                'animal_name'            => sanitize_text_field($data['animal_name']),
                'owner_name'             => sanitize_text_field($data['owner_name']),
                'email'                  => sanitize_email($data['email']),
                'email_verified'         => 0,
                'verification_token'     => $verification_token,
                'edit_token'             => $edit_token,
                'phone'                  => sanitize_text_field($data['phone']),
                'alternate_phone'        => isset($data['alternate_phone']) ? sanitize_text_field($data['alternate_phone']) : '',
                'emergency_contact_name' => sanitize_text_field($data['emergency_contact_name']),
                'emergency_contact_phone'=> sanitize_text_field($data['emergency_contact_phone']),
                'own_water_bottle'       => isset($data['own_water_bottle']) ? 1 : 0,
                'own_bowls'              => isset($data['own_bowls'])        ? 1 : 0,
                'own_house'              => isset($data['own_house'])        ? 1 : 0,
                'own_hay'                => isset($data['own_hay'])          ? 1 : 0,
                'own_pellets'            => isset($data['own_pellets'])      ? 1 : 0,
                'pellets_per_day'        => isset($data['pellets_per_day'])  ? sanitize_text_field($data['pellets_per_day']) : '',
                'insured'                => isset($data['insured'])          ? intval($data['insured']) : 0,
                'insurance_company'      => isset($data['insurance_company'])? sanitize_text_field($data['insurance_company']) : '',
                'insurance_number'       => isset($data['insurance_number']) ? sanitize_text_field($data['insurance_number']) : '',
                'vaccination_certificate'=> $vaccination_certificate,
                'terms_accepted'              => isset($data['terms_accepted'])   ? 1 : 0,
                'accepted_disclaimer_version' => get_option( 'rabbit_register_disclaimer_version', '' ),
                'status'                      => 'pending',
            ),
            array('%s','%d','%s','%s','%s','%d','%s','%s','%s','%s','%s','%s','%d','%d','%d','%d','%d','%s','%d','%s','%s','%s','%d','%s','%s')
        );

        if ($result) {
            return array(
                'slug'               => $registration_slug,
                'verification_token' => $verification_token,
                'edit_token'         => $edit_token,
            );
        }

        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log( 'Rabbit Register: DB insert failed. Error: ' . $wpdb->last_error );
        }
        return false;
    }

    public static function update($registration_slug, $data, $files = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'rabbit_registrations';

        $update_data = array(
            'animal_name'             => sanitize_text_field($data['animal_name']),
            'owner_name'              => sanitize_text_field($data['owner_name']),
            'email'                   => sanitize_email($data['email']),
            'phone'                   => sanitize_text_field($data['phone']),
            'alternate_phone'         => isset($data['alternate_phone']) ? sanitize_text_field($data['alternate_phone']) : '',
            'emergency_contact_name'  => sanitize_text_field($data['emergency_contact_name']),
            'emergency_contact_phone' => sanitize_text_field($data['emergency_contact_phone']),
            'own_water_bottle'        => isset($data['own_water_bottle']) ? 1 : 0,
            'own_bowls'               => isset($data['own_bowls'])        ? 1 : 0,
            'own_house'               => isset($data['own_house'])        ? 1 : 0,
            'own_hay'                 => isset($data['own_hay'])          ? 1 : 0,
            'own_pellets'             => isset($data['own_pellets'])      ? 1 : 0,
            'pellets_per_day'         => isset($data['pellets_per_day'])  ? sanitize_text_field($data['pellets_per_day']) : '',
            'insured'                 => isset($data['insured'])          ? intval($data['insured']) : 0,
            'insurance_company'       => isset($data['insurance_company'])? sanitize_text_field($data['insurance_company']) : '',
            'insurance_number'        => isset($data['insurance_number']) ? sanitize_text_field($data['insurance_number']) : '',
            'terms_accepted'          => isset($data['terms_accepted'])   ? 1 : 0,
        );

        if ($files && isset($files['vaccination_certificate']) && $files['vaccination_certificate']['error'] === UPLOAD_ERR_OK) {
            $update_data['vaccination_certificate'] = self::handle_file_upload($files['vaccination_certificate'], $registration_slug);
        }

        // If the registrant is re-accepting a new disclaimer version, record it.
        if ( ! empty( $data['terms_accepted'] ) && ! empty( $data['disclaimer_version'] ) ) {
            $update_data['accepted_disclaimer_version'] = sanitize_text_field( $data['disclaimer_version'] );
            $update_data['terms_accepted'] = 1;
        }

        return $wpdb->update(
            $table,
            $update_data,
            array('registration_slug' => $registration_slug),
            null,
            array('%s')
        );
    }

    public static function verify_email($token) {
        global $wpdb;
        $table = $wpdb->prefix . 'rabbit_registrations';

        $registration = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE verification_token = %s AND email_verified = 0",
            $token
        ));

        if (!$registration) {
            return false;
        }

        $wpdb->update(
            $table,
            array('email_verified' => 1, 'status' => 'active'),
            array('id' => $registration->id),
            array('%d', '%s'),
            array('%d')
        );

        return $registration;
    }

    public static function get_by_slug($registration_slug) {
        global $wpdb;
        $table = $wpdb->prefix . 'rabbit_registrations';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE registration_slug = %s",
            $registration_slug
        ));
    }

    public static function get_by_slug_and_edit_token($slug, $edit_token) {
        global $wpdb;
        $table = $wpdb->prefix . 'rabbit_registrations';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE registration_slug = %s AND edit_token = %s AND email_verified = 1",
            $slug,
            strtoupper(sanitize_text_field($edit_token))
        ));
    }

    private static function handle_file_upload($file, $slug) {
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/rabbit-registrations/';

        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }

        $file_ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = array('jpg', 'jpeg', 'png', 'pdf');

        if (!in_array($file_ext, $allowed_exts)) {
            return '';
        }

        $filename    = $slug . '_vaccination_' . time() . '.' . $file_ext;
        $target_file = $target_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            return $upload_dir['baseurl'] . '/rabbit-registrations/' . $filename;
        }

        return '';
    }

    /**
     * Delete a registration and its uploaded vaccination certificate (if any).
     */
    public static function delete( $slug ) {
        global $wpdb;
        $table = $wpdb->prefix . 'rabbit_registrations';

        // Remove the uploaded file first.
        $registration = self::get_by_slug( $slug );
        if ( $registration && $registration->vaccination_certificate ) {
            $upload_dir = wp_upload_dir();
            $file_path  = str_replace(
                $upload_dir['baseurl'],
                $upload_dir['basedir'],
                $registration->vaccination_certificate
            );
            if ( file_exists( $file_path ) ) {
                unlink( $file_path );
            }
        }

        return $wpdb->delete(
            $table,
            array( 'registration_slug' => $slug ),
            array( '%s' )
        );
    }
}
