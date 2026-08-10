<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard', [
            'totalUsers' => User::count(),
            'activeUsers' => User::where('status', 'active')->count(),
            'totalRoles' => Role::count(),
        ]);
    }
}
