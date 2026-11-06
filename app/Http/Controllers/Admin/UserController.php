<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::withCount('orders')
            ->when($request->query('search'), fn ($query, $search) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
            ))
            ->when($request->query('role'), fn ($query, $role) => $query->where('role', $role))
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => 'required|in:'.User::ROLE_ADMIN.','.User::ROLE_CUSTOMER,
        ]);

        if ($user->is($request->user()) && $data['role'] !== User::ROLE_ADMIN) {
            return back()->with('error', 'Kamu tidak bisa menurunkan role akunmu sendiri.');
        }

        $user->update(['role' => $data['role']]);

        return back()->with('success', 'Role updated.');
    }
}
