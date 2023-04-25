<?php

namespace App\Http\Controllers\Auth;

use App\DataTransferObjects\OhysBlacklistTitleDTO;
use App\Facades\Keys;
use App\Http\Controllers\Services\Controller;
use App\Models\OhysBlacklistTitle;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OhysBlacklistController extends Controller
{
    public function CreateOhysBlacklistTitle(Request $request): JsonResponse
    {
        try {
            $token = Keys::authAccessToken($request->bearerToken());

            if (!$token) {
                return response()->json('Unauthorized', 401);
            }

            $validator = Validator::make($request->all(), [
                'name' => ['bail', 'required', 'string'],
                'reason' => ['bail', 'sometimes', 'string'],
                'is_active' => ['bail', 'sometimes', 'boolean'],
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->first(), 400);
            }

            $newTitle = OhysBlacklistTitle::query()->create([
                'name'        => strtolower($request->input('name')),
                'is_active'   => true,
                'reason'      => $request->input('reason') ?? "DMCA"
            ]);

            return response()->json(OhysBlacklistTitleDTO::fromModel($newTitle)->GetDTO(), 201);
        } catch (Exception $ex) {
            return response()->json('An error has occurred', 500);
        }
    }

    public function UpdateOhysBlacklistTitle(Request $request, $title_id): JsonResponse
    {
        try {
            $token = Keys::authAccessToken($request->bearerToken());

            if (!$token) {
                return response()->json('Unauthorized', 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'title_id' => $title_id
            ]), [
                'title_id' => ['bail', 'required', 'exists:ohys_blacklist,id'],
                'name' => ['bail', 'sometimes', 'string'],
                'reason' => ['bail', 'sometimes', 'string'],
                'is_active' => ['bail', 'sometimes', 'boolean'],
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->first(), 400);
            }

            $title = OhysBlacklistTitle::query()->Find($title_id);

            if (!$title_id) {
                return response()->json('Not found', 404);
            }

            if (!empty($request->input('name'))) {
                $title->name = $request->input('name');
            }

            if (!empty($request->input('is_active'))) {
                $title->is_active = $request->input('is_active');
            }

            if (!empty($request->input('reason'))) {
                $title->reason = $request->input('reason');
            }

            $title->save();

            return response()->json(OhysBlacklistTitleDTO::fromModel($title)->GetDTO(), 201);
        } catch (Exception $ex) {
            return response()->json('An error has occurred', 500);
        }
    }

    public function DeleteOhysBlacklistTitle(Request $request, $title_id): JsonResponse
    {
        try {
            $token = Keys::authAccessToken($request->bearerToken());

            if (!$token) {
                return response()->json('Unauthorized', 401);
            }

            $validator = Validator::make([
                'title_id' => $title_id
            ], [
                'title_id' => ['bail', 'required', 'exists:ohys_blacklist,id'],
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->first(), 400);
            }

            $toBeDeletedTitleBlacklist = OhysBlacklistTitle::query()->Find($title_id);

            if (!$title_id) {
                return response()->json('Not found', 404);
            }

            $toBeDeletedTitleBlacklist->delete();

            return response()->json(null, 204);
        } catch (Exception $ex) {
            return response()->json('An error has occurred', 500);
        }
    }

    public function GetOhysBlacklistTitle(Request $request, $title_id): JsonResponse
    {
        try {
            $token = Keys::authAccessToken($request->bearerToken());

            if (!$token) {
                return response()->json('Unauthorized', 401);
            }

            $validator = Validator::make([
                'title_id' => $title_id
            ], [
                'title_id' => ['bail', 'required', 'exists:ohys_blacklist,id'],
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->first(), 400);
            }

            $title = OhysBlacklistTitle::query()->where('id', $title_id)->first();

            if (!$title) {
                return response()->json('Not found', 404);
            }

            return response()->json(OhysBlacklistTitleDTO::fromModel($title)->GetDTO(), 201);
        } catch (Exception $ex) {
            return response()->json('An error has occurred', 500);
        }
    }

    public function GetOhysBlacklistTitles(Request $request): JsonResponse
    {
        try {
            $token = Keys::authAccessToken($request->bearerToken());

            if (!$token) {
                return response()->json('Unauthorized', 401);
            }

            $titles = OhysBlacklistTitle::all();

            $items = [];

            foreach ($titles as $item) {
                $items[] = OhysBlacklistTitleDTO::fromModel($item)->GetDTO();
            }

            return response()->json($items);
        } catch (Exception $ex) {
            return response()->json('An error has occurred', 500);
        }
    }
}
