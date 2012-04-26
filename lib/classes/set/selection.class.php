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
class set_selection extends set_abstract
{

    /**
     *
     * @return set_selection
     */
    public function __construct()
    {
        $this->elements = array();

        return $this;
    }

    /**
     *
     * @param \Entities\Basket $basket
     * @return set_selection
     */
    public function load_basket(\Entities\Basket $Basket)
    {
        foreach ($Basket->getElements() as $basket_element) {
            $this->add_element($basket_element->getRecord());
        }

        return $this;
    }

    /**
     *
     * @param array $rights
     * @return set_selection
     */
    public function grep_authorized(Array $rights = array(), Array $sbas_rights = array())
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $session = $appbox->get_session();

        $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);

        $to_remove = array();

        foreach ($this->elements as $id => $record) {
            $base_id = $record->get_base_id();
            $sbas_id = $record->get_sbas_id();
            $record_id = $record->get_record_id();
            if ( ! $rights) {
                if ($user->ACL()->has_hd_grant($record)) {
                    continue;
                }

                if ($user->ACL()->has_preview_grant($record)) {
                    continue;
                }
                if ( ! $user->ACL()->has_access_to_base($base_id)) {
                    $to_remove[] = $id;
                    continue;
                }
            } else {
                foreach ($rights as $right) {
                    if ( ! $user->ACL()->has_right_on_base($base_id, $right)) {
                        $to_remove[] = $id;
                        continue;
                    }
                }
                foreach ($sbas_rights as $right) {
                    if ( ! $user->ACL()->has_right_on_sbas($sbas_id, $right)) {
                        $to_remove[] = $id;
                        continue;
                    }
                }
            }

            try {
                $connsbas = $record->get_databox()->get_connection();

                $sql = 'SELECT record_id
                FROM record
                WHERE ((status ^ ' . $user->ACL()->get_mask_xor($base_id) . ')
                        & ' . $user->ACL()->get_mask_and($base_id) . ')=0
                AND record_id = :record_id';

                $stmt = $connsbas->prepare($sql);
                $stmt->execute(array(':record_id' => $record_id));
                $num_rows = $stmt->rowCount();
                $stmt->closeCursor();

                if ($num_rows == 0) {
                    $to_remove[] = $id;
                }
            } catch (Exception $e) {

            }
        }
        foreach ($to_remove as $id) {
            unset($this->elements[$id]);
        }

        return $this;
    }

    /**
     *
     * @param array $lst
     * @return set_selection
     */
    public function load_list(Array $lst, $flatten_groupings = false)
    {
        foreach ($lst as $basrec) {
            $basrec = explode('_', $basrec);
            if (count($basrec) == 2) {
                try {
                    $record = new record_adapter((int) $basrec[0], (int) $basrec[1], count($this->elements));
                } catch (Exception $e) {
                    continue;
                }
                if ($record->is_grouping() && $flatten_groupings === true) {
                    foreach ($record->get_children() as $rec) {
                        $this->add_element($rec);
                    }
                } else {
                    $this->add_element($record);
                }
            }
        }

        return $this;
    }

    /**
     *
     * @return array
     */
    public function get_distinct_sbas_ids()
    {
        $ret = array();
        foreach ($this->elements as $record) {
            $sbas_id = phrasea::sbasFromBas($record->get_base_id());
            $ret[$sbas_id] = $sbas_id;
        }

        return $ret;
    }
}

