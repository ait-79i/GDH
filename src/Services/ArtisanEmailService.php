<?php
namespace GDH\Services;

class ArtisanEmailService
{
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Build email context for artisan notification
     */
    private function buildArtisanContext($postId, array $formData, $recipientName, $isHtml = false)
    {
        $first   = isset($formData['first_name']) ? $formData['first_name'] : get_post_meta($postId, '_gdh_first_name', true);
        $last    = isset($formData['last_name']) ? $formData['last_name'] : get_post_meta($postId, '_gdh_last_name', true);
        $email   = isset($formData['email']) ? $formData['email'] : get_post_meta($postId, '_gdh_email', true);
        $phone   = isset($formData['phone']) ? $formData['phone'] : get_post_meta($postId, '_gdh_phone', true);
        $address = isset($formData['address']) ? $formData['address'] : get_post_meta($postId, '_gdh_address', true);
        $postal  = isset($formData['postal_code']) ? $formData['postal_code'] : get_post_meta($postId, '_gdh_postal_code', true);
        $city    = isset($formData['city']) ? $formData['city'] : get_post_meta($postId, '_gdh_city', true);
        $slots   = get_post_meta($postId, '_gdh_slots', true);

        $aptDate        = '';
        $slotsFormatted = '';

        if (is_array($slots) && ! empty($slots)) {
            $firstSlot = reset($slots);
            if (isset($firstSlot['date'])) {
                $dateStr = $firstSlot['date'];
                $times   = isset($firstSlot['times']) && is_array($firstSlot['times']) ? $firstSlot['times'] : [];
                $aptDate = date_i18n('d/m/Y', strtotime($dateStr));
                if (! empty($times) && ! in_array('all-day', $times, true)) {
                    $aptDate .= ' ' . implode(', ', $times);
                } elseif (! empty($times) && in_array('all-day', $times, true)) {
                    $aptDate .= ' – Toute la journée';
                }
            }

            // Build full slots list
            $all = [];
            foreach ($slots as $slot) {
                if (empty($slot['date'])) {
                    continue;
                }
                $dateStr = $slot['date'];
                $times   = isset($slot['times']) && is_array($slot['times']) ? $slot['times'] : [];
                $label   = date_i18n('d/m/Y', strtotime($dateStr));
                if (! empty($times)) {
                    if (in_array('all-day', $times, true)) {
                        $label .= ' – Toute la journée';
                    } else {
                        $cleanTimes = array_map('sanitize_text_field', $times);
                        $label .= ' – ' . implode(', ', $cleanTimes);
                    }
                }
                $all[] = $label;
            }

            if (! empty($all)) {
                if ($isHtml) {
                    if (count($all) === 1) {
                        $slotsFormatted = '<div class="gdh-slot">' . esc_html($all[0]) . '</div>';
                    } else {
                        $items = '';
                        foreach ($all as $it) {
                            $items .= '<li>' . esc_html($it) . '</li>';
                        }
                        $slotsFormatted = '<ul class="gdh-slots">' . $items . '</ul>';
                    }
                } else {
                    if (count($all) === 1) {
                        $slotsFormatted = $all[0];
                    } else {
                        $slotsFormatted = '- ' . implode("\n- ", $all);
                    }
                }
            }
        }

        return [
            'nom_lead'         => trim($first . ' ' . $last),
            'date_rdv'         => $aptDate,
            'email_lead'       => (string) $email,
            'phone'            => (string) $phone,
            'address'          => (string) $address,
            'city'             => (string) $city,
            'postal_code'      => (string) $postal,
            'nom_destinataire' => $recipientName,
            'creneaux_rdv'     => $slotsFormatted,
        ];
    }

    /**
     * Replace placeholders in template
     */
    private function replacePlaceholders($text, array $context)
    {
        return preg_replace_callback('/\{\{([a-zA-Z0-9_]+)\}\}/', function ($m) use ($context) {
            $key = $m[1];
            return array_key_exists($key, $context) ? (string) $context[$key] : $m[0];
        }, (string) $text);
    }

    /**
     * Build HTML email body
     */
    private function buildHtmlBody($body, $style = '')
    {
        $styleTag = trim((string) $style) !== '' ? '<style>' . $style . '</style>' : '';
        return '<!DOCTYPE html><html><head><meta charset="UTF-8">' . $styleTag . '</head><body>' . $body . '</body></html>';
    }

    /**
     * Send artisan notification email
     *
     * @param int $postId The appointment post ID
     * @param array $formData The form data
     * @return bool True if email sent successfully, false otherwise
     */
    public function sendArtisanNotification($postId, array $formData)
    {
        // Get recipient from saved post meta
        $recipientEmail = get_post_meta($postId, '_gdh_destinataire_email', true);
        $recipientName = get_post_meta($postId, '_gdh_destinataire_name', true);
        
        if (empty($recipientEmail) || !is_email($recipientEmail)) {
            $this->logger->error('GDH Artisan Email: No valid recipient email in post meta, skipping artisan notification');
            return false;
        }
        
        if (empty($recipientName)) {
            $recipientName = get_bloginfo('name');
        }

        // Load artisan email template from options
        $subject = (string) get_option('gdh_email_subject', '');
        $body    = (string) get_option('gdh_email_body', '');

        if ($subject === '' || $body === '') {
            $this->logger->error('GDH Artisan Email: Subject or body template missing');
            return false;
        }

        // Build context
        $context = $this->buildArtisanContext($postId, $formData, $recipientName, true);

        // Replace placeholders
        $renderedSubject = $this->replacePlaceholders($subject, $context);
        $renderedBody    = $this->replacePlaceholders($body, $context);

        // Send email
        $headers = [];
        $sent    = false;

        $filter = function () {
            return 'text/html';
        };
        add_filter('wp_mail_content_type', $filter);
        $htmlBody = $this->buildHtmlBody($renderedBody, '');
        $sent     = wp_mail($recipientEmail, $renderedSubject, $htmlBody, $headers);
        remove_filter('wp_mail_content_type', $filter);

        if (! $sent) {
            $plain = wp_strip_all_tags($renderedBody);
            $sent  = wp_mail($recipientEmail, $renderedSubject, $plain, $headers);
        }

        if (! $sent) {
            $this->logger->error('GDH Artisan Email: Failed to send to ' . $recipientEmail);
            return false;
        }

        $this->logger->info('GDH Artisan Email: Successfully sent to ' . $recipientEmail);
        return true;
    }
}
