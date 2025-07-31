<?php

namespace Th_Game_Schedule\includes;

use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;

/**
 * 球隊相關的 API，負責提供球隊的統計數據
 */
class Th_Game_Api
{
    /**
     * 建構函式，初始化 REST API 端點
     */
    public function __construct()
    {
        // 註冊 REST API 端點
        add_action('rest_api_init', array($this, 'register_team_stats_routes'));
    }

    /**
     * 註冊球隊數據 API 端點
     */
    public function register_team_stats_routes()
    {
        register_rest_route('th_game/v1', '/stats', array(
            'methods'  => 'GET', // 允許使用 GET 方法存取 API
            'callback' => array($this, 'get_team_stats'), // 指定處理請求的函式
            'permission_callback' => '__return_true' // 允許所有人存取 API
	));
	// 取得賽程表資料
        register_rest_route('th_game/v1', '/GetSchedule', array(
            'methods'  => 'GET', // 允許使用 GET 方法存取 API
            'callback' => array($this, 'get_schedule'), // 指定處理請求的函式
            'permission_callback' => '__return_true' // 允許所有人存取 API
	));
	// 取得球員異動
        register_rest_route('th_game/v1', '/GetTeamTrans', array(
            'methods'  => 'GET', // 允許使用 GET 方法存取 API
            'callback' => array($this, 'get_team_trans'), // 指定處理請求的函式
            'permission_callback' => '__return_true' // 允許所有人存取 API
	));
	// 取得球隊戰績資訊
        register_rest_route('th_game/v1', '/GetTeamRecord', array(
            'methods'  => 'GET', // 允許使用 GET 方法存取 API
            'callback' => array($this, 'get_team_record'), // 指定處理請求的函式
            'permission_callback' => '__return_true' // 允許所有人存取 API
        ));
        // 取得球員基本資料
        register_rest_route('th_game/v1', '/GetPersonnel', array(
            'methods'  => 'GET', // 允許使用 GET 方法存取 API
            'callback' => array($this, 'get_personnel'), // 指定處理請求的函式
            'permission_callback' => '__return_true' // 允許所有人存取 API
        ));
        // 取得球隊選手
        register_rest_route('th_game/v1', '/GetTeamMembers', array(
            'methods'  => 'GET', // 允許使用 GET 方法存取 API
            'callback' => array($this, 'get_team_members'), // 指定處理請求的函式
            'permission_callback' => '__return_true' // 允許所有人存取 API
        ));
        // 取得投手成績
        register_rest_route('th_game/v1', '/GetPitcherData', array(
            'methods'  => 'GET', // 允許使用 GET 方法存取 API
            'callback' => array($this, 'get_pitcher_data'), // 指定處理請求的函式
            'permission_callback' => '__return_true' // 允許所有人存取 API
        ));
        // 取得打擊成績
        register_rest_route('th_game/v1', '/GetHitterData', array(
            'methods'  => 'GET', // 允許使用 GET 方法存取 API
            'callback' => array($this, 'get_hitter_data'), // 指定處理請求的函式
            'permission_callback' => '__return_true' // 允許所有人存取 API
	));
        // 取得記分板資訊
        register_rest_route('th_game/v1', '/GetScoreBoard', array(
            'methods'  => 'GET', // 允許使用 GET 方法存取 API
            'callback' => array($this, 'get_score_board'), // 指定處理請求的函式
            'permission_callback' => '__return_true' // 允許所有人存取 API
        ));
        // 取得球隊教練
        register_rest_route('th_game/v1', '/GetTeamCoach', array(
            'methods'  => 'GET', // 允許使用 GET 方法存取 API
            'callback' => array($this, 'get_team_coach'), // 指定處理請求的函式
            'permission_callback' => '__return_true' // 允許所有人存取 API
        ));
        register_rest_route('th_game/v1', '/test', array(
            'methods'  => 'GET', // 允許使用 GET 方法存取 API
            'callback' => array($this, 'get_team_test'), // 指定處理請求的函式
            'permission_callback' => '__return_true' // 允許所有人存取 API
        ));
    }

