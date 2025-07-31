<?php

namespace Th_Game_Schedule\includes\api_request;

//取得球隊戰績
class Game_Event_Get_Team_Standing
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

    public static function instance()
    {
        if (is_null(self::$_instance)) self::$_instance = new self();
        return self::$_instance;
    }

    /**
     * 取得戰績資料&格式化
     *
     * @param string $season_code - 賽季 ('0' = 全年, '1' = 上半年, '2' = 下半年)
     */
    public function get_team_standings($season_code = '0')
    {
        if (!in_array($season_code, ['0', '1', '2'], true)) return [];
        $standings_data = $this->query_cbpl_team_standings($season_code);
        $format_stndings_data = $this->format_team_standings_data($standings_data);

        return $format_stndings_data;
    }

    //取得當年戰績資料
    private function query_cbpl_team_standings($season_code = '0')
    {
        $year = date("Y");
        $kindCode = "A";
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
            return $response_data['ResponseDto'];
        } else {
            return [];
        }
    }

    //整理戰績資料
    private function format_team_standings_data($response_data)
    {
        if (empty($response_data)) return [];
        $format_data = array();
        foreach ($response_data as $standings_value) {
            if (isset($this->default_img[$standings_value['BaseTeamCode']])) {
                array_push($format_data, array(
                    'Ranking' => $standings_value['Ranking'],
                    'BaseTeamCode' => $standings_value['BaseTeamCode'],
                    'GameResultWCnt' => $standings_value['GameResultWCnt'],
                    'GameResultLCnt' => $standings_value['GameResultLCnt'],
                    'GameResultTCnt' => $standings_value['GameResultTCnt'],
                    'Pct' => number_format($standings_value['Pct'], 3, '.', ''),
                    'GB' => $standings_value['GB'] == null ? '-' : $standings_value['GB'],
                    'BaseTeamImg' => $this->default_img[$standings_value['BaseTeamCode']]
                ));
            }
        }
        usort($format_data, [$this, 'sort_ranking']);
        return $format_data;
    }

    //排序Ranking
    private function sort_ranking($a, $b)
    {
        if ($a['Ranking'] == $b['Ranking']) {
            if ($a['BaseTeamCode'] == 'AKP011') {
                return -1;
            } else {
                return 1;
            }
        }
        return ($a['Ranking'] > $b['Ranking']) ? 1 : -1;
    }
}
