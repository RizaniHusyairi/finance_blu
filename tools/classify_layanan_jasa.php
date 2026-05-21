<?php

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$input = __DIR__ . '/../Update Tarif Layanan Spesifik BLU UPBU 111.xlsx';
$outputDir = __DIR__ . '/../outputs';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$sheetName = $argv[1] ?? 'UPBU APT PRANOTO';
$spreadsheet = IOFactory::load($input);
$sheet = $spreadsheet->getSheetByName($sheetName);
if (!$sheet) {
    fwrite(STDERR, "Sheet tidak ditemukan: {$sheetName}\n");
    exit(1);
}

function clean_name(?string $value): string
{
    $value = (string) $value;
    $value = str_replace("\xc2\xa0", ' ', $value);
    $value = preg_replace('/\s+/u', ' ', trim($value));
    $value = preg_replace('/^[A-Z]\.\s*/u', '', $value);
    $value = preg_replace('/^\d+[\.\)]\s*/u', '', $value);
    $value = preg_replace('/^[a-z]\)\s*/iu', '', $value);
    $value = preg_replace('/^[a-z]\.\s*/iu', '', $value);
    return trim($value);
}

function title_case_id(string $value): string
{
    $value = mb_strtolower($value, 'UTF-8');
    $small = ['dan', 'atau', 'di', 'ke', 'dari', 'yang', 's.d'];
    $parts = explode(' ', $value);
    foreach ($parts as &$part) {
        if ($part === '') {
            continue;
        }
        if (!in_array($part, $small, true)) {
            $part = mb_convert_case($part, MB_CASE_TITLE, 'UTF-8');
        }
    }
    return implode(' ', $parts);
}

