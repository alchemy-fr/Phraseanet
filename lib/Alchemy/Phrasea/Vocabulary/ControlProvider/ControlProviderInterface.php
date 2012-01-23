<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Vocabulary\ControlProvider;

/**
 * ControlProvider Interface
 * 
 * This interface should be used to interconnect vocabularies and metadatas
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
interface ControlProviderInterface
{

  /**
   * @return the type of the ControlProvider
   * 
   * ControlProvider class should be named like {type}Provider 
   * in the ControlProvider namespace
   */
  public static function getType();

  /**
   * @return stringa simple i18n word to reprsent this vocabullary  
   */
  public static function getName();

  /**
   * @return boolean validate an $id in the vocabulary 
   */
  public static function validate($id);

  /**
   * @return string returns the value corresponding to an id
   * @throws \Exception if the $id is invalid
   */
  public static function getValue($id);

  /**
   * Find matching Term in the vocabulary repository
   * 
   * @param string $query A scalar quaery
   * @param \User_Adapter $for_user The user doing the query 
   * @param \databox $on_databox The databox where vocabulary should be requested
   *  
   * @return Doctrine\Common\Collections\ArrayCollection  
   */
  public static function find($query, \User_Adapter $for_user, \databox $on_databox);

}