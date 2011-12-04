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
 * @package     Databox DCES
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class databox_Field_DCES_Coverage extends databox_Field_DCESAbstract
{

  /**
   *
   * @var string
   */
  protected $label = 'Coverage';
  /**
   *
   * @var string
   */
  protected $definition = 'The spatial or temporal topic of the resource,
                          the spatial applicability of the resource,
                          or the jurisdiction under which the resource
                          is relevant.';
  /**
   *
   * @var string
   */
  protected $URI = 'http://dublincore.org/documents/dces/#coverage';
}
