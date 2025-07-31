<?php

namespace Th_Game_Schedule;

defined('ABSPATH') || exit;

class Autoloader
{
    public static function run_autoloader()
    {
        spl_autoload_register(array(__CLASS__, 'classes_autoloader'));
    }

    private static function classes_autoloader($class_name)
    {
        if (0 !== strpos($class_name, __NAMESPACE__)) {
            return;
        }

        $file_name = strtolower(
            preg_replace(
                array('/\b' . __NAMESPACE__ . '\\\/', '/([a-z])([A-Z])/', '/_/', '/\\\/'),
                array('', '$1-$2', '-', DIRECTORY_SEPARATOR),
                $class_name
            )
        );

        $file = THGAMES_DIR_PATH . DIRECTORY_SEPARATOR . $file_name . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
}
