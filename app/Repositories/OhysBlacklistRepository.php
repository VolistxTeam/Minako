<?php

namespace App\Repositories;

use App\Models\OhysBlacklistTitle;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class OhysBlacklistRepository
{
    public function Create(array $inputs): Model|Builder
    {
        return OhysBlacklistTitle::query()->create([
            'name' => strtolower($inputs['name']),
            'is_active' => true,
            'reason' => $inputs['reason'] ?? 'DMCA',
        ]);
    }

    public function Update(string $titleId, array $inputs): ?object
    {
        $title = $this->Find($titleId);

        if (!$title) {
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

    public function Find(string $titleId): ?object
    {
        return OhysBlacklistTitle::query()->where('id', $titleId)->first();
    }

    public function Delete(string $titleId): ?bool
    {
        $toBeDeletedTitle = $this->Find($titleId);

        if (!$toBeDeletedTitle) {
            return null;
        }

        try {
            $toBeDeletedTitle->delete();

            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    public function FindAll(string $search, int $page, int $limit): ?LengthAwarePaginator
    {
        // Handle empty search
        if ($search === '') {
            $search = 'id:';
        }

        if (!str_contains($search, ':')) {
            return null;
        }

        $columns = Schema::getColumnListing('ohys_blacklist');
        $values = explode(':', $search, 2);
        $columnName = strtolower(trim($values[0]));

        if (!in_array($columnName, $columns)) {
            return null;
        }

        $searchValue = strtolower(trim($values[1]));

        return OhysBlacklistTitle::query()
            ->where($values[0], 'LIKE', "%$searchValue%")
            ->paginate($limit, ['*'], 'page', $page);
    }
}
