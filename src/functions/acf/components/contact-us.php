<?php
/**
 * Contact Us Component
 *
 * A stylish contact form component with company information display
 *
 * @package SkylineWP Dev Child
 */

use Extended\ACF\Fields\Email;
use Extended\ACF\Fields\Group;
use Extended\ACF\Fields\Layout;
use Extended\ACF\Fields\Tab;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\Textarea;
use Extended\ACF\Fields\TrueFalse;
use Extended\ACF\Fields\WysiwygEditor;

function contact_us_fields() {
    return [
        Tab::make('Content Settings', wp_unique_id())->placement('left'),

        Text::make('Section Title', 'section_title')
            ->helperText('Main heading for the contact section')
            ->defaultValue('Get In Touch'),

        Textarea::make('Section Description', 'section_description')
            ->helperText('Brief description displayed below the title')
            ->rows(3),

        Tab::make('Company Information', wp_unique_id())->placement('left'),

        Group::make('Company Details', 'company_details')
            ->fields([
                Text::make('Company Name', 'company_name')
                    ->defaultValue('ATS Diamond Tools'),

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

        Text::make('Form Heading', 'form_heading')
            ->helperText('Heading displayed above the form')
            ->defaultValue('Send us a message'),

        Email::make('Recipient Email', 'recipient_email')
            ->helperText('Email address where form submissions will be sent')
            ->required(),

        Text::make('Success Message', 'success_message')
            ->helperText('Message displayed after successful form submission')
            ->defaultValue('Thank you! We\'ll get back to you soon.'),

        TrueFalse::make('Enable Newsletter Checkbox', 'enable_newsletter')
            ->helperText('Show newsletter subscription option')
            ->defaultValue(1),

        TrueFalse::make('Enable reCAPTCHA', 'enable_recaptcha')
            ->helperText('Require reCAPTCHA verification (must be configured in settings)')
            ->defaultValue(1),

        WysiwygEditor::make('Privacy Text', 'privacy_text')
            ->helperText('Privacy notice displayed with the consent checkbox')
            ->mediaUpload(false)
            ->tabs('visual')
            ->toolbar('basic')
            ->defaultValue('This form collects your name, email and telephone number, so we can respond to your enquiry. Check out our <a href="#">Privacy Statement & Cookie Notice</a> to see how we protect and manage your data.'),
    ];
}

function component_contact_us_html(string $output, string $layout): string {
    if ($layout !== 'contact_us') {
        return $output;
    }

    // Get field values
    $section_title = get_sub_field('section_title') ?: 'Get In Touch';
    $section_description = get_sub_field('section_description');
    $company_details = get_sub_field('company_details') ?: [];
    $form_heading = get_sub_field('form_heading') ?: 'Send us a message';
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

    <div class="rfs-ref-contact-us-container py-12 lg:py-20 bg-gradient-to-br from-ats-footer to-white">
        <div class="container mx-auto px-4">
            <!-- Section Header -->
            <div class="rfs-ref-contact-header text-center mb-12 lg:mb-16">
                <h2 class="text-3xl lg:text-5xl font-bold text-ats-dark mb-4">
                    <?php echo esc_html($section_title); ?>
                </h2>
                <?php if (!empty($section_description)): ?>
                    <p class="text-lg text-neutral-700 max-w-2xl mx-auto">
                        <?php echo esc_html($section_description); ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Main Content Grid -->
            <div class="rfs-ref-contact-content-grid grid lg:grid-cols-2 gap-8 lg:gap-16 max-w-7xl mx-auto">

                <!-- Company Information Column -->
                <div class="rfs-ref-company-info-column space-y-8">
                    <div class="rfs-ref-company-info-card bg-white rounded-2xl shadow-xl p-8 lg:p-10 border border-ats-gray">
                        <?php if (!empty($company_details['company_name'])): ?>
                            <h3 class="text-2xl font-bold text-ats-dark mb-6 pb-4 border-b border-ats-gray">
                                <?php echo esc_html($company_details['company_name']); ?>
                            </h3>
                        <?php endif; ?>

                        <div class="rfs-ref-company-details space-y-6">
                            <?php if (!empty($company_details['opening_hours'])): ?>
                                <div class="rfs-ref-info-item">
                                    <div class="flex items-start gap-4">
                                        <div class="flex-shrink-0 w-10 h-10 bg-accent-yellow rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-ats-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-ats-dark mb-1">Opening Hours</p>
                                            <p class="text-neutral-700 whitespace-pre-line"><?php echo esc_html($company_details['opening_hours']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($company_details['telephone'])): ?>
                                <div class="rfs-ref-info-item">
                                    <div class="flex items-start gap-4">
                                        <div class="flex-shrink-0 w-10 h-10 bg-accent-green rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-ats-dark mb-1">Telephone</p>
                                            <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $company_details['telephone'])); ?>" class="text-accent-green hover:text-accent-green/80 font-medium transition-colors">
                                                <?php echo esc_html($company_details['telephone']); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($company_details['address'])): ?>
                                <div class="rfs-ref-info-item">
                                    <div class="flex items-start gap-4">
                                        <div class="flex-shrink-0 w-10 h-10 bg-primary-600 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-ats-dark mb-1">Postal Address</p>
                                            <address class="text-neutral-700 not-italic whitespace-pre-line">
                                                <?php echo esc_html($company_details['address']); ?>
                                            </address>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($company_details['vat_number']) || !empty($company_details['company_registration'])): ?>
                                <div class="rfs-ref-company-legal pt-6 mt-6 border-t border-ats-gray space-y-2">
                                    <?php if (!empty($company_details['vat_number'])): ?>
                                        <p class="text-sm text-neutral-700">
                                            <span class="font-semibold">VAT Number:</span> <?php echo esc_html($company_details['vat_number']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if (!empty($company_details['company_registration'])): ?>
                                        <p class="text-sm text-neutral-700">
                                            <span class="font-semibold">Company Registration:</span> <?php echo esc_html($company_details['company_registration']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Contact Form Column -->
                <div class="rfs-ref-contact-form-column">
                    <div class="rfs-ref-contact-form-card bg-white rounded-2xl shadow-xl p-8 lg:p-10 border border-ats-gray">
                        <?php if (!empty($form_heading)): ?>
                            <h3 class="text-2xl font-bold text-ats-dark mb-6 pb-4 border-b border-ats-gray">
                                <?php echo esc_html($form_heading); ?>
                            </h3>
                        <?php endif; ?>

                        <!-- Form Messages -->
                        <div class="rfs-ref-form-messages mb-6 hidden">
                            <div class="rfs-ref-form-success hidden bg-accent-green/10 border border-accent-green text-accent-green rounded-lg p-4">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <p class="font-medium"></p>
                                </div>
                            </div>
                            <div class="rfs-ref-form-error hidden bg-red-50 border border-red-300 text-red-700 rounded-lg p-4">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                    <p class="font-medium"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Form -->
                        <form id="<?php echo esc_attr($form_id); ?>" class="rfs-ref-contact-form space-y-6" data-recipient="<?php echo esc_attr($recipient_email); ?>" data-success-message="<?php echo esc_attr($success_message); ?>">

                            <!-- Name Field -->
                            <div class="rfs-ref-form-field">
                                <label for="<?php echo esc_attr($form_id); ?>-name" class="block text-sm font-semibold text-ats-dark mb-2">
                                    Your Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="<?php echo esc_attr($form_id); ?>-name"
                                    name="name"
                                    required
                                    class="rfs-ref-input-name w-full px-4 py-3 border border-ats-gray rounded-lg focus:ring-2 focus:ring-accent-yellow focus:border-transparent transition-all"
                                    placeholder="Enter your full name"
                                />
                            </div>

                            <!-- Email Field -->
                            <div class="rfs-ref-form-field">
                                <label for="<?php echo esc_attr($form_id); ?>-email" class="block text-sm font-semibold text-ats-dark mb-2">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="email"
                                    id="<?php echo esc_attr($form_id); ?>-email"
                                    name="email"
                                    required
                                    class="rfs-ref-input-email w-full px-4 py-3 border border-ats-gray rounded-lg focus:ring-2 focus:ring-accent-yellow focus:border-transparent transition-all"
                                    placeholder="your.email@example.com"
                                />
                            </div>

                            <!-- Telephone Field -->
                            <div class="rfs-ref-form-field">
                                <label for="<?php echo esc_attr($form_id); ?>-telephone" class="block text-sm font-semibold text-ats-dark mb-2">
                                    Telephone
                                </label>
                                <input
                                    type="tel"
                                    id="<?php echo esc_attr($form_id); ?>-telephone"
                                    name="telephone"
                                    class="rfs-ref-input-telephone w-full px-4 py-3 border border-ats-gray rounded-lg focus:ring-2 focus:ring-accent-yellow focus:border-transparent transition-all"
                                    placeholder="Your contact number"
                                />
                            </div>

                            <!-- Message Field -->
                            <div class="rfs-ref-form-field">
                                <label for="<?php echo esc_attr($form_id); ?>-message" class="block text-sm font-semibold text-ats-dark mb-2">
                                    Your Enquiry <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    id="<?php echo esc_attr($form_id); ?>-message"
                                    name="message"
                                    rows="6"
                                    required
                                    class="rfs-ref-input-message w-full px-4 py-3 border border-ats-gray rounded-lg focus:ring-2 focus:ring-accent-yellow focus:border-transparent transition-all resize-y"
                                    placeholder="Tell us about your enquiry..."
                                ></textarea>
                            </div>

                            <?php if ($enable_newsletter): ?>
                                <!-- Newsletter Checkbox -->
                                <div class="rfs-ref-form-field">
                                    <label class="flex items-start gap-3 cursor-pointer group">
                                        <input
                                            type="checkbox"
                                            id="<?php echo esc_attr($form_id); ?>-newsletter"
                                            name="newsletter"
                                            class="rfs-ref-checkbox-newsletter mt-1 w-5 h-5 text-accent-green border-ats-gray rounded focus:ring-2 focus:ring-accent-yellow cursor-pointer"
                                        />
                                        <span class="text-sm text-neutral-700 group-hover:text-ats-dark transition-colors">
                                            Subscribe to ATS Diamond Tools newsletter for news, product updates and special offers
                                        </span>
                                    </label>
                                </div>
                            <?php endif; ?>

                            <!-- Consent Checkbox -->
                            <div class="rfs-ref-form-field">
                                <label class="flex items-start gap-3 cursor-pointer group">
                                    <input
                                        type="checkbox"
                                        id="<?php echo esc_attr($form_id); ?>-consent"
                                        name="consent"
                                        required
                                        class="rfs-ref-checkbox-consent mt-1 w-5 h-5 text-accent-green border-ats-gray rounded focus:ring-2 focus:ring-accent-yellow cursor-pointer"
                                    />
                                    <span class="text-sm text-neutral-700 group-hover:text-ats-dark transition-colors">
                                        <span class="text-red-500">*</span> I consent to my submitted data being collected and stored by ATS Diamond Tools
                                    </span>
                                </label>
                                <?php if (!empty($privacy_text)): ?>
                                    <div class="mt-2 ml-8 text-xs text-neutral-700">
                                        <?php echo wp_kses_post($privacy_text); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($enable_recaptcha && !empty($recaptcha_site_key)): ?>
                                <!-- reCAPTCHA -->
                                <div class="rfs-ref-form-field">
                                    <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($recaptcha_site_key); ?>"></div>
                                </div>
                            <?php endif; ?>

                            <!-- Submit Button -->
                            <div class="rfs-ref-form-submit pt-4">
                                <button
                                    type="submit"
                                    class="rfs-ref-submit-button w-full bg-accent-yellow hover:bg-accent-yellow/90 text-ats-dark font-bold py-4 px-8 rounded-lg transition-all transform hover:scale-[1.02] focus:ring-4 focus:ring-accent-yellow/50 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                                >
                                    <span class="rfs-ref-button-text">Send Message</span>
                                    <span class="rfs-ref-button-loading hidden">
                                        <svg class="animate-spin inline-block w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Sending...
                                    </span>
                                </button>
                            </div>

                        </form>
                    </div>
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
    ->fields(contact_us_fields());
