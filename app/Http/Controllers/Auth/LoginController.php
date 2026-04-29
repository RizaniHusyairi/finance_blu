<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;
    
    private const INTERNAL_ROLES = [
        'Super Admin',
        'KPA',
        'Kepala Subbagian Keuangan dan Tata Usaha',
        'Kepala Seksi Pelayanan dan Kerjasama',
        'PPK',
        'PPSPM',
        'Bendahara Pengeluaran',
        'Bendahara Penerimaan',
        'Pejabat Pengadaan',
        'Operator BLU',
        'PPABP',
        'Operator Perjaldin',
        'Koordinator Keuangan',
    ];

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    protected function authenticated(Request $request, $user)
    {
        if ($user->hasRole('Mitra')) {
            return redirect()->route('mitra.dashboard');
        }

        if ($user->hasAnyRole(self::INTERNAL_ROLES)) {
            return redirect()->route('dashboard');
        }

        $this->guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'email' => 'Akun ini belum memiliki role akses.',
        ]);
    }

    /**
     * Get the failed login response instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            $this->username() => ['Email atau password yang Anda masukkan salah.'],
        ]);
    }

    /**
     * Redirect the user after determining they are locked out.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function sendLockoutResponse(Request $request)
    {
        $seconds = $this->limiter()->availableIn(
            $this->throttleKey($request)
        );

        throw ValidationException::withMessages([
            $this->username() => [
                "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
            ],
        ])->status(429);
    }
}
