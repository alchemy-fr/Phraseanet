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
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class metadata_description_IFD0_ProfileEmbedPolicy extends metadata_Abstract implements metadata_Interface
{
  const SOURCE = '/rdf:RDF/rdf:Description/IFD0:ProfileEmbedPolicy';
  const NAME_SPACE = 'IFD0';
  const TAGNAME = 'ProfileEmbedPolicy';
  const MAX_LENGTH = 0;
  const TYPE = self::TYPE_INT32U;
  const MANDATORY = false;
  const MULTI = false;
  const READONLY = false;

  public static function available_values()
  {
    return array(
        '0' => 'Allow Copying'
        , '1' => 'Embed if Used'
        , '2' => 'Never Embed'
        , '3' => 'No Restrictions'
    );
  }

}
