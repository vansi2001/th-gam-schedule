<?php

namespace Th_Game_Schedule\includes\shortcodes;

class Game_Event_Player_Stats_Shortcode
{
    public static $_instance = NULL;

    public function __construct()
    {
        // //註冊css js
        add_action('wp_enqueue_scripts', [$this, 'register_css_js_dependencies']);

        //註冊shortcode
        add_shortcode('cbpl_pitcher_stats', [$this, 'register_pitcher_shortcode']);
        add_shortcode('cbpl_batter_stats', [$this, 'register_batter_shortcode']);
    }

    public static function instance()
    {
        if (is_null(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }

    //註冊css js 
    public function register_css_js_dependencies()
    {
        //共用css
        wp_register_style('game_players_stats_css', THGAMES_URL_PATH . '/assets/css/game-players-stats.css');

        //投手
        wp_register_script('game_pitcher_stats_js', THGAMES_URL_PATH . '/assets/js/game-pitcher-stats.js', array('jquery'), '1.0.0', true);

        //打者
        wp_register_script('game_batter_stats_js', THGAMES_URL_PATH . '/assets/js/game-batter-stats.js', array('jquery'), '1.0.0', true);
    }

    //註冊投手shortcode
    public function register_pitcher_shortcode()
    {
        //引用css
        wp_enqueue_style('game_players_stats_css');

        $table_id = 'pitcher_' . uniqid();
        $default_img = THGAMES_URL_PATH . 'assets/img/default-player.png';

        //引用js
        wp_enqueue_script('game_pitcher_stats_js');

        $pitcher_stats = get_option('pitcher_stats_d');
        $pitcher_stats = unserialize($pitcher_stats);
        $stats_tab = ["Era", "TotalWins", "StrikeOutCnt", "TotalRelief", "TotalSaveOk"];
        ob_start();
?>
        <div class="players-stats-wrapper" id="<?php echo $table_id ?>">
            <div class="players-stats-content">
                <div class="players-stats-table">
                    <div class="players-stats-thead">
                        <div class="players-stats-th pitcher-stats-btn">
                            <div class="stats-tab-active" data-tab="Era">防禦率</div>
                        </div>
                        <div class="players-stats-th pitcher-stats-btn">
                            <div class="" data-tab="TotalWins">勝投</div>
                        </div>
                        <div class="players-stats-th pitcher-stats-btn">
                            <div class="" data-tab="StrikeOutCnt">三振</div>
                        </div>
                        <div class="players-stats-th pitcher-stats-btn">
                            <div class="" data-tab="TotalRelief">中繼</div>
                        </div>
                        <div class="players-stats-th pitcher-stats-btn">
                            <div class="" data-tab="TotalSaveOk">救援</div>
                        </div>
                    </div>
                    <div class="players-stats-tbody">
                        <?php foreach ($stats_tab as $tab_index => $tab_anme) : ?>
                            <div class="players-stats-display <?php if ($tab_index != 0) echo esc_attr("players-tab-hidden") ?>" data-tabcontent="<?php echo esc_attr($tab_anme) ?>">
                                <?php if (isset($pitcher_stats[$tab_anme]) && !empty($pitcher_stats[$tab_anme])) : ?>
                                    <?php foreach ($pitcher_stats[$tab_anme] as $player_key => $player_stats) : ?>
                                        <div class="players-stats-block">
                                            <?php echo esc_html($player_key + 1) ?>
                                        </div>
                                        <div class="players-stats-block">
                                            <img src="<?php echo  esc_attr($player_stats['PlayerImage'] ? $player_stats['PlayerImage'] : $default_img) ?>" alt="" class="players-stats-img">
                                        </div>
                                        <div class="players-stats-block">
                                            <div class="player-name">
                                                <?php echo esc_html($player_stats['PitcherName']) ?>
                                            </div>
                                        </div>
                                        <div class="players-stats-block">
                                            <?php echo esc_html($player_stats['stats']) ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <div class="players-stats-block players-stats-ndata">
                                        尚無成績
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php
        return ob_get_clean();
    }

    //註冊打者shortcode
    public function register_batter_shortcode()
    {
        //引用css
        wp_enqueue_style('game_players_stats_css');

        //引用js
        wp_enqueue_script('game_batter_stats_js');

        $default_img = THGAMES_URL_PATH . 'assets/img/default-player.png';

        $table_id = 'batter_' . uniqid();

        $batter_stats = get_option('batter_stats_d');
        $batter_stats = unserialize($batter_stats);
        $stats_tab = ["Avg", "TotalHittingCnt", "RunBattedINCnt", "StealBaseOKCnt", "TotalHomeRunCnt"];

        ob_start();
    ?>
        <div class="players-stats-wrapper" id="<?php echo $table_id ?>">
            <div class="players-stats-content">
                <div class="players-stats-table">
                    <div class="players-stats-thead">
                        <div class="players-stats-th batter-stats-btn">
                            <div class="stats-tab-active" data-tab="Avg">打擊率</div>
                        </div>
                        <div class="players-stats-th batter-stats-btn">
                            <div class="" data-tab="TotalHittingCnt">安打</div>
                        </div>
                        <div class="players-stats-th batter-stats-btn">
                            <div class="" data-tab="RunBattedINCnt">打點</div>
                        </div>
                        <div class="players-stats-th batter-stats-btn">
                            <div class="" data-tab="StealBaseOKCnt">盜壘</div>
                        </div>
                        <div class="players-stats-th batter-stats-btn">
                            <div class="" data-tab="TotalHomeRunCnt">全壘打</div>
                        </div>
                    </div>
                    <div class="players-stats-tbody">
                        <?php foreach ($stats_tab as $tab_index => $tab_anme) : ?>
                            <div class="players-stats-display <?php if ($tab_index != 0) echo esc_attr("players-tab-hidden") ?>" data-tabcontent="<?php echo esc_attr($tab_anme) ?>">
                                <?php if (isset($batter_stats[$tab_anme]) && !empty($batter_stats[$tab_anme])) : ?>
                                    <?php foreach ($batter_stats[$tab_anme] as $player_key => $player_stats) : ?>
                                        <div class="players-stats-block">
                                            <?php echo esc_html($player_key + 1) ?>
                                        </div>
                                        <div class="players-stats-block">
                                            <img src="<?php echo  esc_attr($player_stats['PlayerImage'] ? $player_stats['PlayerImage'] : $default_img) ?>" alt="" class="players-stats-img">
                                        </div>
                                        <div class="players-stats-block">
                                            <div class="player-name">
                                                <?php echo esc_html($player_stats['BatterName']) ?>
                                            </div>
                                        </div>
                                        <div class="players-stats-block">
                                            <?php echo esc_html($player_stats['stats']) ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <div class="players-stats-block players-stats-ndata">
                                        尚無成績
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
