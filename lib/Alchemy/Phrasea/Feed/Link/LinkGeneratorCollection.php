<?php

namespace Alchemy\Phrasea\Feed\Link;

use Alchemy\Phrasea\Feed\FeedInterface;
use Alchemy\Phrasea\Exception\InvalidArgumentException;

class LinkGeneratorCollection implements LinkGeneratorInterface
{
    private $generators = array();

    public function pushGenerator(LinkGeneratorInterface $generator)
    {
        $this->generators[] = $generator;
    }

    public function generate(FeedInterface $feed, \User_Adapter $user, $format, $page = null, $renew = false)
    {
        if (null === $generator = $this->findGenerator($feed)) {
            throw new InvalidArgumentException(sprintf('Unable to find a valid generator for %s', get_class($feed)));
        }

        return $generator->generate($feed, $user, $format, $page);
    }

    public function generatePublic(FeedInterface $feed, $format, $page = null)
    {
        if (null === $generator = $this->findGenerator($feed)) {
            throw new InvalidArgumentException(sprintf('Unable to find a valid generator for %s', get_class($feed)));
        }

        return $generator->generatePublic($feed, $format, $page);
    }

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
