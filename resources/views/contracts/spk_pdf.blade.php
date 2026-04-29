<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>SPK {{ $kontrak->nomor_spk }}</title>
    <style>
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 14px;
            line-height: 1.5;
            color: #000;
        }

        .spk-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid black;
            margin-bottom: 20px;
        }

        .spk-table td {
            border: 1px solid black;
            padding: 8px 12px;
            vertical-align: top;
        }

        .spk-table .no-border-table td {
            border: none;
            padding: 2px;
        }

        ul {
            list-style-type: lower-alpha;
            padding-left: 20px;
            margin-top: 5px;
            margin-bottom: 5px;
        }

        ol {
            text-align: justify;
            padding-left: 20px;
        }

        ol li {
            margin-bottom: 12px;
        }

        .signatures {
            width: 100%;
            border: none;
            margin-top: 20px;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            border: none;
        }

        .page-break {
            page-break-before: always;
        }

        .rab-image {
            max-width: 100%;
            max-height: 520px;
            display: block;
            margin: 8px auto 0;
        }
    </style>
</head>

<body>
    <table class="spk-table">
        <tr>
            <td style="width: 40%; text-align: center; font-weight: bold; font-size: 16px; padding: 15px;">
                <br>SURAT PERINTAH KERJA<br>(SPK)<br>
            </td>
            <td style="width: 60%;">
                <div style="font-size: 14px;">SATUAN KERJA :</div>
                <div style="font-weight: bold; font-size: 14px;">Badan Layanan Umum Kantor UPBU Kelas I A.P.T. Pranoto Samarinda</div>
            </td>
        </tr>
        <tr>
            <td style="text-align: center;">
                <div style="margin-bottom: 5px;">NOMOR DAN TANGGAL SPK :</div>
                <div style="font-weight: bold;">{{ $kontrak->nomor_spk ?? '-' }}</div>
                <div>{{ $kontrak->tanggal_spk ? \Carbon\Carbon::parse($kontrak->tanggal_spk)->translatedFormat('d F Y') : '-' }}</div>
            </td>
            <td>
                <table class="no-border-table" style="width: 100%;">
                    <tr>
                        <td style="width: 110px;">Nama PPK</td>
                        <td style="width: 10px;">:</td>
                        <td style="font-weight: bold;">{{ strtoupper($kontrak->nama_ppk ?? '-') }}</td>
                    </tr>
                    <tr>
                        <td>Nama Penyedia</td>
                        <td>:</td>
                        <td style="font-weight: bold;">{{ strtoupper($vendor->nama_pihak ?? '-') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div>PAKET PENGADAAN :</div>
                <div style="font-weight: bold;">{{ $kontrak->nama_pekerjaan ?? '-' }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div>NOMOR SURAT UNDANGAN PENGADAAN LANGSUNG :</div>
                <div style="font-weight: bold;">{{ $kontrak->nomor_surat_undangan_pengadaan ?? '-' }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div>NOMOR BERITA ACARA HASIL PENGADAAN LANGSUNG :</div>
                <div style="font-weight: bold;">{{ $kontrak->nomor_ba_hasil_pengadaan ?? '-' }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: justify;">
                <strong>SUMBER DANA:</strong> DIPA Tahun Anggaran {{ $dipa->tahun_anggaran ?? '-' }} BLU Kantor UPBU Kelas I A.P.T. Pranoto Nomor : {{ $dipa->nomor_dipa ?? '-' }} tanggal {{ $dipa && $dipa->tanggal_disahkan ? \Carbon\Carbon::parse($dipa->tanggal_disahkan)->translatedFormat('d F Y') : '-' }} untuk Mata Anggaran Kegiatan {{ $coa->kode_mak_lengkap ?? '-' }}.
            </td>
        </tr>
        <tr>
            <td colspan="2">
                Nilai Kontrak termasuk Pajak Pertambahan Nilai (PPN) adalah sebesar <strong>Rp {{ number_format((float) ($kontrak->nilai_total_kontrak ?? 0), 0, ',', '.') }}</strong> ({{ $terbilangNilaiKontrak ?? '-' }} rupiah).
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <strong>GAMBAR RAB:</strong>
                <img src="{{ $gambarRabDataUri }}" alt="Gambar RAB" class="rab-image">
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <strong>JENIS KONTRAK:</strong> {{ strtoupper($kontrak->metode_pembayaran ?? 'LUMPSUM') }}
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <strong>WAKTU PELAKSANAAN PEKERJAAN:</strong> {{ $kontrak->tanggal_mulai ? \Carbon\Carbon::parse($kontrak->tanggal_mulai)->translatedFormat('d F Y') : '-' }} - {{ $kontrak->tanggal_selesai ? \Carbon\Carbon::parse($kontrak->tanggal_selesai)->translatedFormat('d F Y') : '-' }} ({{ $kontrak->jangka_waktu ?? '-' }} {{ strtolower($kontrak->satuan_waktu ?? '') }})
            </td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td>
                Untuk dan atas nama<br>
                BLU Kantor UPBU Kelas I A.P.T.Pranoto<br>
                Pejabat Pembuat Komitmen<br>
                <br><br><br><br><br>
                <b><u>{{ $kontrak->nama_ppk ?? '-' }}</u></b><br>
                NIP. {{ $kontrak->nip_ppk ?? '-' }}
            </td>
            <td>
                Untuk dan atas nama Penyedia<br>
                <b>{{ strtoupper($vendor->nama_pihak ?? '-') }}</b><br>
                <br>
                <div style="font-size: 10px; color: gray;">Materai 10.000</div>
                <br><br>
                <b><u>{{ $vendor->nama_penanggung_jawab ?? '-' }}</u></b><br>
                Direktur
            </td>
        </tr>
    </table>

    <div class="page-break" style="text-align: center; font-weight: bold; margin-bottom: 20px;">
        SYARAT UMUM<br>
        SURAT PERINTAH KERJA (SPK)
    </div>

    <ol>
        <li><b>LINGKUP PEKERJAAN</b><br>
            Penyedia yang ditunjuk berkewajiban untuk menyelesaikan pekerjaan dalam jangka waktu yang ditentukan sesuai dengan volume, spesifikasi teknis dan harga yang tercantum dalam SPK.
        </li>

        <li><b>HUKUM YANG BERLAKU</b><br>
            Keabsahan, interpretasi, dan pelaksanaan SPK ini didasarkan kepada hukum Republik Indonesia.
        </li>

        <li><b>HARGA SPK</b><br>
            Harga SPK telah memperhitungkan keuntungan, beban pajak dan biaya overhead serta biaya asuransi (apabila dipersyaratkan). Rincian harga SPK sesuai dengan rincian yang tercantum dalam daftar kuantitas dan harga.
        </li>

        <li><b>HAK KEPEMILIKAN</b><br>
            PPK berhak atas kepemilikan semua barang/bahan yang terkait langsung atau disediakan sehubungan dengan jasa yang diberikan oleh penyedia kepada PPK. Jika diminta oleh PPK maka penyedia berkewajiban untuk membantu secara optimal pengalihan hak kepemilikan tersebut kepada PPK sesuai dengan hukum yang berlaku.<br>
            Hak kepemilikan atas peralatan dan barang/bahan yang disediakan oleh PPK tetap pada PPK, dan semua peralatan tersebut harus dikembalikan kepada PPK pada saat SPK berakhir atau jika tidak diperlukan lagi oleh penyedia. Semua peralatan tersebut harus dikembalikan dalam kondisi yang sama pada saat diberikan kepada penyedia dengan pengecualian keausan akibat pemakaian yang wajar.
        </li>

        <li><b>CACAT MUTU</b><br>
            PPK akan memeriksa setiap hasil pekerjaan penyedia dan memberitahukan secara tertulis penyedia atas setiap cacat mutu yang ditemukan. PPK dapat memerintahkan penyedia untuk menguji pekerjaan yang dianggap oleh PPK mengandung cacat mutu. Penyedia bertanggung jawab atas cacat mutu selama masa garansi.
        </li>

        <li><b>PERPAJAKAN</b><br>
            Penyedia berkewajiban untuk membayar semua pajak, bea, retribusi, dan pungutan lain yang sah yang dibebankan oleh hukum yang berlaku atas pelaksanaan SPK. Semua pengeluaran perpajakan ini dianggap telah termasuk dalam harga SPK.
        </li>

        <li><b>PENGALIHAN DAN/ATAU SUBKONTRAK</b><br>
            Penyedia dilarang untuk mengalihkan dan/atau mensubkontrakkan sebagian atau seluruh pekerjaan. Pengalihan seluruh pekerjaan hanya diperbolehkan dalam hal pergantian nama penyedia, baik sebagai akibat peleburan (merger) atau akibat lainnya.
        </li>

        <li><b>JADWAL</b><br>
            SPK ini berlaku efektif pada tanggal penandatanganan oleh para pihak atau pada tanggal yang ditetapkan dalam Surat Perintah Mulai Kerja.<br>
            Waktu pelaksanaan SPK adalah sejak tanggal mulai kerja yang tercantum dalam Surat Perintah Mulai Kerja.<br>
            Penyedia harus menyelesaikan pekerjaan sesuai jadwal yang ditentukan.<br>
            Apabila penyedia tidak dapat menyelesaikan pekerjaan sesuai jadwal karena keadaan diluar pengendaliannya dan penyedia telah melaporkan kejadian tersebut kepada PPK, maka PPK dapat melakukan penjadwalan kembali pelaksanaan tugas penyedia dengan adendum SPK.
        </li>

        <li><b>ASURANSI</b><br>
            Apabila dipersyaratkan, penyedia wajib menyediakan asuransi sejak Surat Perintah Mulai Kerja sampai dengan tanggal selesainya pemeliharaan untuk:
            <ul>
                <li>semua barang dan peralatan yang mempunyai risiko tinggi terjadinya kecelakaan, pelaksanaan pekerjaan, serta pekerja untuk pelaksanaan pekerjaan, atas segala risiko terhadap kecelakaan, kerusakan, kehilangan, serta risiko lain yang tidak dapat diduga;</li>
                <li>pihak ketiga sebagai akibat kecelakaan di tempat kerjanya; dan</li>
                <li>Besarnya asuransi sudah diperhitungkan dalam penawaran dan termasuk dalam harga SPK.</li>
            </ul>
        </li>

        <li><b>PENANGGUNGAN DAN RISIKO</b><br>
            Penyedia berkewajiban untuk melindungi, membebaskan, dan menanggung tanpa batas PPK beserta instansinya terhadap semua bentuk tuntutan, tanggung jawab, kewajiban, kehilangan, kerugian, denda, gugatan atau tuntutan hukum, proses pemeriksaan hukum, dan biaya yang dikenakan terhadap PPK beserta instansinya (kecuali kerugian yang mendasari tuntutan tersebut disebabkan kesalahan atau kelalaian berat PPK) sehubungan dengan klaim yang timbul dari hal-hal berikut terhitung sejak tanggal mulai kerja sampai dengan tanggal penandatanganan berita acara penyerahan akhir:
            <ul>
                <li>kehilangan atau kerusakan peralatan dan harta benda penyedia dan Personel;</li>
                <li>cidera tubuh, sakit atau kematian Personel; dan/atau</li>
                <li>kehilangan atau kerusakan harta benda, cidera tubuh, sakit atau kematian pihak lain.</li>
            </ul>
            Terhitung sejak tanggal mulai kerja sampai dengan tanggal penandatanganan berita acara serah terima, semua risiko kehilangan atau kerusakan hasil pekerjaan ini merupakan risiko penyedia, kecuali kerugian atau kerusakan tersebut diakibatkan oleh kesalahan atau kelalaian PPK.<br>
            Pertanggungan asuransi yang dimiliki oleh penyedia tidak membatasi kewajiban penanggungan dalam syarat ini.<br>
            Kehilangan atau kerusakan terhadap hasil pekerjaan sejak tanggal mulai kerja sampai batas akhir garansi, harus diperbaiki, diganti atau dilengkapi oleh penyedia atas tanggungannya sendiri jika kehilangan atau kerusakan tersebut terjadi akibat tindakan atau kelalaian penyedia.
        </li>

        <li><b>PENGAWASAN DAN PEMERIKSAAN</b><br>
            PPK berwenang melakukan pengawasan dan pemeriksaan terhadap pelaksanaan pekerjaan yang dilaksanakan oleh penyedia. PPK dapat memerintahkan kepada pihak lain untuk melakukan pengawasan dan pemeriksaan atas semua pelaksanaan pekerjaan yang dilaksanakan oleh penyedia.
        </li>

        <li><b>PENGUJIAN</b><br>
            Jika PPK atau Pengawas Pekerjaan memerintahkan penyedia untuk melakukan pengujian Cacat Mutu yang tidak tercantum dalam Spesifikasi Teknis dan Gambar, dan hasil uji coba menunjukkan adanya Cacat Mutu maka penyedia berkewajiban untuk menanggung biaya pengujian tersebut. Jika tidak ditemukan adanya Cacat Mutu maka uji coba tersebut dianggap sebagai Peristiwa Kompensasi.
        </li>

        <li><b>LAPORAN HASIL PEKERJAAN</b><br>
            Pemeriksaan pekerjaan dilakukan selama pelaksanaan Kontrak terhadap kemajuan pekerjaan dalam rangka pengawasan kualitas dan waktu pelaksanaan pekerjaan. Hasil pemeriksaan pekerjaan dituangkan dalam laporan kemajuan hasil pekerjaan.<br>
            Untuk merekam pelaksanaan pekerjaan, PPK dapat menugaskan Pengawas Pekerjaan dan/atau tim teknis membuat foto-foto dokumentasi pelaksanaan pekerjaan di lokasi pekerjaan.
        </li>

        <li><b>WAKTU PENYELESAIAN PEKERJAAN</b><br>
            Kecuali SPK diputuskan lebih awal, penyedia berkewajiban untuk memulai pelaksanaan pekerjaan pada tanggal mulai kerja, dan melaksanakan pekerjaan sesuai dengan program mutu, serta menyelesaikan pekerjaan selambat-lambatnya pada tanggal penyelesaian yang ditetapkan dalam Surat Perintah Mulai Kerja.<br>
            Jika pekerjaan tidak selesai pada tanggal penyelesaian disebabkan karena kesalahan atau kelalaian penyedia maka penyedia dikenakan sanksi berupa denda keterlambatan.<br>
            Jika keterlambatan tersebut disebabkan oleh Peristiwa Kompensasi maka PPK memberikan tambahan perpanjangan waktu penyelesaian pekerjaan.<br>
            Tanggal penyelesaian yang dimaksud dalam ketentuan ini adalah tanggal penyelesaian semua pekerjaan.
        </li>

        <li><b>SERAH TERIMA PEKERJAAN</b><br>
            Setelah pekerjaan selesai 100% (seratus persen), penyedia mengajukan permintaan secara tertulis kepada PPK untuk penyerahan pekerjaan.<br>
            Sebelum dilakukan serah terima, PPK melakukan pemeriksaan terhadap hasil pekerjaan.<br>
            PPK dalam melakukan pemeriksaan hasil pekerjaan dapat dibantu oleh pengawas pekerjaan dan/atau tim teknis.<br>
            Apabila terdapat kekurangan-kekurangan dan/atau cacat hasil pekerjaan, penyedia wajib memperbaiki/menyelesaikannya, atas perintah PPK.<br>
            PPK menerima hasil pekerjaan setelah seluruh hasil pekerjaan dilaksanakan sesuai dengan ketentuan SPK.<br>
            Pembayaran dilakukan sebesar 100% (seratus persen) dari harga SPK dan penyedia harus menyerahkan Sertifikat Garansi.
        </li>

        <li><b>JAMINAN BEBAS CACAT MUTU/GARANSI</b><br>
            Penyedia dengan jaminan pabrikan dari produsen pabrikan (jika ada) berkewajiban untuk menjamin bahwa selama penggunaan secara wajar, Barang tidak mengandung cacat mutu yang disebabkan oleh tindakan atau kelalaian Penyedia, atau cacat mutu akibat desain, bahan, dan cara kerja.<br>
            Jaminan bebas cacat mutu ini berlaku selama masa garansi berlaku.<br>
            PPK akan menyampaikan pemberitahuan cacat mutu kepada Penyedia segera setelah ditemukan cacat mutu tersebut selama masa garansi berlaku.<br>
            Terhadap pemberitahuan cacat mutu oleh PPK, Penyedia berkewajiban untuk memperbaiki, mengganti, dan/atau melengkapi Barang dalam jangka waktu sesuai dengan syarat dan ketentuan dalam Sertifikat Garansi.<br>
            Jika Penyedia tidak memperbaiki, mengganti, atau melengkapi Barang akibat cacat mutu dalam jangka waktu sesuai dengan syarat dan ketentuan dalam Sertifikat Garansi, PPK akan menghitung biaya perbaikan yang diperlukan, dan PPK secara langsung atau melalui pihak ketiga yang ditunjuk oleh PPK akan melakukan perbaikan tersebut. Penyedia berkewajiban untuk membayar biaya perbaikan atau penggantian tersebut sesuai dengan klaim yang diajukan secara tertulis oleh PPK.<br>
            Selain kewajiban penggantian biaya, Penyedia yang lalai memperbaiki cacat mutu dikenakan Sanksi Daftar Hitam.
        </li>

        <li><b>PERUBAHAN SPK</b><br>
            SPK hanya dapat diubah melalui adendum SPK.<br>
            Perubahan SPK dapat dilaksanakan dalam hal terdapat perbedaan antara kondisi lapangan pada saat pelaksanaan dengan SPK dan disetujui oleh para pihak, meliputi:
            <ul>
                <li>menambah atau mengurangi volume yang tercantum dalam SPK;</li>
                <li>menambah dan/atau mengurangi jenis kegiatan;</li>
                <li>mengubah spesifikasi teknis sesuai dengan kondisi lapangan; dan/atau</li>
                <li>mengubah jadwal pelaksanaan pekerjaan.</li>
            </ul>
            Untuk kepentingan perubahan SPK, PPK dapat dibantu Pejabat Peneliti Pelaksanaan Kontrak.
        </li>

        <li><b>PERISTIWA KOMPENSASI</b><br>
            Peristiwa Kompensasi dapat diberikan kepada penyedia dalam hal sebagai berikut:
            <ul>
                <li>PPK mengubah jadwal yang dapat mempengaruhi pelaksanaan pekerjaan;</li>
                <li>keterlambatan pembayaran kepada penyedia;</li>
                <li>PPK tidak memberikan gambar-gambar, spesifikasi dan/atau instruksi sesuai jadwal yang dibutuhkan;</li>
                <li>penyedia belum bisa masuk ke lokasi sesuai jadwal;</li>
                <li>PPK menginstruksikan kepada pihak penyedia untuk melakukan pengujian tambahan yang setelah dilaksanakan pengujian ternyata tidak ditemukan kerusakan/kegagalan/penyimpangan;</li>
                <li>PPK memerintahkan penundaan pelaksanaan pekerjaan;</li>
                <li>PPK memerintahkan untuk mengatasi kondisi tertentu yang tidak dapat diduga sebelumnya dan disebabkan oleh PPK;</li>
                <li>ketentuan lain dalam SPK.</li>
            </ul>
            Jika Peristiwa Kompensasi mengakibatkan pengeluaran tambahan dan/atau keterlambatan penyelesaian pekerjaan maka PPK berkewajiban untuk membayar ganti rugi dan/atau memberikan perpanjangan waktu penyelesaian pekerjaan.<br>
            Ganti rugi hanya dapat dibayarkan jika berdasarkan data penunjang dan perhitungan kompensasi yang diajukan oleh penyedia kepada PPK, dapat dibuktikan kerugian nyata akibat Peristiwa Kompensasi.<br>
            Perpanjangan waktu penyelesaian pekerjaan hanya dapat diberikan jika berdasarkan data penunjang dan perhitungan kompensasi yang diajukan oleh penyedia kepada PPK, dapat dibuktikan perlunya tambahan waktu akibat Peristiwa Kompensasi.<br>
            Penyedia tidak berhak atas ganti rugi dan/atau perpanjangan waktu penyelesaian pekerjaan jika penyedia gagal atau lalai untuk memberikan peringatan dini dalam mengantisipasi atau mengatasi dampak Peristiwa Kompensasi.
        </li>

        <li><b>PERPANJANGAN WAKTU</b><br>
            Jika terjadi Peristiwa Kompensasi sehingga penyelesaian pekerjaan akan melampaui tanggal penyelesaian maka penyedia berhak untuk meminta perpanjangan tanggal penyelesaian berdasarkan data penunjang. PPK berdasarkan pertimbangan Pengawas Pekerjaan memperpanjang tanggal penyelesaian pekerjaan secara tertulis. Perpanjangan tanggal penyelesaian harus dilakukan melalui adendum SPK.<br>
            PPK dapat menyetujui perpanjangan waktu pelaksanaan setelah melakukan penelitian terhadap usulan tertulis yang diajukan oleh penyedia.
        </li>

        <li><b>PENGHENTIAN DAN PEMUTUSAN SPK</b><br>
            Penghentian SPK dapat dilakukan karena terjadi Keadaan Kahar.<br>
            Dalam hal SPK dihentikan, PPK wajib membayar kepada penyedia sesuai dengan prestasi pekerjaan yang telah dicapai, termasuk:
            <ul>
                <li>biaya langsung pengadaan bahan dan perlengkapan untuk pekerjaan ini. Bahan dan perlengkapan ini harus diserahkan oleh Penyedia kepada PPK, dan selanjutnya menjadi hak milik PPK;</li>
                <li>biaya langsung demobilisasi personel.</li>
            </ul>
            Pemutusan SPK dapat dilakukan oleh pihak PPK atau pihak penyedia.<br>
            Menyimpang dari Pasal 1266 dan 1267 Kitab Undang-Undang Hukum Perdata, pemutusan SPK melalui pemberitahuan tertulis dapat dilakukan apabila:
            <ul>
                <li>penyedia terbukti melakukan KKN, kecurangan dan/atau pemalsuan dalam proses Pengadaan yang diputuskan oleh instansi yang berwenang;</li>
                <li>pengaduan tentang penyimpangan prosedur, dugaan KKN dan/atau pelanggaran persaingan sehat dalam pelaksanaan pengadaan dinyatakan benar oleh instansi yang berwenang;</li>
                <li>penyedia lalai/cidera janji dalam melaksanakan kewajibannya dan tidak memperbaiki kelalaiannya dalam jangka waktu yang telah ditetapkan;</li>
                <li>penyedia tanpa persetujuan PPK, tidak memulai pelaksanaan pekerjaan;</li>
                <li>penyedia menghentikan pekerjaan dan penghentian ini tidak tercantum dalam program mutu serta tanpa persetujuan PPK;</li>
                <li>penyedia berada dalam keadaan pailit;</li>
                <li>Penyedia gagal memperbaiki kinerja setelah mendapat Surat Peringatan sebanyak 3 (tiga) kali;</li>
                <li>penyedia selama Masa SPK gagal memperbaiki Cacat Mutu dalam jangka waktu yang ditetapkan oleh PPK;</li>
                <li>PPK memerintahkan penyedia untuk menunda pelaksanaan atau kelanjutan pekerjaan, dan perintah tersebut tidak ditarik selama 28 (dua puluh delapan) hari; dan/atau</li>
                <li>PPK tidak menerbitkan surat perintah pembayaran untuk pembayaran tagihan angsuran sesuai dengan yang disepakati sebagaimana tercantum dalam SPK.</li>
            </ul>
            Dalam hal pemutusan SPK dilakukan karena kesalahan penyedia:
            <ul>
                <li>Sisa uang muka harus dilunasi oleh Penyedia atau Jaminan Uang Muka dicairkan (apabila diberikan);</li>
                <li>penyedia membayar denda keterlambatan (apabila ada); dan/atau</li>
                <li>penyedia dikenakan Sanksi Daftar Hitam.</li>
            </ul>
            Dalam hal pemutusan SPK dilakukan karena PPK terlibat penyimpangan prosedur, melakukan KKN dan/atau pelanggaran persaingan sehat dalam pelaksanaan pengadaan, maka PPK dikenakan sanksi berdasarkan peraturan perundang-undangan.
        </li>

        <li><b>PEMBAYARAN</b><br>
            Pembayaran prestasi hasil pekerjaan yang disepakati dilakukan oleh PPK, dengan ketentuan:
            <ul>
                <li>penyedia telah mengajukan tagihan disertai laporan kemajuan hasil pekerjaan;</li>
                <li>pembayaran dilakukan dengan sistem sekaligus / sistem termin;</li>
                <li>pembayaran harus dipotong denda (apabila ada), dan pajak;</li>
                <li>pembayaran terakhir hanya dilakukan setelah pekerjaan selesai 100% (seratus persen) dan Berita Acara Serah Terima ditandatangani.</li>
            </ul>
            PPK dalam kurun waktu 7 (tujuh) hari kerja setelah pengajuan permintaan pembayaran dari penyedia harus sudah mengajukan surat permintaan pembayaran kepada Pejabat Penandatangan Surat Perintah Membayar (PPSPM).<br>
            Bila terdapat ketidaksesuaian dalam perhitungan angsuran, tidak akan menjadi alasan untuk menunda pembayaran. PPK dapat meminta penyedia untuk menyampaikan perhitungan prestasi sementara dengan mengesampingkan hal-hal yang sedang menjadi perselisihan.
        </li>

        <li><b>DENDA</b><br>
            Jika pekerjaan tidak dapat diselesaikan dalam jangka waktu pelaksanaan pekerjaan karena kesalahan atau kelalaian Penyedia maka Penyedia berkewajiban untuk membayar denda kepada PPK sebesar 1/1000 (satu permil) dari nilai SPK (tidak termasuk PPN) untuk setiap hari keterlambatan.<br>
            PPK mengenakan Denda dengan memotong pembayaran prestasi pekerjaan penyedia. Pembayaran Denda tidak mengurangi tanggung jawab kontraktual penyedia.
        </li>

        <li><b>PENYELESAIAN PERSELISIHAN</b><br>
            PPK dan penyedia berkewajiban untuk berupaya sungguh-sungguh menyelesaikan secara damai semua perselisihan yang timbul dari atau berhubungan dengan SPK ini atau interpretasinya selama atau setelah pelaksanaan pekerjaan. Jika perselisihan tidak dapat diselesaikan secara musyawarah maka perselisihan akan diselesaikan melalui Layanan Penyelesaian Sengketa, arbitrase atau Pengadilan Negeri.
        </li>

        <li><b>LARANGAN PEMBERIAN KOMISI</b><br>
            Penyedia menjamin bahwa tidak satu pun personel satuan kerja PPK telah atau akan menerima komisi atau keuntungan tidak sah lainnya baik langsung maupun tidak langsung dari SPK ini. Penyedia menyetujui bahwa pelanggaran syarat ini merupakan pelanggaran yang mendasar terhadap SPK ini.
        </li>
    </ol>

</body>
</html>
