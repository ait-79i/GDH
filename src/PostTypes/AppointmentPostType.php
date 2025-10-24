<?php
namespace GDHRDV\PostTypes;

use GDHRDV\Services\TwigService;
use GDHRDV\Services\EmailTemplateService;
use GDHRDV\Services\Logger;

class AppointmentPostType
{
    const POST_TYPE = 'gdhrdv_appointment';

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
        add_action('admin_footer', [$this, 'addResendEmailScript']);
        add_action('admin_notices', [$this, 'displayAdminNotices']);
        
        // Ajouter la recherche personnalisée pour les champs meta
        add_filter('posts_where', [$this, 'extendSearchToCustomFields'], 10, 2);
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

// [Metaboxes] — Gestion des boîtes meta en administration

    public function addMetaBoxes()
    {
        // Remove the default Publish box from the sidebar
        remove_meta_box('submitdiv', self::POST_TYPE, 'side');

        add_meta_box(
            'gdhrdv_appointment_details',
            'Détails du rendez-vous',
            [$this, 'renderAppointmentDetailsMetaBox'],
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'gdhrdv_appointment_slots',
            'Créneaux de disponibilité',
            [$this, 'renderSlotsMetaBox'],
            self::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'gdhrdv_appointment_address',
            'Adresse d\'intervention',
            [$this, 'renderAddressMetaBox'],
            self::POST_TYPE,
            'side',
            'default'
        );
    }

    public function renderAppointmentDetailsMetaBox($post)
    {
        wp_nonce_field('gdhrdv_appointment_meta_box', 'gdhrdv_appointment_meta_box_nonce');

        echo $this->twig->render('admin/appointment/appointment-metaboxes/appointment-details-metabox.twig', [
            'first_name'   => get_post_meta($post->ID, '_gdhrdv_first_name', true),
            'last_name'    => get_post_meta($post->ID, '_gdhrdv_last_name', true),
            'email'        => get_post_meta($post->ID, '_gdhrdv_email', true),
            'phone'        => get_post_meta($post->ID, '_gdhrdv_phone', true),
            'accept_terms' => get_post_meta($post->ID, '_gdhrdv_accept_terms', true),
        ]);
    }

    public function renderSlotsMetaBox($post)
    {
        $slots = get_post_meta($post->ID, '_gdhrdv_slots', true);

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
            'address'     => get_post_meta($post->ID, '_gdhrdv_address', true),
            'postal_code' => get_post_meta($post->ID, '_gdhrdv_postal_code', true),
            'city'        => get_post_meta($post->ID, '_gdhrdv_city', true),
        ]);
    }
// []

