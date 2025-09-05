<?php
/**
 * Multi-user Role Permissions System
 * 
 * @package YourHiddenTrip
 * @version 6.3
 */

if (!defined('ABSPATH')) exit;

/**
 * User Roles and Permissions Manager Class
 */
class YHT_User_Roles {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'), 19);
        add_action('wp_ajax_yht_save_role_permissions', array($this, 'ajax_save_role_permissions'));
        add_action('wp_ajax_yht_create_custom_role', array($this, 'ajax_create_custom_role'));
        add_action('wp_ajax_yht_delete_custom_role', array($this, 'ajax_delete_custom_role'));
        add_action('wp_ajax_yht_create_role_from_template', array($this, 'ajax_create_role_from_template'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Initialize custom roles and capabilities
        add_action('init', array($this, 'init_custom_roles'));
        add_action('admin_init', array($this, 'restrict_admin_access'));
    }

    /**
     * Add menu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'yht-dashboard',
            __('👥 Ruoli Utente', 'your-hidden-trip'),
            __('👥 Ruoli Utente', 'your-hidden-trip'),
            'manage_options',
            'yht-user-roles',
            array($this, 'render_page')
        );
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'your-hidden-trip_page_yht-user-roles') return;

        wp_localize_script('jquery', 'yhtUserRoles', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('yht_user_roles_nonce'),
            'strings' => array(
                'saved' => __('Permessi salvati con successo!', 'your-hidden-trip'),
                'created' => __('Ruolo creato con successo!', 'your-hidden-trip'),
                'deleted' => __('Ruolo eliminato!', 'your-hidden-trip'),
                'error' => __('Errore durante l\'operazione.', 'your-hidden-trip'),
                'confirm_delete' => __('Sei sicuro di voler eliminare questo ruolo? Gli utenti con questo ruolo perderanno i permessi.', 'your-hidden-trip'),
                'role_exists' => __('Un ruolo con questo nome esiste già.', 'your-hidden-trip')
            )
        ));
    }

    /**
     * Render the user roles page
     */
    public function render_page() {
        $roles = $this->get_yht_roles();
        $capabilities = $this->get_yht_capabilities();
        ?>
        <div class="wrap yht-user-roles-page">
            <div class="yht-header">
                <h1>👥 <?php _e('Gestione Ruoli e Permessi', 'your-hidden-trip'); ?></h1>
                <p class="description">
                    <?php _e('Configura i ruoli utente e definisci i permessi per l\'accesso alle funzionalità del plugin.', 'your-hidden-trip'); ?>
                </p>
            </div>

            <div class="roles-container">
                
                <!-- Create New Role Section -->
                <div class="roles-section create-role">
                    <div class="section-header">
                        <h2><?php _e('🆕 Crea Nuovo Ruolo', 'your-hidden-trip'); ?></h2>
                    </div>
                    
                    <div class="create-role-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_role_name"><?php _e('Nome Ruolo', 'your-hidden-trip'); ?></label>
                                <input type="text" id="new_role_name" class="regular-text" 
                                       placeholder="<?php _e('Es: Guida Turistica', 'your-hidden-trip'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="new_role_slug"><?php _e('Slug (automatico)', 'your-hidden-trip'); ?></label>
                                <input type="text" id="new_role_slug" class="regular-text" readonly>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_role_description"><?php _e('Descrizione', 'your-hidden-trip'); ?></label>
                            <textarea id="new_role_description" class="large-text" rows="3" 
                                      placeholder="<?php _e('Descrive i compiti e le responsabilità di questo ruolo...', 'your-hidden-trip'); ?>"></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button id="create_role" class="button button-primary">
                                <span class="dashicons dashicons-plus-alt"></span>
                                <?php _e('Crea Ruolo', 'your-hidden-trip'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Existing Roles Section -->
                <div class="roles-section existing-roles">
                    <div class="section-header">
                        <h2><?php _e('⚙️ Configura Permessi Ruoli', 'your-hidden-trip'); ?></h2>
                        <p><?php _e('Seleziona un ruolo per configurare i suoi permessi', 'your-hidden-trip'); ?></p>
                    </div>
                    
                    <div class="roles-tabs">
                        <?php $first = true; foreach ($roles as $role_slug => $role_data): ?>
                            <button class="role-tab <?php echo $first ? 'active' : ''; ?>" data-role="<?php echo esc_attr($role_slug); ?>">
                                <span class="role-icon"><?php echo $this->get_role_icon($role_slug); ?></span>
                                <span class="role-name"><?php echo esc_html($role_data['name']); ?></span>
                                <?php if ($this->is_custom_role($role_slug)): ?>
                                    <span class="custom-badge"><?php _e('Personalizzato', 'your-hidden-trip'); ?></span>
                                <?php endif; ?>
                            </button>
                            <?php $first = false; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="role-permissions">
                        <?php foreach ($roles as $role_slug => $role_data): ?>
                            <div class="role-permission-panel <?php echo $role_slug === array_key_first($roles) ? 'active' : ''; ?>" 
                                 data-role="<?php echo esc_attr($role_slug); ?>">
                                
                                <div class="role-info">
                                    <div class="role-header">
                                        <h3>
                                            <?php echo $this->get_role_icon($role_slug); ?>
                                            <?php echo esc_html($role_data['name']); ?>
                                        </h3>
                                        <?php if ($this->is_custom_role($role_slug)): ?>
                                            <button class="button button-secondary delete-role" 
                                                    data-role="<?php echo esc_attr($role_slug); ?>">
                                                <span class="dashicons dashicons-trash"></span>
                                                <?php _e('Elimina Ruolo', 'your-hidden-trip'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($role_data['description'])): ?>
                                        <p class="role-description"><?php echo esc_html($role_data['description']); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="role-stats">
                                        <span class="user-count">
                                            <?php 
                                            $user_count = $this->get_role_user_count($role_slug);
                                            printf(_n('%d utente', '%d utenti', $user_count, 'your-hidden-trip'), $user_count);
                                            ?>
                                        </span>
                                        <span class="permissions-count">
                                            <?php 
                                            $permissions_count = count(array_filter($role_data['capabilities']));
                                            printf(_n('%d permesso', '%d permessi', $permissions_count, 'your-hidden-trip'), $permissions_count);
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="permissions-grid">
                                    <?php foreach ($capabilities as $category => $caps): ?>
                                        <div class="capability-category">
                                            <h4><?php echo esc_html($category); ?></h4>
                                            <div class="capabilities-list">
                                                <?php foreach ($caps as $cap_slug => $cap_info): ?>
                                                    <label class="capability-item">
                                                        <input type="checkbox" 
                                                               name="permissions[<?php echo esc_attr($role_slug); ?>][<?php echo esc_attr($cap_slug); ?>]"
                                                               value="1"
                                                               <?php checked(isset($role_data['capabilities'][$cap_slug]) && $role_data['capabilities'][$cap_slug]); ?>
                                                               data-role="<?php echo esc_attr($role_slug); ?>"
                                                               data-capability="<?php echo esc_attr($cap_slug); ?>">
                                                        <div class="capability-info">
                                                            <span class="capability-name"><?php echo esc_html($cap_info['name']); ?></span>
                                                            <span class="capability-desc"><?php echo esc_html($cap_info['description']); ?></span>
                                                        </div>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="permission-actions">
                                    <button class="button button-primary save-permissions" data-role="<?php echo esc_attr($role_slug); ?>">
                                        <span class="dashicons dashicons-yes"></span>
                                        <?php _e('Salva Permessi', 'your-hidden-trip'); ?>
                                    </button>
                                    <button class="button select-all-permissions" data-role="<?php echo esc_attr($role_slug); ?>">
                                        <?php _e('Seleziona Tutto', 'your-hidden-trip'); ?>
                                    </button>
                                    <button class="button deselect-all-permissions" data-role="<?php echo esc_attr($role_slug); ?>">
                                        <?php _e('Deseleziona Tutto', 'your-hidden-trip'); ?>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- User Assignment Section -->
                <div class="roles-section user-assignment">
                    <div class="section-header">
                        <h2><?php _e('👤 Assegnazione Utenti', 'your-hidden-trip'); ?></h2>
                        <p><?php _e('Visualizza gli utenti assegnati a ciascun ruolo', 'your-hidden-trip'); ?></p>
                    </div>
                    
                    <div class="users-by-role">
                        <?php foreach ($roles as $role_slug => $role_data): ?>
                            <div class="role-users-group">
                                <h4>
                                    <?php echo $this->get_role_icon($role_slug); ?>
                                    <?php echo esc_html($role_data['name']); ?>
                                    <span class="user-count-badge"><?php echo $this->get_role_user_count($role_slug); ?></span>
                                </h4>
                                
                                <div class="users-list">
                                    <?php 
                                    $users = $this->get_users_by_role($role_slug);
                                    if (empty($users)): 
                                    ?>
                                        <p class="no-users"><?php _e('Nessun utente assegnato a questo ruolo.', 'your-hidden-trip'); ?></p>
                                    <?php else: ?>
                                        <div class="users-grid">
                                            <?php foreach ($users as $user): ?>
                                                <div class="user-card">
                                                    <div class="user-avatar">
                                                        <?php echo get_avatar($user->ID, 32); ?>
                                                    </div>
                                                    <div class="user-info">
                                                        <strong><?php echo esc_html($user->display_name); ?></strong>
                                                        <span><?php echo esc_html($user->user_email); ?></span>
                                                    </div>
                                                    <div class="user-actions">
                                                        <a href="<?php echo admin_url('user-edit.php?user_id=' . $user->ID); ?>" 
                                                           class="button button-small">
                                                            <?php _e('Modifica', 'your-hidden-trip'); ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Role Templates Section -->
                <div class="roles-section role-templates">
                    <div class="section-header">
                        <h2><?php _e('📋 Template Ruoli Predefiniti', 'your-hidden-trip'); ?></h2>
                        <p><?php _e('Crea rapidamente ruoli comuni con permessi pre-configurati', 'your-hidden-trip'); ?></p>
                    </div>
                    
                    <div class="role-templates-grid">
                        <div class="template-card">
                            <div class="template-icon">🗺️</div>
                            <h4><?php _e('Guida Turistica', 'your-hidden-trip'); ?></h4>
                            <p><?php _e('Può visualizzare prenotazioni, gestire clienti e accedere a informazioni sulle destinazioni.', 'your-hidden-trip'); ?></p>
                            <button class="button create-from-template" data-template="tour_guide">
                                <?php _e('Crea Ruolo', 'your-hidden-trip'); ?>
                            </button>
                        </div>
                        
                        <div class="template-card">
                            <div class="template-icon">📊</div>
                            <h4><?php _e('Manager', 'your-hidden-trip'); ?></h4>
                            <p><?php _e('Accesso completo a report, analisi e gestione delle prenotazioni.', 'your-hidden-trip'); ?></p>
                            <button class="button create-from-template" data-template="manager">
                                <?php _e('Crea Ruolo', 'your-hidden-trip'); ?>
                            </button>
                        </div>
                        
                        <div class="template-card">
                            <div class="template-icon">📞</div>
                            <h4><?php _e('Customer Service', 'your-hidden-trip'); ?></h4>
                            <p><?php _e('Gestione clienti, supporto e comunicazioni via email.', 'your-hidden-trip'); ?></p>
                            <button class="button create-from-template" data-template="customer_service">
                                <?php _e('Crea Ruolo', 'your-hidden-trip'); ?>
                            </button>
                        </div>
                        
                        <div class="template-card">
                            <div class="template-icon">💰</div>
                            <h4><?php _e('Contabile', 'your-hidden-trip'); ?></h4>
                            <p><?php _e('Accesso ai report finanziari e gestione dei pagamenti.', 'your-hidden-trip'); ?></p>
                            <button class="button create-from-template" data-template="accountant">
                                <?php _e('Crea Ruolo', 'your-hidden-trip'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .yht-user-roles-page {
                max-width: 1400px;
            }

            .yht-header {
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 1px solid #e1e1e1;
            }

            .roles-container {
                display: flex;
                flex-direction: column;
                gap: 30px;
            }

            .roles-section {
                background: white;
                border: 1px solid #e1e1e1;
                border-radius: 8px;
                padding: 25px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }

            .section-header {
                margin-bottom: 25px;
            }

            .section-header h2 {
                margin: 0 0 5px 0;
                font-size: 20px;
                color: #333;
            }

            .section-header p {
                margin: 0;
                color: #666;
                font-size: 14px;
            }

            .create-role-form {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 6px;
            }

            .form-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                margin-bottom: 20px;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-group label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
                color: #555;
            }

            .form-group input,
            .form-group textarea {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }

            .form-actions {
                display: flex;
                gap: 15px;
            }

            .roles-tabs {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-bottom: 25px;
                padding-bottom: 20px;
                border-bottom: 1px solid #e1e1e1;
            }

            .role-tab {
                display: flex;
                align-items: center;
                gap: 8px;
                background: #f8f9fa;
                border: 1px solid #ddd;
                padding: 12px 16px;
                border-radius: 6px;
                cursor: pointer;
                transition: all 0.3s;
            }

            .role-tab:hover {
                background: #e9ecef;
            }

            .role-tab.active {
                background: #0073aa;
                color: white;
                border-color: #0073aa;
            }

            .role-icon {
                font-size: 18px;
            }

            .role-name {
                font-weight: 600;
            }

            .custom-badge {
                font-size: 11px;
                padding: 2px 6px;
                background: #28a745;
                color: white;
                border-radius: 10px;
            }

            .role-permission-panel {
                display: none;
            }

            .role-permission-panel.active {
                display: block;
            }

            .role-info {
                margin-bottom: 25px;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 6px;
            }

            .role-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 10px;
            }

            .role-header h3 {
                margin: 0;
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 18px;
                color: #333;
            }

            .role-description {
                margin: 10px 0;
                color: #666;
                font-style: italic;
            }

            .role-stats {
                display: flex;
                gap: 20px;
                font-size: 14px;
                color: #666;
            }

            .permissions-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 25px;
                margin-bottom: 25px;
            }

            .capability-category {
                border: 1px solid #e1e1e1;
                border-radius: 6px;
                overflow: hidden;
            }

            .capability-category h4 {
                margin: 0;
                padding: 15px 20px;
                background: #f8f9fa;
                border-bottom: 1px solid #e1e1e1;
                font-size: 16px;
                color: #333;
            }

            .capabilities-list {
                padding: 15px 20px;
            }

            .capability-item {
                display: flex;
                align-items: flex-start;
                gap: 12px;
                margin-bottom: 15px;
                cursor: pointer;
                padding: 8px;
                border-radius: 4px;
                transition: background-color 0.2s;
            }

            .capability-item:hover {
                background: #f8f9fa;
            }

            .capability-item input[type="checkbox"] {
                margin: 3px 0 0 0;
            }

            .capability-info {
                display: flex;
                flex-direction: column;
            }

            .capability-name {
                font-weight: 600;
                color: #333;
                margin-bottom: 3px;
            }

            .capability-desc {
                font-size: 13px;
                color: #666;
            }

            .permission-actions {
                display: flex;
                gap: 15px;
                flex-wrap: wrap;
            }

            .users-by-role {
                display: flex;
                flex-direction: column;
                gap: 25px;
            }

            .role-users-group h4 {
                display: flex;
                align-items: center;
                gap: 10px;
                margin: 0 0 15px 0;
                font-size: 16px;
                color: #333;
            }

            .user-count-badge {
                background: #0073aa;
                color: white;
                font-size: 12px;
                padding: 2px 8px;
                border-radius: 10px;
                font-weight: normal;
            }

            .users-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 15px;
            }

