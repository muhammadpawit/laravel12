<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

class CIDataController extends Controller
{
    public function __construct()
{
    parent::__construct();

    // Header CORS
    header('Access-Control-Allow-Origin: *'); // atau ganti * dengan domain front-end
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');

    
}


    public function users()
    {
        return response()->json([
            'success' => true,
            'data' => User::select('id','name','email')->get()
        ]);
    }
}
