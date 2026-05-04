<?php
/**
 * Email sending class for Rabbit Register plugin
 */

class Rabbit_Register_Email {

    /**
     * Send the email-verification email right after registration.
     */
    public static function send_verification($registration, $verification_token, $page_url) {
        $verify_url = add_query_arg('rabbit_verify', $verification_token, $page_url);
        $is_swedish = self::is_swedish();

        $to      = $registration['email'];
        $subject = $is_swedish
            ? 'Bekräfta din e-postadress – Gunnesbo 4H'
            : 'Confirm your email address – Gunnesbo 4H';

        if ($is_swedish) {
            $body = "Hej " . $registration['owner_name'] . ",\n\n"
                  . "Tack för att du registrerade " . $registration['animal_name'] . " på Gunnesbo 4H Kaninhotell!\n\n"
                  . "Klicka på länken nedan för att bekräfta din e-postadress:\n\n"
                  . $verify_url . "\n\n"
                  . "Länken gäller tills du har klickat på den.\n\n"
                  . "Hälsningar,\nGunnesbo 4H";
        } else {
            $body = "Hi " . $registration['owner_name'] . ",\n\n"
                  . "Thank you for registering " . $registration['animal_name'] . " with Gunnesbo 4H Rabbit Hotel!\n\n"
                  . "Please click the link below to confirm your email address:\n\n"
                  . $verify_url . "\n\n"
                  . "The link is valid until you have clicked it.\n\n"
                  . "Best regards,\nGunnesbo 4H";
        }

        $headers = array('Content-Type: text/plain; charset=UTF-8');
        return wp_mail($to, $subject, $body, $headers);
    }

    /**
     * Send the confirmation email after the email has been verified.
     * Contains the reference slug and the edit code.
     */
    public static function send_confirmation($registration, $page_url) {
        $edit_url   = add_query_arg(
            array(
                'registration_slug' => $registration->registration_slug,
                'edit_token'        => $registration->edit_token,
            ),
            $page_url
        );
        $is_swedish = self::is_swedish();

        $to      = $registration->email;
        $subject = $is_swedish
            ? 'Din registrering är bekräftad – Gunnesbo 4H'
            : 'Your registration is confirmed – Gunnesbo 4H';

        if ($is_swedish) {
            $body = "Hej " . $registration->owner_name . ",\n\n"
                  . "Din e-postadress är verifierad och din registrering är nu aktiv.\n\n"
                  . "--- Din registreringsinformation ---\n"
                  . "Djur: " . $registration->animal_name . "\n"
                  . "Referensnummer: " . $registration->registration_slug . "\n"
                  . "Din redigeringskod: " . $registration->edit_token . "\n\n"
                  . "Spara dessa uppgifter! Du behöver referensnumret och koden för att kunna uppdatera din registrering senare.\n\n"
                  . "Du kan också uppdatera din registrering direkt via länken nedan:\n\n"
                  . $edit_url . "\n\n"
                  . "Kontakta oss för att göra en bokning.\n\n"
                  . "Hälsningar,\nGunnesbo 4H";
        } else {
            $body = "Hi " . $registration->owner_name . ",\n\n"
                  . "Your email address has been verified and your registration is now active.\n\n"
                  . "--- Your registration details ---\n"
                  . "Animal: " . $registration->animal_name . "\n"
                  . "Reference number: " . $registration->registration_slug . "\n"
                  . "Your edit code: " . $registration->edit_token . "\n\n"
                  . "Please save these details! You will need the reference number and edit code to update your registration later.\n\n"
                  . "You can also update your registration directly via the link below:\n\n"
                  . $edit_url . "\n\n"
                  . "Please contact us to make a booking.\n\n"
                  . "Best regards,\nGunnesbo 4H";
        }

        $headers = array('Content-Type: text/plain; charset=UTF-8');
        return wp_mail($to, $subject, $body, $headers);
    }

    /**
     * Re-send the edit code to a verified registrant.
     */
    public static function resend_edit_code($registration, $page_url) {
        $edit_url   = add_query_arg(
            array(
                'registration_slug' => $registration->registration_slug,
                'edit_token'        => $registration->edit_token,
            ),
            $page_url
        );
        $is_swedish = self::is_swedish();

        $to      = $registration->email;
        $subject = $is_swedish
            ? 'Din redigeringskod – Gunnesbo 4H'
            : 'Your edit code – Gunnesbo 4H';

        if ($is_swedish) {
            $body = "Hej " . $registration->owner_name . ",\n\n"
                  . "Här är din redigeringskod för " . $registration->animal_name . ":\n\n"
                  . "Referensnummer: " . $registration->registration_slug . "\n"
                  . "Redigeringskod: " . $registration->edit_token . "\n\n"
                  . "Direktlänk för att redigera:\n" . $edit_url . "\n\n"
                  . "Hälsningar,\nGunnesbo 4H";
        } else {
            $body = "Hi " . $registration->owner_name . ",\n\n"
                  . "Here is your edit code for " . $registration->animal_name . ":\n\n"
                  . "Reference number: " . $registration->registration_slug . "\n"
                  . "Edit code: " . $registration->edit_token . "\n\n"
                  . "Direct link to edit:\n" . $edit_url . "\n\n"
                  . "Best regards,\nGunnesbo 4H";
        }

        $headers = array('Content-Type: text/plain; charset=UTF-8');
        return wp_mail($to, $subject, $body, $headers);
    }

    /**
     * Notify a registrant that the disclaimer / terms have been updated.
     * Always sent in Swedish (the organisation is Swedish and we don't store
     * a per-registration language preference).
     *
     * @param stdClass $registration  DB row with owner_name, email, animal_name,
     *                                registration_slug, edit_token.
     * @param string   $page_url      Base URL of the registration form page.
     * @return bool                   Return value of wp_mail().
     */
    public static function send_disclaimer_changed($registration, $page_url) {
        $edit_url = add_query_arg(
            array(
                'registration_slug' => $registration->registration_slug,
                'edit_token'        => $registration->edit_token,
            ),
            $page_url
        );

        $to      = $registration->email;
        $subject = 'Uppdaterade villkor – Gunnesbo 4H';

        $body = "Hej " . $registration->owner_name . ",\n\n"
              . "Vi har uppdaterat villkoren för Gunnesbo 4H Passehotell.\n\n"
              . "Nästa gång du öppnar din registrering för " . $registration->animal_name
              . " kommer du att uppmanas att läsa igenom och godkänna de nya villkoren.\n\n"
              . "Öppna din registrering här:\n"
              . $edit_url . "\n\n"
              . "Hälsningar,\nGunnesbo 4H";

        $headers = array('Content-Type: text/plain; charset=UTF-8');
        return wp_mail($to, $subject, $body, $headers);
    }

    private static function is_swedish() {
        $lang = isset($_GET['lang']) ? $_GET['lang'] : '';
        return (!$lang || $lang === 'sv');
    }
}
