<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Meja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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

        $id = Auth::id();
        $activity = [
            'id_user' => $id,
            'aksi' => 'menambah meja baru'
        ];
        ActivityLog::create($activity);

        // Logika untuk mengirim notifikasi push
        $contents = 'Meja baru ' . $request->nama . ' telah ditambahkan';
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

        $id = Auth::id();
        $activity = [
            'id_user' => $id,
            'aksi' => 'memperbarui data meja ' . $request->nama
        ];
        ActivityLog::create($activity);

        // Logika untuk mengirim notifikasi push
        $contents = 'Meja ' . $request->nama . ' telah ditambahkan';
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

        $id = Auth::id();
        $activity = [
            'id_user' => $id,
            'aksi' => 'menghapus meja'
        ];
        ActivityLog::create($activity);

        return redirect()->route('meja.index')->with('success', 'Meja terpilih berhasil dihapus.');
    }
}
