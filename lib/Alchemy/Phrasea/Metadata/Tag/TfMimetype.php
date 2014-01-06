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

class TfMimetype extends AbstractTag
{
    protected $Id = 'tf-mimetype';
    protected $Name = 'tf-mimetype';
    protected $FullName = 'Phraseanet::tf-mimetype';
    protected $GroupName = 'Phraseanet';
    protected $g0 = 'Phraseanet';
    protected $g1 = '';
    protected $g2 = '';
    protected $Type = '';
    protected $Writable = false;
    protected $Description = 'The mime type of the archived file';

}
