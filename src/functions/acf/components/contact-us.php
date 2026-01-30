<?php
/**
 * Contact Us Component - Simple Design
 *
 * A clean, minimal contact form component
 *
 * @package SkylineWP Dev Child
 */

use Extended\ACF\Fields\Email;
use Extended\ACF\Fields\Group;
use Extended\ACF\Fields\Image;
use Extended\ACF\Fields\Layout;
use Extended\ACF\Fields\Tab;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\Textarea;
use Extended\ACF\Fields\TrueFalse;
use Extended\ACF\Fields\WYSIWYGEditor;

function contact_us_fields() {
    return [
        Tab::make('Banner Settings', wp_unique_id())->placement('left'),

        Image::make('Banner Image', 'banner_image')
            ->helperText('Banner background image (recommended: 1920x400px or larger)')
            ->returnFormat('id'),

        Text::make('Banner Title', 'banner_title')
            ->helperText('Main heading displayed on the banner')
            ->default('Contact Us'),

        Textarea::make('Banner Description', 'banner_description')
            ->helperText('Description text displayed below the banner title')
            ->rows(2),

        Tab::make('Content Settings', wp_unique_id())->placement('left'),

        Text::make('Section Title', 'section_title')
            ->helperText('Main heading for the contact section')
            ->default('Contact Us'),

        Tab::make('Company Information', wp_unique_id())->placement('left'),

        Group::make('Company Details', 'company_details')
            ->fields([
                Textarea::make('Opening Hours', 'opening_hours')
                    ->helperText('e.g., Monday to Friday 9am to 4.30pm')
                    ->rows(2),

                Text::make('Telephone', 'telephone')
                    ->helperText('Main contact number'),

                Textarea::make('Address', 'address')
                    ->helperText('Full postal address (use line breaks for formatting)')
                    ->rows(6),

                Text::make('VAT Number', 'vat_number'),

                Text::make('Company Registration', 'company_registration'),
            ])
            ->layout('block'),

        Tab::make('Form Settings', wp_unique_id())->placement('left'),

        Email::make('Recipient Email', 'recipient_email')
            ->helperText('Email address where form submissions will be sent')
            ->required(),

        Text::make('Success Message', 'success_message')
            ->helperText('Message displayed after successful form submission')
            ->default('Thank you! We\'ll get back to you soon.'),

        TrueFalse::make('Enable Newsletter Checkbox', 'enable_newsletter')
            ->helperText('Show newsletter subscription option')
            ->default(1),

        TrueFalse::make('Enable reCAPTCHA', 'enable_recaptcha')
            ->helperText('Require reCAPTCHA verification (must be configured in settings)')
            ->default(1),

        WYSIWYGEditor::make('Privacy Text', 'privacy_text')
            ->helperText('Privacy notice displayed with the consent checkbox')
            ->disableMediaUpload()
            ->tabs('visual')
            ->toolbar('basic')
            ->default('This form collects your name, email and telephone number, so we can respond to your enquiry. Check out our <a href="#">Privacy Statement & Cookie Notice</a> to see how we protect and manage your data.'),
    ];
}

