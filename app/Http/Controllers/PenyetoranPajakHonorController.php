<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * PenyetoranPajakHonorController
 *
 * Skeleton minimal untuk mendukung registrasi route `pajak-potongan.honor.*`
 * pada task 3.1 (Spec: penyetoran-pajak-honorarium). Implementasi method
 * lengkap (helper privat `findHonorPajakPotongan`, `penyetoranReadinessError`,
 * `resolveSp2d`, beserta logika index/show/storeBilling/storeNtpn/cetak/bupot)
 * akan ditambahkan pada task 3.2 dan seterusnya sesuai design.
 *
 * Sementara ini setiap method mengembalikan HTTP 501 (Not Implemented) agar
 * `php artisan route:list` dan tooling lain dapat menelusuri kelas tanpa
 * menyebabkan fatal error, dan agar pemanggilan tak sengaja terhadap route
 * yang belum siap memberi sinyal eksplisit.
 */
class PenyetoranPajakHonorController extends Controller
{
    public function index(Request $request)
    {
        abort(501, 'PenyetoranPajakHonorController::index belum diimplementasikan (lihat task 4.1).');
    }

    public function show($id)
    {
        abort(501, 'PenyetoranPajakHonorController::show belum diimplementasikan (lihat task 5.1).');
    }

    public function storeBilling(Request $request, $id)
    {
        abort(501, 'PenyetoranPajakHonorController::storeBilling belum diimplementasikan (lihat task 7.1).');
    }

    public function storeNtpn(Request $request, $id)
    {
        abort(501, 'PenyetoranPajakHonorController::storeNtpn belum diimplementasikan (lihat task 7.4).');
    }

    public function cetak($id)
    {
        abort(501, 'PenyetoranPajakHonorController::cetak belum diimplementasikan (lihat task 8.1).');
    }

    public function bupot($detailHonorarium)
    {
        abort(501, 'PenyetoranPajakHonorController::bupot belum diimplementasikan (lihat task 8.2).');
    }
}
