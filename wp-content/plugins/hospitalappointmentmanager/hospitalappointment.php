<?php 

/*
 * Plugin name: Hospital Appointment Manager
 * description: This plugin that allows users to book appointments with doctors at a hospital.
 * Plugin URI: https://localhost/hospitalappointment/hospital-appointment-manager
 * Author: ragapriya
 * Auther URI: https://example.com
 * Version: 1.0
 * Requires at least: 6.3.2
 * Requires PHP: 8.2
*/
/* While activating the plugin created custom table */
register_activation_hook(__FILE__, 'ham_create_tables');

function ham_create_tables() {
    global $wpdb;

    $table = $wpdb->prefix . 'ham_appointments';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        patient_name VARCHAR(255),
        patient_email VARCHAR(255),
        doctor_id BIGINT(20) UNSIGNED,
        appointment_date DATE,
        time_slot VARCHAR(100),
        status VARCHAR(50) DEFAULT 'Pending'
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
// adding the menu in admin panel
add_action('admin_menu', 'add_hospital_appointments_menu');

// Function to register the menu
function add_hospital_appointments_menu() {
    add_menu_page(
        'Hospital Appointments',        
        'Hospital Appointments',     
        'manage_options',              
        'hospital-appointments',        
        'hospital_appointments_page',   
        'dashicons-calendar-alt',      
        6                               
    );
}
// Function to display content when menu is clicked
function hospital_appointments_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'ham_appointments';
    $results = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);
    ?>

    <div class="wrap">
        <h1>All Appointments</h1>
        <table id="appointments-table" class="display wp-list-table widefat">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Patient Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Doctor Name</th>
                    <th scope="col">Date</th>
                    <th scope="col">Time Slot</th>
                    <th scope="col">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row) : ?>
                    <tr>
                        <td><?= esc_html($row['id']); ?></td>
                        <td><?= esc_html($row['patient_name']); ?></td>
                        <td><?= esc_html($row['patient_email']); ?></td>
                        <td><?= esc_html(get_the_title($row['doctor_id'])); ?></td>
                        <td><?= esc_html($row['appointment_date']); ?></td>
                        <td><?= esc_html($row['time_slot']); ?></td>
                         <td>
                            <select class="ham-status-dropdown" data-id="<?= esc_attr($row['id']); ?>">
                                <option value="Pending" <?= selected($row['status'], 'Pending', false); ?>>Pending</option>
                                <option value="Confirmed" <?= selected($row['status'], 'Confirmed', false); ?>>Confirmed</option>
                                <option value="Rejected" <?= selected($row['status'], 'Rejected', false); ?>>Rejected</option>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
             <tfoot>
                <tr>
                   <th scope="col">ID</th>
                    <th scope="col">Patient Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Doctor Name</th>
                    <th scope="col">Date</th>
                    <th scope="col">Time Slot</th>
                    <th scope="col">Status</th>
                </tr>
            </tfoot>
        </table>
    </div>
<?php
}
// log created 
if (!function_exists('write_log')) {

    function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }

}
// while the wpfroms submission inserting form data into the custom table 
add_action('wpforms_process_complete', 'ham_handle_wpform_appointment', 10, 4);

