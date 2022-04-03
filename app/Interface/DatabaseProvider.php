<?php
namespace App\Interface;

use GuzzleHttp\Promise;
interface DatabaseProvider
{
    public function get_total_number_of_streams_for_each_game($page_index, $page_size);
    public function get_total_number_of_streams_by_start_time($page_index, $page_size);
    public function get_top_games_by_viewer_count_for_each_game($page_index, $page_size);
    public function get_median_number_of_viewers_for_all_streams();
    public function get_streams_user_follow_from_top_streams($gameId_of_streams_followed_by_user, $page_index, $page_size);
    public function get_top_streams_by_viewer_count($page_index, $page_size);
    public function get_number_that_user_lowest_viewer_count_needs_to_be_in_top_stream($lowest_viewer_count);
    public function get_shared_tags_between_user_followed_stream_and_top_stream($tag_ids_of_streams_followed_by_user, $page_index, $page_size);
    public function delete_current_data();
    public function save_new_data();
    public function seed_database($stream);
    public function save_user_data($user_data) : Promise\Promise;
}