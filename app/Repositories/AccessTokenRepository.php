<?php

namespace App\Repositories;

use App\Helpers\SHA256Hasher;
use App\Models\AccessToken;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AccessTokenRepository
{
    public function Create(array $inputs): Model|Builder
    {
        return AccessToken::query()->create([
            'key'           => substr($inputs['key'], 0, 32),
            'secret'        => SHA256Hasher::make(substr($inputs['key'], 32), ['salt' => $inputs['salt']]),
            'secret_salt'   => $inputs['salt'],
        ]);
    }

    public function Find($token_id): object|null
    {
        return AccessToken::query()->where('id', $token_id)->first();
    }

    public function Reset($token_id, $inputs): ?object
    {
        $token = $this->Find($token_id);

        if (!$token) {
            return null;
        }

        $token->key = substr($inputs['key'], 0, 32);
        $token->secret = SHA256Hasher::make(substr($inputs['key'], 32), ['salt' => $inputs['salt']]);
        $token->secret_salt = $inputs['salt'];
        $token->save();

        return $token;
    }

    public function Delete($token_id): ?bool
    {
        $toBeDeletedToken = $this->Find($token_id);

        if (!$toBeDeletedToken) {
            return null;
        }

        $toBeDeletedToken->delete();

        return true;
    }

    public function AuthAccessToken($token): ?object
    {
        return AccessToken::query()->where('key', substr($token, 0, 32))
            ->get()->filter(function ($v) use ($token) {
                return SHA256Hasher::check(substr($token, 32), $v->secret, ['salt' => $v->secret_salt]);
            })->first();
    }
}
