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

class NoSource extends AbstractTag
{
    protected string $id = 'Phraseanet:no-source';
    protected string $name = 'no-source';
    protected array $description = [
        'en' => 'An empty source',
    ];
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
