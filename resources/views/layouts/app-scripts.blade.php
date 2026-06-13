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
                              <a class="dropdown-item border-bottom py-2" href="${notif.url || '#'}" data-notif-id="${notif.id}">
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

    // Klik satu notifikasi: tandai dibaca → hilang dari daftar, lalu buka URL-nya.
    $(document).on('click', '#notificationDropdownList a[data-notif-id]', function (e) {
        e.preventDefault();

        var $item = $(this);
        var url = $item.attr('href');
        var markUrl = "{{ url('/notifications') }}/" + encodeURIComponent($item.data('notif-id')) + "/mark-read";

        // Hapus dari daftar & turunkan badge seketika (optimistic).
        $item.closest('div').remove();
        unreadCount = Math.max(0, unreadCount - 1);
        if (unreadCount > 0) {
            $('#notificationCounter').text(unreadCount).show();
        } else {
            $('#notificationCounter').hide();
            $('#notificationDropdownList').html('<div class="text-center py-4 text-muted"><small>Tidak ada notifikasi baru.</small></div>');
        }

        var goToUrl = function () {
            if (url && url !== '#') window.location.href = url;
        };

        // Tandai dibaca di server; tetap navigasi walau request gagal.
        $.post(markUrl, { _token: "{{ csrf_token() }}" }).always(goToUrl);
    });

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

