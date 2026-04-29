<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MasterMitraVendor;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/mitra';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        // 1) Buat record mitra/vendor dulu sebagai profil utama user
        $mitra = MasterMitraVendor::create([
            'kategori' => 'PENGELUARAN',
            'jenis_entitas' => 'BADAN_USAHA',
            'nama_pihak' => $data['name'],
            'email' => $data['email'],
            'status_aktif' => true,
        ]);

        // 2) Buat user dan hubungkan ke mitra via profilable polymorphic
        $user = User::create([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'profilable_type' => \App\Models\MasterPihak::class,
            'profilable_id' => $mitra->id,
        ]);

        Role::findOrCreate('Mitra', 'web');
        $user->assignRole('Mitra');

        return $user;
    }
}
