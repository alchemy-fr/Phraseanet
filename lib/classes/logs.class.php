<?php

class logs
{
    public static $_last_check = array();

    public static function rotate($filepath)
    {
        $limit = (1024 * 1024 * 20);

        $date_obj = new DateTime('-3 min');
        $check_time = $date_obj->format('U');

        if ( ! isset(self::$_last_check[$filepath]) || self::$_last_check[$filepath] < $check_time) {
            clearstatcache();

            if (file_exists($filepath) && filesize($filepath) > $limit) {
                $n = 1;
                while (file_exists($filepath . '.' . $n))
                    $n ++;
                if (copy($filepath, $filepath . '.' . $n)) {
                    $handle = fopen($filepath, 'r+');
                    ftruncate($handle, 0);
                    fclose($handle);
                }
            }

            self::$_last_check[$filepath] = $check_time;
        }

        return;
    }
}
