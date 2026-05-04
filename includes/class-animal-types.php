<?php
/**
 * Animal Types management class
 */

class Rabbit_Register_Animal_Types {

    /**
     * Add a new animal type
     */
    public static function add_animal_type($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'rabbit_animal_types';

        return $wpdb->insert(
            $table,
            array(
                'name_sv'                        => $data['name_sv'],
                'name_en'                        => $data['name_en'],
                'slug'                           => $data['slug'],
                'enabled'                        => isset($data['enabled']) ? $data['enabled'] : 1,
                'requires_vaccination_certificate' => isset($data['requires_vaccination_certificate']) ? intval($data['requires_vaccination_certificate']) : 0,
            ),
            array('%s', '%s', '%s', '%d', '%d')
        );
    }

    /**
     * Get all animal types
     */
    public static function get_all_animal_types($enabled_only = false) {
        global $wpdb;
        $table = $wpdb->prefix . 'rabbit_animal_types';

        $where = $enabled_only ? 'WHERE enabled = 1' : '';

        return $wpdb->get_results("SELECT * FROM $table $where ORDER BY name_en ASC");
    }

    /**
     * Get animal type by ID
     */
    public static function get_animal_type($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'rabbit_animal_types';

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    /**
     * Get animal type by slug
     */
    public static function get_animal_type_by_slug($slug) {
        global $wpdb;
        $table = $wpdb->prefix . 'rabbit_animal_types';

        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE slug = %s", $slug));
    }

    /**
     * Update animal type
     */
    public static function update_animal_type($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'rabbit_animal_types';

        return $wpdb->update(
            $table,
            $data,
            array('id' => $id),
            null,
            array('%d')
        );
    }

    /**
     * Delete animal type
     */
    public static function delete_animal_type($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'rabbit_animal_types';

        return $wpdb->delete(
            $table,
            array('id' => $id),
            array('%d')
        );
    }

    /**
     * Get localized name
     */
    public static function get_localized_name($animal_type, $lang = null) {
        if (!$lang) {
            $lang = determine_locale();
        }

        if (strpos($lang, 'sv') !== false) {
            return $animal_type->name_sv;
        } else {
            return $animal_type->name_en;
        }
    }

    /**
     * Get vaccination requirements for all animal types (including disabled).
     *
     * @return array  Map of animal type ID (string) => bool.
     */
    public static function get_vaccination_requirements() {
        $types  = self::get_all_animal_types(); // all types, including disabled
        $result = array();
        foreach ( $types as $type ) {
            $result[ (string) $type->id ] = (bool) $type->requires_vaccination_certificate;
        }
        return $result;
    }
}
