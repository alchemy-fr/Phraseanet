<?php

namespace Alchemy\Phrasea\Command\Developer;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RoutesDumper extends Command
{
    public function __construct()
    {
        parent::__construct('routes:dump');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $result = 0;

        $maxNameLength = 0;
        $maxMethodsLength = 0;

        $data = array();

        foreach ($this->container['routes'] as $name => $route) {
            $methods = implode('|', $route->getMethods());
            $pattern = $route->getPattern();
            $warning = false;

            $maxNameLength = max($maxNameLength, strlen($name));
            $maxMethodsLength = max($maxMethodsLength, strlen($methods));

            if (0 === strpos($name, '_')) {
                $result++;
                $warning = true;
            }

            $data[] = array(
                'name' => $name,
                'methods' => $methods ?: 'ALL',
                'pattern' => $pattern,
                'warning' => $warning
            );
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
