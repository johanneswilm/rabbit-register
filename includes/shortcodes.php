<?php
/**
 * Shortcodes for Rabbit Register plugin
 */

/**
 * [rabbit_register_form] shortcode
 *
 * Handles four states via URL parameters:
 *   1. ?rabbit_verify=TOKEN          → verify email, show confirmation
 *   2. ?registration_slug=X&edit_token=Y  → authenticated edit form
 *   3. (default)                     → new registration form + lookup panel
 */
add_shortcode('rabbit_register_form', 'rabbit_register_form_shortcode');
function rabbit_register_form_shortcode($atts) {
    $atts = shortcode_atts(array('lang' => ''), $atts);
    $lang = $atts['lang'] ? $atts['lang'] : (isset($_GET['lang']) ? sanitize_text_field($_GET['lang']) : '');

    ob_start();

    if (isset($_GET['rabbit_verify'])) {
        rabbit_register_handle_verification($_GET['rabbit_verify'], $lang);
    } elseif (isset($_GET['registration_slug']) && isset($_GET['edit_token'])) {
        rabbit_register_render_edit_form(
            sanitize_text_field($_GET['registration_slug']),
            sanitize_text_field($_GET['edit_token']),
            $lang
        );
    } else {
        rabbit_register_render_new_form($lang);
    }

    return ob_get_clean();
}

// ---------------------------------------------------------------------------
// STATE 1 – email verification
// ---------------------------------------------------------------------------

function rabbit_register_handle_verification($token, $lang) {
    $is_swedish = (!$lang || $lang === 'sv');
    $registration = Rabbit_Register_Registration::verify_email(sanitize_text_field($token));

    if (!$registration) {
        echo '<div class="rabbit-register-container">';
        echo '<div class="rr-notice rr-error">';
        echo $is_swedish
            ? '<p>Länken är ogiltig eller har redan använts.</p>'
            : '<p>The link is invalid or has already been used.</p>';
        echo '</div></div>';
        return;
    }

    // Send confirmation email with slug + edit code
    $page_url = get_permalink();
    Rabbit_Register_Email::send_confirmation($registration, $page_url);

    echo '<div class="rabbit-register-container">';
    echo '<div class="rr-notice rr-success">';
    if ($is_swedish) {
        echo '<h2>E-postadressen är verifierad!</h2>';
        echo '<p>Din registrering för <strong>' . esc_html($registration->animal_name) . '</strong> är nu aktiv.</p>';
        echo '<p>Vi har skickat ett e-postmeddelande till <strong>' . esc_html($registration->email) . '</strong> med ditt referensnummer och din redigeringskod. Spara det e-postmeddelandet!</p>';
        echo '<p>Kontakta oss för att göra en bokning.</p>';
    } else {
        echo '<h2>Email address verified!</h2>';
        echo '<p>Your registration for <strong>' . esc_html($registration->animal_name) . '</strong> is now active.</p>';
        echo '<p>We have sent an email to <strong>' . esc_html($registration->email) . '</strong> with your reference number and edit code. Please save that email!</p>';
        echo '<p>Contact us to make a booking.</p>';
    }
    echo '</div></div>';
}

// ---------------------------------------------------------------------------
// STATE 2 – authenticated edit form
// ---------------------------------------------------------------------------

