<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Fractal;

use League\Fractal\Resource\ResourceInterface;
use League\Fractal\Scope;
use League\Fractal\TransformerAbstract;

class ResourceTransformerAccessibleScope extends Scope
{
    /**
     * @return TransformerAbstract
     */
    public function getResourceTransformer()
    {
        $transformer = $this->resource->getTransformer();

        if ($transformer instanceof TransformerAbstract) {
            return $transformer;
        }

        return new CallbackTransformer($transformer);
    }

    /**
     * @param string $scopeIdentifier
     * @param ResourceInterface $resource
     * @return ResourceTransformerAccessibleScope
     */
    public function createChildScope($scopeIdentifier, ResourceInterface $resource)
    {
        $child = new self($this->manager, $resource, $scopeIdentifier);

        $scopeArray = $this->getParentScopes();
        $scopeArray[] = $this->getScopeIdentifier();

        $child->setParentScopes($scopeArray);

        return $child;
    }
}
