<?php

namespace App\Console\Commands\Auth;

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

        $accessToken = $this->AuthAccessToken($token);

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

    private function AuthAccessToken($token): ?object
    {
        return AccessToken::query()->where('key', substr($token, 0, 32))
            ->get()->filter(function ($v) use ($token) {
                return SHA256Hasher::check(substr($token, 32), $v->secret, ['salt' => $v->secret_salt]);
            })->first();
    }
}
