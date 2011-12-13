<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class module_prod_route_records_feed extends module_prod_route_records_abstract
{

  /**
   *
   * @var Array
   */
  protected $required_sbas_rights = array('bas_chupub');
  /**
   *
   * @var boolean
   */
  protected $works_on_unique_sbas = true;
  protected $flatten_groupings = true;

  public function __construct(Symfony\Component\HttpFoundation\Request $request)
  {
    $appbox = appbox::get_instance();

    parent::__construct($request);

    if ($this->is_single_grouping())
    {
      $record = array_pop($this->selection->get_elements());
      foreach ($record->get_children() as $child)
      {
        $this->selection->add_element($child);
      }
    }

    return $this;
  }

}
