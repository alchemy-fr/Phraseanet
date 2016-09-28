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

use League\Fractal\Manager;

class IncludeResolver
{
    /**
     * @var TransformerResolver
     */
    private $transformerResolver;

    public function __construct(TransformerResolver $transformerResolver)
    {
        $this->transformerResolver = $transformerResolver;
    }

    /**
     * @param Manager $manager
     * @return array
     */
    public function resolve(Manager $manager)
    {
        $scope = new ResourceTransformerAccessibleScope($manager, $this->createNullResource());
        $scopes = [];

        $this->appendScopeIdentifiers($scopes, $scope);

        return array_values(array_filter($scopes));
    }

    private function appendScopeIdentifiers(array &$scopes, ResourceTransformerAccessibleScope $scope)
    {
        foreach ($this->figureOutWhichIncludes($scope) as $include) {
            $scopeIdentifier = $scope->getIdentifier($include);

            $scopes[] = $scopeIdentifier;

            $childScope = $scope->createChildScope($include, $this->createNullResource($scopeIdentifier));

            $this->appendScopeIdentifiers($scopes, $childScope);
        }
    }

    private function figureOutWhichIncludes(ResourceTransformerAccessibleScope $scope)
    {
        $transformer = $scope->getResourceTransformer();
        $includes = $transformer->getDefaultIncludes();

        foreach ($transformer->getAvailableIncludes() as $include) {
            if ($scope->isRequested($include)) {
                $includes[] = $include;
            }
        }

        return array_values(array_filter(array_unique($includes)));
    }

    /**
     * @param string $scopeIdentifier
     * @return NullResource
     */
    private function createNullResource($scopeIdentifier = '')
    {
        return new NullResource($this->transformerResolver->resolve($scopeIdentifier));
    }
}