{{-- ====== Enhancer global: COA dropdown searchable + desain konsisten ====== --}}
<style>
    /* Kartu opsi COA dalam dropdown select2 (info MAK + uraian + DIPA) */
    .coa-s2-drop .coa-opt-kode { font-family: SFMono-Regular, Menlo, Consolas, monospace; font-weight: 700; font-size: .8rem; color: #4f46e5; letter-spacing: -.01em; }
    .coa-s2-drop .coa-opt-sisa { color: #059669; font-weight: 700; font-size: .72rem; white-space: nowrap; }
    .coa-s2-drop .coa-opt-uraian { font-size: .74rem; color: #475569; margin-top: 2px; line-height: 1.35; }
    .coa-s2-drop .coa-opt-dipa { display: inline-flex; align-items: center; gap: 4px; font-size: .66rem; font-weight: 600; color: #4f46e5; background: #eef2ff; border: 1px solid #e0e7ff; border-radius: 999px; padding: 1px 9px; margin-top: 5px; }
    .coa-s2-drop .select2-results__option { border-bottom: 1px solid #f1f5f9; }
    /* Saat opsi disorot: latar indigo muda, teks tetap gelap & kontras */
    .coa-s2-drop .select2-results__option--highlighted,
    .coa-s2-drop .select2-results__option--highlighted[aria-selected] {
        background: #eef2ff !important;
        color: #0f172a !important;
    }
    .coa-s2-drop .select2-results__option--highlighted .coa-opt-kode { color: #4338ca; }
    .coa-s2-drop .select2-results__option--highlighted .coa-opt-sisa { color: #047857; }
    .coa-s2-drop .select2-results__option--highlighted .coa-opt-uraian { color: #334155; }
    .coa-s2-drop .select2-results__option--highlighted .coa-opt-dipa { background: #fff; border-color: #c7d2fe; color: #4338ca; }
    /* Opsi yang sedang terpilih (centang) — samakan kontrasnya */
    .coa-s2-drop .select2-results__option--selected .coa-opt-kode { color: #4338ca; }
    .coa-s2-drop .select2-results__option--selected .coa-opt-sisa { color: #047857; }
    .coa-s2-drop .select2-results__option--selected .coa-opt-uraian { color: #334155; }
    .coa-s2-drop .select2-results__option--selected .coa-opt-dipa { background: #fff; border-color: #c7d2fe; color: #4338ca; }
    /* Tampilan pilihan terpasang pada kotak select */
    .coa-s2 .coa-sel-kode { font-family: SFMono-Regular, Menlo, Consolas, monospace; font-weight: 700; }
    .coa-s2 .coa-sel-sisa { color: #059669; font-weight: 600; font-size: .78rem; }
</style>
<script>
(function () {
    // Render kaya untuk opsi COA: kode MAK + sisa pagu + uraian + badge DIPA.
    // Fallback ke teks biasa bila option tidak membawa data-kode.
    function formatCoaResult(opt) {
        if (!opt.element || !opt.element.dataset || !opt.element.dataset.kode) return opt.text;
        var $ = window.jQuery;
        var d = opt.element.dataset;

        var $top = $('<div style="display:flex;justify-content:space-between;align-items:baseline;gap:10px;"></div>')
            .append($('<span class="coa-opt-kode"></span>').text(d.kode))
            .append($('<span class="coa-opt-sisa"></span>').text('Sisa Rp ' + (d.sisa || '0')));

        var $wrap = $('<div class="coa-opt"></div>').append($top);

        if (d.uraian) {
            $wrap.append($('<div class="coa-opt-uraian"></div>').text(d.uraian));
        }

        if (d.dipa) {
            var dipaText = 'DIPA ' + d.dipa
                + (d.dipaTahun ? ' · TA ' + d.dipaTahun : '')
                + (d.dipaRevisi !== undefined && d.dipaRevisi !== '' ? ' · Revisi ke-' + d.dipaRevisi : '');
            $wrap.append($('<span class="coa-opt-dipa"></span>')
                .append('<i class="bi bi-journal-bookmark-fill"></i>')
                .append($('<span></span>').text(dipaText)));
        }

        return $wrap;
    }

    function formatCoaSelection(opt) {
        if (!opt.element || !opt.element.dataset || !opt.element.dataset.kode) return opt.text;
        var $ = window.jQuery;
        var d = opt.element.dataset;

        return $('<span></span>')
            .append($('<span class="coa-sel-kode"></span>').text(d.kode))
            .append($('<span class="coa-sel-sisa"></span>').text(' · Sisa Rp ' + (d.sisa || '0')));
    }

    function enhanceCoaSelects(root) {
        if (!(window.jQuery && typeof window.jQuery.fn.select2 === 'function')) return;
        var $ = window.jQuery;
        var scope = root ? $(root) : $(document);

        scope.find('select.js-coa-select').addBack('select.js-coa-select').each(function () {
            var $el = $(this);

            // Sudah dirapikan oleh enhancer ini? lewati.
            if ($el.data('coaEnhanced')) return;

            // Tentukan placeholder.
            var placeholder = $el.attr('data-coa-placeholder')
                || ($el.find('option[value=""]').first().text() || '').trim()
                || 'Cari & pilih COA...';

            // Dropdown menempel di modal bila berada dalam modal (agar pencarian bisa diketik).
            var $modal = $el.closest('.modal');

            // Bila sudah diinisialisasi (mis. oleh skrip halaman), reset dulu agar tema seragam.
            try {
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }
            } catch (e) { /* abaikan */ }

            $el.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: placeholder,
                allowClear: false,
                containerCssClass: 'coa-s2',
                dropdownCssClass: 'coa-s2-drop',
                dropdownParent: $modal.length ? $modal : $(document.body),
                templateResult: formatCoaResult,
                templateSelection: formatCoaSelection,
                language: {
                    noResults: function () { return 'COA tidak ditemukan'; },
                    searching: function () { return 'Mencari...'; },
                    inputTooShort: function () { return 'Ketik untuk mencari COA'; }
                }
            });

            $el.data('coaEnhanced', true);
        });
    }

    // Ekspos agar konten dinamis bisa memicu ulang.
    window.enhanceCoaSelects = enhanceCoaSelects;

    // Jalankan setelah seluruh init per-halaman selesai (window load > DOMContentLoaded).
    window.addEventListener('load', function () { enhanceCoaSelects(); });

    // Tangani select COA yang baru tampil di dalam modal.
    document.addEventListener('shown.bs.modal', function (e) { enhanceCoaSelects(e.target); });
})();
</script>

@stack('script')
