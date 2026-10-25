<?php

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

test('admin can list products', function () {
    actingAsAdmin();
    Product::factory()->count(3)->create();

    $this->getJson(route('api.v1.admin.products.index'))
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('admin can create a product', function () {
    actingAsAdmin();
    $this->postJson(route('api.v1.admin.products.store'), [
        'name' => 'Laravel Course',
        'price' => 49.99,
    ])->assertCreated()->assertJsonPath('data.name', 'Laravel Course');

    $this->assertDatabaseHas('products', ['name' => 'Laravel Course', 'price' => 49.99]);
});

test('product creation validates input', function () {
    actingAsAdmin();
    $this->postJson(route('api.v1.admin.products.store'), [
        'price' => -5,
    ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'price']);
});

test('admin can update a product', function () {
    actingAsAdmin();
    $product = Product::factory()->create();

    $this->patchJson(route('api.v1.admin.products.update', $product), [
        'price' => 10,
    ])->assertOk()->assertJsonPath('data.price', '10.00');
});

test('admin can soft delete a product', function () {
    actingAsAdmin();
    $product = Product::factory()->create();

    $this->deleteJson(route('api.v1.admin.products.destroy', $product))
        ->assertNoContent();

    $this->assertSoftDeleted($product);
});

test('customer cannot access admin product endpoints', function () {
    actingAsCustomer();

    $this->getJson(route('api.v1.admin.products.index'))->assertForbidden();
    $this->postJson(route('api.v1.admin.products.store'), [
        'name' => 'Hack',
        'price' => 1,
    ])->assertForbidden();
});

test('guest cannot access admin product endpoints', function () {
    $this->getJson(route('api.v1.admin.products.index'))->assertUnauthorized();
});

test('admin can get a presigned image upload url', function () {
    Storage::fake(Product::IMAGE_DISK);
    actingAsAdmin();

    $response = $this->postJson(route('api.v1.admin.products.image-upload-url'), [
        'content_type' => 'image/png',
        'size' => 1024,
    ])->assertOk()
        ->assertJsonPath('data.headers.Content-Type', 'image/png')
        ->assertJsonStructure(['data' => ['file_path', 'url', 'headers', 'expires_at']]);

    expect($response->json('data.file_path'))->toMatch(ProductService::IMAGE_PATH_PATTERN);
});

test('image upload url rejects non images and oversized files', function () {
    actingAsAdmin();

    $this->postJson(route('api.v1.admin.products.image-upload-url'), [
        'content_type' => 'text/html',
        'size' => ProductService::MAX_IMAGE_BYTES + 1,
    ])->assertUnprocessable()->assertJsonValidationErrors(['content_type', 'size']);
});

test('customer cannot get an image upload url', function () {
    actingAsCustomer();

    $this->postJson(route('api.v1.admin.products.image-upload-url'), [
        'content_type' => 'image/png',
        'size' => 1024,
    ])->assertForbidden();
});

test('admin can create a product with an image', function () {
    Storage::fake(Product::IMAGE_DISK);
    actingAsAdmin();
    $filePath = 'products/'.Str::uuid().'.png';

    $this->postJson(route('api.v1.admin.products.store'), [
        'name' => 'Laravel Course',
        'price' => 49.99,
        'file_path' => $filePath,
    ])->assertCreated()
        ->assertJsonPath('data.file_path', $filePath)
        ->assertJsonPath('data.image_url', fn (string $url) => str_contains($url, $filePath));

    $this->assertDatabaseHas('products', ['name' => 'Laravel Course', 'file_path' => $filePath]);
});

test('product file path must be an uploaded product image key', function () {
    actingAsAdmin();

    $this->postJson(route('api.v1.admin.products.store'), [
        'name' => 'Laravel Course',
        'price' => 49.99,
        'file_path' => '../secrets/invoice.pdf',
    ])->assertUnprocessable()->assertJsonValidationErrors(['file_path']);
});

test('replacing a product image deletes the old file', function () {
    Storage::fake(Product::IMAGE_DISK);
    actingAsAdmin();
    $oldFilePath = 'products/'.Str::uuid().'.png';
    $newFilePath = 'products/'.Str::uuid().'.jpg';
    Storage::disk(Product::IMAGE_DISK)->put($oldFilePath, 'old');
    Storage::disk(Product::IMAGE_DISK)->put($newFilePath, 'new');
    $product = Product::factory()->create(['file_path' => $oldFilePath]);

    $this->patchJson(route('api.v1.admin.products.update', $product), [
        'file_path' => $newFilePath,
    ])->assertOk()->assertJsonPath('data.file_path', $newFilePath);

    Storage::disk(Product::IMAGE_DISK)->assertMissing($oldFilePath);
    Storage::disk(Product::IMAGE_DISK)->assertExists($newFilePath);
});

test('admin can remove a product image', function () {
    Storage::fake(Product::IMAGE_DISK);
    actingAsAdmin();
    $filePath = 'products/'.Str::uuid().'.png';
    Storage::disk(Product::IMAGE_DISK)->put($filePath, 'image');
    $product = Product::factory()->create(['file_path' => $filePath]);

    $this->patchJson(route('api.v1.admin.products.update', $product), [
        'file_path' => null,
    ])->assertOk()
        ->assertJsonPath('data.file_path', null)
        ->assertJsonPath('data.image_url', null);

    Storage::disk(Product::IMAGE_DISK)->assertMissing($filePath);
});
