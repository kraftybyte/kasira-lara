<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'status',
        'timezone',
        'currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'slug' => 'string',
            'name' => 'string',
            'email' => 'string',
            'phone' => 'string',
            'status' => 'string',
            'timezone' => 'string',
            'currency' => 'string',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'display_order' => 'integer',
        ];
    }

    /**
     * Boot the model and auto-generate slug from name.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $tenant) {
            if (empty($tenant->slug) && ! empty($tenant->name)) {
                $tenant->slug = Str::slug($tenant->name);
            }
        });

        static::updating(function (self $tenant) {
            if ($tenant->isDirty('name') && ! $tenant->isDirty('slug')) {
                $tenant->slug = Str::slug($tenant->name);
            }
        });
    }

    /**
     * Get the route key name for route model binding.
     * Uses slug instead of ID for security.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the route key (slug) for URLs.
     */
    public function getRouteKey(): string
    {
        return $this->slug;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'tenant_user'
        )->withPivot('status')
            ->withTimestamps();
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function receiptSetting(): HasOne
    {
        return $this->hasOne(TenantReceiptSetting::class);
    }
}
