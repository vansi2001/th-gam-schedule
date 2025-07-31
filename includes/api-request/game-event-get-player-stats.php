<?php

namespace Th_Game_Schedule\includes\api_request;

use WP_Query;

//取得球員數據排行
class Game_Event_Get_Player_Stats
{
    public static $_instance = NULL;

    private $team_total_games = 0;

    public static function instance()
    {
        if (is_null(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }

    public function __construct()
    {
        $team_stats = $this->get_cpbl_team_stats();
        $team_total_games = $team_stats['GameCnt'];
        $this->team_total_games = $team_total_games;
    }

    //取得投手資料&格式化
    public function get_pitcher_stats()
    {
        $players_data = $this->get_cbpl_team_players();
        $player_images = $this->query_roster_list($players_data);
        $player_groups = $this->classify_player($players_data, $player_images);
        $pitcher_stats = $this->format_pitcher_stats($player_groups['pitcher']);

        return $pitcher_stats;
    }

    //取得打者資料&格式化
    public function get_batter_stats()
    {
        $players_data = $this->get_cbpl_team_players();
        $player_images = $this->query_roster_list($players_data);
        $player_groups = $this->classify_player($players_data, $player_images);
        $batter_stats = $this->format_batter_stats($player_groups['batter']);

        return $batter_stats;
    }

    //cURL CBPL API 取得球隊戰績
    private function get_cpbl_team_stats()
    {
        $year = date("Y");
        $kind_code = "A";
        $url = "https://statsapi.cpbl.com.tw/Api/Record/GetTeamRecord?year=" . $year . "&kindCode=" . $kind_code . "&seasonCode=0";
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
            $team_index = array_search('AKP011', array_column($response_data['ResponseDto'], 'BaseTeamCode'));
            if ($team_index !== false) {
                return $response_data['ResponseDto'][$team_index];
            } else {
                return [];
            }
        } else {
            return [];
        }
    }

    //cURL CBPL API 取得球隊選手
    private function get_cbpl_team_players()
    {
        $year = date("Y");
        $kind_code = "A";
        $team_no = "AKP011";

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
            return $this->remove_duplicates_team_players($response_data['ResponseDto']);
        } else {
            return [];
        }
    }

