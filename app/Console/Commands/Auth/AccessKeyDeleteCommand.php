<?php

namespace App\Console\Commands\Auth;

use App\Facades\Keys;
use App\Helpers\SHA256Hasher;
use App\Models\AccessToken;
use Illuminate\Console\Command;

class AccessKeyDeleteCommand extends Command
{
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

        $accessToken = Keys::authAccessToken($token);

        if (!$accessToken) {
            $this->components->error('The specified access key is invalid.');

            return;
        }

        $toBeDeletedToken = AccessToken::query()->Find($accessToken->id);

        if (!$toBeDeletedToken) {
            return null;
        }

        $toBeDeletedToken->delete();

        $this->components->info('Your access key is deleted: '.$token);
    }
}
