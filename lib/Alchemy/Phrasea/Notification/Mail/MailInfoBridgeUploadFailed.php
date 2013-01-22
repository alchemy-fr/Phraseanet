<?php

namespace Alchemy\Phrasea\Notification\Mail;

class MailInfoBridgeUploadFailed extends AbstractMailWithLink
{
    private $adapter;
    private $reason;

    public function setAdapter($adapter)
    {
        $this->adapter = $adapter;
    }
    public function setReason($reason)
    {
        $this->reason = $reason;
    }

    public function subject()
    {
        return sprintf(
            _('Upload failed on %s'),
            $this->app['phraseanet.registry']->get('GV_homeTitle')
        );
    }

    public function message()
    {
        return _('An upload on %s failed, the resaon is : %s', $this->adapter, $this->reason);
    }

    public function buttonText()
    {
    }

    public function buttonURL()
    {
    }
}
