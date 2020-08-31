<?php

/*
 * This file is part of phrasea-4.0.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Mapping;

class DoubleFieldMapping extends ComplexFieldMapping
{
    /**
     * @var string|null
     */
    private $analyzer = null;

    /**
     * @var string|null
     */
    private $termVector = null;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        parent::__construct($name, self::TYPE_DOUBLE);
    }


    /**
     * @return array
     */
    protected function getProperties()
    {
        $properties = [];

        if ($this->analyzer) {
            $properties['analyzer'] = $this->analyzer;
        }

        if ($this->termVector) {
            $properties['term_vector'] = $this->termVector;
        }

        return array_replace(parent::getProperties(), $properties);
    }
}
