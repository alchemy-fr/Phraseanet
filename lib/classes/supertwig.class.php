<?php

class supertwig
{

  protected $ld_path = "";
  protected $tp_path = "";
  protected $twig_extensions = array(
      'Core',
      'Escaper',
      'Sandbox',
      'I18n'
  );
  protected $twig_options = array(
      'debug',
      'trim_blocks',
      'charset',
      'strict_variables',
      'base_template_class',
      'cache',
      'strict_variable',
      'auto_reload'
  );
  protected $extensions = array();
  protected $options = array();
  protected $filters = array();
  protected static $default_vars;
  protected $loader;
  protected $twig;
  protected $template;

  public function __construct($ext = false, $opt = false)
  {
    $browser = browser::getInstance();
    $this->load_default_vars();

    if ($browser->isMobile())
    {
      $this->ld_path = array(
          GV_RootPath . 'config/templates/mobile',
          GV_RootPath . 'templates/mobile'
      );
    }
    else
    {
      $this->ld_path = array(
          GV_RootPath . 'config/templates/web',
          GV_RootPath . 'templates/web'
      );
    }

    $this->loader = new Twig_Loader_Filesystem($this->ld_path);

    if (GV_debug)
    {
      $options = array(
          'debug' => true,
          'strict_variables' => true,
          'trim_blocks' => true,
          'charset' => 'utf-8',
          'auto_reload' => true
      );
    }
    else
    {
      $options = array(
          'cache' => GV_RootPath . 'tmp/cache_twig',
          'debug' => false,
          'strict_variables' => false,
          'trim_blocks' => true,
          'charset' => 'utf-8'
      );
    }

    if (is_array($opt))
      $options = array_merge($options, $opt);

    try
    {
      $this->addOpt($options);
    }
    catch (Exception $e)
    {
      echo $e->getMessage();
    }

    $this->newTwig();

    $extensions = array('I18n' => true);

    if (is_array($ext))
      $extensions = array_merge($extensions, $ext);

    try
    {
      $this->addExt($extensions);
    }
    catch (Exception $e)
    {
      echo $e->getMessage();
    }

    try
    {
      $this->addFilter(array('round' => 'round'));
    }
    catch (Exception $e)
    {
      echo $e->getMessage();
    }
  }

  protected function checkArgs($array, $what, $mes)
  {
    if (!is_array($array))
    {
      throw new Exception("The argument $mes is not an array");
      return false;
    }
    else
    {
      foreach ($array as $key => $value)
      {
        if (!in_array($key, $what))
        {
          throw new Exception("The $mes $key does not exist ... ");
          unset($array[$key]);
        }
      }
    }
    return true;
  }

  protected function addOpt($opt)
  {
    $bool = array('trim_blocks', 'strict_variable', 'debug', 'auto_reload');

    if ($this->checkArgs($opt, $this->twig_options, "option"))
    {
      foreach ($opt as $key => $value)
      {
        if (!is_bool($value) && in_array($key, $bool))
        {
          throw new Exception("Your extension $key must be a boolean");
        }
        else
        {
          $this->options[$key] = $value;
        }
      }
    }
  }

  protected function newTwig()
  {
    if (sizeof($this->options) > 0)
      $this->twig = new Twig_Environment($this->loader, $this->options);
    else
      $this->twig = new Twig_Environment($this->loader);
  }

  protected function addExt($ext)
  {
    if ($ext)
    {
      if ($this->checkArgs($ext, $this->twig_extensions, "extension"))
      {
        foreach ($ext as $key => $value)
        {
          if ($value === true)
          {
            $this->extensions[$key] = $value;
            switch ($key)
            {
              case 'Core':
                $this->twig->addExtension(new Twig_Extension_Core());
                break;

              case 'Escaper':
                $this->twig->addExtension(new Twig_Extension_Escaper());
                break;

              case 'Sandbox':
                $this->twig->addExtension(new Twig_Extension_Sandbox());
                break;

              case 'I18n':
                $this->twig->addExtension(new Twig_Extension_I18n());
                break;
            }
          }
          else
          {
            throw new Exception("Your extension $key must be a boolean");
          }
        }
      }
    }
  }

  public function addFilter($func)
  {
    try
    {
      if (!is_array($func))
      {
        throw new Exception("Your filters variable is not an array");
      }
      else
      {
        foreach ($func as $key => $value)
        {
          $this->twig->addFilter($key, new Twig_Filter_Function($value));
          $this->filters[$key] = $value;
        }
      }
    }
    catch (Exception $e)
    {
      echo $e->getMessage();
    }
  }

  public function render($path, $var)
  {
    $this->tp_path = $path;
    $this->template = $this->twig->loadTemplate($path);

    $var = array_merge(self::$default_vars, $var);

    $rs = $this->template->render($var);

    return $rs;
  }

  public function display($path, $var)
  {
    $this->tp_path = $path;
    $this->template = $this->twig->loadTemplate($path);

    $var = array_merge(self::$default_vars, $var);

    return $this->template->display($var);
  }

  protected function load_default_vars()
  {
    if (!self::$default_vars)
    {
      $session = session::getInstance();
      $browser = browser::getInstance();

      $user = false;
      if (isset($session->usr_id))
        $user = user::getInstance($session->usr_id);

      $date_obj = new DateTime();

      self::$default_vars = array(
          'session' => $session,
          'browser' => $browser,
          'display_chrome_frame' => defined('GV_display_gcf') ? GV_display_gcf : true,
          'user' => $user,
          'current_date' => $date_obj->format(DATE_ATOM),
          'home_title' => GV_homeTitle,
          'meta_description' => GV_metaDescription,
          'meta_keywords' => GV_metaKeywords,
          'maintenance' => GV_maintenance,
      );
    }

    return $this;
  }

}

?>