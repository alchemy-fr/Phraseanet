<?php

namespace App\Utils;

use Symfony\Component\Yaml\Yaml;

class Conf
{

    public function get(array $params)
    {
        $content = Yaml::parse(file_get_contents('../config/configuration.yml'));
        $param_size = count($params);

        switch ($param_size){

            case 0:
                return '';
                break;

            case 1:
                return $content[$params[0]];
                break;

            case 2:
                return $content[$params[0]][$params[1]];
                break;

            case 3:
                return $content[$params[0]][$params[1]][$params[2]];
                break;

            case 4:
                return $content[$params[0]][$params[1]][$params[2]][$params[3]];
                break;

            case 5:
                return $content[$params[0]][$params[1]][$params[2]][$params[3]][$params[4]];
                break;

            default:
                return '';
                break;
        }
    }
}
