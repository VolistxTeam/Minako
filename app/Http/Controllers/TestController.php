<?php

namespace App\Http\Controllers;

use App\Helpers\JikanAPI;
use App\Helpers\NyaaCrawler;
use App\Models\NotifyAnime;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function Test(Request $request) {
        $nyaaCrawler = new NyaaCrawler();
        $data = $nyaaCrawler->getTorrents(0);
        return response(json_encode($data, JSON_PRETTY_PRINT))->header('Content-Type', 'application/json');

//        $anime = NotifyAnime::searchByTitle($request->input('query'), 10);
//        return response(json_encode($anime, JSON_PRETTY_PRINT))->header('Content-Type', 'application/json');

//        $jikanAPI = new JikanAPI();
//        return response(json_encode($jikanAPI->getAnimeEpisodes(11757), JSON_PRETTY_PRINT))->header('Content-Type', 'application/json');
    }
}
