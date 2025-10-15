<?php
namespace GDH\Services;

use GDH\PostTypes\EmailTemplatePostType;

class EmailTemplateService
{
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function getActiveTemplate()
    {
        $args = [
            'post_type'      => EmailTemplatePostType::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_key'       => '_gdh_email_is_active',
            'meta_value'     => '1',
        ];
        $q = new \WP_Query($args);
        if (! $q->have_posts()) {
            return null;
        }
        return $q->posts[0];
    }

    public function getAvailableVariables()
    {
        return [
            'client_name' => 'Nom complet du client',
            'appointment_date' => 'Date et heure du rendez-vous (priorité 1)',
            '.' => 'Nom du service',
            'admin_email' => 'Email de l\'administrateur',
            'appointment_id' => 'Identifiant du rendez-vous',
            'client_email' => 'Email du client',
            'phone' => 'Téléphone du client',
            'address' => 'Adresse',
            'city' => 'Ville',
            'postal_code' => 'Code postal',
            'site_name' => 'Nom du site',
        ];
    }

    private function buildContextFromData($post_id, array $formData)
    {
        $first = isset($formData['first_name']) ? $formData['first_name'] : get_post_meta($post_id, '_gdh_first_name', true);
        $last = isset($formData['last_name']) ? $formData['last_name'] : get_post_meta($post_id, '_gdh_last_name', true);
        $email = isset($formData['email']) ? $formData['email'] : get_post_meta($post_id, '_gdh_email', true);
        $phone = isset($formData['phone']) ? $formData['phone'] : get_post_meta($post_id, '_gdh_phone', true);
        $address = isset($formData['address']) ? $formData['address'] : get_post_meta($post_id, '_gdh_address', true);
        $postal = isset($formData['postal_code']) ? $formData['postal_code'] : get_post_meta($post_id, '_gdh_postal_code', true);
        $city = isset($formData['city']) ? $formData['city'] : get_post_meta($post_id, '_gdh_city', true);
        $service = isset($formData['service_name']) ? $formData['service_name'] : '';
        $slots = get_post_meta($post_id, '_gdh_slots', true);
        $aptDate = '';
        if (is_array($slots) && !empty($slots)) {
            $firstSlot = reset($slots);
            if (isset($firstSlot['date'])) {
                $dateStr = $firstSlot['date'];
                $times = isset($firstSlot['times']) && is_array($firstSlot['times']) ? $firstSlot['times'] : [];
                $aptDate = date_i18n('d/m/Y', strtotime($dateStr));
                if (!empty($times) && !in_array('all-day', $times, true)) {
                    $aptDate .= ' ' . implode(', ', $times);
                }
            }
        }
        $adminEmail = get_option('admin_email');
        return [
            'client_name' => trim($first . ' ' . $last),
            'appointment_date' => $aptDate,
            'service_name' => $service,
            'admin_email' => $adminEmail,
            'appointment_id' => (string)$post_id,
            'client_email' => (string)$email,
            'phone' => (string)$phone,
            'address' => (string)$address,
            'city' => (string)$city,
            'postal_code' => (string)$postal,
            'site_name' => get_bloginfo('name'),
        ];
    }

    private function findPlaceholders($text)
    {
        $matches = [];
        preg_match_all('/\[([a-zA-Z0-9_]+)\]/', (string)$text, $matches);
        return isset($matches[1]) ? array_unique($matches[1]) : [];
    }

    private function validatePlaceholders(array $placeholders, array $available, array $context)
    {
        $unknown = [];
        $missing = [];
        foreach ($placeholders as $key) {
            if (! array_key_exists($key, $available)) {
                $unknown[] = $key;
                continue;
            }
            if (! array_key_exists($key, $context) || $context[$key] === '' || $context[$key] === null) {
                $missing[] = $key;
            }
        }
        return [$unknown, $missing];
    }

    private function replacePlaceholders($text, array $context)
    {
        return preg_replace_callback('/\[([a-zA-Z0-9_]+)\]/', function ($m) use ($context) {
            $k = $m[1];
            return array_key_exists($k, $context) ? (string)$context[$k] : $m[0];
        }, (string)$text);
    }

    private function buildHtmlBody($body, $style)
    {
        $styleTag = trim((string)$style) !== '' ? '<style>' . $style . '</style>' : '';
        return '<!DOCTYPE html><html><head><meta charset="UTF-8">' . $styleTag . '</head><body>' . $body . '</body></html>';
    }

    public function sendOnAppointment($post_id, array $formData)
    {
        $template = $this->getActiveTemplate();
        if (! $template) {
            $this->logger->error('GDH Email: aucun template actif');
            update_option('gdh_email_last_error', 'Aucun template e-mail actif.');
            return false;
        }
        $subject = get_post_meta($template->ID, '_gdh_email_subject', true);
        $style = get_post_meta($template->ID, '_gdh_email_style', true);
        $contentType = get_post_meta($template->ID, '_gdh_email_content_type', true);
        $contentType = $contentType === 'html' ? 'html' : 'plain';
        $body = (string)$template->post_content;
        $available = $this->getAvailableVariables();
        $context = $this->buildContextFromData($post_id, $formData);
        $placeholders = array_unique(array_merge($this->findPlaceholders($subject), $this->findPlaceholders($body)));
        list($unknown, $missing) = $this->validatePlaceholders($placeholders, $available, $context);
        if (!empty($unknown) || !empty($missing)) {
            $msg = 'Template invalide; inconnus=[' . implode(',', $unknown) . '], manquants=[' . implode(',', $missing) . ']';
            $this->logger->error('GDH Email: ' . $msg);
            update_option('gdh_email_last_error', $msg);
            return false;
        }
        $renderedSubject = $this->replacePlaceholders($subject, $context);
        $renderedBody = $this->replacePlaceholders($body, $context);
        $to = $context['client_email'];
        if (! is_email($to)) {
            $this->logger->error('GDH Email: email client invalide');
            update_option('gdh_email_last_error', 'Adresse e-mail client invalide.');
            return false;
        }
        $headers = [];
        $sent = false;
        if ($contentType === 'html') {
            $filter = function () { return 'text/html'; };
            add_filter('wp_mail_content_type', $filter);
            $htmlBody = $this->buildHtmlBody($renderedBody, $style);
            $sent = wp_mail($to, $renderedSubject, $htmlBody, $headers);
            remove_filter('wp_mail_content_type', $filter);
            if (! $sent) {
                $plain = wp_strip_all_tags($renderedBody);
                $sent = wp_mail($to, $renderedSubject, $plain, $headers);
            }
        } else {
            $plain = wp_strip_all_tags($renderedBody);
            $sent = wp_mail($to, $renderedSubject, $plain, $headers);
        }
        if (! $sent) {
            $this->logger->error('GDH Email: envoi échoué');
            update_option('gdh_email_last_error', 'Échec d\'envoi de l\'e-mail.');
            return false;
        }
        $this->logger->info('GDH Email: envoi réussi au client');
        delete_option('gdh_email_last_error');
        return true;
    }
}
