<?php

use Alchemy\Phrasea\Application;

class login
{

    public function get_cgus(Application $app)
    {
        return databox_cgu::getHome($app);
    }

    public function register_enabled(Application $app)
    {
        require_once __DIR__ . '/deprecated/inscript.api.php';

        $bases = giveMeBases($app);

        if ($bases) {
            foreach ($bases as $base) {
                if ($base['inscript']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function get_register_link(Application $app)
    {
        $demandLinkBox = '';

        if (self::register_enabled($app)) {
            $demandLinkBox = '<a href="/login/register/" rel="external" class="link pointer" id="register-tab">' . _('login:: register') . '</a>';
        }

        return $demandLinkBox;
    }

    public function get_guest_link(Application $app)
    {
        $inviteBox = '';

        if (phrasea::guest_allowed($app)) {
            $inviteBox = '<a class="link" rel="external" href="/prod/?nolog=1">' . _('login:: guest Access') . '</a>';
        }

        return $inviteBox;
    }
}
