<?php

declare(strict_types=1);

/**
 * Generator alur proses Tagihan Kontrak (BAST / Termin) untuk Draw.io.
 *
 * 3 halaman:
 *  1) End-to-End Flow  : swimlane horizontal Pejabat Pengadaan → … → SP2D
 *  2) Verifikasi Tagihan: workflow PARALEL 5 verifikator + Kasubbag
 *  3) State Machine     : transisi status tagihan (DRAFT → READY_FOR_SPP → SPP_TERBIT)
 *
 * Eksekusi: php docs/diagrams/build-flow-tagihan-kontrak.php
 * Output  : docs/diagrams/flow-tagihan-kontrak.drawio
 */

/* ========================================================================
 | 1. SHARED HELPERS — primitives untuk node dan edge
 |======================================================================== */

class DrawIoBuilder
{
    /** @var string[] */
    public array $cells = [];
    private int $idCounter = 100;

    public function newId(): int
    {
        return $this->idCounter++;
    }

    public function box(int $id, string $label, int $x, int $y, int $w, int $h, string $style): void
    {
        $this->cells[] = sprintf(
            '<mxCell id="%d" value="%s" style="%s" vertex="1" parent="1"><mxGeometry x="%d" y="%d" width="%d" height="%d" as="geometry"/></mxCell>',
            $id,
            htmlspecialchars($label, ENT_XML1),
            $style,
            $x, $y, $w, $h
        );
    }

    public function activity(int $id, string $label, int $x, int $y, int $w = 200, int $h = 60, string $color = '#dae8fc'): void
    {
        $stroke = $this->stroke($color);
        $this->box($id, $label, $x, $y, $w, $h,
            "rounded=1;whiteSpace=wrap;html=1;fillColor={$color};strokeColor={$stroke};fontSize=11;align=center;verticalAlign=middle;arcSize=20;");
    }

    public function diamond(int $id, string $label, int $x, int $y, int $w = 140, int $h = 90): void
    {
        $this->box($id, $label, $x, $y, $w, $h,
            'rhombus;whiteSpace=wrap;html=1;fillColor=#fff2cc;strokeColor=#d6b656;fontSize=10;align=center;verticalAlign=middle;');
    }

    public function start(int $id, string $label, int $x, int $y): void
    {
        $this->box($id, $label, $x, $y, 60, 60,
            'ellipse;whiteSpace=wrap;html=1;fillColor=#d5e8d4;strokeColor=#82b366;fontSize=10;align=center;verticalAlign=middle;');
    }

    public function endNode(int $id, string $label, int $x, int $y): void
    {
        $this->box($id, $label, $x, $y, 60, 60,
            'ellipse;whiteSpace=wrap;html=1;fillColor=#f8cecc;strokeColor=#b85450;fontSize=10;align=center;verticalAlign=middle;strokeWidth=3;');
    }

    public function gatewayPar(int $id, string $label, int $x, int $y): void
    {
        // BPMN parallel gateway (plus inside diamond)
        $this->box($id, $label, $x, $y, 50, 50,
            'rhombus;html=1;fillColor=#fff2cc;strokeColor=#d6b656;align=center;verticalAlign=middle;fontSize=22;fontStyle=1;');
    }

    public function lane(int $id, string $label, int $x, int $y, int $w, int $h, string $color): void
    {
        $stroke = $this->stroke($color);
        $this->box($id, $label, $x, $y, $w, $h,
            "swimlane;startSize=28;html=1;fillColor={$color};strokeColor={$stroke};fontSize=11;fontStyle=1;align=center;horizontal=0;");
    }

    public function title(int $id, string $label, int $x, int $y, int $w = 1600): void
    {
        $this->box($id, $label, $x, $y, $w, 36,
            'text;html=1;fontSize=18;fontStyle=1;align=left;verticalAlign=middle;');
    }

    public function subtitle(int $id, string $label, int $x, int $y, int $w = 1600): void
    {
        $this->box($id, $label, $x, $y, $w, 22,
            'text;html=1;fontSize=11;fontStyle=2;fontColor=#555555;align=left;verticalAlign=middle;');
    }

