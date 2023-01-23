<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Out\Module;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Out\Tool\PhraseaPDF;

class PDF
{
    protected $app;
    protected $records;
    protected $pdf;

    const LAYOUT_FEEDBACK = 'feedback';
    const LAYOUT_FEEDBACKONLY = 'feedbackOnly';
    const LAYOUT_PREVIEW = 'preview';
    const LAYOUT_PREVIEWCAPTION = 'previewCaption';
    const LAYOUT_PREVIEWCAPTIONTDM = 'previewCaptionTdm';
    const LAYOUT_THUMBNAILLIST = 'thumbnailList';
    const LAYOUT_THUMBNAILGRID = 'thumbnailGrid';
    const LAYOUT_CAPTION = 'caption';

    public function __construct(Application $app)
    {
        $this->app = $app;

        $pdf = new PhraseaPDF("P", "mm", "A4", true, 'UTF-8', false);
        $pdf->setApp($app);

        $pdf->SetAuthor("Phraseanet");
        $pdf->SetTitle("Phraseanet Print");
        $pdf->SetDisplayMode("fullpage", "single");

        $this->pdf = $pdf;
    }

    public function render()
    {
        $this->pdf->Close();

        return $this->pdf->Output('', 'S');
    }
}
