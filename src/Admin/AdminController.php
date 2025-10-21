<?php
namespace GDH\Admin;

use GDH\Services\EmailTemplateService;
use GDH\Services\Logger;
use GDH\Services\TwigService;

class AdminController
{
    private $page_hook;
    private $email_page_hook;
    private $twig;
    private $emailService;

    public function __construct()
    {
        $this->twig         = new TwigService();
        $this->emailService = new EmailTemplateService(new Logger());
        add_action('admin_menu', [$this, 'addSettingsSubmenu']);
        add_action('admin_menu', [$this, 'addEmailSettingsSubmenu']);
        add_action('admin_menu', [$this, 'removeEmailTemplateSubmenus'], 999);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_action('admin_post_gdh_save_email_settings', [$this, 'handleEmailSettingsSave']);
        add_action('wp_ajax_gdh_get_meta_keys', [$this, 'ajaxGetMetaKeys']);
    }

    public function addSettingsSubmenu()
    {
        $parent_slug     = 'edit.php?post_type=gdh_appointment';
        $this->page_hook = add_submenu_page(
            $parent_slug,
            'Apparence de la popup',
            'Apparence de la popup',
            'manage_options',
            'gdh_rdv_design',
            [$this, 'renderSettingsPage']
        );
    }

    public function addEmailSettingsSubmenu()
    {
        $parent_slug           = 'edit.php?post_type=gdh_appointment';
        $this->email_page_hook = add_submenu_page(
            $parent_slug,
            'Paramètres des e-mails',
            'Paramètres des e-mails',
            'manage_options',
            'gdh_email_settings',
            [$this, 'renderEmailSettingsPage']
        );
    }

    public function registerSettings()
    {
        register_setting('gdh_rdv_design_settings_group', 'gdh_rdv_design_settings', [
            'sanitize_callback' => [$this, 'sanitizeSettings'],
        ]);

        add_settings_section('gdh_rdv_design_section', '', '__return_false', 'gdh_rdv_design_page');

        add_settings_field('primary_color', 'Couleur primaire', [$this, 'fieldColor'], 'gdh_rdv_design_page', 'gdh_rdv_design_section', ['key' => 'primary_color', 'default' => '#006847']);
        add_settings_field('primary_color_light', 'Couleur primaire (clair)', [$this, 'fieldColor'], 'gdh_rdv_design_page', 'gdh_rdv_design_section', ['key' => 'primary_color_light', 'default' => '#00855e']);
        add_settings_field('primary_color_dark', 'Couleur primaire (foncé)', [$this, 'fieldColor'], 'gdh_rdv_design_page', 'gdh_rdv_design_section', ['key' => 'primary_color_dark', 'default' => '#004d35']);
        add_settings_field('accent_color', 'Couleur accent', [$this, 'fieldColor'], 'gdh_rdv_design_page', 'gdh_rdv_design_section', ['key' => 'accent_color', 'default' => '#FFB81c']);
        add_settings_field('accent_color_dark', 'Couleur accent (foncé)', [$this, 'fieldColor'], 'gdh_rdv_design_page', 'gdh_rdv_design_section', ['key' => 'accent_color_dark', 'default' => '#F5A623']);
        add_settings_field('buttons_text_color', 'Couleur texte des boutons', [$this, 'fieldColor'], 'gdh_rdv_design_page', 'gdh_rdv_design_section', ['key' => 'buttons_text_color', 'default' => '#000000']);
        add_settings_field('overlay_color', 'Couleur d\'overlay', [$this, 'fieldColor'], 'gdh_rdv_design_page', 'gdh_rdv_design_section', ['key' => 'overlay_color', 'default' => '#000000']);
        add_settings_field('overlay_opacity', 'Opacité overlay (0-1)', [$this, 'fieldOpacity'], 'gdh_rdv_design_page', 'gdh_rdv_design_section', ['key' => 'overlay_opacity', 'default' => '0.4']);
        add_settings_field(
            'font_family',
            'Police',
            [$this, 'fieldSelect'],
            'gdh_rdv_design_page',
            'gdh_rdv_design_section',
            [
                'key'     => 'font_family',
                'default' => '',
                'options' => [
                    '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'            => 'System UI (Par défaut)',
                    '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'   => 'Inter',
                    '"Roboto", -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif'          => 'Roboto',
                    '"Open Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif'       => 'Open Sans',
                    '"Montserrat", -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif'      => 'Montserrat',
                    '"Poppins", -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif'         => 'Poppins',
                    '"Lato", -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif'            => 'Lato',
                    '"Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif'          => 'Nunito',
                    '"Source Sans Pro", -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif' => 'Source Sans Pro',
                    '"Playfair Display", Georgia, "Times New Roman", Times, serif'                                          => 'Playfair Display',
                    '"Merriweather", Georgia, "Times New Roman", Times, serif'                                              => 'Merriweather',
                    '"Oswald", Arial, sans-serif'                                                                           => 'Oswald',
                    '"Raleway", -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif'         => 'Raleway',
                ],
            ]
        );
        add_settings_field('font_url', 'URL de police (facultatif)', [$this, 'fieldText'], 'gdh_rdv_design_page', 'gdh_rdv_design_section', ['key' => 'font_url', 'default' => '']);
        add_settings_field('title_text', 'Titre de la popup', [$this, 'fieldText'], 'gdh_rdv_design_page', 'gdh_rdv_design_section', ['key' => 'title_text', 'default' => 'Prendre rendez-vous']);
        add_settings_field('title_align', 'Alignement du titre', [$this, 'fieldSelect'], 'gdh_rdv_design_page', 'gdh_rdv_design_section', ['key' => 'title_align', 'default' => 'left', 'options' => ['left' => 'Gauche', 'center' => 'Centre', 'right' => 'Droite']]);
        $pages = get_posts([
            'post_type'        => 'page',
            'post_status'      => 'publish',
            'numberposts'      => -1,
            'orderby'          => 'title',
            'order'            => 'ASC',
            'suppress_filters' => false,
        ]);
        $page_options = ['' => '— Sélectionner —'];
        foreach ($pages as $p) {
            $page_options[$p->ID] = get_the_title($p);
        }
        add_settings_field('cgv_page_id', 'CGV', [$this, 'fieldSelect'], 'gdh_rdv_design_page', 'gdh_rdv_design_section', ['key' => 'cgv_page_id', 'default' => '', 'options' => $page_options]);
    }

