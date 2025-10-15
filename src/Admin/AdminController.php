<?php
namespace GDH\Admin;

class AdminController
{
    private $page_hook;

    public function __construct()
    {
        add_action('admin_menu', [$this, 'addSettingsSubmenu']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
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

    public function fieldColor($args)
    {
        $options = get_option('gdh_rdv_design_settings', []);
        $key     = $args['key'];
        $default = $args['default'];
        $value   = isset($options[$key]) ? $options[$key] : $default;
        echo '<input type="text" class="regular-text gdh-color-field" name="gdh_rdv_design_settings[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" data-default-color="' . esc_attr($default) . '" />';
        echo '<script>jQuery(function($){ $(".gdh-color-field").wpColorPicker(); });</script>';
    }

    public function fieldOpacity($args)
    {
        $options = get_option('gdh_rdv_design_settings', []);
        $key     = $args['key'];
        $default = $args['default'];
        $value   = isset($options[$key]) ? $options[$key] : $default;
        echo '<input type="number" step="0.05" min="0" max="1" name="gdh_rdv_design_settings[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" />';
    }

    public function fieldFontFamily($args)
    {
        $options = get_option('gdh_rdv_design_settings', []);
        $key     = $args['key'];
        $default = $args['default'];
        $value   = isset($options[$key]) ? $options[$key] : $default;
        echo '<input type="text" class="regular-text" placeholder="Ex: Inter, \"Segoe UI\", Roboto, sans-serif" name="gdh_rdv_design_settings[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" />';
    }

    public function fieldText($args)
    {
        $options     = get_option('gdh_rdv_design_settings', []);
        $key         = $args['key'];
        $default     = $args['default'];
        $value       = isset($options[$key]) ? $options[$key] : $default;
        $type        = ($key === 'font_url') ? 'url' : 'text';
        $placeholder = ($key === 'font_url') ? 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap' : '';
        echo '<input type="' . esc_attr($type) . '" class="regular-text" placeholder="' . esc_attr($placeholder) . '" name="gdh_rdv_design_settings[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" />';
    }

    public function fieldSelect($args)
    {
        $options = get_option('gdh_rdv_design_settings', []);
        $key     = $args['key'];
        $default = $args['default'];
        $value   = isset($options[$key]) ? $options[$key] : $default;
        $opts    = isset($args['options']) && is_array($args['options']) ? $args['options'] : [];
        echo '<select name="gdh_rdv_design_settings[' . esc_attr($key) . ']">';
        foreach ($opts as $val => $label) {
            $selected = selected($value, $val, false);
            echo '<option value="' . esc_attr($val) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }

    public function sanitizeSettings($input)
    {
        $defaults = [
            'primary_color'       => '#006847',
            'primary_color_light' => '#00855e',
            'primary_color_dark'  => '#004d35',
            'accent_color'        => '#FFB81c',
            'accent_color_dark'   => '#F5A623',
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
