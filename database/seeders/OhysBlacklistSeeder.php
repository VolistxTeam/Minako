<?php

namespace Database\Seeders;

use App\Repositories\OhysBlacklistTitleRepository;
use Illuminate\Database\Seeder;

class OhysBlacklistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(OhysBlacklistTitleRepository $repository)
    {
        $repository->Create([
            'name' => 'Angel Beats'
        ]);
        $repository->Create([
            'name' => 'Black Clover'
        ]);
        $repository->Create([
            'name' => 'Code Geass'
        ]);
        $repository->Create([
            'name' => 'Dr. Stone'
        ]);
        $repository->Create([
            'name' => 'Fairy Tail'
        ]);
        $repository->Create([
            'name' => 'Fruits Basket'
        ]);
        $repository->Create([
            'name' => 'Goblin Slayer'
        ]);
        $repository->Create([
            'name' => 'Jujutsu Kaisen'
        ]);
        $repository->Create([
            'name' => 'Noragami'
        ]);

        $repository->Create([
            'name' => 'Overlord'
        ]);
        $repository->Create([
            'name' => 'Spy X Family'
        ]);
        $repository->Create([
            'name' => 'Steins;Gate'
        ]);
        $repository->Create([
            'name' => 'Tokyo Ghoul'
        ]);
        $repository->Create([
            'name' => 'Toradora!'
        ]);
        $repository->Create([
            'name' => 'My Hero Academia'
        ]);
    }
}
