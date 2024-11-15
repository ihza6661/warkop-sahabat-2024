<?php

namespace App\Http\Controllers;

use App\Models\Meja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MejaController extends Controller
{
    public function index()
    {
        $mejas = Meja::orderBy('created_at', 'desc')->get();

        return view("pages.meja.index", compact("mejas"));
    }

    public function create()
    {
        return view("pages.meja.create");
    }

    public function store(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'nama' => 'required|string|max:255|unique:mejas,nama',
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'nama.string' => 'Nama harus berupa teks.',
            'nama.unique' => 'Nama sudah terdaftar, silakan pilih nama lain.',
        ]);

        if ($validatedData->fails()) {
            return redirect()->back()->withErrors($validatedData)->withInput();
        }

        $meja = new Meja();
        $meja->nama = $request->nama;
        $meja->save();

        return redirect()->route('meja.index')->with('success', 'Meja baru berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $meja = Meja::findOrFail($id);

        return view('pages.meja.edit', compact('meja'));
    }

    public function update(Request $request, $id)
    {
        $meja = Meja::findOrFail($id);

        $validatedData = Validator::make($request->all(), [
            'nama' => 'required|string|max:255|unique:mejas,nama,' . $meja->id,
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'nama.string' => 'Nama harus berupa teks.',
            'nama.unique' => 'Nama sudah terdaftar, silakan pilih nama lain.',
        ]);

        if ($validatedData->fails()) {
            return redirect()->back()->withErrors($validatedData)->withInput();
        }

        $meja->nama = $request->nama;

        $meja->save();

        return redirect()->route('meja.index')->with('success', 'Data meja berhasil diperbarui.');
    }

    public function destroy(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'mejas' => 'required|array',
            'mejas.*' => 'exists:mejas,id',
        ], [
            'mejas.required' => 'Silakan pilih setidaknya satu meja untuk dihapus.',
        ]);

        if ($validatedData->fails()) {
            return redirect()->back()->withErrors($validatedData)->withInput();
        }

        $mejaIdsToDelete = $request->input('mejas');
        Meja::whereIn('id', $mejaIdsToDelete)->delete();

        return redirect()->route('meja.index')->with('success', 'Meja terpilih berhasil dihapus.');
    }
}
