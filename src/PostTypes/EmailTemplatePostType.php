<?php
namespace GDH\PostTypes;

class EmailTemplatePostType
{
    const POST_TYPE = 'gdh_email_template';

    public function __construct()
    {
        add_action('init', [$this, 'register']);
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
        register_post_meta(self::POST_TYPE, '_gdh_email_is_active', [
            'show_in_rest' => false,
            'single'       => true,
            'type'         => 'boolean',
            'auth_callback'=> '__return_true',
        ]);
    }
}
