<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $settings = [
            [
                'name' => 'Website Maintenance',
                'key' => 'maintenance_mode',
                'value' => false,
            ],
            [
                'name' => 'Operation Hours',
                'key' => 'operation_hours',
                'value' => json_encode([
                    [
                        'day' => 'Monday',
                        'opening' => '00:00',
                        'closing' => '23:59',
                    ],
                    [
                        'day' => 'Tuesday',
                        'opening' => '00:00',
                        'closing' => '23:59',
                    ],
                    [
                        'day' => 'Wednesday',
                        'opening' => '00:00',
                        'closing' => '23:59',
                    ],
                    [
                        'day' => 'Thursday',
                        'opening' => '00:00',
                        'closing' => '23:59',
                    ],
                    [
                        'day' => 'Friday',
                        'opening' => '00:00',
                        'closing' => '23:59',
                    ],
                    [
                        'day' => 'Saturday',
                        'opening' => '00:00',
                        'closing' => '23:59',
                    ],
                    [
                        'day' => 'Sunday',
                        'opening' => '00:00',
                        'closing' => '23:59',
                    ],
                ]),
            ],
        ];

        Setting::upsert($settings, ['key'], ['value']);
    }
}
