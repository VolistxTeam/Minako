<?php

namespace App\Console\Commands\Auth;

use App\Facades\Keys;
use App\Repositories\AccessTokenRepository;
use Illuminate\Console\Command;

class AccessKeyGenerateCommand extends Command
{
    private AccessTokenRepository $accessTokenRepository;

    public function __construct(AccessTokenRepository $accessTokenRepository)
    {
        parent::__construct();
        $this->accessTokenRepository = $accessTokenRepository;
    }
    protected $signature = 'access-key:generate';

    protected $description = 'Create an access key';

    public function handle(): void
    {
        $saltedKey = Keys::randomSaltedKey();

        $this->accessTokenRepository->Create([
            'key'             => $saltedKey['key'],
            'salt'            => $saltedKey['salt'],
        ]);

        $this->components->info('Your access key is created: "'.$saltedKey['key'].'"');
    }
}
