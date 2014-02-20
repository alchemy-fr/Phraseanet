<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

class DoDownloadTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\DoDownload::prepareDownload
     * @covers Alchemy\Phrasea\Controller\Prod\DoDownload::connect
     * @covers Alchemy\Phrasea\Controller\Prod\DoDownload::call
     */
    public function testPrepareDownload()
    {
        $token = $this->getToken([
            'export_name' => 'Export_2012-10-23_621.zip',
            'count' => 1,
            'files' => [
                [
                    'base_id' => 1,
                    'record_id' => 1,
                    'export_name' => 'my_downloads',
                    'original_name' => '0470',
                    'subdefs' => [
                        'document' => [
                            'ajout'     => '',
                            'path'      => 'my_base/documents/2012/10/23/00000',
                            'file'      => '1_document.jpg',
                            'label'     => 'Document original',
                            'size'      => 241474,
                            'mime'      => 'image/jpeg',
                            'folder'    => '',
                            'exportExt' => 'jpg'
                        ]
                    ]
                ]
            ]
        ]);
        $url = sprintf('/download/%s/prepare/', $token);
        self::$DI['client']->request('GET', $url);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        unset($response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\DoDownload::prepareDownload
     */
    public function testPrepareDownloadTokenNotFound()
    {
        $token = 'AzBdisusjA';
        self::$DI['client']->request('GET', sprintf('/download/%s/prepare/', $token));

        $this->assertNotFoundResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\DoDownload::prepareDownload
     */
    public function testPrepareDownloadInvalidData()
    {
        $token = $this->getToken(['bad_string' => base64_decode(serialize(['fail']))]);
        self::$DI['client']->request('GET', sprintf('/download/%s/prepare/', $token));

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertTrue(false !== stripos($response->getContent(), 'Whoops, looks like something went wrong'));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\DoDownload::downloadDocuments
     */
    public function testOneDocumentsDownload()
    {
        $nbRowLogsBefore = $this->getNbRowLogs(self::$DI['record_1']->get_databox());
        $thumbnail = self::$DI['record_1']->get_thumbnail();

        $token = $this->getToken([
            'export_name' => 'Export_2012-10-23_621.zip',
            'count' => 1,
            'files' => [
                [
                    'base_id' => self::$DI['record_1']->get_base_id(),
                    'record_id' => self::$DI['record_1']->get_record_id(),
                    'export_name' => 'my_downloads',
                    'original_name' => '0470',
                    'subdefs' => [
                        'document' => [
                            'ajout'     => '',
                            'path'      => $thumbnail->get_path(),
                            'file'      => $thumbnail->get_file(),
                            'label'     => '',
                            'size'      => $thumbnail->get_size(),
                            'mime'      => $thumbnail->get_mime(),
                            'folder'    => '',
                            'exportExt' => pathinfo($thumbnail->get_file(), PATHINFO_EXTENSION)
                        ]
                    ]
                ]
            ]
        ]);

        $url = sprintf('/download/%s/get/', $token);
        self::$DI['client']->request('POST', $url);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertRegExp('#attachment#', $response->headers->get('content-disposition'));
        $this->assertEquals( $response->headers->get('content-length'), $thumbnail->get_size());
        $this->assertEquals( $response->headers->get('content-type'), $thumbnail->get_mime());
        $nbRowLogsAfter = $this->getNbRowLogs(self::$DI['record_1']->get_databox());
        $this->assertGreaterThan($nbRowLogsBefore, $nbRowLogsAfter);
        unset($response);
    }
    /**
     * @covers Alchemy\Phrasea\Controller\Prod\DoDownload::downloadDocuments
     */
    public function testTwoDocumentsDownload()
    {
        $nbRowLogsBefore = $this->getNbRowLogs(self::$DI['record_1']->get_databox());
        $thumbnail = self::$DI['record_1']->get_thumbnail();
        $thumbnail2 = self::$DI['record_2']->get_thumbnail();

        $list = [
            'export_name' => 'Export_2012-10-23_617.zip',
            'count' => 2,
            'files' => [
                [
                    'base_id' => self::$DI['record_1']->get_base_id(),
                    'record_id' => self::$DI['record_1']->get_record_id(),
                    'export_name' => 'my_downloads',
                    'original_name' => '0470',
                    'subdefs' => [
                        'document' => [
                            'ajout'     => '',
                            'path'      => $thumbnail->get_path(),
                            'file'      => $thumbnail->get_file(),
                            'label'     => '',
                            'size'      => $thumbnail->get_size(),
                            'mime'      => $thumbnail->get_mime(),
                            'folder'    => '',
                            'exportExt' => pathinfo($thumbnail->get_file(), PATHINFO_EXTENSION)
                        ]
                    ]
                ],
                [
                    'base_id' => self::$DI['record_2']->get_base_id(),
                    'record_id' => self::$DI['record_2']->get_record_id(),
                    'export_name' => 'my_downloads2',
                    'original_name' => '0471',
                    'subdefs' => [
                        'document' => [
                            'ajout'     => '',
                            'path'      => $thumbnail2->get_path(),
                            'file'      => $thumbnail2->get_file(),
                            'label'     => '',
                            'size'      => $thumbnail2->get_size(),
                            'mime'      => $thumbnail2->get_mime(),
                            'folder'    => '',
                            'exportExt' => pathinfo($thumbnail2->get_file(), PATHINFO_EXTENSION)
                        ]
                    ]
                ]
            ]
        ];

        $token = $this->getToken($list);
        // Get token
        $datas = self::$DI['app']['tokens']->helloToken($token);
        // Build zip
        \set_export::build_zip(
            self::$DI['app'],
            $token,
            $list,
            sprintf('%s/../../../../../../tmp/download/%s.zip', __DIR__, $datas['value']) // Dest file
        );

        // Check response
        $url = sprintf('/download/%s/get/', $token);
        self::$DI['client']->request('POST', $url);
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
        $this->assertRegExp('#attachment#', $response->headers->get('content-disposition'));
        $this->assertEquals('application/zip', $response->headers->get('content-type'));
        $nbRowLogsAfter = $this->getNbRowLogs(self::$DI['record_1']->get_databox());
        $this->assertGreaterThan($nbRowLogsBefore, $nbRowLogsAfter);
        unset($response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\DoDownload::downloadDocuments
     */
    public function testDocumentsDownloadNotFound()
    {
        $token = $this->getToken([
            'export_name' => 'Export_2012-10-23_621.zip',
            'count' => 1,
            'files' => [
                [
                    'base_id' => 1,
                    'record_id' => 1,
                    'export_name' => 'my_downloads',
                    'original_name' => '0470',
                    'subdefs' => [
                        'document' => [
                            'ajout'     => '',
                            'path'      => 'my_base/documents/2012/10/23/00000',
                            'file'      => '1_document.jpg',
                            'label'     => 'Document original',
                            'size'      => 241474,
                            'mime'      => 'image/jpeg',
                            'folder'    => '',
                            'exportExt' => 'jpg'
                        ]
                    ]
                ]
            ]
        ]);
        $url = sprintf('/download/%s/get/', $token);
        self::$DI['client']->request('POST', $url);

        $this->assertNotFoundResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\DoDownload::downloadDocuments
     */
    public function testDocumentsDownloadTokenNotFound()
    {
        $token = 'AzBdisusjA';
        self::$DI['client']->request('POST', sprintf('/download/%s/get/', $token));

        $this->assertNotFoundResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\DoDownload::downloadDocuments
     */
    public function testDocumentsDownloadInvalidData()
    {
        $token = $this->getToken(['bad_string' => base64_decode(serialize(['fail']))]);
        self::$DI['client']->request('POST', sprintf('/download/%s/get/', $token));

        $response = self::$DI['client']->getResponse();
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertTrue(false !== stripos($response->getContent(), 'Whoops, looks like something went wrong'));
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\DoDownload::downloadExecute
     */
    public function testExecuteDownloadInvalidData()
    {
        $token = $this->getToken(['bad_string' => base64_decode(serialize(['fail']))]);
        $url = sprintf('/download/%s/execute/', $token);
        self::$DI['client']->request('POST', $url);
        $response = self::$DI['client']->getResponse();
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertFalse($datas['success']);
        unset($response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\DoDownload::downloadExecute
     */
    public function testExecuteDownloadTokenNotFound()
    {
        $token = 'ABCDEFGHJaajKISU';
        $url = sprintf('/download/%s/execute/', $token);
        self::$DI['client']->request('POST', $url);
        $response = self::$DI['client']->getResponse();
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertFalse($datas['success']);
        unset($response);
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Prod\DoDownload::downloadExecute
     */
    public function testExecuteDownload()
    {
        $thumbnail = self::$DI['record_1']->get_thumbnail();
        $thumbnail2 = self::$DI['record_2']->get_thumbnail();

        $list = [
            'export_name' => 'Export_2012-10-23_617.zip',
            'count' => 2,
            'files' => [
                [
                    'base_id' => 1,
                    'record_id' => 1,
                    'export_name' => 'my_downloads',
                    'original_name' => '0470',
                    'subdefs' => [
                        'document' => [
                            'ajout'     => '',
                            'path'      => $thumbnail->get_path(),
                            'file'      => $thumbnail->get_file(),
                            'label'     => '',
                            'size'      => $thumbnail->get_size(),
                            'mime'      => $thumbnail->get_mime(),
                            'folder'    => '',
                            'exportExt' => pathinfo($thumbnail->get_file(), PATHINFO_EXTENSION)
                        ]
                    ]
                ],
                [
                    'base_id' => 1,
                    'record_id' => 1,
                    'export_name' => 'my_downloads2',
                    'original_name' => '0471',
                    'subdefs' => [
                        'document' => [
                            'ajout'     => '',
                            'path'      => $thumbnail2->get_path(),
                            'file'      => $thumbnail2->get_file(),
                            'label'     => '',
                            'size'      => $thumbnail2->get_size(),
                            'mime'      => $thumbnail2->get_mime(),
                            'folder'    => '',
                            'exportExt' => pathinfo($thumbnail2->get_file(), PATHINFO_EXTENSION)
                        ]
                    ]
                ]
            ]
        ];

        $token = $this->getToken($list);

        $url = sprintf('/download/%s/execute/', $token);
        self::$DI['client']->request('POST', $url);
        $response = self::$DI['client']->getResponse();
        $datas = (array) json_decode($response->getContent());
        $this->assertArrayHasKey('success', $datas);
        $this->assertTrue($datas['success']);
        unset($response);
    }

    private function getToken($datas = [])
    {
        return self::$DI['app']['tokens']->getUrlToken(
            \random::TYPE_DOWNLOAD,
            self::$DI['user']->getId(),
            new \DateTime('+10 seconds'), // Token lifetime
            serialize($datas)
        );
    }

    private function getNbRowLogs(\databox $databox)
    {
        $stmt = $databox->get_connection()->prepare('SELECT COUNT(l.id) as nb_log FROM log_docs l WHERE l.action = "download"');
        $stmt->execute();
        $row = $stmt->fetch();
        $stmt->closeCursor();
        unset($stmt);

        return $row['nb_log'];
    }
}
