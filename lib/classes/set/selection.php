<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\Basket;

class set_selection extends set_abstract
{
    /**
     * @var Application
     */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param  Basket        $Basket
     * @return set_selection
     */
    public function load_basket(Basket $Basket)
    {
        foreach ($Basket->getElements() as $basket_element) {
            $this->add_element($basket_element->getRecord($this->app));
        }

        return $this;
    }

    /**
     * @param array $rights
     * @param array $sbas_rights
     *
     * @return set_selection
     */
    public function grep_authorized(array $rights = [], array $sbas_rights = [])
    {
        $to_remove = [];

        foreach ($this->get_elements() as $id => $record) {
            $base_id = $record->getBaseId();
            $sbas_id = $record->getDataboxId();
            $record_id = $record->getRecordId();
            if (! $rights) {
                if ($this->app->getAclForUser($this->app->getAuthenticatedUser())->has_hd_grant($record)) {
                    continue;
                }

                if ($this->app->getAclForUser($this->app->getAuthenticatedUser())->has_preview_grant($record)) {
                    continue;
                }
                if ( ! $this->app->getAclForUser($this->app->getAuthenticatedUser())->has_access_to_base($base_id)) {
                    $to_remove[] = $id;
                    continue;
                }
            } else {
                foreach ($rights as $right) {
                    if ( ! $this->app->getAclForUser($this->app->getAuthenticatedUser())->has_right_on_base($base_id, $right)) {
                        $to_remove[] = $id;
                        continue;
                    }
                }
                foreach ($sbas_rights as $right) {
                    if ( ! $this->app->getAclForUser($this->app->getAuthenticatedUser())->has_right_on_sbas($sbas_id, $right)) {
                        $to_remove[] = $id;
                        continue;
                    }
                }
            }

            try {
                $connsbas = $record->getDatabox()->get_connection();

                $sql = 'SELECT record_id
                FROM record
                WHERE ((status ^ ' . $this->app->getAclForUser($this->app->getAuthenticatedUser())->get_mask_xor($base_id) . ')
                        & ' . $this->app->getAclForUser($this->app->getAuthenticatedUser())->get_mask_and($base_id) . ')=0
                AND record_id = :record_id';

                $stmt = $connsbas->prepare($sql);
                $stmt->execute([':record_id' => $record_id]);
                $num_rows = $stmt->rowCount();
                $stmt->closeCursor();

                if ($num_rows == 0) {
                    $to_remove[] = $id;
                }
            } catch (\Exception $e) {

            }
        }
        foreach ($to_remove as $id) {
            $this->offsetUnset($id);
        }

        return $this;
    }

    /**
     * @param array   $lst
     * @param Boolean $flatten_groupings
     *
     * @return set_selection
     */
    public function load_list(array $lst, $flatten_groupings = false)
    {
        foreach ($lst as $basrec) {
            $basrec = explode('_', $basrec);
            if (count($basrec) == 2) {
                try {
                    $record = new record_adapter($this->app, (int) $basrec[0], (int) $basrec[1], $this->get_count());
                } catch (\Exception $e) {
                    continue;
                }
                if ($record->isStory() && $flatten_groupings === true) {
                    foreach ($record->getChildren() as $rec) {
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
     * @return array<int>
     */
    public function get_distinct_sbas_ids()
    {
        $ret = [];
        foreach ($this->get_elements() as $record) {
            $sbas_id = $record->getDataboxId();
            $ret[$sbas_id] = $sbas_id;
        }

        return $ret;
    }
}
