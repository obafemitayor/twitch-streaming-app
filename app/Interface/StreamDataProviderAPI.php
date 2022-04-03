<?php
namespace App\Interface;

use GuzzleHttp\Promise;
interface StreamDataProviderAPI
{
    public function getUserCredentialsandUserFollowedStreams($access_token, $tags_promises, $tag_ids_of_streams_followed_by_user, $tags_of_streams_followed_by_user) : Promise\Promise;
    public function validateAccessCode($code);
    public function validateAuthToken($auth_token);
}