@extends('layouts.guest', ['themeOverride' => 'light'])
@section('title')
    Login
@endsection
@section('content')
    <div class="section-authentication-cover">
        <div class="">
            <div class="row g-0">
                <div
                    class="col-12 col-xl-7 col-xxl-7 auth-cover-left align-items-center justify-content-center d-none d-xl-flex border-end">

                    <div class="card rounded-0 mb-0 border-0 shadow-none bg-transparent bg-none text-center">
                        <div>
                            <video autoplay muted playsinline  style="width:100%;
    height:100%;
    object-fit:cover;">
                                <source src="{{ URL::asset('logo/animasi-logo.mp4') }}" type="video/mp4"  style="object-fit: cover; width:100%;">
                            </video>
                        </div>
                    </div>

                </div>

                <div
                    class="col-12 col-xl-5 col-xxl-5 auth-cover-right align-items-center justify-content-center border-top border-4 border-primary border-gradient-1">
                    <div class="card rounded-0 m-3 mb-0 border-0 shadow-none bg-none">
                        <div class="card-body p-sm-5">
                            <img src="{{ URL::asset('logo/minilogo-sikeren.png') }}" class="mb-4" width="120"
                                alt="">
                            <h4 class="fw-bold">Sistem Informasi Keuangan dan Penagihan Terpadu</h4>
                            <p class="mb-0">BLU Kantor UPBU kelas 1 Aji Pangeran Tumenggung Pranoto - Samarinda</p>

                            <div class="form-body mt-4">
                                @if ($errors->any())
                                    <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger d-flex align-items-start mb-3"
                                        role="alert">
                                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                                        <div class="text-start">
                                            <strong>Login gagal!</strong>
                                            <ul class="mb-0 ps-3">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @endif
                                <form method="POST" action="{{ route('login') }}" class="row g-3">
                                    @csrf

                                    <div class="col-12">
                                        <label for="email" class="form-label">Email <span
                                                class="text-danger">*</span></label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                                            id="email" name="email" placeholder="Enter your email"
                                            value="{{ old('email') }}" required autocomplete="email" autofocus>

                                        
                                    </div>
                                    <div class="col-12">
                                        <label for="password" class="form-label">Password <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group" id="show_hide_password">
                                            <input id="password" type="password"
                                                class="form-control @error('password') is-invalid @enderror" name="password"
                                                required autocomplete="current-password" placeholder="Enter your password">
                                            <a href="javascript:void(0);" class="input-group-text bg-transparent"><i
                                                    class="bi bi-eye-slash-fill"></i></a>

                                            @error('password')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        
                                    </div>

                                    @if (Route::has('password.request'))
                                        <div class="col-md-6 text-end"> <a href="{{ route('password.request') }}">Lupa Password?</a>
                                        </div>
                                    @endif

                                    <div class="col-12">
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-grd-primary text-white">Login</button>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="text-center mt-2">
                                            <a href="https://aptpairport.id" class="text-decoration-none text-secondary d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-arrow-left"></i> Kembali ke aptpairport.id
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('script')
    
    <script>
        $(document).ready(function() {
            $("#show_hide_password a").on('click', function(event) {
                event.preventDefault();
                if ($('#show_hide_password input').attr("type") == "text") {
                    $('#show_hide_password input').attr('type', 'password');
                    $('#show_hide_password i').addClass("bi-eye-slash-fill");
                    $('#show_hide_password i').removeClass("bi-eye-fill");
                } else if ($('#show_hide_password input').attr("type") == "password") {
                    $('#show_hide_password input').attr('type', 'text');
                    $('#show_hide_password i').removeClass("bi-eye-slash-fill");
                    $('#show_hide_password i').addClass("bi-eye-fill");
                }
            });
        });
    </script>
@endpush
