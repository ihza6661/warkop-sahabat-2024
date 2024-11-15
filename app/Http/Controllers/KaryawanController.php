<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Peran;
use Illuminate\Support\Facades\Auth;

class KaryawanController extends Controller
{
    public function index()
    {
        $users = User::with('peran')->orderBy('created_at', 'desc')->get();

        return view("pages.karyawan.index", compact("users"));
    }

    public function create()
    {
        $perans = Peran::get();

        return view("pages.karyawan.create", compact("perans"));
    }

    public function store(Request $request)
    {
        // Validasi inputan
        $validatedData = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username|regex:/^\S*$/u',
            'password' => 'required|string|min:8',
            'id_peran' => 'required|exists:perans,id',
            'foto_profil' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'nama.string' => 'Nama harus berupa teks.',
            'nama.max' => 'Nama tidak boleh lebih dari 255 karakter.',

            'username.required' => 'Username wajib diisi.',
            'username.string' => 'Username harus berupa teks.',
            'username.max' => 'Username tidak boleh lebih dari 255 karakter.',
            'username.unique' => 'Username sudah terdaftar, silakan pilih username lain.',
            'username.regex' => 'Username tidak boleh mengandung spasi.',

            'password.required' => 'Password wajib diisi.',
            'password.string' => 'Password harus berupa teks.',
            'password.min' => 'Password harus minimal 8 karakter.',

            'id_peran.required' => 'Peran wajib dipilih.',
            'id_peran.exists' => 'Peran yang dipilih tidak valid.',

            'foto_profil.image' => 'Foto profil harus berupa gambar.',
            'foto_profil.mimes' => 'Foto profil harus berformat: jpeg, png, jpg, gif, atau svg.',
            'foto_profil.max' => 'Ukuran foto profil tidak boleh lebih dari 2MB.',
        ]);

        // Cek apakah file foto_profil ada dan simpan ke folder public/foto_profil
        if ($request->file('foto_profil')) {
            $image = $request->file('foto_profil');
            $imageName = time() . '_' . $image->getClientOriginalName();  // Nama file unik
            $imagePath = $image->move(public_path('foto_profil'), $imageName);  // Pindahkan ke public/foto_profil
        } else {
            $imagePath = null;
        }

        // Jika validasi gagal, kembali ke halaman sebelumnya dengan error
        if ($validatedData->fails()) {
            return redirect()->back()->withErrors($validatedData)->withInput();
        }

        // Menyimpan data user
        $user = new User();
        $user->nama = $request->nama;
        $user->username = $request->username;
        $user->password = Hash::make($request->password);
        $user->id_peran = $request->id_peran;
        $user->foto_profil = 'foto_profil/' . $imageName; // Menyimpan path relatif
        $user->save();

        // Redirect ke halaman index karyawan dengan pesan sukses
        return redirect()->route('karyawan.index')->with('success', 'Karyawan baru berhasil ditambahkan.');
    }


    public function edit($id)
    {
        $user = User::findOrFail($id);
        $perans = Peran::all();

        return view('pages.karyawan.edit', compact('user', 'perans'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validatedData = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id . '|regex:/^\S*$/u',
            'id_peran' => 'required|exists:perans,id',
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'nama.string' => 'Nama harus berupa teks.',
            'nama.max' => 'Nama tidak boleh lebih dari 255 karakter.',

            'username.required' => 'Username wajib diisi.',
            'username.string' => 'Username harus berupa teks.',
            'username.max' => 'Username tidak boleh lebih dari 255 karakter.',
            'username.unique' => 'Username sudah terdaftar, silakan pilih username lain.',
            'username.regex' => 'Username tidak boleh mengandung spasi.',

            'id_peran.required' => 'Peran wajib dipilih.',
            'id_peran.exists' => 'Peran yang dipilih tidak valid.',
        ]);

        if ($validatedData->fails()) {
            return redirect()->back()->withErrors($validatedData)->withInput();
        }

        $user->nama = $request->nama;
        $user->username = $request->username;
        $user->id_peran = $request->id_peran;

        $user->save();

        return redirect()->route('karyawan.index')->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        $loggedInUserId = User::findOrFail($id);

        $validatedData = Validator::make($request->all(), [
            'users' => 'required|array',
            'users.*' => 'exists:users,id',
        ], [
            'users.required' => 'Silakan pilih setidaknya satu pengguna untuk dihapus.',
        ]);

        if ($validatedData->fails()) {
            return redirect()->back()->withErrors($validatedData)->withInput();
        }

        $userIdsToDelete = array_filter($request->input('users'), function ($userId) use ($loggedInUserId) {
            return $userId != $loggedInUserId;
        });

        if (!empty($userIdsToDelete)) {
            User::whereIn('id', $userIdsToDelete)->delete();
            return redirect()->route('karyawan.index')->with('success', 'Pengguna terpilih berhasil dihapus.');
        }
    }
}
