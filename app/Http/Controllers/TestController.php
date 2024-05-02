<?php

namespace App\Http\Controllers;

use App\Facades\JikanAPI;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function Test(Request $request)
    {
        //        $nyaaCrawler = new NyaaCrawler();
        //        $data = $nyaaCrawler->getTorrents(0);
        //        return response(json_encode($data, JSON_PRETTY_PRINT))->header('Content-Type', 'application/json');

        //        $anime = NotifyAnime::searchByTitle($request->input('query'), 10);
        //        return response(json_encode($anime, JSON_PRETTY_PRINT))->header('Content-Type', 'application/json');

        return response(json_encode(JikanAPI::getAnimeEpisodes(11757), JSON_PRETTY_PRINT))->header('Content-Type', 'application/json');
    }
}
