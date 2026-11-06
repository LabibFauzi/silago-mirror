<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class StorageController extends Controller
{
    /**
     * Fallback penyajian file storage publik.
     *
     * Biasanya Apache melayani /storage lewat symlink, tetapi route ini
     * memastikan gambar tetap tampil bila symlink belum dibuat.
     */
    public function show(string $path)
    {
        abort_if(str_contains($path, '..'), 404);
        abort_unless(
            preg_match('/\.(jpe?g|png|webp|gif|svg)$/i', $path) === 1,
            404
        );

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);

        return $disk->response($path);
    }
}
