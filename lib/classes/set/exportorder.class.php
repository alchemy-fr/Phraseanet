<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class set_exportorder extends set_export
{

    /**
     *
     * @param  Int     $from_usr_id
     * @param  String  $usage
     * @param  String  $deadline
     * @return boolean
     * @return $order_id
     */
    public function order_available_elements($from_usr_id, $usage, $deadline)
    {
        $Core = bootstrap::getCore();

        $lst = $this->get_orderable_lst();

        $conn = connection::getPDOConnection();

        $date = phraseadate::format_mysql(new DateTime($deadline));

        $conn->beginTransaction();

        $usage = p4string::cleanTags($usage);

        try {

            $sql = 'INSERT INTO `order`
                (`id`, `usr_id`, `created_on`, `usage`, `deadline`)
                VALUES
                (null, :from_usr_id, NOW(), :usage, :deadline)';

            $params = array(
                ':from_usr_id' => $from_usr_id
                , ':usage'       => $usage
                , ':deadline'    => $deadline
            );
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $stmt->closeCursor();

            $order_id = $conn->lastInsertId();


            $sql = 'INSERT INTO order_elements
                  (id, order_id, base_id, record_id, order_master_id)
                  VALUES
                  (null, :order_id, :base_id, :record_id, null)';
            $stmt = $conn->prepare($sql);

            foreach ($lst as $basrec) {
                $basrec = explode('_', $basrec);

                $record = new record_adapter($basrec[0], $basrec[1]);
                $base_id = $record->get_base_id();
                $record_id = $basrec[1];

                $params = array(
                    ':order_id'  => $order_id
                    , ':base_id'   => $base_id
                    , ':record_id' => $record_id
                );
                $stmt->execute($params);
            }

            $stmt->closeCursor();
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();

            return false;
        }

        $evt_mngr = eventsmanager_broker::getInstance(appbox::get_instance($Core), $Core);

        $params = array(
            'order_id' => $order_id,
            'usr_id'   => $from_usr_id
        );

        $evt_mngr->trigger('__NEW_ORDER__', $params);

        return $order_id;
    }

    /**
     *
     * @return Array
     */
    protected function get_orderable_lst()
    {
        $ret = array();
        foreach ($this as $download_element) {
            foreach ($download_element->get_orderable() as $bool) {
                if ($bool === true) {
                    $ret[] = $download_element->get_serialize_key();
                }
            }
        }

        return $ret;
    }

    /**
     *
     * @param  Int  $admins
     * @param  Int  $base_id
     * @return Void
     */
    public static function set_order_admins($admins, $base_id)
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $conn = $appbox->get_connection();
        $conn->beginTransaction();
        try {
            $user_query = new User_Query($appbox);
            $result = $user_query->on_base_ids(array($base_id))
                    ->who_have_right(array('order_master'))
                    ->execute()->get_results();

            foreach ($result as $user) {
                $user->ACL()->update_rights_to_base($base_id, array('order_master' => false));
            }

            foreach ($admins as $admin) {
                $user = User_Adapter::getInstance($admin, $appbox);
                $user->ACL()->update_rights_to_base($base_id, array('order_master' => true));
            }

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollBack();
        }

        return;
    }
}
