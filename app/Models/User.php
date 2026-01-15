<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'name',
        'phone',
        'address',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
    ];

    public function getAuthIdentifierName()
    {
        return 'username';
    }

    public function setPasswordAttribute($value)
    {
        // Only hash if it's not already hashed
        $this->attributes['password'] = Str::startsWith($value, '$2y$') ? $value : Hash::make($value);
    }

    public function purchaseRequests()
    {
        return $this->hasMany(PurchaseRequest::class, 'requested_by_id');
    }

    public function approvedPurchaseRequests()
    {
        return $this->hasMany(PurchaseRequest::class, 'approved_by_id');
    }

    public function purchaseRequestApprovals()
    {
        return $this->hasMany(PurchaseRequestApproval::class, 'approved_by');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class, 'created_by');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function hasRole(string $name): bool
    {
        return $this->roles()->where('name', $name)->exists();
    }

    public function canManageUsers(): bool
    {
        return $this->hasRole('ADMIN');
    }

    public function canApprovePurchaseOrders(): bool
    {
        return $this->canApprovePurchaseRequests();
    }

    public function canApprovePurchaseRequests(): bool
    {
        return $this->hasRole('ADMIN')
            || $this->hasRole('MANAGER');
    }

    public function canAccessMasterData(): bool
    {
        return $this->hasRole('ADMIN')
            || $this->hasRole('MANAGER')
            || $this->hasRole('PURCHASING')
            || $this->hasRole('WAREHOUSE');
    }

    public function canAccessPurchasing(): bool
    {
        return $this->hasRole('ADMIN')
            || $this->hasRole('MANAGER')
            || $this->hasRole('PURCHASING');
    }

    public function canAccessStockSystem(): bool
    {
        return $this->hasRole('ADMIN')
            || $this->hasRole('MANAGER')
            || $this->hasRole('WAREHOUSE');
    }

    public function canAccessSales(): bool
    {
        return $this->hasRole('ADMIN')
            || $this->hasRole('MANAGER')
            || $this->hasRole('CASHIER');
    }

    public function canManageSystemSettings(): bool
    {
        return $this->hasRole('ADMIN');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('ADMIN')
            || $this->hasRole('MANAGER')
            || $this->hasRole('PURCHASING')
            || $this->hasRole('WAREHOUSE')
            || $this->hasRole('CASHIER');
    }
}
