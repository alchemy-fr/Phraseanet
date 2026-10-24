<?php

/*
 * This file is part of queue-bundle.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\QueueBundle\DependencyInjection;

use Alchemy\QueueBundle\Queue\QueueRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Extension\Extension;

class QueueExtension extends Extension
{
    /**
     * @param array $config
     * @param ContainerBuilder $container
     * @return QueueConfiguration
     */
    public function getConfiguration(array $config, ContainerBuilder $container): QueueConfiguration
    {
        return new QueueConfiguration();
    }

    /**
     * @param array $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $this->loadInternal($this->processConfiguration($this->getConfiguration($configs, $container), $configs), $container);
    }

    /**
     * Configures the passed container according to the merged configuration.
     *
     * @param array $mergedConfig
     * @param ContainerBuilder $container
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $registry = new Definition(QueueRegistry::class);
        $container->setDefinition('alchemy_queues.registry', $registry);

        $queueConfigurations = $mergedConfig['queues'];

        if ($mergedConfig['logger'] != '') {
            $registry->addMethodCall('setLogger', [ new Reference($mergedConfig['logger']) ]);
        }

        foreach ($queueConfigurations as $name => $configuration) {
            $targetRegistry = $registry;

            if (isset($configuration['registry'])) {
                $targetRegistry = $container->getDefinition($configuration['registry']);
            }

            $targetRegistry->addMethodCall('bindConfiguration', [ $name, $configuration ]);
        }
    }
}
