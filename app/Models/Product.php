<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name', 'category', 'unit', 'old_price', 'new_price',
        'stock', 'description', 'images', 'status',
    ];

    protected $appends = ['image_urls'];

    protected function casts(): array
    {
        return [
            'images'    => 'array',
            'old_price' => 'decimal:2',
            'new_price' => 'decimal:2',
        ];
    }

    public function getImageUrlsAttribute(): array
    {
        return collect($this->images ?? [])
            ->map(fn ($path) => asset('storage/' . $path))
            ->values()
            ->all();
    }
}