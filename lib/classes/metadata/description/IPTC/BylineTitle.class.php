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
class metadata_description_IPTC_BylineTitle extends metadata_Abstract implements metadata_Interface
{

  const SOURCE = '/rdf:RDF/rdf:Description/IPTC:By-lineTitle';
  const NAME_SPACE = 'IPTC';
  const TAGNAME = 'By-lineTitle';
  const MAX_LENGTH = 32;
  const TYPE = self::TYPE_STRING;
  const MULTI = true;


}


