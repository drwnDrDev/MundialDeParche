<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status'        => session('status'),
            'adminWhatsapp' => config('app.admin_whatsapp'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     * No email is sent — the admin generates and shares the reset link manually.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();
        if ($user) {
            Password::broker()->createToken($user);
        }

        return back()->with('status', 'solicitud_enviada');
    }
}
