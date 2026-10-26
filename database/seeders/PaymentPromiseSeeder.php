<?php

namespace Database\Seeders;

use App\Models\PaymentPromise;
use Illuminate\Database\Seeder;

class PaymentPromiseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PaymentPromise::factory()->count(5)->create();
    }
}
