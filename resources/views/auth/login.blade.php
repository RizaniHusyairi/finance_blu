@extends('layouts.guest', ['themeOverride' => 'light'])
@section('title')
    Login
@endsection
@section('content')
    <div class="section-authentication-cover">
        <div class="">
            <div class="row g-0">
                <div
                    class="col-12 col-xl-8 col-xxl-8 auth-cover-left align-items-center justify-content-center d-none d-xl-flex border-end">

                    <div class="card rounded-0 mb-0 border-0 shadow-none bg-transparent bg-none text-center">
                        <div>
                            <video autoplay muted playsinline  style="width:50%;
    height:100%;
    object-fit:cover;">
                                <source src="{{ URL::asset('logo/logo_apt_vid.mp4') }}" type="video/mp4"  style="object-fit: cover; width:100%;">
                            </video>
                        </div>
                    </div>

                </div>

                <div
                    class="col-12 col-xl-4 col-xxl-4 auth-cover-right align-items-center justify-content-center border-top border-4 border-primary border-gradient-1">
                    <div class="card rounded-0 m-3 mb-0 border-0 shadow-none bg-none">
                        <div class="card-body p-sm-5">
                            <img src="{{ URL::asset('logo/logo-apt.svg') }}" class="mb-4" width="145"
                                alt="">
                            <h4 class="fw-bold">Sistem Informasi Keuangan dan Tagihan </h4>
                            <p class="mb-0">BLU Kantor UPBU kelas 1 Aji Pangeran Tumenggung Pranoto - Samarinda</p>

                            <div class="form-body mt-4">
                                <form method="POST" action="{{ route('login') }}" class="row g-3">
                                    @csrf

                                    <div class="col-12">
                                        <label for="email" class="form-label">Email <span
                                                class="text-danger">*</span></label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                                            id="email" name="email" placeholder="Enter your email"
                                            value="{{ old('email') }}" required autocomplete="email" autofocus>

                                        @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
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
