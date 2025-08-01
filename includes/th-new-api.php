<?php
namespace Th_Game_Schedule\includes;

use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;


class Th_New_Api {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_api_routes'));
    }
    //api get all schedules
    public function register_api_routes() {
        register_rest_route('th-game-schedule/v1', '/get-schedule-v1', [
            'methods'             => 'GET',
            'callback'            => array($this, 'get_schedule_data_v1'),
            'permission_callback' => '__return_true'
        ]);
        register_rest_route('th-game-schedule/v1', '/get-schedule-v2', [
            'methods'             => 'GET',
            'callback'            => array($this, 'get_schedule_data_v2'),
            'permission_callback' => '__return_true'
        ]);
        
        register_rest_route('th-game-schedule/v1', '/get-contests', [
            'methods' => 'GET',
            'callback' => array($this,'get_filtered_contests'),
            'permission_callback' => '__return_true',
        ]);   

    }
    function get_filtered_contests($request) {
    $keyword = $request->get_param('search'); // URL: ?search=2025

    $args = [
        'post_type' => 'contest_list',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key'     => 'time', // Tên trường ACF
                'value'   => $keyword,
                'compare' => 'LIKE', // So sánh chứa chuỗi
            ],
        ],
    ];

    $query = new WP_Query($args);
    $results = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            $post_id = get_the_ID();
            $fields = get_fields($post_id); // Lấy toàn bộ ACF

            $results[] = [
                'ID'       => $post_id,
                'title'    => get_the_title(),
                'acf'      => $fields,
                'link'     => get_permalink(),
            ];
        }
    }

    wp_reset_postdata();

    return rest_ensure_response([
        'success' => true,
        'count'   => count($results),
        'data'    => $results,
    ]);
}

    public function get_schedule_data_v1() {
        $posts = get_posts([
            'post_type'   => 'contest_list',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby'     => 'meta_value',
            'meta_key'    => 'time',
            'order'       => 'ASC'
        ]);

        if (empty($posts)) {
            return [
                'ErrMsg'      => 'Không có bài contest_list',
                'Successed'   => false,
                'ResponseDto' => []
            ];
        }

        $response = [];
        foreach ($posts as $post) {
            $id = $post->ID;
            $fields = function_exists('get_fields') ? get_fields($id) : [];
            $score_tsg = function_exists('get_field') ? get_field('Score-tsg', $id) : [];
            $score_opp = function_exists('get_field') ? get_field('Score', $id) : [];
            $home = $fields['HOME'] ?? '';
            if (is_array($home) && isset($home['url'])) $home = $home['url'];
            $away = $fields['AWAY'] ?? '';
            if (is_array($away) && isset($away['url'])) $away = $away['url'];
            $response[] = [
                'post_id'          => $id,
                'time'             => $fields['time']     ?? '',
                'location'         => $fields['location'] ?? '',
                'team'             => $fields['team']     ?? [],
                'HOME'             => $home,
                'AWAY'             => $away,
                // 踏鋼點數
                'Score_tsg_1st'    => isset($score_tsg['1st'])   ? (int)$score_tsg['1st']   : 0,
                'Score_tsg_2nd'    => isset($score_tsg['2nd'])   ? (int)$score_tsg['2nd']   : 0,
                'Score_tsg_3rd'    => isset($score_tsg['3rd'])   ? (int)$score_tsg['3rd']   : 0,
                'Score_tsg_4th'    => isset($score_tsg['4th'])   ? (int)$score_tsg['4th']   : 0,
                'Score_tsg_5th'    => isset($score_tsg['5th'])   ? (int)$score_tsg['5th']   : 0,
                'Score_tsg_total'  => isset($score_tsg['total']) ? (int)$score_tsg['total'] : 0,
                // 對手點數
                'Score_1st'        => isset($score_opp['1st'])   ? (int)$score_opp['1st']   : 0,
                'Score_2nd'        => isset($score_opp['2nd'])   ? (int)$score_opp['2nd']   : 0,
                'Score_3rd'        => isset($score_opp['3rd'])   ? (int)$score_opp['3rd']   : 0,
                'Score_4th'        => isset($score_opp['4th'])   ? (int)$score_opp['4th']   : 0,
                'Score_5th'        => isset($score_opp['5th'])   ? (int)$score_opp['5th']   : 0,
                'Score_total'      => isset($score_opp['total']) ? (int)$score_opp['total'] : 0,
            ];
        }
        return [
            'ErrMsg'      => '',
            'Successed'   => true,
            'ResponseDto' => $response
        ];
    }
    
    function get_schedule_data_v2() {
        $posts = get_posts([
            'post_type'   => 'contest_list',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby'     => 'meta_value',
            'meta_key'    => 'time',
            'order'       => 'ASC'
        ]);

        if (empty($posts)) {
            return [
                'ErrMsg' => 'Không có bài contest_list',
                'Successed' => false,
                'ResponseDto' => []
            ];
        }

        $response = [];
        foreach ($posts as $i => $post) {
            $id = $post->ID;
            $f = function_exists('get_fields') ? get_fields($id) : [];
            $score_tsg = get_field('Score-tsg', $id) ?: [];
            $score_opp = get_field('Score', $id) ?: [];

            $home_img = $f['HOME'] ?? '';
            if (is_array($home_img) && isset($home_img['url'])) $home_img = $home_img['url'];

            $away_img = $f['AWAY'] ?? '';
            if (is_array($away_img) && isset($away_img['url'])) $away_img = $away_img['url'];

            // Lấy ngày giờ
            $time = $f['time'] ?? '-';
            $datetime = strtotime($time);
            $game_date = $datetime ? date('Y-m-d', $datetime) : '-';
            $game_month = $datetime ? date('m', $datetime) : '-';
            $game_day = $datetime ? date('d', $datetime) : '-';

            // Tổng điểm từng đội
            $home_total = $score_tsg['total'] ?? 0;
            $away_total = $score_opp['total'] ?? 0;

            // Chi tiết từng hiệp
            $sets = [];
            for ($s = 1; $s <= 5; $s++) {
                $sets[] = [
                    'SetNumber' => $s,
                    'VisitingSetScore' => (int)($score_opp[$s . 'th'] ?? 0),
                    'HomeSetScore' => (int)($score_tsg[$s . 'th'] ?? 0),
                ];
            }

            $response[] = [
                'Seq' => (string)($i + 1),
                'PresentStatus' => 0,
                'IsGameStop' => '-',
                'GameDateTimeS' => $time ?? '-',
                'GameDateTimeE' => '-',
                'GameDuringTime' => '-',
                'MultyGame' => '-',
                'Year' => $datetime ? date('Y', $datetime) : '-',
                'KindCode' => '-',
                'GameSeasonCode' => '-',
                'GameSno' => $id,
                'UpdateTime' => current_time('mysql'),
                'GameDate' => $game_date,
                'GameDateMonth' => $game_month,
                'GameDateDay' => $game_day,
                'GameResult' => $home_total . '-' . $away_total,
                'PreExeDate' => '-',
                'VisitingTeamCode' => '-',
                'VisitingTeamName' => 'AWAY',
                'HomeTeamCode' => '-',
                'HomeTeamName' => 'HOME',
                'FieldNo' => '-',
                'FieldAbbe' => $f['location'] ?? '-',
                'VisitingSetsWon' => $away_total,
                'HomeSetsWon' => $home_total,
                'SetsScoreDetail' => $sets,
                'MvpAcnt' => '-',
                'MvpCount' => 0,
                'VisitingBestPlayerAcnt' => '-',
                'HomeBestPlayerAcnt' => '-',
                'WinningPlayerAcnt' => '-',
                'LoserPlayerAcnt' => '-',
                'AudienceCnt' => 0,
                'VisitingTeamDesc' => '-',
                'HomeTeamDesc' => '-',
                'XweData' => '-',
                'GameResultName' => 'HOME ' . $home_total . ' : ' . $away_total . ' AWAY',
                'VisitingClubSmallImgPath' => $away_img ?: '-',
                'HomeClubSmallImgPath' => $home_img ?: '-',
                'WinningPlayerName' => '-',
                'LoserPlayerName' => '-',
                'MvpName' => '-',
            ];
        }

        return [
            'ErrMsg' => '',
            'Successed' => true,
            'ResponseDto' => $response
        ];
    }
}