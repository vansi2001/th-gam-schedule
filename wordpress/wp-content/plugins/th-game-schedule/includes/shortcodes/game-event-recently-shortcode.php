<?php

namespace Th_Game_Schedule\includes\shortcodes;

class Game_Event_Recently_Shortcode
{
    public static $_instance = NULL;

    private $default_img = array(
        // 'AAA011' => THGAMES_URL_PATH . 'assets/img/Dragons.png',
        // 'AAA022' => THGAMES_URL_PATH . 'assets/img/Dragons.png',
        // 'AEO011' => THGAMES_URL_PATH . 'assets/img/Guardians.png',
        // 'AEO022' => THGAMES_URL_PATH . 'assets/img/Guardians.png',
        // 'ACN011' => THGAMES_URL_PATH . 'assets/img/Brothers.png',
        // 'ACN022' => THGAMES_URL_PATH . 'assets/img/Brothers.png',
        // 'ADD011' => THGAMES_URL_PATH . 'assets/img/Lions.png',
        // 'ADD022' => THGAMES_URL_PATH . 'assets/img/Lions.png',
        // 'AJL011' => THGAMES_URL_PATH . 'assets/img/Monkeys.png',
        // 'AJL022' => THGAMES_URL_PATH . 'assets/img/Monkeys.png',
        // 'AKP011' => THGAMES_URL_PATH . 'assets/img/Hawks.png',
        '69516' => THGAMES_URL_PATH . 'assets/img/TaipeiEastPower.webp',
        '69515' => THGAMES_URL_PATH . 'assets/img/Winstreak.webp',
        '69411' => THGAMES_URL_PATH . 'assets/img/TSGSkyHawks.webp',
        '69518' => THGAMES_URL_PATH . 'assets/img/TaoyuanLeopards.webp',
    );

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
                            <span class="game-info-text"><i class="fas fa-map-marker-alt"></i></span>

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
    
