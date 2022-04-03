<?php
namespace App\Implementation;

use GuzzleHttp\Promise;
use Illuminate\Support\Facades\DB;
use App\Interface\DatabaseProvider;
use Shared\StreamDatabaseLock;

class MysqlDatabaseProvider implements DatabaseProvider
{
    private function group_streams_by_game()
    {
        $number_of_streams_per_game = array();
        $total_number_of_records = DB::select('SELECT * FROM streams_holding');
        foreach ($total_number_of_records as $record) {
            if (! array_key_exists($record->game_id,$number_of_streams_per_game)) {
                $value = array();
                $value['game_id'] = $record->game_id;
                $value['game_name'] = $record->game_name;
                $value['totalstreams'] = 1;
                $value['viewer_count'] = $record->viewer_count;
                $number_of_streams_per_game[$record->game_id] = $value;
            }
            else {
                $value = $number_of_streams_per_game[$record->game_id];
                $value['totalstreams'] = $value['totalstreams'] + 1;
                $value['viewer_count'] = $value['viewer_count'] + $record->viewer_count;
                $number_of_streams_per_game[$record->game_id] = $value;
            }
        }
        return $number_of_streams_per_game;
    }
    public function get_total_number_of_streams_for_each_game($page_index, $page_size)
    {
        $result = array();
        $number_of_streams_per_game = $this->group_streams_by_game();
        $number_of_streams_per_game_values = array_values($number_of_streams_per_game);
        $total_count = count($number_of_streams_per_game_values);
        sort($number_of_streams_per_game_values);
        $total_number_of_streams_per_game = array_slice($number_of_streams_per_game_values,$page_index,$page_size);
        $result['total_count'] = $total_count;
        $result['total_number_of_streams_per_game'] = $total_number_of_streams_per_game;
        return $result;
    }

    public function get_total_number_of_streams_by_start_time($page_index, $page_size)
    {
        $streams_by_start_time = array();
        $total_number_of_records = DB::select('SELECT date_format(start_time,\'%Y-%m-%d %H\') as starttime, viewer_count FROM streams_holding');
        foreach ($total_number_of_records as $record) {
            if (! array_key_exists($record->starttime,$streams_by_start_time)) {
                $value = array();
                $value['starttime'] = $record->starttime;
                $value['totalstreams'] = 1;
                $value['viewer_count'] = $record->viewer_count;
                $streams_by_start_time[$record->starttime] = $value;
            }
            else {
                $value = $streams_by_start_time[$record->starttime];
                $value['totalstreams'] = $value['totalstreams'] + 1;
                $value['viewer_count'] = $value['viewer_count'] + $record->viewer_count;
                $streams_by_start_time[$record->starttime] = $value;
            }
        }
        $streams_by_start_time_values = array_values($streams_by_start_time);
        $total_count = count($streams_by_start_time_values);
        sort($streams_by_start_time_values);
        $all_streams_by_start_time = array_slice($streams_by_start_time_values,$page_index,$page_size);
        $result['total_count'] = $total_count;
        $result['all_streams_by_start_time'] = $all_streams_by_start_time;
        return $result;
    }

    private function cmp($a, $b) {
        return $b['viewer_count'] - $a['viewer_count'];
     }

    public function get_top_games_by_viewer_count_for_each_game($page_index, $page_size)
    {
        $result = array();
        $number_of_streams_per_game = $this->group_streams_by_game();
        $number_of_streams_per_game_values = array_values($number_of_streams_per_game);
        $total_count = count($number_of_streams_per_game_values);
        usort($number_of_streams_per_game_values,array('App\Implementation\MysqlDatabaseProvider','cmp'));
        $top_games_by_viewer_count_for_each_game = array_slice($number_of_streams_per_game_values,$page_index,$page_size);
        $result['total_count'] = $total_count;
        $result['top_games_by_viewer_count_for_each_game'] = $top_games_by_viewer_count_for_each_game;
        return $result;
    }
    public function get_median_number_of_viewers_for_all_streams()
    {
        $all_viewer_count = DB::select('SELECT viewer_count FROM streams_holding ORDER BY viewer_count DESC');
        $data_length = count($all_viewer_count);
        $middle_index = $data_length/2;
        $median_number_of_viewers_for_all_streams = $all_viewer_count[$middle_index];
        return $median_number_of_viewers_for_all_streams;
    }

    public function get_streams_user_follow_from_top_streams($gameId_of_streams_followed_by_user, $page_index, $page_size)
    {
        $result = array();
        $game_id_query = '';
        foreach ($gameId_of_streams_followed_by_user as $gameId) {
            $game_id_query .= $gameId . ','; 
        }
        $new_game_id_query = rtrim($game_id_query, ", ");
        $streams_user_follow_from_top_streams_records = DB::select('SELECT * FROM (
            SELECT stream_title, game_id, game_name, viewer_count FROM streams_holding
            ORDER BY viewer_count DESC LIMIT 1000 
           ) AS allstreams
           WHERE game_id IN (:gameids)', ['gameids' => $new_game_id_query]);
           $total_count = count($streams_user_follow_from_top_streams_records);
           sort($streams_user_follow_from_top_streams_records);
           $streams_user_follow_from_top_streams = array_slice($streams_user_follow_from_top_streams_records,$page_index,$page_size);
        $result['total_count'] = $total_count;
        $result['streams_user_follow_from_top_streams'] = $streams_user_follow_from_top_streams;
        return $result;
    }

