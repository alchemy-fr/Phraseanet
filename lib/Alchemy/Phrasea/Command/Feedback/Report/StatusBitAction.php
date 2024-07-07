<?php

namespace Alchemy\Phrasea\Command\Feedback\Report;

use Twig_Environment;

class StatusBitAction extends Action implements ActionInterface
{
    private $bit;

    public function __construct(Twig_Environment $twig, array $action_conf)
    {
        parent::__construct($twig, $action_conf);
        $bit = (int)($sbit = trim($action_conf['status_bit']));
        // already sanitized
//        if($bit < 4 || $bit > 31) {
//            throw new ConfigurationException(sprintf("bad status bit (%s)", $sbit));
//        }
        $this->bit = $bit;
    }

    public function addAction(array &$actions, array $context)
    {
        if(!array_key_exists('status', $actions)) {
            $actions['status'] = [];
        }
        $actions['status'][] = [
            "bit" => $this->bit,
            "state" => !!trim($this->getValue($context))
        ];
    }
}