            .user-card {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 15px;
                border: 1px solid #e1e1e1;
                border-radius: 6px;
                background: #f8f9fa;
            }

            .user-avatar img {
                border-radius: 50%;
            }

            .user-info {
                flex: 1;
                display: flex;
                flex-direction: column;
            }

            .user-info strong {
                color: #333;
                margin-bottom: 2px;
            }

            .user-info span {
                color: #666;
                font-size: 13px;
            }

            .no-users {
                color: #666;
                font-style: italic;
                text-align: center;
                padding: 20px;
            }

            .role-templates-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
            }

            .template-card {
                border: 1px solid #e1e1e1;
                border-radius: 6px;
                padding: 20px;
                text-align: center;
                transition: all 0.3s;
            }

            .template-card:hover {
                border-color: #0073aa;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }

            .template-icon {
                font-size: 32px;
                margin-bottom: 15px;
            }

            .template-card h4 {
                margin: 0 0 10px 0;
                font-size: 16px;
                color: #333;
            }

            .template-card p {
                margin: 0 0 15px 0;
                color: #666;
                font-size: 14px;
            }

            @media (max-width: 768px) {
                .form-row {
                    grid-template-columns: 1fr;
                }

                .permissions-grid {
                    grid-template-columns: 1fr;
                }

                .role-header {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 10px;
                }

                .permission-actions {
                    flex-direction: column;
                }

                .users-grid {
                    grid-template-columns: 1fr;
                }

                .role-templates-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <script>
            jQuery(document).ready(function($) {
                
                // Auto-generate slug from role name
                $('#new_role_name').on('input', function() {
                    var slug = $(this).val().toLowerCase()
                        .replace(/[^a-z0-9]+/g, '_')
                        .replace(/^_+|_+$/g, '');
                    $('#new_role_slug').val('yht_' + slug);
                });

                // Role tab switching
                $('.role-tab').click(function() {
                    var roleSlug = $(this).data('role');
                    
                    $('.role-tab').removeClass('active');
                    $(this).addClass('active');
                    
                    $('.role-permission-panel').removeClass('active');
                    $('.role-permission-panel[data-role="' + roleSlug + '"]').addClass('active');
                });

                // Create new role
                $('#create_role').click(function() {
                    var name = $('#new_role_name').val();
                    var slug = $('#new_role_slug').val();
                    var description = $('#new_role_description').val();
                    
                    if (!name || !slug) {
                        alert('Nome e slug sono obbligatori.');
                        return;
                    }
                    
                    $(this).prop('disabled', true).text('Creazione...');
                    
                    $.post(yhtUserRoles.ajaxurl, {
                        action: 'yht_create_custom_role',
                        nonce: yhtUserRoles.nonce,
                        name: name,
                        slug: slug,
                        description: description
                    }, function(response) {
                        $('#create_role').prop('disabled', false).text('Crea Ruolo');
                        
                        if (response.success) {
                            alert(yhtUserRoles.strings.created);
                            location.reload();
                        } else {
                            alert(response.data.message || yhtUserRoles.strings.error);
                        }
                    });
                });

                // Save permissions
                $('.save-permissions').click(function() {
                    var roleSlug = $(this).data('role');
                    var $panel = $('.role-permission-panel[data-role="' + roleSlug + '"]');
                    var permissions = {};
                    
                    $panel.find('input[type="checkbox"]').each(function() {
                        var capability = $(this).data('capability');
                        permissions[capability] = $(this).is(':checked');
                    });
                    
                    $(this).prop('disabled', true).text('Salvataggio...');
                    
                    $.post(yhtUserRoles.ajaxurl, {
                        action: 'yht_save_role_permissions',
                        nonce: yhtUserRoles.nonce,
                        role: roleSlug,
                        permissions: permissions
                    }, function(response) {
                        $('.save-permissions[data-role="' + roleSlug + '"]')
                            .prop('disabled', false)
                            .text('Salva Permessi');
                        
                        if (response.success) {
                            alert(yhtUserRoles.strings.saved);
                        } else {
                            alert(yhtUserRoles.strings.error + ' ' + (response.data.message || ''));
                        }
                    });
                });

                // Select/Deselect all permissions
                $('.select-all-permissions').click(function() {
                    var roleSlug = $(this).data('role');
                    $('.role-permission-panel[data-role="' + roleSlug + '"] input[type="checkbox"]').prop('checked', true);
                });

                $('.deselect-all-permissions').click(function() {
                    var roleSlug = $(this).data('role');
                    $('.role-permission-panel[data-role="' + roleSlug + '"] input[type="checkbox"]').prop('checked', false);
                });

                // Delete custom role
                $('.delete-role').click(function() {
                    if (!confirm(yhtUserRoles.strings.confirm_delete)) return;
                    
                    var roleSlug = $(this).data('role');
                    
                    $.post(yhtUserRoles.ajaxurl, {
                        action: 'yht_delete_custom_role',
                        nonce: yhtUserRoles.nonce,
                        role: roleSlug
                    }, function(response) {
                        if (response.success) {
                            alert(yhtUserRoles.strings.deleted);
                            location.reload();
                        } else {
                            alert(yhtUserRoles.strings.error + ' ' + (response.data.message || ''));
                        }
                    });
                });

                // Create from template
                $('.create-from-template').click(function() {
                    var template = $(this).data('template');
                    
                    $(this).prop('disabled', true).text('Creazione...');
                    
                    $.post(yhtUserRoles.ajaxurl, {
                        action: 'yht_create_role_from_template',
                        nonce: yhtUserRoles.nonce,
                        template: template
                    }, function(response) {
                        $('.create-from-template[data-template="' + template + '"]')
                            .prop('disabled', false)
                            .text('Crea Ruolo');
                        
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert(response.data.message || yhtUserRoles.strings.error);
                        }
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * Get YHT roles
     */
    private function get_yht_roles() {
        global $wp_roles;
        
        $yht_roles = array();
        $custom_roles = get_option('yht_custom_roles', array());
        
        // Add default WordPress roles that can access YHT
        $allowed_roles = array('administrator', 'editor');
        
        foreach ($allowed_roles as $role_slug) {
            if (isset($wp_roles->roles[$role_slug])) {
                $role = $wp_roles->roles[$role_slug];
                $yht_roles[$role_slug] = array(
                    'name' => $role['name'],
                    'capabilities' => $this->filter_yht_capabilities($role['capabilities']),
                    'description' => ''
                );
            }
        }
        
        // Add custom YHT roles
        foreach ($custom_roles as $role_slug => $role_data) {
            if (isset($wp_roles->roles[$role_slug])) {
                $wp_role = $wp_roles->roles[$role_slug];
                $yht_roles[$role_slug] = array(
                    'name' => $role_data['name'],
                    'capabilities' => $this->filter_yht_capabilities($wp_role['capabilities']),
                    'description' => $role_data['description'] ?? ''
                );
            }
        }
        
        return $yht_roles;
    }

    /**
     * Get YHT capabilities
     */
    private function get_yht_capabilities() {
        return array(
            __('Dashboard', 'your-hidden-trip') => array(
                'yht_view_dashboard' => array(
                    'name' => __('Visualizza Dashboard', 'your-hidden-trip'),
                    'description' => __('Accesso alla dashboard principale del plugin', 'your-hidden-trip')
                ),
                'yht_view_analytics' => array(
                    'name' => __('Visualizza Analytics', 'your-hidden-trip'),
                    'description' => __('Accesso alle statistiche e metriche', 'your-hidden-trip')
                )
            ),
            __('Prenotazioni', 'your-hidden-trip') => array(
                'yht_view_bookings' => array(
                    'name' => __('Visualizza Prenotazioni', 'your-hidden-trip'),
                    'description' => __('Visualizzare elenco delle prenotazioni', 'your-hidden-trip')
                ),
                'yht_create_bookings' => array(
                    'name' => __('Crea Prenotazioni', 'your-hidden-trip'),
                    'description' => __('Creare nuove prenotazioni', 'your-hidden-trip')
                ),
                'yht_edit_bookings' => array(
                    'name' => __('Modifica Prenotazioni', 'your-hidden-trip'),
                    'description' => __('Modificare prenotazioni esistenti', 'your-hidden-trip')
                ),
                'yht_delete_bookings' => array(
                    'name' => __('Elimina Prenotazioni', 'your-hidden-trip'),
                    'description' => __('Eliminare prenotazioni', 'your-hidden-trip')
                ),
                'yht_manage_booking_status' => array(
                    'name' => __('Gestisci Stati', 'your-hidden-trip'),
                    'description' => __('Cambiare stato delle prenotazioni', 'your-hidden-trip')
                )
            ),
            __('Clienti', 'your-hidden-trip') => array(
                'yht_view_customers' => array(
                    'name' => __('Visualizza Clienti', 'your-hidden-trip'),
                    'description' => __('Accesso all\'elenco clienti', 'your-hidden-trip')
                ),
                'yht_edit_customers' => array(
                    'name' => __('Modifica Clienti', 'your-hidden-trip'),
                    'description' => __('Modificare informazioni clienti', 'your-hidden-trip')
                ),
                'yht_delete_customers' => array(
                    'name' => __('Elimina Clienti', 'your-hidden-trip'),
                    'description' => __('Eliminare clienti dal sistema', 'your-hidden-trip')
                ),
                'yht_contact_customers' => array(
                    'name' => __('Contatta Clienti', 'your-hidden-trip'),
                    'description' => __('Inviare email ai clienti', 'your-hidden-trip')
                )
            ),
            __('Report', 'your-hidden-trip') => array(
                'yht_view_reports' => array(
                    'name' => __('Visualizza Report', 'your-hidden-trip'),
                    'description' => __('Accesso ai report base', 'your-hidden-trip')
                ),
                'yht_advanced_reports' => array(
                    'name' => __('Report Avanzati', 'your-hidden-trip'),
                    'description' => __('Generare e esportare report avanzati', 'your-hidden-trip')
                ),
                'yht_financial_reports' => array(
                    'name' => __('Report Finanziari', 'your-hidden-trip'),
                    'description' => __('Accesso ai dati finanziari', 'your-hidden-trip')
                )
            ),
            __('Configurazione', 'your-hidden-trip') => array(
                'yht_manage_settings' => array(
                    'name' => __('Gestisci Impostazioni', 'your-hidden-trip'),
                    'description' => __('Modificare configurazione del plugin', 'your-hidden-trip')
                ),
                'yht_manage_templates' => array(
                    'name' => __('Gestisci Template', 'your-hidden-trip'),
                    'description' => __('Modificare template email', 'your-hidden-trip')
                ),
                'yht_manage_integrations' => array(
                    'name' => __('Gestisci Integrazioni', 'your-hidden-trip'),
                    'description' => __('Configurare API e integrazioni', 'your-hidden-trip')
                ),
                'yht_manage_backups' => array(
                    'name' => __('Gestisci Backup', 'your-hidden-trip'),
                    'description' => __('Creare e ripristinare backup', 'your-hidden-trip')
                ),
                'yht_system_health' => array(
                    'name' => __('Monitoraggio Sistema', 'your-hidden-trip'),
                    'description' => __('Accesso al controllo salute sistema', 'your-hidden-trip')
                )
            )
        );
    }

    /**
     * Filter YHT capabilities
     */
    private function filter_yht_capabilities($capabilities) {
        $yht_caps = $this->get_yht_capabilities();
        $filtered = array();
        
        foreach ($yht_caps as $category => $caps) {
            foreach ($caps as $cap_slug => $cap_info) {
                $filtered[$cap_slug] = isset($capabilities[$cap_slug]) && $capabilities[$cap_slug];
            }
        }
        
        return $filtered;
    }

    /**
     * Get role icon
     */
    private function get_role_icon($role_slug) {
        $icons = array(
            'administrator' => '👑',
            'editor' => '✏️',
            'yht_tour_guide' => '🗺️',
            'yht_manager' => '📊',
            'yht_customer_service' => '📞',
            'yht_accountant' => '💰'
        );
        
        return $icons[$role_slug] ?? '👤';
    }

    /**
     * Check if custom role
     */
    private function is_custom_role($role_slug) {
        $custom_roles = get_option('yht_custom_roles', array());
        return isset($custom_roles[$role_slug]);
    }

    /**
     * Get role user count
     */
    private function get_role_user_count($role_slug) {
        $users = get_users(array('role' => $role_slug, 'fields' => 'ID'));
        return count($users);
    }

    /**
     * Get users by role
     */
    private function get_users_by_role($role_slug) {
        return get_users(array(
            'role' => $role_slug,
            'number' => 10, // Limit for display
            'fields' => array('ID', 'display_name', 'user_email')
        ));
    }

    /**
     * Initialize custom roles
     */
    public function init_custom_roles() {
        $custom_roles = get_option('yht_custom_roles', array());
        
        foreach ($custom_roles as $role_slug => $role_data) {
            if (!get_role($role_slug)) {
                add_role($role_slug, $role_data['name'], $role_data['capabilities'] ?? array());
            }
        }
        
        // Add YHT capabilities to administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $yht_capabilities = $this->get_yht_capabilities();
            foreach ($yht_capabilities as $category => $caps) {
                foreach ($caps as $cap_slug => $cap_info) {
                    $admin_role->add_cap($cap_slug);
                }
            }
        }
    }

    /**
     * Restrict admin access
     */
    public function restrict_admin_access() {
        if (is_admin() && !current_user_can('yht_view_dashboard')) {
            // Check if user is trying to access YHT pages
            $screen = get_current_screen();
            if ($screen && strpos($screen->id, 'yht-') === 0) {
                wp_die(__('Non hai i permessi per accedere a questa pagina.', 'your-hidden-trip'));
            }
        }
    }

    /**
     * AJAX: Save role permissions
     */
    public function ajax_save_role_permissions() {
        check_ajax_referer('yht_user_roles_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permessi insufficienti.', 'your-hidden-trip'));
        }

        $role_slug = sanitize_key($_POST['role']);
        $permissions = array_map('sanitize_key', $_POST['permissions']);

        $role = get_role($role_slug);
        if (!$role) {
            wp_send_json_error(array('message' => __('Ruolo non trovato.', 'your-hidden-trip')));
        }

        // Remove all YHT capabilities first
        $yht_capabilities = $this->get_yht_capabilities();
        foreach ($yht_capabilities as $category => $caps) {
            foreach ($caps as $cap_slug => $cap_info) {
                $role->remove_cap($cap_slug);
            }
        }

        // Add selected capabilities
        foreach ($permissions as $cap_slug => $enabled) {
            if ($enabled) {
                $role->add_cap($cap_slug);
            }
        }

        wp_send_json_success(array(
            'message' => __('Permessi aggiornati con successo!', 'your-hidden-trip')
        ));
    }

    /**
     * AJAX: Create custom role
     */
    public function ajax_create_custom_role() {
        check_ajax_referer('yht_user_roles_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permessi insufficienti.', 'your-hidden-trip'));
        }

        $name = sanitize_text_field($_POST['name']);
        $slug = sanitize_key($_POST['slug']);
        $description = sanitize_textarea_field($_POST['description']);

        if (get_role($slug)) {
            wp_send_json_error(array('message' => __('Un ruolo con questo slug esiste già.', 'your-hidden-trip')));
        }

        // Create role with basic capabilities
        $basic_caps = array(
            'read' => true,
            'yht_view_dashboard' => true
        );

        add_role($slug, $name, $basic_caps);

        // Save to custom roles option
        $custom_roles = get_option('yht_custom_roles', array());
        $custom_roles[$slug] = array(
            'name' => $name,
            'description' => $description,
            'capabilities' => $basic_caps
        );
        update_option('yht_custom_roles', $custom_roles);

        wp_send_json_success(array(
            'message' => sprintf(__('Ruolo "%s" creato con successo!', 'your-hidden-trip'), $name)
        ));
    }

    /**
     * AJAX: Delete custom role
     */
    public function ajax_delete_custom_role() {
        check_ajax_referer('yht_user_roles_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permessi insufficienti.', 'your-hidden-trip'));
        }

        $role_slug = sanitize_key($_POST['role']);

        if (!$this->is_custom_role($role_slug)) {
            wp_send_json_error(array('message' => __('Puoi eliminare solo ruoli personalizzati.', 'your-hidden-trip')));
        }

        // Remove role from WordPress
        remove_role($role_slug);

        // Remove from custom roles option
        $custom_roles = get_option('yht_custom_roles', array());
        unset($custom_roles[$role_slug]);
        update_option('yht_custom_roles', $custom_roles);

        wp_send_json_success(array(
            'message' => __('Ruolo eliminato con successo!', 'your-hidden-trip')
        ));
    }

    /**
     * AJAX: Create role from template
     */
    public function ajax_create_role_from_template() {
        check_ajax_referer('yht_user_roles_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permessi insufficienti.', 'your-hidden-trip'));
        }

        $template = sanitize_key($_POST['template']);
        $template_data = $this->get_role_template($template);

        if (!$template_data) {
            wp_send_json_error(array('message' => __('Template non trovato.', 'your-hidden-trip')));
        }

        $role_slug = 'yht_' . $template;
        
        // Check if role already exists
        if (get_role($role_slug)) {
            wp_send_json_error(array(
                'message' => sprintf(__('Il ruolo "%s" esiste già.', 'your-hidden-trip'), $template_data['name'])
            ));
        }

        // Create role with template capabilities
        add_role($role_slug, $template_data['name'], $template_data['capabilities']);

        // Save to custom roles option
        $custom_roles = get_option('yht_custom_roles', array());
        $custom_roles[$role_slug] = array(
            'name' => $template_data['name'],
            'description' => $template_data['description'],
            'capabilities' => $template_data['capabilities']
        );
        update_option('yht_custom_roles', $custom_roles);

        wp_send_json_success(array(
            'message' => sprintf(__('Ruolo "%s" creato con successo dal template!', 'your-hidden-trip'), $template_data['name'])
        ));
    }

    /**
     * Get role template data
     */
    private function get_role_template($template) {
        $templates = array(
            'tour_guide' => array(
                'name' => __('Guida Turistica', 'your-hidden-trip'),
                'description' => __('Può visualizzare prenotazioni, gestire clienti e accedere a informazioni sulle destinazioni.', 'your-hidden-trip'),
                'capabilities' => array(
                    'read' => true,
                    'yht_view_dashboard' => true,
                    'yht_view_bookings' => true,
                    'yht_view_customers' => true,
                    'yht_edit_customers' => true,
                    'yht_contact_customers' => true,
                    'yht_view_reports' => true
                )
            ),
            'manager' => array(
                'name' => __('Manager', 'your-hidden-trip'),
                'description' => __('Accesso completo a report, analisi e gestione delle prenotazioni.', 'your-hidden-trip'),
                'capabilities' => array(
                    'read' => true,
                    'yht_view_dashboard' => true,
                    'yht_view_analytics' => true,
                    'yht_view_bookings' => true,
                    'yht_create_bookings' => true,
                    'yht_edit_bookings' => true,
                    'yht_manage_booking_status' => true,
                    'yht_view_customers' => true,
                    'yht_edit_customers' => true,
                    'yht_contact_customers' => true,
                    'yht_view_reports' => true,
                    'yht_advanced_reports' => true,
                    'yht_financial_reports' => true,
                    'yht_manage_templates' => true
                )
            ),
            'customer_service' => array(
                'name' => __('Customer Service', 'your-hidden-trip'),
                'description' => __('Gestione clienti, supporto e comunicazioni via email.', 'your-hidden-trip'),
                'capabilities' => array(
                    'read' => true,
                    'yht_view_dashboard' => true,
                    'yht_view_bookings' => true,
                    'yht_create_bookings' => true,
                    'yht_edit_bookings' => true,
                    'yht_manage_booking_status' => true,
                    'yht_view_customers' => true,
                    'yht_edit_customers' => true,
                    'yht_contact_customers' => true,
                    'yht_view_reports' => true
                )
            ),
            'accountant' => array(
                'name' => __('Contabile', 'your-hidden-trip'),
                'description' => __('Accesso ai report finanziari e gestione dei pagamenti.', 'your-hidden-trip'),
                'capabilities' => array(
                    'read' => true,
                    'yht_view_dashboard' => true,
                    'yht_view_bookings' => true,
                    'yht_view_customers' => true,
                    'yht_view_reports' => true,
                    'yht_advanced_reports' => true,
                    'yht_financial_reports' => true
                )
            )
        );

        return isset($templates[$template]) ? $templates[$template] : null;
    }
}