function classify_service(string $path, string $name, ?string $unit): array
{
    $haystack = mb_strtolower($path . ' ' . $name . ' ' . ($unit ?? ''), 'UTF-8');

    $jenis = 'E. Layanan Lainnya';
    $kategori = 'Perlu Verifikasi';
    $sub = '';
    $dasar = 'Berdasarkan satuan/objek tarif pada daftar tarif layanan BLU/UPBU';
    $ket = 'Perlu konfirmasi unit teknis atas penggunaan layanan.';
    $catatan = '';

    $rules = [
        ['rx' => '/penumpang|passenger|psc|pjp2u|pajak bandara/u', 'jenis' => 'A. Layanan Jasa Kebandarudaraan', 'kategori' => 'Pelayanan penumpang', 'sub' => 'Jasa pelayanan penumpang pesawat udara', 'ket' => 'Dikenakan atas pelayanan kepada penumpang melalui terminal bandar udara.'],
        ['rx' => '/pendaratan|landing|bobot pesawat|ton|kg/u', 'jenis' => 'A. Layanan Jasa Kebandarudaraan', 'kategori' => 'Pelayanan pesawat udara', 'sub' => 'Jasa pendaratan pesawat udara', 'ket' => 'Dikenakan kepada operator pesawat berdasarkan berat pesawat dan/atau pergerakan pendaratan.'],
        ['rx' => '/penempatan|penyimpanan pesawat|parkir pesawat|parking pesawat|apron|hanggar/u', 'jenis' => 'A. Layanan Jasa Kebandarudaraan', 'kategori' => 'Pelayanan apron, parkir, dan penempatan pesawat', 'sub' => 'Penempatan/parkir/penyimpanan pesawat', 'ket' => 'Dikenakan atas penggunaan apron, area parkir, atau fasilitas penempatan pesawat.'],
        ['rx' => '/garbarata|aviobridge|jembatan udara/u', 'jenis' => 'A. Layanan Jasa Kebandarudaraan', 'kategori' => 'Pelayanan fasilitas sisi udara dan sisi darat', 'sub' => 'Penggunaan garbarata', 'ket' => 'Dikenakan atas penggunaan fasilitas garbarata/jembatan penumpang.'],
        ['rx' => '/konter check|check[ -]?in|baggage|bagasi|timbangan/u', 'jenis' => 'A. Layanan Jasa Kebandarudaraan', 'kategori' => 'Pelayanan fasilitas sisi udara dan sisi darat', 'sub' => 'Fasilitas pelayanan terminal', 'ket' => 'Dikenakan atas penggunaan fasilitas operasional terminal untuk pelayanan penumpang/bagasi.'],
        ['rx' => '/luar jam operasi|stand[ -]?by alternate|alternate aerodrome|fids|flight information display/u', 'jenis' => 'A. Layanan Jasa Kebandarudaraan', 'kategori' => 'Pelayanan fasilitas sisi udara dan sisi darat', 'sub' => 'Fasilitas operasional bandara', 'ket' => 'Dikenakan atas penggunaan fasilitas atau dukungan operasional bandara sesuai kondisi layanan.'],
        ['rx' => '/kargo|cargo|pos|gudang kargo/u', 'jenis' => 'A. Layanan Jasa Kebandarudaraan', 'kategori' => 'Pelayanan kargo/pos', 'sub' => 'Jasa pelayanan kargo dan pos', 'ket' => 'Dikenakan atas pelayanan atau pemanfaatan fasilitas kargo/pos di bandara.'],
        ['rx' => '/parkir utama|parkir vip|parkir inap|parkir kendaraan|kendaraan bermotor|roda dua|roda empat/u', 'jenis' => 'C. Layanan Jasa Komersial', 'kategori' => 'Parkir kendaraan', 'sub' => 'Parkir kendaraan darat', 'ket' => 'Dikenakan kepada pengguna fasilitas parkir kendaraan di lingkungan bandara.'],
        ['rx' => '/konsesi|royalti|revenue sharing|bagi hasil/u', 'jenis' => 'C. Layanan Jasa Komersial', 'kategori' => 'Konsesi usaha', 'sub' => 'Konsesi kegiatan usaha', 'ket' => 'Dikenakan atas hak penyelenggaraan usaha/komersial di lingkungan bandara.'],
        ['rx' => '/shooting|pemotretan|promosi|reklame|iklan|media promosi|banner|videotron|billboard|neon box/u', 'jenis' => 'B. Layanan Jasa Penunjang Kebandarudaraan', 'kategori' => 'Layanan reklame/media promosi', 'sub' => 'Media promosi dan publikasi', 'ket' => 'Dikenakan atas pemasangan, pengambilan gambar, atau penayangan media promosi di area bandara.'],
        ['rx' => '/sewa ruang|ruang|tempat|kantor|counter|loket|meja/u', 'jenis' => 'B. Layanan Jasa Penunjang Kebandarudaraan', 'kategori' => 'Sewa ruang/tempat', 'sub' => 'Pemanfaatan ruang/tempat', 'ket' => 'Dikenakan atas pemanfaatan ruang, tempat, loket, kantor, atau area tertentu di lingkungan bandara.'],
        ['rx' => '/sewa lahan|lahan|tanah/u', 'jenis' => 'B. Layanan Jasa Penunjang Kebandarudaraan', 'kategori' => 'Sewa lahan', 'sub' => 'Pemanfaatan lahan', 'ket' => 'Dikenakan atas pemanfaatan lahan/tanah di lingkungan bandara.'],
        ['rx' => '/listrik|air|telepon|utilitas|genset|internet/u', 'jenis' => 'B. Layanan Jasa Penunjang Kebandarudaraan', 'kategori' => 'Penggunaan utilitas', 'sub' => 'Utilitas bandara', 'ket' => 'Dikenakan atas penggunaan utilitas yang disediakan oleh pengelola bandara.'],
        ['rx' => '/kendaraan|peralatan|forklift|bus|ambulance|ambu?lans|pemadam|pkp|toilet car|ground support|push back|traktor pendorong/u', 'jenis' => 'B. Layanan Jasa Penunjang Kebandarudaraan', 'kategori' => 'Layanan kendaraan/peralatan', 'sub' => 'Kendaraan/peralatan operasional', 'ket' => 'Dikenakan atas penggunaan kendaraan atau peralatan milik pengelola bandara.'],
        ['rx' => '/tenant|gerai|toko|restoran|kafe|cafe|kantin|atm|retail/u', 'jenis' => 'C. Layanan Jasa Komersial', 'kategori' => 'Tenant/gerai', 'sub' => 'Tenant dan gerai komersial', 'ket' => 'Dikenakan atas kegiatan tenant/gerai di area komersial bandara.'],
        ['rx' => '/taksi|taxi|angkutan|transportasi|rental|sewa kendaraan/u', 'jenis' => 'C. Layanan Jasa Komersial', 'kategori' => 'Usaha transportasi/taksi/sewa kendaraan', 'sub' => 'Transportasi darat komersial', 'ket' => 'Dikenakan atas kegiatan/akses usaha transportasi darat di bandara.'],
        ['rx' => '/e-pas|pas bandara|izin masuk|daerah keamanan terbatas|dkt|security restricted area|sra/u', 'jenis' => 'D. Layanan Administrasi dan Dokumen', 'kategori' => 'Penerbitan izin/pas', 'sub' => 'Pas/izin masuk daerah keamanan terbatas', 'ket' => 'Dikenakan atas penerbitan izin/pas untuk akses ke daerah keamanan terbatas.'],
        ['rx' => '/rekomendasi|legalisasi|pengesahan/u', 'jenis' => 'D. Layanan Administrasi dan Dokumen', 'kategori' => 'Legalisasi/rekomendasi', 'sub' => 'Rekomendasi/legalisasi', 'ket' => 'Dikenakan atas penerbitan rekomendasi, pengesahan, atau legalisasi dokumen.'],
        ['rx' => '/data|informasi|salinan|copy|dokumen/u', 'jenis' => 'D. Layanan Administrasi dan Dokumen', 'kategori' => 'Layanan data/informasi', 'sub' => 'Data/informasi/dokumen', 'ket' => 'Dikenakan atas penyediaan data, informasi, salinan, atau dokumen operasional.'],
        ['rx' => '/studi lapangan|observasi|penelitian/u', 'jenis' => 'E. Layanan Lainnya', 'kategori' => 'Layanan khusus', 'sub' => 'Kunjungan/studi lapangan', 'ket' => 'Dikenakan atas layanan kunjungan, studi lapangan, penelitian, atau observasi di lingkungan bandara.'],
    ];

    foreach ($rules as $rule) {
        if (preg_match($rule['rx'], $haystack)) {
            $jenis = $rule['jenis'];
            $kategori = $rule['kategori'];
            $sub = $rule['sub'];
            $ket = $rule['ket'];
            break;
        }
    }

    if (str_contains($haystack, 'per izin')) {
        $dasar = 'Per izin/per pas berdasarkan masa berlaku';
    } elseif (str_contains($haystack, 'm2') || str_contains($haystack, 'm²') || str_contains($haystack, 'meter persegi')) {
        $dasar = 'Luas area x jangka waktu pemanfaatan';
    } elseif (str_contains($haystack, '1000 kg') || str_contains($haystack, '1.000 kg') || str_contains($haystack, 'bagiannya')) {
        $dasar = 'Berat pesawat tiap 1.000 kg atau bagiannya';
    } elseif ($unit) {
        $dasar = 'Tarif per ' . $unit;
    }

    $formal = title_case_id(clean_name($name));
    if ($formal !== clean_name($name)) {
        $catatan = 'Rekomendasi penamaan: ' . $formal . '.';
    }

    if ($kategori === 'Perlu Verifikasi') {
        $catatan = trim($catatan . ' Kategori belum dapat ditentukan pasti dari nama layanan/satuan; verifikasi objek layanan dan unit pengelola.');
    }

    if (preg_match('/konsesi/u', $haystack) && preg_match('/ruang|tanah|iklan|reklame|space/u', $haystack)) {
        $catatan = trim($catatan . ' Potensi tumpang tindih: dapat dibaca sebagai pemanfaatan aset/media, tetapi kategori paling tepat adalah konsesi usaha karena objek tarif menyebut hak pengusahaan.');
    }

    return [$jenis, $kategori, $sub, $dasar, $ket, trim($catatan)];
}

