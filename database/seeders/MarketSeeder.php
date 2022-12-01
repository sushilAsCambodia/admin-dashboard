<?php

namespace Database\Seeders;

use App\Models\Market;
use Illuminate\Database\Seeder;

class MarketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'id' => 1,
                'name' => 'A',
                'description' => 'standard rate and Rebate',
                'status' => 'Active',
                'deleted_at' => null,
                'created_at' => '2022-10-04 14:14:40',
                'updated_at' => '2022-10-04 14:14:40',
            ],
            [
                'id' => 2,
                'name' => 'B',
                'description' => 'standard odds with KHR',
                'status' => 'Active',
                'deleted_at' => null,
                'created_at' => '2022-10-04 14:55:33',
                'updated_at' => '2022-10-04 14:55:33',
            ],
            [
                'id' => 3,
                'name' => 'C',
                'description' => 'standard odds with KHR',
                'status' => 'Active',
                'deleted_at' => null,
                'created_at' => '2022-10-04 15:22:10',
                'updated_at' => '2022-10-04 15:22:10',
            ],
        ];

        foreach ($data as $info) {
            Market::updateOrcreate(['id' => $info['id']], $info);
        }
    }
}
