<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

/**
 *
 * @package     Appbox
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class appbox_register
{
    /**
     *
     * @var appbox
     */
    protected $appbox;

    /**
     * Construct an Appbox_Register object which will give use infos
     * about the current registrations on the provided appbox
     *
     * @param  appbox          $appbox
     * @return appbox_register
     */
    public function __construct(appbox $appbox)
    {
        $this->appbox = $appbox;

        return $this;
    }

    /**
     * Add a registration request for a user on a collection
     *
     * @param  User_Interface  $user
     * @param  collection      $collection
     * @return appbox_register
     */
    public function add_request(User_Interface $user, collection $collection)
    {
        $sql = "INSERT INTO demand (date_modif, usr_id, base_id, en_cours, refuser)
      VALUES (now(), :usr_id , :base_id, 1, 0)";
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id'  => $user->get_id(), ':base_id' => $collection->get_base_id()));
        $stmt->closeCursor();

        return $this;
    }

    /**
     * Return an array of collection objects where provided
     * user is waiting for approbation
     *
     * @param  User_Interface $user
     * @return array
     */
    public function get_collection_awaiting_for_user(Application $app, User_Interface $user)
    {
        $sql = 'SELECT base_id FROM demand WHERE usr_id = :usr_id AND en_cours="1" ';
        $stmt = $this->appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':usr_id' => $user->get_id()));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $ret = array();
        foreach ($rs as $row) {
            $ret[] = collection::get_from_base_id($app, $row['base_id']);
        }

        return $ret;
    }

    /**
     * Remove all registration older than a month
     *
     * @param  appbox          $appbox
     * @return appbox_register
     */
    public static function clean_old_requests(appbox $appbox)
    {
        $lastMonth = new DateTime('-1 month');
        $sql = "delete from demand where date_modif < :lastMonth";
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute(array(':lastMonth' => $lastMonth->format(DATE_ISO8601)));
        $stmt->closeCursor();

        return;
    }
}
