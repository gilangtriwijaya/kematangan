@extends('layouts.dashboard')

@section('title', 'Setup 2FA')

@section('content')
<div class="container mt-5">
    <h2>Aktifkan Two-Factor Authentication</h2>

    <p>Scan QR code di bawah ini dengan aplikasi Google Authenticator.</p>

    <div class="mb-3">
        <img src="{{ $QR_Image }}" alt="QR Code">
    </div>

    <form method="POST" action="{{ route('2fa.verify') }}">
        @csrf
        <div class="mb-3">
            <label for="otp" class="form-label">Masukkan Kode OTP</label>
            <input type="text" name="otp" id="otp" class="form-control" required>
        </div>
        <button class="btn btn-primary">Aktifkan 2FA</button>
    </form>
</div>
@endsection
