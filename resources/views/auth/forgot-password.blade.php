@extends('layouts.guest')

@section('title', 'Forgot Password')

@section('content')
    <p class="text-muted small mb-3">
        Enter your email address and we will send you a password reset link.
    </p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror" required autofocus>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex align-items-center justify-content-between">
            <a href="{{ route('login') }}" class="small">Back to login</a>
            <button type="submit" class="btn btn-primary">Email Password Reset Link</button>
        </div>
    </form>
@endsection
