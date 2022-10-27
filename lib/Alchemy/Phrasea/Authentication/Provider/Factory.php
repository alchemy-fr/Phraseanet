<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication\Provider;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;

class Factory
{
    private $generator;
    private $session;

    public function __construct(UrlGenerator $generator, SessionInterface $session)
    {
        $this->generator = $generator;
        $this->session = $session;
    }

    public function build(string $id, string $type, bool $display, string $title, array $options = [])
    {
        $type = implode('', array_map(function ($chunk) {
            return ucfirst(strtolower($chunk));
        }, explode('-', $type)));

        $class_name = sprintf('%s\\%s', __NAMESPACE__, $type);

        if (!class_exists($class_name)) {
            throw new InvalidArgumentException(sprintf('Invalid provider %s', $type));
        }

        /** @var AbstractProvider $o */
        $o = $class_name::create($this->generator, $this->session, $options);   // v1 bc compat : can't change
        $o->setId($id);
        $o->setDisplay($display);
        $o->setTitle($title);
        return $o;
    }
}
