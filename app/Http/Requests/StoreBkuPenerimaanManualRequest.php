<?php

namespace App\Http\Requests;

use App\Enums\JenisTransaksiPenerimaan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi pencatatan mutasi kas non-jasa manual di BKU Penerimaan
 * (Pemindahbukuan/PBK, Setor Kas Negara, Pengembalian, Bunga, dll).
 */
class StoreBkuPenerimaanManualRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasAnyRole(['Super Admin', 'Bendahara Penerimaan']);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tanggal_transaksi' => ['required', 'date'],
            'nomor_bukti' => ['required', 'string', 'max:100'],
            'uraian' => ['required', 'string', 'max:255'],
            'arus_kas' => ['required', Rule::in(['KREDIT_KELUAR', 'DEBIT_MASUK'])],
            'jenis_transaksi' => ['required', Rule::in(JenisTransaksiPenerimaan::values())],
            'nominal' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
