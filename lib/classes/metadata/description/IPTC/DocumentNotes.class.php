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
class metadata_description_IPTC_DocumentNotes extends metadata_Abstract implements metadata_Interface
{

  const SOURCE = '/rdf:RDF/rdf:Description/IPTC:DocumentNotes';
  const NAME_SPACE = 'IPTC';
  const TAGNAME = 'DocumentNotes';
  const MAX_LENGTH = 1024;
  const TYPE = self::TYPE_STRING;
  const MULTI = false;


}


