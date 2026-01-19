<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="card shadow-lg w-100" style="max-width: 400px;">
        <div class="card-body">
            <h4 class="text-center mb-4">Daftar Akun</h4>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
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
                    <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
                </div>

                <div class="form-group">
                    <label>Jabatan</label>
                    <select name="jabatan" class="form-control" required>
                        <option value="">-- Pilih Jabatan --</option>
                        <option value="admin">Admin</option>
                        <option value="sales">Sales</option>
                        <option value="manajer">Manajer</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required value="{{ old('email') }}">
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Daftar</button>
            </form>

            <hr>
            <p class="text-center">Sudah punya akun? <a href="/login">Login di sini</a></p>
        </div>
    </div>
</div>

</body>
</html>
