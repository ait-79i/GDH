<?php
namespace GDH\PostTypes;

use GDH\Services\TwigService;

class AppointmentPostType
{
    const POST_TYPE = 'gdh_appointment';

    private $twig;

    public function __construct()
    {
        $this->twig = new TwigService();

        add_action('init', [$this, 'register']);
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'saveMetaData'], 10, 2);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'setCustomColumns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'renderCustomColumns'], 10, 2);
        add_filter('post_row_actions', [$this, 'filterRowActions'], 10, 2);
        add_action('admin_head', [$this, 'makeTitleReadonly']);
    }

    public function register()
    {
        $labels = [
            'name'               => 'Rendez-vous',
            'singular_name'      => 'Rendez-vous',
            'menu_name'          => 'Rendez-vous',
            'name_admin_bar'     => 'Rendez-vous',
            'add_new'            => 'Ajouter',
            'add_new_item'       => 'Ajouter un rendez-vous',
            'new_item'           => 'Nouveau rendez-vous',
            'edit_item'          => 'Modifier le rendez-vous',
            'view_item'          => 'Voir le rendez-vous',
            'all_items'          => 'Tous les rendez-vous',
            'search_items'       => 'Rechercher',
            'not_found'          => 'Aucun rendez-vous trouvé',
            'not_found_in_trash' => 'Aucun rendez-vous dans la corbeille',
        ];

        $args = [
            'labels'          => $labels,
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => true,
            'menu_icon'       => 'dashicons-calendar-alt',
            'menu_position'   => 25,
            'capability_type' => 'post',
            'hierarchical'    => false,
            'supports'        => ['title'],
            'has_archive'     => false,
            'rewrite'         => false,
            'query_var'       => false,
            'show_in_rest'    => false,
            'capabilities'    => [
                'create_posts' => false, // Removes "Add New" button
            ],
            'map_meta_cap'    => true,
        ];

        register_post_type(self::POST_TYPE, $args);
    }

// [Metaboxes]

    public function addMetaBoxes()
    {
        // Remove the default Publish box from the sidebar
        remove_meta_box('submitdiv', self::POST_TYPE, 'side');

        add_meta_box(
            'gdh_appointment_details',
            'Détails du rendez-vous',
            [$this, 'renderAppointmentDetailsMetaBox'],
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'gdh_appointment_slots',
            'Créneaux de disponibilité',
            [$this, 'renderSlotsMetaBox'],
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'gdh_appointment_address',
            'Adresse d\'intervention',
            [$this, 'renderAddressMetaBox'],
            self::POST_TYPE,
            'side',
            'default'
        );
    }

    public function renderAppointmentDetailsMetaBox($post)
    {
        wp_nonce_field('gdh_appointment_meta_box', 'gdh_appointment_meta_box_nonce');

        echo $this->twig->render('admin/appointment/appointment-metaboxes/appointment-details-metabox.twig', [
            'first_name'   => get_post_meta($post->ID, '_gdh_first_name', true),
            'last_name'    => get_post_meta($post->ID, '_gdh_last_name', true),
            'email'        => get_post_meta($post->ID, '_gdh_email', true),
            'phone'        => get_post_meta($post->ID, '_gdh_phone', true),
            'accept_terms' => get_post_meta($post->ID, '_gdh_accept_terms', true),
        ]);
    }

    public function renderSlotsMetaBox($post)
    {
        $slots = get_post_meta($post->ID, '_gdh_slots', true);

        // Prepare slots data for Twig
        $formattedSlots = [];
        if (! empty($slots) && is_array($slots)) {
            foreach ($slots as $slot) {
                $formattedSlots[] = [
                    'date'           => $slot['date'],
                    'formatted_date' => date_i18n('l j F Y', strtotime($slot['date'])),
                    'times'          => $slot['times'],
                    'is_all_day'     => in_array('all-day', $slot['times']),
                ];
            }
        }

        echo $this->twig->render('admin/appointment/appointment-metaboxes/appointment-slots-metabox.twig', [
            'slots' => $formattedSlots,
        ]);
    }

    public function renderAddressMetaBox($post)
    {
        echo $this->twig->render('admin/appointment/appointment-metaboxes/appointment-address-metabox.twig', [
            'address'     => get_post_meta($post->ID, '_gdh_address', true),
            'postal_code' => get_post_meta($post->ID, '_gdh_postal_code', true),
            'city'        => get_post_meta($post->ID, '_gdh_city', true),
        ]);
    }
