<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProductImageService
{
    /**
     * Simpan gambar produk ke database (PostgreSQL/Supabase), bukan ke disk
     * lokal Render yang bersifat ephemeral.
     */
    public function create(Product $product, UploadedFile $file, int $sortOrder = 0): ProductImage
    {
        $realPath = $file->getRealPath();
        if ($realPath === false) {
            throw new RuntimeException('File gambar tidak dapat dibaca.');
        }

        $contents = file_get_contents($realPath);
        if ($contents === false) {
            throw new RuntimeException('File gambar tidak dapat dibaca.');
        }

        $image = $product->images()->create([
            'image_path' => '',
            'has_data' => true,
            'mime_type' => $this->mimeType($file),
            'sort_order' => $sortOrder,
        ]);

        $image->data()->create([
            'data' => base64_encode($contents),
        ]);

        return $image;
    }

    /**
     * Hapus gambar beserta data dan file lama (jika masih ada di disk).
     */
    public function delete(ProductImage $image): void
    {
        if ($image->image_path && ! str_starts_with($image->image_path, 'http://') && ! str_starts_with($image->image_path, 'https://')) {
            $path = ltrim($image->image_path, '/');
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $image->data()->delete();
        $image->delete();
    }

    private function mimeType(UploadedFile $file): string
    {
        return $file->getMimeType()
            ?: $file->getClientMimeType()
            ?: 'image/jpeg';
    }
}
