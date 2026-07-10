<x-dynamic-component :component="'layouts.' . $role">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h4 class="mb-3 text-center">Login — {{ ucfirst($role) }}</h4>

                    @if ($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form method="POST" action="/{{ $role }}/login">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">No HP</label>
                            <input type="text" name="no_hp" class="form-control" placeholder="08xxxxxxxxxx" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>

                    <p class="mt-3 text-center mb-0">
                        Belum punya akun? <a href="/{{ $role }}/register">Daftar</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>