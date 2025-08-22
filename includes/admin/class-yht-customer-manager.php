<?php
/**
 * YHT Customer Management - Handle customer data and communication
 * 
 * @package YourHiddenTrip
 */

if (!defined('ABSPATH')) exit;

class YHT_Customer_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_yht_customer_search', array($this, 'ajax_customer_search'));
        add_action('wp_ajax_yht_customer_details', array($this, 'ajax_customer_details'));
        add_action('wp_ajax_yht_customer_update_notes', array($this, 'ajax_update_customer_notes'));
        add_action('wp_ajax_yht_send_customer_email', array($this, 'ajax_send_customer_email'));
    }
    
    /**
     * Render customer management page
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">
            <h1>👥 Gestione Clienti</h1>
            
            <div class="yht-customer-manager">
                <!-- Search and Filters -->
                <div class="yht-customer-header">
                    <div class="yht-search-box">
                        <input type="text" id="customer-search" placeholder="Cerca per nome o email..." />
                        <button type="button" id="search-customers" class="button">🔍 Cerca</button>
                    </div>
                    
                    <div class="yht-filters">
                        <select id="customer-status-filter">
                            <option value="">Tutti gli stati</option>
                            <option value="active">Clienti attivi</option>
                            <option value="returning">Clienti abituali</option>
                            <option value="new">Nuovi clienti</option>
                        </select>
                        
                        <select id="customer-date-filter">
                            <option value="">Tutti i periodi</option>
                            <option value="this_month">Questo mese</option>
                            <option value="last_month">Mese scorso</option>
                            <option value="last_3_months">Ultimi 3 mesi</option>
                        </select>
                        
                        <button type="button" id="export-customers" class="button">📥 Esporta CSV</button>
                    </div>
                </div>
                
                <!-- Customer List -->
                <div class="yht-customer-list-container">
                    <div id="customers-loading" class="yht-loading">
                        Caricamento clienti...
                    </div>
                    
                    <table id="customers-table" class="wp-list-table widefat fixed striped" style="display:none;">
                        <thead>
                            <tr>
                                <th class="column-avatar">Avatar</th>
                                <th class="column-customer">Cliente</th>
                                <th class="column-bookings">Prenotazioni</th>
                                <th class="column-total">Totale Speso</th>
                                <th class="column-last-booking">Ultima Prenotazione</th>
                                <th class="column-status">Stato</th>
                                <th class="column-actions">Azioni</th>
                            </tr>
                        </thead>
                        <tbody id="customers-tbody">
                        </tbody>
                    </table>
                    
                    <div id="customers-pagination" class="tablenav bottom" style="display:none;">
                        <div class="tablenav-pages">
                            <span class="pagination-links">
                                <button class="button" id="prev-page" disabled>&laquo; Precedente</button>
                                <span id="page-info">Pagina 1 di 1</span>
                                <button class="button" id="next-page" disabled>Successiva &raquo;</button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Customer Details Modal -->
        <div id="customer-details-modal" class="yht-modal" style="display:none;">
            <div class="yht-modal-content">
                <div class="yht-modal-header">
                    <h2 id="customer-modal-title">Dettagli Cliente</h2>
                    <span class="yht-modal-close">&times;</span>
                </div>
                
                <div class="yht-modal-body">
                    <div class="yht-customer-details">
                        <!-- Customer Info -->
                        <div class="yht-customer-info">
                            <div class="customer-avatar">
                                <img id="customer-avatar" src="" alt="Avatar" />
                            </div>
                            <div class="customer-basic-info">
                                <h3 id="customer-name">--</h3>
                                <p id="customer-email">--</p>
                                <p id="customer-phone">--</p>
                                <div class="customer-stats">
                                    <span class="stat-item">
                                        <strong id="customer-total-bookings">--</strong> prenotazioni
                                    </span>
                                    <span class="stat-item">
                                        <strong id="customer-total-spent">--</strong> speso
                                    </span>
                                    <span class="stat-item">
                                        Registrato: <strong id="customer-registration-date">--</strong>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tabs -->
                        <div class="yht-customer-tabs">
                            <div class="yht-tab-nav">
                                <button class="yht-tab-button active" data-tab="bookings">📋 Prenotazioni</button>
                                <button class="yht-tab-button" data-tab="notes">📝 Note</button>
                                <button class="yht-tab-button" data-tab="communication">📧 Comunicazioni</button>
                            </div>
                            
                            <div class="yht-tab-content">
                                <!-- Bookings Tab -->
                                <div id="tab-bookings" class="yht-tab-panel active">
                                    <div id="customer-bookings-list">
                                        <div class="yht-loading">Caricamento prenotazioni...</div>
                                    </div>
                                </div>
                                
                                <!-- Notes Tab -->
                                <div id="tab-notes" class="yht-tab-panel">
                                    <div class="yht-customer-notes">
                                        <textarea id="customer-notes" rows="6" placeholder="Aggiungi note sul cliente..."></textarea>
                                        <button type="button" id="save-customer-notes" class="button button-primary">💾 Salva Note</button>
                                    </div>
                                </div>
                                
                                <!-- Communication Tab -->
                                <div id="tab-communication" class="yht-tab-panel">
                                    <div class="yht-communication-panel">
                                        <div class="yht-send-email">
                                            <h4>📧 Invia Email</h4>
                                            <input type="text" id="email-subject" placeholder="Oggetto email..." />
                                            <textarea id="email-message" rows="4" placeholder="Messaggio..."></textarea>
                                            <button type="button" id="send-customer-email" class="button button-primary">📤 Invia Email</button>
                                        </div>
                                        
                                        <div class="yht-communication-history">
                                            <h4>📜 Storico Comunicazioni</h4>
                                            <div id="communication-history-list">
                                                <div class="yht-loading">Caricamento storico...</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .yht-customer-manager {
            max-width: 1200px;
        }
        
        .yht-customer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .yht-search-box {
            display: flex;
            gap: 10px;
        }
        
        .yht-search-box input {
            width: 300px;
        }
        
        .yht-filters {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .yht-customer-list-container {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
        }
        
        .yht-loading {
            text-align: center;
            padding: 40px 20px;
            color: #646970;
        }
        
        #customers-table {
            margin: 0;
        }
        
        .column-avatar {
            width: 50px;
        }
        
        .column-customer {
            width: 25%;
        }
        
        .column-bookings {
            width: 15%;
        }
        
        .column-total {
            width: 15%;
        }
        
        .column-last-booking {
            width: 20%;
        }
        
        .column-status {
            width: 15%;
        }
        
        .column-actions {
            width: 10%;
        }
        
        .customer-avatar img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
        }
        
        .customer-status {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        .status-returning {
            background: #cff4fc;
            color: #055160;
        }
        
        .status-new {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Modal Styles */
        .yht-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 100000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .yht-modal-content {
            background: #fff;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .yht-modal-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .yht-modal-header h2 {
            margin: 0;
        }
        
        .yht-modal-close {
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        
        .yht-modal-body {
            padding: 20px;
        }
        
        .yht-customer-info {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .customer-avatar img {
            width: 80px;
            height: 80px;
        }
        
        .customer-stats {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }
        
        .stat-item {
            font-size: 14px;
            color: #646970;
        }
        
        .yht-customer-tabs {
            
        }
        
        .yht-tab-nav {
            display: flex;
            border-bottom: 1px solid #ccd0d4;
            margin-bottom: 20px;
        }
        
        .yht-tab-button {
            padding: 10px 20px;
            border: none;
            background: none;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            font-weight: 500;
        }
        
        .yht-tab-button.active {
            border-bottom-color: #2271b1;
            color: #2271b1;
        }
        
        .yht-tab-panel {
            display: none;
        }
        
        .yht-tab-panel.active {
            display: block;
        }
        
        .yht-customer-notes textarea {
            width: 100%;
            margin-bottom: 10px;
        }
        
        .yht-send-email {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .yht-send-email input,
        .yht-send-email textarea {
            width: 100%;
            margin-bottom: 10px;
        }
        
        .yht-communication-history {
            
        }
        
        @media (max-width: 782px) {
            .yht-customer-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .yht-search-box input {
                width: 100%;
            }
            
            .yht-customer-info {
                flex-direction: column;
                text-align: center;
            }
        }
        </style>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            let currentPage = 1;
            let currentCustomers = [];
            
            // Load customers on page load
            loadCustomers();
            
            // Search functionality
            $('#search-customers').on('click', function() {
                currentPage = 1;
                loadCustomers();
            });
            
            $('#customer-search').on('keypress', function(e) {
                if (e.which == 13) {
                    currentPage = 1;
                    loadCustomers();
                }
            });
            
            // Filter changes
            $('#customer-status-filter, #customer-date-filter').on('change', function() {
                currentPage = 1;
                loadCustomers();
            });
            
            // Pagination
            $('#prev-page').on('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    loadCustomers();
                }
            });
            
            $('#next-page').on('click', function() {
                currentPage++;
                loadCustomers();
            });
            
            // Customer details modal
            $(document).on('click', '.view-customer-details', function() {
                const customerEmail = $(this).data('email');
                showCustomerDetails(customerEmail);
            });
            
            // Modal close
            $('.yht-modal-close').on('click', function() {
                $('#customer-details-modal').hide();
            });
            
            // Tab switching
            $('.yht-tab-button').on('click', function() {
                const tab = $(this).data('tab');
                $('.yht-tab-button').removeClass('active');
                $('.yht-tab-panel').removeClass('active');
                $(this).addClass('active');
                $('#tab-' + tab).addClass('active');
            });
            
            // Save customer notes
            $('#save-customer-notes').on('click', function() {
                saveCustomerNotes();
            });
            
            // Send customer email
            $('#send-customer-email').on('click', function() {
                sendCustomerEmail();
            });
            
            function loadCustomers() {
                $('#customers-loading').show();
                $('#customers-table').hide();
                $('#customers-pagination').hide();
                
                const data = {
                    action: 'yht_customer_search',
                    nonce: '<?php echo wp_create_nonce('yht_customer_nonce'); ?>',
                    search: $('#customer-search').val(),
                    status: $('#customer-status-filter').val(),
                    date_filter: $('#customer-date-filter').val(),
                    page: currentPage
                };
                
                $.post(ajaxurl, data, function(response) {
                    $('#customers-loading').hide();
                    
                    if (response.success) {
                        displayCustomers(response.data.customers);
                        updatePagination(response.data.pagination);
                        $('#customers-table').show();
                        $('#customers-pagination').show();
                    } else {
                        $('#customers-tbody').html('<tr><td colspan="7">Errore nel caricamento dei clienti</td></tr>');
                        $('#customers-table').show();
                    }
                });
            }
            
            function displayCustomers(customers) {
                let html = '';
                
                customers.forEach(function(customer) {
                    html += '<tr>';
                    html += '<td><img src="' + customer.avatar + '" alt="Avatar" style="width:32px;height:32px;border-radius:50%;" /></td>';
                    html += '<td>';
                    html += '<strong>' + customer.name + '</strong><br>';
                    html += '<small>' + customer.email + '</small>';
                    html += '</td>';
                    html += '<td>' + customer.total_bookings + '</td>';
                    html += '<td>€' + customer.total_spent + '</td>';
                    html += '<td>' + customer.last_booking_date + '</td>';
                    html += '<td><span class="customer-status status-' + customer.status + '">' + customer.status_label + '</span></td>';
                    html += '<td>';
                    html += '<button class="button button-small view-customer-details" data-email="' + customer.email + '">👁️ Dettagli</button>';
                    html += '</td>';
                    html += '</tr>';
                });
                
                if (customers.length === 0) {
                    html = '<tr><td colspan="7">Nessun cliente trovato</td></tr>';
                }
                
                $('#customers-tbody').html(html);
            }
            
            function updatePagination(pagination) {
                $('#page-info').text('Pagina ' + pagination.current + ' di ' + pagination.total);
                $('#prev-page').prop('disabled', pagination.current <= 1);
                $('#next-page').prop('disabled', pagination.current >= pagination.total);
            }
            
            function showCustomerDetails(email) {
                $('#customer-details-modal').show();
                
                // Load customer details
                $.post(ajaxurl, {
                    action: 'yht_customer_details',
                    nonce: '<?php echo wp_create_nonce('yht_customer_nonce'); ?>',
                    email: email
                }, function(response) {
                    if (response.success) {
                        const customer = response.data;
                        updateCustomerModal(customer);
                    }
                });
            }
            
            function updateCustomerModal(customer) {
                $('#customer-modal-title').text('Cliente: ' + customer.name);
                $('#customer-avatar').attr('src', customer.avatar);
                $('#customer-name').text(customer.name);
                $('#customer-email').text(customer.email);
                $('#customer-phone').text(customer.phone || 'Non disponibile');
                $('#customer-total-bookings').text(customer.total_bookings);
                $('#customer-total-spent').text('€' + customer.total_spent);
                $('#customer-registration-date').text(customer.first_booking_date);
                $('#customer-notes').val(customer.notes || '');
                
                // Load bookings
                displayCustomerBookings(customer.bookings);
            }
            
            function displayCustomerBookings(bookings) {
                let html = '';
                
                if (bookings.length > 0) {
                    html += '<table class="wp-list-table widefat">';
                    html += '<thead><tr><th>Riferimento</th><th>Tour</th><th>Data</th><th>Stato</th><th>Totale</th></tr></thead>';
                    html += '<tbody>';
                    
                    bookings.forEach(function(booking) {
                        html += '<tr>';
                        html += '<td>' + booking.reference + '</td>';
                        html += '<td>' + booking.tour_name + '</td>';
                        html += '<td>' + booking.travel_date + '</td>';
                        html += '<td>' + booking.status + '</td>';
                        html += '<td>€' + booking.total + '</td>';
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table>';
                } else {
                    html = '<p>Nessuna prenotazione trovata per questo cliente.</p>';
                }
                
                $('#customer-bookings-list').html(html);
            }
            
            function saveCustomerNotes() {
                const email = $('#customer-email').text();
                const notes = $('#customer-notes').val();
                
                $.post(ajaxurl, {
                    action: 'yht_customer_update_notes',
                    nonce: '<?php echo wp_create_nonce('yht_customer_nonce'); ?>',
                    email: email,
                    notes: notes
                }, function(response) {
                    if (response.success) {
                        alert('Note salvate con successo!');
                    } else {
                        alert('Errore nel salvataggio delle note');
                    }
                });
            }
            
            function sendCustomerEmail() {
                const email = $('#customer-email').text();
                const subject = $('#email-subject').val();
                const message = $('#email-message').val();
                
                if (!subject || !message) {
                    alert('Compila tutti i campi per inviare l\'email');
                    return;
                }
                
                $.post(ajaxurl, {
                    action: 'yht_send_customer_email',
                    nonce: '<?php echo wp_create_nonce('yht_customer_nonce'); ?>',
                    email: email,
                    subject: subject,
                    message: message
                }, function(response) {
                    if (response.success) {
                        alert('Email inviata con successo!');
                        $('#email-subject').val('');
                        $('#email-message').val('');
                    } else {
                        alert('Errore nell\'invio dell\'email: ' + response.data);
                    }
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * AJAX: Search customers
     */
    public function ajax_customer_search() {
        check_ajax_referer('yht_customer_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $search = sanitize_text_field($_POST['search'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? '');
        $date_filter = sanitize_text_field($_POST['date_filter'] ?? '');
        $page = max(1, intval($_POST['page'] ?? 1));
        $per_page = 20;
        
        $customers = $this->get_customers($search, $status, $date_filter, $page, $per_page);
        
        wp_send_json_success($customers);
    }
    
    /**
     * AJAX: Get customer details
     */
    public function ajax_customer_details() {
        check_ajax_referer('yht_customer_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $email = sanitize_email($_POST['email'] ?? '');
        $customer = $this->get_customer_details($email);
        
        wp_send_json_success($customer);
    }
    
    /**
     * AJAX: Update customer notes
     */
    public function ajax_update_customer_notes() {
        check_ajax_referer('yht_customer_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $email = sanitize_email($_POST['email'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        // Store notes in a custom table or meta
        update_option('yht_customer_notes_' . md5($email), $notes);
        
        wp_send_json_success();
    }
    
    /**
     * AJAX: Send email to customer
     */
    public function ajax_send_customer_email() {
        check_ajax_referer('yht_customer_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $email = sanitize_email($_POST['email'] ?? '');
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        
        $sent = wp_mail($email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
        
        if ($sent) {
            // Log communication
            $this->log_communication($email, $subject, $message);
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to send email');
        }
    }
    
    /**
     * Get customers with filters
     */
    private function get_customers($search = '', $status = '', $date_filter = '', $page = 1, $per_page = 20) {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        
        // Base query to get unique customers from bookings
        $where_conditions = array("p.meta_key = 'yht_customer_email'");
        
        if (!empty($search)) {
            $search = '%' . $wpdb->esc_like($search) . '%';
            $where_conditions[] = $wpdb->prepare("(
                p.meta_value LIKE %s 
                OR EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} pm 
                    WHERE pm.post_id = p.post_id 
                    AND pm.meta_key = 'yht_customer_name' 
                    AND pm.meta_value LIKE %s
                )
            )", $search, $search);
        }
        
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        
        // Get customer data
        $query = "
            SELECT DISTINCT 
                p.meta_value as email,
                (SELECT pm.meta_value FROM {$wpdb->postmeta} pm WHERE pm.post_id = p.post_id AND pm.meta_key = 'yht_customer_name' LIMIT 1) as name,
                (SELECT pm.meta_value FROM {$wpdb->postmeta} pm WHERE pm.post_id = p.post_id AND pm.meta_key = 'yht_customer_phone' LIMIT 1) as phone,
                COUNT(DISTINCT p.post_id) as total_bookings,
                SUM(CAST((SELECT pm.meta_value FROM {$wpdb->postmeta} pm WHERE pm.post_id = p.post_id AND pm.meta_key = 'yht_total_price' LIMIT 1) AS DECIMAL(10,2))) as total_spent,
                MAX(po.post_date) as last_booking_date,
                MIN(po.post_date) as first_booking_date
            FROM {$wpdb->postmeta} p
            JOIN {$wpdb->posts} po ON p.post_id = po.ID
            {$where_clause}
            GROUP BY p.meta_value
            ORDER BY last_booking_date DESC
            LIMIT {$per_page} OFFSET {$offset}
        ";
        
        $results = $wpdb->get_results($query);
        
        $customers = array();
        foreach ($results as $result) {
            $status_info = $this->determine_customer_status($result);
            
            $customers[] = array(
                'name' => $result->name ?: 'Cliente',
                'email' => $result->email,
                'phone' => $result->phone ?: '',
                'total_bookings' => intval($result->total_bookings),
                'total_spent' => number_format(floatval($result->total_spent), 2),
                'last_booking_date' => date('d/m/Y', strtotime($result->last_booking_date)),
                'first_booking_date' => date('d/m/Y', strtotime($result->first_booking_date)),
                'status' => $status_info['status'],
                'status_label' => $status_info['label'],
                'avatar' => $this->get_gravatar_url($result->email)
            );
        }
        
        // Get total count for pagination
        $count_query = str_replace('SELECT DISTINCT p.meta_value as email,', 'SELECT COUNT(DISTINCT p.meta_value)', $query);
        $count_query = preg_replace('/ORDER BY.*LIMIT.*/', '', $count_query);
        $total_customers = $wpdb->get_var($count_query);
        
        return array(
            'customers' => $customers,
            'pagination' => array(
                'current' => $page,
                'total' => ceil($total_customers / $per_page),
                'total_items' => $total_customers
            )
        );
    }
    
    /**
     * Get detailed customer information
     */
    private function get_customer_details($email) {
        global $wpdb;
        
        // Get customer bookings
        $bookings = $wpdb->get_results($wpdb->prepare("
            SELECT 
                po.ID,
                po.post_date,
                (SELECT pm.meta_value FROM {$wpdb->postmeta} pm WHERE pm.post_id = po.ID AND pm.meta_key = 'yht_booking_reference') as reference,
                (SELECT pm.meta_value FROM {$wpdb->postmeta} pm WHERE pm.post_id = po.ID AND pm.meta_key = 'yht_customer_name') as customer_name,
                (SELECT pm.meta_value FROM {$wpdb->postmeta} pm WHERE pm.post_id = po.ID AND pm.meta_key = 'yht_customer_phone') as customer_phone,
                (SELECT pm.meta_value FROM {$wpdb->postmeta} pm WHERE pm.post_id = po.ID AND pm.meta_key = 'yht_travel_date') as travel_date,
                (SELECT pm.meta_value FROM {$wpdb->postmeta} pm WHERE pm.post_id = po.ID AND pm.meta_key = 'yht_total_price') as total_price,
                (SELECT pm.meta_value FROM {$wpdb->postmeta} pm WHERE pm.post_id = po.ID AND pm.meta_key = 'yht_booking_status') as status,
                (SELECT pm.meta_value FROM {$wpdb->postmeta} pm WHERE pm.post_id = po.ID AND pm.meta_key = 'yht_itinerary_json') as itinerary_json
            FROM {$wpdb->posts} po
            JOIN {$wpdb->postmeta} pm ON po.ID = pm.post_id
            WHERE po.post_type = 'yht_booking'
            AND pm.meta_key = 'yht_customer_email'
            AND pm.meta_value = %s
            ORDER BY po.post_date DESC
        ", $email));
        
        $customer_data = array(
            'name' => '',
            'email' => $email,
            'phone' => '',
            'total_bookings' => count($bookings),
            'total_spent' => 0,
            'first_booking_date' => '',
            'last_booking_date' => '',
            'bookings' => array(),
            'notes' => get_option('yht_customer_notes_' . md5($email), ''),
            'avatar' => $this->get_gravatar_url($email)
        );
        
        foreach ($bookings as $booking) {
            if (empty($customer_data['name']) && !empty($booking->customer_name)) {
                $customer_data['name'] = $booking->customer_name;
            }
            if (empty($customer_data['phone']) && !empty($booking->customer_phone)) {
                $customer_data['phone'] = $booking->customer_phone;
            }
            
            $customer_data['total_spent'] += floatval($booking->total_price);
            
            $itinerary = json_decode($booking->itinerary_json, true);
            $tour_name = $itinerary['name'] ?? 'Tour personalizzato';
            
            $customer_data['bookings'][] = array(
                'reference' => $booking->reference,
                'tour_name' => $tour_name,
                'travel_date' => date('d/m/Y', strtotime($booking->travel_date)),
                'status' => $booking->status ?: 'pending',
                'total' => number_format(floatval($booking->total_price), 2),
                'booking_date' => date('d/m/Y', strtotime($booking->post_date))
            );
        }
        
        if (!empty($bookings)) {
            $customer_data['first_booking_date'] = date('d/m/Y', strtotime($bookings[count($bookings) - 1]->post_date));
            $customer_data['last_booking_date'] = date('d/m/Y', strtotime($bookings[0]->post_date));
        }
        
        $customer_data['total_spent'] = number_format($customer_data['total_spent'], 2);
        $customer_data['name'] = $customer_data['name'] ?: 'Cliente';
        
        return $customer_data;
    }
    
    /**
     * Determine customer status based on activity
     */
    private function determine_customer_status($customer) {
        $last_booking = strtotime($customer->last_booking_date);
        $days_since_last = (time() - $last_booking) / (60 * 60 * 24);
        
        if ($customer->total_bookings >= 3) {
            return array('status' => 'returning', 'label' => 'Cliente abituale');
        } elseif ($days_since_last <= 30) {
            return array('status' => 'active', 'label' => 'Cliente attivo');
        } else {
            return array('status' => 'new', 'label' => 'Nuovo cliente');
        }
    }
    
    /**
     * Get Gravatar URL for customer avatar
     */
    private function get_gravatar_url($email, $size = 32) {
        $hash = md5(strtolower(trim($email)));
        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=mp";
    }
    
    /**
     * Log customer communication
     */
    private function log_communication($email, $subject, $message) {
        $log_entry = array(
            'date' => current_time('mysql'),
            'type' => 'email',
            'subject' => $subject,
            'message' => $message
        );
        
        $existing_log = get_option('yht_customer_communication_' . md5($email), array());
        $existing_log[] = $log_entry;
        
        // Keep only last 50 entries
        $existing_log = array_slice($existing_log, -50);
        
        update_option('yht_customer_communication_' . md5($email), $existing_log);
    }
}