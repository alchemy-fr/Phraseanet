<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Loader;

require_once __DIR__ . "/../../../../vendor/symfony/class-loader/Symfony/Component/ClassLoader/UniversalClassLoader.php";

use Symfony\Component\ClassLoader\UniversalClassLoader;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Autoloader extends UniversalClassLoader
{

  /**
   * An array of path to check
   * @var type
   */
  private $paths = array();
  private $classmap = array();

  /**
   * Construct a new phrasea Autoloader
   * Because some custom classes from library folder might be
   * overwritten in config folder
   * Phraseanet Loader look classes in configuration folders first
   * then check library folder if no classes where matched
   */
  public function __construct()
  {
    $this->paths['config'] = __DIR__ . '/../../../../config/classes/';
    $this->paths['library'] = __DIR__ . '/../../../classes/';

    $getComposerClassMap = function()
            {
              return require realpath(__DIR__ . '/../../../../vendor/.composer/autoload_classmap.php');
            };

    $this->classmap = $getComposerClassMap();
  }

  /**
   * {@inheritdoc}
   */
  public function findFile($class)
  {
    if (!$file = $this->checkFile($class))
    {
      $file = parent::findFile($class);
    }

    return $file;
  }

  /**
   * Add a path to look for autoloading phraseanet classes
   * @param string $name
   * @param string $path
   */
  public function addPath($name, $path)
  {
    $this->paths[$name] = \p4string::addEndSlash($path);
  }

  /**
   * Check whether a class with $class name exists
   * foreach declared paths
   * @param string $class
   * @return mixed string|null
   */
  private function checkFile($classname)
  {
    if (isset($this->classmap[$classname]))
    {
      return $this->classmap[$classname];
    }

    $normalized_classname = str_replace('_', '/', $classname);

    foreach ($this->paths as $path)
    {
      $file = $path . $normalized_classname . '.class.php';

      if (file_exists($file))
      {
        return $file;
      }
    }
  }

  /**
   * Get Paths where classes are checked for autoloading
   * @return Array
   */
  public function getPaths()
  {
    return $this->paths;
  }

}
