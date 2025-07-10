   $('.ham-status-dropdown').on('change', function () {
     	alert("dddd");
                const appointmentId = $(this).data('id');
                const newStatus = $(this).val();

                $.post(ajaxurl, {
                    action: 'ham_update_appointment_status',
                    id: appointmentId,
                    status: newStatus
                }, function(response) {
                    alert(response.data.message);
                });
            });