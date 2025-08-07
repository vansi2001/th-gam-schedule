<?php

namespace Th_Game_Schedule\includes\shortcodes;

use DateTime;
use DateTimeZone;
use WP_Query;

class Game_Event_Recently_Shortcode
{
    public static $_instance = NULL;

    // private $default_img = array(
    //     'AAA011' => THGAMES_URL_PATH . 'assets/img/Dragons.png',
    //     'AAA022' => THGAMES_URL_PATH . 'assets/img/Dragons.png',
    //     'AEO011' => THGAMES_URL_PATH . 'assets/img/Guardians.png',
    //     'AEO022' => THGAMES_URL_PATH . 'assets/img/Guardians.png',
    //     'ACN011' => THGAMES_URL_PATH . 'assets/img/Brothers.png',
    //     'ACN022' => THGAMES_URL_PATH . 'assets/img/Brothers.png',
    //     'ADD011' => THGAMES_URL_PATH . 'assets/img/Lions.png',
    //     'ADD022' => THGAMES_URL_PATH . 'assets/img/Lions.png',
    //     'AJL011' => THGAMES_URL_PATH . 'assets/img/Monkeys.png',
    //     'AJL022' => THGAMES_URL_PATH . 'assets/img/Monkeys.png',
    //     'AKP011' => THGAMES_URL_PATH . 'assets/img/Hawks.png',
    //     'AKP022' => THGAMES_URL_PATH . 'assets/img/Hawks.png',
    // );

    public function __construct()
    {
        //註冊css js
        add_action('wp_enqueue_scripts', [$this, 'register_css_js_dependencies']);

        //註冊admin-ajax
        add_action('wp_ajax_cpbl_game_recently', [$this, 'ajax_cbpl_recently_game_data']);
        add_action('wp_ajax_nopriv_cpbl_game_recently', [$this, 'ajax_cbpl_recently_game_data']);

        //註冊shortcode
        add_shortcode('cbpl_game_recently', [$this, 'register_recently_shortcode']);
    }

