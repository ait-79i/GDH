<?php
namespace GDH\Services;

class EmailTemplateService
{
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    // Post type removed: template is now stored in options via Admin page

    public function getAvailableVariables()
    {
        return [
            'nom_lead',
            'date_rdv',
            'email_lead',
            'phone',
            'address',
            'city',
            'postal_code',
            'nom_destinataire',
            'creneaux_rdv',
        ];
    }

    private function buildContextFromData($post_id, array $formData, $isHtml = false, $recipientInfo = null)
    {
        $first          = isset($formData['first_name']) ? $formData['first_name'] : get_post_meta($post_id, '_gdh_first_name', true);
        $last           = isset($formData['last_name']) ? $formData['last_name'] : get_post_meta($post_id, '_gdh_last_name', true);
        $email          = isset($formData['email']) ? $formData['email'] : get_post_meta($post_id, '_gdh_email', true);
        $phone          = isset($formData['phone']) ? $formData['phone'] : get_post_meta($post_id, '_gdh_phone', true);
        $address        = isset($formData['address']) ? $formData['address'] : get_post_meta($post_id, '_gdh_address', true);
        $postal         = isset($formData['postal_code']) ? $formData['postal_code'] : get_post_meta($post_id, '_gdh_postal_code', true);
        $city           = isset($formData['city']) ? $formData['city'] : get_post_meta($post_id, '_gdh_city', true);
        $slots          = get_post_meta($post_id, '_gdh_slots', true);
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
                if (empty($slot['date'])) {continue;}
                $dateStr = $slot['date'];
                $times   = isset($slot['times']) && is_array($slot['times']) ? $slot['times'] : [];
                $label   = date_i18n('d/m/Y', strtotime($dateStr));
                if (! empty($times)) {
                    if (in_array('all-day', $times, true)) {
                        $label .= ' – Toute la journée';
                    } else {
                        // sanitize times entries
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
                        foreach ($all as $it) {$items .= '<li>' . esc_html($it) . '</li>';}
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
        // Use recipient info passed as parameter
        $recipientName = $recipientInfo ? $recipientInfo['name'] : '';
        
        return [
            'nom_lead'         => trim($first . ' ' . $last),
            'date_rdv'         => $aptDate,
            'email_lead'       => (string) $email,
            'phone'            => (string) $phone,
            'address'          => (string) $address,
            'city'             => (string) $city,
            'postal_code'      => (string) $postal,
            'nom_destinataire' => (string) $recipientName,
            'creneaux_rdv'     => $slotsFormatted,
        ];
    }

    private function getPlaceholderAliases()
    {
        return [
            'artisan_name'      => 'nom_destinataire',
            'client_name'       => 'nom_lead',
            'client_email'      => 'email_lead',
            'appointment_date'  => 'date_rdv',
            'appointment_slots' => 'creneaux_rdv',
        ];
    }

    private function findPlaceholders($text)
    {
        $matches = [];
        preg_match_all('/\{\{([a-zA-Z0-9_]+)\}\}/', (string) $text, $matches);
        return isset($matches[1]) ? array_unique($matches[1]) : [];
    }

    private function validatePlaceholders(array $placeholders, array $available, array $context)
    {
        $unknown = [];
        $missing = [];
        foreach ($placeholders as $key) {
            if (! in_array($key, $available, true)) {
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
        $aliases = $this->getPlaceholderAliases();
        return preg_replace_callback('/\{\{([a-zA-Z0-9_]+)\}\}/', function ($m) use ($context, $aliases) {
            $k   = $m[1];
            $key = array_key_exists($k, $context) ? $k : (isset($aliases[$k]) ? $aliases[$k] : $k);
            return array_key_exists($key, $context) ? (string) $context[$key] : $m[0];
        }, (string) $text);
    }

    private function buildHtmlBody($body, $style)
    {
        $styleTag = trim((string) $style) !== '' ? '<style>' . $style . '</style>' : '';
        return '<!DOCTYPE html><html><head><meta charset="UTF-8">' . $styleTag . '</head><body>' . $body . '</body></html>';
    }

    public function sendOnAppointment($post_id, array $formData)
    {
        $recipientInfo = RecipientService::getSecureRecipientInfo($formData);
        if (!$recipientInfo) {
            $this->logger->error('GDH Email: impossible de résoudre les informations du destinataire');
            return false;
        }
        
        return $this->sendEmail(
            $post_id, 
            $formData, 
            $recipientInfo['email'], 
            'gdh_email_subject', 
            'gdh_email_body',
            'GDH Email',
            $recipientInfo
        );
    }
    
    public function sendConfirmationToClient($post_id, array $formData)
    {
        $recipientInfo = RecipientService::getSecureRecipientInfo($formData);
        $context = $this->buildContextFromData($post_id, $formData, true, $recipientInfo);
        $clientEmail = $context['email_lead'];
        
        if (!is_email($clientEmail)) {
            $this->logger->error('GDH Email Confirmation: email client invalide');
            return false;
        }
        
        return $this->sendEmail(
            $post_id, 
            $formData, 
            $clientEmail, 
            'gdh_email_confirm_subject', 
            'gdh_email_confirm_body',
            'GDH Email Confirmation',
            $recipientInfo
        );
    }
    
    /**
     * Centralized email sending method to avoid code duplication
     */
    private function sendEmail($post_id, array $formData, $to, $subjectOption, $bodyOption, $logPrefix, $recipientInfo = null)
    {
        $subject = (string) get_option($subjectOption, '');
        $body = (string) get_option($bodyOption, '');
        
        if ($subject === '' || $body === '') {
            $this->logger->error($logPrefix . ': sujet ou corps du modèle manquant');
            return false;
        }
        
        $available = $this->getAvailableVariables();
        $context = $this->buildContextFromData($post_id, $formData, true, $recipientInfo);
        $aliases = $this->getPlaceholderAliases();
        $placeholdersRaw = array_unique(array_merge($this->findPlaceholders($subject), $this->findPlaceholders($body)));
        $placeholders = array_map(function ($k) use ($aliases) {return isset($aliases[$k]) ? $aliases[$k] : $k;}, $placeholdersRaw);
        
        list($unknown, $missing) = $this->validatePlaceholders($placeholders, $available, $context);
        if (!empty($unknown) || !empty($missing)) {
            $msg = 'Template invalide; inconnus=[' . implode(',', $unknown) . '], manquants=[' . implode(',', $missing) . ']';
            $this->logger->error($logPrefix . ': ' . $msg);
            return false;
        }
        
        $renderedSubject = $this->replacePlaceholders($subject, $context);
        $renderedBody = $this->replacePlaceholders($body, $context);
        
        // Send HTML email with fallback to plain text
        $filter = function () {return 'text/html';};
        add_filter('wp_mail_content_type', $filter);
        $htmlBody = $this->buildHtmlBody($renderedBody, '');
        $sent = wp_mail($to, $renderedSubject, $htmlBody, []);
        remove_filter('wp_mail_content_type', $filter);
        
        if (!$sent) {
            $plain = wp_strip_all_tags($renderedBody);
            $sent = wp_mail($to, $renderedSubject, $plain, []);
        }
        
        if (!$sent) {
            $this->logger->error($logPrefix . ': envoi échoué');
            return false;
        }
        
        $this->logger->info($logPrefix . ': envoi réussi');
        return true;
    }
}
