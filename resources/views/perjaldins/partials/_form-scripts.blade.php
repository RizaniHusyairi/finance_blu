<script>
    let rowIdx = {{ count($oldPeserta) }};

    function formatNumber(n) {
        return n.toString().replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // key: 'tiket' | 'transport' | 'penginapan' | 'uang-harian'
    window.toggleBuktiFile = function (element, key) {
        let card = $(element).closest('.item-row');
        let val = parseFloat(String($(element).val()).replace(/,/g, '')) || 0;
        let wrapper = card.find('.' + key + '-file-wrapper');
        if (val > 0) {
            wrapper.removeClass('d-none');
        } else {
            wrapper.addClass('d-none');
            wrapper.find('.' + key + '-file-input').val('');
            wrapper.find('.' + key + '-existing-notice').remove();
        }
    };
    window.toggleTiketFile = function (element) { return toggleBuktiFile(element, 'tiket'); };

    function toggleUangHarianFile(card) {
        let total = 0;
        card.find('.uang-harian-component').each(function () {
            let n = parseFloat(String($(this).val()).replace(/,/g, ''));
            if (!isNaN(n)) total += n;
        });
        let wrapper = card.find('.uang-harian-file-wrapper');
        if (total > 0) {
            wrapper.removeClass('d-none');
        } else {
            wrapper.addClass('d-none');
            wrapper.find('.uang-harian-file-input').val('');
            wrapper.find('.uang-harian-existing-notice').remove();
        }
    }

    function calculateUangHarianGroup(card) {
        let total = 0;
        card.find('.uang-harian-component').each(function () {
            let num = parseFloat(String($(this).val()).replace(/,/g, ''));
            if (!isNaN(num)) total += num;
        });
        card.find('.uang-harian-total').text(formatNumber(total));
        toggleUangHarianFile(card);
    }

    window.calculateJumlah = function (element) {
        let val = $(element).val();
        if (val !== '') $(element).val(formatNumber(val));

        let card = $(element).closest('.item-row');
        let total = 0;
        card.find('.biaya-input').each(function () {
            let num = parseFloat($(this).val().replace(/,/g, ''));
            if (!isNaN(num)) total += num;
        });
        card.find('.row-jumlah').val(formatNumber(total));
        card.find('.summary-total').text(formatNumber(total));
        calculateUangHarianGroup(card);
        calculateGrandTotal();
    }

    function calculateGrandTotal() {
        let grandTotal = 0;
        $('.row-jumlah').each(function () {
            let num = parseFloat($(this).val().replace(/,/g, ''));
            if (!isNaN(num)) grandTotal += num;
        });
        $('#grandTotal').val(formatNumber(grandTotal));
        const grandEl = $('#summaryGrandTotal');
        const newText = 'Rp ' + formatNumber(grandTotal);
        if (grandEl.text() !== newText) {
            grandEl.removeClass('flash');
            void grandEl[0].offsetWidth;
            grandEl.addClass('flash');
        }
        grandEl.text(newText);
        $('#summaryCount').text($('.item-row').length);
        updateProgress();
    }

    function calculateUangHarian(card) {
        let provSelect = card.find('.provinsi-select option:selected');
        let tipe = card.find('.tipe-select').val();
        let lamaHari = parseInt(card.find('.lama-hari-input').val()) || 0;

        if (provSelect.val() !== '' && typeof provSelect.val() !== 'undefined' && tipe !== '') {
            let rate = 0;
            if (tipe === 'luar_kota') rate = parseFloat(provSelect.data('luar')) || 0;
            else if (tipe === 'dalam_kota_lebih_8_jam') rate = parseFloat(provSelect.data('dalam')) || 0;
            else if (tipe === 'diklat') rate = parseFloat(provSelect.data('diklat')) || 0;

            let totalUangHarian = rate * lamaHari;
            card.find('.uang-harian-input').val(formatNumber(totalUangHarian));
            calculateJumlah(card.find('.uang-harian-input')[0]);
        }
    }

    // ===== Progress stepper =====
    function updateProgress() {
        const flags = {
            dokumen: ['#inp_deskripsi', '#inp_nomor', '#inp_bulan', '#inp_tahun', '#inp_kota', '#inp_tanggal']
                .every(sel => ($(sel).val() || '').trim() !== ''),
            verifikator: ['#ppkUserId', '#ppspmUserId', '#bendaharaUserId', '#bendaharaPenerimaanUserId', '#koorKeuanganUserId']
                .every(sel => ($(sel).val() || '').trim() !== '')
                && ($('#ppkNipSnapshot').val() || '').trim() !== ''
                && ($('#bendaharaNipSnapshot').val() || '').trim() !== '',
            peserta: false,
        };

        // peserta: minimal 1 row dengan total > 0 dan pegawai terpilih
        let validPeserta = 0;
        $('.item-row').each(function () {
            const card = $(this);
            const total = parseFloat(String(card.find('.row-jumlah').val() || '0').replace(/,/g, '')) || 0;
            const pegawaiVal = card.find('.pegawai-select').val() || '';
            if (total > 0 && pegawaiVal !== '') validPeserta++;
        });
        flags.peserta = validPeserta > 0;

        const total = 3;
        let done = 0;
        const stepEls = document.querySelectorAll('.stepper-item');
        let firstActiveSet = false;
        stepEls.forEach(el => {
            const step = el.dataset.step;
            el.classList.remove('done', 'active');
            if (flags[step]) {
                el.classList.add('done');
                done++;
            } else if (!firstActiveSet) {
                el.classList.add('active');
                firstActiveSet = true;
            }
        });
        const pct = Math.round((done / total) * 100);
        document.getElementById('progressFill').style.width = pct + '%';
        document.getElementById('progressPct').textContent = pct + '% lengkap';

        // Highlight active section card
        document.querySelectorAll('.sec-card').forEach(card => {
            card.classList.toggle('is-active', card.dataset.section === document.querySelector('.stepper-item.active')?.dataset.step);
        });

        // Update submit bar
        const allReady = flags.dokumen && flags.verifikator && flags.peserta;
        const sb = document.getElementById('submitBar');
        const sbIcon = document.getElementById('sbIcon');
        const sbTitle = document.getElementById('sbTitle');
        const sbDesc = document.getElementById('sbDesc');
        if (allReady) {
            sb.classList.add('is-ready');
            sbIcon.className = 'bi bi-shield-check';
            sbTitle.textContent = 'Siap diajukan';
            sbDesc.textContent = 'Semua field wajib sudah terisi. Klik Simpan Pengajuan untuk melanjutkan.';
        } else {
            sb.classList.remove('is-ready');
            sbIcon.className = 'bi bi-shield-exclamation';
            sbTitle.textContent = 'Lengkapi formulir (' + pct + '%)';
            const missing = [];
            if (!flags.dokumen)      missing.push('Dokumen');
            if (!flags.verifikator)  missing.push('Verifikator');
            if (!flags.peserta)      missing.push('Peserta');
            sbDesc.textContent = missing.length ? 'Belum lengkap: ' + missing.join(', ') + '.' : 'Hampir selesai!';
        }
    }

    $(document).ready(function () {
        $('.select2').select2({ theme: 'bootstrap-5', width: '100%' });

        $('.biaya-input').each(function() { calculateJumlah(this); });
        $('.tiket-amount-input').each(function() { toggleBuktiFile(this, 'tiket'); });
        $('.transport-amount-input').each(function() { toggleBuktiFile(this, 'transport'); });
        $('.penginapan-amount-input').each(function() { toggleBuktiFile(this, 'penginapan'); });
        $('.item-row').each(function() { toggleUangHarianFile($(this)); });

        $('#ppkUserId').change(function() {
            let s = $(this).find(':selected');
            if (s.val() !== '') {
                $('#ppkNamaSnapshot').val(s.data('nama'));
                $('#ppkNipSnapshot').val(s.data('nip'));
            }
            updateProgress();
        });
        $('#ppspmUserId').change(function() {
            let s = $(this).find(':selected');
            if (s.val() !== '') {
                $('#ppspmNamaSnapshot').val(s.data('nama'));
                $('#ppspmNipSnapshot').val(s.data('nip'));
            }
            updateProgress();
        });
        $('#bendaharaPenerimaanUserId').change(function() {
            let s = $(this).find(':selected');
            if (s.val() !== '') {
                $('#bendaharaPenerimaanNamaSnapshot').val(s.data('nama'));
                $('#bendaharaPenerimaanNipSnapshot').val(s.data('nip'));
            }
            updateProgress();
        });
        $('#bendaharaUserId').change(function() {
            let s = $(this).find(':selected');
            if (s.val() !== '') {
                $('#bendaharaNamaSnapshot').val(s.data('nama'));
                $('#bendaharaNipSnapshot').val(s.data('nip'));
            }
            updateProgress();
        });
        $('#koorKeuanganUserId').change(function() {
            let s = $(this).find(':selected');
            if (s.val() !== '') {
                $('#koorKeuanganNamaSnapshot').val(s.data('nama'));
                $('#koorKeuanganNipSnapshot').val(s.data('nip'));
            }
            updateProgress();
        });

        // Update progress when document fields change
        $('#inp_deskripsi, #inp_nomor, #inp_bulan, #inp_tahun, #inp_kota, #inp_tanggal, #ppkNipSnapshot, #bendaharaNipSnapshot').on('input change', updateProgress);

        // Stepper click → smooth scroll to section
        $('.stepper-item').on('click', function () {
            const step = $(this).data('step');
            const target = document.querySelector('[data-section="' + step + '"]');
            if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        // Add Row
        $(document).on('click', '.btn-add-row-trigger', function (e) {
            e.preventDefault();

            let firstRow = $('.item-row:first');
            if (firstRow.find('.select2').hasClass('select2-hidden-accessible')) {
                firstRow.find('.select2').select2('destroy');
            }

            let newRow = firstRow.clone();
            firstRow.find('.select2').select2({ theme: 'bootstrap-5', width: '100%' });

            newRow.find('input[type="text"], input[type="number"], input[type="date"], input[type="hidden"], input[type="file"], textarea').val('').removeClass('is-invalid');
            newRow.find('select').prop('selectedIndex', 0).removeClass('is-invalid');
            newRow.find('.row-jumlah').val('0');
            newRow.find('.summary-nama, .summary-tujuan').text('-');
            newRow.find('.summary-total').text('0');
            newRow.find('.uang-harian-total').text('0');
            newRow.find('.file-existing-notice').remove();
            newRow.find('.tiket-existing-notice, .transport-existing-notice, .penginapan-existing-notice, .uang-harian-existing-notice').remove();
            newRow.find('.tiket-file-wrapper, .transport-file-wrapper, .penginapan-file-wrapper, .uang-harian-file-wrapper').addClass('d-none');
            newRow.find('.file-status-badge').removeClass('bg-success').addClass('bg-secondary').html('<i class="bi bi-paperclip"></i> SPT Kosong');
            newRow.find('.nip-input').val('').prop('readonly', true);
            newRow.find('.rekening-input').val('').prop('readonly', false);
            newRow.find('.rek-hint').addClass('d-none');

            newRow.find('.select2-container').remove();
            newRow.find('.select2-hidden-accessible').removeClass('select2-hidden-accessible').removeAttr('data-select2-id aria-hidden tabindex');

            let collapseId = 'collapsePeserta' + rowIdx;
            newRow.find('.collapse-trigger').attr('data-bs-target', '#' + collapseId);
            newRow.find('.peserta-collapse').attr('id', collapseId).addClass('show');

            newRow.find('input, select, textarea').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    name = name.replace(/\[\d+\]/g, '[' + rowIdx + ']');
                    $(this).attr('name', name);
                }
                $(this).removeAttr('id');
            });

            newRow.find('.btn-delete-row').prop('disabled', false);
            $('#pesertaRepeater').append(newRow);

            newRow.find('.select2').select2({ theme: 'bootstrap-5', width: '100%' });

            updateRowNumbers();
            $('.btn-delete-row').prop('disabled', false);
            rowIdx++;
            updateProgress();
        });

        $(document).on('click', '.btn-delete-row', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if ($('.item-row').length > 1) {
                $(this).closest('.item-row').remove();
                updateRowNumbers();
                calculateGrandTotal();
                if ($('.item-row').length === 1) {
                    $('.btn-delete-row').prop('disabled', true);
                }
                updateProgress();
            }
        });

        $(document).on('change', '.provinsi-select, .tipe-select', function() {
            calculateUangHarian($(this).closest('.item-row'));
        });
        $(document).on('input', '.lama-hari-input', function() {
            calculateUangHarian($(this).closest('.item-row'));
        });

        $(document).on('change', '.pegawai-select', function() {
            let card = $(this).closest('.item-row');
            let selected = $(this).find(':selected');
            let nama = selected.data('nama') || '';
            let nip = selected.data('nip') || '';
            let rek = selected.data('rek') || '';

            card.find('.input-nama-hidden').val(nama);
            card.find('.nip-input').val(nip);

            if (rek) {
                card.find('.rekening-input').val(rek).prop('readonly', false);
                card.find('.rek-hint').addClass('d-none');
            } else {
                card.find('.rekening-input').val('').prop('readonly', false);
                if (selected.val() !== '') {
                    card.find('.rek-hint').removeClass('d-none');
                } else {
                    card.find('.rek-hint').addClass('d-none');
                }
            }

            card.find('.summary-nama').text(nama || '-');
            updateProgress();
        });

        $(document).on('input', '.input-tujuan', function() {
            $(this).closest('.item-row').find('.summary-tujuan').text($(this).val() || '-');
        });

        $(document).on('change', '.spt-file-input', function() {
            let card = $(this).closest('.item-row');
            let badge = card.find('.file-status-badge');
            if (this.files && this.files.length > 0) {
                badge.removeClass('bg-secondary text-secondary border-secondary').addClass('bg-success text-white').html('<i class="bi bi-paperclip"></i> SPT: ' + this.files[0].name.substring(0, 15) + '...');
            } else {
                badge.removeClass('bg-success text-white').addClass('bg-secondary text-white').html('<i class="bi bi-paperclip"></i> SPT Kosong');
            }
        });

        $(document).on('click', '.collapse-trigger', function() {
            let icon = $(this).find('.toggle-icon');
            if ($(this).attr('aria-expanded') === 'true') {
                icon.removeClass('bi-chevron-down').addClass('bi-chevron-right');
            } else {
                icon.removeClass('bi-chevron-right').addClass('bi-chevron-down');
            }
        });

        function updateRowNumbers() {
            $('.item-row').each(function (index) {
                $(this).find('.row-number').text(index + 1);
            });
            calculateGrandTotal();
        }

        @if($errors->any())
            $('.is-invalid').closest('.peserta-collapse').addClass('show');
            $('.is-invalid').closest('.item-row').addClass('border border-danger');
        @endif

        updateRowNumbers();
        updateProgress();
    });
</script>