$highestRow = $sheet->getHighestRow();
$stack = [];
$rows = [];
$allNodes = [];

for ($row = 2; $row <= $highestRow; $row++) {
    $levelRaw = $sheet->getCell('A' . $row)->getValue();
    $nameRaw = $sheet->getCell('B' . $row)->getValue();
    $name = clean_name($nameRaw);
    if ($name === '' || !is_numeric($levelRaw)) {
        continue;
    }

    $level = (int) $levelRaw;
    $unit = clean_name($sheet->getCell('D' . $row)->getValue());
    $tarif = $sheet->getCell('H' . $row)->getCalculatedValue();
    if ($tarif === null || $tarif === '') {
        $tarif = $sheet->getCell('G' . $row)->getCalculatedValue();
    }
    $kodeAkun = clean_name($sheet->getCell('L' . $row)->getValue());
    $kodeMak = clean_name($sheet->getCell('C' . $row)->getValue());

    $stack[$level] = $name;
    foreach (array_keys($stack) as $key) {
        if ($key > $level) {
            unset($stack[$key]);
        }
    }

    $allNodes[] = [
        'row' => $row,
        'level' => $level,
        'name' => $name,
        'unit' => $unit,
        'tarif' => $tarif,
        'kode_mak' => $kodeMak,
        'kode_akun' => $kodeAkun,
        'path' => implode(' > ', array_values($stack)),
    ];
}

