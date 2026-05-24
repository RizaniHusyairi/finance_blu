<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMasterPegawaiRequest;
use App\Http\Requests\Admin\UpdateMasterPegawaiRequest;
use App\Models\MasterPegawai;
use App\Models\User;
use Illuminate\Http\Request;

class MasterPegawaiController extends Controller
{
    public function index(Request $request)
    {
        $pegawai = MasterPegawai::query()
            ->with('user.roles')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->string('q')->lower() . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->whereRaw('LOWER(nama_lengkap) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(nip) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(jabatan) LIKE ?', [$term]);
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status_aktif', $request->boolean('status'));
            })
            ->orderBy('nama_lengkap')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total'      => MasterPegawai::count(),
            'aktif'      => MasterPegawai::where('status_aktif', true)->count(),
            'nonaktif'   => MasterPegawai::where('status_aktif', false)->count(),
            'punya_user' => User::where('profilable_type', MasterPegawai::class)->count(),
        ];

        return view('admin.pegawai.index', compact('pegawai', 'stats'));
    }

    public function create()
    {
        return view('admin.pegawai.create', [
            'pegawai' => new MasterPegawai(['status_aktif' => true]),
        ]);
    }

    public function store(StoreMasterPegawaiRequest $request)
    {
        $pegawai = MasterPegawai::create($request->validated());

        return redirect()
            ->route('admin.pegawai.show', $pegawai)
            ->with('success', "Pegawai {$pegawai->nama_lengkap} berhasil ditambahkan.");
    }

    public function show(MasterPegawai $pegawai)
    {
        $pegawai->load('user.roles');

        return view('admin.pegawai.show', compact('pegawai'));
    }

    public function edit(MasterPegawai $pegawai)
    {
        return view('admin.pegawai.edit', compact('pegawai'));
    }

    public function update(UpdateMasterPegawaiRequest $request, MasterPegawai $pegawai)
    {
        $pegawai->update($request->validated());

        return redirect()
            ->route('admin.pegawai.show', $pegawai)
            ->with('success', "Data {$pegawai->nama_lengkap} berhasil diperbarui.");
    }

    public function destroy(MasterPegawai $pegawai)
    {
        if ($pegawai->user()->exists()) {
            return back()->with('error', 'Pegawai ini masih memiliki akun user. Hapus akun user-nya terlebih dahulu.');
        }

        $nama = $pegawai->nama_lengkap;
        $pegawai->delete();

        return redirect()
            ->route('admin.pegawai.index')
            ->with('success', "Pegawai {$nama} berhasil dihapus.");
    }

    public function toggle(MasterPegawai $pegawai)
    {
        $pegawai->update(['status_aktif' => ! $pegawai->status_aktif]);

        $label = $pegawai->status_aktif ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Pegawai {$pegawai->nama_lengkap} berhasil {$label}.");
    }
}
