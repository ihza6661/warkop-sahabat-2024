<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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
            'deskripsi' => 'nullable|string',
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'nama.string' => 'Nama harus berupa teks.',
            'nama.unique' => 'Nama sudah terdaftar, silakan pilih nama lain.',
            'deskripsi.string' => 'Deskripsi harus berupa teks.',
        ]);

        if ($validatedData->fails()) {
            return redirect()->back()->withErrors($validatedData)->withInput();
        }

        $kategori = new Kategori();
        $kategori->nama = $request->nama;
        $kategori->deskripsi = $request->deskripsi;
        $kategori->save();

        $id = Auth::id();
        $activity = [
            'id_user' => $id,
            'aksi' => 'menambah kategori baru ' . $request->nama
        ];
        ActivityLog::create($activity);

        // Logika untuk mengirim notifikasi push
        $contents = 'Kategori baru ' . $request->nama . ' telah ditambahkan';
        $url = 'https://example.com'; // Ganti dengan URL yang relevan

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic os_v2_app_6fg3emk67bavbmgmnh3nhv3g4ehzupmtc3feg5ngkscuxz2t7d4fbuopf2v5dtqdeshspknqtrmoda4vt4tgnzy6dvj7tgc7megugwy',
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])->post('https://onesignal.com/api/v1/notifications', [
                'app_id' => 'f14db231-5ef8-4150-b0cc-69f6d3d766e1',
                'included_segments' => ['All'],
                'contents' => ['en' => $contents],
                'url' => $url
            ]);

            // Debugging response dari OneSignal
            if ($response->failed()) {
                throw new \Exception('Gagal mengirim notifikasi: ' . $response->body());
            }
        } catch (\Exception $e) {
            report($e);
            return response()->json(['error' => $e->getMessage()], 500);
        }

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
            'deskripsi' => 'nullable|string',
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'nama.string' => 'Nama harus berupa teks.',
            'nama.unique' => 'Nama sudah terdaftar, silakan pilih nama lain.',

            'deskripsi.string' => 'Deskripsi harus berupa teks.',
        ]);

        if ($validatedData->fails()) {
            return redirect()->back()->withErrors($validatedData)->withInput();
        }

        $kategori->nama = $request->nama;
        $kategori->deskripsi = $request->deskripsi;

        $kategori->save();

        $id = Auth::id();
        $activity = [
            'id_user' => $id,
            'aksi' => 'memperbarui data kategori ' . $request->nama
        ];
        ActivityLog::create($activity);

        // Logika untuk mengirim notifikasi push
        $contents = 'Kategori ' . $request->nama . ' telah diperbarui';
        $url = 'https://example.com'; // Ganti dengan URL yang relevan

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic os_v2_app_6fg3emk67bavbmgmnh3nhv3g4ehzupmtc3feg5ngkscuxz2t7d4fbuopf2v5dtqdeshspknqtrmoda4vt4tgnzy6dvj7tgc7megugwy',
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])->post('https://onesignal.com/api/v1/notifications', [
                'app_id' => 'f14db231-5ef8-4150-b0cc-69f6d3d766e1',
                'included_segments' => ['All'],
                'contents' => ['en' => $contents],
                'url' => $url
            ]);

            // Debugging response dari OneSignal
            if ($response->failed()) {
                throw new \Exception('Gagal mengirim notifikasi: ' . $response->body());
            }
        } catch (\Exception $e) {
            report($e);
            return response()->json(['error' => $e->getMessage()], 500);
        }

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

        $id = Auth::id();
        $activity = [
            'id_user' => $id,
            'aksi' => 'menghapus kategori'
        ];
        ActivityLog::create($activity);

        return redirect()->route('kategori.index')->with('success', 'Kategori terpilih berhasil dihapus.');
    }
}
