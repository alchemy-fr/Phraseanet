<?php

namespace Alchemy\Phrasea\Metadata\Tag;

class Nosource extends \PHPExiftool\Driver\Tag
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
        return 'No source';
    }
}
