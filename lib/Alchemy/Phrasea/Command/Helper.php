<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command;

use \databox;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Alchemy\Phrasea\Application;


class Helper {
    const OPTION_DISTINT_VALUES = 0;
    const OPTION_ALL_VALUES     = 1;

    const MATCH_DB_BY_ID         = 1;
    const MATCH_DB_BY_NAME       = 2;
    const MATCH_DB_BY_VIEWNAME   = 4;
    const MATCH_ALL_DB_IF_EMPTY  = 8;
    const MATCH_DEFAULT          = 15;


    /**
     * return an array of databoxes matching cmdline option, where the option can be csv or multiple,
     * and where option can match a dbox by id, name or viewname
     *
     * @param Application $app
     * @param InputInterface $input
     * @param string $optionName    name of the option from input cmdline
     * @param int $method           how to match databoxes (bitmask of Helper::MATCH_*)
     * @return databox[]            key id sbas_id
     *
     * @throw \Exception_Databox_DataboxNotFound
     */
    static public function getDataboxesByIdOrName(Application $app, InputInterface $input, $optionName='databox', $method=self::MATCH_DEFAULT)
    {
        $ret = [];
        $dblist = self::getOptionAsArray($input, $optionName, self::OPTION_DISTINT_VALUES);
        $matchAll = empty($dblist);
        foreach($app->getDataboxes() as $db) {
            if($method & self::MATCH_ALL_DB_IF_EMPTY && $matchAll) {
                $ret[$db->get_sbas_id()] = $db;
                continue;
            }
            if($method & self::MATCH_DB_BY_ID && ($k = array_search((string)($db->get_sbas_id()), $dblist)) !== false) {
                $ret[$db->get_sbas_id()] = $db;
                unset($dblist[$k]);
            }
            if($method & self::MATCH_DB_BY_NAME && ($k = array_search($db->get_dbname(), $dblist)) !== false) {
                $ret[$db->get_sbas_id()] = $db;
                unset($dblist[$k]);
            }
            if($method & self::MATCH_DB_BY_VIEWNAME && ($k = array_search($db->get_viewname(), $dblist)) !== false) {
                $ret[$db->get_sbas_id()] = $db;
                unset($dblist[$k]);
            }
        }

        if(!empty($dblist)) {
            throw new \InvalidArgumentException(sprintf("databox(es) [%s] not found", join(',', $dblist)));
        }

        return $ret;
    }

    /**
     * merge options so one can mix csv-option and/or multiple options
     * ex. with keepUnique = false :  --opt=a,b --opt=c --opt=b  ==> [a,b,c,b]
     * ex. with keepUnique = true  :  --opt=a,b --opt=c --opt=b  ==> [a,b,c]
     *
     * @param InputInterface $input
     * @param string $optionName
     * @param int $option
     * @return array
     */
    static public function getOptionAsArray(InputInterface $input, $optionName, $option)
    {
        $ret = [];
        foreach($input->getOption($optionName) as $v0) {
            foreach(explode(',', $v0) as $v) {
                $v = trim($v);
                if($v !== '' && ($option & self::OPTION_ALL_VALUES || !in_array($v, $ret))) {
                    $ret[] = $v;
                }
            }
        }

        return $ret;
    }

}