    public function note(int $id, string $label, int $x, int $y, int $w = 280, int $h = 60): void
    {
        $this->box($id, $label, $x, $y, $w, $h,
            'shape=note;whiteSpace=wrap;html=1;fillColor=#fff2cc;strokeColor=#d6b656;fontSize=10;align=left;verticalAlign=top;spacingLeft=8;spacingTop=4;');
    }

    public function edge(int $sourceId, int $targetId, ?string $label = null, string $extraStyle = ''): void
    {
        $style = 'edgeStyle=orthogonalEdgeStyle;rounded=1;html=1;strokeColor=#555555;jettySize=auto;orthogonalLoop=1;endArrow=block;endFill=1;' . $extraStyle;
        $value = $label ? htmlspecialchars($label, ENT_XML1) : '';
        $this->cells[] = sprintf(
            '<mxCell id="%d" value="%s" style="%s" edge="1" parent="1" source="%d" target="%d"><mxGeometry relative="1" as="geometry"/></mxCell>',
            $this->newId(), $value, $style, $sourceId, $targetId
        );
    }

    public function edgeDashed(int $sourceId, int $targetId, ?string $label = null): void
    {
        $this->edge($sourceId, $targetId, $label, 'strokeColor=#a05a2c;dashed=1;');
    }

    public function edgeReject(int $sourceId, int $targetId, ?string $label = null): void
    {
        $this->edge($sourceId, $targetId, $label, 'strokeColor=#b85450;dashed=1;');
    }

    public function render(): string
    {
        return implode("\n        ", $this->cells);
    }

    private function stroke(string $fill): string
    {
        return match ($fill) {
            '#dae8fc' => '#6c8ebf',
            '#d5e8d4' => '#82b366',
            '#f8cecc' => '#b85450',
            '#fff2cc' => '#d6b656',
            '#e1d5e7' => '#9673a6',
            '#ffe6cc' => '#d79b00',
            '#cce5ff' => '#36393d',
            '#f5f5f5' => '#999999',
            default   => '#888888',
        };
    }
}

/* ========================================================================
 | 2. PAGE 1 — END-TO-END FLOW (SWIMLANE HORIZONTAL)
 |======================================================================== */

