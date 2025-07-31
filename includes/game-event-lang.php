<?php

namespace Th_Game_Schedule\includes;

class Game_Event_Lang
{
    public function load_lang_textdomain()
    {
        load_plugin_textdomain(
            'th-game-schedule',
            false,
            '/' . dirname(dirname(plugin_basename(__FILE__))) . '/languages'
        );
    }
}
