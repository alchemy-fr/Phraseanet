<?php

interface patchInterface
{

    function get_release();

    function concern();

    function require_all_upgrades();

    function apply(base &$base);
}
