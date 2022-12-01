<?php

namespace Database\Seeders;

use App\Models\GamePlay;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $games = [
            [
                'id' => 1,
                'name' => 'Magnum',
                'abbreviation' => 'M',
                'logo_path' => 'public/logos/uSBcaSYf5xV0MW6zt53yhklZhrJcbiv8tmLs8GiS.png',
                'logo_url' => 'http://api.kk-lotto.com:8080/storage/logos/uSBcaSYf5xV0MW6zt53yhklZhrJcbiv8tmLs8GiS.png',
                'status' => 'Active',
            ],
            [
                'id' => 2,
                'name' => 'Da Ma Cai',
                'abbreviation' => 'P',
                'logo_path' => 'public/logos/AODK45ewx2MNpoUjgbRT95Fo5fA9V8gBnsUcJyhH.png',
                'logo_url' => 'http://api.kk-lotto.com:8080/storage/logos/AODK45ewx2MNpoUjgbRT95Fo5fA9V8gBnsUcJyhH.png',
                'status' => 'Active',
            ],
            [
                'id' => 3,
                'name' => 'Toto',
                'abbreviation' => 'T',
                'logo_path' => 'public/logos/hTrnoOiPMz9QtA2TWU7b7uTgpOgLFGwCIXKJ6azd.png',
                'logo_url' => 'http://api.kk-lotto.com:8080/storage/logos/hTrnoOiPMz9QtA2TWU7b7uTgpOgLFGwCIXKJ6azd.png',
                'status' => 'Active',

            ],

        ];

        foreach ($games as $game) {
            GamePlay::updateOrcreate(['id' => $game['id']], $game);
        }
    }
}
