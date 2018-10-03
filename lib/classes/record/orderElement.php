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

class record_orderElement extends record_adapter
{
    /**
     *
     * @var boolean
     */
    protected $deny;

    /**
     *
     * @var int
     */
    protected $order_master_id;

    /**
     *
     * @param Application $app
     * @param int         $sbas_id
     * @param int         $record_id
     * @param boolean     $deny
     * @param int         $order_master_id
     */
    public function __construct(Application $app, $sbas_id, $record_id, $deny, $order_master_id)
    {
        $this->deny = ! ! $deny;
        $this->order_master_id = $order_master_id;

        parent::__construct($app, $sbas_id, $record_id);

        $this->get_subdefs();

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_order_master_name()
    {
        if ($this->order_master_id) {
            $user = $this->app['repo.users']->find($this->order_master_id);

            return $user->getDisplayName();
        }

        return '';
    }

    /**
     *
     * @return int
     */
    public function get_order_master_id()
    {
        return $this->order_master_id;
    }

    /**
     *
     * @return boolean
     */
    public function get_deny()
    {
        return $this->deny;
    }
}
