<?php
namespace App\Implementation;

use GuzzleHttp\Promise;
use App\Interface\StreamDataProviderAPI;
use Illuminate\Support\Facades\Http;

class TwitchStreamDataProviderAPI implements StreamDataProviderAPI
{
    private $lowest_viewer_count_of_logged_in_user = null;

    public function getUserCredentialsandUserFollowedStreams($access_token, $tags_promises, $tag_ids_of_streams_followed_by_user, $tags_of_streams_followed_by_user) : Promise\Promise
    {
        $promise = new Promise\Promise(
            function () use (&$promise, $access_token, $tags_promises, $tag_ids_of_streams_followed_by_user, $tags_of_streams_followed_by_user) {
                try {
                    $counter = 1;
                    $responseData = array();
                    $streams_followed_by_user = array();
                    $gameId_of_streams_followed_by_user = array();
                    $url = 'https://api.twitch.tv/helix/users';
                    $user_details_request = Http::withHeaders(['Authorization' => 'Bearer ' . $access_token, 'Client-Id' => '1hzse3axp1jz580x3eqa0ohmew7rwr'])->get($url);
                    if (! $user_details_request->successful()) {
                        $promise->reject($user_details_request);
                        return;
                    }
                    $user_details_response = $user_details_request->json();
                    $user_details_data = $user_details_response['data'];
                    $user_details = $user_details_data[0];
                    $user_id = $user_details['id'];
                    $pagination_cursor = '';
                    $user_followed_streams = null;
                    do {
                        $url = 'https://api.twitch.tv/helix/streams/followed?user_id=' . $user_id;
                        $user_followed_stream_request = Http::withHeaders(['Authorization' => 'Bearer ' . $access_token, 'Client-Id' => '1hzse3axp1jz580x3eqa0ohmew7rwr'])->get($url);
                        if (! $user_followed_stream_request->successful()) {
                            $promise->reject($user_followed_stream_request);
                            $pagination_cursor  = '';
                            return;
                        }
                        $user_followed_stream_response = $user_followed_stream_request->json();
                        $user_followed_streams = $user_followed_stream_response['data'];
                        $pagination = $user_followed_stream_response['pagination'];
                        foreach ($user_followed_streams as $stream) {
                            array_push($streams_followed_by_user,$stream);
                            array_push($gameId_of_streams_followed_by_user,$stream['game_id']);
                            $tag_ids = $stream['tag_ids'];
                            $tagpromise = $this->getTagDetails($tag_ids, $access_token, $tag_ids_of_streams_followed_by_user, $tags_of_streams_followed_by_user);
                            $tags_promises[$counter] = $tagpromise;
                            $viewer_count_for_user = $stream['viewer_count'];
                            if (is_null($this->lowest_viewer_count_of_logged_in_user)) {
                                $this->lowest_viewer_count_of_logged_in_user = $viewer_count_for_user;
                            }
                            else {
                               if ($viewer_count_for_user < $this->lowest_viewer_count_of_logged_in_user) {
                                $this->lowest_viewer_count_of_logged_in_user = $viewer_count_for_user;
                               }
                            }
                            $counter++;
                        }
                        if (array_key_exists('cursor',$pagination))
                        {
                            $pagination_cursor = $pagination['cursor'];
                        }
                        else
                        {
                            $pagination_cursor  = '';
                        }
                    } while (!empty($pagination_cursor));
                    $responseData['userdata'] = $user_details;
                    $responseData['userfollowedstreams'] = $streams_followed_by_user;
                    $responseData['gameIdofuserfollowedstreams'] = $gameId_of_streams_followed_by_user;
                    $responseData['lowestviewercountofloggedinuser'] = $this->lowest_viewer_count_of_logged_in_user;
                    $promise->resolve($responseData);
                } catch (Exception $e) {
                    $promise->reject($e);
                }
            }
        );
        return $promise;
    }

    private function getTagDetails($tags, $access_token, $tag_ids_of_streams_followed_by_user, $tags_of_streams_followed_by_user)
    {
        $tag_promise = new Promise\Promise(
            function () use (&$promise, $tags, $access_token, $tag_ids_of_streams_followed_by_user, $tags_of_streams_followed_by_user) {
                foreach ($tags as $tag) {
                    try {
                        $url = 'https://api.twitch.tv/helix/tags/streams?tag_id';
                        $tag_details_request = Http::withHeaders(['Authorization' => 'Bearer ' . $access_token, 'Client-Id' => '1hzse3axp1jz580x3eqa0ohmew7rwr'])->get($url);
                        $tag_details_response = $tag_details_request->json();
                        $tag_details = $tag_details_response['data'];
                        $tags_of_streams_followed_by_user[$tag] = $tag_details;
                        array_push( $tag_ids_of_streams_followed_by_user,$tag);
                    } catch (Exception $e) {
                    }
                }
                $promise->resolve('finished');
            }
        );
        return $tag_promise;
    }

    public function validateAccessCode($code)
    {
        $result = array();
        $iscodevalid = true;
        $get_auth_token_response = Http::post("https://id.twitch.tv/oauth2/token?client_id=1hzse3axp1jz580x3eqa0ohmew7rwr&client_secret=c11b8u3ns3b2479vo0ex9k0dalwbzs&code=$code&grant_type=authorization_code&redirect_uri=http://localhost:8080/loadstream");
        if (! $get_auth_token_response->successful()) {
            $iscodevalid = false;
            $result['iscodevalid'] = $iscodevalid;
            $result['errormessage'] = $get_auth_token_response;
            return $result;
        }
        $auth_token_response_body = $get_auth_token_response->json();
        $auth_token = $auth_token_response_body['access_token'];
        $result['iscodevalid'] = $iscodevalid;
        $result['token'] = $auth_token;
        return $result;
    }
    public function validateAuthToken($access_token)
    {
        $isauthtokenvalid = true;
        $url = 'https://id.twitch.tv/oauth2/validate';
        $validate_auth_request = Http::withHeaders(['Authorization' => 'Bearer ' . $access_token])->get($url);
        if (! $validate_auth_request->successful()) {
            $isauthtokenvalid = false;
        }
        return $isauthtokenvalid;
    }
}