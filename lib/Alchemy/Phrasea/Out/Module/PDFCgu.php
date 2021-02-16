<?php

namespace Alchemy\Phrasea\Out\Module;

use Alchemy\Phrasea\Application;

class PDFCgu extends PDF
{
    private $databoxId;

    public function __construct(Application $app, $databoxId)
    {
        parent::__construct($app);

        $this->app = $app;
        $this->databoxId = $databoxId;

        $this->printCgu();
    }

    public function save()
    {
        $this->pdf->Close();
        $pathName =  self::getDataboxCguPath($this->app, $this->databoxId);

        $this->pdf->Output($pathName, 'F');
    }

    public static function getDataboxCguPath(Application $app, $databoxId)
    {

        return \p4string::addEndSlash($app['tmp.download.path']). self::getDataboxCguPdfName($app, $databoxId);
    }

    public static function getDataboxCguPdfName(Application $app, $databoxId)
    {
        $databox = $app->findDataboxById($databoxId);

        return 'cgu_' . $databoxId . '_'. $databox->get_dbname() . '.pdf';
    }

    private function printCgu()
    {
        $databox = $this->app->findDataboxById($this->databoxId);
        $databox->get_dbname();

        $CGUs = $databox->get_cgus();

        $html = '';

        foreach ($CGUs as $locale => $tou) {
            $html .= '<h2> '.$this->app->trans('Terms Of Use', [], 'messages', $locale) .'</h2>';
            $html .= $tou['value'];
        }

        $this->pdf->AddPage();

        $this->pdf->writeHTML($html);
    }
}
