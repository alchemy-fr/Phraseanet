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

require_once __DIR__ . "/../../../vendor/symfony/src/Symfony/Component/ClassLoader/UniversalClassLoader.php";

use Symfony\Component\ClassLoader\UniversalClassLoader;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Autoloader extends UniversalClassLoader
{

  public function findFile($class)
  {
    if (file_exists(__DIR__ . '/../../../../config/classes/' . str_replace('_', '/', $class) . '.class.php'))
    {
      $file = __DIR__ . '/../../../../config/classes/' . str_replace('_', '/', $class) . '.class.php';
    }
    elseif (file_exists(__DIR__ . '/../../../classes/' . str_replace('_', '/', $class) . '.class.php'))
    {
      $file = __DIR__ . '/../../../classes/' . str_replace('_', '/', $class) . '.class.php';
    }
    else
    {
      $file = parent::findFile($class);
    }

    return $file;
  }

}