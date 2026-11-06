<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'in:Penipuan,Barang terlarang,Sengketa transaksi,Perilaku pengguna,Foto tidak sesuai,Spam chat,Lainnya'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