$nodeCount = count($allNodes);
foreach ($allNodes as $idx => $node) {
    $next = $allNodes[$idx + 1] ?? null;
    $isLeaf = !$next || $next['level'] <= $node['level'];
    if (!$isLeaf && $node['unit'] === '' && ($node['tarif'] === null || $node['tarif'] === '')) {
        continue;
    }

    [$jenis, $kategori, $sub, $dasar, $ket, $catatan] = classify_service($node['path'], $node['name'], $node['unit']);
    $rows[] = [
        count($rows) + 1,
        $node['name'],
        $jenis,
        $kategori,
        $sub,
        $node['unit'] ?: '-',
        $dasar,
        $ket,
        $catatan,
        $node['path'],
        $node['tarif'],
        $node['kode_mak'] ?? '',
        $node['kode_akun'] ?? '',
    ];
}

$masterRows = [
    ['JKB', 'Layanan Jasa Kebandarudaraan', 'JKB-PNP', 'Pelayanan penumpang', 'JKB-PNP-001', 'Jasa pelayanan penumpang pesawat udara', 'aktif'],
    ['JKB', 'Layanan Jasa Kebandarudaraan', 'JKB-PSW', 'Pelayanan pesawat udara', 'JKB-PSW-001', 'Jasa pendaratan pesawat udara', 'aktif'],
    ['JKB', 'Layanan Jasa Kebandarudaraan', 'JKB-KRG', 'Pelayanan kargo/pos', 'JKB-KRG-001', 'Jasa pelayanan kargo dan pos', 'aktif'],
    ['JKB', 'Layanan Jasa Kebandarudaraan', 'JKB-APR', 'Pelayanan apron, parkir, dan penempatan pesawat', 'JKB-APR-001', 'Penempatan/parkir pesawat', 'aktif'],
    ['JKB', 'Layanan Jasa Kebandarudaraan', 'JKB-FAS', 'Pelayanan fasilitas sisi udara dan sisi darat', 'JKB-FAS-001', 'Fasilitas pelayanan terminal', 'aktif'],
    ['JPK', 'Layanan Jasa Penunjang Kebandarudaraan', 'JPK-RNG', 'Sewa ruang/tempat', 'JPK-RNG-001', 'Pemanfaatan ruang/tempat', 'aktif'],
    ['JPK', 'Layanan Jasa Penunjang Kebandarudaraan', 'JPK-LHN', 'Sewa lahan', 'JPK-LHN-001', 'Pemanfaatan lahan', 'aktif'],
    ['JPK', 'Layanan Jasa Penunjang Kebandarudaraan', 'JPK-FSL', 'Sewa fasilitas', 'JPK-FSL-001', 'Pemanfaatan fasilitas bandara', 'aktif'],
    ['JPK', 'Layanan Jasa Penunjang Kebandarudaraan', 'JPK-UTL', 'Penggunaan utilitas', 'JPK-UTL-001', 'Utilitas bandara', 'aktif'],
    ['JPK', 'Layanan Jasa Penunjang Kebandarudaraan', 'JPK-ALT', 'Layanan kendaraan/peralatan', 'JPK-ALT-001', 'Kendaraan/peralatan operasional', 'aktif'],
    ['JPK', 'Layanan Jasa Penunjang Kebandarudaraan', 'JPK-RKL', 'Layanan reklame/media promosi', 'JPK-RKL-001', 'Media promosi', 'aktif'],
    ['KOM', 'Layanan Jasa Komersial', 'KOM-KNS', 'Konsesi usaha', 'KOM-KNS-001', 'Konsesi kegiatan usaha', 'aktif'],
    ['KOM', 'Layanan Jasa Komersial', 'KOM-TNT', 'Tenant/gerai', 'KOM-TNT-001', 'Tenant dan gerai komersial', 'aktif'],
    ['KOM', 'Layanan Jasa Komersial', 'KOM-CTR', 'Counter layanan', 'KOM-CTR-001', 'Counter layanan komersial', 'aktif'],
    ['KOM', 'Layanan Jasa Komersial', 'KOM-PKR', 'Parkir kendaraan', 'KOM-PKR-001', 'Parkir kendaraan darat', 'aktif'],
    ['KOM', 'Layanan Jasa Komersial', 'KOM-TRN', 'Usaha transportasi/taksi/sewa kendaraan', 'KOM-TRN-001', 'Transportasi darat komersial', 'aktif'],
    ['KOM', 'Layanan Jasa Komersial', 'KOM-LLN', 'Layanan komersial lainnya', 'KOM-LLN-001', 'Kegiatan komersial lainnya', 'aktif'],
    ['ADM', 'Layanan Administrasi dan Dokumen', 'ADM-PAS', 'Penerbitan izin/pas', 'ADM-PAS-001', 'Pas/izin masuk daerah keamanan terbatas', 'aktif'],
    ['ADM', 'Layanan Administrasi dan Dokumen', 'ADM-LEG', 'Legalisasi/rekomendasi', 'ADM-LEG-001', 'Rekomendasi/legalisasi', 'aktif'],
    ['ADM', 'Layanan Administrasi dan Dokumen', 'ADM-DAT', 'Layanan data/informasi', 'ADM-DAT-001', 'Data/informasi/dokumen', 'aktif'],
    ['ADM', 'Layanan Administrasi dan Dokumen', 'ADM-DOP', 'Layanan dokumen operasional', 'ADM-DOP-001', 'Dokumen operasional', 'aktif'],
    ['LLN', 'Layanan Lainnya', 'LLN-KHS', 'Layanan khusus', 'LLN-KHS-001', 'Layanan khusus', 'aktif'],
    ['LLN', 'Layanan Lainnya', 'LLN-INS', 'Layanan insidentil', 'LLN-INS-001', 'Layanan insidentil', 'aktif'],
    ['LLN', 'Layanan Lainnya', 'LLN-VRF', 'Perlu Verifikasi', 'LLN-VRF-001', 'Objek layanan perlu verifikasi', 'aktif'],
];

