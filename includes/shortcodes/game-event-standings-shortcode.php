<?php

namespace Th_Game_Schedule\includes\shortcodes;

class Game_Event_Standings_Shortcode
{
    public static $_instance = NULL;

    private $default_img = array(
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

    public function __construct()
    {
        //註冊css js
        add_action('wp_enqueue_scripts', [$this, 'register_css_js_dependencies']);

        //註冊shortcode
        add_shortcode('cbpl_team_standings', [$this, 'register_standings_shortcode']);
    }

    public static function instance()
    {
        if (is_null(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }

    //註冊css js 
    public function register_css_js_dependencies()
    {
        wp_register_style('game_standings_sc_css', THGAMES_URL_PATH . '/assets/css/game-team-standings.css');
        wp_register_script('game_standings_sc_js', THGAMES_URL_PATH . '/assets/js/game-team-standings.js', array('jquery'), '1.0.0', true);
    }

    //註冊shortcode
    public function register_standings_shortcode()
    {

        //引用css
        wp_enqueue_style('game_standings_sc_css');

        //預設球隊代碼
        $default_team_code = ['AKP011', 'AJL011', 'ADD011', 'ACN011', 'AEO011', 'AAA011'];

        $team_standings = get_option('team_standing_d');
        $team_standings = unserialize($team_standings);

        ob_start();
?>
        <div class="team-standings-wrapper">
            <div class="team-standings-content">
                <div class="team-standing-table">
                    <div class="team-standing-thead">
                        <div class="team-standing-block team-standing-th">球隊</div>
                        <div class="team-standing-block team-standing-th">勝</div>
                        <div class="team-standing-block team-standing-th">負</div>
                        <div class="team-standing-block team-standing-th">和</div>
                        <div class="team-standing-block team-standing-th">勝率</div>
                        <div class="team-standing-block team-standing-th">勝差</div>
                    </div>
                    <div class="team-standing-tbody">
                        <?php if (!empty($team_standings)) : ?>
                            <?php foreach ($team_standings as $team_stats) : ?>
                                <div class="team-standing-block <?php echo $team_stats['BaseTeamCode'] != 'AKP011' ? 'block-other-team' : '' ?>">
                                    <img class="team-standing-img" src="<?php echo esc_attr($this->default_img[$team_stats['BaseTeamCode']]) ?>" alt="">
                                </div>
                                <div class="team-standing-block <?php echo $team_stats['BaseTeamCode'] != 'AKP011' ? 'block-other-team' : '' ?>"><?php echo esc_html($team_stats['GameResultWCnt']) ?></div>
                                <div class="team-standing-block <?php echo $team_stats['BaseTeamCode'] != 'AKP011' ? 'block-other-team' : '' ?>"><?php echo esc_html($team_stats['GameResultLCnt']) ?></div>
                                <div class="team-standing-block <?php echo $team_stats['BaseTeamCode'] != 'AKP011' ? 'block-other-team' : '' ?>"><?php echo esc_html($team_stats['GameResultTCnt']) ?></div>
                                <div class="team-standing-block <?php echo $team_stats['BaseTeamCode'] != 'AKP011' ? 'block-other-team' : '' ?>"><?php echo esc_html($team_stats['Pct']) ?></div>
                                <div class="team-standing-block <?php echo $team_stats['BaseTeamCode'] != 'AKP011' ? 'block-other-team' : '' ?>"><?php echo esc_html($team_stats['GB']) ?></div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <?php foreach ($default_team_code as $team_code) : ?>
                                <div class="team-standing-block <?php echo $team_code != 'AKP011' ? 'block-other-team' : '' ?>">
                                    <img class="team-standing-img" src="<?php echo esc_attr($this->default_img[$team_code]) ?>" alt="">
                                </div>
                                <div class="team-standing-block <?php echo $team_code != 'AKP011' ? 'block-other-team' : '' ?>">-</div>
                                <div class="team-standing-block <?php echo $team_code != 'AKP011' ? 'block-other-team' : '' ?>">-</div>
                                <div class="team-standing-block <?php echo $team_code != 'AKP011' ? 'block-other-team' : '' ?>">-</div>
                                <div class="team-standing-block <?php echo $team_code != 'AKP011' ? 'block-other-team' : '' ?>">-</div>
                                <div class="team-standing-block <?php echo $team_code != 'AKP011' ? 'block-other-team' : '' ?>">-</div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
