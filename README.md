# Hospital Appointment Manager

**Version:** 1.0  
**Author:** Ragapriya  
**Requires WordPress Version:** 6.3.2 or higher  
**Requires PHP Version:** 8.2  
**Plugin URI:** https://localhost/hospitalappointment/hospital-appointment-manager

---

## Description

The **Hospital Appointment Manager** plugin allows patients to book appointments with doctors. Admins can manage schedules, set available days and time slots, and monitor appointments via the WordPress dashboard.

---

## Features

- Doctor availability management via custom meta box
- Time slot management for each weekday
- Booking form integration (via WPForms)
- AJAX-powered time slot availability
- Email notifications on status update
- Admin dashboard for managing appointments
- DataTables-powered appointment filtering

---

## Installation

1. Download or clone the plugin into your WordPress 'wp-content/plugins' directory

2. Activate the plugin from the **Plugins** page in the WordPress admin.

3. Create a custom post type 'doctor' and custom fields by using ACF Plugin (or use an existing one).

4. Create a WPForm with ID '29' having fields:
- Patient Name (First, Last)
- Email
- Doctor (Dropdown)
- Appointment Date (Text or Date field)
- Time Slot (Dropdown)

5. Place the booking form on a page using the shortcode:['hospital_appointment_form']

### Admin Side:

1. **Define Doctor Schedules:**
   - Edit a doctor post.
   - Use the **Doctor Availability Schedule** meta box to:
     - Select available weekdays.
     - Add multiple time slot ranges per day (e.g., 9:00–12:00, 15:00–18:00).

2. **View Appointments:**
   - Navigate to **Hospital Appointments** in the dashboard menu.
   - Filter by doctor, date, or status using the dropdowns in the DataTable.
   - Change status inline; an email is sent automatically to the patient.

### User Side:

1. Visit the booking page with the `[hospital_appointment_form]` shortcode.
2. Select a doctor.
3. Pick a date (only enabled days show).
4. Select a time slot (unavailable/booked slots are automatically hidden).
5. Submit the form.
6. Appointment will appear as **Pending** by default.

---

## Email Notification

When the admin updates the status of an appointment (e.g., to "Confirmed"), the plugin automatically sends a confirmation email to the patient with the appointment date and time.

---

## Security

- Uses `wp_nonce_field` for secure meta box submission.
- Uses `sanitize_*` and `$wpdb->prepare()` to sanitize inputs and prevent SQL injection.
- Verifies form ID before inserting to avoid unintended submissions.

## Future Improvements

- Patient dashboard to view or cancel appointments
- Email templates and settings panel

---