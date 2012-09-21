<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_303 implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.0.3';

    /**
     *
     * @var Array
     */
    private $concern = array(base::APPLICATION_BOX);

    /**
     *
     * @return string
     */
    public function get_release()
    {
        return $this->release;
    }

    public function require_all_upgrades()
    {
        return false;
    }

    /**
     *
     * @return Array
     */
    public function concern()
    {
        return $this->concern;
    }

    public function apply(base &$appbox, Application $app)
    {
        $this->update_users_log_datas($appbox);
        $this->update_users_search_datas($appbox);

        return true;
    }

    /**
     *
     * @return patch_303
     */
    public function update_users_log_datas(appbox &$appbox)
    {
        $col = array('fonction', 'societe', 'activite', 'pays');

        $f_req = implode(', ', $col);

        $sql = "SELECT usr_id, " . $f_req . " FROM usr";
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $tab_usr[$row['usr_id']] = array(
                'fonction' => $row['fonction'],
                'societe'  => $row['societe'],
                'activite' => $row['activite'],
                'pays'     => $row['pays']
            );
        }

        foreach ($appbox->get_databoxes() as $databox) {
            foreach ($tab_usr as $id => $columns) {
                $f_req = array();
                $params = array(':usr_id' => $id, ':site'   => $appbox->get_registry()->get('GV_sit'));
                foreach ($columns as $column => $value) {
                    $column = trim($column);
                    $f_req[] = $column . " = :" . $column;
                    $params[':' . $column] = $value;
                }
                $f_req = implode(', ', $f_req);
                $sql = "UPDATE log SET " . $f_req . "
                                    WHERE usrid = :usr_id AND site = :site";
                $stmt = $databox->get_connection()->prepare($sql);
                $stmt->execute($params);
                $stmt->closeCursor();
            }
        }

        return $this;
    }

    /**
     *
     * @return patch_303
     */
    public function update_users_search_datas(appbox &$appbox)
    {
        foreach ($appbox->get_databoxes() as $databox) {
            $date_debut = '0000-00-00 00:00:00';

            $sql = 'SELECT MAX(date) as debut FROM `log_search`';

            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if ($row) {
                $date_debut = $row['debut'];
            }

            $sql = 'REPLACE INTO log_search
                                (SELECT null as id, logid as log_id, date, askquest as search,
                                                nbrep as results, coll_id
                                 FROM quest
                                 WHERE `date` > :date)';
            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute(array(':date' => $date_debut));
            $stmt->closeCursor();
        }

        return $this;
    }
}
