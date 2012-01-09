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
class patch_320aa implements patchInterface
{

  /**
   *
   * @var string
   */
  private $release = '3.2.0.0.a1';
  /**
   *
   * @var Array
   */
  private $concern = array(base::APPLICATION_BOX);

  /**
   *
   * @return string
   */
  function get_release()
  {
    return $this->release;
  }

  public function require_all_upgrades()
  {
    return false;
  }

  /**
   *
   * @return Array
   */
  function concern()
  {
    return $this->concern;
  }

  function apply(base &$appbox)
  {
    if(is_file(__DIR__ . '/../../../config/_GV.php'))
      require __DIR__ . '/../../../config/_GV.php';
    require __DIR__ . '/../../../lib/conf.d/_GV_template.inc';

    define('GV_STATIC_URL', '');
    define('GV_sphinx', false);
    define('GV_sphinx_host', '');
    define('GV_sphinx_port', '');
    define('GV_sphinx_rt_host', '');
    define('GV_sphinx_rt_port', '');

    $registry = $appbox->get_registry();

    foreach ($GV as $section => $datas_section)
    {
      foreach ($datas_section['vars'] as $datas)
      {

        $registry->un_set($datas['name']);
        eval('$test = defined("' . $datas["name"] . '");');
        if (!$test)
        {
          continue;
        }
        eval('$val = ' . $datas["name"] . ';');

        $val = $val === true ? '1' : $val;
        $val = $val === false ? '0' : $val;

        if($datas['name'] == 'GV_exiftool' && strpos($val, 'lib/exiftool/exiftool') !== false)
        {
          $val = str_replace('lib/exiftool/exiftool', 'lib/vendor/exiftool/exiftool', $val);
        }

        switch ($datas['type'])
        {
          case registry::TYPE_ENUM_MULTI:
          case registry::TYPE_INTEGER:
          case registry::TYPE_BOOLEAN:
          case registry::TYPE_STRING:
          case registry::TYPE_ARRAY:
            $type = $datas['type'];
            break;
          default:
            $type = registry::TYPE_STRING;
            break;
        }
        $registry->set($datas['name'], $val, $type);
      }
    }
    $registry->un_set('registry_loaded');

    return true;
  }

}
