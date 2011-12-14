<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\RequestHandler\Record;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

use Alchemy\Phrasea\RequestHandler\RecordsAbstract as RecordHandler;
use Symfony\Component\HttpFoundation\Request;

class MoveCollection extends RecordHandler
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
   * Constructor
   *
   * @return action_move
   */
  public function __construct(Request $request)
  {
    parent::__construct($request);
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

    if (!$this->is_possible)

      return $this;

    $appbox = \appbox::get_instance();
    $session = $appbox->get_session();
    $user = \User_Adapter::getInstance($session->get_usr_id(), $appbox);

    $this->available_destinations = array_keys($user->ACL()->get_granted_base(array('canaddrecord'), array($this->sbas_id)));

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
   * @param http_request $request
   * @return action_move
   */
  public function execute(Request $request)
  {
    $appbox = \appbox::get_instance();
    $session = $appbox->get_session();
    $user = \User_Adapter::getInstance($session->get_usr_id(), $appbox);

    $base_dest =
            $user->ACL()->has_right_on_base($request->get('base_id'), 'canaddrecord') ?
            $request->get('base_id') : false;

    if (!$this->is_possible())
      throw new Exception('This action is not possible');

    if ($request->get("chg_coll_son") == "1")
    {
      foreach ($this->selection as $record)
      {
        if (!$record->is_grouping())
          continue;
        foreach ($record->get_children() as $child)
        {
          if (!$user->ACL()->has_right_on_base(
                          $child->get_base_id(), 'candeleterecord'))
            continue;
          $this->selection->add_element($child);
        }
      }
    }

    $collection = \collection::get_from_base_id($base_dest);

    foreach ($this->selection as $record)
    {
      $record->move_to_collection($collection, $appbox);
    }

    return $this;
  }
}
