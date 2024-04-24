<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActivationController extends Controller
{
    public function activate(Request $request)
    {
        $email = $request->query('email');

        if (!$this->isValidSignature($request)) {
            return view('emails.error', ['message' => 'Firma de URL no válida.']);
        }

        $user = User::where('email', $email)->first();        
        Log::info($user);


        if (!$user) {
            return view('emails.error', ['message' => 'Usuario no encontrado.']);
        }

        $user->email_verified_at = now();
        $user->save();
        
        return view('emails.success', ['message' => 'Cuenta activada exitosamente.']);
    }

    private function isValidSignature(Request $request)
    {
        $email = $request->query('email');
        $url = url('/activate-account') . '?email=' . urlencode($email);
        return app('url')->hasValidSignature($request, $url);
    }
}