function ham_handle_wpform_appointment($fields, $entry, $form_data, $entry_id) {
    $target_form_id = 29;

    if ((int) $form_data['id'] !== $target_form_id) {
        return; 
    }

    global $wpdb;
    $table = $wpdb->prefix . 'ham_appointments';
    $form_values = [];

 $patient_name = '';
    $patient_email = '';
    $doctor_id = 0;
    $appointment_date = '';
    $time_slot = '';

    foreach ($fields as $field) {
        $label = strtolower($field['name']);

        if (strpos($label, 'patient name') !== false && isset($field['first'], $field['last'])) {
            $patient_name = sanitize_text_field($field['first'] . ' ' . $field['last']);
        } elseif (strpos($label, 'email') !== false) {
            $patient_email = sanitize_email($field['value']);
        } elseif (strpos($label, 'doctor') !== false) {
            $doctor_id = absint($field['value_raw']); 
        } elseif (strpos($label, 'appointment date') !== false) {
            $appointment_date = sanitize_text_field($field['value']);
        } elseif (strpos($label, 'time slot') !== false || strpos($label, 'time') !== false) {
            $time_slot = sanitize_text_field($field['value_raw'] ?? $field['value']);
        }
    }

    // Combine into datetime format
    $appointment_datetime = date('Y-m-d H:i:s', strtotime("$appointment_date $time_slot"));
    // Check for duplicate
   $existing = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table WHERE doctor_id = %d AND appointment_date = %s AND time_slot = %s",
    $doctor_id, $appointment_date, $time_slot
    ));
    // Insert into DB
    $wpdb->insert($table, [
        'patient_name'      => $patient_name,
        'patient_email'     => $patient_email,
        'doctor_id'         => $doctor_id,
        'appointment_date'  => $appointment_date,
        'time_slot'         => $time_slot,
        'status'            => 'Pending'
    ]);
}
// link the scrips and style files
add_action('wp_enqueue_scripts', 'ham_enqueue_datepicker_assets');
add_action('admin_init', 'ham_enqueue_datepicker_assets');
function ham_enqueue_datepicker_assets() {
  wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-css', 'https://code.jquery.com/ui/1.13.2/themes/smoothness/jquery-ui.css');

    // Time picker addon
    wp_enqueue_script('jquery-ui-timepicker-addon', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.js', ['jquery', 'jquery-ui-datepicker'], null, true);
    wp_enqueue_style('jquery-ui-timepicker-addon-css', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.6.3/jquery-ui-timepicker-addon.min.css');

    wp_enqueue_script('ham-datepicker-js', plugin_dir_url(__FILE__) . 'assets/js/datepicker.js', ['jquery'], null, true);
    wp_enqueue_style('ham-datepicker-style', plugin_dir_url(__FILE__) . 'assets/css/style.css');
    wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css');
    wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', ['jquery'], null, true);
    wp_localize_script('ham-datepicker-js', 'ham_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);
}
// timeslot selection based on the odoctor and appointmentdate
function ham_get_time_slot_intervals($doctor_id, $selected_date = '') {
    global $wpdb;

    $day_of_week = strtolower(date('l', strtotime($selected_date))); 
    $slots_by_day = get_post_meta($doctor_id, 'time_slots_by_day', true);

    if (!is_array($slots_by_day) || !isset($slots_by_day[$day_of_week])) {
        return [];
    }

    $intervals = $slots_by_day[$day_of_week]; 
    $all_slots = [];

    foreach ($intervals as $range) {
        $start_time = strtotime($range['start']);
        $end_time = strtotime($range['end']);

        while ($start_time < $end_time) {
            $slot_start = date('H:i', $start_time);
            $slot_end   = date('H:i', strtotime('+30 minutes', $start_time));
            if (strtotime($slot_end) > $end_time) break;

            $all_slots[] = $slot_start . ' - ' . $slot_end;
            $start_time = strtotime('+30 minutes', $start_time);
        }
    }

    // Get already booked slots for this doctor on this date
    $table = $wpdb->prefix . 'ham_appointments';
    $booked_slots = $wpdb->get_col($wpdb->prepare(
        "SELECT time_slot FROM $table WHERE doctor_id = %d AND appointment_date = %s",
        $doctor_id, $selected_date
    ));

    // Remove booked slots
    $available_slots = array_diff($all_slots, $booked_slots);

    return array_values($available_slots); // reindex
}
// ajax call for get the doctor availability
add_action('wp_ajax_get_doctor_availability', 'ham_get_doctor_availability');
add_action('wp_ajax_nopriv_get_doctor_availability', 'ham_get_doctor_availability');

function ham_get_doctor_availability() {
    $doctor_id = intval($_POST['doctor_id']);
    $selected_date = sanitize_text_field($_POST['appointment_date']);
    $map = [
        'sunday'    => 0,
        'monday'    => 1,
        'tuesday'   => 2,
        'wednesday' => 3,
        'thursday'  => 4,
        'friday'    => 5,
        'saturday'  => 6,
    ];

    $time_slots_by_day = get_post_meta($doctor_id, 'time_slots_by_day', true);

    if (!is_array($time_slots_by_day)) {
        wp_send_json_error(['message' => 'No available slots set.']);
    }

    $allowed_days = [];

    foreach ($time_slots_by_day as $day_name => $slots) {
        $day_name = strtolower($day_name);
        if (isset($map[$day_name])) {
            $allowed_days[] = $map[$day_name];
        }
    }
    $available_slots = ham_get_time_slot_intervals($doctor_id, $selected_date);
    wp_send_json_success([
        'allowed_days'     => $allowed_days,
        'available_slots'  => $available_slots,
    ]);
}
//generated the shortcode for the form
add_shortcode('hospital_appointment_form','hospital_appointment_form');

function hospital_appointment_form()
{
    return do_shortcode('[wpforms id="29"]'); 
}

// admin changes the status
add_action('wp_ajax_ham_update_appointment_status', 'ham_update_appointment_status');
function ham_update_appointment_status() {
    global $wpdb;
    $id = intval($_POST['id']);
    $status = sanitize_text_field($_POST['status']);

    $table = $wpdb->prefix . 'ham_appointments';

    // Fetch full row (patient info)
    $appointment = $wpdb->get_row(
        $wpdb->prepare("SELECT patient_email, patient_name, appointment_date, time_slot FROM $table WHERE id = %d", $id),
        ARRAY_A
    );

    if (!$appointment) {
        wp_send_json_error(['message' => 'Appointment not found']);
        return;
    }

    $updated = $wpdb->update(
        $table,
        ['status' => $status],
        ['id' => $id],
        ['%s'],
        ['%d']
    );

    if ($updated !== false) {
        $to = $appointment['patient_email'];
        $name = $appointment['patient_name'];
        $appointment_date = $appointment['appointment_date'];
        $time_slot = $appointment['time_slot'];

        $subject = "Appointment Status Updated";
        $message = "Hi $name,\n\nYour appointment is now marked as \"$status\".\nOn: $appointment_date at $time_slot.\n\nThanks!";
        $headers = "From: ragapriyanirmala@gmail.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        mail($to, $subject, $message, $headers);
        wp_send_json_success(['message' => 'Status updated and email sent']);
    } else {
        wp_send_json_error(['message' => 'Update failed']);
    }
}
// initialised the meta boxes for the doctor availability
add_action('add_meta_boxes', 'ham_add_doctor_schedule_metabox');
function ham_add_doctor_schedule_metabox() {
    add_meta_box(
        'doctor_schedule',
        'Doctor Availability Schedule',
        'ham_render_schedule_metabox',
        'doctor', 
        'normal',
        'default'
    );
}
//meta box function call
function ham_render_schedule_metabox($post) {
    $weekdays = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

    $saved_days = get_post_meta($post->ID, 'available_days', true) ?: [];
    $saved_slots = get_post_meta($post->ID, 'time_slots_by_day', true) ?: [];

    echo '<p><strong>Select Available Days & Time Slots:</strong></p>';

    foreach ($weekdays as $day) {
        $day_key = strtolower($day);
        $checked = in_array($day, $saved_days) ? 'checked' : '';
        $slots = $saved_slots[$day_key] ?? [];

        echo "<div style='margin-bottom:15px;'>";
        echo "<label><input type='checkbox' class='day-checkbox' name='available_days[]' value='$day' $checked> $day</label>";

        echo "<div class='slots-group' data-day='$day_key' style='" . ($checked ? '' : 'display:none;') . "'>";

        echo "<button type='button' class='add-slot button' data-day='$day_key'>+ Add Slot</button>";

        echo "<div class='slot-wrapper'>";
        if (!empty($slots)) {
            foreach ($slots as $i => $slot) {
                $start = esc_attr($slot['start']);
                $end = esc_attr($slot['end']);
                echo "<div class='slot-row'>
                    From: <input type='time' name='time_slots_by_day[$day_key][$i][start]' value='$start' />
                    To: <input type='time' name='time_slots_by_day[$day_key][$i][end]' value='$end' />
                    <button type='button' class='remove-slot button'>Remove</button>
                </div>";
            }
        }
        echo "</div></div></div>";
    }
    //added nonce for the security
    wp_nonce_field('save_doctor_time_slots', 'doctor_time_slots_nonce');
}
// save the custom metabox details inside the Doctor post
add_action('save_post', function ($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['doctor_time_slots_nonce']) || !wp_verify_nonce($_POST['doctor_time_slots_nonce'], 'save_doctor_time_slots')) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) return;

    $slots = $_POST['time_slots_by_day'] ?? [];
    $cleaned = [];

    foreach ($slots as $day => $rows) {
        foreach ($rows as $slot) {
            $cleaned[$day][] = [
                'start' => sanitize_text_field($slot['start']),
                'end'   => sanitize_text_field($slot['end']),
            ];
        }
    }
    update_post_meta($post_id, 'time_slots_by_day', $cleaned);
});