$out = new Spreadsheet();
$classSheet = $out->getActiveSheet();
$classSheet->setTitle('Klasifikasi Layanan');
$headers = ['No', 'Nama Layanan', 'Jenis Layanan', 'Kategori Layanan', 'Subkategori', 'Satuan', 'Dasar Tarif', 'Keterangan', 'Catatan Perbaikan Nama/Kategori', 'Path Sumber', 'Tarif Sumber', 'Kode MAK', 'Kode Akun'];
$classSheet->fromArray($headers, null, 'A1');
$classSheet->fromArray($rows, null, 'A2');
$classSheet->freezePane('A2');
$classSheet->getStyle('A1:M1')->getFont()->setBold(true);
$classSheet->getStyle('A1:M1')->getFill()->setFillType('solid')->getStartColor()->setRGB('D9EAF7');
$classSheet->getStyle('A:M')->getAlignment()->setWrapText(true)->setVertical('top');
foreach (range('A', 'M') as $col) {
    $classSheet->getColumnDimension($col)->setAutoSize(true);
}
$classSheet->setAutoFilter($classSheet->calculateWorksheetDimension());

$masterSheet = $out->createSheet();
$masterSheet->setTitle('Master Referensi');
$masterHeaders = ['kode_jenis_layanan', 'nama_jenis_layanan', 'kode_kategori', 'nama_kategori', 'kode_subkategori', 'nama_subkategori', 'status_aktif'];
$masterSheet->fromArray($masterHeaders, null, 'A1');
$masterSheet->fromArray($masterRows, null, 'A2');
$masterSheet->freezePane('A2');
$masterSheet->getStyle('A1:G1')->getFont()->setBold(true);
$masterSheet->getStyle('A1:G1')->getFill()->setFillType('solid')->getStartColor()->setRGB('E2F0D9');
foreach (range('A', 'G') as $col) {
    $masterSheet->getColumnDimension($col)->setAutoSize(true);
}
$masterSheet->setAutoFilter($masterSheet->calculateWorksheetDimension());

