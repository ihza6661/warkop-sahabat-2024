<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DetailTransaksi;
use App\Models\Kategori;
use App\Models\Meja;
use App\Models\Menu;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TransaksiController extends Controller
{
    public function index()
    {

        $all = Transaksi::with(['detailTransaksis', 'detailTransaksis.menu'])
            ->filter(request(['year', 'month']))
            ->orderByRaw("status = true")
            ->latest()
            ->get();

        $today = Transaksi::with(['detailTransaksis', 'detailTransaksis.menu'])
            ->whereDate('created_at', Carbon::now())
            ->filter(request(['year', 'month']))
            ->orderByRaw("status = true")
            ->latest()
            ->get();

        $thisMonth = Transaksi::with(['detailTransaksis', 'detailTransaksis.menu'])
            ->whereMonth('created_at', Carbon::now()->month)
            ->filter(request(['year', 'month']))
            ->orderByRaw("status = true")
            ->latest()
            ->get();

        return view('pages.transaksi.index', [
            'all' => $all,
            'today' => $today,
            'thisMonth' => $thisMonth
        ]);
    }

    public function create()
    {
        $kategoris = Kategori::get();
        $menus = Menu::with('kategori')->orderBy('created_at', 'desc')->get();
        $mejas = Meja::orderBy('created_at', 'asc')->get();

        return view("pages.transaksi.create", compact("kategoris", "menus", "mejas"));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_meja' => 'required',
            'id_menu' => 'required',
            'total_transaksi' => 'required|numeric',
        ]);

        // Ambil id_meja berdasarkan nama meja
        $meja = Meja::where('nama', $request->id_meja)->first();
        if (!$meja) {
            return back()->withErrors(['id_meja' => 'Meja tidak ditemukan']);
        }

        // Simpan transaksi utama
        $transaksi = new Transaksi();
        $transaksi->id_user = Auth::user()->id;
        $transaksi->id_meja = $meja->id; // Simpan id meja yang sesuai
        $transaksi->total_transaksi = $request->total_transaksi;
        $transaksi->total_pembayaran = 0;
        $transaksi->save();

        // Decode JSON dari id_menu
        $menuItems = json_decode($request->id_menu, true);

        // Simpan setiap item ke dalam tabel detail_transaksis
        foreach ($menuItems as $id_menu => $item) {
            $detail = new DetailTransaksi();
            $detail->id_transaksi = $transaksi->id;
            $detail->id_menu = $id_menu;
            $detail->jumlah = $item['quantity'];
            $detail->harga = $item['harga'];
            $detail->save();
        }

        $id = Auth::id();
        $activity = [
            'id_user' => $id,
            'aksi' => 'membuat transaksi baru'
        ];
        ActivityLog::create($activity);

        // Logika untuk mengirim notifikasi push
        $contents = 'Transaksi baru '. ' telah ditambahkan oleh ' . Auth::user()->nama ;
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

        return redirect()->route('transaksi.index')->with('success', 'Transaksi berhasil dibuat.');
    }

    public function show($id)
    {
        $transaksi = Transaksi::find($id);

        return view('pages.transaksi.show', [
            'data' => $transaksi->with(['detailTransaksis', 'detailTransaksis.menu', 'user'])->where('id', '=', $transaksi->id)->get()
        ]);
    }

    public function update(Request $request, $id)
    {
        $transaksi = Transaksi::findOrFail($id);
        $validatedData = $request->validate([
            'total_transaksi' => 'required|numeric|min:0',
            'total_pembayaran' => 'required|string|min:0'
        ]);

        $validatedData['total_pembayaran'] = (int) str_replace('.', '', $request->total_pembayaran);

        // Cek jika total pembayaran kurang dari total transaksi
        if ($validatedData['total_pembayaran'] < $transaksi->total_transaksi) {
            return redirect()->back()->withErrors(['total_pembayaran' => 'Total pembayaran tidak boleh kurang dari total transaksi.']);
        }

        $validatedData['status'] = true;

        // Update the transaction
        $transaksi->update($validatedData);

        $id = Auth::id();
        $activity = [
            'id_user' => $id,
            'aksi' => 'memperbarui transaksi'
        ];
        ActivityLog::create($activity);

        // Logika untuk mengirim notifikasi push
        $contents = 'Transaksi '. ' telah diperbarui oleh ' . Auth::user()->nama ;
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


        return redirect()->route('transaksi.index')->with('success', 'Transaksi berhasil diperbarui.');
    }


    public function nota($id)
    {
        $transaksi = Transaksi::find($id);
        return View('pages.transaksi.nota', [
            'data' => $transaksi->with(['detailTransaksis', 'detailTransaksis.menu', 'user'])->where('id', $transaksi->id)->get()
        ]);
    }

    public function laporan(Request $request)
    {
        $data = '';

        if ($request->data == 'all') {
            $data = Transaksi::with(['detailTransaksis', 'detailTransaksis.menu'])
                ->where('status', true)
                ->latest()
                ->get();
        } elseif ($request->data == 'today') {
            $data = Transaksi::with(['detailTransaksis', 'detailTransaksis.menu'])
                ->where('status', true)
                ->whereDate('created_at', Carbon::now())
                ->latest()
                ->get();
        } elseif ($request->data == 'thisMonth') {
            $data = Transaksi::with(['detailTransaksis', 'detailTransaksis.menu'])
                ->where('status', true)
                ->whereMonth('created_at', Carbon::now()->month)
                ->latest()
                ->get();
        } else {
            if ($request->month) {
                $data = Transaksi::with(['detailTransaksis', 'detailTransaksis.menu'])
                    ->where('status', true)
                    ->whereMonth('created_at', $request->month)
                    ->latest()
                    ->get();
            } elseif ($request->year) {
                $data = Transaksi::with(['detailTransaksis', 'detailTransaksis.menu'])
                    ->where('status', true)
                    ->whereYear('created_at', $request->year)
                    ->latest()
                    ->get();
            } else {
                $data = Transaksi::with(['detailTransaksis', 'detailTransaksis.menu'])
                    ->where('status', true)
                    ->whereMonth('created_at', $request->month)
                    ->whereYear('created_at', $request->year)
                    ->latest()
                    ->get();
            }
        }
        return view('pages.transaksi.report', ['data' => $data]);
    }
}
