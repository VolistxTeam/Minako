<?php

namespace App\Http\Controllers\Services;

use Illuminate\Support\Facades\Storage;

class ExportController extends Controller
{
    public function DumpAnime()
    {
        if (Storage::disk('local')->exists('anime.json')) {
            return response()->json(json_decode(Storage::disk('local')->get('anime.json')));
        } else {
            return response('', 404);
        }

    }
}
