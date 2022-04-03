<?php
namespace App\Implementation;

use GuzzleHttp\Promise;

class MongoDBDatabaseProvider implements DatabaseProvider
{
    public function get_total_number_of_streams_for_each_game($page_index, $page_size)
    {
        // To be Implemented
    }
    public function get_total_number_of_streams_by_start_time($page_index, $page_size)
    {
         // To be Implemented
    }
    public function get_top_games_by_viewer_count_for_each_game($page_index, $page_size)
    {
         // To be Implemented

    }
    public function get_median_number_of_viewers_for_all_streams()
    {
         // To be Implemented
    }
    public function get_streams_user_follow_from_top_streams($gameId_of_streams_followed_by_user, $page_index, $page_size)
    {
         // To be Implemented
    }

    public function get_shared_tags_between_user_followed_stream_and_top_stream($tag_ids_of_streams_followed_by_user, $page_index, $page_size)
    {
         // To be Implemented
    }
    public function get_top_streams_by_viewer_count($page_index, $page_size)
    {
         // To be Implemented
    }
    public function get_number_that_user_lowest_viewer_count_needs_to_be_in_top_stream($lowest_viewer_count)
    {
         // To be Implemented
    }
    public function delete_current_data()
    {
         // To be Implemented
    }
    public function save_new_data()
    {
         // To be Implemented
    }
    public function seed_database($stream)
    {
         // To be Implemented
    }
    public function save_user_data($user_data) : Promise
    {
         // To be Implemented
    }
}