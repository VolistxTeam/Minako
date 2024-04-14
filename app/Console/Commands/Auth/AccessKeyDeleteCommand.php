<?php

namespace App\Console\Commands\Auth;

use Illuminate\Console\Command;
use function Laravel\Prompts\text;

class AccessKeyDeleteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'access-key:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete an access key';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $token = text(
            label: 'Please specify your access key to delete.',
            default: '',
            required: true
        );

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
