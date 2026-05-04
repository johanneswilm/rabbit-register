# Rabbit Register - WordPress Plugin

A WordPress plugin for Gunnesbo 4H farm's rabbit hotel registration system. Allows pet owners to register their rabbits (and other small animals) for boarding when they go on holiday.

## Features

- **Multi-language Support**: Full Swedish and English translation support
- **Animal Type Management**: Admin can add/remove animal types (rabbits, guinea pigs, hamsters, etc.)
- **Registration System**: Owners can register their pets with all necessary information
- **Unique Slug Generation**: Each registration gets a unique reference (e.g., "rabbit15", "hamster3")
- **Edit Registration**: Owners can update their registration using their slug
- **File Upload**: Upload vaccination certificate photos (JPG, PNG, PDF)
- **Terms Acceptance**: Users must accept terms and conditions before registration
- **Admin Interface**: View and manage all registrations from WordPress admin
- **Email Notifications**: (Optional) Notify admin on new registrations

## Installation

1. Download the plugin files
2. Upload the `rabbit-register` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Use the shortcode `[rabbit_register_form]` on any page to display the registration form
5. Access admin interface via "Rabbit Register" menu in WordPress admin

## Usage

### Display Registration Form
Add this shortcode to any page or post:
```
[rabbit_register_form]
```

For English version:
```
[rabbit_register_form lang="en"]
```

### Edit Existing Registration
Users can edit their registration by visiting:
```
https://yoursite.com/registration-page/?registration_slug=rabbit15
```

### Admin Pages
- **Registrations**: View all registrations with search/filter
- **Animal Types**: Add, enable/disable, or remove animal types

## Database Structure

### Animal Types Table (`wp_rabbit_animal_types`)
- id
- name_sv (Swedish name)
- name_en (English name)
- slug
- enabled
- created_at

### Registrations Table (`wp_rabbit_registrations`)
- id
- registration_slug (unique reference)
- animal_type_id
- animal_name
- owner_name
- phone
- alternate_phone
- emergency_contact_name
- emergency_contact_phone
- dropoff_date
- pickup_date
- own_water_bottle
- own_bowls
- own_house
- own_hay
- own_pellets
- pellets_per_day
- insured
- insurance_company
- insurance_number
- vaccination_certificate (file path)
- terms_accepted
- status
- created_at
- updated_at

## File Uploads

Uploaded vaccination certificates are stored in:
`/wp-content/uploads/rabbit-registrations/`

This directory is protected with `.htaccess` to prevent direct access.

## Customization

### Adding New Animal Types
1. Go to WordPress Admin → Rabbit Register → Animal Types
2. Fill in Swedish and English names
3. Provide a unique slug (e.g., "hamster")
4. Click "Add Animal Type"

### Styling
Edit `assets/style.css` to customize the form appearance.

### Translation
The plugin supports Swedish and English. To add more languages:
1. Use the POT file in `languages/rabbit-register.pot`
2. Create PO/MO files for your language
3. Place them in the `languages/` folder

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- MySQL 5.6 or higher

## License

GPL v2 or later

## Support

For issues or feature requests, contact Gunnesbo 4H directly.
