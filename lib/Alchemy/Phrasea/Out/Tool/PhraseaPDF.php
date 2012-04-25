<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Out\Tool;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class PhraseaPDF extends \TCPDF
{
    const FONT = 'freesans';

    function Header()
    {

    }

    function Footer()
    {
        $ml = $this->SetLeftMargin(0);
        $mr = $this->SetRightMargin(0);

        $this->SetY(-15);

        $this->SetFont(self::FONT, 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');

        $this->SetFont(self::FONT, '', 8);
        $w = $this->GetStringWidth('Printed by');

        $this->SetFont(self::FONT, 'B', 8);
        $w += $this->GetStringWidth(' Phraseanet');

        $this->SetXY(-$w - $mr - 5, -15);

        $this->SetFont(self::FONT, '', 8);
        $this->Write(8, 'Printed by');

        $this->SetFont(self::FONT, 'B', 8);
        $this->Write(8, ' Phraseanet');
    }
}
