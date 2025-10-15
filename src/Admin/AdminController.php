<?php
namespace GDH\Admin;

use GDH\Services\TwigService;
use GDH\Services\Logger;
use GDH\Services\EmailTemplateService;
use GDH\PostTypes\EmailTemplatePostType;

class AdminController
{
    private $page_hook;
    private $email_page_hook;
    private $twig;
    private $emailService;

    public function __construct()
    {
        $this->twig = new TwigService();
        $this->emailService = new EmailTemplateService(new Logger());
        add_action('admin_menu', [$this, 'addSettingsSubmenu']);
        add_action('admin_menu', [$this, 'addEmailSettingsSubmenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_action('admin_post_gdh_save_email_settings', [$this, 'handleEmailSettingsSave']);
    }

    public function addSettingsSubmenu()
    {
        $parent_slug     = 'edit.php?post_type=gdh_appointment';
        $this->page_hook = add_submenu_page(
            $parent_slug,
            'Design du Popup',
            'Design du Popup',
            'manage_options',
            'gdh_rdv_design',
            [$this, 'renderSettingsPage']
        );
    }

    public function addEmailSettingsSubmenu()
    {
        $parent_slug          = 'edit.php?post_type=gdh_appointment';
        $this->email_page_hook = add_submenu_page(
            $parent_slug,
            'Email Settings',
            'Email Settings',
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
        }
    }

    public function renderSettingsPage()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>Design du Popup RDV</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('gdh_rdv_design_settings_group');
        do_settings_sections('gdh_rdv_design_page');
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    public function renderEmailSettingsPage()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $active = $this->emailService->getActiveTemplate();
        $active_id = $active ? intval($active->ID) : 0;
        $templates = get_posts([
            'post_type'   => EmailTemplatePostType::POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby'     => 'date',
            'order'       => 'DESC',
        ]);
        $vars = $this->emailService->getAvailableVariables();

        echo '<div class="wrap">';
        echo '<h1>Email Settings</h1>';
        if (isset($_GET['gdh_email_saved'])) {
            echo '<div class="notice notice-success is-dismissible"><p>Paramètres e-mail enregistrés.</p></div>';
        }
        $last_err = get_option('gdh_email_last_error');
        if (! empty($last_err)) {
            echo '<div class="notice notice-error is-dismissible"><p><strong>Dernière erreur d\'envoi :</strong> ' . esc_html($last_err) . '</p></div>';
        }

        echo '<h2>Créer un nouveau template</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="gdh_save_email_settings" />';
        wp_nonce_field('gdh_email_settings', 'gdh_email_settings_nonce');
        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row"><label for="gdh_subject">Sujet</label></th><td><input type="text" id="gdh_subject" name="subject" class="regular-text" required /></td></tr>';
        echo '<tr><th scope="row"><label for="gdh_body">Corps</label></th><td>';
        wp_editor('', 'gdh_body', [
            'textarea_name' => 'body',
            'media_buttons' => false,
            'textarea_rows' => 12,
        ]);
        echo '</td></tr>';
        echo '<tr><th scope="row"><label for="gdh_style">Style CSS</label></th><td><textarea id="gdh_style" name="style" rows="6" class="large-text" placeholder="body{font-family:Arial;} .btn{background:#006847;color:#fff;}"></textarea></td></tr>';
        echo '<tr><th scope="row">Format</th><td><fieldset>';
        echo '<label><input type="radio" name="content_type" value="html" checked> HTML</label><br />';
        echo '<label><input type="radio" name="content_type" value="plain"> Texte brut</label>';
        echo '</fieldset></td></tr>';
        echo '<tr><th scope="row"><label for="gdh_version">Version</label></th><td><input type="text" id="gdh_version" name="version" class="regular-text" placeholder="v1.0" /></td></tr>';
        echo '<tr><th scope="row">Activer</th><td><label><input type="checkbox" name="is_active" value="1" /> Définir ce template comme actif</label></td></tr>';
        echo '</table>';
        submit_button('Enregistrer le template');
        echo '</form>';

        echo '<h2>Templates existants</h2>';
        if (! empty($templates)) {
            echo '<table class="widefat fixed striped">';
            echo '<thead><tr><th>ID</th><th>Titre</th><th>Version</th><th>Actif</th><th>Actions</th></tr></thead><tbody>';
            foreach ($templates as $t) {
                $ver = get_post_meta($t->ID, '_gdh_email_version', true);
                $is_active = get_post_meta($t->ID, '_gdh_email_is_active', true) === '1';
                $edit_link = get_edit_post_link($t->ID);
                echo '<tr>';
                echo '<td>' . intval($t->ID) . '</td>';
                echo '<td>' . esc_html(get_the_title($t)) . '</td>';
                echo '<td>' . esc_html($ver) . '</td>';
                echo '<td>' . ($is_active ? '<span class="dashicons dashicons-yes"></span>' : '') . '</td>';
                echo '<td>';
                echo '<a class="button button-secondary" href="' . esc_url($edit_link) . '">Modifier</a> ';
                echo '<form style="display:inline" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
                echo '<input type="hidden" name="action" value="gdh_save_email_settings" />';
                wp_nonce_field('gdh_email_settings', 'gdh_email_settings_nonce');
                echo '<input type="hidden" name="activate_id" value="' . intval($t->ID) . '" />';
                echo '<button type="submit" class="button">' . ($is_active ? 'Actif' : 'Activer') . '</button>';
                echo '</form>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>Aucun template pour le moment.</p>';
        }

        echo '<h2>Variables disponibles</h2>';
        echo '<p>Cliquez pour copier le shortcode.</p>';
        echo '<ul>';
        foreach ($vars as $code => $desc) {
            $ph = '[' . $code . ']';
            echo '<li><button class="button copy-ph" data-ph="' . esc_attr($ph) . '">' . esc_html($ph) . '</button> ' . esc_html($desc) . '</li>';
        }
        echo '</ul>';
        echo '<script>document.addEventListener("click",function(e){ if(e.target && e.target.classList.contains("copy-ph")){ e.preventDefault(); const ph=e.target.getAttribute("data-ph"); navigator.clipboard.writeText(ph); e.target.innerText="Copié!"; setTimeout(()=>{e.target.innerText=ph;},1000);} });</script>';

        echo '</div>';
    }

    public function handleEmailSettingsSave()
    {
        if (! current_user_can('manage_options')) {
            wp_die('Permissions insuffisantes');
        }
        if (! isset($_POST['gdh_email_settings_nonce']) || ! wp_verify_nonce($_POST['gdh_email_settings_nonce'], 'gdh_email_settings')) {
            wp_die('Nonce invalide');
        }

        // Activation d'un template existant
        if (! empty($_POST['activate_id'])) {
            $activate_id = intval($_POST['activate_id']);
            $all = get_posts([
                'post_type'   => EmailTemplatePostType::POST_TYPE,
                'post_status' => 'publish',
                'numberposts' => -1,
                'fields'      => 'ids',
            ]);
            if (! empty($all)) {
                foreach ($all as $tid) {
                    update_post_meta($tid, '_gdh_email_is_active', $tid === $activate_id ? '1' : '0');
                }
            }
            wp_safe_redirect(add_query_arg('gdh_email_saved', '1', menu_page_url('gdh_email_settings', false)));
            exit;
        }

        // Création d'un nouveau template
        $subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '';
        $body    = isset($_POST['body']) ? wp_kses_post($_POST['body']) : '';
        $style   = isset($_POST['style']) ? wp_kses_post($_POST['style']) : '';
        $ctype   = isset($_POST['content_type']) && $_POST['content_type'] === 'plain' ? 'plain' : 'html';
        $version = isset($_POST['version']) && $_POST['version'] !== '' ? sanitize_text_field($_POST['version']) : ('v' . date('YmdHis'));
        $isActive = ! empty($_POST['is_active']) ? '1' : '0';

        $post_id = wp_insert_post([
            'post_type'   => EmailTemplatePostType::POST_TYPE,
            'post_status' => 'publish',
            'post_title'  => 'Email Template ' . $version,
            'post_content'=> $body,
        ]);
        if (! is_wp_error($post_id)) {
            update_post_meta($post_id, '_gdh_email_subject', $subject);
            update_post_meta($post_id, '_gdh_email_style', $style);
            update_post_meta($post_id, '_gdh_email_content_type', $ctype);
            update_post_meta($post_id, '_gdh_email_version', $version);
            update_post_meta($post_id, '_gdh_email_is_active', $isActive);
            if ($isActive === '1') {
                $others = get_posts([
                    'post_type'   => EmailTemplatePostType::POST_TYPE,
                    'post_status' => 'publish',
                    'numberposts' => -1,
                    'fields'      => 'ids',
                    'exclude'     => [$post_id],
                ]);
                foreach ($others as $tid) {
                    update_post_meta($tid, '_gdh_email_is_active', '0');
                }
            }
        }

        wp_safe_redirect(add_query_arg('gdh_email_saved', '1', menu_page_url('gdh_email_settings', false)));
        exit;
    }

    public function fieldColor($args)
    {
        $options = get_option('gdh_rdv_design_settings', []);
        $key     = $args['key'];
        $default = $args['default'];
        $value   = isset($options[$key]) ? $options[$key] : $default;
        echo $this->twig->render('admin/fields/color.twig', [
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
        echo $this->twig->render('admin/fields/opacity.twig', [
            'name'  => 'gdh_rdv_design_settings[' . esc_attr($key) . ']',
            'value' => esc_attr($value),
        ]);
    }

    public function fieldFontFamily($args)
    {
        $options = get_option('gdh_rdv_design_settings', []);
        $key     = $args['key'];
        $default = $args['default'];
        $value   = isset($options[$key]) ? $options[$key] : $default;
        echo $this->twig->render('admin/fields/font-family.twig', [
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
        echo $this->twig->render('admin/fields/text.twig', [
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
        echo $this->twig->render('admin/fields/select.twig', [
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
        return $output;
    }
}