function rabbit_register_render_edit_form($registration_slug, $edit_token, $lang) {
    $is_swedish   = (!$lang || $lang === 'sv');
    $registration = Rabbit_Register_Registration::get_by_slug_and_edit_token($registration_slug, $edit_token);

    if (!$registration) {
        echo '<div class="rabbit-register-container">';
        echo '<div class="rr-notice rr-error">';
        echo $is_swedish
            ? '<p>Registreringen hittades inte eller koden stämmer inte.</p>'
            : '<p>Registration not found or the code is incorrect.</p>';
        echo '</div></div>';
        return;
    }

    $animal_type = Rabbit_Register_Animal_Types::get_animal_type($registration->animal_type_id);
    ?>
    <div class="rabbit-register-container">
        <h2><?php echo $is_swedish ? 'Uppdatera registrering' : 'Update Registration'; ?> &ndash; <span class="rr-slug"><?php echo esc_html($registration_slug); ?></span></h2>

        <form id="edit-registration-form" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('rabbit_register_nonce', 'rabbit_register_nonce'); ?>
            <input type="hidden" name="registration_slug" value="<?php echo esc_attr($registration_slug); ?>">
            <input type="hidden" name="edit_token"        value="<?php echo esc_attr($edit_token); ?>">

            <h3><?php echo $is_swedish ? 'Djurtyp' : 'Animal Type'; ?></h3>
            <p><strong><?php echo $is_swedish ? esc_html($animal_type->name_sv) : esc_html($animal_type->name_en); ?></strong></p>

            <h3><?php echo $is_swedish ? 'Djurets namn' : 'Animal Name'; ?></h3>
            <p>
                <label for="animal_name"><?php echo $is_swedish ? 'Djurets namn:' : 'Animal name:'; ?></label>
                <input type="text" name="animal_name" id="animal_name" value="<?php echo esc_attr($registration->animal_name); ?>" required>
            </p>

            <h3><?php echo $is_swedish ? 'Ägare' : 'Owner'; ?></h3>
            <p>
                <label for="owner_name"><?php echo $is_swedish ? 'Ägare:' : 'Owner:'; ?></label>
                <input type="text" name="owner_name" id="owner_name" value="<?php echo esc_attr($registration->owner_name); ?>" required>
            </p>
            <p>
                <label for="email"><?php echo $is_swedish ? 'E-postadress:' : 'Email address:'; ?></label>
                <input type="email" name="email" id="email" value="<?php echo esc_attr($registration->email); ?>" required>
            </p>
            <p>
                <label for="phone"><?php echo $is_swedish ? 'Telefonnummer:' : 'Phone number:'; ?></label>
                <input type="tel" name="phone" id="phone" value="<?php echo esc_attr($registration->phone); ?>" required>
            </p>
            <p>
                <label for="alternate_phone"><?php echo $is_swedish ? 'Annat nummer:' : 'Alternate number:'; ?></label>
                <input type="tel" name="alternate_phone" id="alternate_phone" value="<?php echo esc_attr($registration->alternate_phone); ?>">
            </p>

            <h3><?php echo $is_swedish ? 'Nödkontaktperson' : 'Emergency Contact'; ?></h3>
            <p>
                <label for="emergency_contact_name"><?php echo $is_swedish ? 'Namn:' : 'Name:'; ?></label>
                <input type="text" name="emergency_contact_name" id="emergency_contact_name" value="<?php echo esc_attr($registration->emergency_contact_name); ?>" required>
            </p>
            <p>
                <label for="emergency_contact_phone"><?php echo $is_swedish ? 'Telefonnummer:' : 'Phone number:'; ?></label>
                <input type="tel" name="emergency_contact_phone" id="emergency_contact_phone" value="<?php echo esc_attr($registration->emergency_contact_phone); ?>" required>
            </p>

            <h3><?php echo $is_swedish ? 'Medtagna tillbehör' : 'Brought Accessories'; ?></h3>
            <p><label><input type="checkbox" name="own_water_bottle" value="1" <?php checked($registration->own_water_bottle, 1); ?>> <?php echo $is_swedish ? 'Egen vattenflaska' : 'Own water bottle'; ?></label></p>
            <p><label><input type="checkbox" name="own_bowls"        value="1" <?php checked($registration->own_bowls,        1); ?>> <?php echo $is_swedish ? 'Egen/egna skålar'  : 'Own bowls';        ?></label></p>
            <p><label><input type="checkbox" name="own_house"        value="1" <?php checked($registration->own_house,        1); ?>> <?php echo $is_swedish ? 'Eget hus'           : 'Own house';        ?></label></p>
            <p><label><input type="checkbox" name="own_hay"          value="1" <?php checked($registration->own_hay,          1); ?>> <?php echo $is_swedish ? 'Eget hö'            : 'Own hay';          ?></label></p>
            <p><label><input type="checkbox" name="own_pellets"      value="1" <?php checked($registration->own_pellets,      1); ?>> <?php echo $is_swedish ? 'Egen pellets'       : 'Own pellets';      ?></label></p>
            <p>
                <label for="pellets_per_day"><?php echo $is_swedish ? 'Hur mycket pellets per dag:' : 'How much pellets per day:'; ?></label>
                <input type="text" name="pellets_per_day" id="pellets_per_day" value="<?php echo esc_attr($registration->pellets_per_day); ?>">
            </p>

            <h3><?php echo $is_swedish ? 'Försäkring' : 'Insurance'; ?></h3>
            <p>
                <span><?php echo $is_swedish ? 'Är djuret försäkrat?' : 'Is the animal insured?'; ?></span>
                <label><input type="radio" name="insured" value="1" <?php checked($registration->insured, 1); ?>> <?php echo $is_swedish ? 'JA' : 'YES'; ?></label>
                <label><input type="radio" name="insured" value="0" <?php checked($registration->insured, 0); ?>> <?php echo $is_swedish ? 'NEJ' : 'NO'; ?></label>
            </p>
            <div id="insurance-details" style="<?php echo $registration->insured ? 'display:block;' : 'display:none;'; ?>">
                <p>
                    <label for="insurance_company"><?php echo $is_swedish ? 'Försäkringsbolag:' : 'Insurance company:'; ?></label>
                    <input type="text" name="insurance_company" id="insurance_company" value="<?php echo esc_attr($registration->insurance_company); ?>">
                </p>
                <p>
                    <label for="insurance_number"><?php echo $is_swedish ? 'Försäkringsnummer:' : 'Insurance number:'; ?></label>
                    <input type="text" name="insurance_number" id="insurance_number" value="<?php echo esc_attr($registration->insurance_number); ?>">
                </p>
            </div>

            <?php if ( $animal_type->requires_vaccination_certificate ): ?>
            <h3><?php echo $is_swedish ? 'Vaccinationsintyg' : 'Vaccination Certificate'; ?></h3>
            <?php if ($registration->vaccination_certificate): ?>
            <p>
                <?php echo $is_swedish ? 'Nuvarande intyg:' : 'Current certificate:'; ?>
                <a href="<?php echo esc_url($registration->vaccination_certificate); ?>" target="_blank"><?php echo $is_swedish ? 'Visa' : 'View'; ?></a>
            </p>
            <?php endif; ?>
            <p>
                <label for="vaccination_certificate"><?php echo $is_swedish ? 'Ladda upp nytt intyg (valfritt):' : 'Upload new certificate (optional):'; ?></label>
                <input type="file" name="vaccination_certificate" id="vaccination_certificate" accept=".jpg,.jpeg,.png,.pdf">
            </p>
            <?php endif; ?>

            <?php
            $current_disclaimer_version = get_option( 'rabbit_register_disclaimer_version', '' );
            $needs_reaccept = ( $registration->accepted_disclaimer_version !== $current_disclaimer_version );

            if ( $needs_reaccept ):
                $disclaimer_text = $is_swedish
                    ? get_option( 'rabbit_register_disclaimer_sv', '' )
                    : get_option( 'rabbit_register_disclaimer_en', '' );
            ?>
            <div class="rr-notice rr-warning">
                <p><?php echo $is_swedish
                    ? '<strong>Villkoren har uppdaterats sedan du senast godkände dem.</strong> Läs igenom och godkänn de nya villkoren nedan för att kunna spara.'
                    : '<strong>The terms have been updated since you last accepted them.</strong> Please read and accept the new terms below to save your changes.'; ?>
                </p>
            </div>
            <h3><?php echo $is_swedish ? 'Uppdaterade villkor' : 'Updated Terms'; ?></h3>
            <div class="terms-box">
            <?php
            $lines = explode( "\n", $disclaimer_text );
            foreach ( $lines as $line ) {
                $line = trim( $line );
                if ( $line !== '' ) {
                    echo '<p>' . esc_html( $line ) . '</p>';
                }
            }
            ?>
            </div>
            <p>
                <label>
                    <input type="checkbox" name="terms_accepted" value="1" required>
                    <?php echo $is_swedish
                        ? 'Jag har läst och accepterar de uppdaterade villkoren'
                        : 'I have read and accept the updated terms'; ?>
                </label>
            </p>
            <input type="hidden" name="disclaimer_version" value="<?php echo esc_attr( $current_disclaimer_version ); ?>">
            <?php else: ?>
            <input type="hidden" name="terms_accepted" value="1">
            <input type="hidden" name="disclaimer_version" value="<?php echo esc_attr( $current_disclaimer_version ); ?>">
            <?php endif; ?>

            <p>
                <input type="submit" name="rabbit_register_update" value="<?php echo $is_swedish ? 'Spara ändringar' : 'Save Changes'; ?>" class="button button-primary">
                <a href="<?php echo esc_url(get_permalink()); ?>" class="button"><?php echo $is_swedish ? 'Avbryt' : 'Cancel'; ?></a>
            </p>
        </form>

        <div id="registration-result" style="display:none;"></div>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// STATE 3 – new registration form + lookup panel
// ---------------------------------------------------------------------------

function rabbit_register_render_new_form($lang) {
    $animal_types = Rabbit_Register_Animal_Types::get_all_animal_types(true);
    $is_swedish   = (!$lang || $lang === 'sv');
    ?>
    <div class="rabbit-register-container">

        <!-- ── New registration ────────────────────────────────────────── -->
        <h2><?php echo $is_swedish ? 'Passekanin på Gunnesbo 4H' : 'Rabbit Hotel at Gunnesbo 4H'; ?></h2>

        <form id="rabbit-register-form" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('rabbit_register_nonce', 'rabbit_register_nonce'); ?>

            <h3><?php echo $is_swedish ? 'Djurtyp' : 'Animal Type'; ?></h3>
            <p>
                <label for="animal_type_id"><?php echo $is_swedish ? 'Välj typ av djur:' : 'Select animal type:'; ?></label>
                <select name="animal_type_id" id="animal_type_id" required>
                    <option value=""><?php echo $is_swedish ? 'Välj...' : 'Select...'; ?></option>
                    <?php foreach ($animal_types as $type): ?>
                        <option value="<?php echo esc_attr($type->id); ?>">
                            <?php echo $is_swedish ? esc_html($type->name_sv) : esc_html($type->name_en); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <h3><?php echo $is_swedish ? 'Djurets namn' : 'Animal Name'; ?></h3>
            <p>
                <label for="animal_name"><?php echo $is_swedish ? 'Kaninens/marsvinens/hamsterns namn:' : 'Name of the rabbit/guinea pig/hamster:'; ?></label>
                <input type="text" name="animal_name" id="animal_name" required>
            </p>

            <h3><?php echo $is_swedish ? 'Ägare' : 'Owner'; ?></h3>
            <p>
                <label for="owner_name"><?php echo $is_swedish ? 'Ägare:' : 'Owner:'; ?></label>
                <input type="text" name="owner_name" id="owner_name" required>
            </p>
            <p>
                <label for="email"><?php echo $is_swedish ? 'E-postadress:' : 'Email address:'; ?></label>
                <input type="email" name="email" id="email" required>
                <span class="rr-field-hint"><?php echo $is_swedish ? 'En bekräftelse skickas hit.' : 'A confirmation will be sent here.'; ?></span>
            </p>
            <p>
                <label for="phone"><?php echo $is_swedish ? 'Telefonnummer:' : 'Phone number:'; ?></label>
                <input type="tel" name="phone" id="phone" required>
            </p>
            <p>
                <label for="alternate_phone"><?php echo $is_swedish ? 'Annat nummer om ägaren inte går att nå:' : 'Alternate number if owner cannot be reached:'; ?></label>
                <input type="tel" name="alternate_phone" id="alternate_phone">
            </p>

            <h3><?php echo $is_swedish ? 'Nödkontaktperson' : 'Emergency Contact'; ?></h3>
            <p><?php echo $is_swedish ? '(måste kunna hämta djuret vid behov)' : '(must be able to pick up the animal if needed)'; ?></p>
            <p>
                <label for="emergency_contact_name"><?php echo $is_swedish ? 'Namn:' : 'Name:'; ?></label>
                <input type="text" name="emergency_contact_name" id="emergency_contact_name" required>
            </p>
            <p>
                <label for="emergency_contact_phone"><?php echo $is_swedish ? 'Telefonnummer:' : 'Phone number:'; ?></label>
                <input type="tel" name="emergency_contact_phone" id="emergency_contact_phone" required>
            </p>

            <h3><?php echo $is_swedish ? 'Medtagna tillbehör' : 'Brought Accessories'; ?></h3>
            <p><?php echo $is_swedish ? 'Kryssa i det som gäller:' : 'Check what applies:'; ?></p>
            <p><label><input type="checkbox" name="own_water_bottle" value="1"> <?php echo $is_swedish ? 'Egen vattenflaska' : 'Own water bottle'; ?></label></p>
            <p><label><input type="checkbox" name="own_bowls"        value="1"> <?php echo $is_swedish ? 'Egen/egna skålar'  : 'Own bowls';        ?></label></p>
            <p><label><input type="checkbox" name="own_house"        value="1"> <?php echo $is_swedish ? 'Eget hus'           : 'Own house';        ?></label></p>
            <p><label><input type="checkbox" name="own_hay"          value="1"> <?php echo $is_swedish ? 'Eget hö'            : 'Own hay';          ?></label></p>
            <p><label><input type="checkbox" name="own_pellets"      value="1"> <?php echo $is_swedish ? 'Egen pellets'       : 'Own pellets';      ?></label></p>
            <p>
                <label for="pellets_per_day"><?php echo $is_swedish ? 'Hur mycket pellets per dag:' : 'How much pellets per day:'; ?></label>
                <input type="text" name="pellets_per_day" id="pellets_per_day" placeholder="<?php echo $is_swedish ? 't.ex. 2 msk' : 'e.g. 2 tbsp'; ?>">
            </p>

            <h3><?php echo $is_swedish ? 'Försäkring' : 'Insurance'; ?></h3>
            <p>
                <span><?php echo $is_swedish ? 'Är djuret försäkrat?' : 'Is the animal insured?'; ?></span>
                <label><input type="radio" name="insured" value="1"> <?php echo $is_swedish ? 'JA' : 'YES'; ?></label>
                <label><input type="radio" name="insured" value="0"> <?php echo $is_swedish ? 'NEJ' : 'NO'; ?></label>
            </p>
            <div id="insurance-details" style="display:none;">
                <p>
                    <label for="insurance_company"><?php echo $is_swedish ? 'Försäkringsbolag:' : 'Insurance company:'; ?></label>
                    <input type="text" name="insurance_company" id="insurance_company">
                </p>
                <p>
                    <label for="insurance_number"><?php echo $is_swedish ? 'Försäkringsnummer:' : 'Insurance number:'; ?></label>
                    <input type="text" name="insurance_number" id="insurance_number">
                </p>
            </div>

            <div id="vaccination-certificate-field">
                <h3><?php echo $is_swedish ? 'Vaccinationsintyg' : 'Vaccination Certificate'; ?></h3>
                <p><?php echo $is_swedish ? 'Ladda upp ett foto av relevant sida i vaccinationsintyget:' : 'Upload a photo of the relevant page of the vaccination certificate:'; ?></p>
                <p>
                    <input type="file" name="vaccination_certificate" id="vaccination_certificate" accept=".jpg,.jpeg,.png,.pdf">
                </p>
            </div>

            <h3><?php echo $is_swedish ? 'Viktig information' : 'Important Information'; ?></h3>
            <div class="terms-box">
            <?php
            $disclaimer_text = $is_swedish
                ? get_option( 'rabbit_register_disclaimer_sv', '' )
                : get_option( 'rabbit_register_disclaimer_en', '' );
            $lines = explode( "\n", $disclaimer_text );
            foreach ( $lines as $line ) {
                $line = trim( $line );
                if ( $line !== '' ) {
                    echo '<p>' . esc_html( $line ) . '</p>';
                }
            }
            ?>
            </div>
            <p>
                <label>
                    <input type="checkbox" name="terms_accepted" value="1" required>
                    <?php echo $is_swedish ? 'Jag har läst och accepterar villkoren ovan' : 'I have read and accept the terms above'; ?>
                </label>
            </p>

            <p>
                <input type="submit" name="rabbit_register_submit" value="<?php echo $is_swedish ? 'Skicka registrering' : 'Submit Registration'; ?>" class="button button-primary">
            </p>
        </form>

        <div id="registration-result" style="display:none;"></div>

        <!-- ── Lookup panel ─────────────────────────────────────────────── -->
        <div class="rr-lookup-panel">
            <h3><?php echo $is_swedish ? 'Har du redan registrerat ett djur?' : 'Already registered an animal?'; ?></h3>
            <p><?php echo $is_swedish
                ? 'Ange ditt referensnummer och din redigeringskod för att uppdatera din registrering.'
                : 'Enter your reference number and edit code to update your registration.'; ?></p>
            <form id="rr-lookup-form" method="get" action="">
                <?php
                // Preserve any existing non-rr query params (e.g. lang)
                foreach ($_GET as $k => $v) {
                    if (!in_array($k, array('registration_slug', 'edit_token'))) {
                        echo '<input type="hidden" name="' . esc_attr($k) . '" value="' . esc_attr($v) . '">';
                    }
                }
                ?>
                <p>
                    <label for="lookup_slug"><?php echo $is_swedish ? 'Referensnummer (t.ex. rabbit3):' : 'Reference number (e.g. rabbit3):'; ?></label>
                    <input type="text" name="registration_slug" id="lookup_slug" placeholder="rabbit3" required>
                </p>
                <p>
                    <label for="lookup_token"><?php echo $is_swedish ? 'Redigeringskod:' : 'Edit code:'; ?></label>
                    <input type="text" name="edit_token" id="lookup_token" placeholder="ABCD1234" required style="text-transform:uppercase;">
                </p>
                <p>
                    <button type="submit" class="button"><?php echo $is_swedish ? 'Öppna min registrering' : 'Open my registration'; ?></button>
                </p>
            </form>

            <hr>
            <p>
                <small><?php echo $is_swedish ? 'Tappat bort din redigeringskod? ' : 'Lost your edit code? '; ?></small>
                <a href="#" id="rr-resend-toggle"><?php echo $is_swedish ? 'Skicka koden igen' : 'Resend the code'; ?></a>
            </p>
            <form id="rr-resend-form" style="display:none;" method="post">
                <?php wp_nonce_field('rabbit_register_nonce', 'rabbit_register_nonce'); ?>
                <p>
                    <label for="resend_slug"><?php echo $is_swedish ? 'Referensnummer:' : 'Reference number:'; ?></label>
                    <input type="text" name="resend_slug" id="resend_slug" placeholder="rabbit3" required>
                </p>
                <p>
                    <label for="resend_email"><?php echo $is_swedish ? 'E-postadress:' : 'Email address:'; ?></label>
                    <input type="email" name="resend_email" id="resend_email" required>
                </p>
                <p>
                    <button type="submit" id="rr-resend-submit" class="button"><?php echo $is_swedish ? 'Skicka igen' : 'Send again'; ?></button>
                </p>
                <div id="rr-resend-result" style="display:none;"></div>
            </form>
        </div>

    </div><!-- .rabbit-register-container -->
    <?php
}

// ---------------------------------------------------------------------------
// AJAX – new registration
// ---------------------------------------------------------------------------

add_action('wp_ajax_rabbit_register_submit',        'rabbit_register_ajax_submit');
add_action('wp_ajax_nopriv_rabbit_register_submit', 'rabbit_register_ajax_submit');
function rabbit_register_ajax_submit() {
    if (!isset($_POST['rabbit_register_nonce']) || !wp_verify_nonce($_POST['rabbit_register_nonce'], 'rabbit_register_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed. Please refresh the page and try again.'));
        wp_die();
    }

    if (empty($_POST['email']) || !is_email($_POST['email'])) {
        wp_send_json_error(array('message' => 'Please enter a valid email address.'));
        wp_die();
    }

    $result = Rabbit_Register_Registration::create($_POST, $_FILES);

    if ($result) {
        // Send verification email
        $page_url        = isset($_POST['page_url']) ? esc_url_raw($_POST['page_url']) : home_url('/');
        $registration_data = array(
            'owner_name'  => sanitize_text_field($_POST['owner_name']),
            'animal_name' => sanitize_text_field($_POST['animal_name']),
            'email'       => sanitize_email($_POST['email']),
        );
        Rabbit_Register_Email::send_verification($registration_data, $result['verification_token'], $page_url);

        wp_send_json_success(array(
            'slug'    => $result['slug'],
            'email'   => sanitize_email($_POST['email']),
        ));
    } else {
        wp_send_json_error(array('message' => 'Registration failed. Please try again.'));
    }
    wp_die();
}

