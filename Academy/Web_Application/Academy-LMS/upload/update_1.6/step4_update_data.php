use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Check if 'sslcommerz' identifier already exists in the payment_gateways table
$sslcommerz = DB::table('payment_gateways')->where('identifier', 'sslcommerz')->first();
if (!$sslcommerz) {
    DB::table('payment_gateways')->insert([
        'identifier' => 'sslcommerz',
        'currency' => 'BDT',
        'title' => 'SSLCommerz',
        'model_name' => 'Sslcommerz',
        'description' => null,
        'keys' => json_encode([
            'store_key' => 'creatxxxxxxxxxxx',
            'store_password' => 'creatxxxxxxxx@ssl',
            'store_live_key' => 'st_live_xxxxxxxxxxxxxxxxxxxxxxxx',
            'store_live_password' => 'sp_live_xxxxxxxxxxxxxxxxxxxxxxxx',
            'sslcz_testmode' => 'true',
            'is_localhost' => 'true',
            'sslcz_live_testmode' => 'false',
            'is_live_localhost' => 'false',
        ]),
        'status' => 1,
        'test_mode' => 1,
        'is_addon' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// Repeat the same logic for the other payment gateways

// Aamarpay
$aamarpay = DB::table('payment_gateways')->where('identifier', 'aamarpay')->first();
if (!$aamarpay) {
    DB::table('payment_gateways')->insert([
        'identifier' => 'aamarpay',
        'currency' => 'BDT',
        'title' => 'Aamarpay',
        'model_name' => 'Aamarpay',
        'description' => null,
        'keys' => json_encode([
            'store_id' => 'xxxxxxxxxxxxx',
            'signature_key' => 'xxxxxxxxxxxxxxxxxxx',
            'store_live_id' => 'st_live_xxxxxxxxxxxxxxxxxxxxxxxx',
            'signature_live_key' => 'si_live_xxxxxxxxxxxxxxxxxxxxxxxx',
        ]),
        'status' => 1,
        'test_mode' => 1,
        'is_addon' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// Doku
$doku = DB::table('payment_gateways')->where('identifier', 'doku')->first();
if (!$doku) {
    DB::table('payment_gateways')->insert([
        'identifier' => 'doku',
        'currency' => 'IDR',
        'title' => 'Doku',
        'model_name' => 'Doku',
        'description' => null,
        'keys' => json_encode([
            'client_id' => 'BRN-xxxx-xxxxxxxxxxxxx',
            'secret_test_key' => 'SK-xxxxxxxxxxxxxxxxxxxx',
            'public_test_key' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
        ]),
        'status' => 1,
        'test_mode' => 1,
        'is_addon' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// Maxicash
$maxicash = DB::table('payment_gateways')->where('identifier', 'maxicash')->first();
if (!$maxicash) {
    DB::table('payment_gateways')->insert([
        'identifier' => 'maxicash',
        'currency' => 'USD',
        'title' => 'Maxicash',
        'model_name' => 'Maxicash',
        'description' => null,
        'keys' => json_encode([
            'merchant_id' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            'merchant_password' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            'merchant_live_id' => 'mr_live_xxxxxxxxxxxxxxxxxxxxxxxx',
            'merchant_live_password' => 'mp_live_xxxxxxxxxxxxxxxxxxxxxxxx',
        ]),
        'status' => 1,
        'test_mode' => 1,
        'is_addon' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}


// Update blogs -> description collation to utf8mb4_unicode_ci
if (Schema::hasColumn('blogs', 'description')) {
    DB::statement("ALTER TABLE `blogs` 
                    CHANGE `description` `description` TEXT 
                    CHARACTER SET utf8mb4 
                    COLLATE utf8mb4_unicode_ci");
}