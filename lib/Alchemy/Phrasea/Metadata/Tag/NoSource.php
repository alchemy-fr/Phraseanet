<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Metadata\Tag;

use PHPExiftool\Driver\AbstractTag;

class NoSource extends AbstractTag
{
    protected $Id = 'no-source';
    protected $Name = 'no-source';
    protected $FullName = 'Phraseanet::no-source';
    protected $GroupName = 'Phraseanet';
    protected $g0 = 'Phraseanet';
    protected $g1 = '';
    protected $g2 = '';
    protected $Type = '';
    protected $Writable = false;
    protected $Description = 'An empty source';
    private $fieldName;

    /**
     * @param string $fieldName
     */
    public function __construct($fieldName = '')
    {
	    $this->fieldName = $fieldName;
    }

    public function getFieldName()
    {
        return $this->fieldName;
    }
}