    /**
     * 取得球隊統計數據
     *
     * @param WP_REST_Request $request - API 請求對象
     * @return WP_REST_Response|WP_Error - 回傳球隊統計數據或錯誤訊息
     */
    public function get_team_stats(WP_REST_Request $request)
    {
        // 從 API 請求中獲取 'type' 參數 (team_standing_d, pitcher_stats_d, batter_stats_d)
        $option_name = $request->get_param('type');

        // 確保請求的數據類型正確
        if (!in_array($option_name, ['team_standing_d', 'pitcher_stats_d', 'batter_stats_d', 'team_standing_d_h1', 'team_standing_d_h2'])) {
            return new WP_Error('invalid_type', 'Invalid stats type', array('status' => 400));
        }

        // 從 WordPress 資料庫中取得對應的數據
        $stats_option = get_option($option_name);

        if ($stats_option) {
            // 反序列化數據，轉換為 PHP 陣列格式
            $stats_option = unserialize($stats_option);
            return rest_ensure_response($stats_option); // 這裡回傳 WP_REST_Response
        } else {
            // 若找不到數據，回傳 404 錯誤
            return new WP_Error('not_found', 'No stats found', array('status' => 404));
        }
    }

    /**
     * 取得賽程表資料
     *
     * @param WP_REST_Request $request - API 請求對象
     * @return WP_REST_Response|WP_Error - 回傳數據或錯誤訊息
     */
    // public function get_schedule(WP_REST_Request $request)
    // {
    //     // 取得請求參數
    //     $year = $request->get_param('year');
    //     $kind_code = $request->get_param('kindCode');
    //     $game_date = $request->get_param('gameDate');

    //     // 檢查必填參數 year 是否存在
    //     if (empty($year)) {
    //         return new WP_Error('missing_year', 'Year is a required parameter', ['status' => 400]);
    //     }

    //     // 若沒有提供 kind_code，則預設為空字串
    //     $kind_code = $kind_code ?: '';

    //     // 若沒有提供 game_date，則預設為空字串
    //     $game_date = $game_date ?: '';

