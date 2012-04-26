<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper;

use Alchemy\Phrasea\Core;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Helper
{
    /**
     *
     * @var \Alchemy\Phrasea\Core\Kernel
     */
    protected $core;

    /**
     *
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     *
     * @param Kernel $kernel
     * @return Helper
     */
    public function __construct(Core $core, Request $Request)
    {
        $this->core = $core;
        $this->request = $Request;

        return $this;
    }

    /**
     *
     * @return \Alchemy\Phrasea\Core
     */
    public function getCore()
    {
        return $this->core;
    }

    /**
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