// []

// [Save meta data]

    public function saveMetaData($post_id, $post)
    {
        // Verify nonce
        if (! isset($_POST['gdh_appointment_meta_box_nonce']) ||
            ! wp_verify_nonce($_POST['gdh_appointment_meta_box_nonce'], 'gdh_appointment_meta_box')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save fields (though they are readonly, this is for programmatic updates)
        $fields = [
            'gdh_first_name'   => '_gdh_first_name',
            'gdh_last_name'    => '_gdh_last_name',
            'gdh_email'        => '_gdh_email',
            'gdh_phone'        => '_gdh_phone',
            'gdh_address'      => '_gdh_address',
            'gdh_postal_code'  => '_gdh_postal_code',
            'gdh_city'         => '_gdh_city',
            'gdh_accept_terms' => '_gdh_accept_terms',
        ];

        foreach ($fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field]));
            }
        }
    }

    public function filterRowActions($actions, $post)
    {
        if (! isset($post->post_type) || $post->post_type !== self::POST_TYPE) {
            return $actions;
        }

        $filtered = [];
        if (isset($actions['view'])) {
            $filtered['view'] = $actions['view'];
        }
        if (isset($actions['trash'])) {
            $filtered['trash'] = $actions['trash'];
        }

        return $filtered;
    }
// []

// [Custom Columns]

    public function setCustomColumns($columns)
    {
        $new_columns                 = [];
        $new_columns['cb']           = $columns['cb'];
        $new_columns['title']        = 'Référence';
        $new_columns['client']       = 'Client';
        $new_columns['email']        = 'Email';
        $new_columns['phone']        = 'Téléphone';
        $new_columns['address']      = 'Adresse';
        $new_columns['availability'] = 'Disponibilités';
        $new_columns['destinataire'] = 'Destinataire';
        $new_columns['email_sent']   = 'Email envoyé';

        return $new_columns;
    }

    public function renderCustomColumns($column, $post_id)
    {
        switch ($column) {
            case 'client':
                $first_name = get_post_meta($post_id, '_gdh_first_name', true);
                $last_name  = get_post_meta($post_id, '_gdh_last_name', true);
                echo esc_html($first_name . ' ' . $last_name);
                break;

            case 'email':
                $email = get_post_meta($post_id, '_gdh_email', true);
                echo '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
                break;

            case 'phone':
                $phone = get_post_meta($post_id, '_gdh_phone', true);
                echo '<a href="tel:' . esc_attr($phone) . '">' . esc_html($phone) . '</a>';
                break;

            case 'address':
                $address = get_post_meta($post_id, '_gdh_address', true);
                $city    = get_post_meta($post_id, '_gdh_city', true);
                echo esc_html($address . ', ' . $city);
                break;

            case 'availability':
                $slots = get_post_meta($post_id, '_gdh_slots', true);

                // Prepare slots data for Twig
                $formattedSlots = [];
                if (! empty($slots) && is_array($slots)) {
                    $priorityColors = [
                        1 => '#2271b1', // Blue for primary
                        2 => '#d63638', // Red for alternative
                        3 => '#50575e', // Gray for backup
                    ];
                    $priorityLabels = [
                        1 => '1ère',
                        2 => '2ème',
                        3 => '3ème',
                    ];

                    foreach ($slots as $index => $slot) {
                        if (! isset($slot['date']) || ! isset($slot['times'])) {
                            continue;
                        }

                        $priority         = $index + 1;
                        $formattedSlots[] = [
                            'date'           => $slot['date'],
                            'formatted_date' => date_i18n('d/m/Y', strtotime($slot['date'])),
                            'day_name'       => date_i18n('D', strtotime($slot['date'])),
                            'times'          => $slot['times'],
                            'is_all_day'     => in_array('all-day', $slot['times']),
                            'color'          => $priorityColors[$priority] ?? '#50575e',
                            'label'          => $priorityLabels[$priority] ?? $priority . 'ème',
                        ];
                    }
                }

                echo $this->twig->render('admin/appointment/appointment-metaboxes/appointment-availability-column.twig', [
                    'slots' => $formattedSlots,
                ]);
                break;

            case 'destinataire':
                $destinataire_name = get_post_meta($post_id, '_gdh_destinataire_name', true);
                $destinataire_email = get_post_meta($post_id, '_gdh_destinataire_email', true);
                $current_post_type = get_post_meta($post_id, '_gdh_current_post_type', true);
                $current_post_id = get_post_meta($post_id, '_gdh_current_post_id', true);
                
                // Get receiver configuration to determine mode
                $receivers = get_option('gdh_receivers', []);
                $dynamicEnabled = isset($receivers['dynamic']['enabled']) && $receivers['dynamic']['enabled'] === '1';
                
                if (!$destinataire_email) {
                    echo '<span style="color:#999;">—</span>';
                    break;
                }
                
                // Dynamic mode: show post ID with public link + name + email
                if ($dynamicEnabled && $current_post_id) {
                    $post_view_link = get_permalink($current_post_id);
                    if ($post_view_link) {
                        echo '<a href="' . esc_url($post_view_link) . '" target="_blank" style="font-weight:600;color:#2271b1;">ID: ' . esc_html($current_post_id) . '</a>';
                        if ($destinataire_name) {
                            echo '<br><span style="font-size:12px;color:#666;">' . esc_html($destinataire_name) . '</span>';
                        }
                        echo '<br><span style="font-size:12px;color:#666;">' . esc_html($destinataire_email) . '</span>';
                    } else {
                        echo '<span style="font-weight:600;">ID: ' . esc_html($current_post_id) . '</span>';
                        if ($destinataire_name) {
                            echo '<br><span style="font-size:12px;color:#666;">' . esc_html($destinataire_name) . '</span>';
                        }
                        echo '<br><span style="font-size:12px;color:#666;">' . esc_html($destinataire_email) . '</span>';
                    }
                } 
                // Static mode: show only email (no link)
                else {
                    echo '<span style="font-weight:600;">' . esc_html($destinataire_email) . '</span>';
                    if ($destinataire_name) {
                        echo '<br><span style="font-size:12px;color:#666;">' . esc_html($destinataire_name) . '</span>';
                    }
                }
                break;

            case 'email_sent':
                $email_sent = get_post_meta($post_id, '_gdh_email_sent', true);
                if ($email_sent === '1') {
                    echo '<span style="color:#46b450;font-weight:600;">✓ Oui</span>';
                } else {
                    echo '<span style="color:#dc3232;font-weight:600;">✗ Non</span>';
                }
                break;
        }
    }

    public function makeTitleReadonly()
    {
        if (! function_exists('get_current_screen')) {
            return;
        }
        $screen = get_current_screen();
        if ($screen && isset($screen->post_type) && $screen->post_type === self::POST_TYPE) {
            echo "<script>jQuery(function($){ $('#title').prop('readonly', true); });</script>";
        }
    }

