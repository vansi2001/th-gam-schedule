<?php

namespace Th_Game_Schedule\includes\shortcodes;

class Game_Event_Recently_Shortcode
{
    private static $_instance = null;

    private const DEFAULT_IMAGES = [
        '69516' => THGAMES_URL_PATH . 'assets/img/TaipeiEastPower.webp',
        '69515' => THGAMES_URL_PATH . 'assets/img/Winstreak.webp',
        '69411' => THGAMES_URL_PATH . 'assets/img/TSGSkyHawks.webp',
        '69518' => THGAMES_URL_PATH . 'assets/img/TaoyuanLeopards.webp',
    ];

    private const GAME_STATUS = [
        0 => 'Final',
        1 => '延賽',
        2 => '保留',
        4 => '取消',
        9 => 'VS',
    ];

    private const WEEK_DAYS = [
        0 => '日',
        1 => '一',
        2 => '二',
        3 => '三',
        4 => '四',
        5 => '五',
        6 => '六',
    ];

    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('wp_ajax_cpbl_game_recently', [$this, 'handle_ajax_request']);
        add_action('wp_ajax_nopriv_cpbl_game_recently', [$this, 'handle_ajax_request']);
        add_shortcode('cbpl_game_recently', [$this, 'render_shortcode']);
    }

    public static function instance(): self
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function register_assets(): void
    {
        wp_register_style(
            'game_recently_sc_css',
            THGAMES_URL_PATH . '/assets/css/game-event-ajax-ver.css'
        );

        wp_register_script(
            'game_recently_sc_js',
            THGAMES_URL_PATH . '/assets/js/game-event-ajax-ver.js',
            ['jquery'],
            '1.0.0',
            true
        );
    }

    public function render_shortcode(array $atts = []): string
    {
        $params = shortcode_atts([
            'year' => date('Y'),
            'game_type' => 'A',
            'total' => '6',
            'future_game' => '3',
            'loading' => 'T'
        ], $atts);

        wp_enqueue_style('game_recently_sc_css');

        $recently_id = 'recently_' . $params['year'] . '_' . $params['game_type'];

        wp_localize_script('game_recently_sc_js', 'RECENTLY_PHP_DATA', [
            'id' => $recently_id,
            'year' => $params['year'],
            'game_type' => $params['game_type'],
            'total' => $params['total'],
            'future_game' => $params['future_game'],
            'ajax_url' => admin_url('admin-ajax.php')
        ]);

        wp_enqueue_script('game_recently_sc_js');

        ob_start();
        ?>
        <div class="th-game-recently">
            <?php if ($params['loading'] === 'T'): ?>
                <div class="th-recently-mask">
                    <img src="<?php echo esc_url(THGAMES_URL_PATH . 'assets/img/loading.gif') ?>" alt=""
                        class="th-recently-loading">
                </div>
            <?php endif; ?>

            <div class="th-move th-prev-btn" data-move="prev">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 360 512" width="30" height="30">
                    <path
                        d="M41.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L109.3 256 246.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z" />
                </svg>
            </div>

            <div class="th-game-wrapper focus-scale focus-border">
                <?php for ($i = 0; $i < 3; $i++): ?>
                    <div class="th-game-card th-game-bg" data-scroll="">
                        <div class="th-game-info">
                            <span class="game-info-text">-</span>
                            <span class="game-info-text"><i class="fas fa-map-marker-alt"></i></span>
                        </div>
                        <div class="th-game-result">
                            <div>
                                <div class="th-game-logo">
                                    <img src="<?php echo esc_url(THGAMES_URL_PATH . 'assets/img/logo.png') ?>" alt="">
                                </div>
                                <div class="game-home-away">AWAY</div>
                            </div>
                            <div class="th-game-score">
                                <div class="game-score-title">Final</div>
                                <div class="game-score-text">- : -</div>
                            </div>
                            <div>
                                <div class="th-game-logo">
                                    <img src="<?php echo esc_url(THGAMES_URL_PATH . 'assets/img/logo.png') ?>" alt="">
                                </div>
                                <div class="game-home-away">HOME</div>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>

            <div class="th-move th-next-btn" data-move="next">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 280 512" width="30" height="30">
                    <path
                        d="M278.6 233.4c12.5 12.5 12.5 32.8 0 45.3l-160 160c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L210.7 256 73.4 118.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l160 160z" />
                </svg>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_ajax_request(): void
    {
        if (empty($_POST)) {
            wp_send_json_error('Error: Method Not Allowed', 405);
            wp_die();
        }

        $query = new \WP_Query([
            'post_type' => 'contest_list',
            'post_status' => 'publish',
            'orderby' => 'post_date',
            'order' => 'ASC',
            'posts_per_page' => 6
        ]);

        $response_data = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $meta = get_post_meta($post_id);

                $home_id = $meta['HOME'][0] ?? '';
                $away_id = $meta['AWAY'][0] ?? '';

                $home_logo = $home_id ? get_the_post_thumbnail_url($home_id, 'full') : '';
                $away_logo = $away_id ? get_the_post_thumbnail_url($away_id, 'full') : '';

                $home_logo_final = $home_logo ?: (self::DEFAULT_IMAGES[$home_id] ?? self::DEFAULT_IMAGES['69515']);
                $away_logo_final = $away_logo ?: (self::DEFAULT_IMAGES[$away_id] ?? self::DEFAULT_IMAGES['69516']);

                $set_scores = $this->calculate_set_scores($meta);
                $has_score = ($set_scores['home_win_sets'] !== 0 || $set_scores['away_win_sets'] !== 0);

                $response_data[] = [
                    'FieldAbbe' => $meta['location'][0] ?? '',
                    'GameSno' => $post_id,
                    'GameDate' => $meta['time'][0] ?? '',
                    'GameDateTimeS' => $meta['time'][0] ?? '',
                    'HomeTeamCode' => $home_id,
                    'HomeTeamImg' => $home_logo_final,
                    'HomeScore' => $set_scores['home_win_sets'] ?: '-',
                    'VisitingTeamCode' => $away_id,
                    'VisitingImg' => $away_logo_final,
                    'VisitingScore' => $set_scores['away_win_sets'] ?: '-',
                    'GameResult' => $has_score ? self::GAME_STATUS[0] : self::GAME_STATUS[9], // Sử dụng hằng số
                ];
            }
            wp_reset_postdata();
        }

        wp_send_json($response_data);
        wp_die();
    }

    private function calculate_set_scores(array $meta): array
    {
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

        $home_win_sets = 0;
        $away_win_sets = 0;

        for ($i = 0; $i < 5; $i++) {
            $home = $home_set_scores[$i];
            $away = $away_set_scores[$i];

            if ($home !== '-' && $away !== '-') {
                $home = (int) $home;
                $away = (int) $away;
                $min_point = ($i === 4) ? 15 : 25;

                if (($home >= $min_point || $away >= $min_point) && abs($home - $away) >= 2) {
                    $home > $away ? $home_win_sets++ : $away_win_sets++;
                }
            }
        }

        return [
            'home_win_sets' => $home_win_sets,
            'away_win_sets' => $away_win_sets
        ];
    }

    private function fetch_cbpl_data(string $year, string $game_type): array
    {
        $url = "https://statsapi.cpbl.com.tw/Api/Record/GetSchedule?year={$year}&kindCode={$game_type}";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        return $data['Successed'] ? $data['ResponseDto'] : [];
    }

    private function format_game_data(array $response_data, int $total, int $future): array
    {
        if (empty($response_data)) {
            return [];
        }

        usort($response_data, function ($a, $b) {
            $dateA = $this->parse_game_date($a['GameDate']);
            $dateB = $this->parse_game_date($b['GameDate']);
            return strtotime($dateA) <=> strtotime($dateB);
        });

        $total = max(3, min(10, $total));
        $future = max(1, $future);

        $th_games = array_filter($response_data, function ($game) {
            return $game['HomeTeamCode'] === "AKP011" || $game['VisitingTeamCode'] === "AKP011";
        });

        $current_date = date("Y-m-d H:i:s");
        $current_date_index = 0;
        $found_current = false;

        foreach ($th_games as $index => $game) {
            if (strtotime($current_date) < strtotime($this->parse_game_date($game['GameDate']))) {
                $current_date_index = $index;
                $found_current = true;
                break;
            }
        }

        if (!$found_current) {
            $current_date_index = count($th_games) - 1;
        }

        $display_games = array_slice($th_games, $current_date_index, $future);
        $remaining = $total - count($display_games);

        if ($remaining > 0) {
            $previous_games = array_slice($th_games, max(0, $current_date_index - $remaining), $remaining);
            $display_games = array_merge($previous_games, $display_games);
        }

        $remaining = $total - count($display_games);
        if ($remaining > 0) {
            $next_games = array_slice($th_games, $current_date_index + $future, $remaining);
            $display_games = array_merge($display_games, $next_games);
        }

        return array_map(function ($game) {
            $full_date = $this->parse_game_date($game['GameDate']);
            $timestamp = strtotime($full_date);

            $game_status = self::GAME_STATUS[$game['GameResult']] ?? self::GAME_STATUS[9];
            return [
                'FieldAbbe' => $game['FieldAbbe'],
                'GameSno' => $game['GameSno'],
                'GameDate' => $game['GameDate'],
                'HomeTeamCode' => $game['HomeTeamCode'],
                'VisitingTeamCode' => $game['VisitingTeamCode'],
                'HomeTeamName' => $game['HomeTeamName'],
                'VisitingTeamName' => $game['VisitingTeamName'],
                'HomeScore' => $game['HomeScore'],
                'VisitingScore' => $game['VisitingScore'],
                'HomeTeamImg' => $game['HomeTeamImg'] ?: (self::DEFAULT_IMAGES[$game['HomeTeamCode']] ?? ''),
                'VisitingImg' => $game['VisitingImg'] ?: (self::DEFAULT_IMAGES[$game['VisitingTeamCode']] ?? ''),
                'GameResultName' => $game_status, // Sử dụng giá trị từ GAME_STATUS
                'GameToday' => (date("Y-m-d") === date("Y-m-d", $timestamp)) ? 'T' : 'F'
            ];
        }, $display_games);
    }

    private function parse_game_date(string $date_str): string
    {
        if (preg_match('/(\d{2})\/(\d{2})/', $date_str, $matches)) {
            return date('Y-m-d', strtotime(date('Y') . "-{$matches[1]}-{$matches[2]}"));
        }
        return '';
    }
}