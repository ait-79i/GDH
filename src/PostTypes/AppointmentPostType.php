<?php
namespace GDH\PostTypes;

class AppointmentPostType
{
    const POST_TYPE = 'gdh_appointment';

    public function __construct()
    {
        add_action('init', [$this, 'register']);
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'saveMetaData'], 10, 2);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'setCustomColumns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'renderCustomColumns'], 10, 2);
    }

    public function register()
    {
        $labels = [
            'name'                  => 'Rendez-vous',
            'singular_name'         => 'Rendez-vous',
            'menu_name'             => 'Rendez-vous',
            'name_admin_bar'        => 'Rendez-vous',
            'add_new'               => 'Ajouter',
            'add_new_item'          => 'Ajouter un rendez-vous',
            'new_item'              => 'Nouveau rendez-vous',
            'edit_item'             => 'Modifier le rendez-vous',
            'view_item'             => 'Voir le rendez-vous',
            'all_items'             => 'Tous les rendez-vous',
            'search_items'          => 'Rechercher',
            'not_found'             => 'Aucun rendez-vous trouvé',
            'not_found_in_trash'    => 'Aucun rendez-vous dans la corbeille'
        ];

        $args = [
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_icon'           => 'dashicons-calendar-alt',
            'menu_position'       => 25,
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => ['title'],
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
            'show_in_rest'        => false,
            'capabilities'        => [
                'create_posts'    => false, // Removes "Add New" button
            ],
            'map_meta_cap'        => true,
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    public function addMetaBoxes()
    {
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

        $first_name = get_post_meta($post->ID, '_gdh_first_name', true);
        $last_name = get_post_meta($post->ID, '_gdh_last_name', true);
        $email = get_post_meta($post->ID, '_gdh_email', true);
        $phone = get_post_meta($post->ID, '_gdh_phone', true);
        $accept_terms = get_post_meta($post->ID, '_gdh_accept_terms', true);

        ?>
        <style>
            .gdh-meta-field { margin-bottom: 15px; }
            .gdh-meta-field label { display: block; font-weight: bold; margin-bottom: 5px; }
            .gdh-meta-field input[type="text"],
            .gdh-meta-field input[type="email"],
            .gdh-meta-field input[type="tel"] { width: 100%; padding: 8px; }
            .gdh-meta-field-group { display: flex; gap: 15px; }
            .gdh-meta-field-group .gdh-meta-field { flex: 1; }
        </style>
        
        <div class="gdh-meta-field-group">
            <div class="gdh-meta-field">
                <label for="gdh_first_name">Prénom</label>
                <input type="text" id="gdh_first_name" name="gdh_first_name" value="<?php echo esc_attr($first_name); ?>" readonly>
            </div>
            <div class="gdh-meta-field">
                <label for="gdh_last_name">Nom</label>
                <input type="text" id="gdh_last_name" name="gdh_last_name" value="<?php echo esc_attr($last_name); ?>" readonly>
            </div>
        </div>

        <div class="gdh-meta-field-group">
            <div class="gdh-meta-field">
                <label for="gdh_email">Email</label>
                <input type="email" id="gdh_email" name="gdh_email" value="<?php echo esc_attr($email); ?>" readonly>
            </div>
            <div class="gdh-meta-field">
                <label for="gdh_phone">Téléphone</label>
                <input type="tel" id="gdh_phone" name="gdh_phone" value="<?php echo esc_attr($phone); ?>" readonly>
            </div>
        </div>

        <div class="gdh-meta-field">
            <label>
                <input type="checkbox" name="gdh_accept_terms" value="1" <?php checked($accept_terms, '1'); ?> disabled>
                Conditions générales acceptées
            </label>
        </div>
        <?php
    }

    public function renderSlotsMetaBox($post)
    {
        $slots = get_post_meta($post->ID, '_gdh_slots', true);
        
        if (empty($slots)) {
            echo '<p>Aucun créneau disponible.</p>';
            return;
        }

        ?>
        <style>
            .gdh-slots-list { list-style: none; padding: 0; margin: 0; }
            .gdh-slot-item { 
                padding: 12px; 
                margin-bottom: 10px; 
                background: #f9f9f9; 
                border-left: 4px solid #2271b1;
                border-radius: 4px;
            }
            .gdh-slot-item strong { display: block; margin-bottom: 5px; color: #2271b1; }
            .gdh-slot-times { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
            .gdh-slot-time-badge { 
                display: inline-block; 
                padding: 4px 10px; 
                background: #2271b1; 
                color: white; 
                border-radius: 3px; 
                font-size: 12px;
            }
            .gdh-slot-all-day { 
                display: inline-block; 
                padding: 4px 10px; 
                background: #00a32a; 
                color: white; 
                border-radius: 3px; 
                font-size: 12px;
                font-weight: bold;
            }
        </style>
        
        <ul class="gdh-slots-list">
            <?php foreach ($slots as $index => $slot): ?>
                <li class="gdh-slot-item">
                    <strong>Créneau #<?php echo ($index + 1); ?></strong>
                    <div>
                        <span class="dashicons dashicons-calendar-alt"></span>
                        Date: <?php echo esc_html(date_i18n('l j F Y', strtotime($slot['date']))); ?>
                    </div>
                    <div class="gdh-slot-times">
                        <?php if (in_array('all-day', $slot['times'])): ?>
                            <span class="gdh-slot-all-day">Toute la journée</span>
                        <?php else: ?>
                            <?php foreach ($slot['times'] as $time): ?>
                                <span class="gdh-slot-time-badge"><?php echo esc_html($time); ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
    }

    public function renderAddressMetaBox($post)
    {
        $address = get_post_meta($post->ID, '_gdh_address', true);
        $postal_code = get_post_meta($post->ID, '_gdh_postal_code', true);
        $city = get_post_meta($post->ID, '_gdh_city', true);

        ?>
        <style>
            .gdh-address-field { margin-bottom: 12px; }
            .gdh-address-field label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 12px; }
            .gdh-address-field input { width: 100%; padding: 6px; }
        </style>

        <div class="gdh-address-field">
            <label for="gdh_address">Adresse</label>
            <input type="text" id="gdh_address" name="gdh_address" value="<?php echo esc_attr($address); ?>" readonly>
        </div>

        <div class="gdh-address-field">
            <label for="gdh_postal_code">Code postal</label>
            <input type="text" id="gdh_postal_code" name="gdh_postal_code" value="<?php echo esc_attr($postal_code); ?>" readonly>
        </div>

        <div class="gdh-address-field">
            <label for="gdh_city">Ville</label>
            <input type="text" id="gdh_city" name="gdh_city" value="<?php echo esc_attr($city); ?>" readonly>
        </div>
        <?php
    }

    public function saveMetaData($post_id, $post)
    {
        // Verify nonce
        if (!isset($_POST['gdh_appointment_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['gdh_appointment_meta_box_nonce'], 'gdh_appointment_meta_box')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save fields (though they are readonly, this is for programmatic updates)
        $fields = [
            'gdh_first_name' => '_gdh_first_name',
            'gdh_last_name' => '_gdh_last_name',
            'gdh_email' => '_gdh_email',
            'gdh_phone' => '_gdh_phone',
            'gdh_address' => '_gdh_address',
            'gdh_postal_code' => '_gdh_postal_code',
            'gdh_city' => '_gdh_city',
            'gdh_accept_terms' => '_gdh_accept_terms',
        ];

        foreach ($fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field]));
            }
        }
    }

    public function setCustomColumns($columns)
    {
        $new_columns = [];
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = 'Référence';
        $new_columns['client'] = 'Client';
        $new_columns['email'] = 'Email';
        $new_columns['phone'] = 'Téléphone';
        $new_columns['address'] = 'Adresse';
        $new_columns['availability'] = 'Disponibilités';
        $new_columns['date'] = 'Date de soumission';
        
        return $new_columns;
    }

    public function renderCustomColumns($column, $post_id)
    {
        switch ($column) {
            case 'client':
                $first_name = get_post_meta($post_id, '_gdh_first_name', true);
                $last_name = get_post_meta($post_id, '_gdh_last_name', true);
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
                $city = get_post_meta($post_id, '_gdh_city', true);
                echo esc_html($address . ', ' . $city);
                break;

            case 'availability':
                $slots = get_post_meta($post_id, '_gdh_slots', true);
                
                if (empty($slots) || !is_array($slots)) {
                    echo '<span style="color: #999;">Aucun créneau</span>';
                    break;
                }
                
                echo '<div style="font-size: 12px; line-height: 1.6;">';
                
                foreach ($slots as $index => $slot) {
                    if (!isset($slot['date']) || !isset($slot['times'])) {
                        continue;
                    }
                    
                    // Format date
                    $date = date_i18n('d/m/Y', strtotime($slot['date']));
                    $dayName = date_i18n('D', strtotime($slot['date']));
                    
                    // Priority badge
                    $priority = $index + 1;
                    $priorityColors = [
                        1 => '#2271b1',  // Blue for primary
                        2 => '#d63638',  // Orange for alternative
                        3 => '#50575e'   // Gray for backup
                    ];
                    $priorityLabels = [
                        1 => '1ère',
                        2 => '2ème',
                        3 => '3ème'
                    ];
                    $color = $priorityColors[$priority] ?? '#50575e';
                    $label = $priorityLabels[$priority] ?? $priority . 'ème';
                    
                    echo '<div style="margin-bottom: 8px; padding: 6px 8px; background: #f6f7f7; border-radius: 4px; border-left: 3px solid ' . esc_attr($color) . ';">';
                    
                    // Priority and date
                    echo '<div style="margin-bottom: 3px;">';
                    echo '<strong style="color: ' . esc_attr($color) . '; font-size: 11px;">' . esc_html($label) . ' choix:</strong> ';
                    echo '<span style="color: #2c3338;">' . esc_html($dayName . ' ' . $date) . '</span>';
                    echo '</div>';
                    
                    // Times
                    if (in_array('all-day', $slot['times'])) {
                        echo '<div style="display: inline-block; padding: 2px 6px; background: #00a32a; color: white; border-radius: 3px; font-size: 10px; font-weight: 600;">';
                        echo 'TOUTE LA JOURNÉE';
                        echo '</div>';
                    } else {
                        echo '<div style="display: flex; flex-wrap: wrap; gap: 4px; margin-top: 4px;">';
                        foreach ($slot['times'] as $time) {
                            echo '<span style="display: inline-block; padding: 2px 6px; background: white; border: 1px solid #dcdcde; border-radius: 3px; font-size: 10px; color: #50575e;">';
                            echo esc_html($time);
                            echo '</span>';
                        }
                        echo '</div>';
                    }
                    
                    echo '</div>';
                }
                
                echo '</div>';
                break;
        }
    }

    /**
     * Create a new appointment from form data
     */
    public static function createAppointment($data)
    {
        // Create post title
        $title = sprintf(
            'RDV - %s %s - %s',
            sanitize_text_field($data['first_name']),
            sanitize_text_field($data['last_name']),
            date('Y-m-d H:i:s')
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
        
        // Save slots data
        if (!empty($data['slots']) && is_array($data['slots'])) {
            update_post_meta($post_id, '_gdh_slots', $data['slots']);
        }

        return $post_id;
    }
}
