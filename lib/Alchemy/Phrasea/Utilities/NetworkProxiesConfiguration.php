<?php

namespace Alchemy\Phrasea\Utilities;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

class NetworkProxiesConfiguration
{
    private $congif;

    public function __construct(PropertyAccess $configuration)
    {
        $this->congif = $configuration;
    }

    /**
     * Get only the http-proxy defined in the configuration
     *
     * @return string|null
     */
    public function getHttpProxyConfiguration()
    {
        if ($this->congif->has(['network-proxies', 'http-proxy', 'enabled']) && $this->congif->get(['network-proxies', 'http-proxy', 'enabled'])) {
            $httpProxy = $this->congif->get(['network-proxies', 'http-proxy']);

            $proxy = '';
            if (!empty($httpProxy['user']) && !empty($httpProxy['password'])) {
                $proxy .= $httpProxy['user'] . ':' . $httpProxy['password'];
            }

            if (!empty($httpProxy['host']) && !empty($httpProxy['port'])) {
                if ($proxy != '') {
                    $proxy .= '@';
                }
                $proxy .= $httpProxy['host'] . ':' . $httpProxy['port'];
            }

            return ($proxy == '') ? null : $proxy;
        }

        return null;
    }

    //TODO: get ftp proxy and socket proxy
}
