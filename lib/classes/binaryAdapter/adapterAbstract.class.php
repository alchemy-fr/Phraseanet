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
 * Abastract adapter object for binaryAdapter
 *
 * @package     binaryAdapter
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class binaryAdapter_adapterAbstract extends binaryAdapter_abstract implements binaryAdapter_adapterInterface
{

  /**
   *
   * @param registry $registry
   * @return binaryAdapter_adapterAbstract
   */
  public function __construct(registry $registry)
  {
    $this->registry = $registry;

    return $this;
  }

  /**
   *
   * @param system_file $origine
   * @param string $dest
   * @param array $options
   * @return system_file
   */
  public function execute(system_file $origine, $dest, Array $options)
  {
    $processors = array();
    foreach ($this->processors as $k => $proc)
    {
      try
      {
        $processors[$k] = new $proc($this->registry);
        $this->log("" . $proc . " disponible");
      }
      catch (Exception $e)
      {
        unset($processors[$k]);
        $this->log($proc . " indisponible : " . $e->getMessage());
      }
    }

    $done = $resized = false;
    while (!$done && count($processors) > 0)
    {
      try
      {
        $proc = array_shift($processors);
        $resized = $proc->execute($origine, $dest, $options);
        $done = true;
        $this->log("effectuee ! ");
      }
      catch (Exception $e)
      {
        $this->log($e->getMessage());
      }
    }
    if ($resized instanceof system_file)

      return $resized;
    throw new Exception('None of the processor where able to perform ' . $this->get_name());
  }

  abstract public function get_name();
}
