<x-dynamic-component :component="'layouts.' . $role">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h4 class="mb-3 text-center">Daftar — {{ ucfirst($role) }}</h4>

                    @if ($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="/{{ $role }}/register">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" name="nama" class="form-control" placeholder="Nama lengkap" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">No HP</label>
                            <input type="text" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email (opsional)</label>
                            <input type="email" name="email" class="form-control" placeholder="email@example.com">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Daftar</button>
                    </form>

                    <p class="mt-3 text-center mb-0">
                        Sudah punya akun? <a href="/{{ $role }}/login">Login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>