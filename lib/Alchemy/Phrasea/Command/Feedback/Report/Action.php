<?php

namespace Alchemy\Phrasea\Command\Feedback\Report;

use Twig_Environment;
use Twig_Template;

class Action
{
    /** @var Twig_Template[] */
    private $template = null;

    /**
     * @var array
     */
    private $action_conf;

    public function __construct(Twig_Environment $twig, array $action_conf)
    {
        $this->action_conf = $action_conf;
        $this->template = $twig->createTemplate($action_conf['value']);
    }

    public function getValue(array $context)
    {
        return $this->template->render($context);
    }

}