function buildPageEndToEnd(): string
{
    $b = new DrawIoBuilder();

    // Layout: 9 lanes vertikal (kolom). Setiap lane berisi aktivitas role.
    // Karena kompleks, kita pakai swimlane horizontal: lane berbaris vertikal,
    // aktivitas mengalir kiri ke kanan tetap. Untuk ini gunakan kolom-kolom (column lanes).
    $laneTop   = 100;
    $laneHeight = 1100;
    $laneWidth  = 230;

    $lanes = [
        ['Pejabat Pengadaan / PPK',          '#dae8fc'],
        ['Verifikasi Step 1 (Paralel)',       '#fff2cc'],
        ['Kasubbag (Step 2)',                 '#e1d5e7'],
        ['Operator BLU',                      '#cce5ff'],
        ['Verifikator SPP/SPM',               '#fff2cc'],
        ['PPSPM',                             '#ffe6cc'],
        ['Bendahara Pengeluaran',             '#d5e8d4'],
        ['Verifikator NPI/SP2D',              '#fff2cc'],
        ['Sistem & BKU',                      '#f5f5f5'],
    ];

    // Title
    $b->title($b->newId(), 'Alur End-to-End — Tagihan Kontrak (BAST / Termin)', 40, 30);
    $b->subtitle($b->newId(), 'Dari draft tagihan oleh Pejabat Pengadaan / PPK sampai SP2D dieksekusi & dicatat BKU.', 40, 60);

    // Lanes
    $laneIds = [];
    foreach ($lanes as $i => [$name, $color]) {
        $id = $b->newId();
        $x = 40 + $i * $laneWidth;
        $b->lane($id, $name, $x, $laneTop, $laneWidth, $laneHeight, $color);
        $laneIds[] = $id;
    }

    // Helper: koordinat aktivitas di dalam lane $i, baris $row
    $cellX = function ($i) use ($laneWidth) {
        return 40 + $i * $laneWidth + 20; // padding kiri 20
    };
    $cellY = function ($row) use ($laneTop) {
        return $laneTop + 50 + $row * 95;
    };
    $w = 190; $h = 65;

    // ---- Lane 0: Pejabat Pengadaan / PPK ----
    $start = $b->newId(); $b->start($start, 'Termin&#10;READY_TO_BILL', $cellX(0) + 65, $cellY(0));

    $a1 = $b->newId(); $b->activity($a1, '1. Pilih Kontrak Aktif &amp; Termin', $cellX(0), $cellY(1), $w, $h);
    $a2 = $b->newId(); $b->activity($a2, '2. Generate Nomor BAPP / BAST / BAP', $cellX(0), $cellY(2), $w, $h);
    $a3 = $b->newId(); $b->activity($a3, '3. Simpan Draft Tagihan&#10;(status: DRAFT)', $cellX(0), $cellY(3), $w, $h);
    $a4 = $b->newId(); $b->activity($a4, '4. Upload Arsip BAPP/BAST/BAP Final', $cellX(0), $cellY(4), $w, $h);

    $a5d = $b->newId(); $b->diamond($a5d, 'Dokumen lengkap?', $cellX(0)+25, $cellY(5)+15, 140, 70);

    $a6 = $b->newId(); $b->activity($a6, '5. Submit ke Verifikasi&#10;(PENDING_VERIFIKASI_KONTRAK)', $cellX(0), $cellY(6) + 30, $w, $h, '#ffe6cc');

    // edges lane 0
    $b->edge($start, $a1);
    $b->edge($a1, $a2);
    $b->edge($a2, $a3);
    $b->edge($a3, $a4);
    $b->edge($a4, $a5d);
    $b->edge($a5d, $a6, 'ya');
    // loop kalau belum lengkap
    $b->edgeDashed($a5d, $a4, 'lengkapi');

    // ---- Lane 1: Verifikator paralel step 1 ----
    $gateOpen = $b->newId(); $b->gatewayPar($gateOpen, '+', $cellX(1) + 70, $cellY(0) + 30);

    $r1 = $b->newId(); $b->activity($r1, 'PPK', $cellX(1), $cellY(1), $w, 50, '#dae8fc');
    $r2 = $b->newId(); $b->activity($r2, 'PPSPM', $cellX(1), $cellY(1)+60, $w, 50, '#dae8fc');
    $r3 = $b->newId(); $b->activity($r3, 'Koordinator Keuangan', $cellX(1), $cellY(1)+120, $w, 50, '#dae8fc');
    $r4 = $b->newId(); $b->activity($r4, 'Bendahara Pengeluaran', $cellX(1), $cellY(1)+180, $w, 50, '#dae8fc');
    $r5 = $b->newId(); $b->activity($r5, 'Bendahara Penerimaan', $cellX(1), $cellY(1)+240, $w, 50, '#dae8fc');

    $gateClose = $b->newId(); $b->gatewayPar($gateClose, '+', $cellX(1) + 70, $cellY(6));

    // edges paralel
    $b->edge($a6, $gateOpen);
    foreach ([$r1, $r2, $r3, $r4, $r5] as $r) {
        $b->edge($gateOpen, $r);
        $b->edge($r, $gateClose);
    }

    // Note revisi/reject paralel
    $note1 = $b->newId(); $b->note($note1, 'Setiap verifikator dapat:&#10;• Approve&#10;• Minta Revisi → kembali ke Pejabat Pengadaan/PPK&#10;• Reject → tagihan ditolak (DITOLAK_*)', $cellX(1) - 15, $cellY(1)+310, 215, 70);
    $b->edgeReject($r1, $a4, 'revisi');
    $b->edgeReject($r3, $a4, 'revisi');

    // ---- Lane 2: Kasubbag step 2 ----
    $kasubD = $b->newId(); $b->diamond($kasubD, 'Setuju?', $cellX(2)+30, $cellY(2)+10, 140, 70);
    $kasub = $b->newId(); $b->activity($kasub, 'Kasubbag verifikasi final', $cellX(2), $cellY(1), $w, $h, '#e1d5e7');
    $b->edge($gateClose, $kasub);
    $b->edge($kasub, $kasubD);

    $kasubReady = $b->newId(); $b->activity($kasubReady, 'Tagihan: READY_FOR_SPP', $cellX(2), $cellY(3) + 30, $w, $h, '#d5e8d4');
    $b->edge($kasubD, $kasubReady, 'approve');
    $b->edgeReject($kasubD, $a4, 'revisi/tolak');

    // ---- Lane 3: Operator BLU SPP ----
    $spp1 = $b->newId(); $b->activity($spp1, '6. Buat SPP Kontrak (DRAFT)', $cellX(3), $cellY(1), $w, $h, '#cce5ff');
    $spp2 = $b->newId(); $b->activity($spp2, '7. Submit SPP ke Verifikasi', $cellX(3), $cellY(2), $w, $h, '#cce5ff');
    $spp3 = $b->newId(); $b->activity($spp3, '8. Cetak &amp; Upload SPP Bertanda Tangan', $cellX(3), $cellY(4), $w, $h, '#cce5ff');
    $b->edge($kasubReady, $spp1);
    $b->edge($spp1, $spp2);

    // ---- Lane 4: Verifikator SPP (PPK, Koor.Keu, Kasubbag) ----
    $vsppGate = $b->newId(); $b->gatewayPar($vsppGate, '+', $cellX(4)+70, $cellY(2)+5);
    $vspp1 = $b->newId(); $b->activity($vspp1, 'PPK', $cellX(4), $cellY(3), $w, 50, '#dae8fc');
    $vspp2 = $b->newId(); $b->activity($vspp2, 'Koordinator Keuangan', $cellX(4), $cellY(3)+60, $w, 50, '#dae8fc');
    $vspp3 = $b->newId(); $b->activity($vspp3, 'Kasubbag', $cellX(4), $cellY(3)+120, $w, 50, '#dae8fc');
    $vsppGateClose = $b->newId(); $b->gatewayPar($vsppGateClose, '+', $cellX(4)+70, $cellY(3)+200);

    $b->edge($spp2, $vsppGate);
    foreach ([$vspp1, $vspp2, $vspp3] as $v) {
        $b->edge($vsppGate, $v);
        $b->edge($v, $vsppGateClose);
    }
    $b->edge($vsppGateClose, $spp3);

    // ---- Lane 5: PPSPM SPM ----
    $spm1 = $b->newId(); $b->activity($spm1, '9. Buat SPM Kontrak (Operator BLU)', $cellX(5), $cellY(1), $w, $h, '#ffe6cc');
    $spm2 = $b->newId(); $b->activity($spm2, '10. PPSPM Verifikasi SPM', $cellX(5), $cellY(2), $w, $h, '#ffe6cc');
    $spm3 = $b->newId(); $b->activity($spm3, '11. Upload SPM Bertanda Tangan', $cellX(5), $cellY(3), $w, $h, '#ffe6cc');
    $b->edge($spp3, $spm1);
    $b->edge($spm1, $spm2);
    $b->edge($spm2, $spm3);

    // ---- Lane 6: Bendahara Pengeluaran NPI ----
    $npi1 = $b->newId(); $b->activity($npi1, '12. Buat NPI Kontrak (Draft)', $cellX(6), $cellY(1), $w, $h, '#d5e8d4');
    $npi2 = $b->newId(); $b->activity($npi2, '13. Submit NPI ke Verifikasi', $cellX(6), $cellY(2), $w, $h, '#d5e8d4');
    $npi3 = $b->newId(); $b->activity($npi3, '14. Upload NPI Bertanda Tangan', $cellX(6), $cellY(4), $w, $h, '#d5e8d4');
    $b->edge($spm3, $npi1);
    $b->edge($npi1, $npi2);

    // ---- Lane 7: Verifikator NPI/SP2D paralel ----
    $vnpiGate = $b->newId(); $b->gatewayPar($vnpiGate, '+', $cellX(7)+70, $cellY(2)+5);
    $vnpi1 = $b->newId(); $b->activity($vnpi1, 'PPK', $cellX(7), $cellY(3), $w, 50, '#dae8fc');
    $vnpi2 = $b->newId(); $b->activity($vnpi2, 'Koordinator Keuangan', $cellX(7), $cellY(3)+60, $w, 50, '#dae8fc');
    $vnpi3 = $b->newId(); $b->activity($vnpi3, 'Bendahara Penerimaan', $cellX(7), $cellY(3)+120, $w, 50, '#dae8fc');
    $vnpi4 = $b->newId(); $b->activity($vnpi4, 'Kasubbag', $cellX(7), $cellY(3)+180, $w, 50, '#dae8fc');
    $vnpiGateClose = $b->newId(); $b->gatewayPar($vnpiGateClose, '+', $cellX(7)+70, $cellY(3)+260);

    $b->edge($npi2, $vnpiGate);
    foreach ([$vnpi1, $vnpi2, $vnpi3, $vnpi4] as $v) {
        $b->edge($vnpiGate, $v);
        $b->edge($v, $vnpiGateClose);
    }
    $b->edge($vnpiGateClose, $npi3);

    // ---- Lane 6: SP2D ----
    $sp2d1 = $b->newId(); $b->activity($sp2d1, '15. Buat SP2D Kontrak (Draft)', $cellX(6), $cellY(5), $w, $h, '#d5e8d4');
    $sp2d2 = $b->newId(); $b->activity($sp2d2, '16. Submit SP2D ke Verifikasi', $cellX(6), $cellY(6), $w, $h, '#d5e8d4');
    $b->edge($npi3, $sp2d1);
    $b->edge($sp2d1, $sp2d2);

    // ---- Lane 7: Verifikator SP2D ----
    $vspd2dGate = $b->newId(); $b->gatewayPar($vspd2dGate, '+', $cellX(7)+70, $cellY(6)+5);
    $vsp2d1 = $b->newId(); $b->activity($vsp2d1, 'PPK', $cellX(7), $cellY(7), $w, 50, '#dae8fc');
    $vsp2d2 = $b->newId(); $b->activity($vsp2d2, 'Koordinator Keuangan', $cellX(7), $cellY(7)+60, $w, 50, '#dae8fc');
    $vsp2d3 = $b->newId(); $b->activity($vsp2d3, 'PPSPM', $cellX(7), $cellY(7)+120, $w, 50, '#dae8fc');
    $vsp2d4 = $b->newId(); $b->activity($vsp2d4, 'Kasubbag', $cellX(7), $cellY(7)+180, $w, 50, '#dae8fc');
    $vsp2dGateClose = $b->newId(); $b->gatewayPar($vsp2dGateClose, '+', $cellX(7)+70, $cellY(7)+260);

    $b->edge($sp2d2, $vspd2dGate);
    foreach ([$vsp2d1, $vsp2d2, $vsp2d3, $vsp2d4] as $v) {
        $b->edge($vspd2dGate, $v);
        $b->edge($v, $vsp2dGateClose);
    }

    // ---- Lane 8: Sistem & BKU ----
    $exec = $b->newId(); $b->activity($exec, '17. Eksekusi SP2D&#10;(transfer dana ke vendor)', $cellX(8), $cellY(7), $w, $h, '#f5f5f5');
    $bku  = $b->newId(); $b->activity($bku,  '18. Catat di BKU&#10;& Buku Pembantu', $cellX(8), $cellY(8), $w, $h, '#f5f5f5');
    $unlock = $b->newId(); $b->activity($unlock, '19. Termin → SUDAH_DITAGIH&#10;Termin berikutnya unlock ke READY_TO_BILL', $cellX(8), $cellY(9), $w, $h, '#f5f5f5');
    $end = $b->newId(); $b->endNode($end, 'Selesai', $cellX(8) + 65, $cellY(10) + 10);

    $b->edge($vsp2dGateClose, $exec);
    $b->edge($exec, $bku);
    $b->edge($bku, $unlock);
    $b->edge($unlock, $end);

    // Note tentang dokumen
    $noteDoc = $b->newId();
    $b->note($noteDoc,
        'Dokumen yang dihasilkan:&#10;'.
        '• BAPP — Berita Acara Pemeriksaan Pekerjaan&#10;'.
        '• BAST — Berita Acara Serah Terima (hanya jika termin = PELUNASAN)&#10;'.
        '• BAP — Berita Acara Pembayaran&#10;'.
        '• SPP, SPM, NPI, SP2D Kontrak',
        cellX_($cellX, 0), $cellY(7) + 30, 220, 110
    );

    return $b->render();
}