    //以背號查詢post_type = roster_list取得球員圖片
    private function query_roster_list($ori_data)
    {
        $meta_query_condition = array();
        foreach ($ori_data as $player_value) {
            $uniform_no = $player_value['UniformNo'];
            array_push($meta_query_condition, $uniform_no);
        }
        $meta_query['relation'] = 'OR';

        $args = array(
            'posts_per_page' => -1,
            'post_type' => array('roster_list'),
            'meta_query' => array(
                'key' => 'number',
                'value' => $meta_query_condition,
                'compare' => 'IN',
            ),
            'meta_key' => 'number',
        );
        $query = new WP_Query($args);
        $images  = array();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $uniform_no = get_post_meta(get_the_ID(), 'number', true);
                if (has_post_thumbnail()) {
                    $images[$uniform_no] =  get_the_post_thumbnail_url(null, 'medium');
                }
            }
        }
        return $images;
    }

    //分類 投手、打者
    private function classify_player($ori_data, $player_images)
    {
        $pitcher = [];
        $batter = [];

        foreach ($ori_data as $player_value) {
            $uniform_no = $player_value['UniformNo'];
            if (isset($player_images[$uniform_no])) {
                $player_value['PlayerImage'] = $player_images[$uniform_no];
            } else {
                $player_value['PlayerImage'] = "";
            }
            if ($player_value["DefendStation"] == "1") {
                array_push($pitcher, $player_value);
            } else {
                array_push($batter, $player_value);
            }
        }

        return array(
            "pitcher" => $pitcher,
            "batter" => $batter
        );
    }

    //整理投手數據
    private function format_pitcher_stats($players)
    {
        $players_detail_stats = [];
        foreach ($players as $player_value) {
            $detail_stats = $this->query_pitcher_stats($player_value['Acnt']);
            if (!empty($detail_stats)) {
                $total_state = array(
                    "Era" => 0,
                    "TotalWins" => 0,
                    "TotalRelief" => 0,
                    "TotalSaveOk" => 0,
                    "StrikeOutCnt" => 0,
                    "TotalInningPitchedCnt" => 0,
                    "UniformNo" => $player_value['UniformNo'],
                    "PlayerImage" => $player_value['PlayerImage'],
                );
                foreach ($detail_stats as $single_game) {
                    $total_state["PitcherName"] = $single_game["PitcherName"];
                    $total_state["Era"] = $single_game["Era"];
                    $total_state["TotalWins"] = $single_game["TotalWins"];
                    $total_state["TotalRelief"] = $single_game["TotalRelief"];
                    $total_state["TotalSaveOk"] = $single_game["TotalSaveOk"];
                    $total_state["TotalInningPitchedCnt"] = $single_game["TotalInningPitchedCnt"];
                    $total_state["StrikeOutCnt"] += $single_game["StrikeOutCnt"];
                }
                array_push($players_detail_stats, $total_state);
            }
        }

        //防禦率 
        $Era = $this->sort_pitcher_stats($players_detail_stats, 'Era', 'asc', false);
        //勝投
        $TotalWins = $this->sort_pitcher_stats($players_detail_stats, 'TotalWins', 'desc', true);
        //三振
        $StrikeOutCnt = $this->sort_pitcher_stats($players_detail_stats, 'StrikeOutCnt', 'desc', true);
        //中繼
        $TotalRelief = $this->sort_pitcher_stats($players_detail_stats, 'TotalRelief', 'desc', true);
        //救援
        $TotalSaveOk = $this->sort_pitcher_stats($players_detail_stats, 'TotalSaveOk', 'desc', true);

        return array(
            'Era' => $Era,
            'TotalWins' => $TotalWins,
            'StrikeOutCnt' => $StrikeOutCnt,
            'TotalRelief' => $TotalRelief,
            'TotalSaveOk' => $TotalSaveOk,
        );
    }

    //排序投手數據
    private function sort_pitcher_stats($pitcher_stats, $stats_type, $order_by, $remove_zero)
    {
        usort($pitcher_stats, function ($a, $b) use ($stats_type, $order_by) {
            if ($a[$stats_type] == $b[$stats_type])  return 0;
            if ($order_by == 'desc')  return ($a[$stats_type] > $b[$stats_type]) ? -1 : 1;
            return ($a[$stats_type] > $b[$stats_type]) ? 1 : -1;
        });
        $return_format_data = array();
        foreach ($pitcher_stats as $stats_value) {
            if (count($return_format_data) >= 5) break;
            if ($remove_zero && $stats_value[$stats_type] == 0) continue;
            if ($stats_type == 'Era') {
                if ($stats_value['TotalInningPitchedCnt'] >= $this->team_total_games * 0.8) {
                    array_push(
                        $return_format_data,
                        array(
                            'UniformNo' => $stats_value['UniformNo'],
                            'PitcherName' => $stats_value['PitcherName'],
                            'PlayerImage' => $stats_value['PlayerImage'],
                            'stats' => number_format($stats_value[$stats_type], 2, '.', ''),
                        )
                    );
                } else {
                    continue;
                }
            } else {
                array_push(
                    $return_format_data,
                    array(
                        'UniformNo' => $stats_value['UniformNo'],
                        'PitcherName' => $stats_value['PitcherName'],
                        'PlayerImage' => $stats_value['PlayerImage'],
                        'stats' => $stats_value[$stats_type],
                    )
                );
            }
        }
        return $return_format_data;
    }

    //查詢投手數據
    private function query_pitcher_stats($acnt)
    {
        $year = date("Y");
        $kind_code = "A";
        $url = "https://statsapi.cpbl.com.tw/Api/Record/GetPitcherData?year=" . $year . "&kindCode=" . $kind_code . "&acnt=" . $acnt;
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
        }
    }

    //整理打者數據
    private function format_batter_stats($players)
    {
        $players_detail_stats = [];
        foreach ($players as $player_value) {
            $detail_stats = $this->query_batter_stats($player_value['Acnt']);
            if (!empty($detail_stats)) {
                $total_state = array(
                    "Avg" => 0,
                    "TotalHittingCnt" => 0,
                    "RunBattedINCnt" => 0,
                    "StealBaseOKCnt" => 0,
                    "TotalHomeRunCnt" => 0,
                    "PlateAppearances" => 0,
                    "UniformNo" => $player_value['UniformNo'],
                    "PlayerImage" => $player_value['PlayerImage'],
                );
                foreach ($detail_stats as $single_game) {
                    $total_state["HitterName"] = $single_game["HitterName"];
                    $total_state["Avg"] = $single_game["Avg"];
                    $total_state["TotalHittingCnt"] = $single_game["TotalHittingCnt"];
                    $total_state["TotalHomeRunCnt"] = $single_game["TotalHomeRunCnt"];
                    $total_state["PlateAppearances"] += $single_game["PlateAppearances"];
                    $total_state["StealBaseOKCnt"] += $single_game["StealBaseOKCnt"];
                    $total_state["RunBattedINCnt"] += $single_game["RunBattedINCnt"];
                }
                array_push($players_detail_stats, $total_state);
            }
        }

        //打擊率 
        $Avg = $this->sort_batter_stats($players_detail_stats, 'Avg', 'desc', true);
        //安打
        $TotalHittingCnt = $this->sort_batter_stats($players_detail_stats, 'TotalHittingCnt', 'desc', true);
        //打點
        $RunBattedINCnt = $this->sort_batter_stats($players_detail_stats, 'RunBattedINCnt', 'desc', true);
        //盜壘
        $StealBaseOKCnt = $this->sort_batter_stats($players_detail_stats, 'StealBaseOKCnt', 'desc', true);
        //全壘打
        $TotalHomeRunCnt = $this->sort_batter_stats($players_detail_stats, 'TotalHomeRunCnt', 'desc', true);

        return array(
            'Avg' => $Avg,
            'TotalHittingCnt' => $TotalHittingCnt,
            'RunBattedINCnt' => $RunBattedINCnt,
            'StealBaseOKCnt' => $StealBaseOKCnt,
            'TotalHomeRunCnt' => $TotalHomeRunCnt,
        );
    }

    //排序打者數據
    private function sort_batter_stats($batter_stats, $stats_type, $order_by, $remove_zero)
    {
        usort($batter_stats, function ($a, $b) use ($stats_type, $order_by) {
            if ($a[$stats_type] == $b[$stats_type])  return 0;
            if ($order_by == 'desc')  return ($a[$stats_type] > $b[$stats_type]) ? -1 : 1;
            return ($a[$stats_type] > $b[$stats_type]) ? 1 : -1;
        });
        $return_format_data = array();
        foreach ($batter_stats as $stats_value) {
            if (count($return_format_data) >= 5) break;
            if ($remove_zero && $stats_value[$stats_type] == 0) continue;
            if ($stats_type == 'Avg') {
                if ($stats_value['PlateAppearances'] >= $this->team_total_games * 2.7) {
                    array_push(
                        $return_format_data,
                        array(
                            'UniformNo' => $stats_value['UniformNo'],
                            'BatterName' => $stats_value['HitterName'],
                            'PlayerImage' => $stats_value['PlayerImage'],
                            'stats' => number_format($stats_value[$stats_type], 3, '.', ''),
                        )
                    );
                } else {
                    continue;
                }
            } else {
                array_push(
                    $return_format_data,
                    array(
                        'UniformNo' => $stats_value['UniformNo'],
                        'BatterName' => $stats_value['HitterName'],
                        'PlayerImage' => $stats_value['PlayerImage'],
                        'stats' => $stats_value[$stats_type],
                    )
                );
            }
        }

        return $return_format_data;
    }

    //查詢打者數據
    private function query_batter_stats($acnt)
    {
        $year = date("Y");
        $kind_code = "A";

        $url = "https://statsapi.cpbl.com.tw/Api/Record/GetHitterData?year=" . $year . "&kindCode=" . $kind_code . "&acnt=" . $acnt;
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
        }
    }

    // 移除重複球隊選手資料
    private function remove_duplicates_team_players($data)
    {
        $unique_data = [];
	$acnt_seen = [];

	foreach ($data as $record) {
            if (!in_array($record['Acnt'], $acnt_seen)) {
                $unique_data[] = $record;
		$acnt_seen[] = $record['Acnt'];
	    }
	}

	return $unique_data;
    }
}