// ---------------------------------------------------------------------------
// AJAX – update existing registration
// ---------------------------------------------------------------------------

add_action('wp_ajax_rabbit_register_update',        'rabbit_register_ajax_update');
add_action('wp_ajax_nopriv_rabbit_register_update', 'rabbit_register_ajax_update');
function rabbit_register_ajax_update() {
    if (!isset($_POST['rabbit_register_nonce']) || !wp_verify_nonce($_POST['rabbit_register_nonce'], 'rabbit_register_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed. Please refresh the page and try again.'));
        wp_die();
    }

    if (empty($_POST['registration_slug']) || empty($_POST['edit_token'])) {
        wp_send_json_error(array('message' => 'Missing registration reference or edit code.'));
        wp_die();
    }

    $slug  = sanitize_text_field($_POST['registration_slug']);
    $token = sanitize_text_field($_POST['edit_token']);

    // Re-verify the edit token before saving
    $registration = Rabbit_Register_Registration::get_by_slug_and_edit_token($slug, $token);
    if (!$registration) {
        wp_send_json_error(array('message' => 'Invalid registration or edit code.'));
        wp_die();
    }

    $result = Rabbit_Register_Registration::update($slug, $_POST, $_FILES);

    if ($result !== false) {
        wp_send_json_success(array('message' => 'Registration updated successfully!'));
    } else {
        wp_send_json_error(array('message' => 'Update failed. Please try again.'));
    }
    wp_die();
}