$summarySheet = $out->createSheet();
$summarySheet->setTitle('Ringkasan');
$summary = [];
foreach ($rows as $row) {
    $key = $row[2] . '|' . $row[3];
    $summary[$key] = ($summary[$key] ?? 0) + 1;
}
$summaryRows = [['Jenis Layanan', 'Kategori Layanan', 'Jumlah Item']];
foreach ($summary as $key => $count) {
    [$jenis, $kategori] = explode('|', $key, 2);
    $summaryRows[] = [$jenis, $kategori, $count];
}
$summarySheet->fromArray([
    ['Dokumen Klasifikasi Layanan Jasa BLU/UPBU'],
    ['Sumber Sheet', $sheetName],
    ['Jumlah node sumber', $nodeCount],
    ['Jumlah item diklasifikasikan', count($rows)],
    [],
], null, 'A1');
$summarySheet->fromArray($summaryRows, null, 'A7');
$summarySheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$summarySheet->getStyle('A7:C7')->getFont()->setBold(true);
foreach (range('A', 'C') as $col) {
    $summarySheet->getColumnDimension($col)->setAutoSize(true);
}

$filename = $outputDir . '/klasifikasi_layanan_jasa_blu_upbu_' . preg_replace('/[^a-z0-9]+/i', '_', strtolower($sheetName)) . '.xlsx';
(new Xlsx($out))->save($filename);

echo $filename . PHP_EOL;
echo 'Jumlah item: ' . count($rows) . PHP_EOL;
echo 'Perlu verifikasi: ' . count(array_filter($rows, fn ($row) => $row[3] === 'Perlu Verifikasi')) . PHP_EOL;
