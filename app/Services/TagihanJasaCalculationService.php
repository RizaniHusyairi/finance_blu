<?php

namespace App\Services;

use App\Models\LayananJasa;

class TagihanJasaCalculationService
{
    public function calculateSubtotal(array $row, ?LayananJasa $layanan): float
    {
        $payload = $this->buildPayload($row, $layanan);
        $price = (float) ($row['harga_satuan'] ?? 0);
        $kurs = (float) ($row['kurs'] ?? 1);

        if (($row['mode'] ?? 'TARIF') === 'PERSENTASE') {
            return ((float) $payload['billable_qty'] * $price / 100) * $kurs;
        }

        return (float) $payload['billable_qty'] * $price * $kurs;
    }

    public function buildPayload(array $row, ?LayananJasa $layanan): array
    {
        $mode = $row['mode'] ?? 'TARIF';
        $postedPayload = $this->decodePostedPayload($row['calculation_payload'] ?? null);
        $postedInputs = $postedPayload['inputs'] ?? [];

        if (($postedPayload['rule'] ?? null) === 'GARBARATA_DETAIL' && $this->isGarbarataService($layanan)) {
            return $this->buildGarbarataPayload($postedPayload, (float) ($row['harga_satuan'] ?? 0));
        }

        if ($mode === 'PERSENTASE') {
            $qty = $this->positiveNumber($row['qty'] ?? 0);

            return [
                'rule' => 'PERSENTASE',
                'label' => 'Persentase omzet',
                'inputs' => ['volume' => $qty],
                'billable_qty' => $qty,
                'formula' => 'Omzet x persentase',
            ];
        }

        $definition = $this->definitionFor($layanan);
        if (! $definition) {
            $qty = $this->positiveNumber($row['qty'] ?? 0);

            return [
                'rule' => 'MANUAL_VOLUME',
                'label' => 'Volume manual',
                'inputs' => ['volume' => $qty],
                'billable_qty' => $qty,
                'formula' => 'Volume manual ' . $this->formatNumber($qty),
            ];
        }

        $inputs = [];
        foreach ($definition['fields'] as $field) {
            $inputs[$field['key']] = $this->positiveNumber($postedInputs[$field['key']] ?? $field['default']);
        }

        $this->ensureLandingWeightFitsSelectedService($layanan, $inputs);

        $billableQty = $this->computeBillableQty($definition, $inputs);

        return [
            'rule' => $definition['rule'],
            'label' => $definition['label'],
            'inputs' => $inputs,
            'billable_qty' => $billableQty,
            'formula' => $this->buildFormula($definition, $inputs, $billableQty),
        ];
    }

    private function isGarbarataService(?LayananJasa $layanan): bool
    {
        if (! $layanan) {
            return false;
        }

        $text = mb_strtolower($layanan->nama_lengkap . ' ' . $layanan->nama_layanan . ' ' . $layanan->satuan);

        return str_contains($text, 'garbarata');
    }

    private function buildGarbarataPayload(array $postedPayload, float $price): array
    {
        $sourceRows = $postedPayload['rows'] ?? ($postedPayload['inputs']['rows'] ?? []);
        $rows = [];
        $billableQty = 0;

        foreach (is_array($sourceRows) ? $sourceRows : [] as $sourceRow) {
            if (! is_array($sourceRow)) {
                continue;
            }

            $docking = $this->cleanString($sourceRow['docking'] ?? '');
            $undocking = $this->cleanString($sourceRow['undocking'] ?? '');
            $durationMinutes = $this->garbarataDurationMinutes($docking, $undocking);
            $rentang = $durationMinutes > 0 ? max(1, (int) ceil($durationMinutes / 120)) : 0;
            $total = $rentang * $price;
            $billableQty += $rentang;

            $rows[] = [
                'tanggal' => $this->cleanString($sourceRow['tanggal'] ?? ''),
                'reg' => $this->cleanString($sourceRow['reg'] ?? ''),
                'flight_arr' => $this->cleanString($sourceRow['flight_arr'] ?? ''),
                'flight_dep' => $this->cleanString($sourceRow['flight_dep'] ?? ''),
                'route' => $this->cleanString($sourceRow['route'] ?? ''),
                'docking' => $docking,
                'undocking' => $undocking,
                'type_pesawat' => $this->cleanString($sourceRow['type_pesawat'] ?? ''),
                'bobot_ton' => $this->positiveNumber($sourceRow['bobot_ton'] ?? 0),
                'jasa_pemakaian_garbarata' => $price,
                'waktu' => $this->formatDuration($durationMinutes),
                'rentang_pemakaian' => $rentang,
                'total' => $total,
            ];
        }

        return [
            'rule' => 'GARBARATA_DETAIL',
            'label' => 'Rincian pemakaian garbarata',
            'inputs' => ['rows' => $rows],
            'rows' => $rows,
            'billable_qty' => $billableQty,
            'formula' => $this->formatNumber($billableQty) . ' rentang pemakaian x tarif per 2 jam',
        ];
    }

