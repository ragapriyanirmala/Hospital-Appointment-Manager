jQuery(document).ready(function($) {
    // added datepicker column for the single text box
    const $dateInput = $('.ham-datepicker input');
    const $doctorSelect = $('.doctor select');
    let isOpen = false;
    let allowedDays = [];

    function initDatepicker() {
        $dateInput.datepicker('destroy').datepicker({
            dateFormat: 'yy-mm-dd',
            minDate: 0,
            showAnim: 'fadeIn',
            changeMonth: true,
            changeYear: true,
            showOtherMonths: true,
            selectOtherMonths: true,
            beforeShowDay: function(date) {
                const day = date.getDay(); 
                return [allowedDays.includes(day)];
            },
            onClose: function () {
                isOpen = false;
            }
        });
    }

    // Toggle datepicker on click
    $dateInput.on('click', function () {
        if (isOpen) {
            $(this).datepicker('hide');
            isOpen = false;
        } else {
            $(this).datepicker('show');
            isOpen = true;
        }
    });

    // Fetch availability when doctor changes
    $doctorSelect.on('change', function () {
        const doctorID = $(this).val();
        if (!doctorID) return;

        $.ajax({
            url: ham_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_doctor_availability',
                doctor_id: doctorID
            },
            success: function(response) {
                if (response.success && response.data.allowed_days) {
                    allowedDays = response.data.allowed_days;
                    initDatepicker();
                }
            }
        });
    });
    initDatepicker();
     const $timeSlotDropdown = $('.wpform-time-slot select');
//updated time slots based on the doctor change and select appointment date
    function updateTimeSlots(doctorID,selectedDate) {
        $.ajax({
            url: ham_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_doctor_availability',
                doctor_id: doctorID,
                appointment_date: selectedDate
            },
            success: function(response) {
                if (response.success && response.data.available_slots) {
                    const slots = response.data.available_slots;
                    $timeSlotDropdown.empty();
                    $timeSlotDropdown.append('<option value="">Select a time slot</option>');
                    slots.forEach(function(slot) {
                        $timeSlotDropdown.append(`<option value="${slot}">${slot}</option>`);
                    });
                }
            }
        });
    }
//get doctor id to pass te time slot
    $doctorSelect.on('change', function () {
        const doctorID = $(this).val();
        const selectedDate = $dateInput.val(); 
        if (doctorID) updateTimeSlots(doctorID,selectedDate);
        $('.ham-datepicker').css('display','block');

    });
 //get the appointment date to pass the time slots
    $dateInput.on('change', function () {
    const doctorID = $doctorSelect.val();
    const selectedDate = $(this).val();
    if (doctorID && selectedDate) {
        updateTimeSlots(doctorID, selectedDate);
        $('.wpform-time-slot').css('display','block');
    }
});
// status update ajax call
$('.ham-status-dropdown').on('change', function () {
        const appointmentId = $(this).data('id');
        const newStatus = $(this).val();
        $.post(ajaxurl, {
               action: 'ham_update_appointment_status',
               id: appointmentId,
               status: newStatus
                }, function(response) {
                	location.reload();
        });
});
   $('.day-checkbox').on('change', function () {
            let container = $(this).closest('div').find('.slots-group');
            container.toggle(this.checked);
        });

        $('.add-slot').click(function () {
            let day = $(this).data('day');
            let wrapper = $(this).siblings('.slot-wrapper');
            let index = wrapper.children('.slot-row').length;

            let html = `<div class="slot-row">
                From: <input type="time" name="time_slots_by_day[${day}][${index}][start]" />
                To: <input type="time" name="time_slots_by_day[${day}][${index}][end]" />
                <button type="button" class="remove-slot button">Remove</button>
            </div>`;
            wrapper.append(html);
        });

        $(document).on('click', '.remove-slot', function () {
            $(this).parent().remove();
        });
      new DataTable('#appointments-table', {
        initComplete: function () {
        this.api()
            .columns()
            .every(function () {
                let column = this;
                let index = column.index();
                if (index === 0) return;

                let select = document.createElement('select');
                select.add(new Option('Filter'));
                column.footer().replaceChildren(select);

                select.addEventListener('change', function () {
                    column
                        .search(select.value, { exact: true })
                        .draw();
                });

                if (index === 6) {
                    column.nodes().each(function (cell) {
                        const val = $(cell).find('select').val();
                        if (val) {
                            $(cell).data('text', val); 
                        }
                    });

                    const uniqueVals = new Set();
                    column.nodes().each(function (cell) {
                        const val = $(cell).data('text');
                        if (val) uniqueVals.add(val);
                    });

                    [...uniqueVals].sort().forEach(function (d) {
                        select.add(new Option(d));
                    });

                    // Use a custom search method
                    column.search = function (val, options) {
                        this.nodes().each(function (cell) {
                            const selected = $(cell).find('select').val();
                            $(cell).parent().toggle(val === '' || selected === val);
                        });
                    };
                } else {
                    // For normal text-based columns
                    column
                        .data()
                        .unique()
                        .sort()
                        .each(function (d) {
                            select.add(new Option(d));
                        });
                }
            });
    }
});

});