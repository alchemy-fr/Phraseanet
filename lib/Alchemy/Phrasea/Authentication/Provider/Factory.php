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

    /**
     * @param string $id
     * @param string $type
     * @param bool $display
     * @param string $title
     * @param array $options
     * @return mixed
     *
     * @uses \Alchemy\Phrasea\Authentication\Provider\Facebook          Facebook provider
     * @uses \Alchemy\Phrasea\Authentication\Provider\Github            Github provider
     * @uses \Alchemy\Phrasea\Authentication\Provider\Linkedin          Linkedin provider
     * @uses \Alchemy\Phrasea\Authentication\Provider\PhraseanetOauth   Phraseanet Oauth provider
     * @uses \Alchemy\Phrasea\Authentication\Provider\Twitter           Twitter provider
     * @uses \Alchemy\Phrasea\Authentication\Provider\Viadeo            Viadeo provider
     */
    public function build($id, $type, $display, $title, array $options = [])
    {
        $class_name = sprintf('%s\\%s', __NAMESPACE__, $type);

        if (!class_exists($class_name)) {
            throw new InvalidArgumentException(sprintf('Invalid provider %s', $type));
        }

        /** @var AbstractProvider $class_name */
        return $class_name::create($this->generator, $this->session, $id, $display, $title, $options);
    }
}
