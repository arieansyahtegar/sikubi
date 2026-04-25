<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'permissions' => [
                'canImport' => $user?->isAdmin() ?? false,
                'canManageAccounts' => $user?->isAdmin() ?? false,
                'canManageSettings' => $user?->isAdmin() ?? false,
                'canDetectAnomalies' => $user?->isAdmin() ?? false,
                'canManageUsers' => $user?->isDirektur() ?? false,
                'canEditTransactions' => $user?->isAdmin() ?? false,
            ],
            'flash' => [
                'importResult' => fn () => $request->session()->get('importResult'),
                'detectResult' => fn () => $request->session()->get('detectResult'),
            ],
        ];
    }
}
