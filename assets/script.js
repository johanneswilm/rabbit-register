/**
 * Rabbit Register Plugin JavaScript
 */

jQuery(document).ready(function ($) {

    // ── Insurance show/hide ───────────────────────────────────────────────
    $('input[name="insured"]').on('change', function () {
        if ($(this).val() === '1') {
            $('#insurance-details').slideDown();
        } else {
            $('#insurance-details').slideUp();
        }
    });

    // ── Vaccination certificate show/hide based on animal type ────────────
    function updateVaccinationField() {
        var animalTypeId = String($('#animal_type_id').val());
        var required     = rabbit_register_ajax.vaccination_required &&
                           rabbit_register_ajax.vaccination_required[animalTypeId] === true;

        if (required) {
            $('#vaccination-certificate-field').show();
            $('#vaccination_certificate').prop('required', true);
        } else {
            $('#vaccination-certificate-field').hide();
            $('#vaccination_certificate').prop('required', false).val('');
        }
    }

    $('#animal_type_id').on('change', updateVaccinationField);

    // Run once on page load to respect any pre-selected value
    updateVaccinationField();

    // ── File upload validation ────────────────────────────────────────────
    $('#vaccination_certificate').on('change', function () {
        var file = this.files[0];
        if (!file) return;

        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5 MB.');
            $(this).val('');
            return;
        }
        var allowed = ['image/jpeg', 'image/png', 'application/pdf'];
        if ($.inArray(file.type, allowed) === -1) {
            alert('Only JPG, PNG, and PDF files are allowed.');
            $(this).val('');
        }
    });

    // ── Lookup token – force uppercase ────────────────────────────────────
    $('#lookup_token').on('input', function () {
        this.value = this.value.toUpperCase();
    });

    // ── New registration ──────────────────────────────────────────────────
    $('#rabbit-register-form').on('submit', function (e) {
        e.preventDefault();

        var $form      = $(this);
        var $btn       = $form.find('input[type="submit"]');
        var origText   = $btn.val();

        $btn.val('...').prop('disabled', true);

        var formData = new FormData(this);
        formData.append('action',   'rabbit_register_submit');
        formData.append('page_url', window.location.href);

        $.ajax({
            url:         rabbit_register_ajax.ajax_url,
            type:        'POST',
            data:        formData,
            contentType: false,
            processData: false,
            success: function (response) {
                if (response.success) {
                    var email = response.data.email;
                    var slug  = response.data.slug;

                    var isSv = document.documentElement.lang &&
                               document.documentElement.lang.indexOf('sv') === 0;

                    var msg = isSv
                        ? '<h3>Tack för din registrering!</h3>'
                          + '<p>Vi har skickat ett bekräftelsemail till <strong>' + email + '</strong>.</p>'
                          + '<p>Klicka på länken i mailet för att verifiera din e-postadress. '
                          + 'Efter verifieringen får du ett nytt mail med ditt referensnummer '
                          + '(<strong>' + slug + '</strong>) och din redigeringskod.</p>'
                          + '<p><em>Glöm inte att kolla skräpposten om mailet inte dyker upp inom några minuter.</em></p>'
                        : '<h3>Thank you for registering!</h3>'
                          + '<p>We have sent a confirmation email to <strong>' + email + '</strong>.</p>'
                          + '<p>Click the link in the email to verify your address. '
                          + 'After verification you will receive another email with your reference number '
                          + '(<strong>' + slug + '</strong>) and your edit code.</p>'
                          + '<p><em>Please check your spam folder if the email does not arrive within a few minutes.</em></p>';

                    $('#registration-result').html('<div class="rr-notice rr-success">' + msg + '</div>').fadeIn();
                    $form.slideUp();
                } else {
                    var err = (response.data && response.data.message) ? response.data.message : 'An error occurred.';
                    $('#registration-result').html('<div class="rr-notice rr-error"><p>' + err + '</p></div>').fadeIn();
                    $btn.val(origText).prop('disabled', false);
                }
            },
            error: function () {
                $('#registration-result').html('<div class="rr-notice rr-error"><p>An error occurred. Please try again.</p></div>').fadeIn();
                $btn.val(origText).prop('disabled', false);
            }
        });
    });

    // ── Edit / update registration ────────────────────────────────────────
    $('#edit-registration-form').on('submit', function (e) {
        e.preventDefault();

        var $form    = $(this);
        var $btn     = $form.find('input[type="submit"]');
        var origText = $btn.val();

        $btn.val('...').prop('disabled', true);

        var formData = new FormData(this);
        formData.append('action', 'rabbit_register_update');

        $.ajax({
            url:         rabbit_register_ajax.ajax_url,
            type:        'POST',
            data:        formData,
            contentType: false,
            processData: false,
            success: function (response) {
                if (response.success) {
                    $('#registration-result')
                        .html('<div class="rr-notice rr-success"><p>' + response.data.message + '</p></div>')
                        .fadeIn();
                    $btn.val(origText).prop('disabled', false);
                    window.scrollTo(0, 0);
                } else {
                    var err = (response.data && response.data.message) ? response.data.message : 'An error occurred.';
                    $('#registration-result').html('<div class="rr-notice rr-error"><p>' + err + '</p></div>').fadeIn();
                    $btn.val(origText).prop('disabled', false);
                }
            },
            error: function () {
                $('#registration-result').html('<div class="rr-notice rr-error"><p>An error occurred. Please try again.</p></div>').fadeIn();
                $btn.val(origText).prop('disabled', false);
            }
        });
    });

    // ── Resend edit-code toggle ───────────────────────────────────────────
    $('#rr-resend-toggle').on('click', function (e) {
        e.preventDefault();
        $('#rr-resend-form').slideToggle();
    });

    // ── Resend edit-code submit ───────────────────────────────────────────
    $('#rr-resend-form').on('submit', function (e) {
        e.preventDefault();

        var $form    = $(this);
        var $btn     = $('#rr-resend-submit');
        var origText = $btn.text();

        $btn.text('...').prop('disabled', true);

        var formData = new FormData(this);
        formData.append('action',   'rabbit_register_resend');
        formData.append('page_url', window.location.href);

        $.ajax({
            url:         rabbit_register_ajax.ajax_url,
            type:        'POST',
            data:        formData,
            contentType: false,
            processData: false,
            success: function (response) {
                var msg = (response.data && response.data.message) ? response.data.message : 'Done.';
                // Always show the success message (even on no-match, to prevent enumeration)
                $('#rr-resend-result')
                    .html('<div class="rr-notice rr-success"><p>' + msg + '</p></div>')
                    .fadeIn();
                $btn.text(origText).prop('disabled', false);
            },
            error: function () {
                $('#rr-resend-result').html('<div class="rr-notice rr-error"><p>An error occurred. Please try again.</p></div>').fadeIn();
                $btn.text(origText).prop('disabled', false);
            }
        });
    });

});
