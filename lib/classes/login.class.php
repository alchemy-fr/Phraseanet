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

        if ($bases) {
            foreach ($bases as $base) {
                if ($base['inscript']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function get_register_link()
    {
        $demandLinkBox = '';

        if (self::register_enabled()) {
            $demandLinkBox = '<a href="/login/register/" rel="external" class="link pointer" id="register-tab">' . _('login:: register') . '</a>';
        }

        return $demandLinkBox;
    }

    public function get_guest_link()
    {
        $inviteBox = '';

        if (phrasea::guest_allowed()) {
            $inviteBox = '<a class="link" rel="external" href="/prod/?nolog=1">' . _('login:: guest Access') . '</a>';
        }

        return $inviteBox;
    }
}
