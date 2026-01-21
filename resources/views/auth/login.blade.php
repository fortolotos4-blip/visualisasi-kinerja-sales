<!DOCTYPE html>
<html lang="id">
<head>
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light d-flex align-items-center" style="min-height:100vh">

<div class="container px-3">
    <div class="card shadow-lg mx-auto" style="max-width: 380px;">
        <div class="card-body p-4">

            <div class="text-center mb-3">
                <img src="{{ asset('images/chopindo.png') }}"
                     alt="Logo"
                     class="img-fluid"
                     style="max-height:120px">
            </div>

            <h5 class="text-center mb-4 font-weight-bold">Login</h5>

            @if (session('error'))
                <div class="alert alert-danger small">{{ session('error') }}</div>
            @endif

            @if (session('success'))
                <div class="alert alert-success small">{{ session('success') }}</div>
            @endif

            <form method="POST" action="/login">
                @csrf

                <div class="form-group">
                    <label>Email</label>
                    <input type="email"
                           name="email"
                           class="form-control"
                           required
                           value="{{ old('email') }}">
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password"
                           name="password"
                           class="form-control"
                           required>
                </div>

                <button class="btn btn-primary btn-block mt-3">
                    Login
                </button>
            </form>

            <hr>

            <p class="text-center mb-0 small">
                Belum punya akun?
                <a href="/register">Daftar</a>
            </p>

        </div>
    </div>
</div>

</body>
</html>