    public static function instance()
    {
        if (is_null(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }

    //註冊css js 
    public function register_css_js_dependencies()
    {
        wp_register_style('game_recently_sc_css', THGAMES_URL_PATH . '/assets/css/game-event-ajax-ver.css');
        wp_register_script('game_recently_sc_js', THGAMES_URL_PATH . '/assets/js/game-event-ajax-ver.js', array('jquery'), '1.0.0', true);
    }

    //註冊shortcode
    public function register_recently_shortcode($atts = [])
    {
        $parameters = shortcode_atts(
            array(
                'year' => date('Y'),
                'game_type' => 'A', 
                'total' => '6',
                'future_game' => '3',
                'loading' => 'T'
            ),
            $atts
        );
        wp_enqueue_style('game_recently_sc_css');

        $recently_id = 'recently' . '_' . $parameters['year'] . '_' . $parameters['game_type'];
        $parameters_to_js = array(
            'id' => $recently_id,
            'year' =>  $parameters['year'],
            'game_type' => $parameters['game_type'], 
            'total' => $parameters['total'],
            'future_game' => $parameters['future_game'],
            'ajax_url' => admin_url('admin-ajax.php')
        );
        //傳變數至js
        wp_localize_script('game_recently_sc_js', 'RECENTLY_PHP_DATA', $parameters_to_js);
        wp_enqueue_script('game_recently_sc_js');

        ob_start();
?>
        <div class="th-game-recently">
            <?php if ($parameters['loading'] == 'T') : ?>
                <div class="th-recently-mask">
                    <img src="<?php echo esc_attr(THGAMES_URL_PATH . 'assets/img/loading.gif') ?>" alt="" class="th-recently-loading">
                </div>
            <?php endif; ?>
            <div class="th-move th-prev-btn" data-move="prev">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 360 512" width="30" height="30">
                    <path d="M41.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L109.3 256 246.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z" />
                </svg>
            </div>
            <div class="th-game-wrapper focus-scale focus-border">
                <?php for ($i = 0; $i < 3; $i++) : ?>
                    <div class="th-game-card th-game-bg" data-scroll="">
                        <div class="th-game-info">
                            <span class="game-info-text">-</span>
                            <span class="game-info-text"><i class="fas fa-map-marker-alt"></i>-</span>
                        </div>
                        <div class="th-game-result">
                            <div>
                                <div class="th-game-logo">
                                    <img src="<?php echo esc_attr(THGAMES_URL_PATH . 'assets/img/logo.png') ?>" alt="">
                                </div>
                                <div class="game-home-away">AWAY</div>
                            </div>
                            <div class="th-game-score">
                                <div class="game-score-title">Final</div>
                                <div class="game-score-text">- : -</div>
                            </div>
                            <div>
                                <div class="th-game-logo">
                                    <img src="<?php echo esc_attr(THGAMES_URL_PATH . 'assets/img/logo.png') ?>" alt="">
                                </div>
                                <div class="game-home-away">HOME</div>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
            <div class="th-move th-next-btn" data-move="next">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 280 512" width="30" height="30">
                    <path d="M278.6 233.4c12.5 12.5 12.5 32.8 0 45.3l-160 160c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L210.7 256 73.4 118.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l160 160z" />
                </svg>
            </div>
        </div>
<?php
        return ob_get_clean();
    }

    //新增admin-ajax.php接口 - new 增加admin-ajax.php接口
    public function ajax_cbpl_recently_game_data()
    {
        if (empty($_POST)) {
            wp_send_json_error('Error: Method Not Allowed', 405);
            wp_die();
        }

        $query_year = date("Y");
        $format_data = $this->get_recently_games($query_year);
        wp_send_json($format_data);
        wp_die();
    }
    // Lấy danh sách các trận đấu - 拿取比賽列表
    public function get_recently_games($year = null) {
        $current_year = date('Y');
        $query_year = $year ?: $current_year;

        // Chuẩn bị các tham số truy vấn bài viết
        $args = [
            'post_type'      => 'contest_list',
            'posts_per_page' => -1,
            'orderby'        => 'meta_value',
            'meta_key'       => 'time',
            'order'          => 'ASC',
        ];

        $search_pattern = $query_year;

        $query = new WP_Query($args);
        $posts = $query->posts;

        // Xử lý trường hợp không có bài viết
        if (empty($posts)) {
            return [
                'ErrMsg' => '没有找到任何文章! (Không tìm thấy bài viết nào!)',
                'Successed' => false,
                'ResponseDto' => []
            ];
        }

        $past_games = [];
        $future_games = [];
        $today = strtotime(date('Y-m-d')); //output : 2025-08-01 
        
        foreach ($posts as $post) {
            $id = $post->ID;
            $f = function_exists('get_fields') ? get_fields($id) : [];

            $raw_time = $f['time'] ?? '';
            if (!$raw_time || strpos($raw_time, $search_pattern) !== 0) continue;

            $timeS = $this->convert_to_iso_datetime($raw_time, $query_year); //out put : 2025-08-01T18:00:00
            $datetime = $timeS ? strtotime($timeS) : false;
            if (!$datetime) continue;

            $score_tsg = get_field('Score-tsg', $id) ?: [];
            $score_opp = get_field('Score', $id) ?: [];


            $title = $post->post_title;
            if (preg_match('/\d{1,2}\/\d{1,2}\s+(.*?)\s+VS\s+(.*)/ui', $title, $matches)) {
                $home_team = trim($matches[1]);
                $visiting_team = trim($matches[2]);
            }

            $home_img = $f['HOME'] ?? '';
            if (is_array($home_img) && isset($home_img['url'])) {
                $home_img = $home_img['url'];
            }
            $away_img = $f['AWAY'] ?? '';
            if (is_array($away_img) && isset($away_img['url'])) {
                $away_img = $away_img['url'];
            }

            $result_data = $this->evaluate_volleyball_game_result($score_tsg, $score_opp, $timeS);
            $istoday = (date("Y-m-d") === date("Y-m-d", strtotime($timeS))) ? 'T' : 'F';

            $formatted_data = [
                'FieldAbbe'         => $f['location'] ?? '-',
                'GameSno'           => '-',
                'GameDateTimeS'     => date('H:i', $datetime),
                'GameDate'          => date('m/d', $datetime),           // 07/04                
                'GameWeek'          => $this->get_chinese_weekday($datetime),
                'HomeTeamCode'      => '-',
                'HomeTeamName'      => $home_team,
                'HomeTeamImg'       => $home_img ?: '-',
                'HomeScore'         => $result_data['HomeSetsWon'],
                'VisitingTeamCode'  => '-',
                'VisitingTeamName'  => $visiting_team,
                'VisitingImg'       => $away_img ?: '-',
                'VisitingScore'     => $result_data['AwaySetsWon'],
                'GameResultName'    => $result_data['GameStatus'],
                'GameResult'        => $result_data['HomeSetsWon'] . '-' . $result_data['AwaySetsWon'],
                'GameToday'         => $istoday,
                'post_url' => get_permalink($post->ID),
            ];

            if ($datetime <= $today) {
                $past_games[] = ['data' => $formatted_data, 'timestamp' => $datetime];
            } else {
                $future_games[] = ['data' => $formatted_data, 'timestamp' => $datetime];
            }
        }

        // Sắp xếp và lấy các trận đấu gần đây nhất và sắp tới
        usort($past_games, fn($a, $b) => $b['timestamp'] - $a['timestamp']);
        usort($future_games, fn($a, $b) => $a['timestamp'] - $b['timestamp']);

        $past = array_slice($past_games, 0, 1);
        $future = array_slice($future_games, 0, 6);

        $final = array_merge($past, $future);
        $response = array_map(fn($item) => $item['data'], $final);

        return $response;
    }
    
    // Các hàm trợ giúp (helper functions) 
    private function convert_to_iso_datetime($input, $default_year) {
        if (preg_match('/^(?:(\d{4})\/)?(\d{1,2})\/(\d{1,2})[\(（][^\)\）]+[\)）]\s+(\d{1,2}):(\d{2})$/u', $input, $matches)) {
            $year  = !empty($matches[1]) ? $matches[1] : $default_year;
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $day   = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
            $hour  = str_pad($matches[4], 2, '0', STR_PAD_LEFT);
            $min   = str_pad($matches[5], 2, '0', STR_PAD_LEFT);
            return "{$year}-{$month}-{$day}T{$hour}:{$min}:00";
        }
        return null;
    }

    private function evaluate_volleyball_game_result($score_tsg, $score_opp, $iso_time) {
        $now = new DateTime('now', new DateTimeZone('Asia/Taipei')); //output : 2025-08-01T12:00:00+08:00
        $game_time = $iso_time ? new DateTime($iso_time) : null; // output : 2025-08-01T18:00:00+08:00
        $home_sets_won = 0;
        $away_sets_won = 0;
        $sets_played = 0;
        $is_ongoing = false;
        $sets = ['1st', '2nd', '3rd', '4th', '5th'];

        foreach ($sets as $index => $set_key) {
            $tsg_score = (int)($score_tsg[$set_key] ?? 0);
            $opp_score = (int)($score_opp[$set_key] ?? 0);

            if ($tsg_score === 0 && $opp_score === 0) {
                if ($sets_played > 0) $is_ongoing = true;
                continue;
            }

            $sets_played++;
            $win_score = ($index < 4) ? 25 : 15;
            $diff = abs($tsg_score - $opp_score);

            if (($tsg_score >= $win_score && $diff >= 2) || ($opp_score >= $win_score && $diff >= 2)) {
                if ($tsg_score > $opp_score) {
                    $home_sets_won++;
                } else {
                    $away_sets_won++;
                }
            } else {
                $is_ongoing = true;
            }
        }

        $status = 'VS';
        if ($home_sets_won >= 3 || $away_sets_won >= 3) {
            $status = 'Final';
        } elseif ($is_ongoing || $sets_played > 0) {
           if (strtotime($now->format('Y-m-d')) > strtotime($game_time->format('Y-m-d'))) {
             $status = '保留';    // thời gian hiện tại lớn hơn thời gian trận đấu thì là bị tạm dừng -  保留    
            } else{
                $status = '比賽中';
            }
        } elseif ($game_time && $now > $game_time) {
            $status = '延賽';
        }

        $result_name = "HOME {$home_sets_won} : {$away_sets_won} AWAY";
        if ($status === 0) {
            $winner = ($home_sets_won > $away_sets_won) ? 'HOME' : 'AWAY';
            $result_name = "{$winner} WIN";
        }

        return [
            'GameStatus'     => $status,
            'GameIsStop'     => $status === 1,
            'GameResultName' => $result_name,
            'HomeSetsWon'    => $home_sets_won,
            'AwaySetsWon'    => $away_sets_won,
            'WinningTeam'    => ($home_sets_won > $away_sets_won) ? 'HOME' : 'AWAY',
            'LosingTeam'     => ($home_sets_won > $away_sets_won) ? 'AWAY' : 'HOME',
        ];
    }
    private function get_chinese_weekday($timestamp) {
        $weekdays = ['日', '一', '二', '三', '四', '五', '六'];
        return $weekdays[date('w', $timestamp)];
    }
}
    //新增admin-ajax.php接口
    // public function ajax_cbpl_recently_game_data()
    // {
    //     if (empty($_POST)) {
    //         wp_send_json_error('Error: Method Not Allowed', 405);
    //         wp_die();
    //     }

    //     $query_year = date("Y");
    //     $query_kind_code = 'A';
    //     $query_total_games = '6';
    //     $query_future_games = '3';
    //     if (isset($_POST['year'])) $query_year = $_POST['year'];
    //     if (isset($_POST['kindCode'])) $query_kind_code = $_POST['kindCode'];
    //     if (isset($_POST['totalGames'])) $query_total_games = $_POST['totalGames'];
    //     if (isset($_POST['futureGames'])) $query_future_games = $_POST['futureGames'];

    //     $response_data = $this->curl_cbpl_recently_game_data($query_year, $query_kind_code);
    //     $format_data = $this->format_cbpl_recently_game_data($response_data, $query_total_games, $query_future_games);
    //     wp_send_json($format_data);
    //     wp_die();
    // }
    //cURL CBPL API
    // private function curl_cbpl_recently_game_data($year, $game_type)
    // {
    //     $url = "https://statsapi.cpbl.com.tw/Api/Record/GetSchedule?year=" . $year . "&kindCode=" . $game_type;

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
    //     if ($response_data['Successed']) {
    //         return $response_data['ResponseDto'];
    //     } else {
    //         return [];
    //     };
    // }
    
// //整理賽程資料
//     private function format_cbpl_recently_game_data($response_data, $total, $future)
//     {
//         if (empty($response_data)) return [];
//         usort($response_data, function ($a, $b) {
//             if (strtotime($a['GameDate']) == strtotime($b['GameDate'])) return 0;
//             return (strtotime($a['GameDate']) > strtotime($b['GameDate'])) ? 1 : -1;
//         });

//         if (!is_numeric($total)) $total = 3;
//         if ($total > 10) $total = 10;
//         if ($total < 3) $total = 3;
//         if (!is_numeric($future)) $future = 1;
//         if ($future <= 0) $future = 1;
//         $current_date = date("Y-m-d H:i:s");
//         $current_date_index = 0;
//         $index_record_flag = true;
//         $th_games = array();
//         foreach ($response_data as $game_data) {
//             if ($game_data['HomeTeamCode'] === "AKP011" || $game_data['VisitingTeamCode'] === "AKP011") {
//                 array_push($th_games, $game_data);
//                 if (strtotime($current_date) < strtotime($game_data['GameDateTimeS'])) {
//                     if ($index_record_flag) {
//                         $current_date_index = count($th_games) - 1;
//                         $index_record_flag = false;
//                     }
//                 }
//             }
//         }
//         if ($index_record_flag) $current_date_index = count($th_games) - 1;

//         $display_games = array();
//         for ($i = $current_date_index; $i < $current_date_index + $future; $i++) {
//             if ($i >= $current_date_index + $total) break;
//             if (!isset($th_games[$i])) break;
//             array_push($display_games, $th_games[$i]);
//         }

//         //狀況一 顯示場數總量扣除目前計算的未來場數，往前補過去比賽補至足夠場數
//         $remain_games_count = $total - count($display_games);
//         for ($j = $current_date_index - 1; $j > $current_date_index - $remain_games_count - 1; $j--) {
//             if (!isset($th_games[$j])) break;
//             array_unshift($display_games, $th_games[$j]);
//         }

//         //狀況二 補完過去比賽場數仍不足需求總場數，往後補未來比賽至足夠場數
//         $remain_games_count = $total - count($display_games);
//         if ($remain_games_count > 0) {
//             $start_fill_inidex = $future  + $current_date_index;
//             for ($k = $start_fill_inidex; $k < $start_fill_inidex + $remain_games_count; $k++) {
//                 if (!isset($th_games[$k])) break;
//                 array_push($display_games, $th_games[$k]);
//             }
//         }

//         //整理成前端所需格式
//         $game_status = array(
//             0 => 'Final',
//             1 => '延賽',
//             2 => '保留',
//             4 => '取消',
//             9 => 'VS',
//         );
//         $week_key = array(
//             0 => '日',
//             1 => '一',
//             2 => '二',
//             3 => '三',
//             4 => '四',
//             5 => '五',
//             6 => '六',
//         );
//         $return_format_data = array();
//         for ($l = 0; $l < count($display_games); $l++) {
//             $game_result_code = $display_games[$l]['GameResult'];
//             if ($game_result_code === "") $game_result_code = 9;
//             $game_result_name = isset($game_status[$game_result_code]) ? $game_status[$game_result_code] : '-';
//             $game_today = date("Y-m-d", strtotime($current_date))  == date("Y-m-d", strtotime($display_games[$l]['GameDateTimeS'])) ? 'T' : 'F';

//             array_push($return_format_data, array(
//                 'FieldAbbe' => $display_games[$l]['FieldAbbe'],
//                 'GameSno' => $display_games[$l]['GameSno'],
//                 'GameDateTimeS' => date('H:i', strtotime($display_games[$l]['GameDateTimeS'])),
//                 'GameDate' => date('m/d', strtotime($display_games[$l]['GameDate'])),
//                 'GameWeek' => $week_key[date('w', strtotime($display_games[$l]['GameDate']))],
//                 'HomeTeamCode' => $display_games[$l]['HomeTeamCode'],
//                 'HomeTeamName' => $display_games[$l]['HomeTeamName'],
//                 'HomeTeamImg' => $this->default_img[$display_games[$l]['HomeTeamCode']],
//                 'HomeScore' => $display_games[$l]['HomeScore'],
//                 'VisitingTeamCode' => $display_games[$l]['VisitingTeamCode'],
//                 'VisitingTeamName' => $display_games[$l]['VisitingTeamName'],
//                 'VisitingImg' => $this->default_img[$display_games[$l]['VisitingTeamCode']],
//                 'VisitingScore' => $display_games[$l]['VisitingScore'],
//                 'GameResultName' => $game_result_name,
//                 'GameResult' => $game_result_code,
//                 'GameToday' => $game_today
//             ));
//         }
//         return $return_format_data;
//     }    
// }