    public function get_shared_tags_between_user_followed_stream_and_top_stream($tag_ids_of_streams_followed_by_user, $page_index, $page_size)
    {
        $result = array();
        $tag_id_query = '';
        foreach ($tag_ids_of_streams_followed_by_user as $tagId) {
            $tag_id_query .= $tagId . ','; 
        }
        $new_tag_id_query = rtrim($tag_id_query, ", ");        
        $shared_tags_btw_user_and_top_streams_records = DB::select('SELECT * FROM (
            SELECT tag_id FROM tags_holding
            LEFT JOIN streams_holding
            ON tags_holding.stream_id = streams_holding.id  
            ORDER BY viewer_count DESC 
            LIMIT 1000
           ) AS tags
           WHERE tag_id IN (:tagids)', ['tagids' => $new_tag_id_query]);
        $total_count = count($shared_tags_btw_user_and_top_streams_records);
        sort($shared_tags_btw_user_and_top_streams_records);
        $shared_tags_btw_user_and_top_streams = array_slice($shared_tags_btw_user_and_top_streams_records,$page_index,$page_size);
     $result['total_count'] = $total_count;
     $result['shared_tags_btw_user_and_top_streams'] = $shared_tags_btw_user_and_top_streams;
     return $result;
    }
    
    public function get_top_streams_by_viewer_count($page_index, $page_size)
    {
        $result = array();
        $top_streams_by_viewer_count_records = DB::select('SELECT stream_title, game_id, game_name, viewer_count 
        FROM streams_holding 
        ORDER BY viewer_count DESC
        LIMIT 1000
        ');
         $total_count = count($top_streams_by_viewer_count_records);
         $top_streams_by_viewer_count = array_slice($top_streams_by_viewer_count_records,$page_index,$page_size);
      $result['total_count'] = $total_count;
      $result['top_streams_by_viewer_count'] = $top_streams_by_viewer_count;
      return $result;
    }

    public function get_number_that_user_lowest_viewer_count_needs_to_be_in_top_stream($lowest_viewer_count)
    {
        $result = 0;
        $top_streams_by_viewer_count = DB::select('SELECT * FROM streams_holding 
        ORDER BY viewer_count DESC 
        LIMIT 1000');
        $total_count = count($top_streams_by_viewer_count);
        $last_index = $total_count - 1;
        $last_element = $top_streams_by_viewer_count[$last_index];
        $viewer_count_of_last_element = $last_element->viewer_count;
        if($viewer_count_of_last_element > $lowest_viewer_count)
        {
            $result = $viewer_count_of_last_element  - $lowest_viewer_count;
        }
        return $result;
    }
    public function save_user_data($user_data) : Promise\Promise
    {
        $promise = new Promise\Promise(
            function () use (&$promise, $user_data) {
                $user_details = DB::select('SELECT * FROM users WHERE TwitchId = :twitchid', ['twitchid' => $user_data['id']]);
                $total_count = count($user_details);
                if ($total_count == 0) {
                    DB::table('streams')->insert([
                        'TwitchId' => $user_data['id'],
                        'Email' => $user_data['email'],
                        'Username' => $user_data['login'],
                    ]);
                }
                $promise->resolve('saved');
            }
        );
        return $promise;
    }
    public function delete_current_data()
    {
        DB::transaction(function (){
            DB::delete('DELETE FROM tags');
            DB::delete('DELETE * FROM streams');
            DB::statement('ALTER TABLE streams AUTO_INCREMENT = 1');
            DB::statement('ALTER TABLE tags AUTO_INCREMENT = 1');
        });
    }

    public function seed_database($stream)
    {
        DB::transaction(function () use ($stream) {
            $id = DB::table('streams')->insertGetId([
                'stream_title' => $stream['title'],
                'game_id' => empty($stream['game_id']) ? 'UNKNOWNGAMEID' : $stream['game_id'],
                'game_name' => empty($stream['game_name']) ? 'UNKNOWNGAMENAME' : $stream['game_name'],
                'viewer_count' => $stream['viewer_count'],
                'start_time' => $stream['started_at'],
                'stream_id' => $stream['id'],
            ]);
            echo nl2br("Inserted Id is " . $id .  "\r\n");
            $tags = $stream['tag_ids'];
            if(!is_null($tags))
            {
                foreach ($tags as $tag_id) {
                   DB::table('tags')->insert([
                        'tag_id' => $tag_id,
                        'stream_id' => $id,
                    ]);
                }
                echo nl2br("Tags Data Inserted \r\n");
            }
            else{
                echo nl2br("Tags Is Null \r\n");
            }
        });
    }
    public function save_new_data()
    {
        DB::transaction(function (){
            DB::delete('DELETE FROM tags_holding');
            DB::delete('DELETE * FROM streams_holding');
            DB::statement('ALTER TABLE streams AUTO_INCREMENT = 1');
            DB::statement('ALTER TABLE tags AUTO_INCREMENT = 1');
            DB::insert('INSERT INTO streams_holding (Id, stream_title, game_id, game_name, viewer_count, start_time)
            SELECT Id, stream_title, game_id, game_name, viewer_count, start_time FROM streams');
            DB::insert('INSERT INTO tags_holding (Id, tag_id, stream_id)
            SELECT Id, tag_id, stream_id FROM tags');
        });
    }
}