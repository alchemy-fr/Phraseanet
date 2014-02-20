<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Form\Constraint;

use Alchemy\Phrasea\Application;
use Symfony\Component\Validator\Constraint;

class NewEmail extends Constraint
{
    public $message = 'This email is already bound to an account';
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        parent::__construct();
    }

    public function isAlreadyRegistered($email)
    {
        return (Boolean) $this->app['manipulator.user']->getRepository()->findByEmail($email);
    }

    public static function create(Application $app)
    {
        return new static($app);
    }
}
