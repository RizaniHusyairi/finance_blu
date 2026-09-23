<?php

namespace App\Console\Commands;

use App\Models\TagihanJasa;
use App\Services\Btn\BtnSnapClient;
use App\Services\Btn\BtnSnapConfig;
use App\Services\Btn\BtnSnapException;
use App\Services\Btn\BtnSnapSignature;
use App\Services\Btn\BtnSnapVirtualAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class BtnVirtualAccountCommand extends Command
{
    protected $signature = 'btn:va {action : check|token|inquiry|recover|update|delete|status|report}
        {--tagihan= : ID tagihan jasa} {--start= : Tanggal awal YYYY-MM-DD} {--end= : Tanggal akhir YYYY-MM-DD}
        {--inquiry-id= : inquiryRequestId BTN} {--payment-id= : paymentRequestId BTN}
        {--execute : Jalankan update/delete ke BTN}';

    protected $description = 'Periksa konfigurasi, kelola VA SNAP, dan baca laporan/status BTN';

    public function handle(BtnSnapConfig $config, BtnSnapClient $client, BtnSnapVirtualAccount $va, BtnSnapSignature $signature): int
    {
        try {
            $action = $this->argument('action');
            $config->assertOutbound();
            if ($action === 'check') {
                $signature->rsa('configuration-check', $config->get('private_key'));
                $this->info('Konfigurasi outbound dan private key valid. Belum melakukan koneksi BTN.');
                $this->line('Mode: '.$config->mode().'; inbound: '.$config->get('inbound_auth', 'disabled'));

                return self::SUCCESS;
            }
            if ($action === 'token') {
                $client->token();
                $this->info('Token BTN berhasil diperoleh; nilainya tidak ditampilkan.');

                return self::SUCCESS;
            }
            if ($action === 'report') {
                Validator::make(['start' => $this->option('start'), 'end' => $this->option('end')], [
                    'start' => 'required|date_format:Y-m-d', 'end' => 'required|date_format:Y-m-d|after_or_equal:start',
                ])->validate();
                $result = $va->report($this->option('start'), $this->option('end'));
            } else {
                $tagihan = TagihanJasa::findOrFail($this->option('tagihan'));
                if (in_array($action, ['update', 'delete'], true) && ! $this->option('execute')) {
                    $this->warn('Belum dikirim. Tambahkan --execute untuk '.$action.' VA tagihan '.$tagihan->nomor_tagihan.'.');

                    return self::FAILURE;
                }
                $result = match ($action) {
                    'inquiry' => $va->inquiry($tagihan),
                    'recover' => $va->recover($tagihan),
                    'update' => $va->update($tagihan),
                    'delete' => $va->delete($tagihan),
                    'status' => $this->status($va, $tagihan),
                    default => throw new BtnSnapException('Action tidak dikenal.'),
                };
            }
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e instanceof BtnSnapException ? $e->getMessage() : 'Perintah gagal. Periksa ID tagihan, format parameter, dan log aplikasi.');

            return self::FAILURE;
        }
    }

    private function status(BtnSnapVirtualAccount $va, TagihanJasa $tagihan): array
    {
        Validator::make(['id' => $this->option('inquiry-id')], ['id' => 'required|string|max:64'])->validate();

        return $va->status($tagihan, $this->option('inquiry-id'), $this->option('payment-id'));
    }
}
