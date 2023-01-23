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
use Alchemy\Phrasea\Model\Entities\User;

class MailRequestAccountDelete extends AbstractMailWithLink
{
    const MAIL_SKIN = 'warning';

    /** @var User */
    private $user;

    /**
     * Set the user owner
     *
     * @param User $userOwner
     */
    public function setUserOwner(User $userOwner)
    {
        $this->user = $userOwner;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject()
    {
        return $this->app->trans('Email:deletion:request:subject Delete account confirmation', [], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        if (!$this->user) {
            throw new LogicException('You must set a user before calling getMessage');
        }

        return $this->app->trans("Email:deletion:request:message Hello %civility% %firstName% %lastName%.
            We have received an account deletion request for your account on %urlInstance%, please confirm this deletion by clicking on the link below.
            If you are not at the origin of this request, please change your password as soon as possible %resetPassword%
            Link is valid for one hour.", [
            '%civility%' => $this->getOwnerCivility(),
            '%firstName%'=> $this->user->getFirstName(),
            '%lastName%' => $this->user->getLastName(),
            '%urlInstance%' => '<a href="'.$this->getPhraseanetURL().'">'.$this->getPhraseanetURL().'</a>',
            '%resetPassword%' => '<a href="'.$this->app->url('reset_password').'">'.$this->app->url('reset_password').'</a>',
        ], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonText()
    {
        return $this->app->trans('Email:deletion:request:textButton Delete my account', [], 'messages', $this->getLocale());
    }

    /**
     * {@inheritdoc}
     */
    public function getButtonURL()
    {
        return $this->url;
    }

    /**
     * {@inheritdoc}
     */
    public function getMailSkin()
    {
        return self::MAIL_SKIN;
    }

    private function getOwnerCivility()
    {
        if (!$this->user) {
            throw new LogicException('You must set a user before calling getMessage');
        }

        $civilities = [
            User::GENDER_MISS => 'Miss',
            User::GENDER_MRS  => 'Mrs',
            User::GENDER_MR   => 'Mr',
        ];

        if (array_key_exists($this->user->getGender(), $civilities)) {
            return $civilities[$this->user->getGender()];
        } else {
            return '';
        }
    }
}
