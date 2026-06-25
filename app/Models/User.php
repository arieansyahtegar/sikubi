<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'password_hint', 'role', 'avatar_url', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function isDirektur(): bool
    {
        return $this->role === 'DIREKTUR';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN_KEUANGAN';
    }

    /**
     * Generate a masked hint from a plain-text password.
     * e.g. "Admin@2026" → "Adm*****26"
     */
    public static function generatePasswordHint(string $password): string
    {
        $len = mb_strlen($password);
        if ($len <= 3) return str_repeat('*', $len);
        $show = min(3, $len - 2);
        $tail = min(2, $len - $show);
        $masked = $len - $show - $tail;
        return mb_substr($password, 0, $show) . str_repeat('*', max($masked, 1)) . mb_substr($password, -$tail);
    }
}
