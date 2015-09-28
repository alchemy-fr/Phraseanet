<?php

namespace Alchemy\Phrasea\Account\Command;

class UpdatePasswordCommand
{
    /**
     * @var string
     */
    private $oldPassword;

    /**
     * @var string
     */
    private $password;

    /**
     * @param string $oldPassword
     * @param string $password
     */
    public function __construct($oldPassword = '', $password = '')
    {
        $this->oldPassword = $oldPassword;
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getOldPassword()
    {
        return $this->oldPassword;
    }

    /**
     * @param string $oldPassword
     */
    public function setOldPassword($oldPassword)
    {
        $this->oldPassword = $oldPassword;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }
}
