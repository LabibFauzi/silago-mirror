<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\MarketplaceDeal;
use App\Models\Report;
use App\Models\AdminAuditLog;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create dummy sellers (5)
        $sellers = [];
        for ($i = 1; $i <= 5; $i++) {
            $sellers[] = User::create([
                'name' => "Penjual $i",
                'username' => "seller$i",
                'nim' => '12345' . $i,
                'email' => "seller$i@polines.ac.id",
                'password' => bcrypt('password'),
                'phone' => '0812345678' . $i,
                'role' => 'user',
                'status' => $i % 5 === 0 ? 'suspended' : 'active',
            ]);
        }

        // Create dummy buyers (5)
        $buyers = [];
        for ($i = 1; $i <= 5; $i++) {
            $buyers[] = User::create([
                'name' => "Pembeli $i",
                'username' => "buyer$i",
                'nim' => '54321' . $i,
                'email' => "buyer$i@polines.ac.id",
                'password' => bcrypt('password'),
                'phone' => '0887654321' . $i,
                'role' => 'user',
                'status' => 'active',
            ]);
        }

        // Create products
        $categories = [1, 2, 3, 4, 5, 6, 7];
        $products = [];
        $statuses = ['available', 'sold'];
        $verifications = ['pending', 'verified', 'rejected'];

        // Nama produk yang bervariasi agar search bisa di-test
        $productNames = [
            1 => 'Laptop ASUS VivoBook Bekas',
            2 => 'Jaket Kulit Vintage',
            3 => 'Sepeda Gunung Polygon',
            4 => 'Buku Pemrograman Dart Flutter',
            5 => 'Rice Cooker Mini',
            6 => 'Dumbbell Set 5KG',
            7 => 'Tas Ransel Eiger',
            8 => 'Mouse Logitech Wireless',
            9 => 'Kaos Polos Cotton',
            10 => 'Helm Motor SNI',
            11 => 'Novel Laskar Pelangi',
            12 => 'Lampu Belajar LED',
            13 => 'Matras Yoga',
            14 => 'Charger Laptop Lenovo',
            15 => 'Celana Jeans',
        ];

        for ($i = 1; $i <= 15; $i++) {
            $status = 'available'; 
            $verification = $i <= 10 ? 'verified' : ($i <= 13 ? 'pending' : 'rejected');
            $seller = $sellers[($i - 1) % 5];

            $product = Product::create([
                'seller_id' => $seller->id,
                'category_id' => $categories[($i - 1) % 7],
                'name' => $productNames[$i],
                'description' => "Deskripsi produk dummy $i - barang bekas dalam kondisi baik.",
                'condition' => $i % 3 === 0 ? 'baru' : 'bekas',
                'specification' => "Spesifikasi produk $i",
                'completeness' => $i % 2 === 0 ? 'lengkap' : 'tidak lengkap',
                'sell_reason' => 'Tidak dipakai lagi',
                'price' => 50000 + ($i * 10000),
                'status' => $status,
                'verification_status' => $verification,
                'verified_by' => $verification === 'verified' ? 1 : null,
                'verified_at' => $verification === 'verified' ? now()->subDays(rand(1, 30)) : null,
                'rejection_note' => $verification === 'rejected' ? 'Foto produk tidak jelas' : null,
                'expires_at' => now()->addDays(30),
            ]);

            $products[] = $product;

            // Add product images
            for ($j = 1; $j <= 2; $j++) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => 'https://via.placeholder.com/400x300?text=Product+' . $i,
                    'sort_order' => $j,
                ]);
            }
        }

        // Create conversations and messages for deals
        $conversations = [];
        foreach ($products as $idx => $product) {
            $buyer = $buyers[$idx % 5];
            
            if ($buyer->id !== $product->seller_id) {
                $conversation = Conversation::create([
                    'product_id' => $product->id,
                    'buyer_id' => $buyer->id,
                    'seller_id' => $product->seller_id,
                ]);
                $conversations[] = $conversation;

                // Create messages
                for ($j = 0; $j < 3; $j++) {
                    Message::create([
                        'conversation_id' => $conversation->id,
                        'sender_id' => $j % 2 === 0 ? $buyer->id : $product->seller_id,
                        'type' => 'text',
                        'message' => 'Pesan dummy ' . ($j + 1) . ' untuk produk ' . $product->name,
                    ]);
                }
            }
        }

        // Create marketplace deals (transactions) - distributed over 7 days
        $verifiedProducts = Product::where('verification_status', 'verified')->get();
        $dealStates = [];

        foreach ($verifiedProducts->take(20) as $idx => $product) {
            $buyer = $buyers[$idx % 5];
            $conv = $conversations[$idx % count($conversations)] ?? null;

            if (!$conv || $buyer->id === $product->seller_id) {
                continue;
            }

            $lastMessage = $conv->messages()->latest()->first();
            $status = ['negotiating', 'agreed', 'completed', 'cancelled'][rand(0, 3)];
            
            // Distribute deals over last 7 days
            $daysAgo = rand(0, 6);

            $deal = MarketplaceDeal::create([
                'conversation_id' => $conv->id,
                'accepted_message_id' => $lastMessage?->id,
                'product_id' => $product->id,
                'buyer_id' => $buyer->id,
                'seller_id' => $product->seller_id,
                'quantity' => rand(1, 3),
                'agreed_price' => $product->price * (0.8 + rand(0, 40) / 100),
                'payment_method' => ['cod', 'transfer'][rand(0, 1)],
                'status' => $status,
                'agreed_at' => now()->subDays($daysAgo)->subHours(rand(0, 23)),
                'completed_at' => $status === 'completed' ? now()->subDays(max(0, $daysAgo - 1)) : null,
                'cancelled_at' => $status === 'cancelled' ? now()->subDays(max(0, $daysAgo - 1)) : null,
                'completed_lat' => $status === 'completed' ? -7.0495 + (rand(-100, 100) / 10000) : null,
                'completed_lng' => $status === 'completed' ? 110.4375 + (rand(-100, 100) / 10000) : null,
                'created_at' => now()->subDays($daysAgo)->subHours(rand(0, 23)),
                'updated_at' => now()->subDays($daysAgo)->subHours(rand(0, 23)),
            ]);

            $dealStates[] = $deal;
        }

        // Create reports (disputes)
        foreach (array_slice($dealStates, 0, 4) as $idx => $deal) {
            $reportTypes = ['seller_unresponsive', 'item_not_as_described', 'payment_issue'];
            Report::create([
                'reporter_id' => $idx % 2 === 0 ? $deal->buyer_id : $deal->seller_id,
                'reported_user_id' => $idx % 2 === 0 ? $deal->seller_id : $deal->buyer_id,
                'marketplace_deal_id' => $deal->id,
                'product_id' => $deal->product_id,
                'reason' => $reportTypes[$idx % 3],
                'description' => 'Deskripsi laporan dummy ' . ($idx + 1),
                'status' => ['pending', 'reviewed', 'resolved'][$idx % 3],
                'resolution' => $idx % 3 === 2 ? ['support_buyer', 'support_seller'][rand(0, 1)] : null,
                'admin_note' => $idx % 3 === 2 ? 'Keputusan sesuai bukti' : null,
                'reviewed_by' => $idx % 3 >= 1 ? 1 : null,
                'reviewed_at' => $idx % 3 >= 1 ? now()->subDays(2) : null,
            ]);
        }

        // Create audit logs
        $actions = [
            ['category' => 'product', 'action' => 'Setujui barang'],
            ['category' => 'product', 'action' => 'Tolak barang'],
            ['category' => 'user', 'action' => 'Suspend pengguna'],
            ['category' => 'user', 'action' => 'Aktifkan pengguna'],
            ['category' => 'report', 'action' => 'Proses laporan'],
            ['category' => 'report', 'action' => 'Selesaikan laporan'],
            ['category' => 'transaction', 'action' => 'Dukung pembeli'],
            ['category' => 'setting', 'action' => 'Ubah pengaturan'],
        ];

        for ($i = 0; $i < 20; $i++) {
            $action = $actions[$i % count($actions)];
            AdminAuditLog::create([
                'admin_id' => 1,
                'category' => $action['category'],
                'action' => $action['action'],
                'target' => 'Target #' . ($i + 1),
                'note' => 'Catatan audit log dummy ' . ($i + 1),
                'meta' => json_encode(['user' => 'superadmin', 'ip' => '127.0.0.1']),
                'created_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
            ]);
        }
    }
}
