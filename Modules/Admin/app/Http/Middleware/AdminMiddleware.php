<?php

namespace Modules\Admin\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Auth\app\Models\User;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (! session()->has('admin_logged_in')) {
            return redirect()->route('admin.login');
        }

        // Pastikan user di session benar-benar admin
        $adminId = session('admin_id');
        if ($adminId) {
            $user = User::find($adminId);
            if (!$user || !$user->is_admin) {
                session()->forget(['admin_logged_in', 'admin_id', 'admin_nama']);
                return redirect()->route('admin.login')->withErrors(['akses' => 'Akses ditolak. Akun Anda bukan admin.']);
            }
        }

        return $next($request);
    }
}