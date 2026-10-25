<?php

namespace App\Http\Controllers;

use App\Models\ProductImage;

class ProductImageController extends Controller
{
    /**
     * Sajikan gambar produk yang tersimpan di database.
     */
    public function show(ProductImage $productImage)
    {
        $stored = $productImage->data()->first();

        abort_unless($stored && $stored->data !== null, 404);

        $bytes = base64_decode($stored->data, true);

        abort_if($bytes === false || $bytes === '', 404);

        return response($bytes, 200, [
            'Content-Type' => $productImage->mime_type ?: 'image/jpeg',
            'Content-Length' => (string) strlen($bytes),
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
