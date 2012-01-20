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
class databox_Field_VocabularyControl
{

  public static function get($type)
  {
    $classname = 'databox_Field_VocabularyControl_' . $type;

    if(!class_exists($classname))
    {
      throw new \Exception('Vocabulary type not found');
    }
    
    return new $classname();
  }

  public static function getAvailable()
  {
    return array(
      new databox_Field_VocabularyControl_User()
    );
  }

}