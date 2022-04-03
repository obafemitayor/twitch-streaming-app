<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Promise;
use Illuminate\Support\Facades\Http;
use App\Interface\DatabaseProvider;
use App\Interface\StreamDataProviderAPI;
use App\Shared\StreamDatabaseLock;

class StreamController extends Controller
{
    protected $databaseProvider;
    protected $streamDataProviderAPI;
    private $tags_promises = array();
    private $tags_of_streams_followed_by_user = array();
    private $tag_ids_of_streams_followed_by_user = array();
    private $lowest_viewer_count_of_logged_in_user = null;

    public function __construct(DatabaseProvider $_databaseProvider, StreamDataProviderAPI $_streamDataProviderAPI)
    {
        $this->middleware('verified');
        $this->databaseProvider = $_databaseProvider;
        $this->streamDataProviderAPI = $_streamDataProviderAPI;
    }

    public function getStreamData(Request $request)
    {
        $access_token = $request->header('authToken');
        $get_user_followed_streams = $request->input('getuserfollowedstreams');
        $get_total_number_of_streams = $request->input('gettotalnumberofstreams');
        $get_top_games_by_viewer_count = $request->input('gettopgamesbyviewercount');
        $get_top_streams_by_viewer_count = $request->input('gettopstreamsbyviewercount');
        $get_total_number_of_streams_by_start_time = $request->input('gettotalnumberofstreamsbystarttime');
        $page_index = (int) $request->input('pageindex');
        $page_size = (int) $request->input('pagesize');
        $response = null;
        try {
            $response = $this->loadDashboard($access_token, $get_user_followed_streams, $get_total_number_of_streams, $get_top_games_by_viewer_count, $get_top_streams_by_viewer_count, $get_total_number_of_streams_by_start_time, $page_index, $page_size);
            $response['access_token'] = $access_token;
            return response($response, 200);
        } catch (Exception $e) {
            return response('An Error Occurred While Trying To Get Stream Data, Please Contact Admin', 500);
        }
    }

    private function loadDashboard($access_token, $get_user_followed_streams, $get_total_number_of_streams,
    $get_top_games_by_viewer_count, $get_top_streams_by_viewer_count, 
    $get_total_number_of_streams_by_start_time, $page_index, $page_size)
    {
        $response = array();
        $user_credentials_and_followed_streams = null;
        $number_of_streams = null;
        $top_game_by_viewer_count = null;
        $top_streams = null;
        $top_streams_user_follow = null;
        $total_number_of_streams_by_start_time = null;
        $shared_tags = null;
        $number_needed_for_user_lowest_viewer_count_to_be_in_top_streams = null;
        if($get_user_followed_streams == 'true')
        {
            $user_credentials_and_followed_streams = $this->streamDataProviderAPI->getUserCredentialsandUserFollowedStreams($access_token, $this->tags_promises, $this->tag_ids_of_streams_followed_by_user, $this->tags_of_streams_followed_by_user);
        }
        $running_operation = null;
        do {
            $running_operation = StreamDatabaseLock::checkIfLockExist();
        } while ($running_operation == true);
        StreamDatabaseLock::aquireLock();
        if($get_total_number_of_streams == 'true')
        {
            $number_of_streams = $this->databaseProvider->get_total_number_of_streams_for_each_game($page_index, $page_size);
        }
        if($get_top_games_by_viewer_count == 'true')
        {
            $top_game_by_viewer_count = $this->databaseProvider->get_top_games_by_viewer_count_for_each_game($page_index, $page_size);
        }
        $median_of_streams = $this->databaseProvider->get_median_number_of_viewers_for_all_streams();
        if($get_top_streams_by_viewer_count == 'true')
        {
            $top_streams = $this->databaseProvider->get_top_streams_by_viewer_count($page_index, $page_size);
        }
        if($get_total_number_of_streams_by_start_time == 'true')
        {
            $total_number_of_streams_by_start_time = $this->databaseProvider->get_total_number_of_streams_by_start_time($page_index, $page_size);
        }
        if ($get_user_followed_streams == 'true') {
            $resolved_data = $user_credentials_and_followed_streams->wait();
            $results = Promise\settle($this->tags_promises)->wait();
            $streams_followed_by_user = $resolved_data['userfollowedstreams'];
            $gameId_of_user_followed_streams = $resolved_data['gameIdofuserfollowedstreams'];
            $top_streams_user_follow = $this->databaseProvider->get_streams_user_follow_from_top_streams($gameId_of_user_followed_streams, $page_index, $page_size);
            $this->lowest_viewer_count_of_logged_in_user = $resolved_data['lowestviewercountofloggedinuser'];
            $number_needed_for_user_lowest_viewer_count_to_be_in_top_streams = $this->databaseProvider->get_number_that_user_lowest_viewer_count_needs_to_be_in_top_stream($this->lowest_viewer_count_of_logged_in_user);
            $user_data = $resolved_data['userdata'];
            $shared_tags = $this->getSharedTags($page_index, $page_size);
            $this->databaseProvider->save_user_data($user_data);
        }
        StreamDatabaseLock::releaseLock();
        $response['numberofstreamspergame'] = $number_of_streams;
        $response['topgamesbyviewercount'] = $top_game_by_viewer_count;
        $response['topstreamsbyviewercount'] = $top_streams;
        $response['topstreamsuserfollow'] = $top_streams_user_follow;
        $response['tagssharedbetweenuserandtopstreams'] = $shared_tags;
        $response['totalnumberofstreamsbystarttime'] = $total_number_of_streams_by_start_time;
        $response['medianofstreams'] = $median_of_streams;
        $response['numberforuserlowestviewtobeintopstream'] = $number_needed_for_user_lowest_viewer_count_to_be_in_top_streams;
        return $response;
    }

    private function getSharedTags($page_index, $page_size)
    {
        $shared_tags_result = array();
        $result = $this->databaseProvider->get_shared_tags_between_user_followed_stream_and_top_stream($this->tag_ids_of_streams_followed_by_user, $page_index, $page_size);
        $shared_tags = array();
        foreach ($result['shared_tags_btw_user_and_top_streams'] as $tag) {
            $tag_details = $this->tags_of_streams_followed_by_user[$tag];
            $shared_tag_details = array();
            $shared_tag_details['tagId'] = $tag;
            $shared_tag_details['localization_name'] = $tag_details['localization_names']['en-us'];
            $shared_tag_details['localization_description'] = $tag_details['localization_descriptions']['en-us'];
            array_push($shared_tags,$shared_tag_details);
        }
        $shared_tags_result['shared_tags_btw_user_and_top_streams'] = $shared_tags;
        $shared_tags_result['total_count'] = $result['total_count'];
        return $shared_tags_result;
    }
}