function cellX_(callable $cellX, int $i): int { return $cellX($i); }

/* ========================================================================
 | 3. PAGE 2 — DETAIL VERIFIKASI TAGIHAN KONTRAK
 |======================================================================== */

function buildPageVerifikasi(): string
{
    $b = new DrawIoBuilder();

    $b->title($b->newId(), 'Detail Verifikasi Tagihan Kontrak — Workflow Paralel', 40, 30);
    $b->subtitle($b->newId(), 'Sumber: WorkflowDefinitionSeeder kode = TAGIHAN_KONTRAK_VERIFIKATOR. 5 verifikator paralel di Step 1, lalu Kasubbag finalisasi di Step 2.', 40, 60);

    // Start
    $start = $b->newId(); $b->start($start, 'Submit&#10;Tagihan', 60, 200);

    // Step 1 gateway open
    $g1 = $b->newId(); $b->gatewayPar($g1, '+', 200, 207);

    // 5 verifikator paralel
    $vrows = [
        'PPK',
        'PPSPM',
        'Koordinator Keuangan',
        'Bendahara Pengeluaran',
        'Bendahara Penerimaan',
    ];
    $verifIds = [];
    foreach ($vrows as $i => $r) {
        $id = $b->newId();
        $b->activity($id, $r, 360, 80 + $i * 80, 200, 60, '#dae8fc');
        $verifIds[] = $id;
        $b->edge($g1, $id);
    }

    // Outcome diamonds (per verifikator) — gabungkan ke gateway close
    $g1close = $b->newId(); $b->gatewayPar($g1close, '+', 700, 207);

    foreach ($verifIds as $i => $vid) {
        // diamond approve/revisi/reject
        $d = $b->newId();
        $b->diamond($d, 'A / R / X', 610, 85 + $i * 80, 80, 50);
        $b->edge($vid, $d);
        $b->edge($d, $g1close, 'A');

        // revisi
        $rev = $b->newId();
        $b->activity($rev, 'REVISI_'.strtoupper(str_replace(' ', '_', $vrows[$i])), 800, 85 + $i * 80, 200, 50, '#fff2cc');
        $b->edgeDashed($d, $rev, 'R');

        // reject
        $rej = $b->newId();
        $b->activity($rej, 'DITOLAK_'.strtoupper(str_replace(' ', '_', $vrows[$i])), 1020, 85 + $i * 80, 200, 50, '#f8cecc');
        $b->edgeReject($d, $rej, 'X');
    }

    // Note paralel
    $note = $b->newId();
    $b->note($note,
        'Step 1 PARALEL — semua role di atas wajib approve agar tagihan&#10;'.
        'lanjut ke Step 2 (Kasubbag). Jika SALAH SATU revisi/reject:&#10;'.
        '• revisi → tagihan kembali ke Pejabat Pengadaan/PPK untuk perbaikan&#10;'.
        '• reject → tagihan ditolak final (workflow berhenti)',
        360, 480, 540, 80
    );

    // Step 2 — Kasubbag
    $kasubAct = $b->newId();
    $b->activity($kasubAct, 'Kasubbag&#10;Verifikasi Final', 360, 620, 200, 70, '#e1d5e7');
    $b->edge($g1close, $kasubAct);

    $kasubD = $b->newId();
    $b->diamond($kasubD, 'Approve / Revisi / Reject', 610, 615, 200, 80);
    $b->edge($kasubAct, $kasubD);

    $ready = $b->newId();
    $b->activity($ready, 'READY_FOR_SPP&#10;(Status final disetujui)', 870, 605, 220, 60, '#d5e8d4');
    $b->edge($kasubD, $ready, 'approve');

    $kasubRev = $b->newId();
    $b->activity($kasubRev, 'REVISI_KASUBBAG&#10;→ Pejabat Pengadaan', 870, 680, 220, 50, '#fff2cc');
    $b->edgeDashed($kasubD, $kasubRev, 'revisi');

    $kasubRej = $b->newId();
    $b->activity($kasubRej, 'DITOLAK_KASUBBAG', 870, 745, 220, 50, '#f8cecc');
    $b->edgeReject($kasubD, $kasubRej, 'reject');

    // Lanjut ke pencairan
    $next = $b->newId();
    $b->activity($next, 'Lanjut ke Operator BLU&#10;→ Pembuatan SPP Kontrak', 1130, 605, 240, 60, '#cce5ff');
    $b->edge($ready, $next);

    $end = $b->newId(); $b->endNode($end, 'Selesai', 1430, 605);
    $b->edge($next, $end);

    // edges revisi → start (loop)
    $b->edge($start, $g1);

    return $b->render();
}