    public function ajax_cbpl_recently_game_data() {
    if (empty($_POST)) {
        wp_send_json_error('Error: Method Not Allowed', 405);
        wp_die();
    }

    $query_total_games  = isset($_POST['totalGames'])  ? intval($_POST['totalGames'])  : 6;
    $query_future_games = isset($_POST['futureGames']) ? intval($_POST['futureGames']) : 3;

    $args = [
    'post_type'      => 'contest_list',
    'post_status'    => 'publish',
    'orderby'        => 'post_date',
    'order'          => 'ASC',
    'posts_per_page' => 6
];

    $query = new \WP_Query($args);
    $response_data = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $meta    = get_post_meta($post_id);
            $title  = get_the_title($post_id);

            // ✅ Lấy đội HOME & AWAY
            $home_id = $meta['HOME'][0] ?? '';
            $away_id = $meta['AWAY'][0] ?? '';
            $home_logo_final = $home_logo ?: ($this->default_img[$home_id] ?? $this->default_img['69515']);
            $away_logo_final = $away_logo ?: ($this->default_img[$away_id] ?? $this->default_img['69516']);


            // $home_name = $home_id ? get_the_title($home_id) : 'HOME';
            // $away_name = $away_id ? get_the_title($away_id) : 'AWAY';

            // $title = $meta['post_title'][0] ?? '';

            $home_logo = $home_id ? get_the_post_thumbnail_url($home_id, 'full') : '';
            $away_logo = $away_id ? get_the_post_thumbnail_url($away_id, 'full') : '';

            $location = $meta['location'][0] ?? '';

            $time_str = $meta['time'][0] ?? '';
            $game_date = $time_str;

            // $home_score_set_1 = $meta['Score-tsg_1st'][0] ?? '-';
            // $home_score_set_2 = $meta['Score-tsg_2nd'][0] ?? '-';
            // $home_score_set_3 = $meta['Score-tsg_3rd'][0] ?? '-';
            // $home_score_set_4 = $meta['Score-tsg_4th'][0] ?? '-';
            // $home_score_set_5 = $meta['Score-tsg_5th'][0] ?? '-';

            // $away_score_set_1 = $meta['Score_1st'][0] ?? '-';
            // $away_score_set_2 = $meta['Score_2nd'][0] ?? '-';
            // $away_score_set_3 = $meta['Score_3rd'][0] ?? '-';
            // $away_score_set_4 = $meta['Score_4th'][0] ?? '-';
            // $away_score_set_5 = $meta['Score_5th'][0] ?? '-';

            $home_set_scores = [
    $meta['Score-tsg_1st'][0] ?? '-',
    $meta['Score-tsg_2nd'][0] ?? '-',
    $meta['Score-tsg_3rd'][0] ?? '-',
    $meta['Score-tsg_4th'][0] ?? '-',
    $meta['Score-tsg_5th'][0] ?? '-'
];

$away_set_scores = [
    $meta['Score_1st'][0] ?? '-',
    $meta['Score_2nd'][0] ?? '-',
    $meta['Score_3rd'][0] ?? '-',
    $meta['Score_4th'][0] ?? '-',
    $meta['Score_5th'][0] ?? '-'
];


            // $home_score = $meta['Score-tsg_total'][0] ?? '-';
            // $home_score = 0;
            // $away_score = $meta['Score_total'][0] ?? '-';
            // $away_score = 0;

            
$home_win_sets = 0;
$away_win_sets = 0;

for ($i = 0; $i < 5; $i++) {
    $home = $home_set_scores[$i];
    $away = $away_set_scores[$i];

    if ($home !== '-' && $away !== '-') {
        $home = intval($home);
        $away = intval($away);

        // Kiểm tra nếu đây là set 5 thì dùng mốc 15 điểm
        $min_point = ($i === 4) ? 15 : 25;

        if (($home >= $min_point || $away >= $min_point) && abs($home - $away) >= 2) {
            if ($home > $away) {
                $home_win_sets++;
            } elseif ($away > $home) {
                $away_win_sets++;
            }
        }
    }
}

            $result = 0;

            $game_status = [
        0 => 'Final',
        1 => '延賽',
        2 => '保留',
        4 => '取消',
        9 => 'VS',
    ];

            $response_data[] = [
                'FieldAbbe'        => $location,
                'GameSno'          => $post_id,
                'GameDate'         => $game_date,
                'GameDateTimeS'    => $time_str,
                'HomeTeamCode'     => $home_id,
                'HomeTeamName'     => $home_name,
                'HomeTeamImg'      => $home_logo_final,
                'HomeScore'        => $home_win_sets ?: '-',
                'VisitingTeamCode' => $away_id,
                'VisitingTeamName' => $away_name,
                'VisitingImg'      => $away_logo_final,
                'VisitingScore'    => $away_win_sets ?: '-',
                'GameResult'       => $home_score ? $game_status[$result ?? 9] : $game_status[9] , // Final
            ];
        }
        wp_reset_postdata();
    }

    wp_send_json($response_data);
    wp_die();
}
    //cURL CBPL API
    private function curl_cbpl_recently_game_data($year, $game_type)
    {
        $url = "https://statsapi.cpbl.com.tw/Api/Record/GetSchedule?year=" . $year . "&kindCode=" . $game_type;

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
            return $response_data['ResponseDto'];
        } else {
            return [];
        };
    }

