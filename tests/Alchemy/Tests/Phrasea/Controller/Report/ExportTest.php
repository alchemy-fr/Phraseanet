<?php

namespace Alchemy\Tests\Phrasea\Controller\Report;

class ExportTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{

    public function testExportCSV()
    {
        $data = 'Year,Make,Model
                1997,Ford,E350
                2000,Mercury,Cougar';

        self::$DI['client']->request('POST', '/report/export/csv', array(
            'csv'           => $data,
            'name'          => 'test',
        ));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());

        $this->assertRegexp('/attachment/', $response->headers->get('content-disposition'));
        $this->assertRegexp('/report_test/', $response->headers->get('content-disposition'));
    }
}
