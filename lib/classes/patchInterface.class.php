<?php

use Alchemy\Phrasea\Application;

interface patchInterface
{

    public function get_release();

    public function concern();

    public function require_all_upgrades();

    public function apply(base &$base, Application $app);
}
