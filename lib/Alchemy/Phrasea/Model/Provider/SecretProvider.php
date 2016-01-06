<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Model\Provider;

use Alchemy\Phrasea\Model\Entities\Secret;
use Alchemy\Phrasea\Model\Entities\User;

interface SecretProvider
{
    /**
     * Get a secret for a user.
     * @param User $user
     * @return Secret
     */
    public function getSecretForUser(User $user);
}
