<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Service;

use Alchemy\Phrasea\Core;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class ServiceAbstract
{

  protected $name;
  protected $core;
  protected $options;
  protected $configuration;

  public function __construct(Core $core, $name, Array $options)
  {
    $this->core = $core;
    $this->name = $name;
    $this->options = $options;
  }

  /**
   *
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }

  /**
   *
   * @return Array
   */
  public function getOptions()
  {
    return $this->options;
  }

  abstract public static function getMandatoryOptions();

}
