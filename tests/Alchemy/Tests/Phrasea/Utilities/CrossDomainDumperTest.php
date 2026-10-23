<?php

namespace Alchemy\Tests\Phrasea\Utilities;

use Alchemy\Phrasea\Utilities\CrossDomainDumper;

class CrossDomainDumperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider crossDomainProvider
     */
    public function testDumper(array $configuration, $expected)
    {
        $dumper = new CrossDomainDumper();
        $this->assertEquals($dumper->dump($configuration), $expected);
    }

    public function crossDomainProvider()
    {
        return array(
            array(
                array(
                    'site-control' => 'master-only',
                    'allow-access-from' => array(
                        array(
                            'domain'=> '*.example.com',
                            'secure'=> 'false'
                        ),
                        array(
                            'domain'=> 'www.example.com',
                            'secure'=>'true',
                            'to-ports'=>'507,516-523'
                        )
                    ),
                    'allow-access-from-identity' => array(
                        array(
                            'fingerprint-algorithm'=> 'sha-1',
                            'fingerprint'=> '01:23:45:67:89:ab:cd:ef:01:23:45:67:89:ab:cd:ef:01:23:45:67'
                        ),
                        array(
                            'fingerprint-algorithm'=> 'sha256',
                            'fingerprint' => '01:23:45:67:89:ab:cd:ef:01:23:45:67:89:ab:cd:ef:01:23:45:67'
                        )
                    ),
                    'allow-http-request-headers-from' => array(
                        array(
                            'domain'=> '*.bar.com',
                            'secure'=> 'true',
                            'headers'=> 'SOAPAction, X-Foo*'
                        ),
                        array(
                            'domain'=> 'foo.example.com',
                            'secure'=> 'false',
                            'headers'=> 'Authorization,X-Foo*'
                        )
                    ),
                ),
                '<?xml version="1.0"?>
<!DOCTYPE cross-domain-policy SYSTEM "http://www.macromedia.com/xml/dtds/cross-domain-policy.dtd">
<cross-domain-policy>
	<site-control permitted-cross-domain-policies="master-only"/>
	<allow-access-from domain="*.example.com" secure="false"/>
	<allow-access-from domain="www.example.com" to-ports="507,516-523" secure="true"/>
	<signatory><certificate fingerprint="01:23:45:67:89:ab:cd:ef:01:23:45:67:89:ab:cd:ef:01:23:45:67" fingerprint-algorithm="sha-1"/></signatory>
	<signatory><certificate fingerprint="01:23:45:67:89:ab:cd:ef:01:23:45:67:89:ab:cd:ef:01:23:45:67" fingerprint-algorithm="sha256"/></signatory>
	<allow-http-request-headers-from domain="*.bar.com" headers="SOAPAction, X-Foo*" secure="true"/>
	<allow-http-request-headers-from domain="foo.example.com" headers="Authorization,X-Foo*" secure="false"/>
</cross-domain-policy>'
            ),
            array(
                array(
                    'allow-access-from' => array(
                        array(
                            'domain'=> '*.cooliris.com',
                            'secure'=> 'false'
                        )
                    )
                ),
                '<?xml version="1.0"?>
<!DOCTYPE cross-domain-policy SYSTEM "http://www.macromedia.com/xml/dtds/cross-domain-policy.dtd">
<cross-domain-policy>
	<allow-access-from domain="*.cooliris.com" secure="false"/>
</cross-domain-policy>'
            )
        );
    }
}
