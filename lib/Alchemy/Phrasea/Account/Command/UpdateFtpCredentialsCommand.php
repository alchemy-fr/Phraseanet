<?php

namespace Alchemy\Phrasea\Account\Command;

class UpdateFtpCredentialsCommand
{

    private $ftpEnabled;

    private $ftpAddress;

    private $ftpLogin;

    private $ftpPassword;

    private $ftpPassiveMode;

    private $ftpTarget;

    private $ftpFolderPrefix;

    private $ftpDefaultData;

    private $retries;

    /**
     * @return mixed
     */
    public function isEnabled()
    {
        return $this->ftpEnabled;
    }

    /**
     * @param mixed $ftpEnabled
     * @return $this
     */
    public function setEnabled($ftpEnabled)
    {
        $this->ftpEnabled = $ftpEnabled;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->ftpAddress;
    }

    /**
     * @param mixed $ftpAddress
     * @return $this
     */
    public function setAddress($ftpAddress)
    {
        $this->ftpAddress = $ftpAddress;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLogin()
    {
        return $this->ftpLogin;
    }

    /**
     * @param mixed $ftpLogin
     * @return $this
     */
    public function setLogin($ftpLogin)
    {
        $this->ftpLogin = $ftpLogin;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->ftpPassword;
    }

    /**
     * @param mixed $ftpPassword
     * @return $this
     */
    public function setPassword($ftpPassword)
    {
        $this->ftpPassword = $ftpPassword;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPassiveMode()
    {
        return $this->ftpPassiveMode;
    }

    /**
     * @param mixed $ftpPassiveMode
     * @return $this
     */
    public function setPassiveMode($ftpPassiveMode)
    {
        $this->ftpPassiveMode = $ftpPassiveMode;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFolder()
    {
        return $this->ftpTarget;
    }

    /**
     * @param mixed $ftpTarget
     * @return $this
     */
    public function setFolder($ftpTarget)
    {
        $this->ftpTarget = $ftpTarget;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFolderPrefix()
    {
        return $this->ftpFolderPrefix;
    }

    /**
     * @param mixed $ftpFolderPrefix
     * @return $this
     */
    public function setFolderPrefix($ftpFolderPrefix)
    {
        $this->ftpFolderPrefix = $ftpFolderPrefix;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefaultData()
    {
        return $this->ftpDefaultData;
    }

    /**
     * @param mixed $ftpDefaultData
     * @return $this
     */
    public function setDefaultData($ftpDefaultData)
    {
        $this->ftpDefaultData = $ftpDefaultData;

        return $this;
    }

    public function getRetries()
    {
        return $this->retries;
    }

    public function setRetries($retries)
    {
        $this->retries = $retries;
    }
}
