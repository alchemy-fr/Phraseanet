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

class TfBits extends AbstractTag
{
    protected $Id = 'tf-bits';
    protected $Name = 'tf-bits';
    protected $FullName = 'Phraseanet::tf-bits';
    protected $GroupName = 'Phraseanet';
    protected $g0 = 'Phraseanet';
    protected $g1 = '';
    protected $g2 = '';
    protected $Type = '';
    protected $Writable = false;
    protected $Description = 'The color depth of the file (if available)';

}
