<?php

namespace App\Console\Commands\Auth;

use App\Repositories\AccessTokenRepository;
use Illuminate\Console\Command;

class AccessKeyDeleteCommand extends Command
{
    private AccessTokenRepository $accessTokenRepository;

    public function __construct(AccessTokenRepository $accessTokenRepository)
    {
        parent::__construct();
        $this->accessTokenRepository = $accessTokenRepository;
    }

    protected $signature = 'access-key:delete {--key=}';

    protected $description = 'Delete an access key';

    /**
     * @return void
     */
    public function handle()
    {
        $token = $this->option('key');

        if (empty($token)) {
            $this->components->error('Please specify your access key to delete.');

            return;
        }

        $accessToken = $this->accessTokenRepository->AuthAccessToken($token);

        if (!$accessToken) {
            $this->components->error('The specified access key is invalid.');

            return;
        }

        $this->accessTokenRepository->Delete($accessToken->id);

        $this->components->info('Your access key is deleted: '.$token);
    }
}
