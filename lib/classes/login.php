<?php

use Alchemy\Phrasea\Application;

class login
{

    public function get_cgus(Application $app)
    {
        return databox_cgu::getHome($app);
    }
}
