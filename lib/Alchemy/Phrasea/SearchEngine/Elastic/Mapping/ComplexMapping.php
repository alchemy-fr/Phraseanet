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

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;

class ComplexMapping extends FieldMapping
{
    /**
     * @var FieldMapping[]
     */
    private $children = [];

    /**
     * @param FieldMapping $child
     * @return FieldMapping
     */
    public function addChild(FieldMapping $child)
    {
        if (isset($this->children[$child->getName()])) {
            throw new \LogicException(sprintf('There is already a "%s" multi field.', $child->getName()));
        }

        if ($child->getType() !== $this->getType() && $this->getType() !== self::TYPE_OBJECT) {
            throw new \LogicException('Child field type must match parent type.');
        }

        return $this->children[$child->getName()] = $child;
    }

    /**
     * @return RawFieldMapping
     */
    public function addRawChild()
    {
        return $this->addChild(new RawFieldMapping($this->getType()));
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return ! empty($this->children);
    }

    /**
     * @return FieldMapping[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return array
     */
    protected function getProperties()
    {
        if (! $this->hasChildren()) {
            return [];
        }

        $properties = [ ];

        foreach ($this->children as $name => $child) {
            $properties[$name] = $child->toArray();
        }

        return $properties;
    }
}
