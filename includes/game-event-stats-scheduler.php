<?php

namespace Th_Game_Schedule\includes;

use  Th_Game_Schedule\includes\api_request\Game_Event_Get_Team_Standing;
use  Th_Game_Schedule\includes\api_request\Game_Event_Get_Player_Stats;

/**
 * 註冊wordpress排程器，定時撈取中華職棒api並存入wp_options
 * 
 */

class Game_Event_Stats_Scheduler
{
    public static $_instance = NULL;

    public static function instance()
    {
        if (is_null(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }

    public function __construct()
    {
        add_filter('cron_schedules', [$this, 'team_stats_interval']);

        add_action('wp', [$this, 'team_stats_scheduler_register']);

        add_action('team_stats_corn', [$this, 'team_stats_scheduler_execute']);

        // 執行取得數據並更新至options資料表
        // $this->team_stats_scheduler_execute();
    }

    //設定定時器執行時間區隔
    public function team_stats_interval($schedules)
    {
        $schedules['six_hours'] = array(
            'interval' => 6 * 60 * 60, // 60 seconds
            'display' => __('Every 6 hours')
        );
        return $schedules;
    }

    //新增wp_cron job
    public function team_stats_scheduler_register()
    {
        if (!wp_next_scheduled('team_stats_corn')) {
            wp_schedule_event(time(), 'six_hours', 'team_stats_corn');
        }
    }

    //執行儲存查詢api & 儲存資料
    public function team_stats_scheduler_execute()
    {
        $team_standing =  Game_Event_Get_Team_Standing::instance();
        $team_standing_data = $team_standing->get_team_standings();
        $this->update_stats_options('team_standing_d', $team_standing_data);
        $team_standing_data_h1 = $team_standing->get_team_standings('1');
        $this->update_stats_options('team_standing_d_h1', $team_standing_data_h1);
        $team_standing_data_h2 = $team_standing->get_team_standings('2');
        $this->update_stats_options('team_standing_d_h2', $team_standing_data_h2);

        $player_stats = Game_Event_Get_Player_Stats::instance();
        $pitcher_stats = $player_stats->get_pitcher_stats();
        $this->update_stats_options('pitcher_stats_d', $pitcher_stats);

        $batter_stats = $player_stats->get_batter_stats();
        $this->update_stats_options('batter_stats_d', $batter_stats);

        $this->purge_home_page_cache();
    }

    //更新wp_options資料表、並判斷空值不更新
    private function update_stats_options($option_name, $update_data)
    {
        $stats_option = get_option($option_name);
        $stats_option = unserialize($stats_option);
        $save_stats_option = array();

        switch ($option_name) {
            case "team_standing_d":
                $save_stats_option = $update_data;
                break;
            case "team_standing_d_h1":
                $save_stats_option = $update_data;
                break;
            case "team_standing_d_h2":
                $save_stats_option = $update_data;
                break;
            case "pitcher_stats_d":
                $stats_field = [
                    'Era',
                    'TotalWins',
                    'StrikeOutCnt',
                    'TotalRelief',
                    'TotalSaveOk'
                ];
                foreach ($stats_field as $target_stats) {
                    $origin_data = isset($stats_option[$target_stats]) ? $stats_option[$target_stats] : [];
                    $new_data =  isset($update_data[$target_stats]) ? $update_data[$target_stats] : [];
                    if (!empty($origin_data) && empty($new_data)) {
                        $save_stats_option[$target_stats] =  $origin_data;
                    } else {
                        $save_stats_option[$target_stats] =  $new_data;
                    }
                }
                break;
            case "batter_stats_d":
                $stats_field = [
                    "Avg",
                    "TotalHittingCnt",
                    "RunBattedINCnt",
                    "StealBaseOKCnt",
                    "TotalHomeRunCnt"
                ];
                foreach ($stats_field as $target_stats) {
                    $origin_data = isset($stats_option[$target_stats]) ? $stats_option[$target_stats] : [];
                    $new_data =  isset($update_data[$target_stats]) ? $update_data[$target_stats] : [];
                    if (!empty($origin_data) && empty($new_data)) {
                        $save_stats_option[$target_stats] =  $origin_data;
                    } else {
                        $save_stats_option[$target_stats] =  $new_data;
                    }
                }
                break;
        }
        $save_stats_option = serialize($save_stats_option);
        if (!empty($stats_option)) {
            if (!empty($update_data)) {
                update_option($option_name, $save_stats_option);
            }
        } else {
            update_option($option_name, $save_stats_option);
        }
    }

    //使用nginx helper 清除首頁快取
    private function purge_home_page_cache()
    {
        if (!is_plugin_active('nginx-helper/nginx-helper.php')) return;
        global $nginx_purger;
        $nginx_purger->purge_url(trailingslashit(site_url()));
    }
}
