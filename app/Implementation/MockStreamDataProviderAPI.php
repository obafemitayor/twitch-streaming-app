<?php
namespace App\Implementation;

use GuzzleHttp\Promise;
use App\Interface\StreamDataProviderAPI;
use Illuminate\Support\Facades\Http;

class MockStreamDataProviderAPI implements StreamDataProviderAPI
{
    private $lowest_viewer_count_of_logged_in_user = 20;

    public function getUserCredentialsandUserFollowedStreams($access_token, $tags_promises, $tag_ids_of_streams_followed_by_user, $tags_of_streams_followed_by_user) : Promise\Promise
    {
        $promise = new Promise\Promise(
            function () use (&$promise, $access_token, $tags_promises, $tag_ids_of_streams_followed_by_user, $tags_of_streams_followed_by_user) {
                try {
                    $counter = 1;
                    $user_followed_streams = array();
                    $data = array("id"=>"41375541868", "game_id"=>"494131", "game_name"=>"Little Nightmares", "viewer_count"=>78365, "tag_ids"=>array("d4bb9c58-2141-4881-bcdc-3fe0505457d1"));
                    array_push( $user_followed_streams,$data);
                    $data = array("id"=>"41375541868", "game_id"=>"494131", "game_name"=>"Little Nightmares", "viewer_count"=>78365, "tag_ids"=>array("d4bb9c58-2141-4881-bcdc-3fe0505457d1"));
                    array_push( $user_followed_streams,$data);
                    $data = array("id"=>"41375541868", "game_id"=>"494131", "game_name"=>"Little Nightmares", "viewer_count"=>78365, "tag_ids"=>array("d4bb9c58-2141-4881-bcdc-3fe0505457d1"));
                    array_push( $user_followed_streams,$data);
                    $streams_followed_by_user = array();
                    $gameId_of_streams_followed_by_user = array();
                    foreach ($user_followed_streams as $stream) {
                        array_push($streams_followed_by_user,$stream);
                        array_push($gameId_of_streams_followed_by_user,$stream['game_id']);
                        $tag_ids = $stream['tag_ids'];
                        $tagpromise = $this->getTagDetails($tag_ids, $access_token, $tag_ids_of_streams_followed_by_user, $tags_of_streams_followed_by_user);
                        $tags_promises[$counter] = $tagpromise;
                        $counter++;
                    }
                    $responseData = array();
                    $responseData['userdata'] = array();
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
                        $tag_details = array();
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
        $result['iscodevalid'] = true;
        $result['token'] = 'token';
        return $result;
    }
    public function validateAuthToken($access_token)
    {
        $isauthtokenvalid = true;
        return $isauthtokenvalid;
    }
}