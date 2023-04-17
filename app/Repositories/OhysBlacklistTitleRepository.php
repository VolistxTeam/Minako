<?php

namespace App\Repositories;

use App\Models\OhysBlacklistTitle;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class OhysBlacklistTitleRepository
{
    public function Create(array $inputs): Model|Builder
    {
        return OhysBlacklistTitle::query()->create([
            'name'        => strtolower($inputs['name']),
            'is_active'   => true,
            'reason'      => $inputs['reason'] ?? "DMCA"
        ]);
    }

    public function Update($title_id, array $inputs): ?object
    {
        $title = $this->Find($title_id);

        if (!$title_id) {
            return null;
        }

        if (array_key_exists('name', $inputs)) {
            $title->name = $inputs['name'];
        }

        if (array_key_exists('is_active', $inputs)) {
            $title->is_active = $inputs['is_active'];
        }

        if (array_key_exists('reason', $inputs)) {
            $title->reason = $inputs['reason'];
        }

        $title->save();

        return $title;
    }

    public function Find($title_id): ?object
    {
        return OhysBlacklistTitle::query()->where('id', $title_id)->first();
    }

    public  function Contains(string $search):bool  {
       return OhysBlacklistTitle::query()->where('name', 'LIKE', `%$search%`)->exists();
    }

    public function Delete($title_id): ?bool
    {
        $toBeDeletedTitleBlacklist = $this->Find($title_id);

        if (!$title_id) {
            return null;
        }

        try {
            $toBeDeletedTitleBlacklist->delete();
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    public function FindAll(): \Illuminate\Database\Eloquent\Collection
    {
        return OhysBlacklistTitle::all();
    }
}
