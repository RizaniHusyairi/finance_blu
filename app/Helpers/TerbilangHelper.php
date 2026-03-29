<?php

if (!function_exists('terbilang')) {
    function terbilang($angka)
    {
        $angka = abs($angka);
        $baca = array("", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas");
        $terbilang = "";

        if ($angka < 12) {
            $terbilang = " " . $baca[$angka];
        } elseif ($angka < 20) {
            $terbilang = terbilang($angka - 10) . " Belas";
        } elseif ($angka < 100) {
            $terbilang = terbilang($angka / 10) . " Puluh" . terbilang($angka % 10);
        } elseif ($angka < 200) {
            $terbilang = " Seratus" . terbilang($angka - 100);
        } elseif ($angka < 1000) {
            $terbilang = terbilang($angka / 100) . " Ratus" . terbilang($angka % 100);
        } elseif ($angka < 2000) {
            $terbilang = " Seribu" . terbilang($angka - 1000);
        } elseif ($angka < 1000000) {
            $terbilang = terbilang($angka / 1000) . " Ribu" . terbilang($angka % 1000);
        } elseif ($angka < 1000000000) {
            $terbilang = terbilang($angka / 1000000) . " Juta" . terbilang($angka % 1000000);
        } elseif ($angka < 1000000000000) {
            $terbilang = terbilang($angka / 1000000000) . " Milyar" . terbilang(fmod($angka, 1000000000));
        } elseif ($angka < 1000000000000000) {
            $terbilang = terbilang($angka / 1000000000000) . " Trilyun" . terbilang(fmod($angka, 1000000000000));
        }

        return $terbilang;
    }
}

if (!function_exists('terbilang_rupiah')) {
    function terbilang_rupiah($angka)
    {
        if ($angka == 0) return "NOL RUPIAH";
        $str = trim(terbilang($angka));
        return strtoupper($str . " RUPIAH");
    }
}
