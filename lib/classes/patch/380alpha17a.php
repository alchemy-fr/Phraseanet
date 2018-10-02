<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class patch_380alpha17a extends patchAbstract
{
    /** @var string */
    private $release = '3.8.0-alpha.17';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

    /**
     * {@inheritdoc}
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $auth = $app['conf']->get('authentication');

        if (isset($auth['captcha']) && isset($auth['captcha']['trials-before-failure'])) {
            $auth['captcha']['trials-before-display'] = $auth['captcha']['trials-before-failure'];
            unset($auth['captcha']['trials-before-failure']);
        }

        if (isset($auth['auto-create']) && isset($auth['auto-create']['enabled'])) {
            unset($auth['auto-create']['enabled']);
        }

        $app['conf']->set('authentication', $auth);

        return true;
    }
}
