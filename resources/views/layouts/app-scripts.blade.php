<!--plugins-->
<script src="{{ URL::asset('build/js/jquery.min.js') }}"></script>
<!--bootstrap js-->
<script src="{{ URL::asset('build/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
<script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ URL::asset('build/plugins/select2/js/select2-custom.js') }}"></script>
<script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
<script src="{{ URL::asset('build/js/main.js') }}"></script>

@include('layouts.theme-customizer-script')

<script>
$(document).ready(function() {
    let unreadCount = 0;
    // SFX from internet (A short calm beep)
    const notifSound = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');

    function fetchNotifications() {
        $.ajax({
            url: "{{ route('notifications.fetch') }}",
            type: "GET",
            success: function(response) {
                // Render List first
                let listHtml = '';
                if (response.notifications.length === 0) {
                    listHtml = '<div class="text-center py-4 text-muted"><small>Tidak ada notifikasi baru.</small></div>';
                } else {
                    response.notifications.forEach(function(notif) {
                        listHtml += `
                            <div>
                              <a class="dropdown-item border-bottom py-2" href="${notif.url}">
                                <div class="d-flex align-items-center gap-3">
                                  <div class="user-wrapper bg-${notif.color} text-${notif.color} bg-opacity-10">
                                    <i class="material-icons-outlined">${notif.icon}</i>
                                  </div>
                                  <div style="width: 200px; white-space: normal;">
                                    <h5 class="notify-title">${notif.title}</h5>
                                    <p class="mb-0 notify-desc" style="line-height:1.2;"><small>${notif.message}</small></p>
                                    <p class="mb-0 notify-time">${notif.time_ago}</p>
                                  </div>
                                </div>
                              </a>
                            </div>
                        `;
                    });
                }
                $('#notificationDropdownList').html(listHtml);

                // Check Unread Count and Play Sound
                if (response.count > 0) {
                    $('#notificationCounter').text(response.count).show();
                    // Play sound if count increased!
                    if (response.count > unreadCount && unreadCount !== 0) {
                        // only alert if there is a fresh one coming during session
                        notifSound.play().catch(e => console.log('Audio blocked by browser.'));
                    }
                } else {
                    $('#notificationCounter').hide();
                }
                // Initialize unread limit state
                if (unreadCount === 0 && response.count > 0) {
                     unreadCount = response.count;
                } else {
                     unreadCount = response.count;
                }
            }
        });
    }

    // Polling every 10 seconds (10000ms)
    setInterval(fetchNotifications, 10000);
    // Initial fetch on load
    fetchNotifications();
});

function markNotificationsAsRead() {
    $.post("{{ route('notifications.mark-read') }}", {
        _token: "{{ csrf_token() }}"
    }, function(data) {
        if(data.status === 'success') {
            $('#notificationCounter').hide();
            $('#notificationDropdownList').html('<div class="text-center py-4 text-muted"><small>Semua telah dibaca.</small></div>');
        }
    });
}
</script>

@stack('script')
