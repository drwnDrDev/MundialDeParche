<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivationController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        if ($request->user()->is_activated) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Activation', [
            'adminName'      => config('app.admin_name'),
            'adminPhone'     => config('app.admin_phone'),
            'adminWhatsApp'  => config('app.admin_whatsapp'),
        ]);
    }
}
