<?php

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

    public function getTagname()
    {
        return '';
    }
}