    private function cleanString(mixed $value): string
    {
        return trim((string) $value);
    }

    private function garbarataDurationMinutes(string $docking, string $undocking): int
    {
        $start = $this->timeToMinutes($docking);
        $end = $this->timeToMinutes($undocking);

        if ($start === null || $end === null) {
            return 0;
        }

        if ($end < $start) {
            $end += 24 * 60;
        }

        return max(0, $end - $start);
    }

    private function timeToMinutes(string $value): ?int
    {
        if (! preg_match('/^(\d{1,2}):(\d{2})$/', $value, $matches)) {
            return null;
        }

        $hours = (int) $matches[1];
        $minutes = (int) $matches[2];

        if ($hours > 23 || $minutes > 59) {
            return null;
        }

        return ($hours * 60) + $minutes;
    }

    private function formatDuration(int $minutes): string
    {
        if ($minutes <= 0) {
            return '00:00';
        }

        return str_pad((string) intdiv($minutes, 60), 2, '0', STR_PAD_LEFT)
            . ':'
            . str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT);
    }

    private function decodePostedPayload(mixed $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (! is_string($payload) || trim($payload) === '') {
            return [];
        }

        $decoded = json_decode($payload, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function definitionFor(?LayananJasa $layanan): ?array
    {
        if (! $layanan || $this->isExcluded($layanan)) {
            return null;
        }

        $unit = $this->normalizeUnit($layanan->satuan);
        $definitions = $this->definitions();

        foreach ($definitions as $definition) {
            if (($definition['match'])($unit)) {
                return $definition;
            }
        }

        return null;
    }

    private function isExcluded(LayananJasa $layanan): bool
    {
        $path = mb_strtolower($layanan->nama_lengkap . ' ' . $layanan->nama_layanan . ' ' . $layanan->satuan);

        return $layanan->tipe_layanan === 'KONSESI'
            || (bool) $layanan->mendukung_konsesi
            || str_contains($path, '%')
            || str_contains($path, 'pjp2u')
            || str_contains($path, 'pax pjp2u')
            || str_contains($path, 'penggunaan listrik')
            || str_contains($path, 'penggunaan air bandar');
    }

    private function ensureLandingWeightFitsSelectedService(?LayananJasa $layanan, array $inputs): void
    {
        if (! $layanan || ! isset($inputs['berat_kg'])) {
            return;
        }

        $range = $this->parseLandingWeightRange($layanan);
        if (! $range) {
            return;
        }

        $weight = $this->positiveNumber($inputs['berat_kg']);
        if ($weight <= 0 || $this->isWeightInRange($weight, $range)) {
            return;
        }

        throw new \InvalidArgumentException(
            'Bobot ' . $this->formatNumber($weight) . ' kg tidak sesuai dengan layanan ' . $range['label'] . '. Pilih bracket bobot jasa pendaratan pesawat yang sesuai.'
        );
    }

    private function parseLandingWeightRange(LayananJasa $layanan): ?array
    {
        $text = mb_strtolower($layanan->nama_lengkap . ' ' . $layanan->nama_layanan);
        $unit = $this->normalizeUnit($layanan->satuan);

        if (! str_contains($unit, 'tiap 1000 kg') || ! str_contains($text, 'bobot pesawat')) {
            return null;
        }

        if (str_contains($text, 's.d. 40.000 kg') || str_contains($text, 's.d. 40000 kg')) {
            return [
                'min' => 0,
                'max' => 40000,
                'min_exclusive' => false,
                'max_inclusive' => true,
                'label' => 'bobot pesawat s.d. 40.000 kg',
            ];
        }

        if ((str_contains($text, 'diatas 40.000 kg') || str_contains($text, 'di atas 40.000 kg') || str_contains($text, 'diatas 40000 kg') || str_contains($text, 'di atas 40000 kg'))
            && (str_contains($text, 's.d. 100.000 kg') || str_contains($text, 's.d. 100000 kg'))) {
            return [
                'min' => 40000,
                'max' => 100000,
                'min_exclusive' => true,
                'max_inclusive' => true,
                'label' => 'bobot pesawat di atas 40.000 kg s.d. 100.000 kg',
            ];
        }

        if (str_contains($text, 'di atas 100.000 kg') || str_contains($text, 'diatas 100.000 kg') || str_contains($text, 'di atas 100000 kg') || str_contains($text, 'diatas 100000 kg')) {
            return [
                'min' => 100000,
                'max' => null,
                'min_exclusive' => true,
                'max_inclusive' => false,
                'label' => 'bobot pesawat di atas 100.000 kg',
            ];
        }

        return null;
    }

    private function isWeightInRange(float $weight, array $range): bool
    {
        $aboveMin = $range['min_exclusive']
            ? $weight > $range['min']
            : $weight >= $range['min'];
        $belowMax = $range['max'] === null
            ? true
            : ($range['max_inclusive'] ? $weight <= $range['max'] : $weight < $range['max']);

        return $aboveMin && $belowMax;
    }

    private function definitions(): array
    {
        return [
            [
                'rule' => 'WEIGHT_PER_1000_PER_12_HOURS_ROUND_UP',
                'label' => 'Tiap 1000 kg per 12 jam',
                'match' => fn (string $unit) => str_contains($unit, 'tiap 1000 kg per 12 jam'),
                'fields' => [
                    ['key' => 'berat_kg', 'label' => 'kg', 'default' => 1000],
                    ['key' => 'durasi_jam', 'label' => 'jam', 'default' => 12],
                ],
                'compute' => fn (array $inputs) => $this->ceilPart($inputs['berat_kg'], 1000) * $this->ceilPart($inputs['durasi_jam'], 12),
                'formula' => fn (array $inputs, float $qty) => 'ceil(' . $this->formatNumber($inputs['berat_kg']) . ' kg / 1000 kg) x ceil(' . $this->formatNumber($inputs['durasi_jam']) . ' jam / 12 jam) = ' . $this->formatNumber($qty) . ' unit tagih',
            ],
            [
                'rule' => 'WEIGHT_PER_1000_ROUND_UP',
                'label' => 'Tiap 1000 kg',
                'match' => fn (string $unit) => str_contains($unit, 'tiap 1000 kg'),
                'fields' => [
                    ['key' => 'berat_kg', 'label' => 'kg', 'default' => 1000],
                ],
                'compute' => fn (array $inputs) => $this->ceilPart($inputs['berat_kg'], 1000),
                'formula' => fn (array $inputs, float $qty) => 'ceil(' . $this->formatNumber($inputs['berat_kg']) . ' kg / 1000 kg) = ' . $this->formatNumber($qty) . ' unit tagih',
            ],
            $this->packageDefinition('PACKAGE_100_EKSEMPLAR_ROUND_UP', 'Per 100 eksemplar', 'per 100 eksemplar', 'jumlah_eksemplar', 'eksemplar', 100),
            $this->packageDefinition('PACKAGE_25_BUKU_ROUND_UP', 'Per 25 buku', 'per 25 buku', 'jumlah_buku', 'buku', 25),
            $this->productDefinition('PER_UNIT_PER_BULAN_PER_SISI_PANDANG', 'Per unit/bulan/sisi', 'per unit per bulan per sisi pandang', [['unit', 'unit'], ['bulan', 'bulan'], ['sisi_pandang', 'sisi pandang']]),
            $this->productDefinition('PER_UNIT_PER_HARI_PER_SISI_PANDANG', 'Per unit/hari/sisi', 'per unit per hari per sisi pandang', [['unit', 'unit'], ['hari', 'hari'], ['sisi_pandang', 'sisi pandang']]),
            $this->productDefinition('PER_M2_REKLAME_PER_TAHUN', 'Per m2 reklame/tahun', 'per m2 reklame per tahun', [['luas_m2', 'm2'], ['tahun', 'tahun']]),
            $this->productDefinition('PER_SAMBUNGAN_CABANG_PER_BULAN', 'Per sambungan cabang/bulan', 'per sambungan cabang per bulan', [['sambungan_cabang', 'sambungan'], ['bulan', 'bulan']]),
            $this->productDefinition('PER_PESAWAT_PER_SEKALI_PENGGUNAAN', 'Per pesawat/penggunaan', 'per pesawat per sekali penggunaan', [['pesawat', 'pesawat'], ['sekali_penggunaan', 'penggunaan']]),
            $this->productDefinition('PER_KEGIATAN_PER_HARI', 'Per kegiatan/hari', 'per kegiatan per hari', [['kegiatan', 'kegiatan'], ['hari', 'hari']]),
            $this->productDefinition('PER_JAM_PER_RUANGAN', 'Per jam/ruangan', 'per jam per ruangan', [['jam', 'jam'], ['ruangan', 'ruangan']]),
            $this->productDefinition('PER_JAM_PER_TON', 'Per jam/ton', 'per jam per ton', [['jam', 'jam'], ['ton', 'ton']]),
            $this->productDefinition('PER_KG_PER_HARI', 'Per kg/hari', 'per kg per hari', [['kg', 'kg'], ['hari', 'hari']]),
            $this->productDefinition('PER_M2_PER_BULAN', 'Per m2/bulan', 'per m2 per bulan', [['luas_m2', 'm2'], ['bulan', 'bulan']]),
            $this->productDefinition('PER_M2_PER_HARI', 'Per m2/hari', 'per m2 per hari', [['luas_m2', 'm2'], ['hari', 'hari']]),
            $this->productDefinition('PER_KENDARAAN_PER_TAHUN', 'Per kendaraan/tahun', 'per kendaraan per tahun', [['kendaraan', 'kendaraan'], ['tahun', 'tahun']]),
            $this->productDefinition('PER_KENDARAAN_PER_BULAN', 'Per kendaraan/bulan', 'per kendaraan per bulan', [['kendaraan', 'kendaraan'], ['bulan', 'bulan']]),
            $this->productDefinition('PER_KENDARAAN_PER_MINGGU', 'Per kendaraan/minggu', 'per kendaraan per minggu', [['kendaraan', 'kendaraan'], ['minggu', 'minggu']]),
            $this->productDefinition('PER_ORANG_PER_TAHUN', 'Per orang/tahun', 'per orang per tahun', [['orang', 'orang'], ['tahun', 'tahun']]),
            $this->productDefinition('PER_ORANG_PER_BULAN', 'Per orang/bulan', 'per orang per bulan', [['orang', 'orang'], ['bulan', 'bulan']]),
            $this->productDefinition('PER_ORANG_PER_MINGGU', 'Per orang/minggu', 'per orang per minggu', [['orang', 'orang'], ['minggu', 'minggu']]),
            $this->productDefinition('PER_UNIT_PER_BULAN', 'Per unit/bulan', 'per unit per bulan', [['unit', 'unit'], ['bulan', 'bulan']]),
            $this->productDefinition('PER_UNIT_PER_HARI', 'Per unit/hari', 'per unit per hari', [['unit', 'unit'], ['hari', 'hari']]),
            $this->directDefinition('PER_SEKALI_LEPAS_LANDAS_PENDARATAN', 'Gerakan pesawat', fn (string $unit) => str_contains($unit, 'per sekali lepas landas') || str_contains($unit, 'pendaratan'), 'gerakan', 'gerakan'),
            $this->directDefinition('PER_PENUMPANG', 'Per penumpang', fn (string $unit) => $unit === 'per penumpang', 'penumpang', 'penumpang'),
            $this->directDefinition('PER_JAM', 'Per jam', fn (string $unit) => $unit === 'per jam', 'jam', 'jam'),
            $this->directDefinition('PER_KG', 'Per kg', fn (string $unit) => $unit === 'per kg', 'kg', 'kg'),
            $this->directDefinition('PER_HARI', 'Per hari', fn (string $unit) => $unit === 'per hari', 'hari', 'hari'),
        ];
    }

    private function productDefinition(string $rule, string $label, string $needle, array $fields): array
    {
        $mappedFields = array_map(fn (array $field) => [
            'key' => $field[0],
            'label' => $field[1],
            'default' => 1,
        ], $fields);

        return [
            'rule' => $rule,
            'label' => $label,
            'match' => fn (string $unit) => str_contains($unit, $needle),
            'fields' => $mappedFields,
            'compute' => fn (array $inputs) => array_reduce(
                $mappedFields,
                fn (float $total, array $field) => $total * $this->positiveNumber($inputs[$field['key']] ?? 0),
                1.0
            ),
            'formula' => fn (array $inputs, float $qty) => implode(' x ', array_map(
                fn (array $field) => $this->formatNumber($inputs[$field['key']] ?? 0) . ' ' . $field['label'],
                $mappedFields
            )) . ' = ' . $this->formatNumber($qty),
        ];
    }

    private function directDefinition(string $rule, string $label, callable $match, string $key, string $fieldLabel): array
    {
        return [
            'rule' => $rule,
            'label' => $label,
            'match' => $match,
            'fields' => [
                ['key' => $key, 'label' => $fieldLabel, 'default' => 1],
            ],
            'compute' => fn (array $inputs) => $this->positiveNumber($inputs[$key] ?? 0),
            'formula' => fn (array $inputs, float $qty) => $this->formatNumber($inputs[$key] ?? 0) . ' ' . $fieldLabel . ' = ' . $this->formatNumber($qty),
        ];
    }

    private function packageDefinition(string $rule, string $label, string $needle, string $key, string $fieldLabel, int $divisor): array
    {
        return [
            'rule' => $rule,
            'label' => $label,
            'match' => fn (string $unit) => str_contains($unit, $needle),
            'fields' => [
                ['key' => $key, 'label' => $fieldLabel, 'default' => $divisor],
            ],
            'compute' => fn (array $inputs) => $this->ceilPart($inputs[$key] ?? 0, $divisor),
            'formula' => fn (array $inputs, float $qty) => 'ceil(' . $this->formatNumber($inputs[$key] ?? 0) . ' / ' . $divisor . ') = ' . $this->formatNumber($qty),
        ];
    }

    private function computeBillableQty(array $definition, array $inputs): float
    {
        return (float) ($definition['compute'])($inputs);
    }

    private function buildFormula(array $definition, array $inputs, float $qty): string
    {
        return (string) ($definition['formula'])($inputs, $qty);
    }

    private function normalizeUnit(?string $unit): string
    {
        $unit = mb_strtolower((string) $unit);
        $unit = preg_replace('/m(?:\x{00C2})?\x{00B2}/u', 'm2', $unit) ?? $unit;

        return trim(preg_replace('/\s+/', ' ', $unit) ?? '');
    }

    private function positiveNumber(mixed $value): float
    {
        $number = is_numeric($value) ? (float) $value : 0.0;

        return $number > 0 ? $number : 0.0;
    }

    private function ceilPart(mixed $value, int $divisor): int
    {
        $number = $this->positiveNumber($value);

        return $number > 0 ? (int) ceil($number / $divisor) : 0;
    }

    private function formatNumber(mixed $value): string
    {
        $number = (float) $value;

        if (floor($number) === $number) {
            return number_format($number, 0, ',', '.');
        }

        return rtrim(rtrim(number_format($number, 2, ',', '.'), '0'), ',');
    }
}
