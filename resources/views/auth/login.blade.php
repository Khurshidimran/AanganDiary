@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   class="form-control @error('email') is-invalid @enderror" required autofocus>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password"
                   class="form-control @error('password') is-invalid @enderror" required>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="remember" id="remember" class="form-check-input">
            <label for="remember" class="form-check-label">Remember me</label>
        </div>

        <div class="d-flex align-items-center justify-content-between">
            <a href="{{ route('password.request') }}" class="small">Forgot your password?</a>
            <button type="submit" class="btn btn-primary">Log in</button>
        </div>
    </form>
@endsection
