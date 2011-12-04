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
 * Processor interface for binaryAdapter
 *
 * @package     binaryAdapter
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
interface binaryAdapter_processorInterface
{
  public function __construct(registry $registry);

  public function execute(system_file $origine, $dest, Array $options);

  public function set_options($options);

  public function log($message);
}
