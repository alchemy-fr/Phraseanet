<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Developer;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouteCollection;

abstract class AbstractRoutesDumper extends Command
{
    protected function dumpRoutes(RouteCollection $routes, InputInterface $input, OutputInterface $output)
    {
        $result = 0;

        $maxNameLength = 0;
        $maxMethodsLength = 0;

        $data = [];

        foreach ($routes as $name => $route) {
            $methods = implode('|', $route->getMethods());
            $pattern = $route->getPattern();
            $warning = false;

            $maxNameLength = max($maxNameLength, strlen($name));
            $maxMethodsLength = max($maxMethodsLength, strlen($methods));

            if (0 === strpos($name, '_')) {
                $result++;
                $warning = true;
            }

            $data[] = [
                'name' => $name,
                'methods' => $methods ?: 'ALL',
                'pattern' => $pattern,
                'warning' => $warning
            ];
        }

        foreach ($data as $route) {
            $line = sprintf('%-'.$maxMethodsLength.'s  %-'.$maxNameLength.'s  %s', $route['methods'], $route['name'], $route['pattern']);

            if ($route['warning']) {
                $line = str_replace(' '.$route['name'].' ', ' <comment>'.$route['name'].'</comment> ', $line);
            }

            $output->writeln($line);
        }

        return $result;
    }
}
