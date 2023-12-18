<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Report;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Entities\User;


class ReportConnections extends Report
{
    private $appKey;

    /* those vars will be set once by computeVars() */
    private $name = null;
    private $sql = null;
    private $columnTitles = [];
    private $keyName = null;


    public function getColumnTitles()
    {
        $this->computeVars();
        return $this->columnTitles;
    }

    public function getKeyName()
    {
        $this->computeVars();
        return $this->keyName;
    }

    public function getName()
    {
        $this->computeVars();
        return $this->name;
    }

    public function setAppKey($appKey)
    {
        $this->appKey = $appKey;

        return $this;
    }

    public function getAllRows($callback)
    {
        $app = $this->getDatabox()->getPhraseApplication();
        $userRepository = $app['repo.users'];

        $this->computeVars();
        $stmt = $this->databox->get_connection()->executeQuery($this->sql, []);
        while (($row = $stmt->fetch())) {
            if ((empty($this->parms['group']) || $this->parms['group'] == 'user')) {
                try {
                    /** @var User $user */
                    $user = $userRepository->find($row['usrid']);
                    $row['user']  = $user->getDisplayName();
                    $row['email'] = $user->getEmail();
                } catch (\Exception $e) {

                }
            }

            $callback($row);
        }
        $stmt->closeCursor();
    }

    private function computeVars()
    {
        if(!is_null($this->name)) {
            // vars already computed
            return;
        }

        switch($this->parms['group']) {
            case null:
                $this->name = "Connections";
                $this->columnTitles = ['id', 'date', 'usrid', 'user', 'email', 'fonction', 'societe', 'activite', 'pays', 'nav', 'version', 'os', 'res', 'ip', 'user_agent'];
                if($this->parms['anonymize']) {
                    $sql = "SELECT `id`, `date`,\n"
                        . "        `usrid`, '-' AS `user`, '-' AS `email`, '-' AS `fonction`, '-' AS `societe`, '-' AS `activite`, '-' AS `pays`,\n"
                        . "        `nav`, `version`, `os`, `res`, `ip`, `user_agent` FROM `log`\n"
                        . " WHERE {{GlobalFilter}}";
                }
                else {
                    $sql = "SELECT `id`, `date`,\n"
                        . "        `usrid`, `user`, '-' AS `email`, `fonction`, `societe`, `activite`, `pays`,\n"
                        . "        `nav`, `version`, `os`, `res`, `ip`, `user_agent` FROM `log`\n"
                        . " WHERE {{GlobalFilter}}";
                }
                $this->keyName = null;
                break;
            case 'user':
                $this->name = "Connections per user";
                $this->columnTitles = ['user_id', 'user', 'email', 'fonction', 'societe', 'activite', 'pays', 'min_date', 'max_date', 'nb'];
                if($this->parms['anonymize']) {
                    $sql = "SELECT `usrid`, '-' AS `user`, '-' AS `email`, '-' AS `fonction`, '-' AS `societe`, '-' AS `activite`, '-' AS `pays`,\n"
                        . "        MIN(`date`) AS `dmin`, MAX(`date`) AS `dmax`, SUM(1) AS `nb` FROM `log`\n"
                        . " WHERE {{GlobalFilter}}\n"
                        . " GROUP BY `usrid`\n"
                        . " ORDER BY `nb` DESC";
                }
                else {
                    $sql = "SELECT `usrid`, `user`, '-' AS `email`, `fonction`, `societe`, `activite`, `pays`,\n"
                        . "        MIN(`date`) AS `dmin`, MAX(`date`) AS `dmax`, SUM(1) AS `nb` FROM `log`\n"
                        . " WHERE {{GlobalFilter}}\n"
                        . " GROUP BY `usrid`\n"
                        . " ORDER BY `nb` DESC";
                }
                $this->keyName = 'usrid';
                break;
            case 'nav':
            case 'nav,version':
            case 'os':
            case 'os,nav':
            case 'os,nav,version':
            case 'res':
                $this->name = "Connections per " . $this->parms['group'];
                $groups  = explode(',', $this->parms['group']);
                $qgroups = implode(
                    ',',
                    array_map(function($g) {return '`'.$g.'`';}, $groups)
                );
                $this->columnTitles = $groups;
                $this->columnTitles[] = 'nb';
                $sql = "SELECT " . $qgroups . ", SUM(1) AS `nb` FROM `log`\n"
                    . " WHERE {{GlobalFilter}}\n"
                    . " GROUP BY " . $qgroups . "\n"
                    . " ORDER BY `nb` DESC"
                ;
                $this->keyName = null;
                break;
            default:
                throw new InvalidArgumentException('invalid "group" argument');
                break;
        }

        $filter = "`usrid`>0";
        // next line : comment to disable "site", to test on an imported dataset from another instance
        $filter .= "  AND `site` = " . $this->databox->get_connection()->quote($this->appKey);

        if($this->parms['dmin']) {
            $filter .= "\n  AND `log`.`date` >= " . $this->databox->get_connection()->quote($this->parms['dmin']);
        }
        if($this->parms['dmax']) {
            $filter .= "\n  AND `log`.`date` <= " . $this->databox->get_connection()->quote($this->parms['dmax'] . " 23:59:59");
        }

        $this->sql = str_replace('{{GlobalFilter}}', $filter, $sql);

        // file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) %s\n", __FILE__, __LINE__, var_export($this->sql, true)), FILE_APPEND);
    }

}
