<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Notification;

use Alchemy\Phrasea\Exception\InvalidArgumentException;

class Receiver implements ReceiverInterface
{
    private $name;
    private $email;

    public function __construct($name, $email)
    {
        $this->name = $name;
        $this->email = $email;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Creates a Receiver given a User
     *
     * @param \User_Adapter $user
     *
     * @return Receiver
     *
     * @throws InvalidArgumentException In case no valid email is found for user
     */
    public static function fromUser(\User_Adapter $user)
    {
        if (!\Swift_Validate::email($user->get_email())) {
            throw new InvalidArgumentException(sprintf(
                'User provided does not have a valid e-mail address (%s)',
                $user->get_email()
            ));
        }

        return new static($user->get_display_name(), $user->get_email());
    }
}
