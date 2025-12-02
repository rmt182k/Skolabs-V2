<?php

namespace App\Http\Controllers;

use Auth;
use DB;
use Exception;
use Illuminate\Http\Request;
use Log;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return redirect()->route('login');
            }
            return view('dashboard.index');
        } catch (Exception $e) {
            Log::error('Error loading dashboard: ' . $e->getMessage());
            return response()->view('errors.500', [], 500);
        }
    }
}
