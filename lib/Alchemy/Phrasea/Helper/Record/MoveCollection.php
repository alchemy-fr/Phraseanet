<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper\Record;

use Alchemy\Phrasea\Core;
use Alchemy\Phrasea\Helper\Record\Helper as RecordHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class MoveCollection extends RecordHelper
{
    /**
     *
     * @var Array
     */
    protected $required_rights = array('candeleterecord');

    /**
     *
     * @var Array
     */
    protected $available_destinations;

    /**
     *
     */
    protected $works_on_unique_sbas = true;

    /**
     *
     * @param  \Alchemy\Phrasea\Core $core
     * @return MoveCollection
     */
    public function __construct(Core $core, Request $Request)
    {
        parent::__construct($core, $Request);
        $this->evaluate_destinations();

        return $this;
    }

    /**
     * Check which collections can receive the documents
     *
     * @return action_move
     */
    protected function evaluate_destinations()
    {
        $this->available_destinations = array();

        if ( ! $this->is_possible) {
            return $this;
        }

        $this->available_destinations = array_keys(
            $this->getCore()->getAuthenticatedUser()->ACL()->get_granted_base(
                array('canaddrecord'), array($this->sbas_id)
            )
        );

        return $this;
    }

    /**
     * Returns an array of base_id
     *
     * @return Array
     */
    public function available_destination()
    {
        return $this->available_destinations;
    }

    public function propose()
    {
        return $this;
    }

    /**
     *
     * @param  http_request $request
     * @return action_move
     */
    public function execute(Request $request)
    {
        $appbox = \appbox::get_instance($this->core);
        $user = $this->getCore()->getAuthenticatedUser();

        $baseId = $request->get('base_id');

        $base_dest =
            $user->ACL()->has_right_on_base($baseId, 'canaddrecord') ?
            $request->get('base_id') : false;

        if ( ! $user->ACL()->has_right_on_base($baseId, 'canaddrecord')) {
            throw new \Exception_Unauthorized(sprintf("%s do not have the permission to move records to %s", $user->get_login()));
        }

        if ( ! $this->is_possible())
            throw new Exception('This action is not possible');

        if ($request->get("chg_coll_son") == "1") {
            foreach ($this->selection as $record) {
                if ( ! $record->is_grouping())
                    continue;
                foreach ($record->get_children() as $child) {
                    if ( ! $user->ACL()->has_right_on_base(
                            $child->get_base_id(), 'candeleterecord'))
                        continue;
                    $this->selection->add_element($child);
                }
            }
        }

        $collection = \collection::get_from_base_id($base_dest);

        foreach ($this->selection as $record) {
            $record->move_to_collection($collection, $appbox);
        }

        return $this;
    }
}
