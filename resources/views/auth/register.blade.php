<!DOCTYPE html>
<html lang="id">
<head>
    <title>Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light d-flex align-items-center" style="min-height:100vh">

<div class="container px-3">
    <div class="card shadow-lg mx-auto" style="max-width: 400px;">
        <div class="card-body p-4">

            <h5 class="text-center mb-4 font-weight-bold">Daftar Akun</h5>

            @if ($errors->any())
                <div class="alert alert-danger small">
                    <ul class="mb-0 pl-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="/register">
                @csrf

                <div class="form-group">
                    <label>Nama</label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Jabatan</label>
                    <select name="jabatan" class="form-control" required>
                        <option value="">-- Pilih Jabatan --</option>
                        <option value="sales">Sales</option>
                        <option value="manajer">Manajer</option>
                        {{-- Admin sebaiknya tidak publik --}}
                    </select>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button class="btn btn-primary btn-block mt-3">
                    Daftar
                </button>
            </form>

            <hr>

            <p class="text-center mb-0 small">
                Sudah punya akun?
                <a href="/login">Login</a>
            </p>

        </div>
    </div>
</div>

</body>
</html>
