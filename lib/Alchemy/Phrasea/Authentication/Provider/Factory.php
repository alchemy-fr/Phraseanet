<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Factory
{
    private $generator;
    private $session;

    public function __construct(UrlGenerator $generator, SessionInterface $session)
    {
        $this->generator = $generator;
        $this->session = $session;
    }

    public function build($name, array $options = [])
    {
        $name = implode('', array_map(function ($chunk) {
            return ucfirst(strtolower($chunk));
        }, explode('-', $name)));

        $class_name = sprintf('%s\\%s', __NAMESPACE__, $name);

        if (!class_exists($class_name)) {
            throw new InvalidArgumentException(sprintf('Invalid provider %s', $name));
        }

        return $class_name::create($this->generator, $this->session, $options);
    }
}
