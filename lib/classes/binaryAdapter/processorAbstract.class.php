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
 * Abstract processor object for binaryAdapter
 *
 * @package     binaryAdapter
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class binaryAdapter_processorAbstract extends binaryAdapter_abstract implements binaryAdapter_processorInterface
{

  /**
   *
   * @var system_file
   */
  protected $binary;
  /**
   *
   * @var string
   */
  protected $binary_name = 'GV_pdf2swf';

  /**
   * Constructor
   *
   * @param registry $registry
   * @return binaryAdapter_processorAbstract
   */
  public function __construct(registry $registry)
  {
    if (!$this->binary_name)
      throw new Exception('/!\ binary_name must be set to use this class');
    if ($this->binary == null)
    {
      $binary = new SplFileObject($registry->get($this->binary_name));
      if (!$binary->isExecutable())
        throw new Exception($this->binary_name . ' is not executable');
      $this->binary = $binary;
    }
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
    $this->set_options($options);
    $this->process($origine, $dest);

    if (!$dest)
      $dest = $origine->getPathname();

    return new system_file($dest);
  }

  protected abstract function process(system_file $raw_datas, $dest);

  /**
   * Escape file arguments
   *
   * @param string $arg
   * @param string $file_option
   * @return string
   */
  protected function escapeshellargs($arg, $file_option='')
  {
    $file = escapeshellcmd($arg) . $file_option;

    return escapeshellarg($file);
  }

  /**
   * Launch provided command in shell, returns stdout
   *
   * @param string $cmd
   * @return array
   */
  protected function shell_cmd($cmd)
  {

    if ($this->debug)
    {
      $message = "****************************************************";
      $message .= "\n\t\t*\n\t\t*\tExecution commande :\n\t\t*\n\t\t*\t\t";
      $message .= $cmd;
      $message .= "\n\t\t*\n\t\t";
      $message .= "****************************************************";
      $this->log($message);
    }

    $system = system_server::get_platform();

    if ($system == 'WINDOWS')
      $cmd = 'start /B /WAIT /LOW ' . $cmd;

    exec($cmd, $return);

    return $return;
  }

  /**
   *
   * @param string $pathfile
   * @param string $extension
   * @return string
   */
  protected function set_extension($pathfile, $extension)
  {
    $pathinfo = pathinfo($pathfile);
    $current_extension = isset($pathinfo['extension']) ?
            $pathinfo['extension'] : '';

    return mb_substr(
            $pathfile, 0,
            (mb_strlen($pathfile) - mb_strlen($current_extension))
    )
    . $extension;
  }

}
