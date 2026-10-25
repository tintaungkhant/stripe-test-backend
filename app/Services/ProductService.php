<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * Image types admins may upload, mapped to the file extension they are stored with.
     *
     * @var array<string, string>
     */
    public const IMAGE_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

    /**
     * Only keys this service hands out, so a product cannot point at any other object in the bucket.
     */
    public const IMAGE_PATH_PATTERN = '/^products\/[0-9a-f-]{36}\.(jpg|png|webp|gif)$/';

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Product::latest()->paginate($perPage);
    }

    /**
     * @param  array{name: string, price: string|float, file_path?: string|null}  $data
     */
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * Replacing or clearing the image deletes the old file from the bucket.
     *
     * @param  array{name?: string, price?: string|float, file_path?: string|null}  $data
     */
    public function update(Product $product, array $data): Product
    {
        $previousFilePath = $product->file_path;

        $product->update($data);

        if ($previousFilePath !== null && $product->wasChanged('file_path')) {
            Storage::disk(Product::IMAGE_DISK)->delete($previousFilePath);
        }

        return $product;
    }

    /**
     * Soft delete so past orders keep pointing at the product.
     */
    public function delete(Product $product): void
    {
        $product->delete();
    }

    /**
     * Presign a PUT so the browser uploads the image straight to the bucket.
     *
     * @return array{file_path: string, url: string, headers: array<string, string>, expires_at: string}
     */
    public function createImageUploadUrl(string $contentType): array
    {
        $filePath = 'products/'.Str::uuid().'.'.self::IMAGE_EXTENSIONS[$contentType];
        $expiresAt = now()->addMinutes(10);

        $upload = Storage::disk(Product::IMAGE_DISK)->temporaryUploadUrl($filePath, $expiresAt);

        return [
            'file_path' => $filePath,
            'url' => $upload['url'],
            'headers' => ['Content-Type' => $contentType],
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }
}