    //     $url = "https://statsapi.cpbl.com.tw/Api/Record/GetSchedule?year=" . $year;
    //     if (!empty($kind_code)) {
    //         $url .= "&kindCode=" . $kind_code;
    //     }
    //     if (!empty($game_date)) {
    //         $url .= "&gameDate=" . $game_date;
    //     }

    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, $url);
    //     curl_setopt($ch, CURLOPT_POST, true);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    //     //暫時不檢查SSL
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    //     $response_data = curl_exec($ch);
    //     curl_close($ch);
    //     $response_data = json_decode($response_data, true);

    //     if ($response_data) {
    //         return new WP_REST_Response($response_data, 200);
    //     } else {
    //         return new WP_Error('not_found', 'No stats found', array('status' => 404));
    //     }
    // }

    // new function to get schedule data
    public function get_schedule(WP_REST_Request $request)
    {
        $game_date = $request->get_param('gameDate') ?: '';
        $kind_code = $request->get_param('kindCode') ?: '';

        // Lấy danh sách post contest_list, lọc theo ngày nếu có
        $args = [
            'post_type' => 'contest_list',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'meta_value',
            'meta_key' => 'time',
            'order' => 'ASC'
        ];

        // Nếu có truyền ngày thì lọc các post có chứa ngày đó
        if (!empty($game_date)) {
            $args['meta_query'] = [
                [
                    'key' => 'time',
                    'value' => $game_date,
                    'compare' => 'LIKE'
                ]
            ];
        }

        $posts = get_posts($args);

        if (empty($posts)) {
            return new WP_REST_Response([
                'ErrMsg' => 'Không tìm thấy trận đấu nào',
                'Successed' => false,
                'ResponseDto' => []
            ], 200);
        }

        $response = [];

        foreach ($posts as $post) {
            $id = $post->ID;
            $f = function_exists('get_fields') ? get_fields($id) : [];

            $home_img = isset($f['HOME']['url']) ? $f['HOME']['url'] : '-';
            $away_img = isset($f['AWAY']['url']) ? $f['AWAY']['url'] : '-';
            $time = $f['time'] ?? '-';

            // Lấy ngày từ chuỗi time (dạng yyyy/mm/dd (sat) 18:00)
            $game_date_value = '-';
            $game_datetime = '-';
            if ($time && $time !== '-') {
                $parts = explode(' ', $time);
                $game_date_value = isset($parts[0]) ? str_replace('/', '-', $parts[0]) : '-';
                $game_datetime = $time;
            }

            $score_tsg = $f['Score-tsg'] ?? [];
            $score_opp = $f['Score'] ?? [];

            $response[] = [
                'post_id' => $id,
                'GameDateTimeS' => $game_datetime,
                'GameSno' => $id,
                'GameDate' => $game_date_value,
                'GameResult' => ($score_tsg['total'] ?? 0) . '-' . ($score_opp['total'] ?? 0),
                'VisitingTeamCode' => '-', // nếu có sẽ thêm sau
                'VisitingTeamName' => 'AWAY',
                'HomeTeamCode' => '-',
                'HomeTeamName' => 'HOME',
                'FieldAbbe' => $f['location'] ?? '-',
                'VisitingScore' => (int)($score_opp['total'] ?? 0),
                'HomeScore' => (int)($score_tsg['total'] ?? 0),

                'HOME' => $home_img,
                'AWAY' => $away_img,

                'Score_tsg_1st' => (int)($score_tsg['1st'] ?? 0),
                'Score_tsg_2nd' => (int)($score_tsg['2nd'] ?? 0),
                'Score_tsg_3rd' => (int)($score_tsg['3rd'] ?? 0),
                'Score_tsg_4th' => (int)($score_tsg['4th'] ?? 0),
                'Score_tsg_5th' => (int)($score_tsg['5th'] ?? 0),
                'Score_tsg_total' => (int)($score_tsg['total'] ?? 0),

                'Score_1st' => (int)($score_opp['1st'] ?? 0),
                'Score_2nd' => (int)($score_opp['2nd'] ?? 0),
                'Score_3rd' => (int)($score_opp['3rd'] ?? 0),
                'Score_4th' => (int)($score_opp['4th'] ?? 0),
                'Score_5th' => (int)($score_opp['5th'] ?? 0),
                'Score_total' => (int)($score_opp['total'] ?? 0),
            ];
        }

        return new WP_REST_Response([
            'ErrMsg' => '',
            'Successed' => true,
            'ResponseDto' => $response
        ], 200);
    }

    /**
     * 取得球員異動
     *
     * @param WP_REST_Request $request - API 請求對象
     * @return WP_REST_Response|WP_Error - 回傳數據或錯誤訊息
     */
    public function get_team_trans(WP_REST_Request $request)
    {
        // 取得請求參數
        $year = $request->get_param('year');
        $club_no = $request->get_param('clubNo');

        // 檢查必填參數 year 是否存在
        if (empty($year)) {
            return new WP_Error('missing_year', 'Year is a required parameter', ['status' => 400]);
        }
        // 檢查必填參數 club_no 是否存在
        if (empty($club_no)) {
            return new WP_Error('missing_club_no', 'ClubNo is a required parameter', ['status' => 400]);
        }

        $url = "https://statsapi.cpbl.com.tw/Api/Record/GetTeamTrans?year=" . $year . "&clubNo=" . $club_no;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //暫時不檢查SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $response_data = curl_exec($ch);
        curl_close($ch);
        $response_data = json_decode($response_data, true);

        if ($response_data) {
            return new WP_REST_Response($response_data, 200);
        } else {
            return new WP_Error('not_found', 'No stats found', array('status' => 404));
        }
    }

    /**
     * 取得球隊戰績資訊
     *
     * @param WP_REST_Request $request - API 請求對象
     * @return WP_REST_Response|WP_Error - 回傳數據或錯誤訊息
     */
    public function get_team_record(WP_REST_Request $request)
    {
        // 取得請求參數
        $year = $request->get_param('year');
        $kind_code = $request->get_param('kindCode');
        $season_code = $request->get_param('seasonCode');

        // 檢查必填參數 year 是否存在
        if (empty($year)) {
            return new WP_Error('missing_year', 'Year is a required parameter', ['status' => 400]);
        }
        // 檢查必填參數 kind_code 是否存在
        if (empty($kind_code)) {
            return new WP_Error('missing_kind_code', 'KindCode is a required parameter', ['status' => 400]);
        }
        // 檢查必填參數 season_code 是否存在
        if (empty($season_code) && $season_code != 0) {
            return new WP_Error('missing_season_code', 'SeasonCode is a required parameter', ['status' => 400]);
        }

        $url = "https://statsapi.cpbl.com.tw/Api/Record/GetTeamRecord?year=" . $year . "&kindCode=" . $kind_code . "&seasonCode=" . $season_code;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //暫時不檢查SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $response_data = curl_exec($ch);
        curl_close($ch);
        $response_data = json_decode($response_data, true);

        if ($response_data) {
            return new WP_REST_Response($response_data, 200);
        } else {
            return new WP_Error('not_found', 'No stats found', array('status' => 404));
        }
    }

    /**
     * 取得球員基本資料
     *
     * @param WP_REST_Request $request - API 請求對象
     * @return WP_REST_Response|WP_Error - 回傳數據或錯誤訊息
     */
    public function get_personnel(WP_REST_Request $request)
    {
        // 取得請求參數
        $acnt = $request->get_param('acnt');

        // 檢查必填參數 acnt 是否存在
        if (empty($acnt)) {
            return new WP_Error('missing_acnt', 'Acnt is a required parameter', ['status' => 400]);
        }

        $url = "https://statsapi.cpbl.com.tw/Api/Record/GetPersonnel?acnt=" . $acnt;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //暫時不檢查SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $response_data = curl_exec($ch);
        curl_close($ch);
        $response_data = json_decode($response_data, true);

        if ($response_data['Successed']) {
            // 收集所有球員的 UniformNo
            $uniform_nos = array_filter(array_column($response_data['ResponseDto'], 'UniformNo'));

            // 查詢所有球員圖片（如果有背號）
            $player_images = !empty($uniform_nos) ? $this->query_roster_list_images($uniform_nos) : [];

            // 將圖片資料加入每位球員資訊
            foreach ($response_data['ResponseDto'] as &$player) {
                $player['PlayerImage'] = $player_images[$player['UniformNo']] ?? '';
            }
	}

        if ($response_data) {
            return new WP_REST_Response($response_data, 200);
        } else {
            return new WP_Error('not_found', 'No stats found', array('status' => 404));
        }
    }

    /**
     * 取得球隊選手
     *
     * @param WP_REST_Request $request - API 請求對象
     * @return WP_REST_Response|WP_Error - 回傳數據或錯誤訊息
     */
    public function get_team_members(WP_REST_Request $request)
    {
        // 取得請求參數
        $year = $request->get_param('year');
        $kind_code = $request->get_param('kindCode');
        $team_no = $request->get_param('teamNo');

        // 檢查必填參數 year 是否存在
        if (empty($year)) {
            return new WP_Error('missing_year', 'Year is a required parameter', ['status' => 400]);
        }
        // 檢查必填參數 kind_code 是否存在
        if (empty($kind_code)) {
            return new WP_Error('missing_kind_code', 'KindCode is a required parameter', ['status' => 400]);
        }
        // 檢查必填參數 team_no 是否存在
        if (empty($team_no)) {
            return new WP_Error('missing_team_no', 'TeamNo is a required parameter', ['status' => 400]);
        }

        $url = "https://statsapi.cpbl.com.tw/Api/Record/GetTeamMembers?year=" . $year . "&kindCode=" . $kind_code . "&teamNo=" . $team_no;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //暫時不檢查SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $response_data = curl_exec($ch);
        curl_close($ch);
        $response_data = json_decode($response_data, true);

        if ($response_data['Successed']) {
            $unique_players = [];

            // 過濾掉 Acnt 重複的球員
            foreach ($response_data['ResponseDto'] as $player) {
                if (!isset($unique_players[$player['Acnt']])) {
                    $unique_players[$player['Acnt']] = $player;
                }
            }

            // 重新整理 ResponseDto，確保沒有重複的 Acnt
	    $response_data['ResponseDto'] = array_values($unique_players);

            // 收集所有球員的 UniformNo
            $uniform_nos = array_filter(array_column($response_data['ResponseDto'], 'UniformNo'));

            // 查詢所有球員圖片（如果有背號）
            $player_images = !empty($uniform_nos) ? $this->query_roster_list_images($uniform_nos) : [];

            // 將圖片資料加入每位球員資訊
            foreach ($response_data['ResponseDto'] as &$player) {
                $player['PlayerImage'] = $player_images[$player['UniformNo']] ?? '';
            }
        }

        if ($response_data) {
            return new WP_REST_Response($response_data, 200);
        } else {
            return new WP_Error('not_found', 'No stats found', array('status' => 404));
        }
    }

    /**
     * 取得投手成績
     *
     * @param WP_REST_Request $request - API 請求對象
     * @return WP_REST_Response|WP_Error - 回傳數據或錯誤訊息
     */
    public function get_pitcher_data(WP_REST_Request $request)
    {
        // 取得請求參數
        $acnt = $request->get_param('acnt');
        $year = $request->get_param('year');
        $kind_code = $request->get_param('kindCode');

        // 檢查必填參數 acnt 是否存在
        if (empty($acnt)) {
            return new WP_Error('missing_acnt', 'Acnt is a required parameter', ['status' => 400]);
        }

        // 若沒有提供 year，則預設為空字串
        $year = $year ?: '';

        // 若沒有提供 kind_code，則預設為空字串
        $kind_code = $kind_code ?: '';

        $url = "https://statsapi.cpbl.com.tw/Api/Record/GetPitcherData?acnt=" . $acnt;
        if (!empty($year)) {
            $url .= "&year=" . $year;
        }
        if (!empty($kind_code)) {
            $url .= "&kindCode=" . $kind_code;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //暫時不檢查SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $response_data = curl_exec($ch);
        curl_close($ch);
        $response_data = json_decode($response_data, true);

        if ($response_data) {
            return new WP_REST_Response($response_data, 200);
        } else {
            return new WP_Error('not_found', 'No stats found', array('status' => 404));
        }
    }

    /**
     * 取得打擊成績
     *
     * @param WP_REST_Request $request - API 請求對象
     * @return WP_REST_Response|WP_Error - 回傳數據或錯誤訊息
     */
    public function get_hitter_data(WP_REST_Request $request)
    {
        // 取得請求參數
        $acnt = $request->get_param('acnt');
        $year = $request->get_param('year');
        $kind_code = $request->get_param('kindCode');

        // 檢查必填參數 acnt 是否存在
        if (empty($acnt)) {
            return new WP_Error('missing_acnt', 'Acnt is a required parameter', ['status' => 400]);
        }

        // 若沒有提供 year，則預設為空字串
        $year = $year ?: '';

        // 若沒有提供 kind_code，則預設為空字串
        $kind_code = $kind_code ?: '';

        $url = "https://statsapi.cpbl.com.tw/Api/Record/GetHitterData?acnt=" . $acnt;
        if (!empty($year)) {
            $url .= "&year=" . $year;
        }
        if (!empty($kind_code)) {
            $url .= "&kindCode=" . $kind_code;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //暫時不檢查SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $response_data = curl_exec($ch);
        curl_close($ch);
        $response_data = json_decode($response_data, true);

        if ($response_data) {
            return new WP_REST_Response($response_data, 200);
        } else {
            return new WP_Error('not_found', 'No stats found', array('status' => 404));
        }
    }

    /**
     * 取得記分板資訊
     *
     * @param WP_REST_Request $request - API 請求對象
     * @return WP_REST_Response|WP_Error - 回傳數據或錯誤訊息
     */
    public function get_score_board(WP_REST_Request $request)
    {
        // 取得請求參數
        $year = $request->get_param('year');
        $kind_code = $request->get_param('kindCode');
        $game_sno = $request->get_param('gameSno');

        // 檢查必填參數 year 是否存在
        if (empty($year)) {
            return new WP_Error('missing_year', 'Year is a required parameter', ['status' => 400]);
        }
        // 檢查必填參數 kind_code 是否存在
        if (empty($kind_code)) {
            return new WP_Error('missing_kind_code', 'KindCode is a required parameter', ['status' => 400]);
        }
        // 檢查必填參數 game_sno 是否存在
        if (empty($game_sno)) {
            return new WP_Error('missing_game_sno', 'GameSno is a required parameter', ['status' => 400]);
        }

        $url = "https://statsapi.cpbl.com.tw/Api/Record/GetScoreBoard?year=" . $year . "&kindCode=" . $kind_code . "&gameSno=" . $game_sno;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //暫時不檢查SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $response_data = curl_exec($ch);
        curl_close($ch);
        $response_data = json_decode($response_data, true);

        if ($response_data) {
            return new WP_REST_Response($response_data, 200);
        } else {
            return new WP_Error('not_found', 'No stats found', array('status' => 404));
        }
    }

    /**
     * 取得球隊教練
     *
     * @param WP_REST_Request $request - API 請求對象
     * @return WP_REST_Response|WP_Error - 回傳數據或錯誤訊息
     */
    public function get_team_coach(WP_REST_Request $request)
    {
        // 取得請求參數
        $year = $request->get_param('year');
        $kind_code = $request->get_param('kindCode');
        $team_no = $request->get_param('teamNo');

        // 檢查必填參數 year 是否存在
        if (empty($year)) {
            return new WP_Error('missing_year', 'Year is a required parameter', ['status' => 400]);
        }
        // 檢查必填參數 kind_code 是否存在
        if (empty($kind_code)) {
            return new WP_Error('missing_kind_code', 'KindCode is a required parameter', ['status' => 400]);
        }
        // 檢查必填參數 team_no 是否存在
        if (empty($team_no)) {
            return new WP_Error('missing_team_no', 'TeamNo is a required parameter', ['status' => 400]);
        }

        $url = "https://statsapi.cpbl.com.tw/Api/Record/GetTeamCoach?year=" . $year . "&kindCode=" . $kind_code . "&teamNo=" . $team_no;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //暫時不檢查SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $response_data = curl_exec($ch);
        curl_close($ch);
        $response_data = json_decode($response_data, true);

        if ($response_data['Successed']) {
            // 收集所有教練的 UniformNo
            $uniform_nos = array_filter(array_column($response_data['ResponseDto'], 'UniformNo'));

            // 查詢所有教練圖片（如果有背號）
            $player_images = !empty($uniform_nos) ? $this->query_coach_list_images($uniform_nos) : [];

            // 將圖片資料加入每位教練資訊
            foreach ($response_data['ResponseDto'] as &$player) {
                $player['PlayerImage'] = $player_images[$player['UniformNo']] ?? '';
            }
        }

        if ($response_data) {
            return new WP_REST_Response($response_data, 200);
        } else {
            return new WP_Error('not_found', 'No stats found', array('status' => 404));
        }
    }

    /**
     * 取得球隊統計數據
     *
     * @param WP_REST_Request $request - API 請求對象
     * @return WP_REST_Response|WP_Error - 回傳球隊統計數據或錯誤訊息
     */
    public function get_team_test(WP_REST_Request $request)
    {
        $year = 2024;
        $kindCode = "A";
        $season_code = "0";
        $url = "https://statsapi.cpbl.com.tw/Api/Record/GetTeamRecord?year=" . $year . "&kindCode=" . $kindCode . "&seasonCode=" . $season_code;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //暫時不檢查SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $response_data = curl_exec($ch);
        curl_close($ch);
        $response_data = json_decode($response_data, true);

        if ($response_data['Successed']) {
            return new WP_REST_Response($response_data['ResponseDto'], 200);
        } else {
            return new WP_Error('not_found', 'No stats found', array('status' => 404));
        }
    }

    /**
     * 以背號查詢 post_type = roster_list 取得球員圖片
     *
     * @param string $uniform_nos 球員背號
     * @return string 球員照片 URL，若無則回傳空字串
     */
    private function query_roster_list_images($uniform_nos)
    {
        if (empty($uniform_nos)) {
            return [];
        }

        // 直接查詢所有指定背號的球員
        $args = array(
            'posts_per_page' => -1,  // 查詢所有匹配的球員
            'post_type'      => 'roster_list',
            'meta_query'     => array(
                array(
                    'key'     => 'number',
                    'value'   => $uniform_nos,
                    'compare' => 'IN',  // 一次查詢多個球員背號
                )
            )
        );

        $posts = get_posts($args);
        $images = [];

        foreach ($posts as $post) {
            $uniform_no = get_post_meta($post->ID, 'number', true);
            if ($uniform_no && has_post_thumbnail($post->ID)) {
                $images[$uniform_no] = get_the_post_thumbnail_url($post->ID, 'medium');
            }
        }

        return $images;
    }

    /**
     * 以背號查詢 post_type = coach_list 取得球員圖片
     *
     * @param string $uniform_nos 球員背號
     * @return string 球員照片 URL，若無則回傳空字串
     */
    private function query_coach_list_images($uniform_nos)
    {
        if (empty($uniform_nos)) {
            return [];
        }

        // 直接查詢所有指定背號的球員
        $args = array(
            'posts_per_page' => -1,  // 查詢所有匹配的球員
            'post_type'      => 'coach_list',
            'meta_query'     => array(
                array(
                    'key'     => 'number',
                    'value'   => $uniform_nos,
                    'compare' => 'IN',  // 一次查詢多個球員背號
                )
            )
        );

        $posts = get_posts($args);
        $images = [];

        foreach ($posts as $post) {
            $uniform_no = get_post_meta($post->ID, 'number', true);
            if ($uniform_no && has_post_thumbnail($post->ID)) {
                $images[$uniform_no] = get_the_post_thumbnail_url($post->ID, 'medium');
            }
        }

        return $images;
    }
}

