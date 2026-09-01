<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\{Inertia,Response};
class UserController extends Controller { public function index():Response{return Inertia::render('Admin/Users/Index',['users'=>User::orderBy('name')->get(['id','name','username','email','role','is_active','must_change_password','last_login_at'])]);} public function store(Request $request):RedirectResponse{$data=$request->validate(['name'=>['required','string','max:150'],'username'=>['required','alpha_dash','max:50','unique:users,username'],'email'=>['required','email','max:190','unique:users,email'],'role'=>['required','in:super_admin,admin_pmb,finance,reviewer,viewer'],'password'=>['required','confirmed',Password::min(12)->letters()->numbers()]]);User::create([...$data,'username'=>strtolower($data['username']),'password'=>Hash::make($data['password']),'is_active'=>true,'must_change_password'=>true]);return back()->with('success','Pengguna admin dibuat.');} public function update(Request $request,User $user):RedirectResponse{$data=$request->validate(['name'=>['required','string','max:150'],'username'=>['required','alpha_dash','max:50','unique:users,username,'.$user->id],'role'=>['required','in:super_admin,admin_pmb,finance,reviewer,viewer'],'is_active'=>['required','boolean']]);if($user->role==='super_admin'&&($data['role']!=='super_admin'||!$data['is_active'])&&User::where('role','super_admin')->where('is_active',true)->count()<=1)throw ValidationException::withMessages(['role'=>'Super admin aktif terakhir tidak dapat dinonaktifkan atau diubah rolenya.']);$data['username']=strtolower($data['username']);$user->update($data);return back()->with('success','Pengguna diperbarui.');} }
