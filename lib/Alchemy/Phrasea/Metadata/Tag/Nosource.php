<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Metadata\Tag;

use PHPExiftool\Driver\AbstractTag;

class Nosource extends AbstractTag
{
    protected $Id = '';
    protected $Name = '';
    protected $FullName = '';
    protected $GroupName = '';
    protected $g0 = '';
    protected $g1 = '';
    protected $g2 = '';
    protected $Type = '';
    protected $Writable = false;
    protected $Description = 'An empty source';

    /**
     * @param string $name
     */
    public function __construct($name = '')
    {
	    $this->Name = $name;
    }

    public function getTagname()
    {
        return $this->Name;
    }
}
