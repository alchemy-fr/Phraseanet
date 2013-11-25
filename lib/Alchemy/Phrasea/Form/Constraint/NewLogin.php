<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Form\Constraint;

use Alchemy\Phrasea\Application;
use Symfony\Component\Validator\Constraint;

class NewLogin extends Constraint
{
    public $message = 'This login is already registered';
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        parent::__construct();
    }

    public function isAlreadyRegistered($login)
    {
        $ret = (Boolean) \User_Adapter::get_usr_id_from_login($this->app, $login);

        return $ret;
    }

    public static function create(Application $app)
    {
        return new static($app);
    }
}
