<?php

namespace Alchemy\Phrasea\Twig;

class JSUniqueID extends \Twig_Extension
{

    /**
     *
     * @return string
     */
    public function getName()
    {
        return 'alchemy_phrasea_jsuniqueid';
    }

    /**
     *
     * @return array
     */
    public function getFilters()
    {
        return array(
            'JSUniqueID' => new \Twig_Filter_Method($this, 'JSUniqueID'),
        );
    }

    public function JSUniqueID($prefix = null, $suffix = null)
    {
        return $prefix . 'id' . str_replace(array(',', '.'), '-', microtime(true)) . base_convert(mt_rand(0x19A100, 0x39AA3FF), 10, 36) . $suffix;
    }
}
