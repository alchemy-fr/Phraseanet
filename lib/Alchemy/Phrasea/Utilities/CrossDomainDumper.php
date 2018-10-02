<?php

namespace Alchemy\Phrasea\Utilities;

/** Build crossdomain.xml file according to configuration */
class CrossDomainDumper
{
    public function dump(array $configuration)
    {
        $xml = '<?xml version="1.0"?>'.PHP_EOL;
        $xml .= '<!DOCTYPE cross-domain-policy SYSTEM "http://www.macromedia.com/xml/dtds/cross-domain-policy.dtd">'.PHP_EOL;
        $xml .= '<cross-domain-policy>' . PHP_EOL;
        $xml .= $this->getSiteControl($configuration);
        $xml .= $this->getAllowAccess($configuration);
        $xml .= $this->getAllowIdentity($configuration);
        $xml .= $this->getAllowHeader($configuration);
        $xml .= "</cross-domain-policy>";

        return $xml;
    }

    private function getSiteControl(array $conf)
    {
        $xml = '';

        if (isset($conf['site-control'])) {
            $xml = "\t".'<site-control permitted-cross-domain-policies="'.$conf['site-control'].'"/>'.PHP_EOL;
        }

        return $xml;
    }

    private function getAllowAccess(array $conf)
    {
        $xml = '';
        if (!isset($conf['allow-access-from'])) {
            return $xml;
        }
        $allowAccess = $conf['allow-access-from'];
        if (!is_array($allowAccess)) {
            return $xml;
        }

        foreach ($allowAccess as $access) {
            // domain is mandatory
            if (!isset($access['domain'])) {
                continue;
            }

            $domain = $access['domain'];
            $secure = isset($access['secure']) ? $access['secure'] : false;
            $ports = isset($access['to-ports']) ? $access['to-ports'] : false;

            $xml .= "\t".'<allow-access-from domain="'.$domain.'"'. ($ports ? ' to-ports="'.$ports.'"' : '') . ($secure ? ' secure="'.$secure.'"': ''). '/>'.PHP_EOL;
        }

        return $xml;
    }

    private function getAllowIdentity(array $conf)
    {
        $xml = '';
        if (!isset($conf['allow-access-from-identity'])) {
            return $xml;
        }
        $allowAccess = $conf['allow-access-from-identity'];
        if (!is_array($allowAccess)) {
            return $xml;
        }
        foreach ($allowAccess as $access) {
            $algorithm = isset($access['fingerprint-algorithm']) ? $access['fingerprint-algorithm'] : false;
            $fingerprint = isset($access['fingerprint']) ? $access['fingerprint'] : false;

            // both are mandatory
            if (!$algorithm || !$fingerprint) {
                continue;
            }

            $xml .= "\t".'<signatory><certificate fingerprint="'.$fingerprint.'" fingerprint-algorithm="'.$algorithm.'"/></signatory>'.PHP_EOL;
        }

        return $xml;
    }

    private function getAllowHeader(array $conf)
    {
        $xml = '';
        if (!isset($conf['allow-http-request-headers-from'])) {
            return $xml;
        }
        $allowHeaders = $conf['allow-http-request-headers-from'];
        if (!is_array($allowHeaders)) {
            return $xml;
        }
        foreach ($allowHeaders as $access) {
            // domain & headers are mandatory
            if (!isset($access['domain']) && !isset($access['headers'])) {
                continue;
            }
            $secure = isset($access['secure']) ? $access['secure'] : false;

            $xml .= "\t".'<allow-http-request-headers-from domain="'.$access['domain'].'" headers="'.$access['headers'].'"'. ($secure ? ' secure="'.$secure.'"': '') . '/>'.PHP_EOL;
        }

        return $xml;
    }
}
