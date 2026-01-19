<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="card shadow-lg w-100" style="max-width: 400px;">
        <div class="card-body">

            <!-- Tambahan logo -->
            <div class="text-center mb-3">
                <img src="{{ asset('images/chopindo.png') }}" alt="Logo" style="max-width: 150px;">
            </div>

            <h4 class="text-center mb-4">Login</h4>

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="/login">
                @csrf

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required value="{{ old('email') }}">
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>

            <hr>
            <p class="text-center">Belum punya akun? <a href="/register">Daftar di sini</a></p>
        </div>
    </div>
</div>

</body>
</html>
