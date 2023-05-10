<?php

namespace Database\Seeders;

use App\Models\OhysBlacklistTitle;
use Illuminate\Database\Seeder;

class OhysBlacklistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $filename = __DIR__ . '/../../blacklist_anime.txt';
        $names = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $titles = [];
        foreach ($names as $name) {
            $titles[] = [
                'id' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
                'name' => strtolower($name),
                'is_active' => true,
                'reason' => 'Copyright Infringement',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        OhysBlacklistTitle::query()->insert($titles);
    }
}
