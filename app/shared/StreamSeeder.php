<?php

namespace App\Shared;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class StreamSeeder
{
    public function seed_database($databaseProvider)
    {
        echo nl2br("Starting Seeding..... \r\n");
        try {
            $response = Http::post('https://id.twitch.tv/oauth2/token?client_id=1hzse3axp1jz580x3eqa0ohmew7rwr&client_secret=c11b8u3ns3b2479vo0ex9k0dalwbzs&grant_type=client_credentials');
            $response_body = $response->json();
            $authtoken = $response_body['access_token'];
            $pagination_cursor = '';
            do {
                $url = empty($pagination_cursor) ? 'https://api.twitch.tv/helix/streams' : 'https://api.twitch.tv/helix/streams?after=' . $pagination_cursor;
                $stream_res = Http::withHeaders(['Authorization' => 'Bearer ' . $authtoken, 'Client-Id' => '1hzse3axp1jz580x3eqa0ohmew7rwr'])->get($url);
                $stream_response = $stream_res->json();
                $streams = $stream_response['data'];
                $pagination = $stream_response['pagination'];
                if (array_key_exists('cursor',$pagination))
                {
                    echo nl2br("pagination cursor From response is " . $pagination['cursor'] .  "\r\n");
                    $pagination_cursor = $pagination['cursor'];
                    echo nl2br("pagination_cursor is " . $pagination_cursor .  "\r\n");
                    foreach ($streams as $stream) {
                        $databaseProvider-> seed_database($stream);
                    }
                    echo nl2br("Data Inserted \r\n");
                }
                else
                {
                    $pagination_cursor  = '';
                }
            } while (!empty($pagination_cursor));
          }
          catch(Exception $e) {
            die('Message: ' .$e->getMessage());
          }
          echo 'Database Seeding Ended\r\n';
    }
}
