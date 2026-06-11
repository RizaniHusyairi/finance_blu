<?php

namespace App\Services;

use App\Models\DokumenNpi;
use App\Models\DokumenSp2d;
use App\Models\DokumenSpm;
use App\Models\DokumenSpp;
use App\Models\Tagihan;
use App\Models\User;
use App\Models\WorkflowApproval;
use App\Models\WorkflowInstance;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Notifier WhatsApp terpusat untuk alur verifikasi berbasis workflow.
 *
 * Dipakai semua engine workflow (WorkflowService, PerjaldinWorkflowService,
 * SppPerjaldinWorkflowService, dan turunannya untuk Kontrak/Honorarium) untuk
 * mengirim WA ke verifikator pada STEP AKTIF (status PENDING) sebuah instance.
 *
 * Tujuan: ketika dokumen (SPP/SPM/NPI/SP2D/Tagihan) naik ke step berikutnya,
 * verifikator pada step baru otomatis mendapat notifikasi WA.
 *
 * Best-effort: kegagalan kirim WA tidak melempar exception.
 */
class WorkflowWaNotifier
{
    public function __construct(private readonly WhatsappService $whatsapp)
    {
    }

    /**
     * Kirim WA ke seluruh verifikator PENDING pada step aktif instance.
     */
    public function notifyPendingApprovals(WorkflowInstance $instance): void
    {
        try {
            $instance->loadMissing('approvals.assignedUser.profilable', 'workflowable');

            $pending = $instance->approvals
                ->where('urutan_step', $instance->step_saat_ini)
                ->where('status', 'PENDING');

            if ($pending->isEmpty()) {
                return;
            }

            $doc = $instance->workflowable;
            if (! $doc) {
                return;
            }

            $meta = $this->resolveDocMeta($doc);
            $sudahDikirim = [];

            foreach ($pending as $approval) {
                $user = $approval->assignedUser
                    ?: $this->resolveSingleUserByRole($this->roleName($approval->role_code));

                $noHp = $user?->profilable->nomor_hp ?? null;
                if (! $user || ! $noHp) {
                    Log::warning("WA workflow: verifikator/nomor HP tidak tersedia untuk role {$approval->role_code} ({$meta['label']} {$meta['nomor']}).");
                    continue;
                }

                if (in_array($noHp, $sudahDikirim, true)) {
                    continue;
                }
                $sudahDikirim[] = $noHp;

                $message = $this->buildMessage($meta, $user, $approval);
                $this->whatsapp->sendMessage($noHp, $message);
            }
        } catch (\Throwable $e) {
            Log::error('WorkflowWaNotifier gagal kirim WA: ' . $e->getMessage());
        }
    }

    /**
     * Resolusi metadata dokumen untuk isi pesan & link.
     *
     * @return array{label:string, nomor:string, uraian:string, nilai:?float, tipe:?string, docKey:string, tagihanId:?int}
     */
    private function resolveDocMeta($doc): array
    {
        $tagihan = $this->resolveTagihan($doc);
        $uraian = $tagihan?->deskripsi ?: '-';
        $nilai = $tagihan ? (float) ($tagihan->total_bruto ?? $tagihan->total_netto ?? 0) : null;
        $tipe = $tagihan?->tipe_tagihan; // PERJALDIN | KONTRAK | HONORARIUM

        return match (true) {
            $doc instanceof Tagihan => [
                'label' => 'Tagihan',
                'nomor' => $doc->nomor_tagihan ?? ('#' . $doc->id),
                'uraian' => $doc->deskripsi ?: '-',
                'nilai' => (float) ($doc->total_bruto ?? $doc->total_netto ?? 0),
                'tipe' => $doc->tipe_tagihan,
                'docKey' => 'TAGIHAN',
                'tagihanId' => $doc->id,
            ],
            $doc instanceof DokumenSpp => [
                'label' => 'SPP', 'nomor' => $doc->nomor_spp ?? ('#' . $doc->id),
                'uraian' => $uraian, 'nilai' => $nilai, 'tipe' => $tipe, 'docKey' => 'SPP', 'tagihanId' => $tagihan?->id,
            ],
            $doc instanceof DokumenSpm => [
                'label' => 'SPM', 'nomor' => $doc->nomor_spm ?? ('#' . $doc->id),
                'uraian' => $uraian, 'nilai' => $nilai, 'tipe' => $tipe, 'docKey' => 'SPM', 'tagihanId' => $tagihan?->id,
            ],
            $doc instanceof DokumenNpi => [
                'label' => 'NPI', 'nomor' => $doc->nomor_npi ?? ('#' . $doc->id),
                'uraian' => $uraian, 'nilai' => $nilai, 'tipe' => $tipe, 'docKey' => 'NPI', 'tagihanId' => $tagihan?->id,
            ],
            $doc instanceof DokumenSp2d => [
                'label' => 'SP2D', 'nomor' => $doc->nomor_sp2d ?? ('#' . $doc->id),
                'uraian' => $uraian, 'nilai' => $nilai, 'tipe' => $tipe, 'docKey' => 'SP2D', 'tagihanId' => $tagihan?->id,
            ],
            default => [
                'label' => 'Dokumen', 'nomor' => '#' . ($doc->id ?? '-'),
                'uraian' => $uraian, 'nilai' => $nilai, 'tipe' => $tipe, 'docKey' => 'LAINNYA', 'tagihanId' => $tagihan?->id,
            ],
        };
    }