// [Sauvegarde des métadonnées]

    public function saveMetaData($post_id, $post)
    {
        // Verify nonce
        if (! isset($_POST['gdhrdv_appointment_meta_box_nonce']) ||
            ! wp_verify_nonce($_POST['gdhrdv_appointment_meta_box_nonce'], 'gdhrdv_appointment_meta_box')) {
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
            'gdhrdv_first_name'   => '_gdhrdv_first_name',
            'gdhrdv_last_name'    => '_gdhrdv_last_name',
            'gdhrdv_email'        => '_gdhrdv_email',
            'gdhrdv_phone'        => '_gdhrdv_phone',
            'gdhrdv_address'      => '_gdhrdv_address',
            'gdhrdv_postal_code'  => '_gdhrdv_postal_code',
            'gdhrdv_city'         => '_gdhrdv_city',
            'gdhrdv_accept_terms' => '_gdhrdv_accept_terms',
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

// [Colonnes personnalisées de la liste]

    public function setCustomColumns($columns)
    {
        $new_columns                 = [];
        $new_columns['cb']           = $columns['cb'];
        $new_columns['title']        = 'Référence';
        $new_columns['created_at']   = 'Date de création';
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
            case 'created_at':
                $post_obj = get_post($post_id);
                if ($post_obj) {
                    echo esc_html(date_i18n('d/m/Y H:i', strtotime($post_obj->post_date)));
                }
                break;

            case 'email':
                $email = get_post_meta($post_id, '_gdhrdv_email', true);
                echo '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a>';
                break;

            case 'phone':
                $phone = get_post_meta($post_id, '_gdhrdv_phone', true);
                echo '<a href="tel:' . esc_attr($phone) . '">' . esc_html($phone) . '</a>';
                break;

            case 'address':
                $address = get_post_meta($post_id, '_gdhrdv_address', true);
                $city    = get_post_meta($post_id, '_gdhrdv_city', true);
                echo esc_html($address . ', ' . $city);
                break;

            case 'availability':
                $slots = get_post_meta($post_id, '_gdhrdv_slots', true);

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
                $destinataire_name = get_post_meta($post_id, '_gdhrdv_destinataire_name', true);
                $destinataire_email = get_post_meta($post_id, '_gdhrdv_destinataire_email', true);
                $current_post_type = get_post_meta($post_id, '_gdhrdv_current_post_type', true);
                $current_post_id = get_post_meta($post_id, '_gdhrdv_current_post_id', true);
                
                // Get receiver configuration to determine mode
                $receivers = get_option('gdhrdv_receivers', []);
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
                $email_sent = get_post_meta($post_id, '_gdhrdv_email_sent', true);
                if ($email_sent === '1') {
                    echo '<span style="color:#46b450;font-weight:600;">✓ Oui</span>';
                } else {
                    echo '<span style="color:#dc3232;font-weight:600;">✗ Non</span>';
                    echo '<button type="button" class="button button-small gdhrdv-resend-email" data-post-id="' . esc_attr($post_id) . '" style="margin:0px 30px;">Envoyer</button>';
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

// [Création d'un rendez-vous à partir des données du formulaire]

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
        update_post_meta($post_id, '_gdhrdv_first_name', sanitize_text_field($data['first_name']));
        update_post_meta($post_id, '_gdhrdv_last_name', sanitize_text_field($data['last_name']));
        update_post_meta($post_id, '_gdhrdv_email', sanitize_email($data['email']));
        update_post_meta($post_id, '_gdhrdv_phone', sanitize_text_field($data['phone']));
        update_post_meta($post_id, '_gdhrdv_address', sanitize_text_field($data['address']));
        update_post_meta($post_id, '_gdhrdv_postal_code', sanitize_text_field($data['postal_code']));
        update_post_meta($post_id, '_gdhrdv_city', sanitize_text_field($data['city']));
        update_post_meta($post_id, '_gdhrdv_accept_terms', $data['accept_terms'] ? '1' : '0');
        update_post_meta($post_id, '_gdhrdv_email_sent', '0');

        // Save slots data
        if (! empty($data['slots']) && is_array($data['slots'])) {
            update_post_meta($post_id, '_gdhrdv_slots', $data['slots']);
        }

        return $post_id;
    }
// [/]

    /**
     * Ajoute le JavaScript pour renvoyer les e‑mails depuis la liste des rendez‑vous
     */
    public function addResendEmailScript()
    {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== self::POST_TYPE) {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $(document).on('click', '.gdhrdv-resend-email', function() {
                const button = $(this);
                const postId = button.data('post-id');
                
                if (!postId) return;
                
                // Désactive le bouton et affiche l'état de chargement
                button.prop('disabled', true).text('Envoi...');
                
                // Envoie la requête AJAX
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'gdhrdv_resend_email',
                        post_id: postId,
                        nonce: '<?php echo wp_create_nonce('gdhrdv_resend_email'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Met à jour la colonne "Email envoyé" pour indiquer le succès
                            const row = button.closest('tr');
                            row.find('td.column-email_sent').html('<span style="color:#46b450;font-weight:600;">✓ Oui</span>');
                            // Supprime le bouton
                            button.remove();
                            
                            // Recharge la page si un message doit être affiché
                            if (response.data && response.data.reload) {
                                window.location.reload();
                            }
                        } else {
                            // Restaure le bouton
                            button.prop('disabled', false).text('Envoyer');
                            
                            // Recharge la page pour afficher l'avis admin
                            window.location.reload();
                        }
                    },
                    error: function() {
                        // Restaure le bouton
                        button.prop('disabled', false).text('Envoyer');
                        // Recharge la page pour afficher l'avis admin
                        window.location.reload();
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Méthode statique pour renvoyer un email (appelée par AdminAjaxHandler)
     */
    public static function resendEmail($post_id)
    {
        $logger = new Logger();
        $emailService = new EmailTemplateService($logger);
        
        // Prépare les données du formulaire depuis les meta
        $formData = [
            'first_name' => get_post_meta($post_id, '_gdhrdv_first_name', true),
            'last_name' => get_post_meta($post_id, '_gdhrdv_last_name', true),
            'email' => get_post_meta($post_id, '_gdhrdv_email', true),
            'phone' => get_post_meta($post_id, '_gdhrdv_phone', true),
            'address' => get_post_meta($post_id, '_gdhrdv_address', true),
            'postal_code' => get_post_meta($post_id, '_gdhrdv_postal_code', true),
            'city' => get_post_meta($post_id, '_gdhrdv_city', true),
            'current_post_type' => get_post_meta($post_id, '_gdhrdv_current_post_type', true),
            'current_post_id' => get_post_meta($post_id, '_gdhrdv_current_post_id', true),
        ];
        
        // Envoi email de notification au destinataire
        $receiverSent = $emailService->sendOnAppointment($post_id, $formData);
        
        if ($receiverSent) {
            update_post_meta($post_id, '_gdhrdv_email_sent', '1');
            
            // Envoi confirmation au client si activé
            $confirmEnabled = get_option('gdhrdv_email_confirm_enabled', '0') === '1';
            if ($confirmEnabled) {
                $emailService->sendConfirmationToClient($post_id, $formData);
            }
        }
        
        return $receiverSent;
    }
    
    /**
     * Store admin notice in transient for display
     */
    private function setAdminNotice($type, $message) {
        set_transient('gdhrdv_admin_notice', [
            'type' => $type,
            'message' => $message
        ], 45); // Expires after 45 seconds
    }
    
    /**
     * Display admin notices from transient
     */
    public function displayAdminNotices() {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== self::POST_TYPE) {
            return;
        }
        
        $notice = get_transient('gdhrdv_admin_notice');
        if (!$notice) {
            return;
        }
        
        // Clear the notice right after displaying it
        delete_transient('gdhrdv_admin_notice');
        
        $type = isset($notice['type']) ? $notice['type'] : 'info';
        $message = isset($notice['message']) ? $notice['message'] : '';
        
        if (!$message) {
            return;
        }
        
        $class = 'notice notice-' . $type . ' is-dismissible';
        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
    }
    
    /**
     * Étend la recherche WordPress aux champs personnalisés pour les rendez-vous
     * 
     * @param string $where La clause WHERE de la requête SQL
     * @param WP_Query $wp_query L'objet WP_Query
     * @return string La clause WHERE modifiée
     */
    public function extendSearchToCustomFields($where, $wp_query) {
        global $wpdb;
        
        // Ne s'applique qu'aux recherches dans l'admin pour notre type de post
        if (!is_admin() || empty($wp_query->query_vars['s']) || $wp_query->query_vars['post_type'] !== self::POST_TYPE) {
            return $where;
        }
        
        $search_term = $wp_query->query_vars['s'];
        
        // Échapper le terme de recherche pour la requête SQL
        $search_term_like = '%' . $wpdb->esc_like($search_term) . '%';
        
        // Champs meta à inclure dans la recherche
        $meta_keys = [
            '_gdhrdv_phone',           // Téléphone du lead
            '_gdhrdv_email',           // Email du lead
            '_gdhrdv_destinataire_email', // Email du destinataire
            '_gdhrdv_destinataire_name',  // Nom du destinataire
            '_gdhrdv_first_name',       // Prénom du lead
            '_gdhrdv_last_name',        // Nom du lead
            '_gdhrdv_address',          // Adresse d'intervention
            '_gdhrdv_postal_code',      // Code postal
            '_gdhrdv_city',             // Ville
        ];
        
        // Construire la requête SQL pour rechercher dans les meta
        $meta_query = [];
        foreach ($meta_keys as $meta_key) {
            $meta_query[] = $wpdb->prepare(
                "(pm.meta_key = %s AND pm.meta_value LIKE %s)",
                $meta_key,
                $search_term_like
            );
        }
        
        // Joindre toutes les conditions avec OR
        $meta_query = implode(' OR ', $meta_query);
        
        // Ajouter la recherche dans les meta à la clause WHERE existante
        $where .= " OR ($wpdb->posts.post_type = '" . self::POST_TYPE . "' AND $wpdb->posts.ID IN (
            SELECT DISTINCT post_id FROM $wpdb->postmeta pm WHERE $meta_query
        ))";
        
        return $where;
    }
}
