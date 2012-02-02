<?php

class login
{

  public function get_cgus()
  {
    return databox_cgu::getHome();
  }

  public function register_enabled()
  {
    $registry = registry::get_instance();
    require_once $registry->get('GV_RootPath') . 'lib/classes/deprecated/inscript.api.php';

    $bases = giveMeBases();

    if ($bases)
    {
      foreach ($bases as $base)
      {
        if ($base['inscript'])
        {
          return true;
        }
      }
    }

    return false;
  }

  public function get_language_selector(registryInterface $registry)
  {
    $lngSelect       = '<select name="lng" id="lng-select" onchange="setLanguage();">';
    
    foreach (\Alchemy\Phrasea\Core::getAvailableLanguages() as $code => $language)
    {
      $lngSelect .= '<option value="' . $code . '" ' . ($code == \Session_Handler::get_locale() ? 'selected' : '') . '>' . $language . '</option>';
    }
    
    $lngSelect .= '</select>';

    return $lngSelect;
  }

  public function get_password_link()
  {
//    $findpwd = '';
//    if(GV_find_password )
//    {
    $findpwd = '<a target="_self" class="link" rel="external" href="/login/forgotpwd.php">' . _('login:: Forgot your password') . '</a>';
//    }
    return $findpwd;
  }

  public function get_register_link()
  {
    $demandLinkBox = '';

    if (self::register_enabled())
    {
      $demandLinkBox = '<a href="register.php" rel="external" class="link pointer" id="register-tab">' . _('login:: register') . '</a>';
    }

    return $demandLinkBox;
  }

  public function get_guest_link()
  {
    $inviteBox = '';

    if (phrasea::guest_allowed())
    {
      $inviteBox = '<a class="link" rel="external" href="/prod/index.php?nolog=1">' . _('login:: guest Access') . '</a>';
    }

    return $inviteBox;
  }

}
