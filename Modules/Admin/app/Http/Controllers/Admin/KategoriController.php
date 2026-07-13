<?php

namespace Modules\Admin\app\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Warung\app\Models\Kategori;

class KategoriController extends Controller
{
    public function index()
    {
        $kategoris = Kategori::with('parent', 'children')->orderBy('nama')->get();
        $rootKategoris = Kategori::root();

        return view('admin::kategori.index', compact('kategoris', 'rootKategoris'));
    }

    public function store(Request $request)
    {
        $valid = $request->validate([
            'nama'      => 'required|string|max:100|unique:kategoris,nama',
            'parent_id' => 'nullable|exists:kategoris,id',
        ]);

        Kategori::create($valid);

        return redirect()->route('admin.kategori.index')
            ->with('success', "Kategori '{$valid['nama']}' berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $kategori = Kategori::findOrFail($id);

        $valid = $request->validate([
            'nama'      => 'required|string|max:100|unique:kategoris,nama,' . $id,
            'parent_id' => 'nullable|exists:kategoris,id',
        ]);

        // Cegah self-referencing (parent_id = id sendiri)
        if (isset($valid['parent_id']) && $valid['parent_id'] == $id) {
            return back()->withErrors(['parent_id' => 'Kategori tidak bisa menjadi parent dari dirinya sendiri.']);
        }

        $kategori->update($valid);

        return redirect()->route('admin.kategori.index')
            ->with('success', "Kategori '{$kategori->nama}' berhasil diupdate.");
    }

    public function destroy($id)
    {
        $kategori = Kategori::findOrFail($id);

        // Cek apakah punya anak
        if ($kategori->children()->count() > 0) {
            return back()->withErrors(['delete' => "Kategori '{$kategori->nama}' masih memiliki sub kategori. Hapus sub kategori dulu."]);
        }

        // Cek apakah dipakai produk_master
        if ($kategori->produkMaster()->count() > 0) {
            return back()->withErrors(['delete' => "Kategori '{$kategori->nama}' masih digunakan oleh produk master."]);
        }

        $nama = $kategori->nama;
        $kategori->delete();

        return redirect()->route('admin.kategori.index')
            ->with('success', "Kategori '{$nama}' berhasil dihapus.");
    }
}