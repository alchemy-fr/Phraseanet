<?php

/*
 * This file is part of alchemy/worker-bundle.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\WorkerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WorkerCompilerPass implements CompilerPassInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition('alchemy_worker.type_based_worker_resolver')) {
            return;
        }

        $resolverDefinition = $container->getDefinition('alchemy_worker.type_based_worker_resolver');
        $factoryServices = $container->findTaggedServiceIds('alchemy_worker.worker_factory');

        foreach ($factoryServices as $id => $tags) {
            foreach ($tags as $tag) {
                $resolverDefinition->addMethodCall('setFactory', [ $tag['message_type'], new Reference($id) ]);
            }
        }
    }
}
