<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $languages = [
            [
                'id' => 1,
                'name' => 'English',
                'locale' => 'en',
            ],
            [
                'id' => 2,
                'name' => '中文',
                'locale' => 'ch',
            ],
            [
                'id' => 3,
                'name' => 'Khmer',
                'locale' => 'kh',
            ],
            [
                'id' => 4,
                'name' => 'Vietnamese',
                'locale' => 'vt',
            ],
            [
                'id' => 5,
                'name' => 'Filipino',
                'locale' => 'ph',
            ],
        ];
        foreach ($languages as $language) { // filters
            Language::updateOrcreate(['id' => $language['id']], $language);
        }
    }
}
