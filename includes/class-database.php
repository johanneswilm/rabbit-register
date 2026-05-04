<?php
/**
 * Database handling class for Rabbit Register plugin
 */

class Rabbit_Register_Database {

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_animal_types = $wpdb->prefix . 'rabbit_animal_types';
        $sql_animal_types = "CREATE TABLE $table_animal_types (
            id int(11) NOT NULL AUTO_INCREMENT,
            name_sv varchar(100) NOT NULL,
            name_en varchar(100) NOT NULL,
            slug varchar(50) NOT NULL,
            enabled tinyint(1) DEFAULT 1,
            requires_vaccination_certificate tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

        $table_registrations = $wpdb->prefix . 'rabbit_registrations';
        $sql_registrations = "CREATE TABLE $table_registrations (
            id int(11) NOT NULL AUTO_INCREMENT,
            registration_slug varchar(50) NOT NULL,
            animal_type_id int(11) NOT NULL,
            animal_name varchar(255) NOT NULL,
            owner_name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            email_verified tinyint(1) DEFAULT 0,
            verification_token varchar(64),
            edit_token varchar(16),
            phone varchar(50) NOT NULL,
            alternate_phone varchar(50),
            emergency_contact_name varchar(255) NOT NULL,
            emergency_contact_phone varchar(50) NOT NULL,
            own_water_bottle tinyint(1) DEFAULT 0,
            own_bowls tinyint(1) DEFAULT 0,
            own_house tinyint(1) DEFAULT 0,
            own_hay tinyint(1) DEFAULT 0,
            own_pellets tinyint(1) DEFAULT 0,
            pellets_per_day varchar(50),
            insured tinyint(1) DEFAULT 0,
            insurance_company varchar(255),
            insurance_number varchar(255),
            vaccination_certificate varchar(500),
            terms_accepted tinyint(1) DEFAULT 0,
            accepted_disclaimer_version varchar(64) DEFAULT '',
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY registration_slug (registration_slug),
            KEY animal_type_id (animal_type_id),
            KEY verification_token (verification_token),
            KEY edit_token (edit_token)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_animal_types);
        dbDelta($sql_registrations);
    }

    /**
     * Run idempotent migrations after every version bump.
     */
    public static function run_migrations() {
        global $wpdb;

        // 1. Set requires_vaccination_certificate = 1 for the 'rabbit' animal type.
        $table_animal_types = $wpdb->prefix . 'rabbit_animal_types';
        $wpdb->update(
            $table_animal_types,
            array( 'requires_vaccination_certificate' => 1 ),
            array( 'slug' => 'rabbit' ),
            array( '%d' ),
            array( '%s' )
        );

        // 2. Initialize disclaimer WP options only if they don't already exist.
        $sv = self::get_default_disclaimer( 'sv' );
        $en = self::get_default_disclaimer( 'en' );

        if ( false === get_option( 'rabbit_register_disclaimer_sv' ) ) {
            add_option( 'rabbit_register_disclaimer_sv', $sv );
        }
        if ( false === get_option( 'rabbit_register_disclaimer_en' ) ) {
            add_option( 'rabbit_register_disclaimer_en', $en );
        }
        if ( false === get_option( 'rabbit_register_disclaimer_version' ) ) {
            add_option( 'rabbit_register_disclaimer_version', md5( $sv . '###' . $en ) );
        }

        // 3. Backfill accepted_disclaimer_version on existing registrations that have it empty/null.
        $current_version = get_option( 'rabbit_register_disclaimer_version', '' );
        if ( $current_version ) {
            $table_registrations = $wpdb->prefix . 'rabbit_registrations';
            $wpdb->query( $wpdb->prepare(
                "UPDATE $table_registrations
                    SET accepted_disclaimer_version = %s
                  WHERE ( accepted_disclaimer_version IS NULL OR accepted_disclaimer_version = '' )",
                $current_version
            ) );
        }
    }

    /**
     * Return the default disclaimer text for the given language.
     *
     * @param string $lang  'sv' (default) or 'en'.
     * @return string       Plain-text bullet lines separated by "\n".
     */
    public static function get_default_disclaimer( $lang = 'sv' ) {
        if ( 'sv' === $lang ) {
            return implode( "\n", array(
                "\u{2022} Kaniner (hane) ska vara kastrerade vid ankomst.",
                "\u{2022} Djuret ska vara vaccinerat vid ankomst om det kr\u{e4}vs f\u{f6}r djurtypen. Vaccinationsintyg ska uppvisas vid bokning.",
                "\u{2022} Gunnesbo 4H har inte m\u{f6}jlighet att sj\u{e4}lva ta djuret till veterin\u{e4}r.",
                "\u{2022} Om djuret blir sjukt eller skadat kommer vi omedelbart att f\u{f6}rs\u{f6}ka n\u{e5} dig som \u{e4}gare eller angiven n\u{f6}dkontakt f\u{f6}r att djuret ska kunna h\u{e4}mtas och f\u{e5} v\u{e5}rd.",
                "\u{2022} Om vi inte lyckas n\u{e5} n\u{e5}gon kontaktperson, kommer vi att tillkalla Distriktsveterinär, som kan komma ut till g\u{e5}rden. Det \u{e4}r dock inte garanterat att veterin\u{e4}ren kan komma samma dag.",
                "\u{2022} Alla kostnader f\u{f6}r veterin\u{e4}rv\u{e5}rd debiteras djur\u{e4}garen.",
                "\u{2022} Gunnesbo 4H G\u{e5}rd ansvarar inte f\u{f6}r skador, sjukdom eller andra of\u{f6}rutsedda h\u{e4}ndelser som kan uppst\u{e5} under vistelsen. Vi g\u{f6}r alltid v\u{e5}rt b\u{e4}sta f\u{f6}r att ta hand om djuret p\u{e5} ett tryggt och omsorgsfullt s\u{e4}tt. Djurets s\u{e4}kerhet, v\u{e4}lbefinnande och omsorg \u{e4}r alltid v\u{e5}r h\u{f6}gsta prioritet.",
                "\u{2022} Bokningsavgift p\u{e5} 400 kr betalas vid bokning och \u{e4}r ej \u{e5}terbetalningsbar.",
            ) );
        }

        // English default
        return implode( "\n", array(
            "\u{2022} Rabbits (male) must be neutered upon arrival.",
            "\u{2022} The animal must be vaccinated upon arrival where required for the animal type. Vaccination certificate must be shown at booking.",
            "\u{2022} Gunnesbo 4H is not able to take the animal to the vet themselves.",
            "\u{2022} If the animal becomes sick or injured, we will immediately try to reach you as the owner or the emergency contact so the animal can be picked up and receive care.",
            "\u{2022} If we cannot reach any contact person, we will call the District Veterinarian, who can come to the farm. It is not guaranteed they can come the same day.",
            "\u{2022} All veterinary costs are charged to the animal owner.",
            "\u{2022} Gunnesbo 4H Farm is not responsible for injuries, illness or other unforeseen events during the stay. The animal's safety and well-being is always our highest priority.",
            "\u{2022} A booking fee of 400 SEK is paid at booking and is non-refundable.",
        ) );
    }

    public static function generate_registration_slug($animal_type_slug) {
        global $wpdb;
        $table = $wpdb->prefix . 'rabbit_registrations';

        $counter = 1;
        do {
            $slug   = $animal_type_slug . $counter;
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE registration_slug = %s", $slug));
            $counter++;
        } while ($exists);

        return $slug;
    }

    public static function generate_verification_token() {
        return bin2hex(random_bytes(24)); // 48-char hex string
    }

    public static function generate_edit_token() {
        // 8-char uppercase alphanumeric, easy to read/type
        $chars  = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0/O/1/I
        $result = '';
        for ($i = 0; $i < 8; $i++) {
            $result .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $result;
    }
}
