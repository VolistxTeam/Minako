<?php

namespace App\Http\Controllers\Auth;

use App\DataTransferObjects\OhysBlacklist;
use App\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Repositories\OhysBlacklistRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OhysBlacklistController extends Controller
{
    private OhysBlacklistRepository $blacklistRepository;

    public function __construct(OhysBlacklistRepository $blacklistRepository)
    {
        $this->blacklistRepository = $blacklistRepository;
    }

    public function Create(Request $request): JsonResponse
    {
        try {
            $token = Auth::authAccessToken($request->bearerToken());

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

            $newTitle = $this->blacklistRepository->Create($request->all());

            return response()->json(OhysBlacklist::fromModel($newTitle)->GetDTO(), 201);
        } catch (Exception $ex) {
            return response()->json('An error has occurred', 500);
        }
    }

    public function Update(Request $request, $title_id): JsonResponse
    {
        try {
            $token = Auth::authAccessToken($request->bearerToken());

            if (!$token) {
                return response()->json('Unauthorized', 401);
            }

            $validator = Validator::make(array_merge($request->all(), [
                'title_id' => $title_id,
            ]), [
                'title_id' => ['bail', 'required', 'exists:ohys_blacklist,id'],
                'name' => ['bail', 'sometimes', 'string'],
                'reason' => ['bail', 'sometimes', 'string'],
                'is_active' => ['bail', 'sometimes', 'boolean'],
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->first(), 400);
            }

            $title = $this->blacklistRepository->Update($title_id, $request->all());

            return response()->json(OhysBlacklist::fromModel($title)->GetDTO(), 201);
        } catch (Exception $ex) {
            return response()->json('An error has occurred', 500);
        }
    }

    public function Delete(Request $request, $title_id): JsonResponse
    {
        try {
            $token = Auth::authAccessToken($request->bearerToken());

            if (!$token) {
                return response()->json('Unauthorized', 401);
            }

            $validator = Validator::make([
                'title_id' => $title_id,
            ], [
                'title_id' => ['bail', 'required', 'exists:ohys_blacklist,id'],
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors()->first(), 400);
            }

            $this->blacklistRepository->Delete($title_id);

            return response()->json(null, 204);
        } catch (Exception $ex) {
            return response()->json('An error has occurred', 500);
        }
    }

    public function Get(Request $request, $title_id): JsonResponse
    {
        try {
            $token = Auth::authAccessToken($request->bearerToken());

            if (!$token) {
                return response()->json('Unauthorized', 401);
            }

            $title = $this->blacklistRepository->Find($title_id);

            if (!$title) {
                return response()->json('Not found', 404);
            }

            return response()->json(OhysBlacklist::fromModel($title)->GetDTO(), 201);
        } catch (Exception $ex) {
            return response()->json('An error has occurred', 500);
        }
    }

    public function GetAll(Request $request): JsonResponse
    {
        try {
            $token = Auth::authAccessToken($request->bearerToken());

            if (!$token) {
                return response()->json('Unauthorized', 401);
            }

            $search = $request->input('search', '');
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 50);

            $items = $this->blacklistRepository->FindAll($search, $page, $limit);

            foreach ($items as $item) {
                $items[] = OhysBlacklist::fromModel($item)->GetDTO();
            }

            return response()->json($items);
        } catch (Exception $ex) {
            return response()->json('An error has occurred', 500);
        }
    }
}
