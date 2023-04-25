<?php

namespace App\Console\Commands\Auth;

use App\Facades\Keys;
use App\Helpers\SHA256Hasher;
use App\Models\AccessToken;
use Illuminate\Console\Command;

class AccessKeyGenerateCommand extends Command
{
    protected $signature = 'access-key:generate';

    protected $description = 'Create an access key';

    public function handle(): void
    {
        $saltedKey = Keys::randomSaltedKey();

        AccessToken::query()->create([
            'key'           => substr($saltedKey['key'], 0, 32),
            'secret'        => SHA256Hasher::make(substr($saltedKey['key'], 32), ['salt' => $saltedKey['salt']]),
            'secret_salt'   => $saltedKey['salt'],
        ]);

        $this->components->info('Your access key is created: "'.$saltedKey['key'].'"');
    }
}