// []

// [Create anew appointment from form data]

    public static function createAppointment($data)
    {
        // Create post title
        $title = sprintf(
            'RDV - %s %s',
            sanitize_text_field($data['first_name']),
            sanitize_text_field($data['last_name'])
        );

        // Create the post
        $post_id = wp_insert_post([
            'post_type'   => self::POST_TYPE,
            'post_title'  => $title,
            'post_status' => 'publish',
            'post_author' => 1,
        ]);

        if (is_wp_error($post_id)) {
            return false;
        }

        // Save meta data
        update_post_meta($post_id, '_gdh_first_name', sanitize_text_field($data['first_name']));
        update_post_meta($post_id, '_gdh_last_name', sanitize_text_field($data['last_name']));
        update_post_meta($post_id, '_gdh_email', sanitize_email($data['email']));
        update_post_meta($post_id, '_gdh_phone', sanitize_text_field($data['phone']));
        update_post_meta($post_id, '_gdh_address', sanitize_text_field($data['address']));
        update_post_meta($post_id, '_gdh_postal_code', sanitize_text_field($data['postal_code']));
        update_post_meta($post_id, '_gdh_city', sanitize_text_field($data['city']));
        update_post_meta($post_id, '_gdh_accept_terms', $data['accept_terms'] ? '1' : '0');
        update_post_meta($post_id, '_gdh_email_sent', '0');

        // Save slots data
        if (! empty($data['slots']) && is_array($data['slots'])) {
            update_post_meta($post_id, '_gdh_slots', $data['slots']);
        }

        return $post_id;
    }
// [/]

}
