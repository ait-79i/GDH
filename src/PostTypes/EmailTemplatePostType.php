<?php
namespace GDH\PostTypes;

class EmailTemplatePostType
{
    const POST_TYPE = 'gdh_email_template';

    public function __construct()
    {
        add_action('init', [$this, 'register']);
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('edit_form_after_title', [$this, 'renderSubjectInline']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'saveMeta'], 10, 2);
        add_filter('redirect_post_location', [$this, 'redirectAfterSave'], 10, 2);
    }

    public function register()
    {
        $labels = [
            'name'               => 'Templates e-mail',
            'singular_name'      => 'Template e-mail',
            'menu_name'          => 'Templates e-mail',
            'name_admin_bar'     => 'Template e-mail',
            'add_new'            => 'Ajouter',
            'add_new_item'       => 'Ajouter un template',
            'new_item'           => 'Nouveau template',
            'edit_item'          => 'Modifier le template',
            'view_item'          => 'Voir le template',
            'all_items'          => 'Tous les templates',
            'search_items'       => 'Rechercher',
            'not_found'          => 'Aucun template trouvé',
            'not_found_in_trash' => 'Aucun template dans la corbeille',
        ];

        $args = [
            'labels'          => $labels,
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => 'edit.php?post_type=' . AppointmentPostType::POST_TYPE,
            'menu_position'   => 30,
            'capability_type' => 'post',
            'hierarchical'    => false,
            'supports'        => ['title', 'editor', 'revisions'],
            'has_archive'     => false,
            'rewrite'         => false,
            'query_var'       => false,
            'show_in_rest'    => false,
        ];

        register_post_type(self::POST_TYPE, $args);

        // Register meta fields
        register_post_meta(self::POST_TYPE, '_gdh_email_subject', [
            'show_in_rest' => false,
            'single'       => true,
            'type'         => 'string',
            'auth_callback'=> '__return_true',
        ]);
        register_post_meta(self::POST_TYPE, '_gdh_email_style', [
            'show_in_rest' => false,
            'single'       => true,
            'type'         => 'string',
            'auth_callback'=> '__return_true',
        ]);
        register_post_meta(self::POST_TYPE, '_gdh_email_content_type', [
            'show_in_rest' => false,
            'single'       => true,
            'type'         => 'string',
            'auth_callback'=> '__return_true',
        ]);
        register_post_meta(self::POST_TYPE, '_gdh_email_version', [
            'show_in_rest' => false,
            'single'       => true,
            'type'         => 'string',
            'auth_callback'=> '__return_true',
        ]);
    }

    public function addMetaBoxes()
    {
        add_meta_box(
            'gdh_email_template_settings',
            'Paramètres de l\'e-mail',
            [$this, 'renderSettingsBox'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function renderSettingsBox($post)
    {
        wp_nonce_field('gdh_email_tpl_meta', 'gdh_email_tpl_meta_nonce');

        $subject = get_post_meta($post->ID, '_gdh_email_subject', true);
        $style   = get_post_meta($post->ID, '_gdh_email_style', true);
        $ctype   = get_post_meta($post->ID, '_gdh_email_content_type', true);
        $ctype   = $ctype === 'plain' ? 'plain' : 'html';
        $version = get_post_meta($post->ID, '_gdh_email_version', true);

        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row"><label for="gdh_email_subject">Sujet</label></th><td><input type="text" id="gdh_email_subject" name="gdh_email_subject" class="regular-text" value="' . esc_attr($subject) . '" /></td></tr>';
        echo '<tr><th scope="row">Format</th><td><fieldset>';
        echo '<label><input type="radio" name="gdh_email_content_type" value="html" ' . checked($ctype, 'html', false) . '> HTML</label><br />';
        echo '<label><input type="radio" name="gdh_email_content_type" value="plain" ' . checked($ctype, 'plain', false) . '> Texte brut</label>';
        echo '</fieldset></td></tr>';
        echo '<tr><th scope="row"><label for="gdh_email_version">Version</label></th><td><input type="text" id="gdh_email_version" name="gdh_email_version" class="regular-text" value="' . esc_attr($version) . '" /></td></tr>';
        echo '<tr><th scope="row"><label for="gdh_email_style">Style CSS</label></th><td><textarea id="gdh_email_style" name="gdh_email_style" rows="6" class="large-text">' . esc_textarea($style) . '</textarea></td></tr>';
        echo '</table>';
    }

    public function renderSubjectInline($post)
    {
        if ($post->post_type !== self::POST_TYPE) {
            return;
        }
        $subject = get_post_meta($post->ID, '_gdh_email_subject', true);
        // Inline nonce to allow save even if the meta box is hidden
        wp_nonce_field('gdh_email_tpl_inline', 'gdh_email_tpl_inline_nonce');
        echo '<div class="notice-inline" style="padding:8px 12px;margin-bottom:12px;background:#fff;border:1px solid #dcdcde;border-radius:4px;">';
        echo '<label for="gdh_email_subject_inline" style="display:block;margin-bottom:6px;font-weight:600;">Sujet de l\'e-mail</label>';
        echo '<input type="text" id="gdh_email_subject_inline" name="gdh_email_subject_inline" class="large-text" value="' . esc_attr($subject) . '" placeholder="Sujet de l\'e-mail" />';
        echo '</div>';
    }

    public function saveMeta($post_id, $post)
    {
        $has_meta_nonce   = isset($_POST['gdh_email_tpl_meta_nonce']) && wp_verify_nonce($_POST['gdh_email_tpl_meta_nonce'], 'gdh_email_tpl_meta');
        $has_inline_nonce = isset($_POST['gdh_email_tpl_inline_nonce']) && wp_verify_nonce($_POST['gdh_email_tpl_inline_nonce'], 'gdh_email_tpl_inline');
        if (! $has_meta_nonce && ! $has_inline_nonce) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        if ($post->post_type !== self::POST_TYPE) {
            return;
        }

        if (isset($_POST['gdh_email_subject_inline'])) {
            update_post_meta($post_id, '_gdh_email_subject', sanitize_text_field($_POST['gdh_email_subject_inline']));
        } elseif (isset($_POST['gdh_email_subject'])) {
            update_post_meta($post_id, '_gdh_email_subject', sanitize_text_field($_POST['gdh_email_subject']));
        }
        if (isset($_POST['gdh_email_style'])) {
            update_post_meta($post_id, '_gdh_email_style', wp_kses_post($_POST['gdh_email_style']));
        }
        if (isset($_POST['gdh_email_content_type'])) {
            $ctype = $_POST['gdh_email_content_type'] === 'plain' ? 'plain' : 'html';
            update_post_meta($post_id, '_gdh_email_content_type', $ctype);
        }
        if (isset($_POST['gdh_email_version'])) {
            update_post_meta($post_id, '_gdh_email_version', sanitize_text_field($_POST['gdh_email_version']));
        }
    }

    public function redirectAfterSave($location, $post_id)
    {
        if (get_post_type($post_id) === self::POST_TYPE) {
            return admin_url('admin.php?page=gdh_email_settings&gdh_email_saved=1');
        }
        return $location;
    }
}
