<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

class CIDataController extends Controller
{
    public function users()
    {
        return response()->json([
            'success' => true,
            'data' => User::select('id','name','email')->get()
        ]);
    }
}
