<?php

/*
 * This file is part of alchemy/worker-bundle.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\WorkerBundle;

use Alchemy\WorkerBundle\DependencyInjection\WorkerExtension;
use Alchemy\WorkerBundle\DependencyInjection\Compiler\WorkerCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class WorkerBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new WorkerCompilerPass());
    }

    /**
     * @return WorkerExtension
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new WorkerExtension();
    }
}
