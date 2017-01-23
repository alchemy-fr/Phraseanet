<?php

namespace Alchemy\Phrasea\Setup\Action;

class ActionResult
{

    /**
     * @var string[]
     */
    private $messages = [];

    /**
     * @var mixed|null
     */
    private $data;

    /**
     * @var bool
     */
    private $success;

    /**
     * @param bool $success
     * @param null|mixed $data
     * @param string[] $messages
     */
    public function __construct($success, $data = null, array $messages = [])
    {
        $this->success = (bool) $success;
        $this->data = $data;
        $this->messages = $messages;
    }

    /**
     * @return \string[]
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @return mixed|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return boolean
     */
    public function isSuccess()
    {
        return $this->success;
    }

}
