<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            if (blank($category->tenant_id)) {
                $tenant = Filament::getTenant();

                if ($tenant) {
                    $category->tenant_id = $tenant->id;
                }
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
