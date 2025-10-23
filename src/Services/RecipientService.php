<?php
namespace GDHRDV\Services;

class RecipientService
{
    /**
     * Get recipient info from server configuration, never trust form data
     */
    public static function getSecureRecipientInfo($formData)
    {
        $receivers = get_option('gdhrdv_receivers', []);
        
        // Check static mode first
        $staticEnabled = isset($receivers['static']['enabled']) && $receivers['static']['enabled'] === '1';
        if ($staticEnabled) {
            $email = isset($receivers['static']['email']) ? trim($receivers['static']['email']) : '';
            $name  = isset($receivers['static']['name']) ? trim($receivers['static']['name']) : '';
            
            if ($email && is_email($email)) {
                return [
                    'email' => $email,
                    'name'  => $name ?: get_bloginfo('name'),
                ];
            }
        }
        
        // Check dynamic mode
        $dynamicEnabled = isset($receivers['dynamic']['enabled']) && $receivers['dynamic']['enabled'] === '1';
        if ($dynamicEnabled) {
            $configuredPostType = isset($receivers['dynamic']['post_type']) ? $receivers['dynamic']['post_type'] : '';
            $emailMetaKey       = isset($receivers['dynamic']['email']) ? trim($receivers['dynamic']['email']) : '';
            $nameMetaKey        = isset($receivers['dynamic']['name']) ? trim($receivers['dynamic']['name']) : '';
            
            // Get current post info from form data (but validate it exists)
            $currentPostId = isset($formData['current_post_id']) ? absint($formData['current_post_id']) : 0;
            $currentPostType = isset($formData['current_post_type']) ? sanitize_text_field($formData['current_post_type']) : '';
            
            // Security: Verify the post exists and matches expected type
            if ($currentPostId && $currentPostType === $configuredPostType) {
                $post = get_post($currentPostId);
                if ($post && $post->post_type === $configuredPostType) {
                    $email = trim(get_post_meta($currentPostId, $emailMetaKey, true));
                    $name  = trim(get_post_meta($currentPostId, $nameMetaKey, true));
                    
                    if ($email && is_email($email)) {
                        return [
                            'email' => $email,
                            'name'  => $name ?: get_the_title($currentPostId),
                        ];
                    }
                }
            }
        }
        
        return false;
    }
}
