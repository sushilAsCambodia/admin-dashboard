<?php

namespace Database\Seeders;

use App\Models\Merchant;
use Illuminate\Database\Seeder;

class MerchantSeeder extends Seeder
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
                'name' => 'KK',
                'code' => 'kktest',
                'bet_limit_id' => 1,
                'market_id' => 1,
                'currency_id' => 2,
                'secret_key' => 'aU7RC1JC6qTFv73Rs8kkmSekhmCUXw00O1JloFU',
                'token' => '',
                'credit_limit' => 1000000.00,
                'description' => 'kk merchant for testing',
                'status' => 'Active',
                'created_at' => '2022-10-04 14:21:24',
                'updated_at' => '2022-10-04 14:21:24',
                'deleted_at' => null,
            ],
            [
                'id' => 2,
                'name' => 'KK2',
                'code' => 'kk2test',
                'bet_limit_id' => 2,
                'market_id' => 2,
                'currency_id' => 21,
                'secret_key' => 'aU7RC1JC6qTFv73Rs8kkmSekhmCUXw00O1JloFU',
                'token' => '',
                'credit_limit' => 100000.00,
                'description' => 'test merchant with KHR currency',
                'status' => 'Active',
                'created_at' => '2022-10-04 14:56:38',
                'updated_at' => '2022-10-04 14:56:38',
                'deleted_at' => null,
            ],
            [
                'id' => 3,
                'name' => 'kk3',
                'code' => 'kk3test',
                'bet_limit_id' => 3,
                'market_id' => 3,
                'currency_id' => 25,
                'secret_key' => 'aU7RC1JC6qTFv73Rs8kkmSekhmCUXw00O1JloFU',
                'token' => '',
                'credit_limit' => 1000.00,
                'description' => 'test',
                'status' => 'Active',
                'created_at' => '2022-10-04 15:22:48',
                'updated_at' => '2022-10-04 15:22:48',
                'deleted_at' => null,
            ],
        ];

        foreach ($data as $info) {
            Merchant::updateOrcreate(['id' => $info['id']], $info);
        }
    }
}
