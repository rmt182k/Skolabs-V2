<?php

namespace App\Http\Controllers;

use Auth;
use DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::user()->id;
        $role = DB::table('user_roles')
            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
            ->where('user_id', $userId)
            ->value('roles.name');

        $userCredentials = [
            'id' => $userId,
            'name' => Auth::user()->name,
            'email' => Auth::user()->email,
            'role' => $role,
        ];

        return view('dashboard.index', compact('userCredentials'));
    }
}