    /**
     * Telusuri rantai dokumen sampai ke Tagihan untuk ambil uraian/nilai/tipe.
     */
    private function resolveTagihan($doc): ?Tagihan
    {
        try {
            if ($doc instanceof Tagihan) {
                return $doc;
            }
            if ($doc instanceof DokumenSpp) {
                return $doc->tagihan;
            }
            if ($doc instanceof DokumenSpm) {
                return $doc->spp?->tagihan;
            }
            if ($doc instanceof DokumenNpi) {
                return $doc->spm?->spp?->tagihan;
            }
            if ($doc instanceof DokumenSp2d) {
                return $doc->npi?->spm?->spp?->tagihan;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    private function buildMessage(array $meta, User $user, WorkflowApproval $approval): string
    {
        $peran = $approval->nama_step ?: $this->roleName($approval->role_code);
        $url = $this->resolveActionUrl($meta['docKey'], $meta['tipe'], $approval->role_code, $meta['tagihanId'] ?? null);
        $nilaiBaris = $meta['nilai'] !== null
            ? "\n*Nilai:* Rp " . number_format((float) $meta['nilai'], 0, ',', '.')
            : '';

        $message  = "*PENGAJUAN VERIFIKASI {$meta['label']}*\n\n";
        $message .= "Yth. {$user->name},\n";
        $message .= "Terdapat dokumen {$meta['label']} yang menunggu verifikasi Anda sebagai *{$peran}*.\n\n";
        $message .= "*Nomor:* {$meta['nomor']}\n";
        $message .= "*Uraian:* {$meta['uraian']}{$nilaiBaris}\n\n";
        $message .= "Silakan login ke SIKEREN-BLU untuk meninjau dan memproses dokumen.";
        if ($url) {
            $message .= "\n{$url}";
        }

        return $message;
    }

    /**
     * Resolusi route index halaman verifikasi sesuai jenis dokumen, tipe tagihan,
     * dan role verifikator. Dibungkus try/catch agar nama route yang tidak cocok
     * tidak menggagalkan notifikasi.
     */
    private function resolveActionUrl(string $docKey, ?string $tipe, string $roleCode, ?int $tagihanId = null): ?string
    {
        if ($tagihanId && in_array($docKey, ['SPP', 'SPM', 'NPI', 'SP2D'], true)) {
            try {
                return route('proses-tagihan.show', $tagihanId);
            } catch (\Throwable $e) {
                return null;
            }
        }

        $tipe = Str::upper((string) $tipe);
        $role = $this->normalizeRole($roleCode);

        $name = $this->routeName($docKey, $tipe, $role);
        if (! $name) {
            return null;
        }

        try {
            return route($name);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function routeName(string $docKey, string $tipe, string $role): ?string
    {
        return match ($docKey) {
            'TAGIHAN' => match ($tipe) {
                'KONTRAK' => 'verifikasi-tagihan-kontrak.index',
                'HONORARIUM' => 'verifikasi-tagihan-honorarium.index',
                'PERJALDIN' => match ($role) {
                    'PPK' => 'verifikasi-ppk.perjaldin.index',
                    'PPSPM' => 'verifikasi-ppspm.perjaldin.index',
                    'BENDAHARA_PENGELUARAN' => 'verifikasi-bendahara.perjaldin.index',
                    'BENDAHARA_PENERIMAAN' => 'verifikasi-bendahara-penerimaan.perjaldin.index',
                    'KOORDINATOR_KEUANGAN' => 'verifikasi-koordinator.perjaldin.index',
                    'KASUBBAG' => 'verifikasi-kasubag.index',
                    default => null,
                },
                default => null,
            },
            'SPP' => match ($tipe) {
                'KONTRAK' => 'verifikasi-spp.kontrak.index',
                'HONORARIUM' => 'verifikasi-spp.honor.index',
                'PERJALDIN' => 'verifikasi-spp.perjaldin.index',
                default => null,
            },
            'SPM' => match ($tipe) {
                'HONORARIUM' => 'verifikasi-spm.honor.index',
                'PERJALDIN' => match ($role) {
                    'PPSPM' => 'verifikasi-ppspm.spm-perjaldin.index',
                    'KASUBBAG' => 'verifikasi-kasubag.spm-perjaldin.index',
                    'KOORDINATOR_KEUANGAN' => 'verifikasi-koordinator.spm-perjaldin.index',
                    default => null,
                },
                'KONTRAK' => match ($role) {
                    'PPSPM' => 'verifikasi-ppspm.spm.kontrak.index',
                    'KASUBBAG' => 'verifikasi-kasubag.spm.kontrak.index',
                    'KOORDINATOR_KEUANGAN' => 'verifikasi-koordinator.spm.kontrak.index',
                    default => null,
                },
                default => null,
            },
            'NPI' => match ($tipe) {
                'PERJALDIN' => 'verifikasi-npi.perjaldin.index',
                'HONORARIUM' => 'verifikasi-npi.honor.index',
                'KONTRAK' => match ($role) {
                    'PPK' => 'verifikasi-ppk.npi.kontrak.index',
                    'KASUBBAG' => 'verifikasi-kasubag.npi.kontrak.index',
                    'KOORDINATOR_KEUANGAN' => 'verifikasi-koordinator.npi.kontrak.index',
                    'BENDAHARA_PENERIMAAN' => 'verifikasi-bendahara-penerimaan.npi.kontrak.index',
                    default => null,
                },
                default => null,
            },
            'SP2D' => match ($tipe) {
                'PERJALDIN' => 'verifikasi-sp2d.perjaldin.index',
                'HONORARIUM' => 'verifikasi-sp2d.honor.index',
                'KONTRAK' => match ($role) {
                    'PPK' => 'verifikasi-ppk.sp2d.kontrak.index',
                    'PPSPM' => 'verifikasi-ppspm.sp2d.kontrak.index',
                    'KASUBBAG' => 'verifikasi-kasubag.sp2d.kontrak.index',
                    'KOORDINATOR_KEUANGAN' => 'verifikasi-koordinator.sp2d.kontrak.index',
                    default => null,
                },
                default => null,
            },
            default => null,
        };
    }

    /**
     * Normalisasi role_code menjadi token baku.
     */
    private function normalizeRole(string $roleCode): string
    {
        $n = Str::upper(str_replace([' ', '-'], '_', trim($roleCode)));

        return match ($n) {
            'KEPALA_SUBBAGIAN_KEUANGAN_DAN_TATA_USAHA' => 'KASUBBAG',
            default => $n,
        };
    }

    /**
     * Mapping role_code internal -> nama role Spatie (untuk lookup user & label).
     */
    private function roleName(string $roleCode): string
    {
        return match ($this->normalizeRole($roleCode)) {
            'PPK' => 'PPK',
            'PPSPM' => 'PPSPM',
            'KASUBBAG' => 'Kepala Subbagian Keuangan dan Tata Usaha',
            'KOORDINATOR_KEUANGAN' => 'Koordinator Keuangan',
            'BENDAHARA_PENGELUARAN' => 'Bendahara Pengeluaran',
            'BENDAHARA_PENERIMAAN' => 'Bendahara Penerimaan',
            'OPERATOR_BLU' => 'Operator BLU',
            'OPERATOR_PERJALDIN' => 'Operator Perjaldin',
            default => $roleCode,
        };
    }

    private function resolveSingleUserByRole(string $roleName): ?User
    {
        try {
            $users = User::role($roleName)->with('profilable')->get();
        } catch (\Throwable $e) {
            return null;
        }

        return $users->count() === 1 ? $users->first() : null;
    }
}
