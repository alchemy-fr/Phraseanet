<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

class module_console_systemClearCache extends Command
{

  public function __construct($name = null)
  {
    parent::__construct($name);

    $this->setDescription('Empty cache directories, clear Memcached, Redis if avalaible');

    return $this;
  }

  public function execute(InputInterface $input, OutputInterface $output)
  {
    $files = $dirs = array();
    $finder = new Finder();
    $finder
            ->files()
            ->exclude('.git')
            ->exclude('.svn')
            ->in(array(
                __DIR__ . '/../../../../tmp/cache_minify/'
                , __DIR__ . '/../../../../tmp/cache_twig/'
            ))
    ;
    $count = 1;
    foreach ($finder as $file)
    {
      $files[$file->getPathname()] = $file->getPathname();
      $count++;
    }

    $finder = new Finder();
    $finder
            ->directories()
            ->in(array(
                __DIR__ . '/../../../../tmp/cache_minify'
                , __DIR__ . '/../../../../tmp/cache_twig'
            ))
            ->exclude('.git')
            ->exclude('.svn')
    ;
    foreach ($finder as $file)
    {
      $dirs[$file->getPathname()] = $file->getPathname();
      printf('%4d) %s' . PHP_EOL, $count, $file->getPathname());
      $count++;
    }

    foreach ($files as $file)
    {
      unlink($file);
    }
    foreach ($dirs as $dir)
    {
      rmdir($dir);
    }

    if(setup::is_installed())
    {
      $registry = registry::get_instance();
      $cache = cache_adapter::get_instance($registry);
      if($cache->ping())
      {
        $cache->flush();
      }
    }

    $output->write('Finished !', true);

    return;
  }

}
