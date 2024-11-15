<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KategoriController extends Controller
{
    public function index()
    {
        $kategoris = Kategori::orderBy('created_at', 'desc')->get();

        return view("pages.kategori.index", compact("kategoris"));
    }

    public function create()
    {
        return view("pages.kategori.create");
    }

    public function store(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'nama' => 'required|string|max:255|unique:kategoris,nama',
            'deskripsi' => 'required|string',
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'nama.string' => 'Nama harus berupa teks.',
            'nama.unique' => 'Nama sudah terdaftar, silakan pilih nama lain.',

            'deskripsi.required' => 'Deskripsi wajib diisi.',
            'deskripsi.string' => 'Deskripsi harus berupa teks.',
        ]);

        if ($validatedData->fails()) {
            return redirect()->back()->withErrors($validatedData)->withInput();
        }

        $kategori = new Kategori();
        $kategori->nama = $request->nama;
        $kategori->deskripsi = $request->deskripsi;
        $kategori->save();

        return redirect()->route('kategori.index')->with('success', 'Kategori baru berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $kategori = Kategori::findOrFail($id);

        return view('pages.kategori.edit', compact('kategori'));
    }

    public function update(Request $request, $id)
    {
        $kategori = Kategori::findOrFail($id);

        $validatedData = Validator::make($request->all(), [
            'nama' => 'required|string|max:255|unique:kategoris,nama,' . $kategori->id,
            'deskripsi' => 'required|string',
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'nama.string' => 'Nama harus berupa teks.',
            'nama.unique' => 'Nama sudah terdaftar, silakan pilih nama lain.',

            'deskripsi.required' => 'Deskripsi wajib diisi.',
            'deskripsi.string' => 'Deskripsi harus berupa teks.',
        ]);

        if ($validatedData->fails()) {
            return redirect()->back()->withErrors($validatedData)->withInput();
        }

        $kategori->nama = $request->nama;
        $kategori->deskripsi = $request->deskripsi;

        $kategori->save();

        return redirect()->route('kategori.index')->with('success', 'Data Kategori berhasil diperbarui.');
    }

    public function destroy(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'kategoris' => 'required|array',
            'kategoris.*' => 'exists:kategoris,id',
        ], [
            'kategoris.required' => 'Silakan pilih setidaknya satu kategori untuk dihapus.',
        ]);

        if ($validatedData->fails()) {
            return redirect()->back()->withErrors($validatedData)->withInput();
        }

        $kategoriIdsToDelete = $request->input('kategoris');
        Kategori::whereIn('id', $kategoriIdsToDelete)->delete();

        return redirect()->route('kategori.index')->with('success', 'Kategori terpilih berhasil dihapus.');
    }
}
