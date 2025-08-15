<?php

// namespace Th_Game_Schedule\includes;

// use WP_Error;
// use WP_Query;
// use WP_REST_Request;
// use WP_REST_Response;

namespace Th_Game_Schedule\includes;

use WP_REST_Request;
use WP_REST_Response;
use DateTime;
use DateTimeZone;
use WP_Query;
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
         add_action('acf/save_post', [$this, 'auto_calculate_volleyball_score_total'], 20);
        // Thêm hook mới để xử lý trường 'time'
        add_action('acf/save_post', [$this, 'auto_add_year_to_time'], 10);
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
    //AD New code
// new function to get schedule data
    public function get_schedule(WP_REST_Request $request) {

        $year  = $request->get_param('year');
        $month = $request->get_param('month');
        $day   = $request->get_param('day');

        $current_year = date('Y');
        $query_year = $current_year; // Năm mặc định đặt cho trường nhập thiếu năm - 默認年份
        // Nếu không có指定年份，使用當前年份

        if (!$year) {
            return array(
                "ErrMsg" => "Missing parameter: year",
                "Successed" => false,
                "ResponseDto" => []
            );
        }
        // 
        $args = array(
            'post_type'      => 'contest_list', 
            'posts_per_page' => -1,
            'orderby'        => 'meta_value',
            'meta_key'       => 'time', 
            'order'          => 'ASC',
        );

        // Tạo mẫu chuỗi cần kiểm tra - 創建要檢查的字符串模式
        $search_pattern = "$year";
        if ($month) {
            $search_pattern .= '/' . str_pad($month, 2, '0', STR_PAD_LEFT);
        }
        if ($day) {
            $search_pattern .= '/' . str_pad($day, 2, '0', STR_PAD_LEFT);
        }
        // Truy vấn - 查詢
        $query = new WP_Query($args);
        $posts = $query->posts;

        if (empty($posts)) {
            return [
                'ErrMsg'      => 'Không tìm thấy bài viết nào phù hợp.',
                'Successed'   => false,
                'ResponseDto' => []
            ];
        }

        $response = [];
        
        foreach ($posts as $i => $post) {
            $id = $post->ID;
            $f = function_exists('get_fields') ? get_fields($id) : [];

            $raw_time = $f['time'] ?? '';
            if (strpos($raw_time, $search_pattern) !== 0) {
                continue; // không khớp ngày => bỏ qua
            }

            $timeS = $this->convert_to_iso_datetime($raw_time, $query_year);
            $datetime = $timeS ? strtotime($timeS) : false;
            $week_from_time = $this->extract_weekday($raw_time);

        // ... (Phần xử lý dữ liệu còn lại giống như phiên bản trước) ...
            $score_tsg = get_field('Score-tsg', $id) ?: [];
            $score_opp = get_field('Score', $id) ?: [];

        // GET team names from post title
            $home_team = '';
            $visiting_team = '';
            $title = $post->post_title;
            if (preg_match('/\d{1,2}\/\d{1,2}\s+(.*?)\s+VS\s+(.*)/ui', $title, $matches)) {
                $home_team = trim($matches[2]);
                $visiting_team = trim($matches[1]);
            }

        // Get team images from ACF fields
        // auto by ACF fields
            $home_img = $f['HOME'] ?? '';
            if (is_array($home_img) && isset($home_img['url'])) {
                $home_img = $home_img['url'];
            }
            $away_img = $f['AWAY'] ?? '';
            if (is_array($away_img) && isset($away_img['url'])) {
                $away_img = $away_img['url'];
            }
        // Determine if 天鷹 is home or away
            $tsg_is_home = $f['team'] ?? ''; // 'Home' or 'Away'

            if ($tsg_is_home === 'AWAY') {
                // 天鷹是客場
                
                $home_score_totol = (int)($score_opp['total'] ?? 0);
                $visiting_score_totol = (int)($score_tsg['total'] ?? 0);
                $sets = [];
                foreach (['1st', '2nd', '3rd', '4th', '5th'] as $set_key) {
                    $sets[] = [
                        'SetNumber'        => str_replace(['st', 'nd', 'rd', 'th'], '', $set_key),
                        'VisitingSetScore' => (int)($score_tsg[$set_key] ?? 0),
                        'HomeSetScore'     => (int)($score_opp[$set_key] ?? 0)
                    ];
                }
            } else {
                // 天鷹是主場                
                $home_score_totol = (int)($score_tsg['total'] ?? 0);
                $visiting_score_totol = (int)($score_opp['total'] ?? 0);
                $sets = [];
                foreach (['1st', '2nd', '3rd', '4th', '5th'] as $set_key) {
                    $sets[] = [
                        'SetNumber'        => str_replace(['st', 'nd', 'rd', 'th'], '', $set_key),
                        'VisitingSetScore' => (int)($score_opp[$set_key] ?? 0),
                        'HomeSetScore'     => (int)($score_tsg[$set_key] ?? 0),
                    ];
                }
            }

            // Time and date formatting
            $game_date_formatted = $datetime ? date('Y-m-d', $datetime) : '-';
            $game_month = $datetime ? date('m', $datetime) : '-';
            $game_day = $datetime ? date('d', $datetime) : '-';
            $game_year = $datetime ? date('Y', $datetime) : '';

            $GameSno = $f['gamesNo'] ?? '-';
            $game_staus_default = $f['gameStatus']; // 9: VS 未開始, 0: 結束, 1: 延期, 2: 保留, 3: 比賽中, 4 : 取消
            $weekdays = date('N', $datetime); // 1 (Thứ Hai) tới 7 (Chủ nhật)

            $result_data = $this->evaluate_volleyball_game_result($score_tsg, $score_opp, $timeS,$tsg_is_home);
            $response[] = [
                'Seq'                    => (string)($i + 1),
                'Game_title'             => $post->post_title,
                'PresentStatus'          => $game_staus_default?? $result_data['GameStatus_auto_count_by_score']?? 9, // 9: 未開始, 0: 結束, 1: 延期, 2: 保留, 3: 比賽中, 4 : 取消
                'GameDateTimeS'          => $timeS ?: '',
                'GameDateTimeE'          => null,
                'GameDuringTime'         => null,
                'GameWeekday'            => $week_from_time?$week_from_time: $weekdays, // 1 (Thứ Hai) tới 7 (Chủ nhật)
                'Year'                   => $game_year,
                // 'KindCode'               => '-',
                // 'GameSeasonCode'         => '-',
                'GameSno'                => $GameSno,
                // 'UpdateTime'             => current_time('mysql'),
                'GameDate'               => $game_date_formatted,
                'GameDateMonth'          => $game_month,
                'GameDateDay'            => $game_day,
                'GameResult'             => $result_data['HomeSetsWon'] . '-' . $result_data['AwaySetsWon'],
                // 'PreExeDate'             => '-',
                'HomeTeamCode'           => '-',
                'HomeTeamName'           => $home_team,
                'VisitingTeamCode'       => '-',
                'VisitingTeamName'       => $visiting_team,
                'FieldNo'                => '-',
                'FieldAbbe'              => $f['location'] ?? '-',
                'VisitingWonScore'       => $away_total,
                'HomeWonScore'           => $home_total,
                'SetsScoreDetail'        => $sets,
                'GameResultName'         => $result_data['GameResultName'],
                'HomeSetsWon'            => $result_data['HomeSetsWon'],
                'VisitingSetsWon'        => $result_data['AwaySetsWon'],
                'WinningTeam'            => $result_data['WinningTeam'],
                'LoserTeam'              => $result_data['LosingTeam'],
                'VisitingClubSmallImgPath' => $away_img ?: '-',
                'HomeClubSmallImgPath'   => $home_img ?: '-',
            ];
        }
            usort($response, function($a, $b) {
            $timeA = strtotime($a['GameDateTimeS']);
            $timeB = strtotime($b['GameDateTimeS']);
            
            // So sánh thời gian
            if ($timeA === $timeB) {
                return 0;
            }
            return ($timeA < $timeB) ? -1 : 1;
        });

        return new WP_REST_Response([
            'ErrMsg'      => '',
            'Successed'   => true,
            'ResponseDto' => $response
        ], 200);
    }
    // chinese weekday helper function - 中文星期幫助函數
    function get_chinese_weekday($timestamp) {
        $weekdays = ['日', '一', '二', '三', '四', '五', '六'];
        return $weekdays[date('w', $timestamp)];
    }
    // Hàm helper đã được cập nhật để nhận tham số $year - helper function updated to accept $year parameter
    // Chuyển đổi định dạng ngày giờ sang ISO 8601 - 將日期時間轉換為 ISO 8601 格式
    private function convert_to_iso_datetime($input, $default_year) {
        // Regex sẽ khớp với cả chuỗi có năm và không có năm - 正規表達式將匹配包含年份和不包含年份的字符串
        if (preg_match('/^(?:(\d{4})\/)?(\d{1,2})\/(\d{1,2})\s*\([^\)]+\)\s*(\d{1,2}):(\d{2})$/u', $input, $matches)) {
            // Nếu chuỗi có năm, lấy năm đó. Ngược lại, dùng năm mặc định.
            // 如果字符串包含年份，則使用該年份，否則使用默認年份。
            $year  = !empty($matches[1]) ? $matches[1] : $default_year;
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $day   = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
            $hour  = str_pad($matches[4], 2, '0', STR_PAD_LEFT);
            $min   = str_pad($matches[5], 2, '0', STR_PAD_LEFT);
            
            return "{$year}-{$month}-{$day}T{$hour}:{$min}:00";
        }
        return null;
    }
    
    // Tự động tính toán tổng điểm bóng chuyền - 自動計算排球總分
    public function auto_calculate_volleyball_score_total($post_id) {
        if (get_post_type($post_id) !== 'contest_list') {
            return;
        }

        $score_tsg = get_field('Score-tsg', $post_id);
        if (is_array($score_tsg) && (!isset($score_tsg['total']) || empty($score_tsg['total']))) {
            $sum_tsg = 0;
            foreach (['1st', '2nd', '3rd', '4th', '5th'] as $set) {
                $sum_tsg += intval($score_tsg[$set] ?? 0);
            }
            $score_tsg['total'] = $sum_tsg;
            update_field('Score-tsg', $score_tsg, $post_id);
        }

        $score_opponent = get_field('Score', $post_id);
        if (is_array($score_opponent) && (!isset($score_opponent['total']) || empty($score_opponent['total']))) {
            $sum_opponent = 0;
            foreach (['1st', '2nd', '3rd', '4th', '5th'] as $set) {
                $sum_opponent += intval($score_opponent[$set] ?? 0);
            }
            $score_opponent['total'] = $sum_opponent;
            update_field('Score', $score_opponent, $post_id);
        }
    }
    // 
    /**
 * Tự động thêm năm vào trường 'time' của ACF nếu bị thiếu. - 自動將年份添加到 ACF 的 'time' 欄位中，如果缺少年份。 
 *
 * @param int $post_id ID của bài viết đang được lưu. - 當前正在保存的文章 ID。
 */
    public function auto_add_year_to_time($post_id) {
        // Chỉ xử lý nếu là post type 'contest_list' - 只處理 'contest_list' 的文章類型
        if (get_post_type($post_id) !== 'contest_list') {
            return;        }
        $raw_time = get_field('time', $post_id, false); // 获取 'time' 字段的当前值
        if (!empty($raw_time) && !preg_match('/^\d{4}\//', $raw_time)) {
            $current_year = date('Y');
            $new_time_value = $current_year . '/' . $raw_time;
            update_field('time', $new_time_value, $post_id);
        }
    }
    private function evaluate_volleyball_game_result($score_tsg, $score_opp, $iso_time, $tsg_is_home) {
        $now = new DateTime('now', new DateTimeZone('Asia/Taipei'));
        $game_time = !empty($iso_time) ? new DateTime($iso_time) : null;

        $home_sets_won = 0;
        $away_sets_won = 0;
        $sets_played = 0;
        $is_ongoing = false;
        $sets = ['1st', '2nd', '3rd', '4th', '5th'];

        // Nếu Score-tsg là đội khách thì hoán đổi điểm để tính đúng
        if ($tsg_is_home === 'AWAY') {
            // Hoán đổi biến cho dễ tính
            $score_home = $score_opp;
            $score_away = $score_tsg;
        } else {
            $score_home = $score_tsg;
            $score_away = $score_opp;
        }

        foreach ($sets as $index => $set_key) {
            $home_score = (int)($score_home[$set_key] ?? 0);
            $away_score = (int)($score_away[$set_key] ?? 0);

            if ($home_score === 0 && $away_score === 0) {
                if ($sets_played > 0) {
                    $is_ongoing = true;
                }
                continue;
            }

            $sets_played++;
            $win_score = ($index < 4) ? 25 : 15;
            $score_difference = abs($home_score - $away_score);

            if (($home_score >= $win_score && $score_difference >= 2) || ($away_score >= $win_score && $score_difference >= 2)) {
                if ($home_score > $away_score) {
                    $home_sets_won++;
                } else {
                    $away_sets_won++;
                }
            } else {
                $is_ongoing = true;
            }
        }

        $game_status_by_score = 9; // 未開始

        if ($home_sets_won >= 3 || $away_sets_won >= 3) {
            $game_status_by_score = 0; // 結束
        } elseif ($is_ongoing || $sets_played > 0) {
            if ($game_time !== null && strtotime($now->format('Y-m-d')) > strtotime($game_time->format('Y-m-d'))) {
                $game_status_by_score = 2; // 保留
            } else {
                $game_status_by_score = 3; // 比賽中
            }
        } elseif ($game_time !== null && $now > $game_time) {
            $game_status_by_score = 1; // 延期
        }

        $winning_team = '-';
        $losing_team = '-';
        $game_result_name = "HOME {$home_sets_won} : {$away_sets_won} AWAY";

        if ($game_status_by_score === 0) {
            if ($home_sets_won > $away_sets_won) {
                $winning_team = 'HOME';
                $losing_team = 'AWAY';
            } else {
                $winning_team = 'AWAY';
                $losing_team = 'HOME';
            }
            $game_result_name = "{$winning_team} WIN";
        }

        return [
            'GameStatus_auto_count_by_score' => $game_status_by_score,
            'GameResultName' => $game_result_name,
            'HomeSetsWon' => $home_sets_won,
            'AwaySetsWon' => $away_sets_won,
            'WinningTeam' => $winning_team,
            'LosingTeam' => $losing_team,
        ];
        
    }

    private function extract_weekday($input) {
        if (preg_match('/\(([^)]+)\)/', $input, $matches)) {
            $weekday = trim($matches[1]);
            $map = [
                '日' => 7, '一' => 1, '二' => 2, '三' => 3, '四' => 4, '五' => 5, '六' => 6,
                'sun' => 7, 'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6,
            ];
            $key = strtolower($weekday);
            return $map[$key] ?? null;
        }
        return null;
    }


    // This í old function, not used now
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

