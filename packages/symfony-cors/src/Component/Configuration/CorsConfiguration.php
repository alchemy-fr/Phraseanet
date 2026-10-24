<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Cors\Configuration;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\BooleanNodeDefinition;
use Symfony\Component\Config\Definition\Builder\IntegerNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class CorsConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('alchemy_cors');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode->append($this->getDefaultsSection());
        $rootNode->append($this->getPathsSection());

        return $treeBuilder;
    }

    private function getDefaultsSection()
    {
        $node = new ArrayNodeDefinition('defaults');
        $node->addDefaultsIfNotSet();

        $this->appendPrototypedConfiguration($node);

        return $node;
    }

    private function getPathsSection()
    {
        $node = new ArrayNodeDefinition('paths');

        $node
            ->useAttributeAsKey('path')
            ->normalizeKeys(false);

        /** @var ArrayNodeDefinition $prototype */
        $prototype = $node->prototype('array');

        $this->appendPrototypedConfiguration($prototype);

        return $node;
    }

    private function appendPrototypedConfiguration(ArrayNodeDefinition $node)
    {
        $node
            ->append($this->getAllowCredentials())
            ->append($this->getAllowOrigin())
            ->append($this->getOriginRegex())
            ->append($this->getAllowHeaders())
            ->append($this->getAllowMethods())
            ->append($this->getExposeHeaders())
            ->append($this->getMaxAge())
            ->append($this->getHosts())
        ;
    }

    private function getAllowCredentials()
    {
        $node = new BooleanNodeDefinition('allow_credentials');
        $node->defaultFalse();

        return $node;
    }

    private function getAllowOrigin()
    {
        $node = new ArrayNodeDefinition('allow_origin');

        $node
            ->beforeNormalization()
                ->always(function ($v) {
                    if ($v === '*') {
                        return array('*');
                    }

                    return $v;
                })
            ->end();

        $node->prototype('scalar')->end();

        return $node;
    }

    private function getOriginRegex()
    {
        $node = new BooleanNodeDefinition('origin_regex');
        $node->defaultFalse();

        return $node;
    }

    private function getAllowHeaders()
    {
        $node = new ArrayNodeDefinition('allow_headers');

        $node
            ->beforeNormalization()
                ->always(function ($v) {
                    if ($v === '*') {
                        return array('*');
                    }

                    return $v;
                })
            ->end();

        $node->prototype('scalar')->end();

        return $node;
    }

    private function getAllowMethods()
    {
        $node = new ArrayNodeDefinition('allow_methods');

        $node->prototype('scalar')->end();

        return $node;
    }

    private function getExposeHeaders()
    {
        $node = new ArrayNodeDefinition('expose_headers');

        $node->prototype('scalar')->end();

        return $node;
    }

    private function getMaxAge()
    {
        $node = new IntegerNodeDefinition('max_age');

        $node
            ->defaultValue(0)
            ->min(0)
        ;

        return $node;
    }

    private function getHosts()
    {
        $node = new ArrayNodeDefinition('hosts');

        $node->prototype('scalar')->end();

        return $node;
    }
}
