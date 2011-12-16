<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class supertwig
{

  /**
   *
   * @var string
   */
  protected $ld_path = "";
  /**
   *
   * @var array
   */
  protected $default_vars;
  /**
   *
   * @var Twig_Environment
   */
  protected $twig_object;
  /**
   *
   * @var array
   */
  protected $options;
  /**
   *
   * @var array
   */
  protected $extensions;

  /**
   *
   * @param <type> $extensions
   * @param <type> $options
   * @return supertwig
   */
  public function __construct($extensions = array(), $options = array())
  {
    $browser = Browser::getInstance();
    $this->load_default_vars();
    $registry = registry::get_instance();

    if (($browser->isTablet()))
    {
      $this->ld_path = array(
          $registry->get('GV_RootPath') . 'config/templates/mobile',
          $registry->get('GV_RootPath') . 'templates/mobile'
      );
    }
    elseif ($browser->isMobile())
    {
      $this->ld_path = array(
          $registry->get('GV_RootPath') . 'config/templates/mobile',
          $registry->get('GV_RootPath') . 'templates/mobile'
      );
    }
    else
    {
      $this->ld_path = array(
          $registry->get('GV_RootPath') . 'config/templates/web',
          $registry->get('GV_RootPath') . 'templates/web'
      );
    }


    $default_extensions = array('I18n' => true, 'Optimizer' => true);

    if ($registry->get('GV_debug'))
    {
      $default_options = array(
          'debug' => true,
          'strict_variables' => true,
          'trim_blocks' => true,
          'charset' => 'utf-8',
          'auto_reload' => true
      );
      $default_extensions['Debug'] = true;
    }
    else
    {
      $default_options = array(
          'cache' => $registry->get('GV_RootPath') . 'tmp/cache_twig',
          'debug' => false,
          'strict_variables' => false,
          'trim_blocks' => true,
          'charset' => 'utf-8'
      );
    }

    $options = array_merge($default_options, $options);
    $extensions = array_merge($default_extensions, $extensions);

    try
    {
      $this->set_options($options);
      $this->init_twig();
      $this->set_extensions($extensions);
      $this->addFilter(array('round' => 'round'));
    }
    catch (Exception $e)
    {

    }

    return $this;
  }

  /**
   *
   * @param array $filters
   * @return supertwig
   */
  public function addFilter(Array $filters)
  {
    foreach ($filters as $name => $function)
    {
      $this->twig_object->addFilter($name, new Twig_Filter_Function($function));
    }

    return $this;
  }

  /**
   *
   * @param string $path
   * @param string $var
   * @return string
   */
  public function render($path, $var)
  {
    $template = $this->twig_object->loadTemplate($path);

    $var = array_merge($this->default_vars, $var);

    return $template->render($var);
  }

  /**
   *
   * @param string $path
   * @param string $var
   * @return void
   */
  public function display($path, $var)
  {
    $template = $this->twig_object->loadTemplate($path);

    $var = array_merge($this->default_vars, $var);

    return $template->display($var);
  }

  /**
   *
   * @param array $options
   * @return supertwig
   */
  protected function set_options(Array $options)
  {
    foreach ($options as $key => $value)
    {
      $this->options[$key] = $value;
    }

    return $this;
  }

  /**
   *
   * @return supertwig
   */
  protected function init_twig()
  {
    $loader = new Twig_Loader_Filesystem($this->ld_path);

    if (sizeof($this->options) > 0)
      $this->twig_object = new Twig_Environment($loader, $this->options);
    else
      $this->twig_object = new Twig_Environment($loader);

    return $this;
  }

  /**
   *
   * @param array $extensions
   * @return supertwig
   */
  protected function set_extensions(Array $extensions)
  {
    $twig_lib = array('core', 'escaper', 'optimizer', 'sandbox');
    foreach ($extensions as $name => $boolean)
    {
      $name = strtolower($name);
      $boolean = !!$boolean;
      $this->extensions[$name] = $boolean;

      if ($boolean)
      {
        if (in_array($name, $twig_lib))
          $extension_classname = 'Twig_Extension_' . ucfirst($name);
        else
          $extension_classname = 'Twig_Extensions_Extension_' . ucfirst($name);

        $this->twig_object->addExtension(new $extension_classname());
      }
      else
      {
        $this->twig_object->removeExtension($name);
      }
    }

    return $this;
  }

  /**
   *
   * @return supertwig
   */
  protected function load_default_vars()
  {
    if (!$this->default_vars)
    {
      $appbox = appbox::get_instance();
      $session = $appbox->get_session();
      $browser = Browser::getInstance();
      $registry = $appbox->get_registry();
      $request = new http_request();

      $user = false;
      if ($session->is_authenticated())
      {
        $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
      }

      $core = bootstrap::getCore();
      
      $this->default_vars = array(
          'session' => $session,
          'version_number' => $core->getVersion()->getNumber(),
          'version_name' => $core->getVersion()->getName(),
          'core' => $core,
          'browser' => $browser,
          'request' => $request,
          'display_chrome_frame' => $registry->is_set('GV_display_gcf') ? $registry->get('GV_display_gcf') : true,
          'user' => $user,
          'current_date' => new DateTime(),
          'home_title' => $registry->get('GV_homeTitle'),
          'meta_description' => $registry->get('GV_metaDescription'),
          'meta_keywords' => $registry->get('GV_metaKeywords'),
          'maintenance' => $registry->get('GV_maintenance'),
          'registry' => $registry
      );
    }

    return $this;
  }

}