private function format_cbpl_recently_game_data($response_data, $total, $future)
{
    if (empty($response_data)) return [];

    usort($response_data, function ($a, $b) {
        $dateA = $this->convert_time_to_date($a['GameDate']);
        $dateB = $this->convert_time_to_date($b['GameDate']);
        return strtotime($dateA) <=> strtotime($dateB);
    });

    if (!is_numeric($total)) $total = 3;
    if ($total > 10) $total = 10;
    if ($total < 3) $total = 3;
    if (!is_numeric($future)) $future = 1;
    if ($future <= 0) $future = 1;

    $current_date = date("Y-m-d H:i:s");
    $current_date_index = 0;
    $index_record_flag = true;
    $th_games = [];

    foreach ($response_data as $game_data) {
        if ($game_data['HomeTeamCode'] === "AKP011" || $game_data['VisitingTeamCode'] === "AKP011") {
            $th_games[] = $game_data;

            if (strtotime($current_date) < strtotime($this->convert_time_to_date($game_data['GameDate']))) {
                if ($index_record_flag) {
                    $current_date_index = count($th_games) - 1;
                    $index_record_flag = false;
                }
            }
        }
    }
    if ($index_record_flag) $current_date_index = count($th_games) - 1;

    $display_games = [];
    for ($i = $current_date_index; $i < $current_date_index + $future; $i++) {
        if ($i >= $current_date_index + $total) break;
        if (!isset($th_games[$i])) break;
        $display_games[] = $th_games[$i];
    }

    $remain_games_count = $total - count($display_games);
    for ($j = $current_date_index - 1; $j > $current_date_index - $remain_games_count - 1; $j--) {
        if (!isset($th_games[$j])) break;
        array_unshift($display_games, $th_games[$j]);
    }

    $remain_games_count = $total - count($display_games);
    if ($remain_games_count > 0) {
        $start_fill_index = $future + $current_date_index;
        for ($k = $start_fill_index; $k < $start_fill_index + $remain_games_count; $k++) {
            if (!isset($th_games[$k])) break;
            $display_games[] = $th_games[$k];
        }
    }

    $game_status = [
        0 => 'Final',
        1 => '延賽',
        2 => '保留',
        4 => '取消',
        9 => 'VS',
    ];
    $week_key = [
        'Sun' => '日',
        'Mon' => '一',
        'Tue' => '二',
        'Wed' => '三',
        'Thu' => '四',
        'Fri' => '五',
        'Sat' => '六',
    ];

    $return_format_data = [];
    $result = 0;
    foreach ($display_games as $g) {
    // $game_result_code = $g['GameResult'] ?? '-';
    // $game_result_name = $game_status[$game_result_code] ?? '-';
    $game_result_name = $game_status[$result] ?? '-';

    $full_date = $this->convert_time_to_date($g['GameDate']);
    $timestamp = strtotime($full_date);

    if ($timestamp) {
        $md_format = date('m/d', $timestamp);         
        $weekday_en = date('D', $timestamp);          
        $weekday_zh = $week_key[$weekday_en] ?? '-'; 
        $game_date_display = "{$md_format}";          
        $game_week = $weekday_zh;                     
        $game_today = (date("Y-m-d") == date("Y-m-d", $timestamp)) ? 'T' : 'F';
    } else {
        $game_date_display = '??/??';
        $game_week = '-';
        $game_today = 'F';
    }

    $game_time = '';
    if (!empty($g['GameDateTimeS']) && preg_match('/(\d{2}:\d{2})/', $g['GameDateTimeS'], $matches)) {
        $game_time = $matches[1];
    }

    $return_format_data[] = [
        'FieldAbbe'        => $g['FieldAbbe'],
        'GameSno'          => $g['GameSno'],
        'GameDate' => $g['GameDate'],
        'HomeTeamCode'     => $g['HomeTeamCode'],
        'VisitingTeamCode' => $g['VisitingTeamCode'],
        'HomeTeamName'     => $g['HomeTeamName'],
        'VisitingTeamName' => $g['VisitingTeamName'],
        'HomeScore'        => $g['HomeScore'],
        'VisitingScore'    => $g['VisitingScore'],
        'HomeTeamImg' => !empty($g['HomeTeamImg']) ? $g['HomeTeamImg'] : ($this->default_img[$g['HomeTeamCode']] ?? ''),
'VisitingImg' => !empty($g['VisitingImg']) ? $g['VisitingImg'] : ($this->default_img[$g['VisitingTeamCode']] ?? ''),
        // 'GameResultName'   => $game_result_name,
        'GameResultName'   => $g['GameResult'] ?? '-',
        'GameResult'       => $game_result_code,
        'GameToday'        => $game_today
    ];
}

    return $return_format_data;
}

private function convert_time_to_date($str) {
    if (preg_match('/(\d{2})\/(\d{2})/', $str, $matches)) {
        $month = $matches[1];
        $day   = $matches[2];

        $year = date('Y');

        return date('Y-m-d', strtotime("{$year}-{$month}-{$day}"));
    }
    return '';
}



}
