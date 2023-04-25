<?php

namespace App\Http\Controllers\Auth;

use App\DataTransferObjects\OhysBlacklistTitleDTO;
use App\Http\Controllers\Services\Controller;
use App\Repositories\AccessTokenRepository;
use App\Repositories\OhysBlacklistTitleRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OhysBlacklistController extends Controller
{
    private OhysBlacklistTitleRepository $ohysBlacklistTitleRepository;
    private AccessTokenRepository $accessTokenRepository;

    public function __construct(OhysBlacklistTitleRepository $ohysBlacklistTitleRepository, AccessTokenRepository $accessTokenRepository)
    {
        $this->ohysBlacklistTitleRepository = $ohysBlacklistTitleRepository;
        $this->accessTokenRepository = $accessTokenRepository;
    }

    public function CreateOhysBlacklistTitle(Request $request): JsonResponse
    {
        try {
            $token = $this->accessTokenRepository->AuthAccessToken($request->bearerToken());

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

            $newTitle = $this->ohysBlacklistTitleRepository->Create($request->all());

            return response()->json(OhysBlacklistTitleDTO::fromModel($newTitle)->GetDTO(), 201);
        } catch (Exception $ex) {
            return response()->json('An error has occurred', 500);
        }
    }

    public function UpdateOhysBlacklistTitle(Request $request, $title_id): JsonResponse
    {
        try {
            $token = $this->accessTokenRepository->AuthAccessToken($request->bearerToken());

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

            $updatedTitle = $this->ohysBlacklistTitleRepository->Update($title_id, $request->all());

            if (!$updatedTitle) {
                return response()->json('Not found', 404);
            }

            return response()->json(OhysBlacklistTitleDTO::fromModel($updatedTitle)->GetDTO(), 201);
        } catch (Exception $ex) {
            return response()->json('An error has occurred', 500);
        }
    }

    public function DeleteOhysBlacklistTitle(Request $request, $title_id): JsonResponse
    {
        try {
            $token = $this->accessTokenRepository->AuthAccessToken($request->bearerToken());

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

            $result = $this->ohysBlacklistTitleRepository->Delete($title_id);

            if ($result === null) {
                return response()->json('Not found', 404);
            }

            return response()->json(null, 204);
        } catch (Exception $ex) {
            return response()->json('An error has occurred', 500);
        }
    }

    public function GetOhysBlacklistTitle(Request $request, $title_id): JsonResponse
    {
        try {
            $token = $this->accessTokenRepository->AuthAccessToken($request->bearerToken());

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

            $title = $this->ohysBlacklistTitleRepository->Find($title_id);

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
            $token = $this->accessTokenRepository->AuthAccessToken($request->bearerToken());

            if (!$token) {
                return response()->json('Unauthorized', 401);
            }

            $titles = $this->ohysBlacklistTitleRepository->FindAll();

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