/* ========================================================================
 | 4. PAGE 3 — STATE MACHINE TAGIHAN KONTRAK
 |======================================================================== */

function buildPageStateMachine(): string
{
    $b = new DrawIoBuilder();

    $b->title($b->newId(), 'State Machine — Status Tagihan Kontrak', 40, 30);
    $b->subtitle($b->newId(), 'Status tagihan dari pertama dibuat hingga selesai dieksekusi SP2D. Diturunkan dari TagihanKontrakWorkflowService.syncTagihanStatus().', 40, 60);

    $stateStyle = 'rounded=1;whiteSpace=wrap;html=1;arcSize=30;fillColor=%s;strokeColor=%s;fontSize=11;fontStyle=1;align=center;verticalAlign=middle;';

    $state = function (string $label, int $x, int $y, string $fill = '#dae8fc', string $stroke = '#6c8ebf', int $w = 200, int $h = 60) use ($b, $stateStyle) {
        $id = $b->newId();
        $b->box($id, $label, $x, $y, $w, $h, sprintf($stateStyle, $fill, $stroke));
        return $id;
    };

    // Initial
    $init = $b->newId();
    $b->start($init, 'Mulai', 60, 100);

    $draft         = $state('DRAFT',                   180, 90,  '#ffe6cc', '#d79b00');
    $pending       = $state('PENDING_VERIFIKASI_KONTRAK', 440, 90, '#fff2cc', '#d6b656');
    $revisi        = $state('REVISI_*', 440, 200, '#fff2cc', '#d6b656');
    $ditolak       = $state('DITOLAK_*', 440, 290, '#f8cecc', '#b85450');

    $pendingKasub  = $state('PENDING_KASUBBAG', 720, 90, '#fff2cc', '#d6b656');
    $revisiKasub   = $state('REVISI_KASUBBAG', 720, 200, '#fff2cc', '#d6b656');
    $ditolakKasub  = $state('DITOLAK_KASUBBAG', 720, 290, '#f8cecc', '#b85450');

    $ready         = $state('READY_FOR_SPP',           1000, 90, '#d5e8d4', '#82b366');
    $proses        = $state('PROSES_SPP',              1240, 90, '#dae8fc', '#6c8ebf');
    $sebagian      = $state('SEBAGIAN_SPP_TERBIT',     1240, 170, '#dae8fc', '#6c8ebf');
    $terbit        = $state('SPP_TERBIT',              1480, 90, '#dae8fc', '#6c8ebf');
    $lengkap       = $state('SPP_LENGKAP',             1480, 170, '#dae8fc', '#6c8ebf');

    // SPM/NPI/SP2D states
    $spm           = $state('SPM_TERBIT',              1480, 270, '#dae8fc', '#6c8ebf');
    $npi           = $state('NPI_TERBIT',              1480, 350, '#dae8fc', '#6c8ebf');
    $sp2d          = $state('SP2D_TERBIT',             1480, 430, '#dae8fc', '#6c8ebf');
    $cair          = $state('SP2D_CAIR / SUDAH_DITAGIH', 1480, 510, '#d5e8d4', '#82b366');

    $end = $b->newId();
    $b->endNode($end, 'Selesai', 1530, 620);

    // Transisi
    $b->edge($init, $draft, 'create()');
    $b->edge($draft, $pending, 'submit ke verifikasi');

    // Step 1 paralel (5 verifikator)
    $b->edge($pending, $revisi, 'salah satu req. revisi');
    $b->edge($revisi, $draft, 'PP/PPK perbaiki + submit ulang');

    $b->edgeReject($pending, $ditolak, 'salah satu reject');

    // Step 1 → Step 2
    $b->edge($pending, $pendingKasub, 'semua approve');

    $b->edge($pendingKasub, $revisiKasub, 'request revisi');
    $b->edge($revisiKasub, $draft, 'perbaiki');
    $b->edgeReject($pendingKasub, $ditolakKasub, 'reject');

    $b->edge($pendingKasub, $ready, 'approve final');

    // Pasca READY_FOR_SPP
    $b->edge($ready, $proses, 'Operator BLU buat SPP');
    $b->edge($proses, $terbit, 'SPP signed');
    $b->edge($proses, $sebagian, 'sebagian termin');
    $b->edge($sebagian, $lengkap, 'semua termin');

    $b->edge($terbit, $spm, 'PPSPM verifikasi');
    $b->edge($spm, $npi, 'NPI signed');
    $b->edge($npi, $sp2d, 'SP2D signed');
    $b->edge($sp2d, $cair, 'eksekusi & catat BKU');
    $b->edge($cair, $end);

    // Note status mapping
    $note = $b->newId();
    $b->note($note,
        'Catatan:&#10;'.
        '• Status REVISI_* = REVISI_PPK / REVISI_PPSPM / REVISI_KOORDINATOR_KEUANGAN /&#10;'.
        '  REVISI_BENDAHARA_PENGELUARAN / REVISI_BENDAHARA_PENERIMAAN&#10;'.
        '• Status DITOLAK_* = DITOLAK_PPK / DITOLAK_PPSPM / DITOLAK_*&#10;'.
        '• Saat sub-tagihan dieksekusi, termin → SUDAH_DITAGIH dan termin&#10;'.
        '  selanjutnya unlock dari LOCKED → READY_TO_BILL (DokumenSp2d.php).',
        60, 470, 700, 130
    );

    return $b->render();
}

