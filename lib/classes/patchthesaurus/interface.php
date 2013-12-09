<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

interface patchthesaurus_interface
{
    public function patch($version, \DOMDocument $domct, \DOMDocument $domth, \connection_interface $connbas, \unicode $unicode);
}
