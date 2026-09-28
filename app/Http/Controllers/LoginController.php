<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'nisn' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $student = Student::query()
            ->where('nisn', trim($request->string('nisn')->toString()))
            ->where('password', $request->string('password')->toString())
            ->first();

        if (!$student) {
            return back()->withErrors([
                'nisn' => 'NISN atau password tidak sesuai.',
            ])->withInput($request->only('nisn'));
        }

        // Tabel students tidak menggunakan remember_token; gunakan session login biasa.
        auth()->guard('student')->login($student);
        $request->session()->regenerate();

        return redirect()->route('student.dashboard');
    }

    public function logout(Request $request)
    {
        auth()->guard('student')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