    public function enqueueAdminAssets($hook)
    {
        if ($this->page_hook && $hook === $this->page_hook) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
        }
        if ($this->email_page_hook && $hook === $this->email_page_hook) {
            wp_enqueue_style('wp-editor');
            wp_enqueue_editor();
            // Enqueue scoped admin stylesheet for Email Settings page only
            $css_path = GDH_PLUGIN_PATH . 'assets/css/admin.css';
            $css_ver  = file_exists($css_path) ? filemtime($css_path) : null;
            wp_enqueue_style(
                'gdh-admin-css',
                GDH_PLUGIN_URL . 'assets/css/admin.css',
                ['wp-editor'],
                $css_ver
            );
            // Enqueue page-specific JS
            $js_path = GDH_PLUGIN_PATH . 'assets/js/mail-setting.js';
            $js_ver  = file_exists($js_path) ? filemtime($js_path) : null;
            wp_enqueue_script(
                'gdh-mail-settings',
                GDH_PLUGIN_URL . 'assets/js/mail-setting.js',
                ['jquery', 'wp-editor'],
                $js_ver,
                true
            );
            wp_localize_script(
                'gdh-mail-settings',
                'gdhMailSettings',
                [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce'    => wp_create_nonce('gdh_meta_keys'),
                ]
            );
        }
    }

    public function renderSettingsPage()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        ob_start();
        settings_fields('gdh_rdv_design_settings_group');
        $settings_fields_html = ob_get_clean();

        ob_start();
        do_settings_sections('gdh_rdv_design_page');
        $sections_html = ob_get_clean();

        ob_start();
        submit_button();
        $submit_button_html = ob_get_clean();

        echo $this->twig->render('admin/appointment/design-settings.twig', [
            'title'                => 'Apparence de la popup RDV',
            'form_action'          => 'options.php',
            'settings_fields_html' => $settings_fields_html,
            'sections_html'        => $sections_html,
            'submit_button_html'   => $submit_button_html,
        ]);
    }

    public function renderEmailSettingsPage()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $vars          = $this->emailService->getAvailableVariables();
        $subject_value = (string) get_option('gdh_email_subject', '');
        $body_initial  = (string) get_option('gdh_email_body', '');

        // Confirmation email settings (stored as options)
        $confirm_enabled       = get_option('gdh_email_confirm_enabled', '0') === '1';
        $confirm_subject_value = (string) get_option('gdh_email_confirm_subject', '');
        $confirm_body_initial  = (string) get_option('gdh_email_confirm_body', '');

        // Receiver settings (static and dynamic)
        $receivers_opt       = get_option('gdh_receivers', []);
        $recv_static_enabled = isset($receivers_opt['static']['enabled']) && $receivers_opt['static']['enabled'] === '1';
        $recv_static_email   = isset($receivers_opt['static']['email']) ? (string) $receivers_opt['static']['email'] : '';
        $recv_static_name    = isset($receivers_opt['static']['name']) ? (string) $receivers_opt['static']['name'] : '';
        $recv_dyn_enabled    = isset($receivers_opt['dynamic']['enabled']) && $receivers_opt['dynamic']['enabled'] === '1';
        $recv_dyn_email      = isset($receivers_opt['dynamic']['email']) ? (string) $receivers_opt['dynamic']['email'] : '';
        $recv_dyn_name       = isset($receivers_opt['dynamic']['name']) ? (string) $receivers_opt['dynamic']['name'] : '';
        $recv_dyn_post_type  = isset($receivers_opt['dynamic']['post_type']) ? (string) $receivers_opt['dynamic']['post_type'] : '';

        // Build list of available post types (UI-visible)
        $pts            = get_post_types(['show_ui' => true], 'objects');
        $all_post_types = [];
        foreach ($pts as $pt) {
            $label                     = isset($pt->labels->singular_name) && $pt->labels->singular_name ? $pt->labels->singular_name : $pt->label;
            $all_post_types[$pt->name] = $label;
        }

        // Derive current receiver mode for radio UI (legacy-compatible)
        $recv_mode = $recv_static_enabled ? 'static' : ($recv_dyn_enabled ? 'dynamic' : '');

        // Nonce field HTML
        $nonce_field = wp_nonce_field('gdh_email_settings', 'gdh_email_settings_nonce', true, false);

        // Editor HTML capture with initial content (patch current template)
        ob_start();
        wp_editor($body_initial, 'gdh_body', [
            'textarea_name' => 'body',
            'media_buttons' => false,
            'textarea_rows' => 16,
            'editor_height' => 380,
        ]);
        $editor_html = ob_get_clean();

        // Confirmation body editor
        ob_start();
        wp_editor($confirm_body_initial, 'gdh_confirm_body', [
            'textarea_name' => 'confirm_body',
            'media_buttons' => false,
            'textarea_rows' => 14,
            'editor_height' => 340,
        ]);
        $confirm_editor_html = ob_get_clean();

        $view = $this->twig->render('admin/mail/email-settings.twig', [
            'variables'                    => $vars,
            'variables_sujet_gauche'       => ['nom_lead', 'date_rdv'],
            'variables_confirmation_sujet' => ['nom_destinataire', 'date_rdv'],
            'show_saved_notice'            => isset($_GET['gdh_email_saved']),
            'admin_post_url'               => esc_url(admin_url('admin-post.php')),
            'nonce_field'                  => $nonce_field,
            'editor_html'                  => $editor_html,
            'subject_value'                => $subject_value,
            // Confirmation props
            'confirm_enabled'              => $confirm_enabled,
            'confirm_subject_value'        => $confirm_subject_value,
            'confirm_editor_html'          => $confirm_editor_html,
            // Receiver props
            'recv_mode'                    => $recv_mode,
            'recv_static_enabled'          => $recv_static_enabled,
            'recv_static_email'            => $recv_static_email,
            'recv_static_name'             => $recv_static_name,
            'recv_dyn_enabled'             => $recv_dyn_enabled,
            'recv_dyn_email'               => $recv_dyn_email,
            'recv_dyn_name'                => $recv_dyn_name,
            'recv_dyn_post_type'           => $recv_dyn_post_type,
            'all_post_types'               => $all_post_types,
        ]);
        echo $view;
    }

    public function handleEmailSettingsSave()
    {
        if (! current_user_can('manage_options')) {
            wp_die('Permissions insuffisantes');
        }
        if (! isset($_POST['gdh_email_settings_nonce']) || ! wp_verify_nonce($_POST['gdh_email_settings_nonce'], 'gdh_email_settings')) {
            wp_die('Nonce invalide');
        }

        // Save main template subject/body as options (single template storage)
        $subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '';
        $body    = isset($_POST['body']) ? wp_kses_post($_POST['body']) : '';
        update_option('gdh_email_subject', $subject);
        update_option('gdh_email_body', $body);

        // Save confirmation email settings
        $confirm_enabled_post = isset($_POST['confirm_enabled']) && $_POST['confirm_enabled'] === '1' ? '1' : '0';
        $confirm_subject_post = isset($_POST['confirm_subject']) ? sanitize_text_field($_POST['confirm_subject']) : '';
        $confirm_body_post    = isset($_POST['confirm_body']) ? wp_kses_post($_POST['confirm_body']) : '';
        update_option('gdh_email_confirm_enabled', $confirm_enabled_post);
        update_option('gdh_email_confirm_subject', $confirm_subject_post);
        update_option('gdh_email_confirm_body', $confirm_body_post);

        // Save receiver settings (combined array option)
        $recv_mode_post = isset($_POST['receiver_mode']) ? sanitize_key($_POST['receiver_mode']) : '';
        $recv_static_enabled_post = ($recv_mode_post === 'static') ? '1' : '0';
        $recv_static_email_post   = isset($_POST['receiver_static_email']) ? sanitize_email($_POST['receiver_static_email']) : '';
        $recv_static_name_post    = isset($_POST['receiver_static_name']) ? sanitize_text_field($_POST['receiver_static_name']) : '';
        $recv_dyn_enabled_post    = ($recv_mode_post === 'dynamic') ? '1' : '0';
        $recv_dyn_email_post      = isset($_POST['receiver_dynamic_email']) ? sanitize_text_field($_POST['receiver_dynamic_email']) : '';
        $recv_dyn_name_post       = isset($_POST['receiver_dynamic_name']) ? sanitize_text_field($_POST['receiver_dynamic_name']) : '';
        $recv_dyn_post_type_post  = isset($_POST['receiver_dynamic_post_type']) ? sanitize_key($_POST['receiver_dynamic_post_type']) : '';

        $receivers_new = [
            'static'  => [
                'enabled' => $recv_static_enabled_post,
                'email'   => $recv_static_email_post,
                'name'    => $recv_static_name_post,
            ],
            'dynamic' => [
                'enabled'   => $recv_dyn_enabled_post,
                'email'     => $recv_dyn_email_post,
                'name'      => $recv_dyn_name_post,
                'post_type' => $recv_dyn_post_type_post,
            ],
        ];
        update_option('gdh_receivers', $receivers_new);

        wp_safe_redirect(admin_url('admin.php?page=gdh_email_settings'));
        exit;
    }

    public function ajaxGetMetaKeys()
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'forbidden'], 403);
        }
        if (! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'gdh_meta_keys')) {
            wp_send_json_error(['message' => 'invalid_nonce'], 400);
        }
        $post_type = isset($_POST['post_type']) ? sanitize_key($_POST['post_type']) : '';
        if (! $post_type) {
            wp_send_json_error(['message' => 'missing_post_type'], 400);
        }
        global $wpdb;
        $keys = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT pm.meta_key
                 FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                 WHERE p.post_type = %s
                   AND pm.meta_key IS NOT NULL
                   AND pm.meta_key <> ''
                 ORDER BY pm.meta_key ASC",
                $post_type
            )
        );
        if (! is_array($keys)) {
            $keys = [];
        }
        wp_send_json_success(['meta_keys' => array_values($keys)]);
    }

    public function removeEmailTemplateSubmenus()
    {
        // Remove the CPT submenus under Rendez-vous
        remove_submenu_page('edit.php?post_type=gdh_appointment', 'edit.php?post_type=gdh_email_template');
        remove_submenu_page('edit.php?post_type=gdh_appointment', 'post-new.php?post_type=gdh_email_template');
    }

    public function fieldColor($args)
    {
        $options = get_option('gdh_rdv_design_settings', []);
        $key     = $args['key'];
        $default = $args['default'];
        $value   = isset($options[$key]) ? $options[$key] : $default;
        echo $this->twig->render('admin/appointment/fields/color.twig', [
            'name'    => 'gdh_rdv_design_settings[' . esc_attr($key) . ']',
            'value'   => esc_attr($value),
            'default' => esc_attr($default),
        ]);
    }

    public function fieldOpacity($args)
    {
        $options = get_option('gdh_rdv_design_settings', []);
        $key     = $args['key'];
        $default = $args['default'];
        $value   = isset($options[$key]) ? $options[$key] : $default;
        echo $this->twig->render('admin/appointment/fields/opacity.twig', [
            'name'  => 'gdh_rdv_design_settings[' . esc_attr($key) . ']',
            'value' => esc_attr($value),
        ]);
    }

    public function fieldText($args)
    {
        $options     = get_option('gdh_rdv_design_settings', []);
        $key         = $args['key'];
        $default     = $args['default'];
        $value       = isset($options[$key]) ? $options[$key] : $default;
        $type        = ($key === 'font_url') ? 'url' : 'text';
        $placeholder = ($key === 'font_url') ? 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap' : '';
        echo $this->twig->render('admin/appointment/fields/text.twig', [
            'name'        => 'gdh_rdv_design_settings[' . esc_attr($key) . ']',
            'value'       => esc_attr($value),
            'placeholder' => esc_attr($placeholder),
            'type'        => esc_attr($type),
        ]);
    }

    public function fieldSelect($args)
    {
        $options = get_option('gdh_rdv_design_settings', []);
        $key     = $args['key'];
        $default = $args['default'];
        $value   = isset($options[$key]) ? $options[$key] : $default;
        $opts    = isset($args['options']) && is_array($args['options']) ? $args['options'] : [];
        echo $this->twig->render('admin/appointment/fields/select.twig', [
            'name'    => 'gdh_rdv_design_settings[' . esc_attr($key) . ']',
            'value'   => $value,
            'options' => $opts,
        ]);
    }

    public function sanitizeSettings($input)
    {
        $defaults = [
            'primary_color'       => '#006847',
            'primary_color_light' => '#00855e',
            'primary_color_dark'  => '#004d35',
            'accent_color'        => '#FFB81c',
            'accent_color_dark'   => '#F5A623',
            'buttons_text_color'  => '#000000',
            'overlay_color'       => '#000000',
            'overlay_opacity'     => '0.4',
            'font_family'         => '',
            'font_url'            => '',
        ];

        $output                        = [];
        $output['primary_color']       = sanitize_hex_color(isset($input['primary_color']) ? $input['primary_color'] : $defaults['primary_color']);
        $output['primary_color_light'] = sanitize_hex_color(isset($input['primary_color_light']) ? $input['primary_color_light'] : $defaults['primary_color_light']);
        $output['primary_color_dark']  = sanitize_hex_color(isset($input['primary_color_dark']) ? $input['primary_color_dark'] : $defaults['primary_color_dark']);
        $output['accent_color']        = sanitize_hex_color(isset($input['accent_color']) ? $input['accent_color'] : $defaults['accent_color']);
        $output['accent_color_dark']   = sanitize_hex_color(isset($input['accent_color_dark']) ? $input['accent_color_dark'] : $defaults['accent_color_dark']);
        $output['buttons_text_color']  = sanitize_hex_color(isset($input['buttons_text_color']) ? $input['buttons_text_color'] : $defaults['buttons_text_color']);
        $overlay                       = isset($input['overlay_color']) ? $input['overlay_color'] : $defaults['overlay_color'];
        $output['overlay_color']       = sanitize_hex_color($overlay) ?: '#000000';
        $opacity                       = isset($input['overlay_opacity']) ? floatval($input['overlay_opacity']) : floatval($defaults['overlay_opacity']);
        if ($opacity < 0) {
            $opacity = 0;
        }

        if ($opacity > 1) {
            $opacity = 1;
        }

        $output['overlay_opacity'] = (string) $opacity;
        $output['font_family']     = isset($input['font_family']) ? sanitize_text_field($input['font_family']) : '';
        // If user provided a custom font URL, keep it; otherwise, set based on popular font selection
        $provided_url      = isset($input['font_url']) ? esc_url_raw($input['font_url']) : '';
        $popular_font_urls = [
            '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif'   => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
            '"Roboto", -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif'          => 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap',
            '"Open Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif'       => 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap',
            '"Montserrat", -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif'      => 'https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700&display=swap',
            '"Poppins", -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif'         => 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap',
            '"Lato", -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif'            => 'https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap',
            '"Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif'          => 'https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap',
            '"Source Sans Pro", -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif' => 'https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@400;600;700&display=swap',
            '"Playfair Display", Georgia, "Times New Roman", Times, serif'                                          => 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&display=swap',
            '"Merriweather", Georgia, "Times New Roman", Times, serif'                                              => 'https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&display=swap',
            '"Oswald", Arial, sans-serif'                                                                           => 'https://fonts.googleapis.com/css2?family=Oswald:wght@400;600&display=swap',
            '"Raleway", -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif'         => 'https://fonts.googleapis.com/css2?family=Raleway:wght@500;600;700&display=swap',
        ];
        if (! empty($provided_url)) {
            $output['font_url'] = $provided_url;
        } else {
            $output['font_url'] = isset($popular_font_urls[$output['font_family']]) ? $popular_font_urls[$output['font_family']] : '';
        }
        $output['title_text']  = isset($input['title_text']) ? sanitize_text_field($input['title_text']) : 'Prendre rendez-vous';
        $align                 = isset($input['title_align']) ? $input['title_align'] : 'left';
        $allowed               = ['left', 'center', 'right'];
        $output['title_align'] = in_array($align, $allowed, true) ? $align : 'left';
        $output['cgv_page_id'] = isset($input['cgv_page_id']) ? (string) absint($input['cgv_page_id']) : '';
        return $output;
    }
}
