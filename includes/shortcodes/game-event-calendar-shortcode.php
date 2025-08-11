<?php

namespace Th_Game_Schedule\includes\shortcodes;

use \DateTime;
use \DateInterval;
use \DatePeriod;

class Game_Event_Calendar_Shortcode
{
    public static $_instance = NULL;

    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'register_css_js_dependencies']);

        add_action('wp_ajax_cpbl_game_schedule', [$this, 'get_cbpl_data']);
        add_action('wp_ajax_nopriv_cpbl_game_schedule', [$this, 'get_cbpl_data']);

        add_shortcode('cbpl_game_schedule', [$this, 'register_calendar_shortcode']);
    }

    //註冊ajax接口

public function get_cbpl_data()
{
    if (!defined('DOING_AJAX') || !DOING_AJAX || empty($_POST)) {
        wp_send_json_error(['message' => 'Invalid or malformed request.'], 400);
        wp_die();
    }

    $query_year = isset($_POST['year']) ? sanitize_text_field($_POST['year']) : date("Y");

    $args = [
        'post_type'      => 'contest_list',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'date_query'     => [
            [
                'year' => (int) $query_year,
            ],
        ],
        'orderby'        => 'meta_value',
        'meta_key'       => 'time',
        'order'          => 'ASC',
    ];

    $query = new \WP_Query($args);
    $formatted_data = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            $post_id = get_the_ID();
            $fields = function_exists('get_fields') ? (get_fields($post_id) ?: []) : [];

            $post_title = get_the_title($post_id);

            $home_team_name = '';
            $visiting_team_name = '';

            if (preg_match('/^\d{1,2}\/\d{1,2}\s+(.+?)\s+vs\s+(.+)$/ui', $post_title, $matches)) {
                $visiting_team_name = trim($matches[1]);
                $home_team_name = trim($matches[2]);
            }
            
            $game_date = '';
            $game_time_string = '';
            $game_day_of_week = '';
            $datetime_obj = null;
            $now = new DateTime();

            if (!empty($fields['time'])) {
                $raw_time_string = $fields['time'];
                $clean_time_string = preg_replace('/\(\w+\)/u', '', $raw_time_string);
                $datetime_obj = DateTime::createFromFormat('Y/m/d H:i', $clean_time_string);

                if ($datetime_obj !== false) {
                    $game_date = $datetime_obj->format('Y-m-d');
                    $game_time_string = $datetime_obj->format('H:i');
                    $days_map = ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'];
                    $day_index = $datetime_obj->format('N') - 1;
                    $game_day_of_week = $days_map[$day_index] ?? '';
                } else {
                    $datetime_obj_date_only = DateTime::createFromFormat('Y/m/d', $clean_time_string);
                    if ($datetime_obj_date_only !== false) {
                        $game_date = $datetime_obj_date_only->format('Y-m-d');
                        $game_time_string = '';
                        $days_map = ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'];
                        $day_index = $datetime_obj_date_only->format('N') - 1;
                        $game_day_of_week = $days_map[$day_index] ?? '';
                    }
                }
            }

            $home_img = '';
            if (!empty($fields['HOME'])) {
                $home_img = is_array($fields['HOME']) ? ($fields['HOME']['url'] ?? '') : $fields['HOME'];
            }
            $away_img = '';
            if (!empty($fields['AWAY'])) {
                $away_img = is_array($fields['AWAY']) ? ($fields['AWAY']['url'] ?? '') : $fields['AWAY'];
            }
            
            $home_sets_won = 0;
            $away_sets_won = 0;
            $sets = ['1st', '2nd', '3rd', '4th', '5th'];
            $score_tsg = is_array($fields['Score-tsg'] ?? null) ? $fields['Score-tsg'] : [];
            $score_opp = is_array($fields['Score'] ?? null) ? $fields['Score'] : [];

            foreach ($sets as $index => $set_key) {
                $tsg_score = (int)($score_tsg[$set_key] ?? 0);
                $opp_score = (int)($score_opp[$set_key] ?? 0);

                if ($tsg_score === 0 && $opp_score === 0) {
                    continue;
                }
                
                $win_score = ($index < 4) ? 25 : 15;
                $score_difference = abs($tsg_score - $opp_score);

                if (($tsg_score >= $win_score && $score_difference >= 2) || ($opp_score >= $win_score && $score_difference >= 2)) {
                    if ($tsg_score > $opp_score) {
                        $home_sets_won++;
                    } else {
                        $away_sets_won++;
                    }
                }
            }

            $game_status = $fields['gameStatus'] ?? 9; 

            $game_result_text = '';
            $display_home_score = '_';
            $display_visiting_score = '_';
            $winning_team = '-';
            $losing_team = '-';
            $game_result_name = '';

            switch ((int) $game_status) {
                case 0: 
                    $game_result_text = 'FINAL';
                    $display_home_score = $home_sets_won;
                    $display_visiting_score = $away_sets_won;
                    if ($home_sets_won > $away_sets_won) {
                        $winning_team = 'HOME';
                        $losing_team = 'AWAY';
                    } else {
                        $winning_team = 'AWAY';
                        $losing_team = 'HOME';
                    }
                    $game_result_name = "{$winning_team} WIN";
                    break;
                case 1: 
                    $game_result_text = 'VS';
                    $game_time_string = '<span class="game-postponed-text">延賽</span>';
                    $display_home_score = '0';
                    $display_visiting_score = '0';
                    $game_result_name = 'Hoãn';
                    break;
                case 2: 
                    $game_result_text = '<span class="game-postponed-text">保留</span>';                   
                    $display_home_score = $home_sets_won;
                    $display_visiting_score = $away_sets_won;
                    $game_result_name = "HOME {$home_sets_won} : {$away_sets_won} AWAY";
                    break;
                case 3: 
                    $game_result_text = '<span class="game-postponed-text">比賽中</span>';;     
                    $display_home_score = $home_sets_won;
                    $display_visiting_score = $away_sets_won;
                    $game_result_name = "HOME {$home_sets_won} : {$away_sets_won} AWAY";
                    break;
                case 4: 
                    $game_result_text = '<span class="game-postponed-text">取消</span>';
                    $display_home_score = '_';
                    $display_visiting_score = '_';
                    $game_result_name = 'Hủy';
                    break;
                case 9: 
                default:
                    $game_result_text = 'VS';
                    $display_home_score = '_';
                    $display_visiting_score = '_';
                    $game_result_name = 'Chưa đấu';
                    break;
            }

            $formatted_data[] = [
                'GameDate' => $game_date,
                'GameDateTimeS' => $game_time_string,
                'GameResult' => $fields['GameResult'] ?? 0,
                'GameResultName' => $game_result_name,
                'GameLink' => get_permalink($post_id),
                'GameSno' => $fields['gamesNo'], 
                'GameMonth' => $datetime_obj ? $datetime_obj->format('Y-m') : '',
                'GameWeek' => $game_day_of_week,
                'HomeTeamName' => $home_team_name,
                'HomeTeamImg' => $home_img,
                'HomeScore' => $display_home_score, 
                'VisitingTeamName' => $visiting_team_name,
                'VisitingTeamImg' => $away_img,
                'VisitingScore' => $display_visiting_score, 
                'FieldAbbe' => is_array($fields['location'] ?? '') ? ($fields['location']['name'] ?? '') : ($fields['location'] ?? ''),
                'GameResultText' => $game_result_text,
                'GameStatus' => $game_status,
                'WinningTeam' => $winning_team,
                'LosingTeam' => $losing_team,
            ];
        }
        wp_reset_postdata();
    }
    wp_send_json($formatted_data);
    wp_die();
}
private function get_image_url_from_acf($fields, $key)
{
    if (empty($fields[$key])) {
        return '';
    }
    return is_array($fields[$key]) ? ($fields[$key]['url'] ?? '') : $fields[$key];
}

    private function format_cpbl_game_schedule($data)
    {
        $return_data = array();
        $default_img = array(
            'AAA011' => THGAMES_URL_PATH . 'assets/img/Dragons.png',
            'AAA022' => THGAMES_URL_PATH . 'assets/img/Dragons.png',
            'AEO011' => THGAMES_URL_PATH . 'assets/img/Guardians.png',
            'AEO022' => THGAMES_URL_PATH . 'assets/img/Guardians.png',
            'ACN011' => THGAMES_URL_PATH . 'assets/img/Brothers.png',
            'ACN022' => THGAMES_URL_PATH . 'assets/img/Brothers.png',
            'ADD011' => THGAMES_URL_PATH . 'assets/img/Lions.png',
            'ADD022' => THGAMES_URL_PATH . 'assets/img/Lions.png',
            'AJL011' => THGAMES_URL_PATH . 'assets/img/Monkeys.png',
            'AJL022' => THGAMES_URL_PATH . 'assets/img/Monkeys.png',
            'AKP011' => THGAMES_URL_PATH . 'assets/img/Hawks.png',
            'AKP022' => THGAMES_URL_PATH . 'assets/img/Hawks.png',
        );
        $game_status = array(
            0 => '結束',
            1 => '延賽',
            2 => '保留',
            4 => '取消',
            9 => '未賽',
        );
        $week_title = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        foreach ($data as $value) {
            $need_info = array(
                'GameSno' => '',
                'FieldAbbe' => '',
                'GameDateTimeS' => '',
                'GameDate' => '',
                'GameMonth' => '',
                'GameWeek' => '',
                'HomeTeamName' => '',
                'HomeTeamCode' => '',
                'HomeTeamImg' => '',
                'HomeScore' => '',
                'VisitingTeamName' => '',
                'VisitingTeamCode' => '',
                'VisitingTeamImg' => '',
                'VisitingScore'  => '',
                'GameResult' => '',
                'GameResultName' => '',
                'GameResultText' => 'VS'
            );

            if ($value['VisitingTeamCode'] === 'AKP011' || $value['HomeTeamCode'] === 'AKP011') {
                $need_info['GameSno'] = $value['GameSno'];
                $need_info['FieldAbbe'] = $value['FieldAbbe'];
                $need_info['GameDateTimeS'] = date('H:i', strtotime($value['GameDateTimeS']));
                $need_info['GameDate'] = date('Y-m-d', strtotime($value['GameDate']));
                $need_info['GameMonth'] = date('Y-m', strtotime($value['GameDate']));
                $need_info['GameWeek'] = $week_title[date('w', strtotime($value['GameDate']))];
                $need_info['HomeTeamName'] = $value['HomeTeamName'];
                $need_info['HomeTeamCode'] = $value['HomeTeamCode'];
                $need_info['HomeScore'] = $value['HomeScore'];
                $need_info['HomeTeamImg'] = $default_img[$value['HomeTeamCode']];
                $need_info['VisitingTeamName'] = $value['VisitingTeamName'];
                $need_info['VisitingTeamCode'] = $value['VisitingTeamCode'];
                $need_info['VisitingScore'] = $value['VisitingScore'];
                $need_info['VisitingTeamImg'] = $default_img[$value['VisitingTeamCode']];
                $need_info['GameResult'] = $value['GameResult'];
                if ($need_info['GameResult'] === '') {
                    $need_info['GameResult'] = 9;
                    $need_info['HomeScore'] = '-';
                    $need_info['VisitingScore'] = '-';
                }
                if ($need_info['GameResult'] == 0)  $need_info['GameResultText'] = 'FINAL';

                $need_info['GameResultName'] = $game_status[$need_info['GameResult']];

                array_push($return_data, $need_info);
            }
        }
        return $return_data;
    }

    //註冊 css js
    public function register_css_js_dependencies()
    {
        wp_register_style('game_calendar_sc_css', THGAMES_URL_PATH . '/assets/css/game-calendar-ajax-ver.css');
        wp_register_script('game_calendar_sc_js', THGAMES_URL_PATH . '/assets/js/game-calendar-ajax-ver.js', array('jquery'), '1.0.0', true);
    }

    //註冊shortcode
    function register_calendar_shortcode($atts = [])
    {
        $parameters = shortcode_atts(
            array(
                'year'   =>  date('Y'),
                'game_type'   =>  'A',
                'loading' => 'T',
            ),
            $atts
        );

        $calendar_id = 'schedule' . '_' . $parameters['year'] . '_' . $parameters['game_type'];

        $parameters_to_js = array(
            'id' => $calendar_id,
            'year' =>  $parameters['year'],
            'game_type' => $parameters['game_type'],
            'ajax_url' => admin_url('admin-ajax.php')
        );

        wp_enqueue_style('game_calendar_sc_css');

        wp_localize_script('game_calendar_sc_js', 'PHP_DATA', $parameters_to_js);
        wp_enqueue_script('game_calendar_sc_js');

        //當前日期
        $server_web_today = date('Y-m-d');
        //當前月份
        $current_month = date('Y-m');

        //生成月份陣列 +-1years
        $first_month = date('Y-m-d', strtotime('-1 year'));
        $last_month = date('Y-m-d', strtotime('+1 year'));
        $month_generate = [$first_month, $last_month];
        $all_calendar_month = $this->get_calendar_month($month_generate[0],  $month_generate[1]);
        $current_month_key = 0;
        $all_month_list = array();
        foreach ($all_calendar_month as $need_month_key => $month_value) {
            array_push($all_month_list, $month_value['year_month']);
            if ($current_month == $month_value['year_month']) $current_month_key = $need_month_key;
        }

        //week title
        $week_title = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        ob_start();
?>
        <div class="game-calendar-wrapper" id="<?php echo esc_attr($calendar_id) ?>">
            <div class="year-month-title" data-all-month="<?php echo esc_attr(json_encode($all_month_list)) ?>" data-display-month="<?php echo esc_attr($current_month_key) ?>">
                <div class="display-tabs">
                </div>
                <button class="month-switcher-btn calendar-title" data-switch="prev">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" width="16px" height="16px">
                        <path d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z" />
                    </svg></button>
                <div class="year-month-text calendar-title">
                    <?php echo $current_month ?>
                </div>
                <button class="month-switcher-btn calendar-title" data-switch="next">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" width="16px" height="16px">
                        <path d="M310.6 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L242.7 256 73.4 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z" />
                    </svg>
                </button>
                <div class="display-tabs">
                    <button class="calendar-tab-active calendar-switch-btn" data-calendarview="month">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="12px" height="12px">
                            <path class="btn-svg-icon" d="M152 24c0-13.3-10.7-24-24-24s-24 10.7-24 24V64H64C28.7 64 0 92.7 0 128v16 48V448c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V192 144 128c0-35.3-28.7-64-64-64H344V24c0-13.3-10.7-24-24-24s-24 10.7-24 24V64H152V24zM48 192h80v56H48V192zm0 104h80v64H48V296zm128 0h96v64H176V296zm144 0h80v64H320V296zm80-48H320V192h80v56zm0 160v40c0 8.8-7.2 16-16 16H320V408h80zm-128 0v56H176V408h96zm-144 0v56H64c-8.8 0-16-7.2-16-16V408h80zM272 248H176V192h96v56z" />
                        </svg>
                        <span>
                            月
                        </span>
                    </button>
                    <button class="calendar-switch-btn" data-calendarview="list">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="12px" height="12px">
                            <path class="btn-svg-icon" d="M40 48C26.7 48 16 58.7 16 72v48c0 13.3 10.7 24 24 24H88c13.3 0 24-10.7 24-24V72c0-13.3-10.7-24-24-24H40zM192 64c-17.7 0-32 14.3-32 32s14.3 32 32 32H480c17.7 0 32-14.3 32-32s-14.3-32-32-32H192zm0 160c-17.7 0-32 14.3-32 32s14.3 32 32 32H480c17.7 0 32-14.3 32-32s-14.3-32-32-32H192zm0 160c-17.7 0-32 14.3-32 32s14.3 32 32 32H480c17.7 0 32-14.3 32-32s-14.3-32-32-32H192zM16 232v48c0 13.3 10.7 24 24 24H88c13.3 0 24-10.7 24-24V232c0-13.3-10.7-24-24-24H40c-13.3 0-24 10.7-24 24zM40 368c-13.3 0-24 10.7-24 24v48c0 13.3 10.7 24 24 24H88c13.3 0 24-10.7 24-24V392c0-13.3-10.7-24-24-24H40z" />
                        </svg>
                        <span>
                            活動列表
                        </span>
                    </button>
                </div>
            </div>
            <?php foreach ($all_calendar_month as $month_key => $month) :  ?>
                <div class="game-calendar-content" <?php if ($current_month !== $month['year_month']) echo 'hidden'; ?> data-month="<?php echo esc_attr($month_key); ?>">
                    <?php if ($parameters['loading'] == 'T') : ?>
                        <div class="calendar-loading-mask">
                            <div>
                                <img src="<?php echo esc_attr(THGAMES_URL_PATH . 'assets/img/loading.gif') ?>" alt="" class="calendar-loading">
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="calendar-table-view">
                        <table class="game-calendar-table">
                            <thead class="game-calendar-thead">
                                <tr>
                                    <?php foreach ($week_title as $week_title_value) : ?>
                                        <th class="week-text"><?php echo esc_html($week_title_value) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody class="calendar-tbody">
                                <?php foreach ($month['calendar'] as $week) : ?>
                                    <tr>
                                        <?php foreach ($week as $day) : ?>
                                            <td data-date="<?php echo esc_attr($day[2]) ?>">
                                                <div class="calendar-day-text">
                                                    <span class="<?php
                                                                    $the_day = date('Y-m-d', strtotime($day[1] . '-' . $day[0]));
                                                                    if ($day[1] !== $month['year_month']) echo 'calendar-non-day';
                                                                    if ($the_day === $server_web_today) echo 'calendar-today';
                                                                    ?>">
                                                        <?php echo esc_html($day[0]) ?>
                                                    </span>
                                                </div>
                                                <div class="calendar-day-info">

                                                </div>
                                            </td>
                                        <?php endforeach ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="calendar-list-view" data-month-list="<?php echo esc_attr($month['year_month']); ?>">
                        <div class="calendar-list-ngame calendar-list-content">
                            <div class="per-game">
                                無賽事
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
<?php
        return ob_get_clean();
    }

    private function get_calendar_month($start, $end)
    {
        //取得間隔月份
        $month_interval = $this->get_month_interval($start, $end);
        $month_calendar = array();
        foreach ($month_interval as $month) {
            //取得該月份天數
            $month_day = date('t', strtotime($month));


            //取得上月份
            $last_month = date('Y-m', strtotime($month . '- 1 days'));
            //取得上月份天數
            $last_month_day = date('t', strtotime($month . '- 1 days'));

            //取得該月份第一天星期幾
            $week_day = date('w', strtotime($month));

            $calendar = array();

            $week_array = array();

            //每個月第一天當周補上個月日期
            for ($j = $week_day; $j > 0; $j--) {
                $target_day = $last_month_day - $j + 1;
                array_push($week_array, [$target_day, $last_month, $last_month . '-' . str_pad($target_day, 2, '0', STR_PAD_LEFT)]);
            }

            for ($i = 1; $i <= $month_day; $i++) {
                if ($i == $month_day) {
                    //每個月最後一天當周補下個月日期
                    array_push($week_array, [$i, $month, $month . '-' . str_pad($i, 2, '0', STR_PAD_LEFT)]);
                    $next_month = date('Y-m', strtotime($month . '-' . $i . '+1 day'));
                    $next_mon_day = 1;
                    while ($next_mon_day <= 7) {
                        if (count($week_array) == 7) break;
                        array_push($week_array, [$next_mon_day, $next_month, $next_month . '-' . str_pad($next_mon_day, 2, '0', STR_PAD_LEFT)]);
                        $next_mon_day++;
                    }
                    array_push($calendar, $week_array);
                    $week_array = array();
                } else {
                    array_push($week_array, [$i, $month, $month . '-' . str_pad($i, 2, '0', STR_PAD_LEFT)]);
                    if (count($week_array) === 7) {
                        array_push($calendar, $week_array);
                        $week_array = array();
                    }
                }
            }
            array_push($month_calendar, array(
                'year_month' => $month,
                'calendar' => $calendar
            ));
        }
        return $month_calendar;
    }

    private function get_month_interval($start, $end)
    {
        $start_date = (new DateTime($start))->modify('first day of this month');

        $end_date = (new DateTime($end))->modify('first day of next month');

        $interval = DateInterval::createFromDateString('1 month');

        $period = new DatePeriod($start_date, $interval, $end_date);

        $month_interval = array();

        foreach ($period as $dt) {

            array_push($month_interval, $dt->format("Y-m"));
        }

        return $month_interval;
    }

    public static function instance()
    {
        if (is_null(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }
}
