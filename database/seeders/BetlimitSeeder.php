<?php

namespace Database\Seeders;

use App\Models\BetLimit;
use Illuminate\Database\Seeder;

class BetlimitSeeder extends Seeder
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
                'name' => 'AA',
                'description' => 'Standard bet and game limit',
                'currency_id' => 2,
                'created_at' => '2022-10-04 14:19:08',
                'updated_at' => '2022-10-04 14:19:08',
                'deleted_at' => null,
            ],
            [
                'id' => 2,
                'name' => 'AB',
                'description' => 'standard limit with KHR',
                'currency_id' => 21,
                'created_at' => '2022-10-04 14:53:05',
                'updated_at' => '2022-10-04 14:53:05',
                'deleted_at' => null,
            ],
            [
                'id' => 3,
                'name' => 'AC',
                'description' => 'CNY bet limit',
                'currency_id' => 25,
                'created_at' => '2022-10-04 15:14:21',
                'updated_at' => '2022-10-04 15:14:21',
                'deleted_at' => null,
            ],
        ];

        foreach ($data as $info) {
            BetLimit::updateOrcreate(['id' => $info['id']], $info);
        }
    }
}
