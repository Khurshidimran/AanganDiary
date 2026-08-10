<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}">
</head>
<body class="auth-shell">
    <div class="container">
        <div class="d-flex justify-content-center">
            <div class="auth-card">
                <div class="text-center mb-4">
                    <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="auth-logo mb-2">
                    <h1 class="h4 fw-bold text-white">{{ config('app.name') }}</h1>
                </div>
                <div class="card shadow">
                    <div class="card-body p-4">
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
