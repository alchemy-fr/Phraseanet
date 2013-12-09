<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Feed\Link;

use Alchemy\Phrasea\Feed\FeedInterface;
use Alchemy\Phrasea\Exception\InvalidArgumentException;

class LinkGeneratorCollection implements LinkGeneratorInterface
{
    private $generators = [];

    /**
     * Adds a LinkGeneratorInterface to the internal array.
     *
     * @param LinkGeneratorInterface $generator
     */
    public function pushGenerator(LinkGeneratorInterface $generator)
    {
        $this->generators[] = $generator;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(FeedInterface $feed, \User_Adapter $user, $format, $page = null, $renew = false)
    {
        if (null === $generator = $this->findGenerator($feed)) {
            throw new InvalidArgumentException(sprintf('Unable to find a valid generator for %s', get_class($feed)));
        }

        return $generator->generate($feed, $user, $format, $page);
    }

    /**
     * {@inheritdoc}
     */
    public function generatePublic(FeedInterface $feed, $format, $page = null)
    {
        if (null === $generator = $this->findGenerator($feed)) {
            throw new InvalidArgumentException(sprintf('Unable to find a valid generator for %s', get_class($feed)));
        }

        return $generator->generatePublic($feed, $format, $page);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(FeedInterface $feed)
    {
        return null !== $this->findGenerator($feed);
    }

    private function findGenerator(FeedInterface $feed)
    {
        foreach ($this->generators as $generator) {
            if ($generator->supports($feed)) {
                return $generator;
            }
        }

        return null;
    }
}
