@php
    $method = $paymentMethod ?? null;
    $label = '-';
    if ($method === 'transfer') {
        $label = 'Transfer Bank';
    } elseif ($method === 'cod') {
        $label = 'Bayar di Tempat (COD)';
    } elseif ($method) {
        $label = ucfirst($method);
    }
@endphp
<span class="text-ink font-medium">{{ $label }}</span>
