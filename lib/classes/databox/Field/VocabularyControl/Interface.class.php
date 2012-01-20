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
interface databox_Field_VocabularyControl_Interface
{

  public static function getType();

  public static function getName();

  public static function find($query, \User_Adapter $for_user, \databox $on_databox);
}