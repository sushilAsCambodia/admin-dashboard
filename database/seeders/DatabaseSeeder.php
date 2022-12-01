<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            RoleSeeder::class,
            AdminSeeder::class,
            CurrencySeeder::class,
            ProductSeeder::class,
            GameSeeder::class,
            BetlimitSeeder::class,
            LimitSettingSeeder::class,
            MarketSeeder::class,
            MerchantSeeder::class,
            OddSettingSeeder::class,
            LanguageSeeder::class,
            SettingSeeder::class,
            ResultSeeder::class,
        ]);
    }
}
