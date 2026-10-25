<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The bucket is private, so images are only ever shown through temporary URLs.
     */
    public const IMAGE_DISK = 's3';

    protected $fillable = [
        'name',
        'price',
        'file_path',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    /**
     * Short-lived signed URL for the product image, or null when there is none.
     */
    public function imageUrl(): ?string
    {
        if ($this->file_path === null) {
            return null;
        }

        return Storage::disk(self::IMAGE_DISK)->temporaryUrl($this->file_path, now()->addHour());
    }
}
