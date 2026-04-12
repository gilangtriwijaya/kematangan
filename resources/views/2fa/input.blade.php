@extends('layouts.dashboard')

@section('title', 'Masukkan Kode OTP')

@section('content')
<div class="container mt-5">
    <h2>Verifikasi 2FA</h2>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('2fa.process') }}">
        @csrf
        <div class="mb-3">
            <label for="otp" class="form-label">Kode OTP</label>
            <input type="text" name="otp" id="otp" class="form-control" required>
        </div>
        <button class="btn btn-success">Verifikasi</button>
    </form>
</div>
@endsection
