<?php
/*
Plugin Name: TSG Hawks Game Schedule
Plugin URI: 
Description: 中華職棒台鋼雄鷹賽程表
Author: Sapido
Version: 2.0.1
Text Domain: th-game-schedule
Requires PHP: 7.4
*/

use Th_Game_Schedule\Autoloader;
use Th_Game_Schedule\includes\Game_Event_Lang;
use Th_Game_Schedule\includes\shortcodes\Game_Event_Calendar_Shortcode;
use Th_Game_Schedule\includes\shortcodes\Game_Event_Recently_Shortcode;
use Th_Game_Schedule\includes\shortcodes\Game_Event_Player_Stats_Shortcode;
use Th_Game_Schedule\includes\shortcodes\Game_Event_Standings_Shortcode;
use Th_Game_Schedule\includes\Game_Event_Stats_Scheduler;
use Th_Game_Schedule\includes\Th_Game_Api;
use Th_Game_Schedule\includes\Th_New_Api;
use Th_Game_Schedule\includes\Th_Test_Api;


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Th_Game_Schedule
{
    public function __construct()
    {
        $this->init();
        new Th_Game_Api;
        new Th_New_Api();
        new Th_Test_Api;
    }

    public function init()
    {
        //外掛資料夾路徑
        if (!defined('THGAMES_DIR_PATH')) {
            define('THGAMES_DIR_PATH', dirname(__FILE__));
        }
        //定義外掛根目錄
        if (!defined('THGAMES_URL_PATH')) {
            define('THGAMES_URL_PATH', plugin_dir_url(__FILE__));
        }

        //定義網站根目錄
        if (!defined('THGAMES_ROOT_PATH')) {
            define('THGAMES_ROOT_PATH', site_url());
        }

        define('THGAMES_PLUGIN_BASE', plugin_basename(__FILE__));

        $this->autoload_init();

        add_action('init', array($this, 'loading_plugin_lang'));
        add_action('plugins_loaded', array($this, 'loading_plugin'));
        add_action('after_setup_theme', array($this, 'stats_scheduler'));
    }

    public function autoload_init()
    {
        require_once THGAMES_DIR_PATH . '/autoloader.php';
        Autoloader::run_autoloader();
    }

    public function loading_plugin_lang()
    {
        $lang = new Game_Event_Lang();
        $lang->load_lang_textdomain();
    }

    public function loading_plugin()
    {
        //賽程表
        Game_Event_Calendar_Shortcode::instance();
        //近期對戰
        Game_Event_Recently_Shortcode::instance();
        
        //戰績表
        Game_Event_Standings_Shortcode::instance();

        //選手數據
        Game_Event_Player_Stats_Shortcode::instance();
    }

    public function stats_scheduler()
    {
        Game_Event_Stats_Scheduler::instance();
    }
}

new Th_Game_Schedule();
