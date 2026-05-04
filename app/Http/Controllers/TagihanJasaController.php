<?php

namespace App\Http\Controllers;

use App\Models\LayananJasa;
use App\Models\MasterPihak;
use App\Models\TagihanJasa;
use App\Models\User;
use App\Services\WhatsappService;
use App\Services\WorkflowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class TagihanJasaController extends Controller
{
    public function index()
    {
        $tagihans = TagihanJasa::with(['mitra', 'creator'])
            ->latest()
            ->get();

        return view('tagihan_jasa.index', compact('tagihans'));
    }

    public function create()
    {
        $mitras = MasterPihak::query()
            ->where('status_aktif', true)
            ->orderBy('nama_pihak')
            ->get();

        $layanans = LayananJasa::query()
            ->where('is_active', true)
            ->orderBy('nama_layanan')
            ->get();

        return view('tagihan_jasa.create', compact('mitras', 'layanans'));
    }

    public function store(Request $request, WorkflowService $workflowService)
    {
        $validated = $request->validate([
            'mitra_id' => ['required', 'exists:master_pihak,id'],
            'tanggal_tagihan' => ['required', 'date'],
            'nomor_kontrak' => ['nullable', 'string', 'max:255'],
            'tanggal_mulai_kontrak' => ['nullable', 'date'],
            'tanggal_selesai_kontrak' => ['nullable', 'date', 'after_or_equal:tanggal_mulai_kontrak'],
            'file_kontrak' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'layanan' => ['required', 'array', 'min:1'],
            'layanan.*.id' => ['required', 'exists:layanan_jasas,id'],
            'layanan.*.qty' => ['required', 'numeric', 'min:0.01'],
            'layanan.*.harga_satuan' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $tagihan = DB::transaction(function () use ($request, $validated, $workflowService) {
                $fileKontrakPath = $request->hasFile('file_kontrak')
                    ? $request->file('file_kontrak')->store('tagihan-jasa/kontrak', 'public')
                    : null;

                $totalTagihan = collect($validated['layanan'])->sum(function ($row) {
                    return (float) $row['qty'] * (float) $row['harga_satuan'];
                });

                $tagihan = TagihanJasa::create([
                    'mitra_id' => $validated['mitra_id'],
                    'file_kontrak' => $fileKontrakPath,
                    'nomor_kontrak' => $validated['nomor_kontrak'] ?? null,
                    'tanggal_mulai_kontrak' => $validated['tanggal_mulai_kontrak'] ?? null,
                    'tanggal_selesai_kontrak' => $validated['tanggal_selesai_kontrak'] ?? null,
                    'nomor_tagihan' => $this->generateNomorTagihan(),
                    'tanggal_tagihan' => $validated['tanggal_tagihan'],
                    'total_tagihan' => $totalTagihan,
                    'status' => 'VERIFIKASI_KOORDINATOR',
                    'created_by' => Auth::id(),
                ]);

                foreach ($validated['layanan'] as $row) {
                    $qty = (float) $row['qty'];
                    $hargaSatuan = (float) $row['harga_satuan'];

                    $tagihan->details()->create([
                        'layanan_jasa_id' => $row['id'],
                        'qty' => $qty,
                        'harga_satuan' => $hargaSatuan,
                        'subtotal' => $qty * $hargaSatuan,
                    ]);
                }

                $workflowService->startWorkflow('TAGIHAN_JASA', $tagihan);

                return $tagihan;
            });

            return redirect()
                ->route('tagihan-jasa.show', $tagihan->id)
                ->with('success', 'Tagihan Jasa berhasil dibuat dan masuk alur verifikasi.');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal membuat tagihan jasa: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $tagihan = TagihanJasa::with([
            'mitra',
            'creator',
            'details.layananJasa',
            'workflowInstance.approvals.actedByUser',
        ])->findOrFail($id);

        return view('tagihan_jasa.show', compact('tagihan'));
    }

    public function generateInvoicePdf($id)
    {
        $tagihan = TagihanJasa::with(['mitra', 'details.layananJasa'])->findOrFail($id);
        $terbilang = function_exists('terbilang_rupiah')
            ? terbilang_rupiah((float) $tagihan->total_tagihan)
            : trim(terbilang((float) $tagihan->total_tagihan)) . ' Rupiah';

        $pdf = Pdf::loadView('tagihan_jasa.pdf', compact('tagihan', 'terbilang'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('invoice-' . $tagihan->nomor_tagihan . '.pdf');
    }

    public function publish(Request $request, $id, WhatsappService $whatsappService)
    {
        $validated = $request->validate([
            'wa_tujuan' => ['required', 'string', 'max:30'],
        ]);

        $tagihan = TagihanJasa::with(['mitra', 'workflowInstance'])->findOrFail($id);

        if (!$tagihan->workflowInstance || $tagihan->workflowInstance->status !== 'APPROVED') {
            return back()->with('error', 'Tagihan belum selesai diverifikasi.');
        }

        $accountInfo = $this->ensureMitraAccount($tagihan->mitra);

        $tagihan->update([
            'status' => 'PUBLISHED',
            'nomor_va' => $tagihan->nomor_va ?: $this->generateNomorVa($tagihan),
        ]);

        $message = $this->buildWhatsappMessage($tagihan->fresh('mitra'), $accountInfo);
        $whatsappService->sendMessage($validated['wa_tujuan'], $message);

        return back()
            ->with('success', 'Tagihan berhasil dipublish dan notifikasi WA diproses.')
            ->with('wa_message_preview', $message)
            ->with('is_new_mitra', $accountInfo['is_new'])
            ->with('mitra_email', $accountInfo['email'])
            ->with('mitra_password', $accountInfo['password']);
    }

    public function markAsPaid($id)
    {
        $tagihan = TagihanJasa::findOrFail($id);

        if ($tagihan->status !== 'PUBLISHED') {
            return back()->with('error', 'Hanya tagihan yang sudah dipublish yang dapat ditandai lunas.');
        }

        $tagihan->update(['status' => 'LUNAS']);

        return back()->with('success', 'Tagihan berhasil ditandai LUNAS.');
    }

    public function autoApproveAll($id)
    {
        $tagihan = TagihanJasa::with('workflowInstance.approvals')->findOrFail($id);

        try {
            DB::transaction(function () use ($tagihan) {
                $instance = $tagihan->workflowInstance;

                if (!$instance || $instance->status !== 'IN_PROGRESS') {
                    throw new \RuntimeException('Workflow tidak sedang berjalan.');
                }

                $instance->approvals()
                    ->whereIn('status', ['PENDING', 'WAITING'])
                    ->update([
                        'status' => 'APPROVED',
                        'acted_by_user_id' => Auth::id(),
                        'acted_at' => now(),
                        'catatan' => 'Auto-approved untuk testing',
                        'ip_address' => request()->ip(),
                    ]);

                $lastStep = (int) $instance->approvals()->max('urutan_step');
                $instance->update([
                    'step_saat_ini' => $lastStep,
                    'status' => 'APPROVED',
                ]);

                $tagihan->update(['status' => 'VERIFIKASI_KABANDARA']);
            });

            return back()->with('success', 'Semua step verifikasi berhasil di-auto-approve.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal auto-approve: ' . $e->getMessage());
        }
    }

    private function generateNomorTagihan(): string
    {
        $prefix = 'PNBP-JASA/' . now()->format('Ymd');
        $count = TagihanJasa::whereDate('created_at', now()->toDateString())->count() + 1;

        do {
            $nomor = $prefix . '/' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
            $count++;
        } while (TagihanJasa::where('nomor_tagihan', $nomor)->exists());

        return $nomor;
    }

    private function generateNomorVa(TagihanJasa $tagihan): string
    {
        return '88' . str_pad((string) $tagihan->id, 10, '0', STR_PAD_LEFT);
    }

    private function ensureMitraAccount(MasterPihak $mitra): array
    {
        $user = $mitra->user;

        if ($user) {
            return [
                'is_new' => false,
                'email' => $user->email,
                'password' => null,
            ];
        }

        $email = $mitra->email ?: 'mitra-' . $mitra->id . '@sikeren.id';

        if (User::where('email', $email)->exists()) {
            $email = 'mitra-' . $mitra->id . '-' . Str::lower(Str::random(5)) . '@sikeren.id';
        }
        $password = Str::password(10);

        Role::findOrCreate('Mitra', 'web');

        $user = User::create([
            'email' => $email,
            'password' => Hash::make($password),
            'profilable_type' => MasterPihak::class,
            'profilable_id' => $mitra->id,
        ]);
        $user->assignRole('Mitra');

        return [
            'is_new' => true,
            'email' => $email,
            'password' => $password,
        ];
    }

    private function buildWhatsappMessage(TagihanJasa $tagihan, array $accountInfo): string
    {
        $message = "*PEMBERITAHUAN TAGIHAN PNBP*\n\n";
        $message .= "Yth. " . ($tagihan->mitra->nama_pihak ?? '-') . ",\n\n";
        $message .= "Berikut adalah informasi tagihan layanan Anda:\n";
        $message .= "No Tagihan: *" . $tagihan->nomor_tagihan . "*\n";
        $message .= "Total Tagihan: *Rp " . number_format((float) $tagihan->total_tagihan, 0, ',', '.') . "*\n\n";
        $message .= "Silakan lakukan pembayaran melalui Virtual Account Bank BTN berikut:\n";
        $message .= "No VA: *" . ($tagihan->nomor_va ?? '-') . "*\n\n";
        $message .= "----------------------------------------\n";
        $message .= "*AKUN PORTAL MITRA*\n";
        $message .= "Email Login: " . ($accountInfo['email'] ?? '-') . "\n";

        if (!empty($accountInfo['password'])) {
            $message .= "Password: " . $accountInfo['password'] . "\n";
            $message .= "Mohon segera ubah password setelah login pertama.\n";
        } else {
            $message .= "Gunakan password akun yang sudah terdaftar sebelumnya.\n";
        }

        $message .= "Login Portal: " . route('login') . "\n";
        $message .= "----------------------------------------\n\n";
        $message .= "Terima kasih atas kerja sama Anda.\n";
        $message .= "_Sistem Informasi Keuangan (SIKEREN)_";

        return $message;
    }
}
