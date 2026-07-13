@extends('admin::layouts.master')

@section('title', 'Kelola Kategori')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">🏷️ Kelola Kategori Produk</h4>
        <button class="btn btn-sm btn-primary" onclick="document.getElementById('form-tambah').classList.toggle('d-none')">
            <i class="bi bi-plus-lg"></i> Tambah Kategori
        </button>
    </div>

    {{-- Success / Error Messages --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Form Tambah --}}
    <div class="card mb-4 d-none" id="form-tambah">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.kategori.store') }}" class="row g-2">
                @csrf
                <div class="col-md-5">
                    <input type="text" name="nama" class="form-control" placeholder="Nama kategori baru" required>
                </div>
                <div class="col-md-4">
                    <select name="parent_id" class="form-select">
                        <option value="">-- Kategori Utama (parent) --</option>
                        @foreach ($rootKategoris as $kat)
                            <option value="{{ $kat->id }}">{{ $kat->nama }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Kosongi jika ini kategori utama</small>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Daftar Kategori --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Nama Kategori</th>
                            <th>Parent</th>
                            <th>Sub Kategori</th>
                            <th>Produk Master</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kategoris as $k)
                            <tr>
                                <td>{{ $k->id }}</td>
                                <td>
                                    <strong>{{ $k->nama }}</strong>
                                    @if (!$k->parent_id)
                                        <span class="badge bg-primary ms-1">Utama</span>
                                    @endif
                                </td>
                                <td>{{ $k->parent?->nama ?? '-' }}</td>
                                <td>
                                    @if ($k->children->count() > 0)
                                        <span class="badge bg-info">{{ $k->children->count() }} sub</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $k->produkMaster()->count() }}</td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-primary" onclick="editKategori({{ $k->id }}, '{{ $k->nama }}', '{{ $k->parent_id }}')">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="{{ route('admin.kategori.destroy', $k->id) }}" onsubmit="return confirm('Hapus kategori {{ $k->nama }}?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Belum ada kategori. Tambah kategori pertama!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Form Edit (hidden, diisi via JS) --}}
    <div class="card mt-3 d-none" id="form-edit">
        <div class="card-body">
            <h6>Edit Kategori</h6>
            <form method="POST" action="" id="edit-form" class="row g-2">
                @csrf @method('PUT')
                <div class="col-md-5">
                    <input type="text" name="nama" id="edit-nama" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <select name="parent_id" id="edit-parent" class="form-select">
                        <option value="">-- Kategori Utama --</option>
                        @foreach ($rootKategoris as $kat)
                            <option value="{{ $kat->id }}">{{ $kat->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-1">
                    <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Update</button>
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('form-edit').classList.add('d-none')">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editKategori(id, nama, parentId) {
        document.getElementById('edit-nama').value = nama;
        document.getElementById('edit-parent').value = parentId || '';
        document.getElementById('edit-form').action = '/admin/kategori/' + id;
        document.getElementById('form-edit').classList.remove('d-none');
        document.getElementById('form-edit').scrollIntoView({ behavior: 'smooth' });
    }
</script>
@endsection