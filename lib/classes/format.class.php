<?php

class format
{

    static public function arr_to_csv_line($arr, $tri_column = false)
    {
        $line = array();
        $tmp = array();
        foreach ($arr as $v) {
            if (is_array($v)) {
                $line[] = self::arr_to_csv_line($v);
            } elseif ($tri_column) {
                $key = array_search($v, $arr);
                unset($arr[$key]);

                if (array_key_exists($key, $tri_column)) {
                    $tmp[$key] = $v;
                }
            }
            else
                $line[] = '"' . str_replace('"', '""', strip_tags($v)) . '"';
        }
        if ($tri_column) {
            foreach ($tri_column as $key => $value) {
                foreach ($tmp as $k => $v) {
                    if ($key == $k) {
                        $line[] = '"' . str_replace('"', '""', strip_tags($v)) . '"';
                    }
                }
            }
        }

        if ($tri_column && count($tri_column) == count($line)) {
            return implode(",", $line);
        } elseif (count($arr) == count($line)) {
            return implode(",", $line);
        }
        else
            throw new Exception('CSV failed');
    }

    static public function arr_to_csv($arr, $tri_column = false)
    {
        $lines = array();

        if ($tri_column) {
            $title = "";
            foreach ($tri_column as $v) {
                if (isset($v['title']))
                    $title .= ( empty($title) ? "" : ",") . '"' . str_replace('"', '""', strip_tags($v['title'])) . '"';
            }
            ! empty($title) ? $lines[] = $title : "";
        }
        foreach ($arr as $v) {
            $lines[] = self::arr_to_csv_line($v, $tri_column);
        }

        return implode("\n", $lines);
    }
}
