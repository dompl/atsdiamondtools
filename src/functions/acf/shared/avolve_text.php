<?php

/**
 * Parses a string to replace inline style definitions with styled HTML spans.
 *
 * This function looks for patterns like "text|token1,token2" within a string.
 * It supports multiple tokens in any order, such as "strong", "bold", "italic",
 * and color names. It wraps the text in a <span> with corresponding
 * Tailwind CSS classes and preserves line breaks.
 *
 * Example Usage:
 * $input = "Brooke Ponce|orange,strong\nAnother Line|italic";
 * echo avolve_text($input);
 * // Output:
 * // <span class="text-orange-500 font-bold">Brooke Ponce</span><br />
 * // <span class="font-italic">Another Line</span>
 *
 * @param string|null $text The input string to parse. Can be null.
 * @return string The formatted HTML string.
 */
function avolve_text( ?string $text ): string {
    // Return an empty string if the input is null or empty to prevent errors.
    if ( empty( $text ) ) {
        return '';
    }

    // First, convert all newline characters to <br> tags to preserve them.
    $text_with_breaks = nl2br( $text, false );

    // Use a regular expression to find all occurrences of the "|tokens" pattern.
    $formatted_text = preg_replace_callback(
        '/([^|]+)\|([\w,-]+)/', // The Regex pattern:
        // ([^|]+)     - Group 1: Capture one or more characters that are NOT a pipe.
        // \|           - Match the literal pipe character.
        // ([\w,-]+)  - Group 2: Capture one or more word characters, commas, or hyphens (the tokens).
        function ( $matches ) {
            // The text part might contain <br> tags from nl2br, which should be preserved.
            // We trim whitespace but leave the <br> tags.
            $text_part = trim( $matches[1] );
            $token_str = strtolower( trim( $matches[2] ) );

            // Split the tokens string by the comma.
            $tokens = array_map( 'trim', explode( ',', $token_str ) );

            $css_classes = [];

            // Define recognized style keywords and their corresponding CSS classes.
            $style_keywords = [
                'strong' => 'font-bold',
                'bold'   => 'font-bold',
                'italic' => 'font-italic'
                // You can add more keywords here in the future, e.g., 'uppercase' => 'uppercase'
            ];

            // Process each token
            foreach ( $tokens as $token ) {
                if ( isset( $style_keywords[$token] ) ) {
                    // It's a recognized keyword (like 'strong' or 'italic').
                    $css_classes[] = $style_keywords[$token];
                } else {
                    // It's not a keyword, so assume it's a color.
                    // Sanitize the color name to ensure it's a valid CSS class part.
                    $safe_color_name = preg_replace( '/[^a-z0-9\-]/', '', $token );
                    if ( !empty( $safe_color_name ) ) {
                        // Create the CSS class. Using a -500 shade as a default for colors.
                        $css_classes[] = 'text-' . $safe_color_name;
                    }
                }
            }

            // Escape HTML from the text part, but allow <br> tags for line breaks.
            $safe_text = wp_kses( $text_part, ['br' => []] );

            // If any classes were generated, wrap the text in a span.
            if ( !empty( $css_classes ) ) {
                return sprintf(
                    '<span class="%s">%s</span>',
                    esc_attr( implode( ' ', array_unique( $css_classes ) ) ), // Use array_unique to prevent duplicate classes
                    $safe_text
                );
            }

            // If no valid tokens were found, return the original text part, safely escaped.
            return $safe_text;
        },
        $text_with_breaks
    );

    // The function preg_replace_callback returns NULL on error, so add a fallback.
    return $formatted_text ?? $text_with_breaks;
}