<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::orderBy('name')->get(['id', 'name', 'username', 'email', 'role', 'is_active', 'must_change_password', 'last_login_at']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'alpha_dash', 'max:50', 'unique:users,username'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'role' => ['required', 'in:super_admin,admin_pmb,finance,reviewer,viewer'],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ]);

        User::create([...$data, 'username' => strtolower($data['username']), 'password' => Hash::make($data['password']), 'is_active' => true, 'must_change_password' => true]);

        return back()->with('success', 'Pengguna admin dibuat.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'alpha_dash', 'max:50', 'unique:users,username,'.$user->id],
            'role' => ['required', 'in:super_admin,admin_pmb,finance,reviewer,viewer'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($user->role === 'super_admin' && ($data['role'] !== 'super_admin' || ! $data['is_active']) && $this->activeSuperAdminCount() <= 1) {
            throw ValidationException::withMessages(['role' => 'Super admin aktif terakhir tidak dapat dinonaktifkan atau diubah rolenya.']);
        }

        $user->update([...$data, 'username' => strtolower($data['username'])]);

        return back()->with('success', 'Pengguna diperbarui.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $user->update(['password' => Hash::make('123456789'), 'must_change_password' => true]);

        return back()->with('success', "Password {$user->name} berhasil direset ke 123456789. Pengguna wajib menggantinya saat login.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Akun yang sedang digunakan tidak dapat dihapus. Gunakan akun super admin lain untuk menghapusnya.');
        }

        if ($user->role === 'super_admin' && $user->is_active && $this->activeSuperAdminCount() <= 1) {
            return back()->with('error', 'Super admin aktif terakhir tidak dapat dihapus agar akses sistem tetap tersedia.');
        }

        if (DB::table('document_reviews')->where('reviewer_id', $user->id)->exists()) {
            return back()->with('error', 'Pengguna tidak dapat dihapus karena memiliki riwayat pemeriksaan dokumen. Riwayat audit harus tetap tersimpan.');
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', "Pengguna {$name} berhasil dihapus.");
    }

    private function activeSuperAdminCount(): int
    {
        return User::where('role', 'super_admin')->where('is_active', true)->count();
    }
}
