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

        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return redirect()->back()->with('login_error', 'El usuario no existe.');
        }

        if (!$user->estado) {
            return redirect()->back()->with('login_error', 'Tu usuario está deshabilitado. Contacta al administrador.');
        }

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user->update(['conectado' => true]);
            return redirect()->route('main');
        }

        return redirect()->back()->with('login_error', 'Credenciales inválidas. Verifique su email y contraseña.');
}


    public function logout(Request $request)
    {
        if (Auth::check()) {
            Auth::user()->update(['conectado' => false]);
        }

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
        return view('main', [
            'darkMode' => Auth::user()->dark_mode
        ]);
    }

    public function toggleDarkMode(Request $request)
    {
        $user = Auth::user();
        $user->update([
            'dark_mode' => !$user->dark_mode
        ]);

        return response()->json(['dark_mode' => $user->dark_mode]);
    }

}