// ---------------------------------------------------------------------------
// AJAX – resend edit code
// ---------------------------------------------------------------------------

add_action('wp_ajax_rabbit_register_resend',        'rabbit_register_ajax_resend');
add_action('wp_ajax_nopriv_rabbit_register_resend', 'rabbit_register_ajax_resend');
function rabbit_register_ajax_resend() {
    if (!isset($_POST['rabbit_register_nonce']) || !wp_verify_nonce($_POST['rabbit_register_nonce'], 'rabbit_register_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed. Please refresh and try again.'));
        wp_die();
    }

    $slug  = sanitize_text_field($_POST['resend_slug'] ?? '');
    $email = sanitize_email($_POST['resend_email'] ?? '');

    if (!$slug || !$email) {
        wp_send_json_error(array('message' => 'Please fill in all fields.'));
        wp_die();
    }

    $registration = Rabbit_Register_Registration::get_by_slug($slug);

    // Always respond the same way to prevent enumeration
    if ($registration && $registration->email_verified && strtolower($registration->email) === strtolower($email)) {
        $page_url = isset($_POST['page_url']) ? esc_url_raw($_POST['page_url']) : home_url('/');
        Rabbit_Register_Email::resend_edit_code($registration, $page_url);
    }

    wp_send_json_success(array('message' => 'If that reference number and email match our records, you will receive an email shortly.'));
    wp_die();
}