function component_contact_us_html(string $output, string $layout): string {
    if ($layout !== 'contact_us') {
        return $output;
    }

    // Get field values
    $section_title = get_sub_field('section_title') ?: 'Contact Us';
    $company_details = get_sub_field('company_details') ?: [];
    $recipient_email = get_sub_field('recipient_email');
    $success_message = get_sub_field('success_message');
    $enable_newsletter = get_sub_field('enable_newsletter');
    $enable_recaptcha = get_sub_field('enable_recaptcha');
    $privacy_text = get_sub_field('privacy_text');

    // Generate unique ID for this form instance
    $form_id = 'contact-form-' . uniqid();

    // Get reCAPTCHA site key if enabled
    $recaptcha_site_key = '';
    if ($enable_recaptcha && defined('RECAPTCHA_SITE_KEY')) {
        $recaptcha_site_key = RECAPTCHA_SITE_KEY;
    }

    ob_start();
    ?>

    <div class="rfs-ref-contact-us-simple py-8 lg:py-12 bg-white">
        <div class="container mx-auto px-4">

            <!-- Page Title -->
            <h1 class="text-2xl font-bold text-ats-dark mb-8 pb-4 border-b border-gray-200">
                <?php echo esc_html($section_title); ?>
            </h1>

            <div class="grid lg:grid-cols-3 gap-12">

                <!-- Left Column: Company Info (1/3 width) -->
                <div class="rfs-ref-company-info lg:col-span-1">

                    <?php if (!empty($company_details['opening_hours'])): ?>
                        <div class="rfs-ref-info-block mb-8">
                            <h3 class="text-base font-bold text-ats-dark mb-3">Opening Hours</h3>
                            <div class="text-sm text-gray-600 whitespace-pre-line leading-relaxed">
                                <?php echo esc_html($company_details['opening_hours']); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($company_details['telephone'])): ?>
                        <div class="rfs-ref-info-block mb-8">
                            <h3 class="text-base font-bold text-ats-dark mb-3">Telephone</h3>
                            <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $company_details['telephone'])); ?>"
                               class="text-sm text-gray-600 hover:text-ats-dark transition-colors">
                                <?php echo esc_html($company_details['telephone']); ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($company_details['address'])): ?>
                        <div class="rfs-ref-info-block mb-8">
                            <h3 class="text-base font-bold text-ats-dark mb-3">Address</h3>
                            <address class="text-sm text-gray-600 not-italic whitespace-pre-line leading-relaxed">
                                <?php echo esc_html($company_details['address']); ?>
                            </address>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($company_details['vat_number']) || !empty($company_details['company_registration'])): ?>
                        <div class="rfs-ref-company-legal pt-6 border-t border-gray-200 space-y-2">
                            <?php if (!empty($company_details['vat_number'])): ?>
                                <p class="text-xs text-gray-500">
                                    VAT: <?php echo esc_html($company_details['vat_number']); ?>
                                </p>
                            <?php endif; ?>
                            <?php if (!empty($company_details['company_registration'])): ?>
                                <p class="text-xs text-gray-500">
                                    Company No: <?php echo esc_html($company_details['company_registration']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column: Contact Form (2/3 width) -->
                <div class="rfs-ref-contact-form-wrapper lg:col-span-2">

                    <!-- Form Messages -->
                    <div class="rfs-ref-form-messages mb-6 hidden">
                        <div class="rfs-ref-form-success hidden bg-green-50 border border-green-200 text-green-800 p-4 text-sm">
                            <p class="font-medium"></p>
                        </div>
                        <div class="rfs-ref-form-error hidden bg-red-50 border border-red-200 text-red-800 p-4 text-sm">
                            <p class="font-medium"></p>
                        </div>
                    </div>

                    <!-- Contact Form -->
                    <form id="<?php echo esc_attr($form_id); ?>"
                          class="rfs-ref-contact-form"
                          data-recipient="<?php echo esc_attr($recipient_email); ?>"
                          data-success-message="<?php echo esc_attr($success_message); ?>">

                        <div class="grid md:grid-cols-2 gap-6 mb-6">
                            <!-- Name Field -->
                            <div class="rfs-ref-form-field">
                                <label for="<?php echo esc_attr($form_id); ?>-name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Your Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="<?php echo esc_attr($form_id); ?>-name"
                                    name="name"
                                    required
                                    class="rfs-ref-input-name w-full px-4 py-2.5 border border-gray-300 focus:border-ats-dark focus:ring-1 focus:ring-ats-dark outline-none transition-colors"
                                />
                            </div>

                            <!-- Email Field -->
                            <div class="rfs-ref-form-field">
                                <label for="<?php echo esc_attr($form_id); ?>-email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="email"
                                    id="<?php echo esc_attr($form_id); ?>-email"
                                    name="email"
                                    required
                                    class="rfs-ref-input-email w-full px-4 py-2.5 border border-gray-300 focus:border-ats-dark focus:ring-1 focus:ring-ats-dark outline-none transition-colors"
                                />
                            </div>
                        </div>

                        <!-- Telephone Field -->
                        <div class="rfs-ref-form-field mb-6">
                            <label for="<?php echo esc_attr($form_id); ?>-telephone" class="block text-sm font-medium text-gray-700 mb-2">
                                Telephone
                            </label>
                            <input
                                type="tel"
                                id="<?php echo esc_attr($form_id); ?>-telephone"
                                name="telephone"
                                class="rfs-ref-input-telephone w-full px-4 py-2.5 border border-gray-300 focus:border-ats-dark focus:ring-1 focus:ring-ats-dark outline-none transition-colors"
                            />
                        </div>

                        <!-- Message Field -->
                        <div class="rfs-ref-form-field mb-6">
                            <label for="<?php echo esc_attr($form_id); ?>-message" class="block text-sm font-medium text-gray-700 mb-2">
                                Message <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                id="<?php echo esc_attr($form_id); ?>-message"
                                name="message"
                                rows="6"
                                required
                                class="rfs-ref-input-message w-full px-4 py-2.5 border border-gray-300 focus:border-ats-dark focus:ring-1 focus:ring-ats-dark outline-none transition-colors resize-y"
                            ></textarea>
                        </div>

                        <?php if ($enable_newsletter): ?>
                            <!-- Newsletter Checkbox -->
                            <div class="rfs-ref-form-field mb-4">
                                <label class="flex items-start gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        id="<?php echo esc_attr($form_id); ?>-newsletter"
                                        name="newsletter"
                                        class="rfs-ref-checkbox-newsletter mt-0.5 w-4 h-4 text-ats-dark border-gray-300 focus:ring-ats-dark cursor-pointer"
                                    />
                                    <span class="text-sm text-gray-600">
                                        Subscribe to our newsletter
                                    </span>
                                </label>
                            </div>
                        <?php endif; ?>

                        <!-- Consent Checkbox -->
                        <div class="rfs-ref-form-field mb-6">
                            <label class="flex items-start gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    id="<?php echo esc_attr($form_id); ?>-consent"
                                    name="consent"
                                    required
                                    class="rfs-ref-checkbox-consent mt-0.5 w-4 h-4 text-ats-dark border-gray-300 focus:ring-ats-dark cursor-pointer"
                                />
                                <span class="text-sm text-gray-600">
                                    <span class="text-red-500">*</span> I consent to my submitted data being collected and stored
                                </span>
                            </label>
                            <?php if (!empty($privacy_text)): ?>
                                <div class="mt-2 ml-6 text-xs text-gray-500">
                                    <?php echo wp_kses_post($privacy_text); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($enable_recaptcha && !empty($recaptcha_site_key)): ?>
                            <!-- reCAPTCHA -->
                            <div class="rfs-ref-form-field mb-6">
                                <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($recaptcha_site_key); ?>"></div>
                            </div>
                        <?php endif; ?>

                        <!-- Submit Button -->
                        <div class="rfs-ref-form-submit">
                            <button
                                type="submit"
                                class="rfs-ref-submit-button bg-ats-dark hover:bg-gray-800 text-white font-medium py-3 px-8 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span class="rfs-ref-button-text">Send Message</span>
                                <span class="rfs-ref-button-loading hidden">
                                    Sending...
                                </span>
                            </button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>

    <?php if ($enable_recaptcha && !empty($recaptcha_site_key)): ?>
        <!-- Load reCAPTCHA script -->
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>

    <?php
    return ob_get_clean();
}
add_filter('skylinewp_flexible_content_output', 'component_contact_us_html', 10, 2);

// Define the custom layout for flexible content
return Layout::make('Contact Us', 'contact_us')
    ->layout('block')
    ->fields(contact_us_simple_fields());
