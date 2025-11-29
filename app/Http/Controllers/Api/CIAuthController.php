<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;

class CIAuthController extends Controller
{
    public function requestToken(Request $request)
    {
        // Validasi input
        $request->validate([
            'secret_key' => 'required'
        ]);

        // Validasi secret key
        if ($request->secret_key !== env('CI_SECRET_KEY')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Ambil user khusus API
        $user = User::where('email', 'ci@app.com')->first();

        // Jika user belum ada → buat otomatis
        if (!$user) {
            $user = User::create([
                'name' => 'CI User',
                'email' => 'ci@app.com',
                'password' => bcrypt('ci12345'),
            ]);
        }

        // Generate token Sanctum dinamis
        $token = $user->createToken('ci-dynamic-token')->plainTextToken;
        $expires = Carbon::now('Asia/Jakarta')->addMinutes(30);
        return response()->json([
            'success' => true,
            'token' => $token,
            'expires' => $expires->toDateTimeString()
        ]);
    }
}
