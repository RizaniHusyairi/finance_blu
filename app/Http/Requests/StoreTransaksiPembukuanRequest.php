<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi jurnal manual SILABI ("Input Transaksi") Bendahara Pengeluaran.
 */
class StoreTransaksiPembukuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasAnyRole(['Super Admin', 'Bendahara Pengeluaran']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'no_bukti' => ['required', 'string', 'max:100'],
            'kode_transaksi' => ['required', 'string', Rule::exists('kode_transaksi', 'kode')->where('status_aktif', true)],
            'uraian' => ['required', 'string'],
            'jumlah_kotor' => ['required', 'numeric', 'min:0.01'],
            'penerima_id' => ['nullable', 'integer', Rule::exists('master_pihak', 'id')],
            'keg_output_akun_id' => ['nullable', 'integer', Rule::exists('master_coas', 'id')],
            'cara_pembayaran' => ['nullable', Rule::in(['UP', 'TU', 'GU', 'LS'])],
            'pungut_setor_pajak' => ['nullable', 'string', 'max:20'],
            'unsur_pajak_ppn' => ['nullable', 'numeric', 'min:0'],
            'unsur_pajak_pph' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
