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
}
