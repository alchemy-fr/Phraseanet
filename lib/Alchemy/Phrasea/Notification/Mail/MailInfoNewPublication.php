<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Notification\Mail;

use Alchemy\Phrasea\Exception\LogicException;

class MailInfoNewPublication extends AbstractMailWithLink
{
    /** @string */
    private $author;
    /** @string */
    private $title;

    /**
     * Sets the title
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Sets the author
     *
     * @param string $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        if (!$this->title) {
            throw new LogicException('You must set an title before calling getMessage');
        }

        return $this->app->trans('Nouvelle publication : %title%', ['%title%' => $this->title], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        if (!$this->author) {
            throw new LogicException('You must set an author before calling getMessage');
        }
        if (!$this->title) {
            throw new LogicException('You must set an title before calling getMessage');
        }

        return $this->app->trans('%user% vient de publier %title%', ['%user%' => $this->author, '%title%' => $this->title], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return $this->app->trans('View on %title%', ['%title%' => $this->getPhraseanetTitle()], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->url;
    }
}
