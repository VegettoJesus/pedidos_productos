<?php

namespace App\Http\Controllers\Auth;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('main');
        }
        return view('login_empleado');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('main');
        }

        return redirect()->back()
            ->with('login_error', 'Credenciales inválidas. Verifique su email y contraseña.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');  
    }

    public function username()
    {
        return 'Login';
    }

    public function main()
    {
        return view('main');
    }
}