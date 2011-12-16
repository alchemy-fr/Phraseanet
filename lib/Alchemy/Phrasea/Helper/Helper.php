<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper;

use Alchemy\Phrasea\Kernel;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Helper
{

  private $kernel;

  /**
   *
   * @param Kernel $kernel
   * @return Helper 
   */
  public function __construct(Kernel $kernel)
  {
    $this->kernel = $kernel;

    return $this;
  }

  /**
   *
   * @return Alchemy\Phrasea\Kernel 
   */
  public function getKernel()
  {
    return $this->kernel;
  }

}