/* ========================================================================
 | 5. ASSEMBLE
 |======================================================================== */

$pages = [
    ['name' => 'End-to-End Flow',          'render' => 'buildPageEndToEnd',     'pageWidth' => 2400, 'pageHeight' => 1300],
    ['name' => 'Detail Verifikasi Tagihan','render' => 'buildPageVerifikasi',   'pageWidth' => 1700, 'pageHeight' => 900],
    ['name' => 'State Machine Tagihan',    'render' => 'buildPageStateMachine', 'pageWidth' => 1900, 'pageHeight' => 800],
];

$diagrams = '';
foreach ($pages as $i => $page) {
    $body = call_user_func($page['render']);
    $diagId = 'flow-' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT);
    $name = htmlspecialchars($page['name'], ENT_XML1 | ENT_QUOTES);
    $pw = $page['pageWidth'];
    $ph = $page['pageHeight'];
    $diagrams .= <<<XML

  <diagram id="{$diagId}" name="{$name}">
    <mxGraphModel dx="1422" dy="827" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="{$pw}" pageHeight="{$ph}" math="0" shadow="0">
      <root>
        <mxCell id="0"/>
        <mxCell id="1" parent="0"/>
        {$body}
      </root>
    </mxGraphModel>
  </diagram>
XML;
}

$generatedAt = date('Y-m-d H:i');
$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<mxfile host="diagrams.net" modified="{$generatedAt}" agent="SIKEREN Flow Generator" version="22.0.0">
{$diagrams}
</mxfile>
XML;

$out = __DIR__ . '/flow-tagihan-kontrak.drawio';
file_put_contents($out, $xml);

echo "✓ Tergenerate: {$out}\n";
echo "  - " . count($pages) . " halaman\n";
foreach ($pages as $p) echo "    • {$p['name']}\n";
echo "Buka di app.diagrams.net atau VS Code Draw.io extension.\n";
