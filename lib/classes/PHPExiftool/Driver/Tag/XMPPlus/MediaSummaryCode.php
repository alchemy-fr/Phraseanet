<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPPlus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MediaSummaryCode extends AbstractTag
{

    protected $Id = 'MediaSummaryCode';

    protected $Name = 'MediaSummaryCode';

    protected $FullName = 'PLUS::XMP';

    protected $GroupName = 'XMP-plus';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-plus';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Media Summary Code';

    protected $Values = array(
        '1IAA' => array(
            'Id' => '1IAA',
            'Label' => '1 Usage Item:',
        ),
        '1IAB' => array(
            'Id' => '1IAB',
            'Label' => '2 Usage Items:',
        ),
        '1IAC' => array(
            'Id' => '1IAC',
            'Label' => '3 Usage Items:',
        ),
        '1IAD' => array(
            'Id' => '1IAD',
            'Label' => '4 Usage Items:',
        ),
        '1IAE' => array(
            'Id' => '1IAE',
            'Label' => '5 Usage Items:',
        ),
        '1UNA' => array(
            'Id' => '1UNA',
            'Label' => 'Usage Number A',
        ),
        '1UNB' => array(
            'Id' => '1UNB',
            'Label' => 'Usage Number B',
        ),
        '1UNC' => array(
            'Id' => '1UNC',
            'Label' => 'Usage Number C',
        ),
        '1UND' => array(
            'Id' => '1UND',
            'Label' => 'Usage Number D',
        ),
        '1UNE' => array(
            'Id' => '1UNE',
            'Label' => 'Usage Number E',
        ),
        '2AAA' => array(
            'Id' => '2AAA',
            'Label' => 'Advertising|All Media Types|All Formats|All Distribution Formats',
        ),
        '2AAB' => array(
            'Id' => '2AAB',
            'Label' => 'All Categories|Book|All Book Types|All Distribution Formats',
        ),
        '2AAC' => array(
            'Id' => '2AAC',
            'Label' => 'All Categories|Periodicals|All Periodical Types|All Distribution Formats',
        ),
        '2AAD' => array(
            'Id' => '2AAD',
            'Label' => 'All Categories|Display|All Display Types|All Distribution Formats',
        ),
        '2AAE' => array(
            'Id' => '2AAE',
            'Label' => 'Editorial|All Media Types|All Formats|All Distribution Formats',
        ),
        '2AAG' => array(
            'Id' => '2AAG',
            'Label' => 'All Categories|Product Packaging|All Product Packaging Types|All Distribution Formats',
        ),
        '2AAH' => array(
            'Id' => '2AAH',
            'Label' => 'All Categories|Merchandise|All Merchandise Types|All Distribution Formats',
        ),
        '2AAI' => array(
            'Id' => '2AAI',
            'Label' => 'Internal Company Use|All Media Types|All Formats|All Distribution Formats',
        ),
        '2AAL' => array(
            'Id' => '2AAL',
            'Label' => 'All Categories|Mobile|All Mobile Types|All Distribution Formats',
        ),
        '2AAM' => array(
            'Id' => '2AAM',
            'Label' => 'Motion Picture & TV|All Media Types|All Formats|All Distribution Formats',
        ),
        '2AAN' => array(
            'Id' => '2AAN',
            'Label' => 'All Categories|Television Programming|All Television Programming Types|All Distribution Formats',
        ),
        '2AAP' => array(
            'Id' => '2AAP',
            'Label' => 'Products|All Media Types|All Formats|All Distribution Formats',
        ),
        '2AAR' => array(
            'Id' => '2AAR',
            'Label' => 'All Categories|Point of Purchase|All Point Of Purchase Types|All Distribution Formats',
        ),
        '2AAT' => array(
            'Id' => '2AAT',
            'Label' => 'All Categories|Marketing Materials|All Marketing Material Types|All Distribution Formats',
        ),
        '2AAU' => array(
            'Id' => '2AAU',
            'Label' => 'Personal Use|All Media Types|All Formats|All Distribution Formats',
        ),
        '2AAV' => array(
            'Id' => '2AAV',
            'Label' => 'All Categories|Music Video|All Music Video Types|All Distribution Formats',
        ),
        '2AAX' => array(
            'Id' => '2AAX',
            'Label' => 'All Categories|Motion Picture|All Motion Picture Types|All Distribution Formats',
        ),
        '2AAY' => array(
            'Id' => '2AAY',
            'Label' => 'Advertising|All Media Types|Promotional Reproduction of Licensed Usage in Context|All Distribution Formats',
        ),
        '2ACE' => array(
            'Id' => '2ACE',
            'Label' => 'Advertising|Periodicals|Magazine, Consumer|Internet Website',
        ),
        '2ACT' => array(
            'Id' => '2ACT',
            'Label' => 'Motion Picture & TV|Motion Picture|All Motion Picture Types|All Electronic Distribution Formats',
        ),
        '2ADD' => array(
            'Id' => '2ADD',
            'Label' => 'Products|Merchandise|Address Book|Printed',
        ),
        '2ADH' => array(
            'Id' => '2ADH',
            'Label' => 'Advertising|Point of Purchase|Adhesive Tag|Printed',
        ),
        '2ADS' => array(
            'Id' => '2ADS',
            'Label' => 'Advertising|Periodicals|Magazine, Consumer|Internet Email',
        ),
        '2AFT' => array(
            'Id' => '2AFT',
            'Label' => 'Advertising|Periodicals|Magazine, Trade|Internet Email',
        ),
        '2AGE' => array(
            'Id' => '2AGE',
            'Label' => 'Advertising|Periodicals|Magazine, Trade|Recordable Media',
        ),
        '2AGO' => array(
            'Id' => '2AGO',
            'Label' => 'Advertising|Periodicals|Magazine, Corporate|Internet Email',
        ),
        '2AHA' => array(
            'Id' => '2AHA',
            'Label' => 'Advertising|Periodicals|Magazine, Education|Internet Email',
        ),
        '2AID' => array(
            'Id' => '2AID',
            'Label' => 'Motion Picture & TV|Motion Picture|Movie Trailer|All Electronic Distribution Formats',
        ),
        '2AIL' => array(
            'Id' => '2AIL',
            'Label' => 'Advertising|Periodicals|Magazine, Custom Published|Internet Email',
        ),
        '2AIM' => array(
            'Id' => '2AIM',
            'Label' => 'Motion Picture & TV|Motion Picture|Short Film|Recordable Media',
        ),
        '2ALB' => array(
            'Id' => '2ALB',
            'Label' => 'Products|Merchandise|Photo Album|Printed',
        ),
        '2ALL' => array(
            'Id' => '2ALL',
            'Label' => 'All Categories|All Media Types|All Formats|All Distribution Formats',
        ),
        '2ALP' => array(
            'Id' => '2ALP',
            'Label' => 'Advertising|Periodicals|Magazine, Custom Published|Recordable Media',
        ),
        '2AMI' => array(
            'Id' => '2AMI',
            'Label' => 'Advertising|Periodicals|Magazine, Custom Published|All Electronic Distribution Formats',
        ),
        '2AMP' => array(
            'Id' => '2AMP',
            'Label' => 'Advertising|Periodicals|Magazine Reprints, All Types|Internet Email',
        ),
        '2ANA' => array(
            'Id' => '2ANA',
            'Label' => 'Advertising|Periodicals|Magazine, All Types|Internet Website',
        ),
        '2ANN' => array(
            'Id' => '2ANN',
            'Label' => 'Advertising|Periodicals|Annual Report|Printed',
        ),
        '2ANT' => array(
            'Id' => '2ANT',
            'Label' => 'Products|Merchandise|Anthology|Printed',
        ),
        '2ANY' => array(
            'Id' => '2ANY',
            'Label' => 'Advertising|Periodicals|Magazine, All Types|Internet Downloadable File',
        ),
        '2APE' => array(
            'Id' => '2APE',
            'Label' => 'Advertising|Periodicals|Magazine, All Types|Internet Email',
        ),
        '2APP' => array(
            'Id' => '2APP',
            'Label' => 'Products|Merchandise|Apparel, General Apparel|Printed or Woven',
        ),
        '2APT' => array(
            'Id' => '2APT',
            'Label' => 'Advertising|Periodicals|Magazine, All Types|All Internet Distribution Formats',
        ),
        '2ARC' => array(
            'Id' => '2ARC',
            'Label' => 'Motion Picture & TV|Motion Picture|Set Decor|Projected Display',
        ),
        '2ARK' => array(
            'Id' => '2ARK',
            'Label' => 'Advertising|Periodicals|Magazine, All Types|All Electronic Distribution Formats',
        ),
        '2ARM' => array(
            'Id' => '2ARM',
            'Label' => 'Advertising|Periodicals|Newspaper, Weekly Supplement|Internet Email',
        ),
        '2ART' => array(
            'Id' => '2ART',
            'Label' => 'Advertising|Art|Art Display, All Art Types|Printed',
        ),
        '2ASH' => array(
            'Id' => '2ASH',
            'Label' => 'Advertising|Periodicals|Newspaper, Tabloid|Internet Downloadable File',
        ),
        '2ASK' => array(
            'Id' => '2ASK',
            'Label' => 'Motion Picture & TV|Motion Picture|Short Film|All Internet Distribution Formats',
        ),
        '2ATE' => array(
            'Id' => '2ATE',
            'Label' => 'Motion Picture & TV|Motion Picture|Movie Trailer|Projected Display',
        ),
        '2ATM' => array(
            'Id' => '2ATM',
            'Label' => 'Products|Merchandise|Card, ATM Card|Printed',
        ),
        '2ATT' => array(
            'Id' => '2ATT',
            'Label' => 'Products|Merchandise|Card, Phone Card|Printed',
        ),
        '2BAA' => array(
            'Id' => '2BAA',
            'Label' => 'All Categories|Website|All Web Page Types|All Distribution Formats',
        ),
        '2BAB' => array(
            'Id' => '2BAB',
            'Label' => 'Products|Art|Artist\'s Reference, Tattoo|Printed',
        ),
        '2BAG' => array(
            'Id' => '2BAG',
            'Label' => 'Advertising|Periodicals|All Periodical Types|Printed',
        ),
        '2BAH' => array(
            'Id' => '2BAH',
            'Label' => 'Advertising|Periodicals|Quarterly Report|Printed',
        ),
        '2BAJ' => array(
            'Id' => '2BAJ',
            'Label' => 'Products|Merchandise|Computer Software|Recordable Media',
        ),
        '2BAK' => array(
            'Id' => '2BAK',
            'Label' => 'Products|Merchandise|Computer Software|Internet Downloadable File',
        ),
        '2BAL' => array(
            'Id' => '2BAL',
            'Label' => 'Advertising|Periodicals|Quarterly Report|Internet Email',
        ),
        '2BAM' => array(
            'Id' => '2BAM',
            'Label' => 'Advertising|Periodicals|Magazine, Advertorial|Internet Downloadable File',
        ),
        '2BAN' => array(
            'Id' => '2BAN',
            'Label' => 'Advertising|Display|Banner, All Types|Electronic Display',
        ),
        '2BAP' => array(
            'Id' => '2BAP',
            'Label' => 'Advertising|Periodicals|Magazine, Advertorial|Internet Website',
        ),
        '2BAR' => array(
            'Id' => '2BAR',
            'Label' => 'Advertising|Point of Purchase|All Point of Purchase Types|All Electronic Distribution Formats',
        ),
        '2BAS' => array(
            'Id' => '2BAS',
            'Label' => 'Advertising|Periodicals|Annual Report|Recordable Media',
        ),
        '2BAT' => array(
            'Id' => '2BAT',
            'Label' => 'Advertising|Periodicals|Free Standing Insert, All Insert Types|Printed',
        ),
        '2BAU' => array(
            'Id' => '2BAU',
            'Label' => 'Products|Merchandise|Computer Software|Internet Email',
        ),
        '2BAV' => array(
            'Id' => '2BAV',
            'Label' => 'Products|Merchandise|Computer Software|All Internet Distribution Formats',
        ),
        '2BAW' => array(
            'Id' => '2BAW',
            'Label' => 'Products|Merchandise|Diary|Recordable Media',
        ),
        '2BAY' => array(
            'Id' => '2BAY',
            'Label' => 'Advertising|Periodicals|Newspaper, Tabloid|Internet Email',
        ),
        '2BAZ' => array(
            'Id' => '2BAZ',
            'Label' => 'Products|Merchandise|Diary|Internet Downloadable File',
        ),
        '2BBA' => array(
            'Id' => '2BBA',
            'Label' => 'Products|Merchandise|Diary|Internet Email',
        ),
        '2BCF' => array(
            'Id' => '2BCF',
            'Label' => 'Products|Merchandise|Retail Calendar, Multi-Page|Recordable Media',
        ),
        '2BCG' => array(
            'Id' => '2BCG',
            'Label' => 'Products|Merchandise|Retail Calendar, Multi-Page|All Electronic Distribution Formats',
        ),
        '2BCH' => array(
            'Id' => '2BCH',
            'Label' => 'Products|Merchandise|Retail Calendar, Multi-Page|All Internet Distribution Formats',
        ),
        '2BCI' => array(
            'Id' => '2BCI',
            'Label' => 'Products|Merchandise|Retail Calendar, Multi-Page|Internet Email',
        ),
        '2BCJ' => array(
            'Id' => '2BCJ',
            'Label' => 'Products|Merchandise|Retail Calendar, Multi-Page|Internet Downloadable File',
        ),
        '2BED' => array(
            'Id' => '2BED',
            'Label' => 'Advertising|Periodicals|Newspaper, All Types|Internet Email',
        ),
        '2BEE' => array(
            'Id' => '2BEE',
            'Label' => 'Editorial|Book|Textbook, Middle Reader|Printed',
        ),
        '2BEG' => array(
            'Id' => '2BEG',
            'Label' => 'Advertising|Book|Retail Book, Directory|Printed',
        ),
        '2BEL' => array(
            'Id' => '2BEL',
            'Label' => 'Advertising|Art|Art Display, All Art Types|Internet Website',
        ),
        '2BEN' => array(
            'Id' => '2BEN',
            'Label' => 'Advertising|Periodicals|Magazine, Advertorial|Printed',
        ),
        '2BER' => array(
            'Id' => '2BER',
            'Label' => 'Motion Picture & TV|Music Video|All Music Video Types|Recordable Media',
        ),
        '2BES' => array(
            'Id' => '2BES',
            'Label' => 'Motion Picture & TV|Music Video|All Music Video Types|All Internet Distribution Formats',
        ),
        '2BET' => array(
            'Id' => '2BET',
            'Label' => 'Motion Picture & TV|Motion Picture|Movie Trailer|All Internet Distribution Formats',
        ),
        '2BEY' => array(
            'Id' => '2BEY',
            'Label' => 'Advertising|Art|Art Display, All Art Types|All Internet Distribution Formats',
        ),
        '2BFH' => array(
            'Id' => '2BFH',
            'Label' => 'Personal Use|Art|Art Display, All Art Types|All Electronic Distribution Formats',
        ),
        '2BFI' => array(
            'Id' => '2BFI',
            'Label' => 'Personal Use|Art|Art Display, All Art Types|All Internet Distribution Formats',
        ),
        '2BFJ' => array(
            'Id' => '2BFJ',
            'Label' => 'Personal Use|Art|Art Display, All Art Types|Internet Website',
        ),
        '2BFK' => array(
            'Id' => '2BFK',
            'Label' => 'Personal Use|Art|Art Display, All Art Types|Electronic Display',
        ),
        '2BFN' => array(
            'Id' => '2BFN',
            'Label' => 'Personal Use|Art|Art Display, Display Print|Printed',
        ),
        '2BFP' => array(
            'Id' => '2BFP',
            'Label' => 'Personal Use|Art|Study Print, Educational|Printed',
        ),
        '2BFR' => array(
            'Id' => '2BFR',
            'Label' => 'Personal Use|Website|Web Page, All Types|Internet Website',
        ),
        '2BFS' => array(
            'Id' => '2BFS',
            'Label' => 'Personal Use|Website|Web Page, All Types|Recordable Media',
        ),
        '2BFT' => array(
            'Id' => '2BFT',
            'Label' => 'Personal Use|Website|Web Page, All Types|All Electronic Distribution Formats',
        ),
        '2BFU' => array(
            'Id' => '2BFU',
            'Label' => 'Personal Use|Website|Web Page, All Types|All Internet Distribution Formats',
        ),
        '2BHB' => array(
            'Id' => '2BHB',
            'Label' => 'Internal Company Use|Art|Art Display, Display Print|Printed',
        ),
        '2BHD' => array(
            'Id' => '2BHD',
            'Label' => 'Internal Company Use|Email|All Email Types|Intranet and Extranet Email',
        ),
        '2BHF' => array(
            'Id' => '2BHF',
            'Label' => 'Internal Company Use|Live Presentation|Internal Presentation|Projected Display',
        ),
        '2BHG' => array(
            'Id' => '2BHG',
            'Label' => 'Internal Company Use|Promotional Materials|Corporate Brochure|Recordable Media',
        ),
        '2BHH' => array(
            'Id' => '2BHH',
            'Label' => 'Internal Company Use|Promotional Materials|Corporate Brochure|Intranet and Extranet Downloadable File',
        ),
        '2BHI' => array(
            'Id' => '2BHI',
            'Label' => 'Internal Company Use|Promotional Materials|Corporate Brochure|Intranet and Extranet Email',
        ),
        '2BHK' => array(
            'Id' => '2BHK',
            'Label' => 'Internal Company Use|Promotional Materials|Corporate Calendar|Recordable Media',
        ),
        '2BHL' => array(
            'Id' => '2BHL',
            'Label' => 'Internal Company Use|Promotional Materials|Corporate Calendar|Intranet and Extranet Downloadable File',
        ),
        '2BHM' => array(
            'Id' => '2BHM',
            'Label' => 'Internal Company Use|Promotional Materials|Corporate Calendar|Intranet and Extranet Email',
        ),
        '2BHN' => array(
            'Id' => '2BHN',
            'Label' => 'Internal Company Use|Promotional Materials|Corporate Calendar|Printed',
        ),
        '2BHQ' => array(
            'Id' => '2BHQ',
            'Label' => 'Internal Company Use|Promotional Materials|Card, Corporate Card|Printed',
        ),
        '2BHS' => array(
            'Id' => '2BHS',
            'Label' => 'Internal Company Use|Promotional Materials|Card, Corporate Card|Recordable Media',
        ),
        '2BHT' => array(
            'Id' => '2BHT',
            'Label' => 'Internal Company Use|Promotional Materials|Card, Corporate Card|Intranet and Extranet Downloadable File',
        ),
        '2BHU' => array(
            'Id' => '2BHU',
            'Label' => 'Internal Company Use|Promotional Materials|Card, Corporate Card|Intranet and Extranet Email',
        ),
        '2BHV' => array(
            'Id' => '2BHV',
            'Label' => 'Internal Company Use|Promotional Materials|Sales Kit|All Electronic Distribution Formats',
        ),
        '2BHW' => array(
            'Id' => '2BHW',
            'Label' => 'Internal Company Use|Promotional Materials|Training Materials|Printed',
        ),
        '2BHY' => array(
            'Id' => '2BHY',
            'Label' => 'Internal Company Use|Promotional Materials|Corporate Folder|Printed',
        ),
        '2BHZ' => array(
            'Id' => '2BHZ',
            'Label' => 'Internal Company Use|Promotional Materials|CD ROM|Recordable Media',
        ),
        '2BIA' => array(
            'Id' => '2BIA',
            'Label' => 'Internal Company Use|Periodicals|Magazine, Custom Published|Recordable Media',
        ),
        '2BIB' => array(
            'Id' => '2BIB',
            'Label' => 'Advertising|Point of Purchase|All Point of Purchase Types|Electronic Display',
        ),
        '2BIC' => array(
            'Id' => '2BIC',
            'Label' => 'Internal Company Use|Periodicals|Magazine, Custom Published|Intranet and Extranet Website',
        ),
        '2BID' => array(
            'Id' => '2BID',
            'Label' => 'Advertising|Periodicals|Newsletter, All Types|Internet Email',
        ),
        '2BIF' => array(
            'Id' => '2BIF',
            'Label' => 'Internal Company Use|Periodicals|Magazine, Custom Published|Intranet and Extranet Downloadable File',
        ),
        '2BIG' => array(
            'Id' => '2BIG',
            'Label' => 'Advertising|Display|All Display Types|Printed',
        ),
        '2BIH' => array(
            'Id' => '2BIH',
            'Label' => 'Internal Company Use|Periodicals|Magazine, Custom Published|All Electronic Distribution Formats',
        ),
        '2BII' => array(
            'Id' => '2BII',
            'Label' => 'Internal Company Use|Periodicals|Magazine, Custom Published|All Intranet and Extranet Distribution Formats',
        ),
        '2BIL' => array(
            'Id' => '2BIL',
            'Label' => 'Advertising|Display|Billboard, All Types|Printed',
        ),
        '2BIN' => array(
            'Id' => '2BIN',
            'Label' => 'Advertising|Point of Purchase|All Point of Purchase Types|Printed',
        ),
        '2BIO' => array(
            'Id' => '2BIO',
            'Label' => 'Products|Merchandise|Birthday Book|Printed',
        ),
        '2BIS' => array(
            'Id' => '2BIS',
            'Label' => 'Advertising|Art|Art Display, All Art Types|All Electronic Distribution Formats',
        ),
        '2BIT' => array(
            'Id' => '2BIT',
            'Label' => 'Products|Merchandise|Card, Debit Card|Printed',
        ),
        '2BIZ' => array(
            'Id' => '2BIZ',
            'Label' => 'Advertising|Marketing Materials|Card, Business Greeting Card|Printed',
        ),
        '2BJH' => array(
            'Id' => '2BJH',
            'Label' => 'Internal Company Use|Website|Webcast, All Types|Intranet and Extranet Website',
        ),
        '2BJK' => array(
            'Id' => '2BJK',
            'Label' => 'Internal Company Use|Website|Web Page, All Types|All Electronic Distribution Formats',
        ),
        '2BJL' => array(
            'Id' => '2BJL',
            'Label' => 'Internal Company Use|Website|Web Page, All Types|Recordable Media',
        ),
        '2BJN' => array(
            'Id' => '2BJN',
            'Label' => 'Internal Company Use|Website|Web Page, Content Body|Intranet and Extranet Website',
        ),
        '2BLA' => array(
            'Id' => '2BLA',
            'Label' => 'Products|Merchandise|Blank Note Book|Printed',
        ),
        '2BLD' => array(
            'Id' => '2BLD',
            'Label' => 'Advertising|Display|Billboard, Building Wrap|Printed',
        ),
        '2BNK' => array(
            'Id' => '2BNK',
            'Label' => 'Products|Merchandise|Card, Bank Card|Printed',
        ),
        '2BOA' => array(
            'Id' => '2BOA',
            'Label' => 'Editorial|Book|Textbook, Student Edition|Printed',
        ),
        '2BOB' => array(
            'Id' => '2BOB',
            'Label' => 'Advertising|Periodicals|Magazine, Consumer|Printed',
        ),
        '2BOD' => array(
            'Id' => '2BOD',
            'Label' => 'Advertising|Periodicals|All Periodical Types|Internet Downloadable File',
        ),
        '2BOG' => array(
            'Id' => '2BOG',
            'Label' => 'Advertising|Display|All Display Types|Electronic Display',
        ),
        '2BOO' => array(
            'Id' => '2BOO',
            'Label' => 'Editorial|Book|Retail Book, All Types|Printed',
        ),
        '2BOP' => array(
            'Id' => '2BOP',
            'Label' => 'Advertising|Periodicals|Magazine, Advertorial|Internet Email',
        ),
        '2BOS' => array(
            'Id' => '2BOS',
            'Label' => 'Advertising|Art|Art Display, All Art Types|Electronic Display',
        ),
        '2BOT' => array(
            'Id' => '2BOT',
            'Label' => 'Advertising|Point of Purchase|Bottlenecker|Printed',
        ),
        '2BOW' => array(
            'Id' => '2BOW',
            'Label' => 'Advertising|Periodicals|All Periodical Types|Internet Email',
        ),
        '2BOX' => array(
            'Id' => '2BOX',
            'Label' => 'Products|Merchandise|Gift Box|Printed',
        ),
        '2BOY' => array(
            'Id' => '2BOY',
            'Label' => 'Advertising|Art|Art Display, Display Print|Printed',
        ),
        '2BPE' => array(
            'Id' => '2BPE',
            'Label' => 'Advertising|Display|Transit Advertising, Bus Poster|Electronic Display',
        ),
        '2BRA' => array(
            'Id' => '2BRA',
            'Label' => 'Advertising|Display|Banner, Airborne Display|Printed',
        ),
        '2BRO' => array(
            'Id' => '2BRO',
            'Label' => 'Advertising|Display|Banner, All Types|Printed',
        ),
        '2BRR' => array(
            'Id' => '2BRR',
            'Label' => 'Advertising|Display|Banner, Backdrop|Printed',
        ),
        '2BUB' => array(
            'Id' => '2BUB',
            'Label' => 'Advertising|Display|Banner, Background|Printed',
        ),
        '2BUD' => array(
            'Id' => '2BUD',
            'Label' => 'Advertising|Display|Billboard, All Types|Electronic Display',
        ),
        '2BUG' => array(
            'Id' => '2BUG',
            'Label' => 'Editorial|Book|Retail Book, Children\'s Book|Printed',
        ),
        '2BUN' => array(
            'Id' => '2BUN',
            'Label' => 'Advertising|Marketing Materials|Promo Card|Printed',
        ),
        '2BUR' => array(
            'Id' => '2BUR',
            'Label' => 'Advertising|Display|Billboard, Bulletin|Electronic Display',
        ),
        '2BUS' => array(
            'Id' => '2BUS',
            'Label' => 'Advertising|Display|Transit Advertising, Bus Panel|Printed',
        ),
        '2BUY' => array(
            'Id' => '2BUY',
            'Label' => 'Advertising|Point of Purchase|Floor Graphic|Printed',
        ),
        '2BYE' => array(
            'Id' => '2BYE',
            'Label' => 'Advertising|Display|Billboard, Mobile Billboard|Electronic Display',
        ),
        '2BYS' => array(
            'Id' => '2BYS',
            'Label' => 'Advertising|Display|Billboard, Mobile Billboard|Printed',
        ),
        '2CAL' => array(
            'Id' => '2CAL',
            'Label' => 'Products|Merchandise|Retail Calendar, Multi-Page|Printed',
        ),
        '2CAR' => array(
            'Id' => '2CAR',
            'Label' => 'Advertising|Display|Shopping Cart|Printed',
        ),
        '2CAS' => array(
            'Id' => '2CAS',
            'Label' => 'Advertising|Point of Purchase|Case Card|Printed',
        ),
        '2CDR' => array(
            'Id' => '2CDR',
            'Label' => 'Products|Merchandise|CD ROM|Recordable Media',
        ),
        '2CHK' => array(
            'Id' => '2CHK',
            'Label' => 'Products|Merchandise|Check|Printed',
        ),
        '2CLO' => array(
            'Id' => '2CLO',
            'Label' => 'Products|Merchandise|Retail Calendar, One Page|Printed',
        ),
        '2COU' => array(
            'Id' => '2COU',
            'Label' => 'Advertising|Point of Purchase|Counter Card|Printed',
        ),
        '2CRD' => array(
            'Id' => '2CRD',
            'Label' => 'Products|Merchandise|Card, Other Card|Printed',
        ),
        '2CRE' => array(
            'Id' => '2CRE',
            'Label' => 'Products|Merchandise|Card, Credit Card|Printed',
        ),
        '2CRT' => array(
            'Id' => '2CRT',
            'Label' => 'Advertising|Display|Shopping Cart|Electronic Display',
        ),
        '2CUS' => array(
            'Id' => '2CUS',
            'Label' => 'Advertising|Periodicals|Magazine, Custom Published|Printed',
        ),
        '2DAB' => array(
            'Id' => '2DAB',
            'Label' => 'Advertising|Display|Billboard, Rotating Billboard|Printed',
        ),
        '2DAD' => array(
            'Id' => '2DAD',
            'Label' => 'Advertising|Display|Billboard, Spectacular|Electronic Display',
        ),
        '2DAG' => array(
            'Id' => '2DAG',
            'Label' => 'Advertising|Display|Billboard, Spectacular|Printed',
        ),
        '2DAH' => array(
            'Id' => '2DAH',
            'Label' => 'Advertising|Display|Billboard, Wallscape|Electronic Display',
        ),
        '2DAK' => array(
            'Id' => '2DAK',
            'Label' => 'Advertising|Display|Billboard, Wallscape|Printed',
        ),
        '2DAL' => array(
            'Id' => '2DAL',
            'Label' => 'Advertising|Display|Event, Stadium Advertising|Electronic Display',
        ),
        '2DAM' => array(
            'Id' => '2DAM',
            'Label' => 'Advertising|Display|Event, Stadium Advertising|Printed',
        ),
        '2DAP' => array(
            'Id' => '2DAP',
            'Label' => 'Advertising|Display|Event, Trade Show Display|Electronic Display',
        ),
        '2DAW' => array(
            'Id' => '2DAW',
            'Label' => 'Advertising|Display|Poster, All Types|Electronic Display',
        ),
        '2DAY' => array(
            'Id' => '2DAY',
            'Label' => 'Products|Product Packaging|All Product Packaging Types|Printed',
        ),
        '2DEB' => array(
            'Id' => '2DEB',
            'Label' => 'Advertising|Periodicals|Magazine, Education|Printed',
        ),
        '2DEE' => array(
            'Id' => '2DEE',
            'Label' => 'Advertising|Periodicals|Wrapper|Printed',
        ),
        '2DEL' => array(
            'Id' => '2DEL',
            'Label' => 'Advertising|Display|Poster, Backlit Print|Printed',
        ),
        '2DEN' => array(
            'Id' => '2DEN',
            'Label' => 'Advertising|Display|Poster, Corporate Poster|Electronic Display',
        ),
        '2DEV' => array(
            'Id' => '2DEV',
            'Label' => 'Advertising|Display|Poster, Corporate Poster|Printed',
        ),
        '2DEW' => array(
            'Id' => '2DEW',
            'Label' => 'Editorial|Book|Trade Book, All Types|Printed',
        ),
        '2DEX' => array(
            'Id' => '2DEX',
            'Label' => 'Advertising|Display|Poster, Display Chrome|Printed',
        ),
        '2DEY' => array(
            'Id' => '2DEY',
            'Label' => 'Advertising|Display|Poster, Door Side Poster|Electronic Display',
        ),
        '2DIA' => array(
            'Id' => '2DIA',
            'Label' => 'Products|Merchandise|Diary|Printed',
        ),
        '2DIB' => array(
            'Id' => '2DIB',
            'Label' => 'Advertising|Display|Poster, Door Side Poster|Printed',
        ),
        '2DID' => array(
            'Id' => '2DID',
            'Label' => 'Advertising|Display|Poster, Elevator Advertising|Electronic Display',
        ),
        '2DIG' => array(
            'Id' => '2DIG',
            'Label' => 'Motion Picture & TV|Motion Picture|Movie Trailer|Recordable Media',
        ),
        '2DIM' => array(
            'Id' => '2DIM',
            'Label' => 'Personal Use|Personal Review|All Review Types|All Electronic Distribution Formats',
        ),
        '2DIN' => array(
            'Id' => '2DIN',
            'Label' => 'Advertising|Display|Store Display, All Display Types|Electronic Display',
        ),
        '2DIP' => array(
            'Id' => '2DIP',
            'Label' => 'Motion Picture & TV|Motion Picture|All Motion Picture Types|All Internet Distribution Formats',
        ),
        '2DIR' => array(
            'Id' => '2DIR',
            'Label' => 'Editorial|Book|Retail Book, Directory|Printed',
        ),
        '2DIS' => array(
            'Id' => '2DIS',
            'Label' => 'Advertising|Display|Event, Trade Show Display|Printed',
        ),
        '2DIT' => array(
            'Id' => '2DIT',
            'Label' => 'Advertising|Display|Store Display, In-Store Poster|Electronic Display',
        ),
        '2DOC' => array(
            'Id' => '2DOC',
            'Label' => 'Motion Picture & TV|Motion Picture|Documentary Film|All Electronic Distribution Formats',
        ),
        '2DOE' => array(
            'Id' => '2DOE',
            'Label' => 'Advertising|Display|Terminal Advertising, All Types|Electronic Display',
        ),
        '2DOG' => array(
            'Id' => '2DOG',
            'Label' => 'Editorial|Book|Reference Book, All Types|Printed',
        ),
        '2DOL' => array(
            'Id' => '2DOL',
            'Label' => 'Advertising|Display|Terminal Advertising, Bus Stop Advertising|Electronic Display',
        ),
        '2DOM' => array(
            'Id' => '2DOM',
            'Label' => 'Advertising|Display|Terminal Advertising, Bus Stop Advertising|Printed',
        ),
        '2DON' => array(
            'Id' => '2DON',
            'Label' => 'Advertising|Display|Terminal Advertising, Ferry Terminal Advertising|Electronic Display',
        ),
        '2DOR' => array(
            'Id' => '2DOR',
            'Label' => 'Advertising|Display|Terminal Advertising, Ferry Terminal Advertising|Printed',
        ),
        '2DOS' => array(
            'Id' => '2DOS',
            'Label' => 'Advertising|Display|Terminal Advertising, Shelter Advertising|Electronic Display',
        ),
        '2DOT' => array(
            'Id' => '2DOT',
            'Label' => 'Advertising|Marketing Materials|Artist\'s Reference, All Types|All Electronic Distribution Formats',
        ),
        '2DOW' => array(
            'Id' => '2DOW',
            'Label' => 'Advertising|Display|Terminal Advertising, Station Poster|Electronic Display',
        ),
        '2DRY' => array(
            'Id' => '2DRY',
            'Label' => 'Advertising|Display|Poster, Restroom Poster|Printed',
        ),
        '2DUB' => array(
            'Id' => '2DUB',
            'Label' => 'Advertising|Display|Terminal Advertising, Station Poster|Printed',
        ),
        '2DUE' => array(
            'Id' => '2DUE',
            'Label' => 'Advertising|Display|Terminal Advertising, Subway Terminal Advertising|Electronic Display',
        ),
        '2DUG' => array(
            'Id' => '2DUG',
            'Label' => 'Advertising|Display|Terminal Advertising, Subway Terminal Advertising|Printed',
        ),
        '2DUH' => array(
            'Id' => '2DUH',
            'Label' => 'Advertising|Periodicals|Magazine, Advertorial|All Electronic Distribution Formats',
        ),
        '2DUI' => array(
            'Id' => '2DUI',
            'Label' => 'Advertising|Display|Terminal Advertising, Train Terminal Advertising|Electronic Display',
        ),
        '2DUN' => array(
            'Id' => '2DUN',
            'Label' => 'Advertising|Display|Terminal Advertising, Train Terminal Advertising|Printed',
        ),
        '2DUO' => array(
            'Id' => '2DUO',
            'Label' => 'Advertising|Marketing Materials|All Marketing Material Types|Recordable Media',
        ),
        '2DUP' => array(
            'Id' => '2DUP',
            'Label' => 'Advertising|Periodicals|Magazine Reprints, All Types|Printed',
        ),
        '2DVA' => array(
            'Id' => '2DVA',
            'Label' => 'Advertising|Marketing Materials|DVD|Recordable Media',
        ),
        '2DVD' => array(
            'Id' => '2DVD',
            'Label' => 'Products|Merchandise|DVD|Recordable Media',
        ),
        '2DVE' => array(
            'Id' => '2DVE',
            'Label' => 'Editorial|Merchandise|DVD|Recordable Media',
        ),
        '2DVI' => array(
            'Id' => '2DVI',
            'Label' => 'Internal Company Use|Promotional Materials|DVD|Recordable Media',
        ),
        '2DVL' => array(
            'Id' => '2DVL',
            'Label' => 'Products|Product Packaging|Packaging For Recordable Media, Liner Notes|Printed',
        ),
        '2DVP' => array(
            'Id' => '2DVP',
            'Label' => 'Products|Product Packaging|Packaging For Recordable Media, All Packaging Types|Printed',
        ),
        '2DYE' => array(
            'Id' => '2DYE',
            'Label' => 'Advertising|Marketing Materials|All Marketing Material Types|Internet Email',
        ),
        '2EAB' => array(
            'Id' => '2EAB',
            'Label' => 'Editorial|Book|Retail Book, Directory|All E-Book Distribution Formats',
        ),
        '2EAC' => array(
            'Id' => '2EAC',
            'Label' => 'Advertising|Book|Retail Book, Directory|E-Book in Internet Website',
        ),
        '2EAD' => array(
            'Id' => '2EAD',
            'Label' => 'Advertising|Book|Retail Book, Directory|E-Book in Internet Downloadable File',
        ),
        '2EAE' => array(
            'Id' => '2EAE',
            'Label' => 'Advertising|Book|Retail Book, Directory|All E-Book Internet Distribution Formats',
        ),
        '2EAF' => array(
            'Id' => '2EAF',
            'Label' => 'Advertising|Book|Retail Book, Directory|E-Book on Recordable Media',
        ),
        '2EAG' => array(
            'Id' => '2EAG',
            'Label' => 'Advertising|Book|Retail Book, Directory|All E-Book Distribution Formats',
        ),
        '2EAH' => array(
            'Id' => '2EAH',
            'Label' => 'Advertising|Book|Textbook, All Types|E-Book in Internet Website',
        ),
        '2EAJ' => array(
            'Id' => '2EAJ',
            'Label' => 'Advertising|Book|Textbook, All Types|E-Book in Internet Downloadable File',
        ),
        '2EAK' => array(
            'Id' => '2EAK',
            'Label' => 'Advertising|Book|Textbook, All Types|All E-Book Internet Distribution Formats',
        ),
        '2EAL' => array(
            'Id' => '2EAL',
            'Label' => 'Advertising|Book|Textbook, All Types|E-Book on Recordable Media',
        ),
        '2EAM' => array(
            'Id' => '2EAM',
            'Label' => 'Advertising|Book|Textbook, All Types|All E-Book Distribution Formats',
        ),
        '2EAN' => array(
            'Id' => '2EAN',
            'Label' => 'Advertising|Book|All Book Types|All E-Book Internet Distribution Formats',
        ),
        '2EAP' => array(
            'Id' => '2EAP',
            'Label' => 'Advertising|Book|All Book Types|All E-Book Distribution Formats',
        ),
        '2EAQ' => array(
            'Id' => '2EAQ',
            'Label' => 'Editorial|Book|Reference Book, Encyclopedia|E-Book in Internet Website',
        ),
        '2EAR' => array(
            'Id' => '2EAR',
            'Label' => 'Advertising|Live Presentation|All Live Presentation Types|All Electronic Distribution Formats',
        ),
        '2EAT' => array(
            'Id' => '2EAT',
            'Label' => 'Products|Merchandise|Edible Media|Printed',
        ),
        '2EAU' => array(
            'Id' => '2EAU',
            'Label' => 'Editorial|Display|Poster, Educational Poster|Printed',
        ),
        '2EBA' => array(
            'Id' => '2EBA',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Lab Manual|E-Book in Internet Downloadable File',
        ),
        '2EBB' => array(
            'Id' => '2EBB',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Workbook|E-Book in Internet Downloadable File',
        ),
        '2EBC' => array(
            'Id' => '2EBC',
            'Label' => 'Editorial|Book|Retail Book, All Types|E-Book in Internet Downloadable File',
        ),
        '2EBD' => array(
            'Id' => '2EBD',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Teacher\'s Manual|E-Book in Internet Downloadable File',
        ),
        '2EBE' => array(
            'Id' => '2EBE',
            'Label' => 'Editorial|Book|Retail Book, Children\'s Book|E-Book in Internet Downloadable File',
        ),
        '2EBF' => array(
            'Id' => '2EBF',
            'Label' => 'Editorial|Book|Retail Book, Concept Book|E-Book in Internet Downloadable File',
        ),
        '2EBG' => array(
            'Id' => '2EBG',
            'Label' => 'Editorial|Book|Retail Book, Directory|E-Book in Internet Downloadable File',
        ),
        '2EBH' => array(
            'Id' => '2EBH',
            'Label' => 'Editorial|Book|Retail Book, Handbook|E-Book in Internet Downloadable File',
        ),
        '2EBI' => array(
            'Id' => '2EBI',
            'Label' => 'Editorial|Book|Retail Book, Hi-lo Book|E-Book in Internet Downloadable File',
        ),
        '2EBJ' => array(
            'Id' => '2EBJ',
            'Label' => 'Editorial|Book|Retail Book, Illustrated Book|E-Book in Internet Downloadable File',
        ),
        '2EBK' => array(
            'Id' => '2EBK',
            'Label' => 'Editorial|Book|All Book Types|E-Book in Internet Downloadable File',
        ),
        '2EBL' => array(
            'Id' => '2EBL',
            'Label' => 'Editorial|Book|Retail Book, Illustrated Guide|E-Book in Internet Downloadable File',
        ),
        '2EBM' => array(
            'Id' => '2EBM',
            'Label' => 'Editorial|Book|Retail Book, Manual|E-Book in Internet Downloadable File',
        ),
        '2EBN' => array(
            'Id' => '2EBN',
            'Label' => 'Editorial|Book|Retail Book, Novelty Book|E-Book in Internet Downloadable File',
        ),
        '2EBP' => array(
            'Id' => '2EBP',
            'Label' => 'Editorial|Book|Retail Book, Postcard Book|E-Book in Internet Downloadable File',
        ),
        '2EBQ' => array(
            'Id' => '2EBQ',
            'Label' => 'Editorial|Book|Retail Book, Young Adult Book|E-Book in Internet Downloadable File',
        ),
        '2EBR' => array(
            'Id' => '2EBR',
            'Label' => 'Editorial|Book|Textbook, All Types|E-Book in Internet Downloadable File',
        ),
        '2EBS' => array(
            'Id' => '2EBS',
            'Label' => 'Editorial|Book|Textbook, Compendium|E-Book in Internet Downloadable File',
        ),
        '2EBT' => array(
            'Id' => '2EBT',
            'Label' => 'Editorial|Book|Textbook, Middle Reader|E-Book in Internet Downloadable File',
        ),
        '2EBV' => array(
            'Id' => '2EBV',
            'Label' => 'Editorial|Book|Textbook, Student Edition|E-Book in Internet Downloadable File',
        ),
        '2EBW' => array(
            'Id' => '2EBW',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Teachers\' Edition|E-Book in Internet Downloadable File',
        ),
        '2ECU' => array(
            'Id' => '2ECU',
            'Label' => 'Advertising|Marketing Materials|Artist\'s Reference, All Types|All Internet Distribution Formats',
        ),
        '2EDH' => array(
            'Id' => '2EDH',
            'Label' => 'Advertising|Marketing Materials|Artist\'s Reference, All Types|Printed',
        ),
        '2EDU' => array(
            'Id' => '2EDU',
            'Label' => 'Advertising|Marketing Materials|CD ROM|Recordable Media',
        ),
        '2EEL' => array(
            'Id' => '2EEL',
            'Label' => 'Editorial|Book|Textbook, Compendium|Printed',
        ),
        '2EFF' => array(
            'Id' => '2EFF',
            'Label' => 'Advertising|Marketing Materials|Brochure|Internet Downloadable File',
        ),
        '2EFS' => array(
            'Id' => '2EFS',
            'Label' => 'Advertising|Marketing Materials|Brochure|Internet Email',
        ),
        '2EFT' => array(
            'Id' => '2EFT',
            'Label' => 'Advertising|Marketing Materials|Brochure|Recordable Media',
        ),
        '2EFX' => array(
            'Id' => '2EFX',
            'Label' => 'Motion Picture & TV|Motion Picture|Prop|All Electronic Distribution Formats',
        ),
        '2EGA' => array(
            'Id' => '2EGA',
            'Label' => 'Editorial|Book|Textbook, All Types|E-Book in Internet Email',
        ),
        '2EGB' => array(
            'Id' => '2EGB',
            'Label' => 'Editorial|Book|Retail Book, Children\'s Book|E-Book in Internet Email',
        ),
        '2EGC' => array(
            'Id' => '2EGC',
            'Label' => 'Editorial|Book|Retail Book, Children\'s Book|All E-Book Distribution Formats',
        ),
        '2EGD' => array(
            'Id' => '2EGD',
            'Label' => 'Editorial|Book|Retail Book, Concept Book|E-Book in Internet Email',
        ),
        '2EGE' => array(
            'Id' => '2EGE',
            'Label' => 'Editorial|Book|Retail Book, Concept Book|All E-Book Distribution Formats',
        ),
        '2EGG' => array(
            'Id' => '2EGG',
            'Label' => 'Advertising|Marketing Materials|Brochure|Printed',
        ),
        '2EGH' => array(
            'Id' => '2EGH',
            'Label' => 'Editorial|Book|Retail Book, Handbook|E-Book in Internet Email',
        ),
        '2EGJ' => array(
            'Id' => '2EGJ',
            'Label' => 'Editorial|Book|Retail Book, Handbook|All E-Book Distribution Formats',
        ),
        '2EGK' => array(
            'Id' => '2EGK',
            'Label' => 'Editorial|Book|Retail Book, Hi-lo Book|E-Book in Internet Email',
        ),
        '2EGL' => array(
            'Id' => '2EGL',
            'Label' => 'Editorial|Book|Retail Book, Hi-lo Book|All E-Book Distribution Formats',
        ),
        '2EGM' => array(
            'Id' => '2EGM',
            'Label' => 'Editorial|Book|Retail Book, Illustrated Book|E-Book in Internet Email',
        ),
        '2EGN' => array(
            'Id' => '2EGN',
            'Label' => 'Editorial|Book|Retail Book, Illustrated Book|All E-Book Distribution Formats',
        ),
        '2EGO' => array(
            'Id' => '2EGO',
            'Label' => 'Advertising|Marketing Materials|Coupon|Internet Downloadable File',
        ),
        '2EGP' => array(
            'Id' => '2EGP',
            'Label' => 'Editorial|Book|Retail Book, Illustrated Guide|E-Book in Internet Email',
        ),
        '2EGQ' => array(
            'Id' => '2EGQ',
            'Label' => 'Editorial|Book|Retail Book, Illustrated Guide|All E-Book Distribution Formats',
        ),
        '2EGR' => array(
            'Id' => '2EGR',
            'Label' => 'Editorial|Book|Retail Book, Illustrated Guide|All E-Book Distribution Formats',
        ),
        '2EGS' => array(
            'Id' => '2EGS',
            'Label' => 'Editorial|Book|Retail Book, Manual|E-Book in Internet Email',
        ),
        '2EGT' => array(
            'Id' => '2EGT',
            'Label' => 'Editorial|Book|Retail Book, Manual|All E-Book Distribution Formats',
        ),
        '2EGV' => array(
            'Id' => '2EGV',
            'Label' => 'Editorial|Book|Retail Book, Novelty Book|E-Book in Internet Email',
        ),
        '2EGW' => array(
            'Id' => '2EGW',
            'Label' => 'Editorial|Book|Retail Book, Novelty Book|All E-Book Distribution Formats',
        ),
        '2EGY' => array(
            'Id' => '2EGY',
            'Label' => 'Editorial|Book|Retail Book, Postcard Book|E-Book in Internet Email',
        ),
        '2EGZ' => array(
            'Id' => '2EGZ',
            'Label' => 'Editorial|Book|Retail Book, Postcard Book|All E-Book Distribution Formats',
        ),
        '2EJA' => array(
            'Id' => '2EJA',
            'Label' => 'Editorial|Book|All Book Types|All E-Book Distribution Formats',
        ),
        '2EJB' => array(
            'Id' => '2EJB',
            'Label' => 'Editorial|Book|Retail Book, Young Adult Book|E-Book in Internet Email',
        ),
        '2EJC' => array(
            'Id' => '2EJC',
            'Label' => 'Editorial|Book|Retail Book, Young Adult Book|All E-Book Distribution Formats',
        ),
        '2EJD' => array(
            'Id' => '2EJD',
            'Label' => 'Editorial|Book|Retail Book, All Types|E-Book in Internet Email',
        ),
        '2EJE' => array(
            'Id' => '2EJE',
            'Label' => 'Editorial|Book|Retail Book, All Types|All E-Book Distribution Formats',
        ),
        '2EJF' => array(
            'Id' => '2EJF',
            'Label' => 'Editorial|Book|Textbook, Compendium|E-Book in Internet Email',
        ),
        '2EJJ' => array(
            'Id' => '2EJJ',
            'Label' => 'Editorial|Book|Textbook, Compendium|All E-Book Distribution Formats',
        ),
        '2EJK' => array(
            'Id' => '2EJK',
            'Label' => 'Editorial|Book|Textbook, Middle Reader|E-Book in Internet Email',
        ),
        '2EJL' => array(
            'Id' => '2EJL',
            'Label' => 'Editorial|Book|Textbook, Middle Reader|All E-Book Distribution Formats',
        ),
        '2EJM' => array(
            'Id' => '2EJM',
            'Label' => 'Editorial|Book|Textbook, Student Edition|E-Book in Internet Email',
        ),
        '2EJN' => array(
            'Id' => '2EJN',
            'Label' => 'Editorial|Book|Textbook, Student Edition|All E-Book Distribution Formats',
        ),
        '2EJP' => array(
            'Id' => '2EJP',
            'Label' => 'Editorial|Book|Textbook, All Types|All E-Book Distribution Formats',
        ),
        '2EJQ' => array(
            'Id' => '2EJQ',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Lab Manual|E-Book in Internet Email',
        ),
        '2EJR' => array(
            'Id' => '2EJR',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Lab Manual|All E-Book Distribution Formats',
        ),
        '2EJS' => array(
            'Id' => '2EJS',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Teachers\' Edition|E-Book in Internet Email',
        ),
        '2EJT' => array(
            'Id' => '2EJT',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Teachers\' Edition|All E-Book Distribution Formats',
        ),
        '2EJU' => array(
            'Id' => '2EJU',
            'Label' => 'Editorial|Book|All Book Types|E-Book in Internet Email',
        ),
        '2EJV' => array(
            'Id' => '2EJV',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Teacher\'s Manual|E-Book in Internet Email',
        ),
        '2EJW' => array(
            'Id' => '2EJW',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Teacher\'s Manual|All E-Book Distribution Formats',
        ),
        '2EJY' => array(
            'Id' => '2EJY',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Workbook|E-Book in Internet Email',
        ),
        '2EJZ' => array(
            'Id' => '2EJZ',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Workbook|All E-Book Distribution Formats',
        ),
        '2EKA' => array(
            'Id' => '2EKA',
            'Label' => 'Editorial|Book|All Book Types|All E-Book Internet Distribution Formats',
        ),
        '2EKB' => array(
            'Id' => '2EKB',
            'Label' => 'Editorial|Book|Retail Book, Children\'s Book|All E-Book Internet Distribution Formats',
        ),
        '2EKC' => array(
            'Id' => '2EKC',
            'Label' => 'Editorial|Book|Retail Book, Concept Book|All E-Book Internet Distribution Formats',
        ),
        '2EKD' => array(
            'Id' => '2EKD',
            'Label' => 'Editorial|Book|Retail Book, Handbook|All E-Book Internet Distribution Formats',
        ),
        '2EKE' => array(
            'Id' => '2EKE',
            'Label' => 'Editorial|Book|Retail Book, Hi-lo Book|All E-Book Internet Distribution Formats',
        ),
        '2EKF' => array(
            'Id' => '2EKF',
            'Label' => 'Editorial|Book|Retail Book, Illustrated Book|All E-Book Internet Distribution Formats',
        ),
        '2EKG' => array(
            'Id' => '2EKG',
            'Label' => 'Editorial|Book|Retail Book, Illustrated Guide|All E-Book Internet Distribution Formats',
        ),
        '2EKH' => array(
            'Id' => '2EKH',
            'Label' => 'Editorial|Book|Retail Book, Manual|All E-Book Internet Distribution Formats',
        ),
        '2EKJ' => array(
            'Id' => '2EKJ',
            'Label' => 'Editorial|Book|Retail Book, Novelty Book|All E-Book Internet Distribution Formats',
        ),
        '2EKK' => array(
            'Id' => '2EKK',
            'Label' => 'Editorial|Book|Retail Book, Postcard Book|All E-Book Internet Distribution Formats',
        ),
        '2EKL' => array(
            'Id' => '2EKL',
            'Label' => 'Editorial|Book|Retail Book, Young Adult Book|All E-Book Internet Distribution Formats',
        ),
        '2EKM' => array(
            'Id' => '2EKM',
            'Label' => 'Editorial|Book|Retail Book, All Types|All E-Book Internet Distribution Formats',
        ),
        '2EKN' => array(
            'Id' => '2EKN',
            'Label' => 'Editorial|Book|Textbook, Compendium|All E-Book Internet Distribution Formats',
        ),
        '2EKP' => array(
            'Id' => '2EKP',
            'Label' => 'Editorial|Book|Textbook, Middle Reader|All E-Book Internet Distribution Formats',
        ),
        '2EKQ' => array(
            'Id' => '2EKQ',
            'Label' => 'Editorial|Book|Textbook, Student Edition|All E-Book Internet Distribution Formats',
        ),
        '2EKR' => array(
            'Id' => '2EKR',
            'Label' => 'Editorial|Book|Textbook, All Types|All E-Book Internet Distribution Formats',
        ),
        '2EKS' => array(
            'Id' => '2EKS',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Lab Manual|All E-Book Internet Distribution Formats',
        ),
        '2EKT' => array(
            'Id' => '2EKT',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Teachers\' Edition|All E-Book Internet Distribution Formats',
        ),
        '2EKU' => array(
            'Id' => '2EKU',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Teacher\'s Manual|All E-Book Internet Distribution Formats',
        ),
        '2EKV' => array(
            'Id' => '2EKV',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Workbook|All E-Book Internet Distribution Formats',
        ),
        '2EKW' => array(
            'Id' => '2EKW',
            'Label' => 'Editorial|Book|Artist\'s Reference, All Types|All E-Book Internet Distribution Formats',
        ),
        '2ELE' => array(
            'Id' => '2ELE',
            'Label' => 'Advertising|Display|Poster, Elevator Advertising|Printed',
        ),
        '2ELF' => array(
            'Id' => '2ELF',
            'Label' => 'Advertising|Marketing Materials|Catalog|Recordable Media',
        ),
        '2ELK' => array(
            'Id' => '2ELK',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, All Ancillary Types|Printed',
        ),
        '2ELL' => array(
            'Id' => '2ELL',
            'Label' => 'Advertising|Marketing Materials|Catalog|Internet Downloadable File',
        ),
        '2ELM' => array(
            'Id' => '2ELM',
            'Label' => 'Advertising|Book|Textbook, All Types|Printed',
        ),
        '2ELS' => array(
            'Id' => '2ELS',
            'Label' => 'Advertising|Marketing Materials|Catalog|Internet Email',
        ),
        '2EMA' => array(
            'Id' => '2EMA',
            'Label' => 'Advertising|Email|All Email Types|Internet Email',
        ),
        '2EMB' => array(
            'Id' => '2EMB',
            'Label' => 'Editorial|Book|Reference Book, Encyclopedia|E-Book in Internet Downloadable File',
        ),
        '2EMC' => array(
            'Id' => '2EMC',
            'Label' => 'Editorial|Book|Reference Book, Encyclopedia|All E-Book Internet Distribution Formats',
        ),
        '2EMD' => array(
            'Id' => '2EMD',
            'Label' => 'Editorial|Book|Reference Book, Encyclopedia|E-Book on Recordable Media',
        ),
        '2EMF' => array(
            'Id' => '2EMF',
            'Label' => 'Editorial|Book|Reference Book, Telephone Book|E-Book in Internet Website',
        ),
        '2EMG' => array(
            'Id' => '2EMG',
            'Label' => 'Editorial|Book|Reference Book, Telephone Book|E-Book in Internet Downloadable File',
        ),
        '2EMH' => array(
            'Id' => '2EMH',
            'Label' => 'Editorial|Book|Reference Book, Telephone Book|All E-Book Internet Distribution Formats',
        ),
        '2EMI' => array(
            'Id' => '2EMI',
            'Label' => 'Editorial|Book|Reference Book, Telephone Book|E-Book on Recordable Media',
        ),
        '2EMJ' => array(
            'Id' => '2EMJ',
            'Label' => 'Editorial|Book|Reference Book, Telephone Book|All E-Book Distribution Formats',
        ),
        '2EMK' => array(
            'Id' => '2EMK',
            'Label' => 'Editorial|Book|Reference Book, All Types|E-Book in Internet Website',
        ),
        '2EML' => array(
            'Id' => '2EML',
            'Label' => 'Editorial|Book|Reference Book, All Types|E-Book in Internet Downloadable File',
        ),
        '2EMM' => array(
            'Id' => '2EMM',
            'Label' => 'Editorial|Book|Reference Book, All Types|All E-Book Internet Distribution Formats',
        ),
        '2EMN' => array(
            'Id' => '2EMN',
            'Label' => 'Editorial|Book|Reference Book, All Types|E-Book on Recordable Media',
        ),
        '2EMP' => array(
            'Id' => '2EMP',
            'Label' => 'Editorial|Book|Reference Book, All Types|All E-Book Distribution Formats',
        ),
        '2EMQ' => array(
            'Id' => '2EMQ',
            'Label' => 'Editorial|Book|Trade Book, All Types|E-Book in Internet Downloadable File',
        ),
        '2EMR' => array(
            'Id' => '2EMR',
            'Label' => 'Editorial|Book|Trade Book, All Types|E-Book in Internet Email',
        ),
        '2EMS' => array(
            'Id' => '2EMS',
            'Label' => 'Editorial|Book|Trade Book, All Types|All E-Book Internet Distribution Formats',
        ),
        '2EMT' => array(
            'Id' => '2EMT',
            'Label' => 'Editorial|Book|Trade Book, All Types|All E-Book Distribution Formats',
        ),
        '2EMU' => array(
            'Id' => '2EMU',
            'Label' => 'Editorial|Book|Textbook, Course Pack|Printed',
        ),
        '2EMV' => array(
            'Id' => '2EMV',
            'Label' => 'Editorial|Book|Artist\'s Reference, All Types|All E-Book Distribution Formats',
        ),
        '2END' => array(
            'Id' => '2END',
            'Label' => 'Advertising|Marketing Materials|Magalog|Internet Downloadable File',
        ),
        '2ENG' => array(
            'Id' => '2ENG',
            'Label' => 'Advertising|Marketing Materials|Coupon|Internet Email',
        ),
        '2ENS' => array(
            'Id' => '2ENS',
            'Label' => 'Advertising|Marketing Materials|Coupon|Recordable Media',
        ),
        '2EON' => array(
            'Id' => '2EON',
            'Label' => 'Advertising|Marketing Materials|Magalog|Internet Email',
        ),
        '2EPA' => array(
            'Id' => '2EPA',
            'Label' => 'Editorial|Book|All Book Types|E-Book in Internet Website',
        ),
        '2EPB' => array(
            'Id' => '2EPB',
            'Label' => 'Editorial|Book|Retail Book, All Types|E-Book in Internet Website',
        ),
        '2EPC' => array(
            'Id' => '2EPC',
            'Label' => 'Editorial|Book|Retail Book, Children\'s Book|E-Book in Internet Website',
        ),
        '2EPD' => array(
            'Id' => '2EPD',
            'Label' => 'Editorial|Book|Retail Book, Concept Book|E-Book in Internet Website',
        ),
        '2EPE' => array(
            'Id' => '2EPE',
            'Label' => 'Editorial|Book|Retail Book, Directory|E-Book in Internet Website',
        ),
        '2EPF' => array(
            'Id' => '2EPF',
            'Label' => 'Editorial|Book|Retail Book, Handbook|E-Book in Internet Website',
        ),
        '2EPG' => array(
            'Id' => '2EPG',
            'Label' => 'Editorial|Book|Retail Book, Hi-lo Book|E-Book in Internet Website',
        ),
        '2EPH' => array(
            'Id' => '2EPH',
            'Label' => 'Editorial|Book|Retail Book, Illustrated Book|E-Book in Internet Website',
        ),
        '2EPJ' => array(
            'Id' => '2EPJ',
            'Label' => 'Editorial|Book|Retail Book, Illustrated Guide|E-Book in Internet Website',
        ),
        '2EPK' => array(
            'Id' => '2EPK',
            'Label' => 'Editorial|Book|Retail Book, Manual|E-Book in Internet Website',
        ),
        '2EPL' => array(
            'Id' => '2EPL',
            'Label' => 'Editorial|Book|Retail Book, Novelty Book|E-Book in Internet Website',
        ),
        '2EPM' => array(
            'Id' => '2EPM',
            'Label' => 'Editorial|Book|Retail Book, Postcard Book|E-Book in Internet Website',
        ),
        '2EPN' => array(
            'Id' => '2EPN',
            'Label' => 'Editorial|Book|Retail Book, Young Adult Book|E-Book in Internet Website',
        ),
        '2EPP' => array(
            'Id' => '2EPP',
            'Label' => 'Editorial|Book|Textbook, All Types|E-Book in Internet Website',
        ),
        '2EPQ' => array(
            'Id' => '2EPQ',
            'Label' => 'Editorial|Book|Textbook, Compendium|E-Book in Internet Website',
        ),
        '2EPR' => array(
            'Id' => '2EPR',
            'Label' => 'Editorial|Book|Textbook, Middle Reader|E-Book in Internet Website',
        ),
        '2EPS' => array(
            'Id' => '2EPS',
            'Label' => 'Editorial|Book|Textbook, Student Edition|E-Book in Internet Website',
        ),
        '2EPT' => array(
            'Id' => '2EPT',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Teachers\' Edition|E-Book in Internet Website',
        ),
        '2EPV' => array(
            'Id' => '2EPV',
            'Label' => 'Editorial|Book|Trade Book, All Types|E-Book in Internet Website',
        ),
        '2EPW' => array(
            'Id' => '2EPW',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Teacher\'s Manual|E-Book in Internet Website',
        ),
        '2EPY' => array(
            'Id' => '2EPY',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Workbook|E-Book in Internet Website',
        ),
        '2EPZ' => array(
            'Id' => '2EPZ',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Lab Manual|E-Book in Internet Website',
        ),
        '2ERA' => array(
            'Id' => '2ERA',
            'Label' => 'Editorial|Book|Trade Book, All Types|E-Book on Recordable Media',
        ),
        '2ERB' => array(
            'Id' => '2ERB',
            'Label' => 'Editorial|Book|Retail Book, Children\'s Book|E-Book on Recordable Media',
        ),
        '2ERC' => array(
            'Id' => '2ERC',
            'Label' => 'Editorial|Book|Retail Book, Concept Book|E-Book on Recordable Media',
        ),
        '2ERD' => array(
            'Id' => '2ERD',
            'Label' => 'Editorial|Book|Retail Book, Directory|E-Book on Recordable Media',
        ),
        '2ERF' => array(
            'Id' => '2ERF',
            'Label' => 'Editorial|Book|Retail Book, Handbook|E-Book on Recordable Media',
        ),
        '2ERG' => array(
            'Id' => '2ERG',
            'Label' => 'Editorial|Book|Retail Book, Hi-lo Book|E-Book on Recordable Media',
        ),
        '2ERH' => array(
            'Id' => '2ERH',
            'Label' => 'Editorial|Book|Retail Book, Illustrated Book|E-Book on Recordable Media',
        ),
        '2ERI' => array(
            'Id' => '2ERI',
            'Label' => 'Editorial|Book|Retail Book, Illustrated Guide|E-Book on Recordable Media',
        ),
        '2ERJ' => array(
            'Id' => '2ERJ',
            'Label' => 'Editorial|Book|Retail Book, Manual|E-Book on Recordable Media',
        ),
        '2ERK' => array(
            'Id' => '2ERK',
            'Label' => 'Editorial|Book|Retail Book, Novelty Book|E-Book on Recordable Media',
        ),
        '2ERL' => array(
            'Id' => '2ERL',
            'Label' => 'Editorial|Book|Retail Book, Postcard Book|E-Book on Recordable Media',
        ),
        '2ERM' => array(
            'Id' => '2ERM',
            'Label' => 'Editorial|Book|Retail Book, Young Adult Book|E-Book on Recordable Media',
        ),
        '2ERN' => array(
            'Id' => '2ERN',
            'Label' => 'Editorial|Book|Retail Book, All Types|E-Book on Recordable Media',
        ),
        '2ERP' => array(
            'Id' => '2ERP',
            'Label' => 'Editorial|Book|Textbook, Compendium|E-Book on Recordable Media',
        ),
        '2ERQ' => array(
            'Id' => '2ERQ',
            'Label' => 'Editorial|Book|Textbook, Middle Reader|E-Book on Recordable Media',
        ),
        '2ERS' => array(
            'Id' => '2ERS',
            'Label' => 'Editorial|Book|Textbook, Student Edition|E-Book on Recordable Media',
        ),
        '2ERT' => array(
            'Id' => '2ERT',
            'Label' => 'Editorial|Book|Textbook, All Types|E-Book on Recordable Media',
        ),
        '2ERU' => array(
            'Id' => '2ERU',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Lab Manual|E-Book on Recordable Media',
        ),
        '2ERV' => array(
            'Id' => '2ERV',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Teachers\' Edition|E-Book on Recordable Media',
        ),
        '2ERW' => array(
            'Id' => '2ERW',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Teacher\'s Manual|E-Book on Recordable Media',
        ),
        '2ERY' => array(
            'Id' => '2ERY',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Workbook|E-Book on Recordable Media',
        ),
        '2ERZ' => array(
            'Id' => '2ERZ',
            'Label' => 'Editorial|Book|All Book Types|E-Book on Recordable Media',
        ),
        '2ESS' => array(
            'Id' => '2ESS',
            'Label' => 'Advertising|Marketing Materials|Promotional Calendar, Multi-Page|Internet Downloadable File',
        ),
        '2ETA' => array(
            'Id' => '2ETA',
            'Label' => 'Advertising|Marketing Materials|Promotional Calendar, Multi-Page|Internet Email',
        ),
        '2EVE' => array(
            'Id' => '2EVE',
            'Label' => 'Products|Product Packaging|Retail Packaging, All Packaging Types|Printed',
        ),
        '2EWE' => array(
            'Id' => '2EWE',
            'Label' => 'Advertising|Marketing Materials|Promotional Calendar, One Page|All Electronic Distribution Formats',
        ),
        '2EYE' => array(
            'Id' => '2EYE',
            'Label' => 'Advertising|Live Presentation|Panel Presentation|Projected Display',
        ),
        '2FAB' => array(
            'Id' => '2FAB',
            'Label' => 'Internal Company Use|Website|Web Page, Content Body|Recordable Media',
        ),
        '2FAC' => array(
            'Id' => '2FAC',
            'Label' => 'Internal Company Use|Website|Web Page, Content Body|All Electronic Distribution Formats',
        ),
        '2FAD' => array(
            'Id' => '2FAD',
            'Label' => 'Advertising|Display|Poster, Movie Poster|Printed',
        ),
        '2FAH' => array(
            'Id' => '2FAH',
            'Label' => 'Internal Company Use|Website|Web Page, Design Element|Intranet and Extranet Website',
        ),
        '2FAI' => array(
            'Id' => '2FAI',
            'Label' => 'Internal Company Use|Website|Web Page, Design Element|Recordable Media',
        ),
        '2FAJ' => array(
            'Id' => '2FAJ',
            'Label' => 'Internal Company Use|Website|Web Page, Design Element|All Electronic Distribution Formats',
        ),
        '2FAN' => array(
            'Id' => '2FAN',
            'Label' => 'Editorial|Periodicals|Newspaper, Tabloid|Printed',
        ),
        '2FAR' => array(
            'Id' => '2FAR',
            'Label' => 'Products|Merchandise|Retail Postcard|Printed',
        ),
        '2FAS' => array(
            'Id' => '2FAS',
            'Label' => 'Advertising|Marketing Materials|Promotional Calendar, One Page|Internet Downloadable File',
        ),
        '2FAT' => array(
            'Id' => '2FAT',
            'Label' => 'Advertising|Display|Poster, All Types|Printed',
        ),
        '2FAX' => array(
            'Id' => '2FAX',
            'Label' => 'Advertising|Marketing Materials|Magalog|Recordable Media',
        ),
        '2FAY' => array(
            'Id' => '2FAY',
            'Label' => 'Advertising|Marketing Materials|Promotional Calendar, One Page|Internet Email',
        ),
        '2FEA' => array(
            'Id' => '2FEA',
            'Label' => 'Motion Picture & TV|Motion Picture|Feature Film|Projected Display',
        ),
        '2FEH' => array(
            'Id' => '2FEH',
            'Label' => 'Advertising|Marketing Materials|Promotional E-card|Recordable Media',
        ),
        '2FEM' => array(
            'Id' => '2FEM',
            'Label' => 'Products|Merchandise|Textiles|Printed or Woven',
        ),
        '2FEN' => array(
            'Id' => '2FEN',
            'Label' => 'Advertising|Marketing Materials|Promotional E-card|Internet Downloadable File',
        ),
        '2FER' => array(
            'Id' => '2FER',
            'Label' => 'Advertising|Display|Transit Advertising, Ferry Advertising|Printed',
        ),
        '2FET' => array(
            'Id' => '2FET',
            'Label' => 'Advertising|Marketing Materials|Promotional E-card|Internet Email',
        ),
        '2FEU' => array(
            'Id' => '2FEU',
            'Label' => 'Advertising|Marketing Materials|Promotional E-card|All Internet Distribution Formats',
        ),
        '2FEZ' => array(
            'Id' => '2FEZ',
            'Label' => 'Advertising|Merchandise|Apparel, General Apparel|Printed or Woven',
        ),
        '2FIB' => array(
            'Id' => '2FIB',
            'Label' => 'Products|Merchandise|Screen Saver|Internet Downloadable File',
        ),
        '2FID' => array(
            'Id' => '2FID',
            'Label' => 'Advertising|Merchandise|Apparel, T-Shirts|Printed or Woven',
        ),
        '2FIE' => array(
            'Id' => '2FIE',
            'Label' => 'Advertising|Merchandise|Folder|Printed',
        ),
        '2FIG' => array(
            'Id' => '2FIG',
            'Label' => 'Advertising|Marketing Materials|Catalog|Printed',
        ),
        '2FIL' => array(
            'Id' => '2FIL',
            'Label' => 'Motion Picture & TV|Motion Picture|Feature Film|All Electronic Distribution Formats',
        ),
        '2FIN' => array(
            'Id' => '2FIN',
            'Label' => 'Advertising|Point of Purchase|Kiosk, All Types|Printed',
        ),
        '2FIR' => array(
            'Id' => '2FIR',
            'Label' => 'Advertising|Periodicals|All Periodical Types|Recordable Media',
        ),
        '2FIT' => array(
            'Id' => '2FIT',
            'Label' => 'Motion Picture & TV|Motion Picture|Short Film|All Electronic Distribution Formats',
        ),
        '2FIX' => array(
            'Id' => '2FIX',
            'Label' => 'Motion Picture & TV|Motion Picture|All Motion Picture Types|Projected Display',
        ),
        '2FIZ' => array(
            'Id' => '2FIZ',
            'Label' => 'Advertising|Periodicals|All Periodical Types|Internet Website',
        ),
        '2FLU' => array(
            'Id' => '2FLU',
            'Label' => 'Advertising|Periodicals|All Periodical Types|All Electronic Distribution Formats',
        ),
        '2FLY' => array(
            'Id' => '2FLY',
            'Label' => 'Advertising|Marketing Materials|Flyer|Printed',
        ),
        '2FOB' => array(
            'Id' => '2FOB',
            'Label' => 'Advertising|Periodicals|All Periodical Types|All Internet Distribution Formats',
        ),
        '2FOE' => array(
            'Id' => '2FOE',
            'Label' => 'Internal Company Use|Periodicals|Magazine, Custom Published|Internet Email',
        ),
        '2FOG' => array(
            'Id' => '2FOG',
            'Label' => 'Advertising|Display|Store Display, All Display Types|Printed',
        ),
        '2FOH' => array(
            'Id' => '2FOH',
            'Label' => 'Advertising|Periodicals|Annual Report|Internet Downloadable File',
        ),
        '2FOL' => array(
            'Id' => '2FOL',
            'Label' => 'Products|Merchandise|Folder|Printed',
        ),
        '2FON' => array(
            'Id' => '2FON',
            'Label' => 'Advertising|Periodicals|Annual Report|Internet Website',
        ),
        '2FOP' => array(
            'Id' => '2FOP',
            'Label' => 'Advertising|Periodicals|Annual Report|Internet Email',
        ),
        '2FOU' => array(
            'Id' => '2FOU',
            'Label' => 'Advertising|Periodicals|Annual Report|All Electronic Distribution Formats',
        ),
        '2FOX' => array(
            'Id' => '2FOX',
            'Label' => 'Editorial|Book|Textbook, All Types|Printed',
        ),
        '2FOY' => array(
            'Id' => '2FOY',
            'Label' => 'Advertising|Periodicals|Annual Report|All Internet Distribution Formats',
        ),
        '2FPO' => array(
            'Id' => '2FPO',
            'Label' => 'Internal Company Use|Comp Use|All Comp Types|All Electronic Distribution Formats',
        ),
        '2FRA' => array(
            'Id' => '2FRA',
            'Label' => 'Products|Product Packaging|Picture Frame Insert|Printed',
        ),
        '2FRO' => array(
            'Id' => '2FRO',
            'Label' => 'Internal Company Use|Comp Use|All Comp Types|Printed',
        ),
        '2FRY' => array(
            'Id' => '2FRY',
            'Label' => 'Motion Picture & TV|Motion Picture|Prop|Recordable Media',
        ),
        '2FUB' => array(
            'Id' => '2FUB',
            'Label' => 'Advertising|Periodicals|Belly Band|Printed',
        ),
        '2FUD' => array(
            'Id' => '2FUD',
            'Label' => 'Advertising|Periodicals|Cover Wrap|Printed',
        ),
        '2FUN' => array(
            'Id' => '2FUN',
            'Label' => 'Products|Merchandise|Playing Cards|Printed',
        ),
        '2FUR' => array(
            'Id' => '2FUR',
            'Label' => 'Advertising|Live Presentation|Sales Presentation|Projected Display',
        ),
        '2GAB' => array(
            'Id' => '2GAB',
            'Label' => 'Products|Merchandise|Game, Computer Game|Internet Downloadable File',
        ),
        '2GAD' => array(
            'Id' => '2GAD',
            'Label' => 'Products|Merchandise|Game, All Types|All Internet Distribution Formats',
        ),
        '2GAE' => array(
            'Id' => '2GAE',
            'Label' => 'Products|Merchandise|Game, Computer Game|All Internet Distribution Formats',
        ),
        '2GAG' => array(
            'Id' => '2GAG',
            'Label' => 'Products|Merchandise|Game, Computer Game|Recordable Media',
        ),
        '2GAH' => array(
            'Id' => '2GAH',
            'Label' => 'Products|Merchandise|Game, Computer Game|Internet Email',
        ),
        '2GAL' => array(
            'Id' => '2GAL',
            'Label' => 'Editorial|Display|Gallery Exhibition|Printed',
        ),
        '2GAM' => array(
            'Id' => '2GAM',
            'Label' => 'Products|Merchandise|Game, All Types|All Electronic Distribution Formats',
        ),
        '2GAN' => array(
            'Id' => '2GAN',
            'Label' => 'Products|Merchandise|Game, Computer Game|All Electronic Distribution Formats',
        ),
        '2GAP' => array(
            'Id' => '2GAP',
            'Label' => 'Products|Merchandise|Game, All Types|Printed',
        ),
        '2GAR' => array(
            'Id' => '2GAR',
            'Label' => 'Products|Merchandise|Game, All Types|Recordable Media',
        ),
        '2GAS' => array(
            'Id' => '2GAS',
            'Label' => 'Products|Merchandise|Game, All Types|Internet Email',
        ),
        '2GAT' => array(
            'Id' => '2GAT',
            'Label' => 'Products|Merchandise|Game, All Types|Internet Downloadable File',
        ),
        '2GBM' => array(
            'Id' => '2GBM',
            'Label' => 'Editorial|Book|Textbook, Course Pack|Internet Downloadable File',
        ),
        '2GBN' => array(
            'Id' => '2GBN',
            'Label' => 'Editorial|Book|Textbook, Course Pack|Internet Website',
        ),
        '2GBP' => array(
            'Id' => '2GBP',
            'Label' => 'Editorial|Book|Textbook, Course Pack|All Distribution Formats',
        ),
        '2GBQ' => array(
            'Id' => '2GBQ',
            'Label' => 'Editorial|Book|Textbook, Course Pack|All Internet Distribution Formats',
        ),
        '2GBW' => array(
            'Id' => '2GBW',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, All Ancillary Types|All Distribution Formats',
        ),
        '2GBY' => array(
            'Id' => '2GBY',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, All Ancillary Types|All Internet Distribution Formats',
        ),
        '2GBZ' => array(
            'Id' => '2GBZ',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Educational Film Set|Projected Display',
        ),
        '2GDJ' => array(
            'Id' => '2GDJ',
            'Label' => 'Editorial|Display|Gallery Exhibition|Electronic Display',
        ),
        '2GDK' => array(
            'Id' => '2GDK',
            'Label' => 'Editorial|Display|Museum Display|Electronic Display',
        ),
        '2GDM' => array(
            'Id' => '2GDM',
            'Label' => 'Editorial|Periodicals|Magazine, All Types|Internet Downloadable File',
        ),
        '2GDP' => array(
            'Id' => '2GDP',
            'Label' => 'Editorial|Periodicals|Magazine, All Types|Internet Website',
        ),
        '2GDQ' => array(
            'Id' => '2GDQ',
            'Label' => 'Editorial|Periodicals|Magazine, All Types|Internet Email',
        ),
        '2GDR' => array(
            'Id' => '2GDR',
            'Label' => 'Editorial|Periodicals|Magazine, All Types|Recordable Media',
        ),
        '2GDS' => array(
            'Id' => '2GDS',
            'Label' => 'Editorial|Periodicals|Magazine, All Types|All Electronic Distribution Formats',
        ),
        '2GDT' => array(
            'Id' => '2GDT',
            'Label' => 'Editorial|Periodicals|Magazine, All Types|All Internet Distribution Formats',
        ),
        '2GDV' => array(
            'Id' => '2GDV',
            'Label' => 'Editorial|Periodicals|Magazine, Consumer|Recordable Media',
        ),
        '2GDW' => array(
            'Id' => '2GDW',
            'Label' => 'Editorial|Periodicals|Magazine, Consumer|Internet Website',
        ),
        '2GDY' => array(
            'Id' => '2GDY',
            'Label' => 'Editorial|Periodicals|Magazine, Consumer|Internet Downloadable File',
        ),
        '2GDZ' => array(
            'Id' => '2GDZ',
            'Label' => 'Editorial|Periodicals|Magazine, Consumer|All Electronic Distribution Formats',
        ),
        '2GEA' => array(
            'Id' => '2GEA',
            'Label' => 'Editorial|Periodicals|Magazine, Consumer|All Internet Distribution Formats',
        ),
        '2GEC' => array(
            'Id' => '2GEC',
            'Label' => 'Editorial|Periodicals|Magazine, Custom Published|Recordable Media',
        ),
        '2GED' => array(
            'Id' => '2GED',
            'Label' => 'Internal Company Use|Internal Review|All Review Types|Printed',
        ),
        '2GEE' => array(
            'Id' => '2GEE',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Workbook|Printed',
        ),
        '2GEF' => array(
            'Id' => '2GEF',
            'Label' => 'Editorial|Periodicals|Magazine, Custom Published|Internet Website',
        ),
        '2GEG' => array(
            'Id' => '2GEG',
            'Label' => 'Editorial|Periodicals|Magazine, Custom Published|Internet Downloadable File',
        ),
        '2GEH' => array(
            'Id' => '2GEH',
            'Label' => 'Editorial|Periodicals|Magazine, Custom Published|All Electronic Distribution Formats',
        ),
        '2GEI' => array(
            'Id' => '2GEI',
            'Label' => 'Editorial|Periodicals|Magazine, Custom Published|All Internet Distribution Formats',
        ),
        '2GEK' => array(
            'Id' => '2GEK',
            'Label' => 'Editorial|Periodicals|Magazine, Education|Recordable Media',
        ),
        '2GEL' => array(
            'Id' => '2GEL',
            'Label' => 'Products|Merchandise|Screen Saver|Internet Email',
        ),
        '2GEM' => array(
            'Id' => '2GEM',
            'Label' => 'Editorial|Periodicals|Magazine, Consumer|Printed',
        ),
        '2GEN' => array(
            'Id' => '2GEN',
            'Label' => 'Advertising|Periodicals|Magazine, Consumer|Internet Downloadable File',
        ),
        '2GEP' => array(
            'Id' => '2GEP',
            'Label' => 'Editorial|Periodicals|Magazine, Education|Internet Website',
        ),
        '2GEQ' => array(
            'Id' => '2GEQ',
            'Label' => 'Editorial|Periodicals|Magazine, Education|Internet Downloadable File',
        ),
        '2GER' => array(
            'Id' => '2GER',
            'Label' => 'Editorial|Periodicals|Magazine, Education|All Electronic Distribution Formats',
        ),
        '2GES' => array(
            'Id' => '2GES',
            'Label' => 'Editorial|Periodicals|Magazine, Education|All Internet Distribution Formats',
        ),
        '2GEV' => array(
            'Id' => '2GEV',
            'Label' => 'Editorial|Periodicals|Magazine, Partworks|Internet Website',
        ),
        '2GEW' => array(
            'Id' => '2GEW',
            'Label' => 'Editorial|Periodicals|Magazine, Partworks|Internet Downloadable File',
        ),
        '2GEY' => array(
            'Id' => '2GEY',
            'Label' => 'Advertising|Periodicals|Magazine, Consumer|All Electronic Distribution Formats',
        ),
        '2GEZ' => array(
            'Id' => '2GEZ',
            'Label' => 'Editorial|Periodicals|Magazine, Partworks|Recordable Media',
        ),
        '2GFA' => array(
            'Id' => '2GFA',
            'Label' => 'Editorial|Periodicals|Magazine, Partworks|All Electronic Distribution Formats',
        ),
        '2GFB' => array(
            'Id' => '2GFB',
            'Label' => 'Editorial|Periodicals|Magazine, Partworks|All Internet Distribution Formats',
        ),
        '2GFD' => array(
            'Id' => '2GFD',
            'Label' => 'Editorial|Periodicals|Magazine, Trade|Recordable Media',
        ),
        '2GFG' => array(
            'Id' => '2GFG',
            'Label' => 'Editorial|Periodicals|Newsletter|Recordable Media',
        ),
        '2GFH' => array(
            'Id' => '2GFH',
            'Label' => 'Editorial|Periodicals|Newsletter|Internet Website',
        ),
        '2GFI' => array(
            'Id' => '2GFI',
            'Label' => 'Editorial|Periodicals|Newsletter|Internet Downloadable File',
        ),
        '2GFJ' => array(
            'Id' => '2GFJ',
            'Label' => 'Editorial|Periodicals|Newsletter|All Electronic Distribution Formats',
        ),
        '2GFK' => array(
            'Id' => '2GFK',
            'Label' => 'Editorial|Periodicals|Newsletter|All Internet Distribution Formats',
        ),
        '2GFM' => array(
            'Id' => '2GFM',
            'Label' => 'Editorial|Periodicals|Newspaper, All Types|Recordable Media',
        ),
        '2GFN' => array(
            'Id' => '2GFN',
            'Label' => 'Editorial|Periodicals|Newspaper, All Types|Internet Website',
        ),
        '2GFP' => array(
            'Id' => '2GFP',
            'Label' => 'Editorial|Periodicals|Newspaper, All Types|Internet Downloadable File',
        ),
        '2GFQ' => array(
            'Id' => '2GFQ',
            'Label' => 'Editorial|Periodicals|Newspaper, All Types|All Electronic Distribution Formats',
        ),
        '2GFR' => array(
            'Id' => '2GFR',
            'Label' => 'Editorial|Periodicals|Newspaper, All Types|All Internet Distribution Formats',
        ),
        '2GFS' => array(
            'Id' => '2GFS',
            'Label' => 'Editorial|Periodicals|Newspaper, Weekly Supplement|Recordable Media',
        ),
        '2GFT' => array(
            'Id' => '2GFT',
            'Label' => 'Products|Merchandise|Card, Gift Card|Printed',
        ),
        '2GFU' => array(
            'Id' => '2GFU',
            'Label' => 'Editorial|Periodicals|Newspaper, Weekly Supplement|All Electronic Distribution Formats',
        ),
        '2GFV' => array(
            'Id' => '2GFV',
            'Label' => 'Editorial|Periodicals|Newspaper, Weekly Supplement|All Internet Distribution Formats',
        ),
        '2GFW' => array(
            'Id' => '2GFW',
            'Label' => 'Editorial|Periodicals|Newspaper, Weekly Supplement|Internet Website',
        ),
        '2GFY' => array(
            'Id' => '2GFY',
            'Label' => 'Editorial|Periodicals|Newspaper, Weekly Supplement|Internet Downloadable File',
        ),
        '2GGA' => array(
            'Id' => '2GGA',
            'Label' => 'Editorial|Periodicals|Newspaper, Tabloid|Internet Downloadable File',
        ),
        '2GGD' => array(
            'Id' => '2GGD',
            'Label' => 'Editorial|Periodicals|Scholarly Journal|Recordable Media',
        ),
        '2GGF' => array(
            'Id' => '2GGF',
            'Label' => 'Editorial|Periodicals|Scholarly Journal|Internet Website',
        ),
        '2GGG' => array(
            'Id' => '2GGG',
            'Label' => 'Editorial|Periodicals|Scholarly Journal|Internet Downloadable File',
        ),
        '2GGH' => array(
            'Id' => '2GGH',
            'Label' => 'Editorial|Periodicals|Scholarly Journal|All Electronic Distribution Formats',
        ),
        '2GGI' => array(
            'Id' => '2GGI',
            'Label' => 'Editorial|Periodicals|Scholarly Journal|All Internet Distribution Formats',
        ),
        '2GGM' => array(
            'Id' => '2GGM',
            'Label' => 'Editorial|Merchandise|CD ROM|Recordable Media',
        ),
        '2GHA' => array(
            'Id' => '2GHA',
            'Label' => 'Editorial|Website|Webcast, All Types|Internet Website',
        ),
        '2GHB' => array(
            'Id' => '2GHB',
            'Label' => 'Editorial|Website|Web Page, All Types|Internet Website',
        ),
        '2GHC' => array(
            'Id' => '2GHC',
            'Label' => 'Editorial|Website|Web Page, All Types|Recordable Media',
        ),
        '2GHD' => array(
            'Id' => '2GHD',
            'Label' => 'Editorial|Website|Web Page, All Types|All Electronic Distribution Formats',
        ),
        '2GHF' => array(
            'Id' => '2GHF',
            'Label' => 'Editorial|Website|Web Page, All Types|All Internet Distribution Formats',
        ),
        '2GHG' => array(
            'Id' => '2GHG',
            'Label' => 'Editorial|Website|Web Page, Body Content|Internet Website',
        ),
        '2GHH' => array(
            'Id' => '2GHH',
            'Label' => 'Editorial|Website|Web Page, Body Content|Recordable Media',
        ),
        '2GHI' => array(
            'Id' => '2GHI',
            'Label' => 'Advertising|Periodicals|Magazine, Consumer|Recordable Media',
        ),
        '2GHJ' => array(
            'Id' => '2GHJ',
            'Label' => 'Editorial|Website|Web Page, Body Content|All Electronic Distribution Formats',
        ),
        '2GHK' => array(
            'Id' => '2GHK',
            'Label' => 'Editorial|Website|Web Page, Body Content|All Internet Distribution Formats',
        ),
        '2GIB' => array(
            'Id' => '2GIB',
            'Label' => 'Advertising|Periodicals|Magazine, Consumer|All Internet Distribution Formats',
        ),
        '2GID' => array(
            'Id' => '2GID',
            'Label' => 'Internal Company Use|Internal Review|All Review Types|All Electronic Distribution Formats',
        ),
        '2GIE' => array(
            'Id' => '2GIE',
            'Label' => 'Advertising|Periodicals|Magazine, Corporate|Internet Downloadable File',
        ),
        '2GIF' => array(
            'Id' => '2GIF',
            'Label' => 'Products|Merchandise|Gift Certificate|Printed',
        ),
        '2GIG' => array(
            'Id' => '2GIG',
            'Label' => 'Products|Merchandise|Datebook|Printed',
        ),
        '2GIN' => array(
            'Id' => '2GIN',
            'Label' => 'Advertising|Periodicals|Magazine, Corporate|Internet Website',
        ),
        '2GIP' => array(
            'Id' => '2GIP',
            'Label' => 'Advertising|Periodicals|Magazine, Corporate|Recordable Media',
        ),
        '2GIT' => array(
            'Id' => '2GIT',
            'Label' => 'Advertising|Periodicals|Magazine, Corporate|All Electronic Distribution Formats',
        ),
        '2GNU' => array(
            'Id' => '2GNU',
            'Label' => 'Advertising|Periodicals|Magazine, Corporate|All Internet Distribution Formats',
        ),
        '2GOA' => array(
            'Id' => '2GOA',
            'Label' => 'Internal Company Use|Promotional Materials|Corporate Brochure|Printed',
        ),
        '2GOB' => array(
            'Id' => '2GOB',
            'Label' => 'Advertising|Periodicals|Magazine, Advertorial|All Internet Distribution Formats',
        ),
        '2GOO' => array(
            'Id' => '2GOO',
            'Label' => 'Advertising|Periodicals|Magazine, All Types|Recordable Media',
        ),
        '2GOR' => array(
            'Id' => '2GOR',
            'Label' => 'Advertising|Periodicals|Magazine, Custom Published|Internet Website',
        ),
        '2GOS' => array(
            'Id' => '2GOS',
            'Label' => 'Advertising|Periodicals|Magazine, Custom Published|Internet Downloadable File',
        ),
        '2GOX' => array(
            'Id' => '2GOX',
            'Label' => 'Advertising|Periodicals|Magazine, Custom Published|All Internet Distribution Formats',
        ),
        '2GOY' => array(
            'Id' => '2GOY',
            'Label' => 'Internal Company Use|Art|Art Display, All Art Types|Printed',
        ),
        '2GRE' => array(
            'Id' => '2GRE',
            'Label' => 'Products|Merchandise|Card, Greeting Card|Printed',
        ),
        '2GUL' => array(
            'Id' => '2GUL',
            'Label' => 'Advertising|Periodicals|Magazine, Education|Internet Downloadable File',
        ),
        '2GUM' => array(
            'Id' => '2GUM',
            'Label' => 'Advertising|Periodicals|Magazine, Education|Internet Website',
        ),
        '2GUN' => array(
            'Id' => '2GUN',
            'Label' => 'Advertising|Point of Purchase|Kiosk, Interactive Kiosk|Electronic Display',
        ),
        '2GUT' => array(
            'Id' => '2GUT',
            'Label' => 'Advertising|Marketing Materials|Promotional Calendar, One Page|Recordable Media',
        ),
        '2GUV' => array(
            'Id' => '2GUV',
            'Label' => 'Advertising|Periodicals|Magazine, Education|Recordable Media',
        ),
        '2GUY' => array(
            'Id' => '2GUY',
            'Label' => 'Advertising|Periodicals|Magazine, Education|All Electronic Distribution Formats',
        ),
        '2GYM' => array(
            'Id' => '2GYM',
            'Label' => 'Advertising|Point of Purchase|Kiosk, Interactive Kiosk|Printed',
        ),
        '2GYP' => array(
            'Id' => '2GYP',
            'Label' => 'Advertising|Periodicals|Magazine, Education|All Internet Distribution Formats',
        ),
        '2HAD' => array(
            'Id' => '2HAD',
            'Label' => 'Advertising|Marketing Materials|Promotional Calendar, Multi-Page|Recordable Media',
        ),
        '2HAE' => array(
            'Id' => '2HAE',
            'Label' => 'Advertising|Periodicals|Magazine, Trade|Internet Downloadable File',
        ),
        '2HAG' => array(
            'Id' => '2HAG',
            'Label' => 'Advertising|Periodicals|Magazine, Trade|Internet Website',
        ),
        '2HAH' => array(
            'Id' => '2HAH',
            'Label' => 'Advertising|Periodicals|Magazine, Trade|All Electronic Distribution Formats',
        ),
        '2HAJ' => array(
            'Id' => '2HAJ',
            'Label' => 'Advertising|Periodicals|Magazine, Trade|All Internet Distribution Formats',
        ),
        '2HAM' => array(
            'Id' => '2HAM',
            'Label' => 'Advertising|Marketing Materials|All Marketing Material Types|Internet Downloadable File',
        ),
        '2HAN' => array(
            'Id' => '2HAN',
            'Label' => 'Editorial|Book|Retail Book, Handbook|Printed',
        ),
        '2HAO' => array(
            'Id' => '2HAO',
            'Label' => 'Internal Company Use|Art|Art Display, All Art Types|Electronic Display',
        ),
        '2HAP' => array(
            'Id' => '2HAP',
            'Label' => 'Advertising|Periodicals|Magazine Reprints, All Types|Internet Downloadable File',
        ),
        '2HAS' => array(
            'Id' => '2HAS',
            'Label' => 'Advertising|Periodicals|Magazine Reprints, All Types|Internet Website',
        ),
        '2HAT' => array(
            'Id' => '2HAT',
            'Label' => 'Advertising|Periodicals|Magazine, Trade|Printed',
        ),
        '2HAW' => array(
            'Id' => '2HAW',
            'Label' => 'Advertising|Periodicals|Magazine Reprints, All Types|Recordable Media',
        ),
        '2HAY' => array(
            'Id' => '2HAY',
            'Label' => 'Advertising|Periodicals|Magazine Reprints, All Types|All Electronic Distribution Formats',
        ),
        '2HEH' => array(
            'Id' => '2HEH',
            'Label' => 'Advertising|Periodicals|Magazine Reprints, All Types|All Internet Distribution Formats',
        ),
        '2HEM' => array(
            'Id' => '2HEM',
            'Label' => 'Advertising|Display|Billboard, Bulletin|Printed',
        ),
        '2HEN' => array(
            'Id' => '2HEN',
            'Label' => 'Advertising|Periodicals|Newsletter, All Types|All Electronic Distribution Formats',
        ),
        '2HEP' => array(
            'Id' => '2HEP',
            'Label' => 'Advertising|Periodicals|Newsletter, All Types|Internet Downloadable File',
        ),
        '2HER' => array(
            'Id' => '2HER',
            'Label' => 'Products|Merchandise|Card, Hero Card|Printed',
        ),
        '2HES' => array(
            'Id' => '2HES',
            'Label' => 'Advertising|Periodicals|Newsletter, All Types|Internet Website',
        ),
        '2HET' => array(
            'Id' => '2HET',
            'Label' => 'Advertising|Periodicals|Newsletter, All Types|Recordable Media',
        ),
        '2HEW' => array(
            'Id' => '2HEW',
            'Label' => 'Advertising|Periodicals|Newsletter, All Types|All Internet Distribution Formats',
        ),
        '2HEX' => array(
            'Id' => '2HEX',
            'Label' => 'Products|Merchandise|Screen Saver|All Electronic Distribution Formats',
        ),
        '2HEY' => array(
            'Id' => '2HEY',
            'Label' => 'Advertising|Periodicals|Newspaper, All Types|All Electronic Distribution Formats',
        ),
        '2HIC' => array(
            'Id' => '2HIC',
            'Label' => 'Advertising|Periodicals|Newspaper, All Types|All Internet Distribution Formats',
        ),
        '2HID' => array(
            'Id' => '2HID',
            'Label' => 'Advertising|Periodicals|Newspaper, All Types|Internet Website',
        ),
        '2HIE' => array(
            'Id' => '2HIE',
            'Label' => 'Advertising|Periodicals|Newspaper, All Types|Internet Downloadable File',
        ),
        '2HIL' => array(
            'Id' => '2HIL',
            'Label' => 'Editorial|Book|Retail Book, Hi-lo Book|Printed',
        ),
        '2HIM' => array(
            'Id' => '2HIM',
            'Label' => 'Advertising|Periodicals|Newspaper, All Types|Recordable Media',
        ),
        '2HIP' => array(
            'Id' => '2HIP',
            'Label' => 'Advertising|Periodicals|Free Standing Insert, Advertorial Insert|Printed',
        ),
        '2HIS' => array(
            'Id' => '2HIS',
            'Label' => 'Advertising|Periodicals|Newspaper, Weekly Supplement|All Electronic Distribution Formats',
        ),
        '2HIT' => array(
            'Id' => '2HIT',
            'Label' => 'Motion Picture & TV|Motion Picture|Prop|All Internet Distribution Formats',
        ),
        '2HMM' => array(
            'Id' => '2HMM',
            'Label' => 'Advertising|Periodicals|Newspaper, Weekly Supplement|All Internet Distribution Formats',
        ),
        '2HOB' => array(
            'Id' => '2HOB',
            'Label' => 'Advertising|Periodicals|Newspaper, Weekly Supplement|Internet Website',
        ),
        '2HOD' => array(
            'Id' => '2HOD',
            'Label' => 'Advertising|Periodicals|Newspaper, Weekly Supplement|Internet Downloadable File',
        ),
        '2HOE' => array(
            'Id' => '2HOE',
            'Label' => 'Advertising|Periodicals|Newspaper, Weekly Supplement|Recordable Media',
        ),
        '2HOG' => array(
            'Id' => '2HOG',
            'Label' => 'Advertising|Book|All Book Types|Printed',
        ),
        '2HON' => array(
            'Id' => '2HON',
            'Label' => 'Advertising|Display|Poster, Movie Poster|Electronic Display',
        ),
        '2HOP' => array(
            'Id' => '2HOP',
            'Label' => 'Motion Picture & TV|Motion Picture|Prop|Projected Display',
        ),
        '2HOT' => array(
            'Id' => '2HOT',
            'Label' => 'Advertising|Display|Terminal Advertising, Airport Display|Printed',
        ),
        '2HOW' => array(
            'Id' => '2HOW',
            'Label' => 'Advertising|Periodicals|Newspaper, Tabloid|All Electronic Distribution Formats',
        ),
        '2HOY' => array(
            'Id' => '2HOY',
            'Label' => 'Advertising|Periodicals|Newspaper, Tabloid|Internet Website',
        ),
        '2HUB' => array(
            'Id' => '2HUB',
            'Label' => 'Products|Merchandise|Double Postcard|Printed',
        ),
        '2HUE' => array(
            'Id' => '2HUE',
            'Label' => 'Advertising|Periodicals|Newspaper, Tabloid|Recordable Media',
        ),
        '2HUG' => array(
            'Id' => '2HUG',
            'Label' => 'Motion Picture & TV|Motion Picture|Set Decor|Recordable Media',
        ),
        '2HUH' => array(
            'Id' => '2HUH',
            'Label' => 'Advertising|Periodicals|Newspaper, Tabloid|All Internet Distribution Formats',
        ),
        '2HUM' => array(
            'Id' => '2HUM',
            'Label' => 'Motion Picture & TV|Motion Picture|Set Decor|All Electronic Distribution Formats',
        ),
        '2HUP' => array(
            'Id' => '2HUP',
            'Label' => 'Advertising|Periodicals|Quarterly Report|Internet Downloadable File',
        ),
        '2HUT' => array(
            'Id' => '2HUT',
            'Label' => 'Advertising|Periodicals|Quarterly Report|Internet Website',
        ),
        '2HYP' => array(
            'Id' => '2HYP',
            'Label' => 'Advertising|Periodicals|Quarterly Report|Recordable Media',
        ),
        '2JAB' => array(
            'Id' => '2JAB',
            'Label' => 'Motion Picture & TV|Motion Picture|Set Decor|All Internet Distribution Formats',
        ),
        '2JAG' => array(
            'Id' => '2JAG',
            'Label' => 'Advertising|Periodicals|Quarterly Report|All Electronic Distribution Formats',
        ),
        '2JAM' => array(
            'Id' => '2JAM',
            'Label' => 'Advertising|Marketing Materials|All Marketing Material Types|Printed',
        ),
        '2JAR' => array(
            'Id' => '2JAR',
            'Label' => 'Advertising|Periodicals|Magazine, Corporate|Printed',
        ),
        '2JAW' => array(
            'Id' => '2JAW',
            'Label' => 'Advertising|Live Presentation|Stage Performance|Projected Display',
        ),
        '2JAY' => array(
            'Id' => '2JAY',
            'Label' => 'Advertising|Periodicals|Newsletter, All Types|Printed',
        ),
        '2JEE' => array(
            'Id' => '2JEE',
            'Label' => 'Advertising|Periodicals|Quarterly Report|All Internet Distribution Formats',
        ),
        '2JET' => array(
            'Id' => '2JET',
            'Label' => 'Editorial|Periodicals|Newspaper, All Types|Printed',
        ),
        '2JIB' => array(
            'Id' => '2JIB',
            'Label' => 'Advertising|Marketing Materials|Public Relations, All Types|Printed',
        ),
        '2JIG' => array(
            'Id' => '2JIG',
            'Label' => 'Products|Merchandise|Jigsaw Puzzle|Printed',
        ),
        '2JIL' => array(
            'Id' => '2JIL',
            'Label' => 'Products|Merchandise|Jigsaw Puzzle|Recordable Media',
        ),
        '2JIN' => array(
            'Id' => '2JIN',
            'Label' => 'Products|Merchandise|Jigsaw Puzzle|Internet Downloadable File',
        ),
        '2JOB' => array(
            'Id' => '2JOB',
            'Label' => 'Advertising|Marketing Materials|Public Relations, Press Kit|Recordable Media',
        ),
        '2JOE' => array(
            'Id' => '2JOE',
            'Label' => 'Advertising|Marketing Materials|Public Relations, Press Kit|All Electronic Distribution Formats',
        ),
        '2JOG' => array(
            'Id' => '2JOG',
            'Label' => 'Motion Picture & TV|Motion Picture|Documentary Film|Projected Display',
        ),
        '2JOT' => array(
            'Id' => '2JOT',
            'Label' => 'Motion Picture & TV|Motion Picture|In Theater Commercial|Projected Display',
        ),
        '2JOU' => array(
            'Id' => '2JOU',
            'Label' => 'Advertising|Display|Terminal Advertising, Airport Display|Electronic Display',
        ),
        '2JOW' => array(
            'Id' => '2JOW',
            'Label' => 'Advertising|Marketing Materials|Public Relations, Press Kit|All Internet Distribution Formats',
        ),
        '2JOY' => array(
            'Id' => '2JOY',
            'Label' => 'Advertising|Display|Terminal Advertising, All Types|Printed',
        ),
        '2JRN' => array(
            'Id' => '2JRN',
            'Label' => 'Products|Merchandise|Journal|Printed',
        ),
        '2JUG' => array(
            'Id' => '2JUG',
            'Label' => 'Advertising|Point of Purchase|Kiosk, Telephone Kiosk|Printed',
        ),
        '2JUN' => array(
            'Id' => '2JUN',
            'Label' => 'Advertising|Marketing Materials|Public Relations, Press Kit|Internet Downloadable File',
        ),
        '2JUS' => array(
            'Id' => '2JUS',
            'Label' => 'Advertising|Marketing Materials|Public Relations, Press Kit|Internet Email',
        ),
        '2JUT' => array(
            'Id' => '2JUT',
            'Label' => 'Advertising|Marketing Materials|Public Relations, Press Kit|Internet Website',
        ),
        '2JWL' => array(
            'Id' => '2JWL',
            'Label' => 'Advertising|Website|Web Page, Web Interstitial Ad|Internet Website',
        ),
        '2KAB' => array(
            'Id' => '2KAB',
            'Label' => 'Advertising|Marketing Materials|Public Relations, Press Kit|Television Broadcast',
        ),
        '2KAF' => array(
            'Id' => '2KAF',
            'Label' => 'Advertising|Marketing Materials|Public Relations, Press Kit|Printed',
        ),
        '2KAS' => array(
            'Id' => '2KAS',
            'Label' => 'Advertising|Marketing Materials|Public Relations, Press Release|Recordable Media',
        ),
        '2KAT' => array(
            'Id' => '2KAT',
            'Label' => 'Advertising|Marketing Materials|Public Relations, Press Release|Internet Downloadable File',
        ),
        '2KAY' => array(
            'Id' => '2KAY',
            'Label' => 'Advertising|Marketing Materials|Public Relations, Press Release|Internet Email',
        ),
        '2KEA' => array(
            'Id' => '2KEA',
            'Label' => 'Advertising|Marketing Materials|Public Relations, Press Release|Internet Website',
        ),
        '2KEF' => array(
            'Id' => '2KEF',
            'Label' => 'Advertising|Marketing Materials|Public Relations, Press Release|Television Broadcast',
        ),
        '2KEG' => array(
            'Id' => '2KEG',
            'Label' => 'Advertising|Periodicals|Newspaper, Weekly Supplement|Printed',
        ),
        '2KEN' => array(
            'Id' => '2KEN',
            'Label' => 'Advertising|Periodicals|Newspaper, Tabloid|Printed',
        ),
        '2KEP' => array(
            'Id' => '2KEP',
            'Label' => 'Products|Merchandise|Souvenir|Printed',
        ),
        '2KEX' => array(
            'Id' => '2KEX',
            'Label' => 'Advertising|Marketing Materials|Public Relations, Press Release|Printed',
        ),
        '2KID' => array(
            'Id' => '2KID',
            'Label' => 'Advertising|Marketing Materials|Public Relations, Press Release|All Electronic Distribution Formats',
        ),
        '2KIN' => array(
            'Id' => '2KIN',
            'Label' => 'Editorial|Periodicals|Magazine, Consumer|Internet Email',
        ),
        '2KIO' => array(
            'Id' => '2KIO',
            'Label' => 'Advertising|Point of Purchase|Kiosk, All Types|Electronic Display',
        ),
        '2KIP' => array(
            'Id' => '2KIP',
            'Label' => 'Advertising|Periodicals|Program Advertising|Printed',
        ),
        '2KIT' => array(
            'Id' => '2KIT',
            'Label' => 'Personal Use|Art|Art Display, All Art Types|Printed',
        ),
        '2KOA' => array(
            'Id' => '2KOA',
            'Label' => 'Motion Picture & TV|Television Programming|Artist\'s Reference, All Types|Projected Display',
        ),
        '2KOB' => array(
            'Id' => '2KOB',
            'Label' => 'Motion Picture & TV|Television Programming|Artist\'s Reference, All Types|Internet Downloadable File',
        ),
        '2KOI' => array(
            'Id' => '2KOI',
            'Label' => 'Motion Picture & TV|Television Programming|Artist\'s Reference, All Types|All Internet Distribution Formats',
        ),
        '2KOP' => array(
            'Id' => '2KOP',
            'Label' => 'Motion Picture & TV|Television Programming|Artist\'s Reference, All Types|Recordable Media',
        ),
        '2KOR' => array(
            'Id' => '2KOR',
            'Label' => 'Motion Picture & TV|Television Programming|Artist\'s Reference, All Types|All Electronic Distribution Formats',
        ),
        '2KOS' => array(
            'Id' => '2KOS',
            'Label' => 'Motion Picture & TV|Television Programming|Artist\'s Reference, All Types|Television Broadcast',
        ),
        '2KUE' => array(
            'Id' => '2KUE',
            'Label' => 'Advertising|Website|Web Page, All Types|All Internet Distribution Formats',
        ),
        '2MAB' => array(
            'Id' => '2MAB',
            'Label' => 'Products|Merchandise|Map|All Electronic Distribution Formats',
        ),
        '2MAC' => array(
            'Id' => '2MAC',
            'Label' => 'Advertising|Website|Web Page, All Types|Recordable Media',
        ),
        '2MAD' => array(
            'Id' => '2MAD',
            'Label' => 'Products|Merchandise|Map|Internet Downloadable File',
        ),
        '2MAE' => array(
            'Id' => '2MAE',
            'Label' => 'Advertising|Website|Web Page, All Types|All Electronic Distribution Formats',
        ),
        '2MAG' => array(
            'Id' => '2MAG',
            'Label' => 'Editorial|Periodicals|Magazine, All Types|Printed',
        ),
        '2MAN' => array(
            'Id' => '2MAN',
            'Label' => 'Editorial|Book|Retail Book, Manual|Printed',
        ),
        '2MAP' => array(
            'Id' => '2MAP',
            'Label' => 'Products|Merchandise|Map|Printed',
        ),
        '2MAR' => array(
            'Id' => '2MAR',
            'Label' => 'Advertising|Website|Web Page, Design Element|Internet Website',
        ),
        '2MAS' => array(
            'Id' => '2MAS',
            'Label' => 'Advertising|Website|Web Page, Design Element|Recordable Media',
        ),
        '2MAT' => array(
            'Id' => '2MAT',
            'Label' => 'Products|Merchandise|Placemat|Printed',
        ),
        '2MAW' => array(
            'Id' => '2MAW',
            'Label' => 'Advertising|Website|Web Page, Design Element|All Electronic Distribution Formats',
        ),
        '2MAX' => array(
            'Id' => '2MAX',
            'Label' => 'Advertising|Website|Web Page, Design Element|All Internet Distribution Formats',
        ),
        '2MAY' => array(
            'Id' => '2MAY',
            'Label' => 'Editorial|Periodicals|Magazine, Education|Printed',
        ),
        '2MCH' => array(
            'Id' => '2MCH',
            'Label' => 'Products|Merchandise|Other Merchandise|Printed',
        ),
        '2MED' => array(
            'Id' => '2MED',
            'Label' => 'Advertising|Website|Web Page, Web Banner Ad|Internet Website',
        ),
        '2MEG' => array(
            'Id' => '2MEG',
            'Label' => 'Advertising|Website|Web Page, Web Banner Ad|Recordable Media',
        ),
        '2MEL' => array(
            'Id' => '2MEL',
            'Label' => 'Editorial|Periodicals|Magazine, Partworks|Printed',
        ),
        '2MEM' => array(
            'Id' => '2MEM',
            'Label' => 'Advertising|Website|Web Page, Web Banner Ad|All Electronic Distribution Formats',
        ),
        '2MEN' => array(
            'Id' => '2MEN',
            'Label' => 'Advertising|Point of Purchase|Menu|Printed',
        ),
        '2MER' => array(
            'Id' => '2MER',
            'Label' => 'Products|Merchandise|All Merchandise Types|Printed',
        ),
        '2MET' => array(
            'Id' => '2MET',
            'Label' => 'Products|Merchandise|Map|Internet Email',
        ),
        '2MEW' => array(
            'Id' => '2MEW',
            'Label' => 'Advertising|Website|Web Page, Web Banner Ad|All Internet Distribution Formats',
        ),
        '2MIB' => array(
            'Id' => '2MIB',
            'Label' => 'Advertising|Website|Web Page, Web Interstitial Ad|Recordable Media',
        ),
        '2MID' => array(
            'Id' => '2MID',
            'Label' => 'Advertising|Website|Web Page, Web Interstitial Ad|All Electronic Distribution Formats',
        ),
        '2MIG' => array(
            'Id' => '2MIG',
            'Label' => 'Advertising|Website|Web Page, Web Interstitial Ad|All Internet Distribution Formats',
        ),
        '2MIL' => array(
            'Id' => '2MIL',
            'Label' => 'Advertising|Website|Webcast, All Types|Internet Website',
        ),
        '2MIM' => array(
            'Id' => '2MIM',
            'Label' => 'Products|Merchandise|Map|All Internet Distribution Formats',
        ),
        '2MIR' => array(
            'Id' => '2MIR',
            'Label' => 'Products|Merchandise|Map|Recordable Media',
        ),
        '2MIX' => array(
            'Id' => '2MIX',
            'Label' => 'Motion Picture & TV|Motion Picture|Artist\'s Reference, All Types|Recordable Media',
        ),
        '2MMA' => array(
            'Id' => '2MMA',
            'Label' => 'Advertising|Marketing Materials|Bill Insert|Printed',
        ),
        '2MMB' => array(
            'Id' => '2MMB',
            'Label' => 'Advertising|Marketing Materials|Blow In Card|Printed',
        ),
        '2MMC' => array(
            'Id' => '2MMC',
            'Label' => 'Advertising|Marketing Materials|Bound-in Insert|Printed',
        ),
        '2MMD' => array(
            'Id' => '2MMD',
            'Label' => 'Advertising|Marketing Materials|Broadside|Printed',
        ),
        '2MME' => array(
            'Id' => '2MME',
            'Label' => 'Advertising|Marketing Materials|Buckslip|Printed',
        ),
        '2MMF' => array(
            'Id' => '2MMF',
            'Label' => 'Advertising|Marketing Materials|Business Card|Printed',
        ),
        '2MMG' => array(
            'Id' => '2MMG',
            'Label' => 'Advertising|Marketing Materials|Business Envelope|Printed',
        ),
        '2MMI' => array(
            'Id' => '2MMI',
            'Label' => 'Advertising|Marketing Materials|Business Invitation|Printed',
        ),
        '2MMJ' => array(
            'Id' => '2MMJ',
            'Label' => 'Advertising|Marketing Materials|Business Reply Card|Printed',
        ),
        '2MMK' => array(
            'Id' => '2MMK',
            'Label' => 'Advertising|Marketing Materials|Business Reply Envelope|Printed',
        ),
        '2MML' => array(
            'Id' => '2MML',
            'Label' => 'Advertising|Marketing Materials|Business Stationery|Printed',
        ),
        '2MMM' => array(
            'Id' => '2MMM',
            'Label' => 'Advertising|Marketing Materials|Compliment Slip|Printed',
        ),
        '2MMN' => array(
            'Id' => '2MMN',
            'Label' => 'Advertising|Marketing Materials|Coupon|Printed',
        ),
        '2MMP' => array(
            'Id' => '2MMP',
            'Label' => 'Advertising|Marketing Materials|Coupon Packs|Printed',
        ),
        '2MMQ' => array(
            'Id' => '2MMQ',
            'Label' => 'Advertising|Marketing Materials|Flyaway Card|Printed',
        ),
        '2MMR' => array(
            'Id' => '2MMR',
            'Label' => 'Advertising|Marketing Materials|Leaflet|Printed',
        ),
        '2MMS' => array(
            'Id' => '2MMS',
            'Label' => 'Advertising|Marketing Materials|Magalog|Printed',
        ),
        '2MMT' => array(
            'Id' => '2MMT',
            'Label' => 'Advertising|Marketing Materials|One Sheet|Printed',
        ),
        '2MMU' => array(
            'Id' => '2MMU',
            'Label' => 'Advertising|Marketing Materials|Onsert|Printed',
        ),
        '2MMV' => array(
            'Id' => '2MMV',
            'Label' => 'Advertising|Marketing Materials|Polybag|Printed',
        ),
        '2MMW' => array(
            'Id' => '2MMW',
            'Label' => 'Advertising|Marketing Materials|Promotional Calendar, One Page|Printed',
        ),
        '2MMX' => array(
            'Id' => '2MMX',
            'Label' => 'Advertising|Marketing Materials|Promotional Envelope|Printed',
        ),
        '2MMY' => array(
            'Id' => '2MMY',
            'Label' => 'Advertising|Marketing Materials|Sales Kit|Printed',
        ),
        '2MMZ' => array(
            'Id' => '2MMZ',
            'Label' => 'Advertising|Marketing Materials|Self Mailer|Printed',
        ),
        '2MOB' => array(
            'Id' => '2MOB',
            'Label' => 'Advertising|Display|Store Display, In-Store Poster|Printed',
        ),
        '2MOG' => array(
            'Id' => '2MOG',
            'Label' => 'Editorial|Book|All Book Types|Printed',
        ),
        '2MOM' => array(
            'Id' => '2MOM',
            'Label' => 'Editorial|Periodicals|Magazine, Trade|Printed',
        ),
        '2MOO' => array(
            'Id' => '2MOO',
            'Label' => 'Advertising|Periodicals|Magazine, All Types|Printed',
        ),
        '2MOP' => array(
            'Id' => '2MOP',
            'Label' => 'Editorial|Periodicals|Magazine, Custom Published|Printed',
        ),
        '2MOR' => array(
            'Id' => '2MOR',
            'Label' => 'Editorial|Book|Artist\'s Reference, All Types|Printed',
        ),
        '2MOU' => array(
            'Id' => '2MOU',
            'Label' => 'Products|Merchandise|Mouse Pad|Printed',
        ),
        '2MOV' => array(
            'Id' => '2MOV',
            'Label' => 'Motion Picture & TV|Motion Picture|All Motion Picture Types|Recordable Media',
        ),
        '2MOW' => array(
            'Id' => '2MOW',
            'Label' => 'Editorial|Periodicals|Magazine, Trade|Internet Website',
        ),
        '2MPA' => array(
            'Id' => '2MPA',
            'Label' => 'Motion Picture & TV|Motion Picture|All Motion Picture Types|Television Broadcast',
        ),
        '2MPB' => array(
            'Id' => '2MPB',
            'Label' => 'Motion Picture & TV|Music Video|All Music Video Types|Television Broadcast',
        ),
        '2MPC' => array(
            'Id' => '2MPC',
            'Label' => 'Motion Picture & TV|Motion Picture|Artist\'s Reference, All Types|Television Broadcast',
        ),
        '2MPD' => array(
            'Id' => '2MPD',
            'Label' => 'Motion Picture & TV|Motion Picture|Documentary Film|Television Broadcast',
        ),
        '2MPE' => array(
            'Id' => '2MPE',
            'Label' => 'Motion Picture & TV|Motion Picture|Feature Film|Television Broadcast',
        ),
        '2MPF' => array(
            'Id' => '2MPF',
            'Label' => 'Motion Picture & TV|Motion Picture|Movie Trailer|Television Broadcast',
        ),
        '2MPG' => array(
            'Id' => '2MPG',
            'Label' => 'Motion Picture & TV|Motion Picture|Prop|Television Broadcast',
        ),
        '2MPH' => array(
            'Id' => '2MPH',
            'Label' => 'Motion Picture & TV|Motion Picture|Set Decor|Television Broadcast',
        ),
        '2MPI' => array(
            'Id' => '2MPI',
            'Label' => 'Motion Picture & TV|Motion Picture|Short Film|Television Broadcast',
        ),
        '2MPJ' => array(
            'Id' => '2MPJ',
            'Label' => 'Motion Picture & TV|Motion Picture|All Motion Picture Types|Internet Downloadable File',
        ),
        '2MPK' => array(
            'Id' => '2MPK',
            'Label' => 'Motion Picture & TV|Music Video|All Music Video Types|Internet Downloadable File',
        ),
        '2MPL' => array(
            'Id' => '2MPL',
            'Label' => 'Motion Picture & TV|Motion Picture|Artist\'s Reference, All Types|Internet Downloadable File',
        ),
        '2MPM' => array(
            'Id' => '2MPM',
            'Label' => 'Motion Picture & TV|Motion Picture|Documentary Film|Internet Downloadable File',
        ),
        '2MPN' => array(
            'Id' => '2MPN',
            'Label' => 'Motion Picture & TV|Motion Picture|Feature Film|Internet Downloadable File',
        ),
        '2MPP' => array(
            'Id' => '2MPP',
            'Label' => 'Motion Picture & TV|Motion Picture|Movie Trailer|Internet Downloadable File',
        ),
        '2MPQ' => array(
            'Id' => '2MPQ',
            'Label' => 'Motion Picture & TV|Motion Picture|Prop|Internet Downloadable File',
        ),
        '2MPR' => array(
            'Id' => '2MPR',
            'Label' => 'Motion Picture & TV|Motion Picture|Set Decor|Internet Downloadable File',
        ),
        '2MPS' => array(
            'Id' => '2MPS',
            'Label' => 'Motion Picture & TV|Motion Picture|Short Film|Internet Downloadable File',
        ),
        '2MRP' => array(
            'Id' => '2MRP',
            'Label' => 'Products|Merchandise|Plates|Printed',
        ),
        '2MUG' => array(
            'Id' => '2MUG',
            'Label' => 'Products|Merchandise|Mugs|Printed',
        ),
        '2MUS' => array(
            'Id' => '2MUS',
            'Label' => 'Editorial|Display|Museum Display|Printed',
        ),
        '2MUT' => array(
            'Id' => '2MUT',
            'Label' => 'Editorial|Periodicals|Magazine, Trade|Internet Downloadable File',
        ),
        '2NAB' => array(
            'Id' => '2NAB',
            'Label' => 'Editorial|Periodicals|Magazine, Trade|Internet Email',
        ),
        '2NAG' => array(
            'Id' => '2NAG',
            'Label' => 'Editorial|Periodicals|Magazine, Trade|All Internet Distribution Formats',
        ),
        '2NAH' => array(
            'Id' => '2NAH',
            'Label' => 'Editorial|Book|Reference Book, Encyclopedia|All Electronic Distribution Formats',
        ),
        '2NAP' => array(
            'Id' => '2NAP',
            'Label' => 'Motion Picture & TV|Motion Picture|Artist\'s Reference, All Types|All Electronic Distribution Formats',
        ),
        '2NAY' => array(
            'Id' => '2NAY',
            'Label' => 'Editorial|Book|Reference Book, Encyclopedia|Printed',
        ),
        '2NEL' => array(
            'Id' => '2NEL',
            'Label' => 'Editorial|Periodicals|Newsletter|Internet Email',
        ),
        '2NET' => array(
            'Id' => '2NET',
            'Label' => 'Internal Company Use|Website|Web Page, All Types|Intranet and Extranet Website',
        ),
        '2NEW' => array(
            'Id' => '2NEW',
            'Label' => 'Advertising|Periodicals|Newspaper, All Types|Printed',
        ),
        '2NIP' => array(
            'Id' => '2NIP',
            'Label' => 'Editorial|Book|Reference Book, Telephone Book|Printed',
        ),
        '2NOB' => array(
            'Id' => '2NOB',
            'Label' => 'Editorial|Periodicals|Magazine, Trade|All Electronic Distribution Formats',
        ),
        '2NOD' => array(
            'Id' => '2NOD',
            'Label' => 'Motion Picture & TV|Motion Picture|Artist\'s Reference, All Types|All Internet Distribution Formats',
        ),
        '2NOV' => array(
            'Id' => '2NOV',
            'Label' => 'Products|Merchandise|Novelty Products|Printed',
        ),
        '2NOW' => array(
            'Id' => '2NOW',
            'Label' => 'Editorial|Periodicals|Magazine, Education|Internet Email',
        ),
        '2NUN' => array(
            'Id' => '2NUN',
            'Label' => 'Editorial|Periodicals|Magazine, Custom Published|Internet Email',
        ),
        '2NUT' => array(
            'Id' => '2NUT',
            'Label' => 'Advertising|Marketing Materials|Promotional Postcard|Printed',
        ),
        '2TAB' => array(
            'Id' => '2TAB',
            'Label' => 'Editorial|Periodicals|Magazine, Partworks|Internet Email',
        ),
        '2TAD' => array(
            'Id' => '2TAD',
            'Label' => 'Editorial|Periodicals|Scholarly Journal|Internet Email',
        ),
        '2TAE' => array(
            'Id' => '2TAE',
            'Label' => 'Editorial|Book|Retail Book, Coffee Table Book|Printed',
        ),
        '2TAG' => array(
            'Id' => '2TAG',
            'Label' => 'Advertising|Point of Purchase|Hang Tag|Printed',
        ),
        '2TAJ' => array(
            'Id' => '2TAJ',
            'Label' => 'Editorial|Periodicals|Newspaper, All Types|Internet Email',
        ),
        '2TAL' => array(
            'Id' => '2TAL',
            'Label' => 'Advertising|Point of Purchase|Shelf Talker|Printed',
        ),
        '2TAN' => array(
            'Id' => '2TAN',
            'Label' => 'Advertising|Display|Terminal Advertising, Shelter Advertising|Printed',
        ),
        '2TAO' => array(
            'Id' => '2TAO',
            'Label' => 'Editorial|Periodicals|Newspaper, Weekly Supplement|Internet Email',
        ),
        '2TAP' => array(
            'Id' => '2TAP',
            'Label' => 'Motion Picture & TV|Motion Picture|Artist\'s Reference, All Types|Projected Display',
        ),
        '2TAT' => array(
            'Id' => '2TAT',
            'Label' => 'Personal Use|Art|Artist\'s Reference, Tattoo|Printed',
        ),
        '2TAV' => array(
            'Id' => '2TAV',
            'Label' => 'Editorial|Book|Retail Book, Concept Book|Printed',
        ),
        '2TAX' => array(
            'Id' => '2TAX',
            'Label' => 'Advertising|Display|Transit Advertising, Taxi Advertising|Printed',
        ),
        '2TEA' => array(
            'Id' => '2TEA',
            'Label' => 'Motion Picture & TV|Television Programming|All Television Advertising Types|Internet Downloadable File',
        ),
        '2TEB' => array(
            'Id' => '2TEB',
            'Label' => 'Motion Picture & TV|Television Programming|Commercial|Internet Downloadable File',
        ),
        '2TEC' => array(
            'Id' => '2TEC',
            'Label' => 'Motion Picture & TV|Television Programming|Commercial|All Internet Distribution Formats',
        ),
        '2TED' => array(
            'Id' => '2TED',
            'Label' => 'Motion Picture & TV|Television Programming|All Television Advertising Types|Recordable Media',
        ),
        '2TEE' => array(
            'Id' => '2TEE',
            'Label' => 'Motion Picture & TV|Television Programming|All Editorial Television Types|Television Broadcast',
        ),
        '2TEF' => array(
            'Id' => '2TEF',
            'Label' => 'Motion Picture & TV|Television Programming|News Program, Flash|Television Broadcast',
        ),
        '2TEG' => array(
            'Id' => '2TEG',
            'Label' => 'Motion Picture & TV|Television Programming|Commercial|Recordable Media',
        ),
        '2TEH' => array(
            'Id' => '2TEH',
            'Label' => 'Motion Picture & TV|Television Programming|Infomercial|Internet Downloadable File',
        ),
        '2TEJ' => array(
            'Id' => '2TEJ',
            'Label' => 'Motion Picture & TV|Television Programming|Infomercial|All Internet Distribution Formats',
        ),
        '2TEK' => array(
            'Id' => '2TEK',
            'Label' => 'Motion Picture & TV|Television Programming|Infomercial|Recordable Media',
        ),
        '2TEL' => array(
            'Id' => '2TEL',
            'Label' => 'Motion Picture & TV|Television Programming|On-Air Promotion|Internet Downloadable File',
        ),
        '2TEM' => array(
            'Id' => '2TEM',
            'Label' => 'Motion Picture & TV|Television Programming|On-Air Promotion|All Internet Distribution Formats',
        ),
        '2TEP' => array(
            'Id' => '2TEP',
            'Label' => 'Motion Picture & TV|Television Programming|On-Air Promotion|Recordable Media',
        ),
        '2TEQ' => array(
            'Id' => '2TEQ',
            'Label' => 'Motion Picture & TV|Television Programming|All Television Advertising Types|All Internet Distribution Formats',
        ),
        '2TER' => array(
            'Id' => '2TER',
            'Label' => 'Motion Picture & TV|Television Programming|Documentary Program|Internet Downloadable File',
        ),
        '2TES' => array(
            'Id' => '2TES',
            'Label' => 'Motion Picture & TV|Television Programming|Documentary Program|All Internet Distribution Formats',
        ),
        '2TET' => array(
            'Id' => '2TET',
            'Label' => 'Motion Picture & TV|Television Programming|Documentary Program|Recordable Media',
        ),
        '2TEU' => array(
            'Id' => '2TEU',
            'Label' => 'Motion Picture & TV|Television Programming|Educational Program|Internet Downloadable File',
        ),
        '2TEV' => array(
            'Id' => '2TEV',
            'Label' => 'Motion Picture & TV|Television Programming|Educational Program|All Internet Distribution Formats',
        ),
        '2TEW' => array(
            'Id' => '2TEW',
            'Label' => 'Motion Picture & TV|Television Programming|Educational Program|Recordable Media',
        ),
        '2TEX' => array(
            'Id' => '2TEX',
            'Label' => 'Editorial|Periodicals|Newspaper, Tabloid|Internet Website',
        ),
        '2TEY' => array(
            'Id' => '2TEY',
            'Label' => 'Motion Picture & TV|Television Programming|Entertainment Program|Internet Downloadable File',
        ),
        '2TEZ' => array(
            'Id' => '2TEZ',
            'Label' => 'Motion Picture & TV|Television Programming|Entertainment Program|All Internet Distribution Formats',
        ),
        '2TIC' => array(
            'Id' => '2TIC',
            'Label' => 'Products|Merchandise|Sticker|Printed',
        ),
        '2TIE' => array(
            'Id' => '2TIE',
            'Label' => 'Editorial|Periodicals|Newspaper, Tabloid|Recordable Media',
        ),
        '2TIN' => array(
            'Id' => '2TIN',
            'Label' => 'Advertising|Point of Purchase|Slip Case|Printed',
        ),
        '2TIP' => array(
            'Id' => '2TIP',
            'Label' => 'Editorial|Periodicals|Newspaper, Tabloid|Internet Email',
        ),
        '2TKT' => array(
            'Id' => '2TKT',
            'Label' => 'Products|Merchandise|Ticket|Printed',
        ),
        '2TLA' => array(
            'Id' => '2TLA',
            'Label' => 'Motion Picture & TV|Television Programming|Entertainment Program|Recordable Media',
        ),
        '2TLB' => array(
            'Id' => '2TLB',
            'Label' => 'Motion Picture & TV|Television Programming|Made For TV Movie|All Internet Distribution Formats',
        ),
        '2TLC' => array(
            'Id' => '2TLC',
            'Label' => 'Motion Picture & TV|Television Programming|Made For TV Movie|Internet Downloadable File',
        ),
        '2TLD' => array(
            'Id' => '2TLD',
            'Label' => 'Motion Picture & TV|Television Programming|Made For TV Movie|Recordable Media',
        ),
        '2TLE' => array(
            'Id' => '2TLE',
            'Label' => 'Motion Picture & TV|Television Programming|News Program|Internet Downloadable File',
        ),
        '2TLF' => array(
            'Id' => '2TLF',
            'Label' => 'Motion Picture & TV|Television Programming|News Program|All Internet Distribution Formats',
        ),
        '2TLG' => array(
            'Id' => '2TLG',
            'Label' => 'Motion Picture & TV|Television Programming|News Program|Recordable Media',
        ),
        '2TLH' => array(
            'Id' => '2TLH',
            'Label' => 'Motion Picture & TV|Television Programming|Non Broadcast Pilot|Recordable Media',
        ),
        '2TLJ' => array(
            'Id' => '2TLJ',
            'Label' => 'Motion Picture & TV|Television Programming|Non Broadcast Pilot|Projected Display',
        ),
        '2TLK' => array(
            'Id' => '2TLK',
            'Label' => 'Motion Picture & TV|Television Programming|Non-Profit Program|Internet Downloadable File',
        ),
        '2TLL' => array(
            'Id' => '2TLL',
            'Label' => 'Motion Picture & TV|Television Programming|Non-Profit Program|All Internet Distribution Formats',
        ),
        '2TLM' => array(
            'Id' => '2TLM',
            'Label' => 'Motion Picture & TV|Television Programming|Non-Profit Program|Recordable Media',
        ),
        '2TLN' => array(
            'Id' => '2TLN',
            'Label' => 'Motion Picture & TV|Television Programming|Prop|Internet Downloadable File',
        ),
        '2TLP' => array(
            'Id' => '2TLP',
            'Label' => 'Motion Picture & TV|Television Programming|Prop|All Internet Distribution Formats',
        ),
        '2TLQ' => array(
            'Id' => '2TLQ',
            'Label' => 'Motion Picture & TV|Television Programming|Prop|Recordable Media',
        ),
        '2TLR' => array(
            'Id' => '2TLR',
            'Label' => 'Motion Picture & TV|Television Programming|Prop|Television Broadcast',
        ),
        '2TLS' => array(
            'Id' => '2TLS',
            'Label' => 'Motion Picture & TV|Television Programming|Set Decor|Internet Downloadable File',
        ),
        '2TLT' => array(
            'Id' => '2TLT',
            'Label' => 'Motion Picture & TV|Television Programming|Set Decor|All Internet Distribution Formats',
        ),
        '2TLU' => array(
            'Id' => '2TLU',
            'Label' => 'Motion Picture & TV|Television Programming|Set Decor|Recordable Media',
        ),
        '2TLV' => array(
            'Id' => '2TLV',
            'Label' => 'Motion Picture & TV|Television Programming|Set Decor|Television Broadcast',
        ),
        '2TLW' => array(
            'Id' => '2TLW',
            'Label' => 'Motion Picture & TV|Television Programming|All Editorial Television Types|Internet Downloadable File',
        ),
        '2TLY' => array(
            'Id' => '2TLY',
            'Label' => 'Motion Picture & TV|Television Programming|All Editorial Television Types|All Internet Distribution Formats',
        ),
        '2TLZ' => array(
            'Id' => '2TLZ',
            'Label' => 'Motion Picture & TV|Television Programming|All Editorial Television Types|Recordable Media',
        ),
        '2TOD' => array(
            'Id' => '2TOD',
            'Label' => 'Products|Merchandise|Screen Saver|All Internet Distribution Formats',
        ),
        '2TOE' => array(
            'Id' => '2TOE',
            'Label' => 'Advertising|Live Presentation|Trade Show Presentation|Projected Display',
        ),
        '2TOM' => array(
            'Id' => '2TOM',
            'Label' => 'Editorial|Periodicals|Newsletter|Printed',
        ),
        '2TON' => array(
            'Id' => '2TON',
            'Label' => 'Editorial|Periodicals|Newspaper, Tabloid|All Internet Distribution Formats',
        ),
        '2TOP' => array(
            'Id' => '2TOP',
            'Label' => 'Products|Merchandise|Poster, Retail Poster|Printed',
        ),
        '2TOT' => array(
            'Id' => '2TOT',
            'Label' => 'Editorial|Periodicals|Newspaper, Tabloid|All Electronic Distribution Formats',
        ),
        '2TOY' => array(
            'Id' => '2TOY',
            'Label' => 'Products|Merchandise|Toy|Printed',
        ),
        '2TRA' => array(
            'Id' => '2TRA',
            'Label' => 'Advertising|Display|Transit Advertising, All Types|Printed',
        ),
        '2TRB' => array(
            'Id' => '2TRB',
            'Label' => 'Advertising|Display|Transit Advertising, All Types|Electronic Display',
        ),
        '2TRC' => array(
            'Id' => '2TRC',
            'Label' => 'Advertising|Display|Transit Advertising, Bus Panel|Electronic Display',
        ),
        '2TRD' => array(
            'Id' => '2TRD',
            'Label' => 'Products|Merchandise|Trading Cards|Printed',
        ),
        '2TRE' => array(
            'Id' => '2TRE',
            'Label' => 'Advertising|Display|Transit Advertising, Bus Poster|Printed',
        ),
        '2TRF' => array(
            'Id' => '2TRF',
            'Label' => 'Advertising|Display|Transit Advertising, Bus Rear Display|Electronic Display',
        ),
        '2TRG' => array(
            'Id' => '2TRG',
            'Label' => 'Advertising|Display|Transit Advertising, Bus Rear Display|Printed',
        ),
        '2TRH' => array(
            'Id' => '2TRH',
            'Label' => 'Advertising|Display|Transit Advertising, Ferry Advertising|Electronic Display',
        ),
        '2TRI' => array(
            'Id' => '2TRI',
            'Label' => 'Advertising|Display|Transit Advertising, Subway Advertising|Electronic Display',
        ),
        '2TRJ' => array(
            'Id' => '2TRJ',
            'Label' => 'Advertising|Display|Transit Advertising, Subway Advertising|Printed',
        ),
        '2TRK' => array(
            'Id' => '2TRK',
            'Label' => 'Advertising|Display|Transit Advertising, Train Advertising|Electronic Display',
        ),
        '2TRL' => array(
            'Id' => '2TRL',
            'Label' => 'Advertising|Display|Transit Advertising, Train Advertising|Printed',
        ),
        '2TRM' => array(
            'Id' => '2TRM',
            'Label' => 'Advertising|Display|Transit Advertising, Bus Wrap|Electronic Display',
        ),
        '2TRN' => array(
            'Id' => '2TRN',
            'Label' => 'Advertising|Display|Transit Advertising, Bus Wrap|Printed',
        ),
        '2TRQ' => array(
            'Id' => '2TRQ',
            'Label' => 'Advertising|Display|Transit Advertising, Commercial Vehicles|Printed',
        ),
        '2TRR' => array(
            'Id' => '2TRR',
            'Label' => 'Advertising|Display|Transit Advertising, Commercial Vehicles|Electronic Display',
        ),
        '2TRY' => array(
            'Id' => '2TRY',
            'Label' => 'Motion Picture & TV|Motion Picture|Documentary Film|Recordable Media',
        ),
        '2TST' => array(
            'Id' => '2TST',
            'Label' => 'Products|Merchandise|Apparel, T-Shirts|Printed or Woven',
        ),
        '2TUA' => array(
            'Id' => '2TUA',
            'Label' => 'Products|Merchandise|Virtual Reality|Recordable Media',
        ),
        '2TUB' => array(
            'Id' => '2TUB',
            'Label' => 'Editorial|Periodicals|Newspaper, Weekly Supplement|Printed',
        ),
        '2TUG' => array(
            'Id' => '2TUG',
            'Label' => 'Motion Picture & TV|Motion Picture|Documentary Film|All Internet Distribution Formats',
        ),
        '2TUX' => array(
            'Id' => '2TUX',
            'Label' => 'Internal Company Use|Periodicals|Magazine, Custom Published|Printed',
        ),
        '2TVA' => array(
            'Id' => '2TVA',
            'Label' => 'Motion Picture & TV|Television Programming|All Television Advertising Types|Television Broadcast',
        ),
        '2TVB' => array(
            'Id' => '2TVB',
            'Label' => 'Motion Picture & TV|Television Programming|All Television Advertising Types|All Electronic Distribution Formats',
        ),
        '2TVC' => array(
            'Id' => '2TVC',
            'Label' => 'Motion Picture & TV|Television Programming|Commercial|Television Broadcast',
        ),
        '2TVD' => array(
            'Id' => '2TVD',
            'Label' => 'Motion Picture & TV|Television Programming|Commercial|All Electronic Distribution Formats',
        ),
        '2TVE' => array(
            'Id' => '2TVE',
            'Label' => 'Motion Picture & TV|Television Programming|Infomercial|Television Broadcast',
        ),
        '2TVF' => array(
            'Id' => '2TVF',
            'Label' => 'Motion Picture & TV|Television Programming|Infomercial|All Electronic Distribution Formats',
        ),
        '2TVG' => array(
            'Id' => '2TVG',
            'Label' => 'Motion Picture & TV|Television Programming|On-Air Promotion|Television Broadcast',
        ),
        '2TVH' => array(
            'Id' => '2TVH',
            'Label' => 'Motion Picture & TV|Television Programming|On-Air Promotion|All Electronic Distribution Formats',
        ),
        '2TVI' => array(
            'Id' => '2TVI',
            'Label' => 'Motion Picture & TV|Television Programming|Documentary Program|Television Broadcast',
        ),
        '2TVJ' => array(
            'Id' => '2TVJ',
            'Label' => 'Motion Picture & TV|Television Programming|Documentary Program|All Electronic Distribution Formats',
        ),
        '2TVK' => array(
            'Id' => '2TVK',
            'Label' => 'Motion Picture & TV|Television Programming|Educational Program|Television Broadcast',
        ),
        '2TVL' => array(
            'Id' => '2TVL',
            'Label' => 'Motion Picture & TV|Television Programming|Educational Program|All Electronic Distribution Formats',
        ),
        '2TVM' => array(
            'Id' => '2TVM',
            'Label' => 'Motion Picture & TV|Television Programming|Entertainment Program|Television Broadcast',
        ),
        '2TVN' => array(
            'Id' => '2TVN',
            'Label' => 'Motion Picture & TV|Television Programming|Entertainment Program|All Electronic Distribution Formats',
        ),
        '2TVP' => array(
            'Id' => '2TVP',
            'Label' => 'Motion Picture & TV|Television Programming|Made For TV Movie|All Electronic Distribution Formats',
        ),
        '2TVQ' => array(
            'Id' => '2TVQ',
            'Label' => 'Motion Picture & TV|Television Programming|Set Decor|All Electronic Distribution Formats',
        ),
        '2TVR' => array(
            'Id' => '2TVR',
            'Label' => 'Motion Picture & TV|Music Video|All Music Video Types|All Electronic Distribution Formats',
        ),
        '2TVS' => array(
            'Id' => '2TVS',
            'Label' => 'Motion Picture & TV|Television Programming|News Program|Television Broadcast',
        ),
        '2TVT' => array(
            'Id' => '2TVT',
            'Label' => 'Motion Picture & TV|Television Programming|News Program|All Electronic Distribution Formats',
        ),
        '2TVU' => array(
            'Id' => '2TVU',
            'Label' => 'Motion Picture & TV|Television Programming|Non-Profit Program|Television Broadcast',
        ),
        '2TVV' => array(
            'Id' => '2TVV',
            'Label' => 'Motion Picture & TV|Television Programming|Non-Profit Program|All Electronic Distribution Formats',
        ),
        '2TVW' => array(
            'Id' => '2TVW',
            'Label' => 'Motion Picture & TV|Television Programming|Prop|All Electronic Distribution Formats',
        ),
        '2TVY' => array(
            'Id' => '2TVY',
            'Label' => 'Motion Picture & TV|Television Programming|Made For TV Movie|Television Broadcast',
        ),
        '2TVZ' => array(
            'Id' => '2TVZ',
            'Label' => 'Motion Picture & TV|Television Programming|All Editorial Television Types|All Electronic Distribution Formats',
        ),
        '2UNL' => array(
            'Id' => '2UNL',
            'Label' => 'Unlicensed|Not Applicable|Not Applicable|Not Applicable',
        ),
        '2WAB' => array(
            'Id' => '2WAB',
            'Label' => 'Editorial|Book|Retail Book, Illustrated Book|Printed',
        ),
        '2WAG' => array(
            'Id' => '2WAG',
            'Label' => 'Motion Picture & TV|Motion Picture|Feature Film|All Internet Distribution Formats',
        ),
        '2WAL' => array(
            'Id' => '2WAL',
            'Label' => 'Products|Merchandise|Wallpaper|Printed',
        ),
        '2WAN' => array(
            'Id' => '2WAN',
            'Label' => 'Internal Company Use|Website|All Website Types|Intranet and Extranet Website',
        ),
        '2WAR' => array(
            'Id' => '2WAR',
            'Label' => 'Products|Merchandise|Computer Software|All Electronic Distribution Formats',
        ),
        '2WAX' => array(
            'Id' => '2WAX',
            'Label' => 'Advertising|Point of Purchase|Table Tent|Printed',
        ),
        '2WEB' => array(
            'Id' => '2WEB',
            'Label' => 'Advertising|Website|Web Page, All Types|Internet Website',
        ),
        '2WED' => array(
            'Id' => '2WED',
            'Label' => 'Personal Use|Personal Review|All Review Types|Printed',
        ),
        '2WEE' => array(
            'Id' => '2WEE',
            'Label' => 'Products|Merchandise|Stamp|Printed',
        ),
        '2WET' => array(
            'Id' => '2WET',
            'Label' => 'Advertising|Display|Poster, Restroom Poster|Electronic Display',
        ),
        '2WHA' => array(
            'Id' => '2WHA',
            'Label' => 'Editorial|Book|Retail Book, Illustrated Guide|Printed',
        ),
        '2WHO' => array(
            'Id' => '2WHO',
            'Label' => 'Products|Product Packaging|Wholesale Packaging, All Packaging Types|Printed',
        ),
        '2WIG' => array(
            'Id' => '2WIG',
            'Label' => 'Editorial|Periodicals|Scholarly Journal|Printed',
        ),
        '2WIN' => array(
            'Id' => '2WIN',
            'Label' => 'Motion Picture & TV|Motion Picture|Feature Film|Recordable Media',
        ),
        '2WIT' => array(
            'Id' => '2WIT',
            'Label' => 'Products|Merchandise|Stationery|Printed',
        ),
        '2WIZ' => array(
            'Id' => '2WIZ',
            'Label' => 'Motion Picture & TV|Motion Picture|Short Film|Projected Display',
        ),
        '2WRP' => array(
            'Id' => '2WRP',
            'Label' => 'Products|Merchandise|Gift Wrap|Printed',
        ),
        '2WRY' => array(
            'Id' => '2WRY',
            'Label' => 'Products|Merchandise|Screen Saver|Recordable Media',
        ),
        '2YAK' => array(
            'Id' => '2YAK',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Teachers\' Edition|Printed',
        ),
        '2YAM' => array(
            'Id' => '2YAM',
            'Label' => 'Advertising|Marketing Materials|Promotional Calendar, Multi-Page|Printed',
        ),
        '2YAP' => array(
            'Id' => '2YAP',
            'Label' => 'Editorial|Book|Retail Book, Novelty Book|Printed',
        ),
        '2YEA' => array(
            'Id' => '2YEA',
            'Label' => 'Editorial|Book|Retail Book, Directory|All Electronic Distribution Formats',
        ),
        '2YEN' => array(
            'Id' => '2YEN',
            'Label' => 'Editorial|Book|Retail Book, Postcard Book|Printed',
        ),
        '2YET' => array(
            'Id' => '2YET',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Packaging For Recordable Media|Printed',
        ),
        '2YOK' => array(
            'Id' => '2YOK',
            'Label' => 'Editorial|Book|Retail Book, Young Adult Book|Printed',
        ),
        '2YUM' => array(
            'Id' => '2YUM',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Lab Manual|Printed',
        ),
        '2ZAG' => array(
            'Id' => '2ZAG',
            'Label' => 'Editorial|Mobile|All Mobile Types|Mobile',
        ),
        '2ZAM' => array(
            'Id' => '2ZAM',
            'Label' => 'Products|Mobile|All Mobile Types|Mobile',
        ),
        '2ZAP' => array(
            'Id' => '2ZAP',
            'Label' => 'Products|Mobile|Entertainment Programming|Mobile',
        ),
        '2ZEN' => array(
            'Id' => '2ZEN',
            'Label' => 'Products|Mobile|Computer Software|Mobile',
        ),
        '2ZIG' => array(
            'Id' => '2ZIG',
            'Label' => 'Products|Mobile|Wallpaper|Mobile',
        ),
        '2ZIP' => array(
            'Id' => '2ZIP',
            'Label' => 'Products|Mobile|Game, All Types|Mobile',
        ),
        '2ZIT' => array(
            'Id' => '2ZIT',
            'Label' => 'Personal Use|Mobile|Wallpaper|Mobile',
        ),
        '2ZOA' => array(
            'Id' => '2ZOA',
            'Label' => 'Editorial|Book|Textbook, Course Pack|Recordable Media',
        ),
        '2ZOB' => array(
            'Id' => '2ZOB',
            'Label' => 'Personal Use|Mobile|All Mobile Types|Mobile',
        ),
        '2ZOO' => array(
            'Id' => '2ZOO',
            'Label' => 'Editorial|Book|Textbook, Course Pack|Internet Email',
        ),
        '2ZOT' => array(
            'Id' => '2ZOT',
            'Label' => 'Editorial|Book|Textbook Ancillary Materials, Teacher\'s Manual|Printed',
        ),
        '2ZUM' => array(
            'Id' => '2ZUM',
            'Label' => 'Internal Company Use|Mobile|All Mobile Types|Mobile',
        ),
        '2ZUS' => array(
            'Id' => '2ZUS',
            'Label' => 'Advertising|Mobile|All Mobile Types|Mobile',
        ),
        '3PAA' => array(
            'Id' => '3PAA',
            'Label' => 'Any Placements on All Pages',
        ),
        '3PNB' => array(
            'Id' => '3PNB',
            'Label' => 'Multiple Placements on One Side',
        ),
        '3PNC' => array(
            'Id' => '3PNC',
            'Label' => 'Multiple Placements on Back Cover',
        ),
        '3PND' => array(
            'Id' => '3PND',
            'Label' => 'Single Placement as Chapter Opener',
        ),
        '3PNE' => array(
            'Id' => '3PNE',
            'Label' => 'Single Placement on Front Side',
        ),
        '3PNF' => array(
            'Id' => '3PNF',
            'Label' => 'Single Placement on Front Cover And Back Cover',
        ),
        '3PNH' => array(
            'Id' => '3PNH',
            'Label' => 'Multiple Placements in Body Of Program',
        ),
        '3PNI' => array(
            'Id' => '3PNI',
            'Label' => 'Single Placement as Chapter Opener',
        ),
        '3PNJ' => array(
            'Id' => '3PNJ',
            'Label' => 'Multiple Placements on Pop Ups',
        ),
        '3PNK' => array(
            'Id' => '3PNK',
            'Label' => 'Multiple Placements on Front Side',
        ),
        '3PNL' => array(
            'Id' => '3PNL',
            'Label' => 'Multiple Placements on Item',
        ),
        '3PNM' => array(
            'Id' => '3PNM',
            'Label' => 'Single Placement in Body Of Program',
        ),
        '3PNN' => array(
            'Id' => '3PNN',
            'Label' => 'Single Placement on Front Cover',
        ),
        '3PNP' => array(
            'Id' => '3PNP',
            'Label' => 'Single Placement on Both Sides',
        ),
        '3PNQ' => array(
            'Id' => '3PNQ',
            'Label' => 'Single Placement in Content Body',
        ),
        '3PNR' => array(
            'Id' => '3PNR',
            'Label' => 'Single Placement on Back Cover',
        ),
        '3PNS' => array(
            'Id' => '3PNS',
            'Label' => 'Multiple Placements on Splash Pages',
        ),
        '3PNT' => array(
            'Id' => '3PNT',
            'Label' => 'Single Placement on Front Page',
        ),
        '3PNU' => array(
            'Id' => '3PNU',
            'Label' => 'Multiple Placements on Home Page',
        ),
        '3PNV' => array(
            'Id' => '3PNV',
            'Label' => 'Multiple Placements on Front Side',
        ),
        '3PNW' => array(
            'Id' => '3PNW',
            'Label' => 'Single Placement in Body Of Program',
        ),
        '3PNX' => array(
            'Id' => '3PNX',
            'Label' => 'Multiple Placements as Flash',
        ),
        '3PNY' => array(
            'Id' => '3PNY',
            'Label' => 'Multiple Placements on Inside Cover',
        ),
        '3PNZ' => array(
            'Id' => '3PNZ',
            'Label' => 'Single Placement in Bibliography',
        ),
        '3PPA' => array(
            'Id' => '3PPA',
            'Label' => 'Single Placement on Dust Jacket',
        ),
        '3PPB' => array(
            'Id' => '3PPB',
            'Label' => 'Single Placement in Closing Sequence',
        ),
        '3PPC' => array(
            'Id' => '3PPC',
            'Label' => 'Multiple Placements in Packaging Interior',
        ),
        '3PPD' => array(
            'Id' => '3PPD',
            'Label' => 'Single Placement in Packaging Interior',
        ),
        '3PPE' => array(
            'Id' => '3PPE',
            'Label' => 'Single Placement as Flash',
        ),
        '3PPF' => array(
            'Id' => '3PPF',
            'Label' => 'Multiple Placements on Front Cover',
        ),
        '3PPG' => array(
            'Id' => '3PPG',
            'Label' => 'Single Placement on Splash Page',
        ),
        '3PPH' => array(
            'Id' => '3PPH',
            'Label' => 'Single Placement on Inside',
        ),
        '3PPI' => array(
            'Id' => '3PPI',
            'Label' => 'Multiple Placements on Landing Pages',
        ),
        '3PPJ' => array(
            'Id' => '3PPJ',
            'Label' => 'Multiple Placements in Body Of Advertisement',
        ),
        '3PPK' => array(
            'Id' => '3PPK',
            'Label' => 'Single Placement on Inside Cover',
        ),
        '3PPL' => array(
            'Id' => '3PPL',
            'Label' => 'Single Placement on Both Sides',
        ),
        '3PPM' => array(
            'Id' => '3PPM',
            'Label' => 'Multiple Placements on Any Pages',
        ),
        '3PPN' => array(
            'Id' => '3PPN',
            'Label' => 'Multiple Placements in Closing Sequence',
        ),
        '3PPP' => array(
            'Id' => '3PPP',
            'Label' => 'Multiple Placements on Front Cover And Interior Pages',
        ),
        '3PPQ' => array(
            'Id' => '3PPQ',
            'Label' => 'Multiple Placements on Home Page And Secondary Pages',
        ),
        '3PPR' => array(
            'Id' => '3PPR',
            'Label' => 'Single Placement in Any Part',
        ),
        '3PPS' => array(
            'Id' => '3PPS',
            'Label' => 'Single Placement on Landing Page',
        ),
        '3PPU' => array(
            'Id' => '3PPU',
            'Label' => 'Single Placement on Title Page',
        ),
        '3PPV' => array(
            'Id' => '3PPV',
            'Label' => 'Multiple Placements on Front Cover',
        ),
        '3PPW' => array(
            'Id' => '3PPW',
            'Label' => 'Single Placement as Forward',
        ),
        '3PPX' => array(
            'Id' => '3PPX',
            'Label' => 'Single Placement on Packaging Exterior|Front',
        ),
        '3PPY' => array(
            'Id' => '3PPY',
            'Label' => 'Single Placement on Home Page',
        ),
        '3PPZ' => array(
            'Id' => '3PPZ',
            'Label' => 'Multiple Placements in Body Of Program',
        ),
        '3PQA' => array(
            'Id' => '3PQA',
            'Label' => 'Single Placement as Frontispiece',
        ),
        '3PQB' => array(
            'Id' => '3PQB',
            'Label' => 'Multiple Placements on Wrap Around Cover',
        ),
        '3PQC' => array(
            'Id' => '3PQC',
            'Label' => 'Single Placement on Back Side',
        ),
        '3PQD' => array(
            'Id' => '3PQD',
            'Label' => 'Single Placement on Secondary Page',
        ),
        '3PQE' => array(
            'Id' => '3PQE',
            'Label' => 'Single Placement on Spine',
        ),
        '3PQF' => array(
            'Id' => '3PQF',
            'Label' => 'Single Placements in Bibliography',
        ),
        '3PQG' => array(
            'Id' => '3PQG',
            'Label' => 'Single Placement on Any Interior Page',
        ),
        '3PQH' => array(
            'Id' => '3PQH',
            'Label' => 'Single Placement on Back Cover',
        ),
        '3PQI' => array(
            'Id' => '3PQI',
            'Label' => 'Single Placement on Preface',
        ),
        '3PQJ' => array(
            'Id' => '3PQJ',
            'Label' => 'Single Placement on Wrap Around Cover',
        ),
        '3PQK' => array(
            'Id' => '3PQK',
            'Label' => 'Multiple Placements on Both Sides',
        ),
        '3PQL' => array(
            'Id' => '3PQL',
            'Label' => 'Multiple Placements in Closing Sequence',
        ),
        '3PQM' => array(
            'Id' => '3PQM',
            'Label' => 'Multiple Placements on Back Side',
        ),
        '3PQN' => array(
            'Id' => '3PQN',
            'Label' => 'Single Placement as Vignette',
        ),
        '3PQP' => array(
            'Id' => '3PQP',
            'Label' => 'Single Placement on Pop Up',
        ),
        '3PQQ' => array(
            'Id' => '3PQQ',
            'Label' => 'Single Placement as Unit Opener',
        ),
        '3PQS' => array(
            'Id' => '3PQS',
            'Label' => 'Multiple Placements on Back Cover',
        ),
        '3PQT' => array(
            'Id' => '3PQT',
            'Label' => 'Single Placement on Table Of Contents',
        ),
        '3PQU' => array(
            'Id' => '3PQU',
            'Label' => 'Single Placement in Closing Sequence',
        ),
        '3PQW' => array(
            'Id' => '3PQW',
            'Label' => 'Multiple Placements on Any Interior Pages',
        ),
        '3PQX' => array(
            'Id' => '3PQX',
            'Label' => 'Single Placement on Any Interior Page',
        ),
        '3PQY' => array(
            'Id' => '3PQY',
            'Label' => 'Single Placement on Front Cover',
        ),
        '3PQZ' => array(
            'Id' => '3PQZ',
            'Label' => 'Single Placement on Index',
        ),
        '3PRB' => array(
            'Id' => '3PRB',
            'Label' => 'Single Placement on Front Cover',
        ),
        '3PRC' => array(
            'Id' => '3PRC',
            'Label' => 'Single Placement on Table Of Contents',
        ),
        '3PRD' => array(
            'Id' => '3PRD',
            'Label' => 'Multiple Placements in Content Body',
        ),
        '3PRF' => array(
            'Id' => '3PRF',
            'Label' => 'Multiple Placements on Any Interior Pages',
        ),
        '3PRG' => array(
            'Id' => '3PRG',
            'Label' => 'Multiple Placements on Front Cover And Back Cover',
        ),
        '3PRH' => array(
            'Id' => '3PRH',
            'Label' => 'Single Placement on Any Interior Page',
        ),
        '3PRI' => array(
            'Id' => '3PRI',
            'Label' => 'Single Placement as Colophon',
        ),
        '3PRJ' => array(
            'Id' => '3PRJ',
            'Label' => 'Multiple Placements on Front Cover',
        ),
        '3PRK' => array(
            'Id' => '3PRK',
            'Label' => 'Multiple Placements on Inside',
        ),
        '3PRL' => array(
            'Id' => '3PRL',
            'Label' => 'Multiple Placements on Any Interior Pages',
        ),
        '3PRM' => array(
            'Id' => '3PRM',
            'Label' => 'Single Placement on Front Cover And Back Cover',
        ),
        '3PRN' => array(
            'Id' => '3PRN',
            'Label' => 'Single Placement on Any Interior Page',
        ),
        '3PRP' => array(
            'Id' => '3PRP',
            'Label' => 'Single Placement in Body Of Advertisement',
        ),
        '3PRQ' => array(
            'Id' => '3PRQ',
            'Label' => 'Single Placement on Screen',
        ),
        '3PRR' => array(
            'Id' => '3PRR',
            'Label' => 'Single Placement in Title Sequence',
        ),
        '3PRS' => array(
            'Id' => '3PRS',
            'Label' => 'Single Placement in Content Body',
        ),
        '3PRT' => array(
            'Id' => '3PRT',
            'Label' => 'Single Placement on Pop Up',
        ),
        '3PRU' => array(
            'Id' => '3PRU',
            'Label' => 'Single Placement on Front Side',
        ),
        '3PRV' => array(
            'Id' => '3PRV',
            'Label' => 'Multiple Placements on Both Sides',
        ),
        '3PRW' => array(
            'Id' => '3PRW',
            'Label' => 'Multiple Placements in Any Part',
        ),
        '3PRY' => array(
            'Id' => '3PRY',
            'Label' => 'Multiple Placements in Content Body',
        ),
        '3PRZ' => array(
            'Id' => '3PRZ',
            'Label' => 'Multiple Placements on Front Cover',
        ),
        '3PSA' => array(
            'Id' => '3PSA',
            'Label' => 'Single Placement as Front Matter',
        ),
        '3PSB' => array(
            'Id' => '3PSB',
            'Label' => 'Multiple Placements on Back Side',
        ),
        '3PSC' => array(
            'Id' => '3PSC',
            'Label' => 'Single Placement in Title Sequence',
        ),
        '3PSD' => array(
            'Id' => '3PSD',
            'Label' => 'Multiple Placements on Screen',
        ),
        '3PSF' => array(
            'Id' => '3PSF',
            'Label' => 'Multiple Placements on Back Cover',
        ),
        '3PSH' => array(
            'Id' => '3PSH',
            'Label' => 'Single Placement on Back Cover',
        ),
        '3PSI' => array(
            'Id' => '3PSI',
            'Label' => 'Multiple Placements on Packaging Exterior|Front',
        ),
        '3PSJ' => array(
            'Id' => '3PSJ',
            'Label' => 'Single Placement on Front Cover',
        ),
        '3PSK' => array(
            'Id' => '3PSK',
            'Label' => 'Multiple Placements in Title Sequence',
        ),
        '3PSL' => array(
            'Id' => '3PSL',
            'Label' => 'Single Placement on Flap',
        ),
        '3PSM' => array(
            'Id' => '3PSM',
            'Label' => 'Multiple Placements on Any Interior Pages',
        ),
        '3PSN' => array(
            'Id' => '3PSN',
            'Label' => 'Multiple Placements on Back Cover',
        ),
        '3PSP' => array(
            'Id' => '3PSP',
            'Label' => 'Single Placement on Back Cover',
        ),
        '3PSQ' => array(
            'Id' => '3PSQ',
            'Label' => 'Single Placement on Back Side',
        ),
        '3PSR' => array(
            'Id' => '3PSR',
            'Label' => 'Single Placement on Inside Cover',
        ),
        '3PSS' => array(
            'Id' => '3PSS',
            'Label' => 'Multiple Placements on Front Cover And Back Cover',
        ),
        '3PST' => array(
            'Id' => '3PST',
            'Label' => 'Single Placement on Any Interior Page',
        ),
        '3PSU' => array(
            'Id' => '3PSU',
            'Label' => 'Multiple Placements on Inside Cover',
        ),
        '3PSV' => array(
            'Id' => '3PSV',
            'Label' => 'Multiple Placements in Title Sequence',
        ),
        '3PSW' => array(
            'Id' => '3PSW',
            'Label' => 'Single Placement on Item',
        ),
        '3PSY' => array(
            'Id' => '3PSY',
            'Label' => 'Multiple Placements on Any Interior Pages',
        ),
        '3PSZ' => array(
            'Id' => '3PSZ',
            'Label' => 'Multiple Placements on Secondary Pages',
        ),
        '3PTA' => array(
            'Id' => '3PTA',
            'Label' => 'Single Placement on One Side',
        ),
        '3PTB' => array(
            'Id' => '3PTB',
            'Label' => 'Single Placement on Front Cover And Interior Page',
        ),
        '3PTC' => array(
            'Id' => '3PTC',
            'Label' => 'Multiple Placements on Dust Jacket',
        ),
        '3PTD' => array(
            'Id' => '3PTD',
            'Label' => 'Single Placements on Interior, Covers and Jacket',
        ),
        '3PTE' => array(
            'Id' => '3PTE',
            'Label' => 'Multiple Placements on Interior, Covers and Jacket',
        ),
        '3PTF' => array(
            'Id' => '3PTF',
            'Label' => 'Single Placement on Section Opener Page',
        ),
        '3PTG' => array(
            'Id' => '3PTG',
            'Label' => 'Multiple Placements on Section Opener Page',
        ),
        '3PTH' => array(
            'Id' => '3PTH',
            'Label' => 'Single Placement on Section Opener and Front Page',
        ),
        '3PTI' => array(
            'Id' => '3PTI',
            'Label' => 'Multiple Placements on Section Opener and Front Page',
        ),
        '3PTJ' => array(
            'Id' => '3PTJ',
            'Label' => 'Single Placement on Any Covers And Interior Pages',
        ),
        '3PTK' => array(
            'Id' => '3PTK',
            'Label' => 'Multiple Placements on Any Covers And Interior Pages',
        ),
        '3PTL' => array(
            'Id' => '3PTL',
            'Label' => 'Single Placement on Front Cover And Back Cover',
        ),
        '3PTM' => array(
            'Id' => '3PTM',
            'Label' => 'Multiple Placements on Front Cover And Back Cover',
        ),
        '3PTN' => array(
            'Id' => '3PTN',
            'Label' => 'Multiple Placements on Any Covers And Interior Pages',
        ),
        '3PTP' => array(
            'Id' => '3PTP',
            'Label' => 'Single Placement on Any Cover And Interior Page',
        ),
        '3PTQ' => array(
            'Id' => '3PTQ',
            'Label' => 'Single Placement on Any Covers And Interior Pages',
        ),
        '3PTR' => array(
            'Id' => '3PTR',
            'Label' => 'Multiple Placements on Any Covers And Interior Pages',
        ),
        '3PTS' => array(
            'Id' => '3PTS',
            'Label' => 'Single Placement Anywhere On Packaging',
        ),
        '3PTT' => array(
            'Id' => '3PTT',
            'Label' => 'Multiple Placements Anywhere On Packaging',
        ),
        '3PTU' => array(
            'Id' => '3PTU',
            'Label' => 'Multiple Placements in Any Part',
        ),
        '3PTV' => array(
            'Id' => '3PTV',
            'Label' => 'Single Placement in Any Part',
        ),
        '3PTW' => array(
            'Id' => '3PTW',
            'Label' => 'Single Placement on Any Pages',
        ),
        '3PTY' => array(
            'Id' => '3PTY',
            'Label' => 'Single Placement on Home Page And Secondary Pages',
        ),
        '3PTZ' => array(
            'Id' => '3PTZ',
            'Label' => 'Multiple Placements on Any Pages',
        ),
        '3PUB' => array(
            'Id' => '3PUB',
            'Label' => 'Multiple Placements in Any Part',
        ),
        '3PUC' => array(
            'Id' => '3PUC',
            'Label' => 'Single Placement in Any Part',
        ),
        '3PUD' => array(
            'Id' => '3PUD',
            'Label' => 'Single Placement on Back Cover Or Interior Page',
        ),
        '3PUF' => array(
            'Id' => '3PUF',
            'Label' => 'Single Placement on Front Cover Or Back Cover',
        ),
        '3PUH' => array(
            'Id' => '3PUH',
            'Label' => 'Multiple Placements on Packaging Exterior|Back or Side',
        ),
        '3PUJ' => array(
            'Id' => '3PUJ',
            'Label' => 'Single Placement on Packaging Exterior|Back or Side',
        ),
        '3PUL' => array(
            'Id' => '3PUL',
            'Label' => 'Any Placements',
        ),
        '3PXX' => array(
            'Id' => '3PXX',
            'Label' => 'Not Applicable or None',
        ),
        '4SAA' => array(
            'Id' => '4SAA',
            'Label' => 'Any Size Image|Any Size Media',
        ),
        '4SAB' => array(
            'Id' => '4SAB',
            'Label' => 'Up To 1/2 Area Image|Up To A0 Display',
        ),
        '4SAC' => array(
            'Id' => '4SAC',
            'Label' => 'Up To Full Page Image|Up To Full Page Media',
        ),
        '4SAD' => array(
            'Id' => '4SAD',
            'Label' => 'Up To 1/4 Area Image|Up To 16 Sheet Display',
        ),
        '4SAE' => array(
            'Id' => '4SAE',
            'Label' => 'Up To 14 x 20 Inch Image|Any Size Media',
        ),
        '4SAF' => array(
            'Id' => '4SAF',
            'Label' => 'Up To 1/2 Area Image|Up To 4,800 Square Foot Display',
        ),
        '4SAG' => array(
            'Id' => '4SAG',
            'Label' => 'Up To Full Page Image|Any Size Page',
        ),
        '4SAH' => array(
            'Id' => '4SAH',
            'Label' => 'Up To Full Area Image|Up To 69 x 48 Inch Display',
        ),
        '4SAI' => array(
            'Id' => '4SAI',
            'Label' => 'Up To 1/2 Area Image|Up To 180 x 150 Pixels Ad',
        ),
        '4SAJ' => array(
            'Id' => '4SAJ',
            'Label' => 'Up To 1/2 Area Image|Up To 11 x 36 Foot Display',
        ),
        '4SAK' => array(
            'Id' => '4SAK',
            'Label' => 'Up To 1/4 Page Image|Any Size Page',
        ),
        '4SAL' => array(
            'Id' => '4SAL',
            'Label' => 'Up To 1/4 Area Image|Up To Full Area Media',
        ),
        '4SAM' => array(
            'Id' => '4SAM',
            'Label' => 'Up To 1/2 Area Image|Up To B1 Media',
        ),
        '4SAN' => array(
            'Id' => '4SAN',
            'Label' => 'Up To Full Area Image|Up To 10 x 8 Foot Media',
        ),
        '4SAP' => array(
            'Id' => '4SAP',
            'Label' => 'Up To 1/4 Screen Image|Up To 32 Inch Screen',
        ),
        '4SAQ' => array(
            'Id' => '4SAQ',
            'Label' => 'Up To 1/2 Area Image|Any Size Display',
        ),
        '4SAR' => array(
            'Id' => '4SAR',
            'Label' => 'Up To Full Area Image|Up To  728 x 90 Pixels Ad',
        ),
        '4SAS' => array(
            'Id' => '4SAS',
            'Label' => 'Up To 1/2 Area Image|Up To 24 x 36 Inch Media',
        ),
        '4SAT' => array(
            'Id' => '4SAT',
            'Label' => 'Up To 75 x 75 cm Image|Up To 100 x 100 cm Media',
        ),
        '4SAU' => array(
            'Id' => '4SAU',
            'Label' => 'Up To 1/2 Area Image|Up To 26 x 241 Inch Display',
        ),
        '4SAV' => array(
            'Id' => '4SAV',
            'Label' => 'Up To 8 x 12 Inch Image|Any Size Media',
        ),
        '4SAW' => array(
            'Id' => '4SAW',
            'Label' => 'Up To 1/2 Area Image|Up To 10,000 Square Foot Display',
        ),
        '4SAX' => array(
            'Id' => '4SAX',
            'Label' => 'Up To 1/2 Area Image|Up To 4 x 8 Foot Media',
        ),
        '4SAY' => array(
            'Id' => '4SAY',
            'Label' => 'Up To 1/2 Area Image|Up To 43 x 62 Inch Display',
        ),
        '4SAZ' => array(
            'Id' => '4SAZ',
            'Label' => 'Up To 1/2 Area Image|Up To B0 Display',
        ),
        '4SBA' => array(
            'Id' => '4SBA',
            'Label' => 'Up To 1/2 Area Image|Up To 24 x 30 Inch Display',
        ),
        '4SBC' => array(
            'Id' => '4SBC',
            'Label' => 'Up To 1/4 Area Image|Up To 8 Sheet Display',
        ),
        '4SBD' => array(
            'Id' => '4SBD',
            'Label' => 'Up To Full Area Image|Up To 10,000 Square Foot Display',
        ),
        '4SBE' => array(
            'Id' => '4SBE',
            'Label' => 'Up To 1/2 Screen Image|Up To 63 Inch Screen',
        ),
        '4SBF' => array(
            'Id' => '4SBF',
            'Label' => 'Up To Full Area Image|Any Size Display',
        ),
        '4SBG' => array(
            'Id' => '4SBG',
            'Label' => 'Any Size Image|Up To Full Screen Ad',
        ),
        '4SBH' => array(
            'Id' => '4SBH',
            'Label' => 'Up To Full Area Image|Up To A1 Media',
        ),
        '4SBI' => array(
            'Id' => '4SBI',
            'Label' => 'Up To 1/4 Area Image|Up To 10 x 8 Foot Media',
        ),
        '4SBJ' => array(
            'Id' => '4SBJ',
            'Label' => 'Up To 20 x 20 cm Image|Up To 100 x 100 cm Media',
        ),
        '4SBK' => array(
            'Id' => '4SBK',
            'Label' => 'Up To 1/16 Page Image|Any Size Page',
        ),
        '4SBL' => array(
            'Id' => '4SBL',
            'Label' => 'Up To 1/2 Area Image|Up To 8 Sheet Display',
        ),
        '4SBM' => array(
            'Id' => '4SBM',
            'Label' => 'Up To Full Area Image|Up To B0 Media',
        ),
        '4SBN' => array(
            'Id' => '4SBN',
            'Label' => 'Up To 1/8 Page Image|Any Size Page',
        ),
        '4SBP' => array(
            'Id' => '4SBP',
            'Label' => 'Any Size Image|Any Size Page',
        ),
        '4SBQ' => array(
            'Id' => '4SBQ',
            'Label' => 'Up To 1/2 Area Image|Up To 40 x 60 Inch Media',
        ),
        '4SBR' => array(
            'Id' => '4SBR',
            'Label' => 'Up To 1/2 Area Image|Up To 30 x 240 Inch Display',
        ),
        '4SBS' => array(
            'Id' => '4SBS',
            'Label' => 'Up To 1/4 Area Image|Up To 83 x 135 Inch Display',
        ),
        '4SBT' => array(
            'Id' => '4SBT',
            'Label' => 'Up To Full Area Image|Up To 30 x 40 Inch Display',
        ),
        '4SBU' => array(
            'Id' => '4SBU',
            'Label' => 'Up To 1/2 Page Image|Up To 2 Page Ad',
        ),
        '4SBV' => array(
            'Id' => '4SBV',
            'Label' => 'Up To 1/2 Area Image|Up To 43 x 126 Inch Display',
        ),
        '4SBW' => array(
            'Id' => '4SBW',
            'Label' => 'Up To 1/2 Area Image|Up To 138 x 53 Inch Media',
        ),
        '4SBX' => array(
            'Id' => '4SBX',
            'Label' => 'Up To Full Area Image|Up To 83 x 135 Inch Display',
        ),
        '4SBY' => array(
            'Id' => '4SBY',
            'Label' => 'Up To 1/2 Area Image|Up To 60 x 40 Inch Display',
        ),
        '4SBZ' => array(
            'Id' => '4SBZ',
            'Label' => 'Up To Full Screen Image|Up To 32 Inch Screen',
        ),
        '4SCA' => array(
            'Id' => '4SCA',
            'Label' => 'Up To 1/4 Area Image|Up To 468 x 60 Pixels Ad',
        ),
        '4SCB' => array(
            'Id' => '4SCB',
            'Label' => 'Up To 50 x 50 cm Image|Up To 100 x 100 cm Media',
        ),
        '4SCC' => array(
            'Id' => '4SCC',
            'Label' => 'Up To 1/2 Area Image|Up To B1 Display',
        ),
        '4SCD' => array(
            'Id' => '4SCD',
            'Label' => 'Up To 1/4 Area Image|Up To 48 Sheet Display',
        ),
        '4SCE' => array(
            'Id' => '4SCE',
            'Label' => 'Up To 20 x 20 Inch Image|Up To 40 x 40 Inch Media',
        ),
        '4SCF' => array(
            'Id' => '4SCF',
            'Label' => 'Up To 1/4 Area Image|Up To  300 x 600 Pixels Ad',
        ),
        '4SCG' => array(
            'Id' => '4SCG',
            'Label' => 'Up To 1/2 Area Image|Any Size Item',
        ),
        '4SCH' => array(
            'Id' => '4SCH',
            'Label' => 'Up To 1/4 Page Image|Up To 1/2 Page Ad',
        ),
        '4SCI' => array(
            'Id' => '4SCI',
            'Label' => 'Up To 100 x 100 cm Image|Up To 100 x 100 cm Media',
        ),
        '4SCJ' => array(
            'Id' => '4SCJ',
            'Label' => 'Up To 1/4 Area Image|Up To B1 Media',
        ),
        '4SCK' => array(
            'Id' => '4SCK',
            'Label' => 'Up To 1/4 Area Image|Up To  728 x 90 Pixels Ad',
        ),
        '4SCL' => array(
            'Id' => '4SCL',
            'Label' => 'Up To 1/2 Area Image|Up To 30 x 40 Inch Display',
        ),
        '4SCM' => array(
            'Id' => '4SCM',
            'Label' => 'Up To 40 x 60 Inch Image|Any Size Media',
        ),
        '4SCN' => array(
            'Id' => '4SCN',
            'Label' => 'Up To 1/8 Page Image|Up To Full Page Media',
        ),
        '4SCP' => array(
            'Id' => '4SCP',
            'Label' => 'Up To Full Page Image|Up To Full Page Ad',
        ),
        '4SCQ' => array(
            'Id' => '4SCQ',
            'Label' => 'Up To 70 x 100 cm Image|Any Size Media',
        ),
        '4SCR' => array(
            'Id' => '4SCR',
            'Label' => 'Up To 1/2 Area Image|Up To 14 x 48 Foot Display',
        ),
        '4SCS' => array(
            'Id' => '4SCS',
            'Label' => 'Up To 1/4 Screen Image|Up To 21 Inch Screen',
        ),
        '4SCT' => array(
            'Id' => '4SCT',
            'Label' => 'Up To 40 x 40 Inch Image|Up To 40 x 40 Inch Media',
        ),
        '4SCU' => array(
            'Id' => '4SCU',
            'Label' => 'Up To 6 x 9 Inch Image|Any Size Media',
        ),
        '4SCV' => array(
            'Id' => '4SCV',
            'Label' => 'Up To 1/2 Page Image|Up To 1/2 Page Ad',
        ),
        '4SCW' => array(
            'Id' => '4SCW',
            'Label' => 'Up To 1/2 Area Image|Up To  728 x 90 Pixels Ad',
        ),
        '4SCX' => array(
            'Id' => '4SCX',
            'Label' => 'Up To Full Area Image|Any Size Display',
        ),
        '4SCY' => array(
            'Id' => '4SCY',
            'Label' => 'Up To Full Screen Image|Up To 15 Inch Screen',
        ),
        '4SCZ' => array(
            'Id' => '4SCZ',
            'Label' => 'Up To 1/4 Screen Image|Up To 15 Inch Screen',
        ),
        '4SDA' => array(
            'Id' => '4SDA',
            'Label' => 'Up To 11 x 15 cm Image|Any Size Media',
        ),
        '4SDB' => array(
            'Id' => '4SDB',
            'Label' => 'Up To 20 x 24 Inch Image|Any Size Media',
        ),
        '4SDC' => array(
            'Id' => '4SDC',
            'Label' => 'Up To 50 x 71 cm Image|Any Size Media',
        ),
        '4SDD' => array(
            'Id' => '4SDD',
            'Label' => 'Up To 1/4 Area Image|Up To 4 Sheet Display',
        ),
        '4SDE' => array(
            'Id' => '4SDE',
            'Label' => 'Up To 30 x 40 Inch Image|Any Size Media',
        ),
        '4SDF' => array(
            'Id' => '4SDF',
            'Label' => 'Up To 1/2 Area Image|Up To 96 Sheet Display',
        ),
        '4SDG' => array(
            'Id' => '4SDG',
            'Label' => 'Up To 24 x 30 Inch Image|Any Size Media',
        ),
        '4SDH' => array(
            'Id' => '4SDH',
            'Label' => 'Up To 1/4 Area Image|Up To 27 x 141 Inch Display',
        ),
        '4SDI' => array(
            'Id' => '4SDI',
            'Label' => 'Up To Full Area Image|Up To 12 x 24 Foot Media',
        ),
        '4SDJ' => array(
            'Id' => '4SDJ',
            'Label' => 'Up To Full Area Image|Up To 138 x 53 Inch Media',
        ),
        '4SDK' => array(
            'Id' => '4SDK',
            'Label' => 'Up To Full Area Image|Up To 96 Sheet Display',
        ),
        '4SDL' => array(
            'Id' => '4SDL',
            'Label' => 'Up To Full Screen Image|Any Size Screen',
        ),
        '4SDM' => array(
            'Id' => '4SDM',
            'Label' => 'Up To 1/4 Area Image|Up To 27 x 85 Inch Display',
        ),
        '4SDN' => array(
            'Id' => '4SDN',
            'Label' => 'Up To Full Area Image|Up To 40 x 60 Inch Media',
        ),
        '4SDP' => array(
            'Id' => '4SDP',
            'Label' => 'Up To 30x 30 Inch Image|Up To 40 x 40 Inch Media',
        ),
        '4SDQ' => array(
            'Id' => '4SDQ',
            'Label' => 'Up To 1/2 Screen Image|Up To 100 Diagonal Foot Screen',
        ),
        '4SDR' => array(
            'Id' => '4SDR',
            'Label' => 'Up To Full Area Image|Up To 26 x 241 Inch Display',
        ),
        '4SDS' => array(
            'Id' => '4SDS',
            'Label' => 'Up To Full Area Image|Up To 1,200 Square Foot Display',
        ),
        '4SDT' => array(
            'Id' => '4SDT',
            'Label' => 'Up To 1/4 Area Image|Up To 46 x 60 Inch Media',
        ),
        '4SDU' => array(
            'Id' => '4SDU',
            'Label' => 'Up To 1/2 Area Image|Up To 4 Sheet Display',
        ),
        '4SDV' => array(
            'Id' => '4SDV',
            'Label' => 'Up To 1/4 Area Image|Up To 40 x 60 Inch Media',
        ),
        '4SDW' => array(
            'Id' => '4SDW',
            'Label' => 'Up To Full Area Image|Up To 43 x 62 Inch Display',
        ),
        '4SDX' => array(
            'Id' => '4SDX',
            'Label' => 'Up To 1/2 Area Image|Up To 10 x 40 Foot Display',
        ),
        '4SDY' => array(
            'Id' => '4SDY',
            'Label' => 'Up To 60 x 85 cm Image|Any Size Media',
        ),
        '4SDZ' => array(
            'Id' => '4SDZ',
            'Label' => 'Up To 1/2 Area Image|Up To A0 Media',
        ),
        '4SEA' => array(
            'Id' => '4SEA',
            'Label' => 'Up To 1/4 Area Image|Up To 30 Sheet Display',
        ),
        '4SEB' => array(
            'Id' => '4SEB',
            'Label' => 'Up To Full Screen Image|Up To 21 Inch Screen',
        ),
        '4SEC' => array(
            'Id' => '4SEC',
            'Label' => 'Up To Full Area Image|Up To A0 Display',
        ),
        '4SED' => array(
            'Id' => '4SED',
            'Label' => 'Up To 1/2 Screen Image|Up To 15 Inch Screen',
        ),
        '4SEE' => array(
            'Id' => '4SEE',
            'Label' => 'Up To 42 x 60 cm Image|Any Size Media',
        ),
        '4SEF' => array(
            'Id' => '4SEF',
            'Label' => 'Up To 1/4 Area Image|Up To A1 Media',
        ),
        '4SEG' => array(
            'Id' => '4SEG',
            'Label' => 'Up To Full Area Image|Up To  300 x 600 Pixels Ad',
        ),
        '4SEH' => array(
            'Id' => '4SEH',
            'Label' => 'Up To 1/4 Area Image|Up To 43 x 126 Inch Display',
        ),
        '4SEI' => array(
            'Id' => '4SEI',
            'Label' => 'Up To 1/4 Area Image|Up To B0 Display',
        ),
        '4SEJ' => array(
            'Id' => '4SEJ',
            'Label' => 'Up To 1/2 Page Image|Any Size Page',
        ),
        '4SEK' => array(
            'Id' => '4SEK',
            'Label' => 'Up To 1/4 Screen Image|Up To 63 Inch Screen',
        ),
        '4SEL' => array(
            'Id' => '4SEL',
            'Label' => 'Up To Full Area Image|Up To A0 Media',
        ),
        '4SEM' => array(
            'Id' => '4SEM',
            'Label' => 'Up To 1/4 Screen Image|Up To 100 Diagonal Foot Screen',
        ),
        '4SEN' => array(
            'Id' => '4SEN',
            'Label' => 'Up To 1/2 Area Image|Up To 16 Sheet Display',
        ),
        '4SEP' => array(
            'Id' => '4SEP',
            'Label' => 'Up To 1/2 Area Image|Up To 83 x 135 Inch Display',
        ),
        '4SEQ' => array(
            'Id' => '4SEQ',
            'Label' => 'Up To 5 x 5 Inch Image|Up To 40 x 40 Inch Media',
        ),
        '4SER' => array(
            'Id' => '4SER',
            'Label' => 'Up To 1/2 Area Image|Up To 12 Sheet Display',
        ),
        '4SES' => array(
            'Id' => '4SES',
            'Label' => 'Up To Full Screen Image|Any Size Screen',
        ),
        '4SET' => array(
            'Id' => '4SET',
            'Label' => 'Up To Full Area Image|Up To B0 Display',
        ),
        '4SEU' => array(
            'Id' => '4SEU',
            'Label' => 'Up To 1/3 Page Image|Any Size Page',
        ),
        '4SEV' => array(
            'Id' => '4SEV',
            'Label' => 'Up To 21 x 30 cm Image|Any Size Media',
        ),
        '4SEW' => array(
            'Id' => '4SEW',
            'Label' => 'Up To 1/4 Area Image|Up To 48 x 71 Inch Display',
        ),
        '4SEX' => array(
            'Id' => '4SEX',
            'Label' => 'Up To 1/2 Area Image|Up To 46 x 60 Inch Media',
        ),
        '4SEY' => array(
            'Id' => '4SEY',
            'Label' => 'Up To 1/4 Area Image|Up To 10,000 Square Foot Display',
        ),
        '4SEZ' => array(
            'Id' => '4SEZ',
            'Label' => 'Up To 16 x 20 Inch Image|Any Size Media',
        ),
        '4SFA' => array(
            'Id' => '4SFA',
            'Label' => 'Up To 1/4 Area Image|Any Size Item',
        ),
        '4SFB' => array(
            'Id' => '4SFB',
            'Label' => 'Up To 1/4 Area Image|Up To 138 x 53 Inch Media',
        ),
        '4SFC' => array(
            'Id' => '4SFC',
            'Label' => 'Up To 15 x 21 cm Image|Any Size Media',
        ),
        '4SFD' => array(
            'Id' => '4SFD',
            'Label' => 'Up To Full Area Image|Up To 30 Sheet Display',
        ),
        '4SFE' => array(
            'Id' => '4SFE',
            'Label' => 'Up To 1/4 Area Image|Any Size Display',
        ),
        '4SFF' => array(
            'Id' => '4SFF',
            'Label' => 'Up To 1/4 Area Image|Any Size Media',
        ),
        '4SFG' => array(
            'Id' => '4SFG',
            'Label' => 'Up To 4 Page Image|Any Size Pages',
        ),
        '4SFH' => array(
            'Id' => '4SFH',
            'Label' => 'Up To 5 x 7 Inch Image|Any Size Media',
        ),
        '4SFI' => array(
            'Id' => '4SFI',
            'Label' => 'Up To 2 Page Image|Any Size Pages',
        ),
        '4SFJ' => array(
            'Id' => '4SFJ',
            'Label' => 'Up To 2 Page Image|Any Size Pages',
        ),
        '4SFK' => array(
            'Id' => '4SFK',
            'Label' => 'Up To 1/4 Area Image|Up To 30 x 40 Inch Media',
        ),
        '4SFL' => array(
            'Id' => '4SFL',
            'Label' => 'Up To Full Area Image|Up To 4 Sheet Display',
        ),
        '4SFM' => array(
            'Id' => '4SFM',
            'Label' => 'Up To Full Area Image|Up To 11 x 36 Foot Display',
        ),
        '4SFN' => array(
            'Id' => '4SFN',
            'Label' => 'Up To 1/4 Area Image|Up To 25 x 13 Inch Media',
        ),
        '4SFP' => array(
            'Id' => '4SFP',
            'Label' => 'Up To 11 x 14 Inch Image|Any Size Media',
        ),
        '4SFQ' => array(
            'Id' => '4SFQ',
            'Label' => 'Up To 30 x 42 cm Image|Any Size Media',
        ),
        '4SFR' => array(
            'Id' => '4SFR',
            'Label' => 'Up To 2 Page Image|Any Size Ad',
        ),
        '4SFS' => array(
            'Id' => '4SFS',
            'Label' => 'Up To Full Area Image|Up To 8 Sheet Display',
        ),
        '4SFT' => array(
            'Id' => '4SFT',
            'Label' => 'Up To 1/2 Area Image|Up To Full Screen Ad',
        ),
        '4SFU' => array(
            'Id' => '4SFU',
            'Label' => 'Up To Full Area Image|Up To 48 Sheet Display',
        ),
        '4SFV' => array(
            'Id' => '4SFV',
            'Label' => 'Up To 1/2 Screen Image|Up To 21 Inch Screen',
        ),
        '4SFW' => array(
            'Id' => '4SFW',
            'Label' => 'Up To Full Area Image|Up To 48 x 71 Inch Display',
        ),
        '4SFX' => array(
            'Id' => '4SFX',
            'Label' => 'Up To 8 x 10 Inch Image|Any Size Media',
        ),
        '4SFZ' => array(
            'Id' => '4SFZ',
            'Label' => 'Up To 1/2 Area Image|Up To 27 x 141 Inch Display',
        ),
        '4SGA' => array(
            'Id' => '4SGA',
            'Label' => 'Up To Full Area Image|Up To 27 x 141 Inch Display',
        ),
        '4SGB' => array(
            'Id' => '4SGB',
            'Label' => 'Up To 1/2 Area Image|Up To 30 x 40 Inch Media',
        ),
        '4SGC' => array(
            'Id' => '4SGC',
            'Label' => 'Up To 1/2 Screen Image|Any Size Screen',
        ),
        '4SGD' => array(
            'Id' => '4SGD',
            'Label' => 'Up To Full Area Image|Up To 468 x 60 Pixels Ad',
        ),
        '4SGE' => array(
            'Id' => '4SGE',
            'Label' => 'Up To Full Area Image|Up To 14 x 48 Foot Display',
        ),
        '4SGF' => array(
            'Id' => '4SGF',
            'Label' => 'Up To 1/4 Page Image|Up To 1/4 Page Ad',
        ),
        '4SGG' => array(
            'Id' => '4SGG',
            'Label' => 'Up To Full Area Image|Up To 46 x 60 Inch Media',
        ),
        '4SGH' => array(
            'Id' => '4SGH',
            'Label' => 'Up To 1/4 Screen Image|Any Size Screen',
        ),
        '4SGI' => array(
            'Id' => '4SGI',
            'Label' => 'Up To Full Area Image|Up To 24 x 30 Inch Display',
        ),
        '4SGJ' => array(
            'Id' => '4SGJ',
            'Label' => 'Up To 1/2 Screen Image|Up To 32 Inch Screen',
        ),
        '4SGK' => array(
            'Id' => '4SGK',
            'Label' => 'Up To Full Area Image|Up To 27 x 85 Inch Display',
        ),
        '4SGL' => array(
            'Id' => '4SGL',
            'Label' => 'Up To Full Page Image|Up To 2 Page Ad',
        ),
        '4SGM' => array(
            'Id' => '4SGM',
            'Label' => 'Up To Full Page Image|Any Size Ad',
        ),
        '4SGN' => array(
            'Id' => '4SGN',
            'Label' => 'Up To Full Area Image|Up To 43 x 126 Inch Display',
        ),
        '4SGP' => array(
            'Id' => '4SGP',
            'Label' => 'Any Size Image|Any Size Media',
        ),
        '4SGQ' => array(
            'Id' => '4SGQ',
            'Label' => 'Up To 1/2 Area Image|Up To 48 Sheet Display',
        ),
        '4SGR' => array(
            'Id' => '4SGR',
            'Label' => 'Up To Full Area Image|Up To B1 Display',
        ),
        '4SGS' => array(
            'Id' => '4SGS',
            'Label' => 'Up To 1/2 Area Image|Up To 1,200 Square Foot Display',
        ),
        '4SGT' => array(
            'Id' => '4SGT',
            'Label' => 'Up To 1/2 Area Image|Up To B0 Media',
        ),
        '4SGU' => array(
            'Id' => '4SGU',
            'Label' => 'Up To 1/4 Area Image|Up To 14 x 48 Foot Display',
        ),
        '4SGV' => array(
            'Id' => '4SGV',
            'Label' => 'Up To 1/2 Area Image|Up To 25 x 13 Inch Media',
        ),
        '4SGW' => array(
            'Id' => '4SGW',
            'Label' => 'Up To 1/2 Page Image|Any Size Page',
        ),
        '4SGX' => array(
            'Id' => '4SGX',
            'Label' => 'Up To Full Area Image|Up To 4,800 Square Foot Display',
        ),
        '4SGY' => array(
            'Id' => '4SGY',
            'Label' => 'Up To 1/2 Screen Image|Up To 30 Diagonal Foot Screen',
        ),
        '4SGZ' => array(
            'Id' => '4SGZ',
            'Label' => 'Up To 3 x 4.5 Inch Image|Any Size Media',
        ),
        '4SHA' => array(
            'Id' => '4SHA',
            'Label' => 'Up To 1/4 Area Image|Up To 10 x 40 Foot Display',
        ),
        '4SIB' => array(
            'Id' => '4SIB',
            'Label' => 'Up To 1/4 Area Image|Up To 69 x 48 Inch Display',
        ),
        '4SIC' => array(
            'Id' => '4SIC',
            'Label' => 'Up To 1/2 Area Image|Up To 27 x 85 Inch Display',
        ),
        '4SID' => array(
            'Id' => '4SID',
            'Label' => 'Up To Full Area Image|Up To 30 x 240 Inch Display',
        ),
        '4SIE' => array(
            'Id' => '4SIE',
            'Label' => 'Up To 1/4 Screen Image|Up To 30 Diagonal Foot Screen',
        ),
        '4SIF' => array(
            'Id' => '4SIF',
            'Label' => 'Up To Full Area Image|Up To 12 Sheet Display',
        ),
        '4SIG' => array(
            'Id' => '4SIG',
            'Label' => 'Up To 18 x 25 cm Image|Any Size Media',
        ),
        '4SIH' => array(
            'Id' => '4SIH',
            'Label' => 'Up To 1/4 Area Image|Up To 24 x 30 Inch Display',
        ),
        '4SII' => array(
            'Id' => '4SII',
            'Label' => 'Up To 1/2 Area Image|Up To 468 x 60 Pixels Ad',
        ),
        '4SIJ' => array(
            'Id' => '4SIJ',
            'Label' => 'Up To 1/2 Screen Image|Up To 10 Diagonal Foot Screen',
        ),
        '4SIK' => array(
            'Id' => '4SIK',
            'Label' => 'Up To 1/4 Area Image|Up To 24 x 36 Inch Media',
        ),
        '4SIL' => array(
            'Id' => '4SIL',
            'Label' => 'Up To Full Area Image|Up To 60 x 40 Inch Display',
        ),
        '4SIM' => array(
            'Id' => '4SIM',
            'Label' => 'Up To 1/4 Area Image|Up To 12 x 24 Foot Media',
        ),
        '4SIN' => array(
            'Id' => '4SIN',
            'Label' => 'Any Size Image|Any Size Media',
        ),
        '4SIP' => array(
            'Id' => '4SIP',
            'Label' => 'Up To 1/4 Area Image|Up To B0 Media',
        ),
        '4SIQ' => array(
            'Id' => '4SIQ',
            'Label' => 'Up To Full Area Image|Up To 24 x 36 Inch Media',
        ),
        '4SIR' => array(
            'Id' => '4SIR',
            'Label' => 'Up To 1/4 Area Image|Up To 26 x 53 Inch Media',
        ),
        '4SIS' => array(
            'Id' => '4SIS',
            'Label' => 'Up To Full Area Image|Up To 16 Sheet Display',
        ),
        '4SIT' => array(
            'Id' => '4SIT',
            'Label' => 'Up To Full Area Image|Up To 2,400 Square Foot Display',
        ),
        '4SIU' => array(
            'Id' => '4SIU',
            'Label' => 'Up To 1/4 Area Image|Up To Full Screen Ad',
        ),
        '4SIV' => array(
            'Id' => '4SIV',
            'Label' => 'Up To 10 x 10 Inch Image|Up To 40 x 40 Inch Media',
        ),
        '4SIW' => array(
            'Id' => '4SIW',
            'Label' => 'Any Size Image|Any Size Pages',
        ),
        '4SIX' => array(
            'Id' => '4SIX',
            'Label' => 'Up To 1/4 Area Image|Up To 11 x 36 Foot Display',
        ),
        '4SIY' => array(
            'Id' => '4SIY',
            'Label' => 'Up To 1/4 Page Image|Up To Full Page Ad',
        ),
        '4SIZ' => array(
            'Id' => '4SIZ',
            'Label' => 'Up To 1/2 Page Image|Up To Full Page Media',
        ),
        '4SJA' => array(
            'Id' => '4SJA',
            'Label' => 'Up To 1/16 Page|Any Size Page',
        ),
        '4SJB' => array(
            'Id' => '4SJB',
            'Label' => 'Up To 1/4 Page Image|Any Size Page',
        ),
        '4SJC' => array(
            'Id' => '4SJC',
            'Label' => 'Up To 100 x 142 cm Image|Any Size Media',
        ),
        '4SJD' => array(
            'Id' => '4SJD',
            'Label' => 'Up To 21 x 30 cm Image|Any Size Media',
        ),
        '4SJE' => array(
            'Id' => '4SJE',
            'Label' => 'Up To 38 x 50 Inch Image|Any Size Media',
        ),
        '4SJF' => array(
            'Id' => '4SJF',
            'Label' => 'Up To Full Screen Image|Up To 63 Inch Screen',
        ),
        '4SJG' => array(
            'Id' => '4SJG',
            'Label' => 'Up To 1/4 Area Image|Up To A0 Media',
        ),
        '4SJI' => array(
            'Id' => '4SJI',
            'Label' => 'Up To 1/4 Area Image|Up To 4 x 8 Foot Media',
        ),
        '4SJJ' => array(
            'Id' => '4SJJ',
            'Label' => 'Up To 25 x 36 cm Image|Any Size Media',
        ),
        '4SJK' => array(
            'Id' => '4SJK',
            'Label' => 'Any Size Image|Up To Full Card',
        ),
        '4SJL' => array(
            'Id' => '4SJL',
            'Label' => 'Up To 1/4 Area Image|Up To 30 x 240 Inch Display',
        ),
        '4SJM' => array(
            'Id' => '4SJM',
            'Label' => 'Up To 1/2 Area Image|Up To Full Area Media',
        ),
        '4SJN' => array(
            'Id' => '4SJN',
            'Label' => 'Up To Full Area Image|Up To 25 x 13 Inch Media',
        ),
        '4SJP' => array(
            'Id' => '4SJP',
            'Label' => 'Up To 1/4 Area Image|Up To 60 x 40 Inch Display',
        ),
        '4SJQ' => array(
            'Id' => '4SJQ',
            'Label' => 'Up To 1/4 Area Image|Up To 4,800 Square Foot Display',
        ),
        '4SJR' => array(
            'Id' => '4SJR',
            'Label' => 'Up To 1/2 Area Image|Up To 50 x 24 Inch Media',
        ),
        '4SJS' => array(
            'Id' => '4SJS',
            'Label' => 'Up To 1/8 Page Image|Any Size Page',
        ),
        '4SJT' => array(
            'Id' => '4SJT',
            'Label' => 'Up To Full Area Image|Up To 180 x 150 Pixels Ad',
        ),
        '4SJU' => array(
            'Id' => '4SJU',
            'Label' => 'Up To 2 Page Image|Any Size Pages',
        ),
        '4SJV' => array(
            'Id' => '4SJV',
            'Label' => 'Up To Full Page Image|Any Size Page',
        ),
        '4SJW' => array(
            'Id' => '4SJW',
            'Label' => 'Up To 40 x 40 cm Image|Up To 100 x 100 cm Media',
        ),
        '4SJX' => array(
            'Id' => '4SJX',
            'Label' => 'Up To 1/4 Area Image|Up To B0 Display',
        ),
        '4SJY' => array(
            'Id' => '4SJY',
            'Label' => 'Up To Full Screen Image|Up To 30 Diagonal Foot Screen',
        ),
        '4SJZ' => array(
            'Id' => '4SJZ',
            'Label' => 'Up To 3 Page Image|Any Size Pages',
        ),
        '4SKA' => array(
            'Id' => '4SKA',
            'Label' => 'Up To 1/4 Area Image|Up To 20 x 30 Inch Media',
        ),
        '4SKB' => array(
            'Id' => '4SKB',
            'Label' => 'Up To 11 x 14 Inch Image|Any Size Media',
        ),
        '4SKC' => array(
            'Id' => '4SKC',
            'Label' => 'Up To Full Screen Image|Up To 100 Diagonal Foot Screen',
        ),
        '4SKD' => array(
            'Id' => '4SKD',
            'Label' => 'Up To Full Page Image|Any Size Page',
        ),
        '4SKE' => array(
            'Id' => '4SKE',
            'Label' => 'Up To 1/8 Page Image|Any Size Page',
        ),
        '4SKF' => array(
            'Id' => '4SKF',
            'Label' => 'Up To 1/4 Area Image|Up To 672 Square Foot Display',
        ),
        '4SKG' => array(
            'Id' => '4SKG',
            'Label' => 'Any Size Image|Any Size Screen',
        ),
        '4SKH' => array(
            'Id' => '4SKH',
            'Label' => 'Up To Full Area Image|Up To 50 x 24 Inch Media',
        ),
        '4SKI' => array(
            'Id' => '4SKI',
            'Label' => 'Up To 30 x 30 cm Image|Up To 100 x 100 cm Media',
        ),
        '4SKJ' => array(
            'Id' => '4SKJ',
            'Label' => 'Up To 1/2 Area Image|Up To A1 Media',
        ),
        '4SKK' => array(
            'Id' => '4SKK',
            'Label' => 'Up To Full Area Image|Up To B0 Display',
        ),
        '4SKL' => array(
            'Id' => '4SKL',
            'Label' => 'Up To 1/2 Area Image|Up To 26 x 53 Inch Media',
        ),
        '4SKM' => array(
            'Id' => '4SKM',
            'Label' => 'Up To Full Area Image|Up To 26 x 53 Inch Media',
        ),
        '4SKN' => array(
            'Id' => '4SKN',
            'Label' => 'Up To Full Area Image|Up To 20 x 30 Inch Media',
        ),
        '4SKP' => array(
            'Id' => '4SKP',
            'Label' => 'Any Size Image|Any Size Ad',
        ),
        '4SKQ' => array(
            'Id' => '4SKQ',
            'Label' => 'Up To 1/4 Area Image|Up To 180 x 150 Pixels Ad',
        ),
        '4SKR' => array(
            'Id' => '4SKR',
            'Label' => 'Up To 1/4 Area Image|Up To 1,200 Square Foot Display',
        ),
        '4SKS' => array(
            'Id' => '4SKS',
            'Label' => 'Up To 1/2 Area Image|Up To 672 Square Foot Display',
        ),
        '4SKT' => array(
            'Id' => '4SKT',
            'Label' => 'Up To 1/4 Area Image|Up To 43 x 62 Inch Display',
        ),
        '4SKU' => array(
            'Id' => '4SKU',
            'Label' => 'Up To 1/2 Area Image|Up To 20 x 30 Inch Media',
        ),
        '4SKV' => array(
            'Id' => '4SKV',
            'Label' => 'Up To Full Area Image|Up To 10 x 40 Foot Display',
        ),
        '4SKW' => array(
            'Id' => '4SKW',
            'Label' => 'Up To 1/4 Area Image|Up To 30 x 40 Inch Display',
        ),
        '4SKX' => array(
            'Id' => '4SKX',
            'Label' => 'Up To 1/4 Area Image|Up To 96 Sheet Display',
        ),
        '4SKY' => array(
            'Id' => '4SKY',
            'Label' => 'Up To 1/2 Area Image|Up To 48 x 71 Inch Display',
        ),
        '4SKZ' => array(
            'Id' => '4SKZ',
            'Label' => 'Up To Full Area Image|Up To Full Area Media',
        ),
        '4SLA' => array(
            'Id' => '4SLA',
            'Label' => 'Any Size Image|Any Size Pages',
        ),
        '4SLC' => array(
            'Id' => '4SLC',
            'Label' => 'Up To 1/4 Area Image|Up To 50 x 24 Inch Media',
        ),
        '4SLD' => array(
            'Id' => '4SLD',
            'Label' => 'Up To 1/2 Page Image|Any Size Page',
        ),
        '4SLE' => array(
            'Id' => '4SLE',
            'Label' => 'Up To Full Screen Image|Up To 10 Diagonal Foot Screen',
        ),
        '4SLF' => array(
            'Id' => '4SLF',
            'Label' => 'Up To 8 x 10 Inch Image|Any Size Media',
        ),
        '4SLG' => array(
            'Id' => '4SLG',
            'Label' => 'Up To Full Area Image|Up To 4 x 8 Foot Media',
        ),
        '4SLH' => array(
            'Id' => '4SLH',
            'Label' => 'Up To 30 x 42 cm Image|Any Size Media',
        ),
        '4SLI' => array(
            'Id' => '4SLI',
            'Label' => 'Up To 1/2 Area Image|Up To 10 x 8 Foot Media',
        ),
        '4SLJ' => array(
            'Id' => '4SLJ',
            'Label' => 'Up To 1/4 Area Image|Up To 12 Sheet Display',
        ),
        '4SLK' => array(
            'Id' => '4SLK',
            'Label' => 'Up To 1/4 Area Image|Up To 2,400 Square Foot Display',
        ),
        '4SLL' => array(
            'Id' => '4SLL',
            'Label' => 'Up To 1/4 Area Image|Up To A0 Display',
        ),
        '4SLM' => array(
            'Id' => '4SLM',
            'Label' => 'Up To 1/2 Area Image|Up To  300 x 600 Pixels Ad',
        ),
        '4SLN' => array(
            'Id' => '4SLN',
            'Label' => 'Up To Full Area Image|Up To 672 Square Foot Display',
        ),
        '4SLP' => array(
            'Id' => '4SLP',
            'Label' => 'Up To 1/2 Area Image|Up To 30 Sheet Display',
        ),
        '4SLQ' => array(
            'Id' => '4SLQ',
            'Label' => 'Up To 1/4 Area Image|Up To B1 Display',
        ),
        '4SLR' => array(
            'Id' => '4SLR',
            'Label' => 'Up To Full Area Image|Any Size Item',
        ),
        '4SLS' => array(
            'Id' => '4SLS',
            'Label' => 'Up To 1/2 Area Image|Up To 12 x 24 Foot Media',
        ),
        '4SLT' => array(
            'Id' => '4SLT',
            'Label' => 'Up To Full Area Image|Up To 30 x 40 Inch Media',
        ),
        '4SLU' => array(
            'Id' => '4SLU',
            'Label' => 'Up To Full Area Image|Up To B1 Media',
        ),
        '4SLV' => array(
            'Id' => '4SLV',
            'Label' => 'Up To 1/4 Page Image|Any Size Page',
        ),
        '4SLW' => array(
            'Id' => '4SLW',
            'Label' => 'Up To 1/4 Page Image|Up To Full Page Media',
        ),
        '4SLX' => array(
            'Id' => '4SLX',
            'Label' => 'Up To 1/2 Area Image|Up To B0 Display',
        ),
        '4SLY' => array(
            'Id' => '4SLY',
            'Label' => 'Up To 1/2 Page Image|Up To Full Page Ad',
        ),
        '4SLZ' => array(
            'Id' => '4SLZ',
            'Label' => 'Up To 1/4 Screen Image|Up To 10 Diagonal Foot Screen',
        ),
        '4SMA' => array(
            'Id' => '4SMA',
            'Label' => 'Up To 1/2 Area Image|Up To 2,400 Square Foot Display',
        ),
        '4SMB' => array(
            'Id' => '4SMB',
            'Label' => 'Up To 2 Page Image|Up To 2 Page Ad',
        ),
        '4SMD' => array(
            'Id' => '4SMD',
            'Label' => 'Up To 25 x 36 cm Image|Any Size Media',
        ),
        '4SME' => array(
            'Id' => '4SME',
            'Label' => 'Up To 13 x 18 cm Image|Any Size Media',
        ),
        '4SMF' => array(
            'Id' => '4SMF',
            'Label' => 'Up To 85 x 119 cm Image|Any Size Media',
        ),
        '4SMG' => array(
            'Id' => '4SMG',
            'Label' => 'Up To 3/4 Page Image|Any Size Page',
        ),
        '4SMH' => array(
            'Id' => '4SMH',
            'Label' => 'Up To 150 x 150 Pixels Image|Any Size Screen',
        ),
        '4SMJ' => array(
            'Id' => '4SMJ',
            'Label' => 'Up To 300 x 600 Pixels Image|Any Size Screen',
        ),
        '4SMK' => array(
            'Id' => '4SMK',
            'Label' => 'Up To 1/2 Area Image|Any Size Media',
        ),
        '4SML' => array(
            'Id' => '4SML',
            'Label' => 'Up To Full Area Image|Any Size Media',
        ),
        '4SMN' => array(
            'Id' => '4SMN',
            'Label' => 'Up To 1/4 Area Image|Any Size Display',
        ),
        '4SMP' => array(
            'Id' => '4SMP',
            'Label' => 'Up To 1/2 Area Image|Any Size Display',
        ),
        '4SMQ' => array(
            'Id' => '4SMQ',
            'Label' => 'Up To Full Area Image|Any Size Display',
        ),
        '4SMR' => array(
            'Id' => '4SMR',
            'Label' => 'Up To 1/4 Area Image|Any Size Display',
        ),
        '4SMS' => array(
            'Id' => '4SMS',
            'Label' => 'Up To 1/4 Screen Image|Any Size Screen',
        ),
        '4SMT' => array(
            'Id' => '4SMT',
            'Label' => 'Up To 1/2 Screen Image|Any Size Screen',
        ),
        '4SMU' => array(
            'Id' => '4SMU',
            'Label' => 'Up To Full Screen Image|Any Size Screen',
        ),
        '4SMV' => array(
            'Id' => '4SMV',
            'Label' => 'Any Size Image|Any Size Media',
        ),
        '4SMW' => array(
            'Id' => '4SMW',
            'Label' => 'Up To Full Area Image|Any Size Display',
        ),
        '4SMX' => array(
            'Id' => '4SMX',
            'Label' => 'Up To 1/4 Area Image|Any Size Media',
        ),
        '4SMY' => array(
            'Id' => '4SMY',
            'Label' => 'Up To 1/2 Area Image|Any Size Media',
        ),
        '4SMZ' => array(
            'Id' => '4SMZ',
            'Label' => 'Up To Full Area Image|Any Size Media',
        ),
        '4SNG' => array(
            'Id' => '4SNG',
            'Label' => 'Up To 1/2 Area Image|Any Size Display',
        ),
        '4SNO' => array(
            'Id' => '4SNO',
            'Label' => 'Up To Full Area Image|Any Size Display',
        ),
        '4SUL' => array(
            'Id' => '4SUL',
            'Label' => 'Any Sizes',
        ),
        '4SXX' => array(
            'Id' => '4SXX',
            'Label' => 'Not Applicable or None',
        ),
        '5VAA' => array(
            'Id' => '5VAA',
            'Label' => 'All Versions',
        ),
        '5VUG' => array(
            'Id' => '5VUG',
            'Label' => 'Single Edition',
        ),
        '5VUK' => array(
            'Id' => '5VUK',
            'Label' => 'Single Hardcover Edition',
        ),
        '5VUL' => array(
            'Id' => '5VUL',
            'Label' => 'Any Versions',
        ),
        '5VUP' => array(
            'Id' => '5VUP',
            'Label' => 'Single Version',
        ),
        '5VUU' => array(
            'Id' => '5VUU',
            'Label' => 'Multiple Hardcover Editions',
        ),
        '5VUY' => array(
            'Id' => '5VUY',
            'Label' => 'Multiple Versions',
        ),
        '5VUZ' => array(
            'Id' => '5VUZ',
            'Label' => 'Single Issue',
        ),
        '5VVB' => array(
            'Id' => '5VVB',
            'Label' => 'Multiple Print Versions',
        ),
        '5VVC' => array(
            'Id' => '5VVC',
            'Label' => 'Single Paperback Edition',
        ),
        '5VVG' => array(
            'Id' => '5VVG',
            'Label' => 'Multiple Editions',
        ),
        '5VVH' => array(
            'Id' => '5VVH',
            'Label' => 'Single Edition in All Binding Formats',
        ),
        '5VVJ' => array(
            'Id' => '5VVJ',
            'Label' => 'Multiple Issues',
        ),
        '5VVK' => array(
            'Id' => '5VVK',
            'Label' => 'Multiple Paperback Editions',
        ),
        '5VVL' => array(
            'Id' => '5VVL',
            'Label' => 'Multiple Editions in All Binding Formats',
        ),
        '5VVM' => array(
            'Id' => '5VVM',
            'Label' => 'Single Print Version',
        ),
        '5VXX' => array(
            'Id' => '5VXX',
            'Label' => 'Not Applicable or None',
        ),
        '6QAA' => array(
            'Id' => '6QAA',
            'Label' => 'Any Quantity',
        ),
        '6QAB' => array(
            'Id' => '6QAB',
            'Label' => 'Up To 10,000|Impressions',
        ),
        '6QAC' => array(
            'Id' => '6QAC',
            'Label' => 'Up To 1,000|Copies',
        ),
        '6QAD' => array(
            'Id' => '6QAD',
            'Label' => 'Up To 10,000|Copies',
        ),
        '6QAE' => array(
            'Id' => '6QAE',
            'Label' => 'Up To 100|Displays',
        ),
        '6QAF' => array(
            'Id' => '6QAF',
            'Label' => 'Up To 50,000|Copies',
        ),
        '6QAG' => array(
            'Id' => '6QAG',
            'Label' => 'Up To 500|Displays',
        ),
        '6QAH' => array(
            'Id' => '6QAH',
            'Label' => 'Up To 100,000|Print Run',
        ),
        '6QAI' => array(
            'Id' => '6QAI',
            'Label' => 'Up To 500|Copies',
        ),
        '6QAK' => array(
            'Id' => '6QAK',
            'Label' => 'Up To 10|Copies',
        ),
        '6QAL' => array(
            'Id' => '6QAL',
            'Label' => 'Up To 10|Displays',
        ),
        '6QAM' => array(
            'Id' => '6QAM',
            'Label' => 'Any Quantity Of|Copies',
        ),
        '6QAN' => array(
            'Id' => '6QAN',
            'Label' => 'Up To 50,000|Total Circulation',
        ),
        '6QAP' => array(
            'Id' => '6QAP',
            'Label' => 'Up To 500|Displays',
        ),
        '6QAQ' => array(
            'Id' => '6QAQ',
            'Label' => 'Up To 5,000|Print Run',
        ),
        '6QAR' => array(
            'Id' => '6QAR',
            'Label' => 'Up To 3 Million|Print Run',
        ),
        '6QAS' => array(
            'Id' => '6QAS',
            'Label' => 'Up To 1,000|Copies',
        ),
        '6QAT' => array(
            'Id' => '6QAT',
            'Label' => 'Up To 5,000|Total Circulation',
        ),
        '6QAU' => array(
            'Id' => '6QAU',
            'Label' => 'One|Print Run',
        ),
        '6QAV' => array(
            'Id' => '6QAV',
            'Label' => 'Up To 5 Million|Total Circulation',
        ),
        '6QAW' => array(
            'Id' => '6QAW',
            'Label' => 'Up To 5|Copies',
        ),
        '6QAX' => array(
            'Id' => '6QAX',
            'Label' => 'Up To 500,000|Displays',
        ),
        '6QAY' => array(
            'Id' => '6QAY',
            'Label' => 'Up To 1,000|Reprints',
        ),
        '6QAZ' => array(
            'Id' => '6QAZ',
            'Label' => 'Up To 100|Print Run',
        ),
        '6QBA' => array(
            'Id' => '6QBA',
            'Label' => 'Up To 1,000|Print Run',
        ),
        '6QBB' => array(
            'Id' => '6QBB',
            'Label' => 'Up To 10,000|Total Circulation',
        ),
        '6QBC' => array(
            'Id' => '6QBC',
            'Label' => 'Up To 2,500|Displays',
        ),
        '6QBD' => array(
            'Id' => '6QBD',
            'Label' => 'Up To 50|Displays',
        ),
        '6QBE' => array(
            'Id' => '6QBE',
            'Label' => 'One|Copy',
        ),
        '6QBF' => array(
            'Id' => '6QBF',
            'Label' => 'Up To 10,000|Reprints',
        ),
        '6QBG' => array(
            'Id' => '6QBG',
            'Label' => 'Up To 250,000|Print Run',
        ),
        '6QBH' => array(
            'Id' => '6QBH',
            'Label' => 'Up To 40,000|Print Run',
        ),
        '6QBI' => array(
            'Id' => '6QBI',
            'Label' => 'Up To 100 Million|Viewers',
        ),
        '6QBK' => array(
            'Id' => '6QBK',
            'Label' => 'Four|Copies',
        ),
        '6QBL' => array(
            'Id' => '6QBL',
            'Label' => 'Five|Copies',
        ),
        '6QBM' => array(
            'Id' => '6QBM',
            'Label' => 'Up To 10 Million|Impressions',
        ),
        '6QBN' => array(
            'Id' => '6QBN',
            'Label' => 'Up To 1 Million|Viewers',
        ),
        '6QBP' => array(
            'Id' => '6QBP',
            'Label' => 'Up To 5|Displays',
        ),
        '6QBQ' => array(
            'Id' => '6QBQ',
            'Label' => 'Any Quantity Of|Viewers',
        ),
        '6QBR' => array(
            'Id' => '6QBR',
            'Label' => 'Up To 100,000|Impressions',
        ),
        '6QBS' => array(
            'Id' => '6QBS',
            'Label' => 'Up To 500|Copies',
        ),
        '6QBT' => array(
            'Id' => '6QBT',
            'Label' => 'Up To 25,000|Copies',
        ),
        '6QBU' => array(
            'Id' => '6QBU',
            'Label' => 'One|Copy',
        ),
        '6QBV' => array(
            'Id' => '6QBV',
            'Label' => 'Up To 25,000|Print Run',
        ),
        '6QBW' => array(
            'Id' => '6QBW',
            'Label' => 'Up To 250,000|Displays',
        ),
        '6QBX' => array(
            'Id' => '6QBX',
            'Label' => 'Up To 1 Million|Copies',
        ),
        '6QBY' => array(
            'Id' => '6QBY',
            'Label' => 'Up To 1,000|Displays',
        ),
        '6QBZ' => array(
            'Id' => '6QBZ',
            'Label' => 'Up To 100,000|Viewers',
        ),
        '6QCA' => array(
            'Id' => '6QCA',
            'Label' => 'Up To 250|Copies',
        ),
        '6QCB' => array(
            'Id' => '6QCB',
            'Label' => 'Up To 100|Reprints',
        ),
        '6QCC' => array(
            'Id' => '6QCC',
            'Label' => 'Any Quantity Of|Viewers',
        ),
        '6QCD' => array(
            'Id' => '6QCD',
            'Label' => 'Up To 1,000|Total Circulation',
        ),
        '6QCE' => array(
            'Id' => '6QCE',
            'Label' => 'Up To 250|Displays',
        ),
        '6QCF' => array(
            'Id' => '6QCF',
            'Label' => 'Three|Copies',
        ),
        '6QCG' => array(
            'Id' => '6QCG',
            'Label' => 'Up To 50,000|Print Run',
        ),
        '6QCH' => array(
            'Id' => '6QCH',
            'Label' => 'One|Copy',
        ),
        '6QCI' => array(
            'Id' => '6QCI',
            'Label' => 'Up To 500,000|Total Circulation',
        ),
        '6QCJ' => array(
            'Id' => '6QCJ',
            'Label' => 'Up To 5 Million|Displays',
        ),
        '6QCK' => array(
            'Id' => '6QCK',
            'Label' => 'Up To 2 Million|Total Circulation',
        ),
        '6QCL' => array(
            'Id' => '6QCL',
            'Label' => 'Up To 3 Million|Total Circulation',
        ),
        '6QCM' => array(
            'Id' => '6QCM',
            'Label' => 'Up To 100|Copies',
        ),
        '6QCN' => array(
            'Id' => '6QCN',
            'Label' => 'Up To 1 Million|Total Circulation',
        ),
        '6QCP' => array(
            'Id' => '6QCP',
            'Label' => 'Up To 10|Copies',
        ),
        '6QCQ' => array(
            'Id' => '6QCQ',
            'Label' => 'Up To 10,000|Copies',
        ),
        '6QCR' => array(
            'Id' => '6QCR',
            'Label' => 'Up To 100|Copies',
        ),
        '6QCS' => array(
            'Id' => '6QCS',
            'Label' => 'Up To 100,000|Total Circulation',
        ),
        '6QCT' => array(
            'Id' => '6QCT',
            'Label' => 'Up To 2 Million|Print Run',
        ),
        '6QCU' => array(
            'Id' => '6QCU',
            'Label' => 'Any Quantity Of|Reprints',
        ),
        '6QCV' => array(
            'Id' => '6QCV',
            'Label' => 'Up To 100,000|Viewers',
        ),
        '6QCW' => array(
            'Id' => '6QCW',
            'Label' => 'Up To 5,000|Print Run',
        ),
        '6QCX' => array(
            'Id' => '6QCX',
            'Label' => 'One|Display',
        ),
        '6QCY' => array(
            'Id' => '6QCY',
            'Label' => 'Up To 1 Million|Impressions',
        ),
        '6QCZ' => array(
            'Id' => '6QCZ',
            'Label' => 'Up To 1 Million|Print Run',
        ),
        '6QDA' => array(
            'Id' => '6QDA',
            'Label' => 'Up To 10|Print Run',
        ),
        '6QDB' => array(
            'Id' => '6QDB',
            'Label' => 'Up To 1 Million|Displays',
        ),
        '6QDC' => array(
            'Id' => '6QDC',
            'Label' => 'Up To 5 Million|Print Run',
        ),
        '6QDD' => array(
            'Id' => '6QDD',
            'Label' => 'Up To 100|Viewers',
        ),
        '6QDE' => array(
            'Id' => '6QDE',
            'Label' => 'Up To 250,000|Print Run',
        ),
        '6QDF' => array(
            'Id' => '6QDF',
            'Label' => 'Up To 1,000|Copies',
        ),
        '6QDG' => array(
            'Id' => '6QDG',
            'Label' => 'Up To 10|Reprints',
        ),
        '6QDH' => array(
            'Id' => '6QDH',
            'Label' => 'Up To 10|Print Run',
        ),
        '6QDI' => array(
            'Id' => '6QDI',
            'Label' => 'One|Display',
        ),
        '6QDJ' => array(
            'Id' => '6QDJ',
            'Label' => 'Up To 500,000|Print Run',
        ),
        '6QDK' => array(
            'Id' => '6QDK',
            'Label' => 'Up To 10 Million|Total Circulation',
        ),
        '6QDL' => array(
            'Id' => '6QDL',
            'Label' => 'Any Quantity Of|Print Run',
        ),
        '6QDM' => array(
            'Id' => '6QDM',
            'Label' => 'Up To 500,000|Copies',
        ),
        '6QDN' => array(
            'Id' => '6QDN',
            'Label' => 'Up To 100,000|Copies',
        ),
        '6QDP' => array(
            'Id' => '6QDP',
            'Label' => 'Up To 50,000|Copies',
        ),
        '6QDQ' => array(
            'Id' => '6QDQ',
            'Label' => 'Up To 100,000|Print Run',
        ),
        '6QDR' => array(
            'Id' => '6QDR',
            'Label' => 'Up To 10|Total Circulation',
        ),
        '6QDS' => array(
            'Id' => '6QDS',
            'Label' => 'Up To 25,000|Displays',
        ),
        '6QDT' => array(
            'Id' => '6QDT',
            'Label' => 'Up To 250,000|Total Circulation',
        ),
        '6QDU' => array(
            'Id' => '6QDU',
            'Label' => 'Up To 10 Million|Print Run',
        ),
        '6QDV' => array(
            'Id' => '6QDV',
            'Label' => 'Up To 50|Copies',
        ),
        '6QDW' => array(
            'Id' => '6QDW',
            'Label' => 'Up To 5,000|Copies',
        ),
        '6QDX' => array(
            'Id' => '6QDX',
            'Label' => 'Up To 25|Displays',
        ),
        '6QDY' => array(
            'Id' => '6QDY',
            'Label' => 'Up To 5|Displays',
        ),
        '6QDZ' => array(
            'Id' => '6QDZ',
            'Label' => 'Up To 100,000|Displays',
        ),
        '6QEA' => array(
            'Id' => '6QEA',
            'Label' => 'Up To 100|Displays',
        ),
        '6QEB' => array(
            'Id' => '6QEB',
            'Label' => 'Up To 10|Copies',
        ),
        '6QEC' => array(
            'Id' => '6QEC',
            'Label' => 'Up To 10,000|Print Run',
        ),
        '6QED' => array(
            'Id' => '6QED',
            'Label' => 'Up To 100,000|Copies',
        ),
        '6QEE' => array(
            'Id' => '6QEE',
            'Label' => 'Up To 10,000|Copies',
        ),
        '6QEF' => array(
            'Id' => '6QEF',
            'Label' => 'Up To 25,000|Copies',
        ),
        '6QEG' => array(
            'Id' => '6QEG',
            'Label' => 'Up To 10,000|Print Run',
        ),
        '6QEH' => array(
            'Id' => '6QEH',
            'Label' => 'One|Print Run',
        ),
        '6QEI' => array(
            'Id' => '6QEI',
            'Label' => 'Any Quantity Of|Displays',
        ),
        '6QEJ' => array(
            'Id' => '6QEJ',
            'Label' => 'Up To 100|Total Circulation',
        ),
        '6QEK' => array(
            'Id' => '6QEK',
            'Label' => 'Up To 2 Million|Displays',
        ),
        '6QEL' => array(
            'Id' => '6QEL',
            'Label' => 'Up To 25|Displays',
        ),
        '6QEM' => array(
            'Id' => '6QEM',
            'Label' => 'Up To 50|Displays',
        ),
        '6QEN' => array(
            'Id' => '6QEN',
            'Label' => 'Up To 500,000|Print Run',
        ),
        '6QEP' => array(
            'Id' => '6QEP',
            'Label' => 'One|Copy',
        ),
        '6QEQ' => array(
            'Id' => '6QEQ',
            'Label' => 'Up To 1,000|Viewers',
        ),
        '6QER' => array(
            'Id' => '6QER',
            'Label' => 'Any Quantity Of|Copies',
        ),
        '6QES' => array(
            'Id' => '6QES',
            'Label' => 'Up To 50,000|Displays',
        ),
        '6QET' => array(
            'Id' => '6QET',
            'Label' => 'Up To 5,000|Displays',
        ),
        '6QEU' => array(
            'Id' => '6QEU',
            'Label' => 'Any Quantity Of|Print Run',
        ),
        '6QEV' => array(
            'Id' => '6QEV',
            'Label' => 'Any Quantity Of|Circulation',
        ),
        '6QEW' => array(
            'Id' => '6QEW',
            'Label' => 'Up To 2,500|Copies',
        ),
        '6QEX' => array(
            'Id' => '6QEX',
            'Label' => 'Two|Copies',
        ),
        '6QEY' => array(
            'Id' => '6QEY',
            'Label' => 'Up To 100|Print Run',
        ),
        '6QEZ' => array(
            'Id' => '6QEZ',
            'Label' => 'Up To 10,000|Viewers',
        ),
        '6QFA' => array(
            'Id' => '6QFA',
            'Label' => 'Up To 3 Million|Displays',
        ),
        '6QFB' => array(
            'Id' => '6QFB',
            'Label' => 'Up To 1,000|Print Run',
        ),
        '6QFC' => array(
            'Id' => '6QFC',
            'Label' => 'Up To 5|Copies',
        ),
        '6QFD' => array(
            'Id' => '6QFD',
            'Label' => 'Up To 25,000|Print Run',
        ),
        '6QFE' => array(
            'Id' => '6QFE',
            'Label' => 'Up To 5,000|Copies',
        ),
        '6QFF' => array(
            'Id' => '6QFF',
            'Label' => 'Up To 10,000|Displays',
        ),
        '6QFG' => array(
            'Id' => '6QFG',
            'Label' => 'Up To 250|Displays',
        ),
        '6QFH' => array(
            'Id' => '6QFH',
            'Label' => 'Up To 10,000|Viewers',
        ),
        '6QFI' => array(
            'Id' => '6QFI',
            'Label' => 'One|Reprint',
        ),
        '6QFJ' => array(
            'Id' => '6QFJ',
            'Label' => 'Up To 10|Displays',
        ),
        '6QFK' => array(
            'Id' => '6QFK',
            'Label' => 'Up To 250,000|Copies',
        ),
        '6QFL' => array(
            'Id' => '6QFL',
            'Label' => 'Up To 100|Copies',
        ),
        '6QFM' => array(
            'Id' => '6QFM',
            'Label' => 'Any Quantity Of|Impressions',
        ),
        '6QFN' => array(
            'Id' => '6QFN',
            'Label' => 'Any Quantity Of|Copies',
        ),
        '6QFP' => array(
            'Id' => '6QFP',
            'Label' => 'Up To 10 Million|Viewers',
        ),
        '6QFQ' => array(
            'Id' => '6QFQ',
            'Label' => 'Any Quantity Of|Displays',
        ),
        '6QFR' => array(
            'Id' => '6QFR',
            'Label' => 'Up To 50,000|Print Run',
        ),
        '6QFS' => array(
            'Id' => '6QFS',
            'Label' => 'Up To 25,000|Total Circulation',
        ),
        '6QFT' => array(
            'Id' => '6QFT',
            'Label' => 'One|Copy',
        ),
        '6QFU' => array(
            'Id' => '6QFU',
            'Label' => 'Up To 25|Total Circulation',
        ),
        '6QFV' => array(
            'Id' => '6QFV',
            'Label' => 'Up To 50|Total Circulation',
        ),
        '6QFW' => array(
            'Id' => '6QFW',
            'Label' => 'Up To 250|Total Circulation',
        ),
        '6QFY' => array(
            'Id' => '6QFY',
            'Label' => 'Up To 500|Total Circulation',
        ),
        '6QFZ' => array(
            'Id' => '6QFZ',
            'Label' => 'Up To 2,500|Total Circulation',
        ),
        '6QGB' => array(
            'Id' => '6QGB',
            'Label' => 'Up To 25 Million|Total Circulation',
        ),
        '6QGC' => array(
            'Id' => '6QGC',
            'Label' => 'Up To 50 Million|Total Circulation',
        ),
        '6QGD' => array(
            'Id' => '6QGD',
            'Label' => 'Up To 25|Copies',
        ),
        '6QGE' => array(
            'Id' => '6QGE',
            'Label' => 'Up To 50|Copies',
        ),
        '6QUL' => array(
            'Id' => '6QUL',
            'Label' => 'Any Quantity',
        ),
        '6QXX' => array(
            'Id' => '6QXX',
            'Label' => 'Not Applicable or None',
        ),
        '7DAA' => array(
            'Id' => '7DAA',
            'Label' => 'In Perpetuity',
        ),
        '7DUL' => array(
            'Id' => '7DUL',
            'Label' => 'Any Durations',
        ),
        '7DUQ' => array(
            'Id' => '7DUQ',
            'Label' => 'Up To 10 Years',
        ),
        '7DUS' => array(
            'Id' => '7DUS',
            'Label' => 'Up To 6 Months',
        ),
        '7DUT' => array(
            'Id' => '7DUT',
            'Label' => 'Up To 10 Years',
        ),
        '7DUV' => array(
            'Id' => '7DUV',
            'Label' => 'Up To 6 Months',
        ),
        '7DUW' => array(
            'Id' => '7DUW',
            'Label' => 'Up To 1 Year',
        ),
        '7DUY' => array(
            'Id' => '7DUY',
            'Label' => 'Up To 3 Years',
        ),
        '7DUZ' => array(
            'Id' => '7DUZ',
            'Label' => 'Up To 5 Years',
        ),
        '7DWB' => array(
            'Id' => '7DWB',
            'Label' => 'Up To 7 Years',
        ),
        '7DWC' => array(
            'Id' => '7DWC',
            'Label' => 'Up To 3 Months',
        ),
        '7DWE' => array(
            'Id' => '7DWE',
            'Label' => 'Up To 2 Years',
        ),
        '7DWG' => array(
            'Id' => '7DWG',
            'Label' => 'Up To 15 Years',
        ),
        '7DWI' => array(
            'Id' => '7DWI',
            'Label' => 'Up To 1 Year',
        ),
        '7DWJ' => array(
            'Id' => '7DWJ',
            'Label' => 'Up To 1 Month',
        ),
        '7DWK' => array(
            'Id' => '7DWK',
            'Label' => 'Up To 2 Years',
        ),
        '7DWL' => array(
            'Id' => '7DWL',
            'Label' => 'Up To 1 Week',
        ),
        '7DWM' => array(
            'Id' => '7DWM',
            'Label' => 'In Perpetuity',
        ),
        '7DWP' => array(
            'Id' => '7DWP',
            'Label' => 'Up To 13 Weeks',
        ),
        '7DWR' => array(
            'Id' => '7DWR',
            'Label' => 'In Perpetuity',
        ),
        '7DWS' => array(
            'Id' => '7DWS',
            'Label' => 'Life Of Publication',
        ),
        '7DWT' => array(
            'Id' => '7DWT',
            'Label' => 'Up To 52 Weeks',
        ),
        '7DWU' => array(
            'Id' => '7DWU',
            'Label' => 'Up To 7 Years',
        ),
        '7DWV' => array(
            'Id' => '7DWV',
            'Label' => 'Up To 5 Years',
        ),
        '7DWW' => array(
            'Id' => '7DWW',
            'Label' => 'Up To 6 Months',
        ),
        '7DWY' => array(
            'Id' => '7DWY',
            'Label' => 'Up To 3 Months',
        ),
        '7DWZ' => array(
            'Id' => '7DWZ',
            'Label' => 'Up To 2 Years',
        ),
        '7DXA' => array(
            'Id' => '7DXA',
            'Label' => 'Full Term Of Copyright',
        ),
        '7DXB' => array(
            'Id' => '7DXB',
            'Label' => 'Up To 1 Week',
        ),
        '7DXC' => array(
            'Id' => '7DXC',
            'Label' => 'Up To 1 Day',
        ),
        '7DXD' => array(
            'Id' => '7DXD',
            'Label' => 'Up To 1 Year',
        ),
        '7DXE' => array(
            'Id' => '7DXE',
            'Label' => 'Up To 1 Year',
        ),
        '7DXF' => array(
            'Id' => '7DXF',
            'Label' => 'Up To 1 Month',
        ),
        '7DXG' => array(
            'Id' => '7DXG',
            'Label' => 'Up To 10 Years',
        ),
        '7DXH' => array(
            'Id' => '7DXH',
            'Label' => 'Life Of Publication',
        ),
        '7DXI' => array(
            'Id' => '7DXI',
            'Label' => 'In Perpetuity',
        ),
        '7DXK' => array(
            'Id' => '7DXK',
            'Label' => 'Up To 1 Month',
        ),
        '7DXL' => array(
            'Id' => '7DXL',
            'Label' => 'Up To 1 Week',
        ),
        '7DXM' => array(
            'Id' => '7DXM',
            'Label' => 'Up To 1 Year',
        ),
        '7DXP' => array(
            'Id' => '7DXP',
            'Label' => 'Up To 3 Years',
        ),
        '7DXQ' => array(
            'Id' => '7DXQ',
            'Label' => 'Up To 1 Year',
        ),
        '7DXR' => array(
            'Id' => '7DXR',
            'Label' => 'Up To 10 Years',
        ),
        '7DXS' => array(
            'Id' => '7DXS',
            'Label' => 'In Perpetuity',
        ),
        '7DXT' => array(
            'Id' => '7DXT',
            'Label' => 'Up To 1 Day',
        ),
        '7DXV' => array(
            'Id' => '7DXV',
            'Label' => 'Up To 15 Years',
        ),
        '7DXW' => array(
            'Id' => '7DXW',
            'Label' => 'Up To 6 Months',
        ),
        '7DXX' => array(
            'Id' => '7DXX',
            'Label' => 'Not Applicable or None',
        ),
        '7DXY' => array(
            'Id' => '7DXY',
            'Label' => 'Up To 15 Years',
        ),
        '7DXZ' => array(
            'Id' => '7DXZ',
            'Label' => 'Up To 3 Months',
        ),
        '7DYA' => array(
            'Id' => '7DYA',
            'Label' => 'Up To 5 Years',
        ),
        '7DYB' => array(
            'Id' => '7DYB',
            'Label' => 'Up To 6 Months',
        ),
        '7DYC' => array(
            'Id' => '7DYC',
            'Label' => 'Up To 10 Years',
        ),
        '7DYD' => array(
            'Id' => '7DYD',
            'Label' => 'Up To 5 Years',
        ),
        '7DYE' => array(
            'Id' => '7DYE',
            'Label' => 'Up To 3 Months',
        ),
        '7DYF' => array(
            'Id' => '7DYF',
            'Label' => 'Up To 1 Day',
        ),
        '7DYG' => array(
            'Id' => '7DYG',
            'Label' => 'Up To 1 Day',
        ),
        '7DYI' => array(
            'Id' => '7DYI',
            'Label' => 'Up To 1 Month',
        ),
        '7DYJ' => array(
            'Id' => '7DYJ',
            'Label' => 'Up To 2 Weeks',
        ),
        '7DYK' => array(
            'Id' => '7DYK',
            'Label' => 'Up To 2 Years',
        ),
        '7DYL' => array(
            'Id' => '7DYL',
            'Label' => 'Up To 3 Years',
        ),
        '7DYM' => array(
            'Id' => '7DYM',
            'Label' => 'Up To 1 Year',
        ),
        '7DYN' => array(
            'Id' => '7DYN',
            'Label' => 'Up To 5 Years',
        ),
        '7DYP' => array(
            'Id' => '7DYP',
            'Label' => 'Up To 1 Week',
        ),
        '7DYQ' => array(
            'Id' => '7DYQ',
            'Label' => 'In Perpetuity',
        ),
        '7DYS' => array(
            'Id' => '7DYS',
            'Label' => 'Life Of Publication',
        ),
        '7DYT' => array(
            'Id' => '7DYT',
            'Label' => 'Up To 26 Weeks',
        ),
        '7DYV' => array(
            'Id' => '7DYV',
            'Label' => 'Life Of Event',
        ),
        '7DYW' => array(
            'Id' => '7DYW',
            'Label' => 'Up To 1 Day',
        ),
        '7DYX' => array(
            'Id' => '7DYX',
            'Label' => 'Up To 6 Months',
        ),
        '7DYY' => array(
            'Id' => '7DYY',
            'Label' => 'Up To 10 Years',
        ),
        '7DYZ' => array(
            'Id' => '7DYZ',
            'Label' => 'Up To 1 Day',
        ),
        '7DZA' => array(
            'Id' => '7DZA',
            'Label' => 'Up To 1 Year',
        ),
        '7DZB' => array(
            'Id' => '7DZB',
            'Label' => 'Up To 3 Years',
        ),
        '7DZD' => array(
            'Id' => '7DZD',
            'Label' => 'Up To 1 Week',
        ),
        '7DZF' => array(
            'Id' => '7DZF',
            'Label' => 'Up To 2 Months',
        ),
        '7DZG' => array(
            'Id' => '7DZG',
            'Label' => 'Up To 1 Year',
        ),
        '7DZH' => array(
            'Id' => '7DZH',
            'Label' => 'Up To 2 Years',
        ),
        '7DZJ' => array(
            'Id' => '7DZJ',
            'Label' => 'Up To 3 Years',
        ),
        '7DZK' => array(
            'Id' => '7DZK',
            'Label' => 'Up To 5 Years',
        ),
        '7DZL' => array(
            'Id' => '7DZL',
            'Label' => 'Up To 10 Years',
        ),
        '7DZM' => array(
            'Id' => '7DZM',
            'Label' => 'Up To 2 Years',
        ),
        '7DZN' => array(
            'Id' => '7DZN',
            'Label' => 'Up To 3 Years',
        ),
        '7DZP' => array(
            'Id' => '7DZP',
            'Label' => 'Up To 2 Years',
        ),
        '8IAA' => array(
            'Id' => '8IAA',
            'Label' => 'All Industries',
        ),
        '8IAD' => array(
            'Id' => '8IAD',
            'Label' => 'Advertising and Marketing',
        ),
        '8IAE' => array(
            'Id' => '8IAE',
            'Label' => 'Arts and Entertainment',
        ),
        '8IAG' => array(
            'Id' => '8IAG',
            'Label' => 'Agriculture, Farming and Horticulture',
        ),
        '8IAL' => array(
            'Id' => '8IAL',
            'Label' => 'Alcohol',
        ),
        '8IAP' => array(
            'Id' => '8IAP',
            'Label' => 'Consumer Appliances and Electronics',
        ),
        '8IAR' => array(
            'Id' => '8IAR',
            'Label' => 'Architecture and Engineering',
        ),
        '8IAT' => array(
            'Id' => '8IAT',
            'Label' => 'Airline Transportation',
        ),
        '8IAU' => array(
            'Id' => '8IAU',
            'Label' => 'Automotive',
        ),
        '8IAV' => array(
            'Id' => '8IAV',
            'Label' => 'Aviation',
        ),
        '8IBA' => array(
            'Id' => '8IBA',
            'Label' => 'Baby and Childcare',
        ),
        '8IBE' => array(
            'Id' => '8IBE',
            'Label' => 'Beauty and Personal Care',
        ),
        '8IBI' => array(
            'Id' => '8IBI',
            'Label' => 'Biotechnology',
        ),
        '8IBR' => array(
            'Id' => '8IBR',
            'Label' => 'Broadcast Media',
        ),
        '8ICC' => array(
            'Id' => '8ICC',
            'Label' => 'Construction and Contracting',
        ),
        '8ICE' => array(
            'Id' => '8ICE',
            'Label' => 'Communications Equipment and Services',
        ),
        '8ICG' => array(
            'Id' => '8ICG',
            'Label' => 'Counseling',
        ),
        '8ICH' => array(
            'Id' => '8ICH',
            'Label' => 'Chemicals',
        ),
        '8ICO' => array(
            'Id' => '8ICO',
            'Label' => 'Business Consulting and Services',
        ),
        '8IEC' => array(
            'Id' => '8IEC',
            'Label' => 'Ecology, Environmental and Conservation',
        ),
        '8IED' => array(
            'Id' => '8IED',
            'Label' => 'Education',
        ),
        '8IEM' => array(
            'Id' => '8IEM',
            'Label' => 'Employment Training and Recruitment',
        ),
        '8IEN' => array(
            'Id' => '8IEN',
            'Label' => 'Energy, Utilities and Fuel',
        ),
        '8IEV' => array(
            'Id' => '8IEV',
            'Label' => 'Events and Conventions',
        ),
        '8IFA' => array(
            'Id' => '8IFA',
            'Label' => 'Fashion',
        ),
        '8IFB' => array(
            'Id' => '8IFB',
            'Label' => 'Food and Beverage Processing',
        ),
        '8IFI' => array(
            'Id' => '8IFI',
            'Label' => 'Financial Services and Banking',
        ),
        '8IFL' => array(
            'Id' => '8IFL',
            'Label' => 'Food and Beverage Retail',
        ),
        '8IFO' => array(
            'Id' => '8IFO',
            'Label' => 'Forestry and Wood Products',
        ),
        '8IFR' => array(
            'Id' => '8IFR',
            'Label' => 'Freight and Warehousing',
        ),
        '8IFS' => array(
            'Id' => '8IFS',
            'Label' => 'Food Services',
        ),
        '8IFU' => array(
            'Id' => '8IFU',
            'Label' => 'Furniture',
        ),
        '8IGA' => array(
            'Id' => '8IGA',
            'Label' => 'Games, Toys and Hobbies',
        ),
        '8IGC' => array(
            'Id' => '8IGC',
            'Label' => 'Greeting Card',
        ),
        '8IGI' => array(
            'Id' => '8IGI',
            'Label' => 'Gaming Industry',
        ),
        '8IGL' => array(
            'Id' => '8IGL',
            'Label' => 'Gardening and Landscaping',
        ),
        '8IGO' => array(
            'Id' => '8IGO',
            'Label' => 'Government and Politics',
        ),
        '8IGR' => array(
            'Id' => '8IGR',
            'Label' => 'Graphic Design',
        ),
        '8IHA' => array(
            'Id' => '8IHA',
            'Label' => 'Household Appliances',
        ),
        '8IHC' => array(
            'Id' => '8IHC',
            'Label' => 'Household Cleaning Products',
        ),
        '8IHH' => array(
            'Id' => '8IHH',
            'Label' => 'Hotels and Hospitality',
        ),
        '8IHI' => array(
            'Id' => '8IHI',
            'Label' => 'Heavy Industry',
        ),
        '8IHO' => array(
            'Id' => '8IHO',
            'Label' => 'Home Improvement',
        ),
        '8IHS' => array(
            'Id' => '8IHS',
            'Label' => 'Computer Hardware, Software and Peripherals',
        ),
        '8IIM' => array(
            'Id' => '8IIM',
            'Label' => 'Industry and Manufacturing',
        ),
        '8IIN' => array(
            'Id' => '8IIN',
            'Label' => 'Insurance',
        ),
        '8IIS' => array(
            'Id' => '8IIS',
            'Label' => 'Internet Services',
        ),
        '8IIT' => array(
            'Id' => '8IIT',
            'Label' => 'Information Technologies',
        ),
        '8ILS' => array(
            'Id' => '8ILS',
            'Label' => 'Legal Services',
        ),
        '8IME' => array(
            'Id' => '8IME',
            'Label' => 'Medical and Healthcare',
        ),
        '8IMM' => array(
            'Id' => '8IMM',
            'Label' => 'Mining and Metals',
        ),
        '8IMS' => array(
            'Id' => '8IMS',
            'Label' => 'Microelectronics and Semiconductors',
        ),
        '8IMU' => array(
            'Id' => '8IMU',
            'Label' => 'Music',
        ),
        '8IMW' => array(
            'Id' => '8IMW',
            'Label' => 'Military and Weapons',
        ),
        '8INP' => array(
            'Id' => '8INP',
            'Label' => 'Not For Profit, Social, Charitable',
        ),
        '8IOG' => array(
            'Id' => '8IOG',
            'Label' => 'Oil and Gas',
        ),
        '8IOI' => array(
            'Id' => '8IOI',
            'Label' => 'Other Industry',
        ),
        '8IOP' => array(
            'Id' => '8IOP',
            'Label' => 'Office Products',
        ),
        '8IPM' => array(
            'Id' => '8IPM',
            'Label' => 'Publishing Media',
        ),
        '8IPO' => array(
            'Id' => '8IPO',
            'Label' => 'Personal Use Only',
        ),
        '8IPP' => array(
            'Id' => '8IPP',
            'Label' => 'Pet Products and Services',
        ),
        '8IPR' => array(
            'Id' => '8IPR',
            'Label' => 'Public Relations',
        ),
        '8IPS' => array(
            'Id' => '8IPS',
            'Label' => 'Pharmaceuticals and Supplements',
        ),
        '8IPT' => array(
            'Id' => '8IPT',
            'Label' => 'Printing and Reprographics',
        ),
        '8IRE' => array(
            'Id' => '8IRE',
            'Label' => 'Real Estate',
        ),
        '8IRM' => array(
            'Id' => '8IRM',
            'Label' => 'Retail Merchandise',
        ),
        '8IRR' => array(
            'Id' => '8IRR',
            'Label' => 'Religion and Religious Services',
        ),
        '8ISC' => array(
            'Id' => '8ISC',
            'Label' => 'Sciences',
        ),
        '8ISF' => array(
            'Id' => '8ISF',
            'Label' => 'Sports, Fitness and Recreation',
        ),
        '8ISH' => array(
            'Id' => '8ISH',
            'Label' => 'Shipping',
        ),
        '8ISM' => array(
            'Id' => '8ISM',
            'Label' => 'Retail Sales and Marketing',
        ),
        '8ISO' => array(
            'Id' => '8ISO',
            'Label' => 'Software',
        ),
        '8ISS' => array(
            'Id' => '8ISS',
            'Label' => 'Safety and Security',
        ),
        '8ITB' => array(
            'Id' => '8ITB',
            'Label' => 'Tobacco',
        ),
        '8ITE' => array(
            'Id' => '8ITE',
            'Label' => 'Telecommunications',
        ),
        '8ITR' => array(
            'Id' => '8ITR',
            'Label' => 'Travel and Tourism',
        ),
        '8ITX' => array(
            'Id' => '8ITX',
            'Label' => 'Textiles and Apparel',
        ),
        '8IUL' => array(
            'Id' => '8IUL',
            'Label' => 'Any Industries',
        ),
        '8IXX' => array(
            'Id' => '8IXX',
            'Label' => 'Not Applicable or None',
        ),
        '8LAA' => array(
            'Id' => '8LAA',
            'Label' => 'All Languages',
        ),
        '8LAF' => array(
            'Id' => '8LAF',
            'Label' => 'Afrikaans',
        ),
        '8LAR' => array(
            'Id' => '8LAR',
            'Label' => 'Arabic',
        ),
        '8LBO' => array(
            'Id' => '8LBO',
            'Label' => 'Bosnian',
        ),
        '8LBU' => array(
            'Id' => '8LBU',
            'Label' => 'Bulgarian',
        ),
        '8LCA' => array(
            'Id' => '8LCA',
            'Label' => 'Chinese-Cantonese',
        ),
        '8LCH' => array(
            'Id' => '8LCH',
            'Label' => 'Chinese-Mandarin',
        ),
        '8LCP' => array(
            'Id' => '8LCP',
            'Label' => 'Chinese-Other',
        ),
        '8LCR' => array(
            'Id' => '8LCR',
            'Label' => 'Croatian',
        ),
        '8LCZ' => array(
            'Id' => '8LCZ',
            'Label' => 'Czech',
        ),
        '8LDA' => array(
            'Id' => '8LDA',
            'Label' => 'Danish',
        ),
        '8LDU' => array(
            'Id' => '8LDU',
            'Label' => 'Dutch',
        ),
        '8LEN' => array(
            'Id' => '8LEN',
            'Label' => 'English',
        ),
        '8LES' => array(
            'Id' => '8LES',
            'Label' => 'Estonian',
        ),
        '8LFI' => array(
            'Id' => '8LFI',
            'Label' => 'Finnish',
        ),
        '8LFR' => array(
            'Id' => '8LFR',
            'Label' => 'French',
        ),
        '8LGE' => array(
            'Id' => '8LGE',
            'Label' => 'German',
        ),
        '8LGR' => array(
            'Id' => '8LGR',
            'Label' => 'Greek',
        ),
        '8LHE' => array(
            'Id' => '8LHE',
            'Label' => 'Hebrew',
        ),
        '8LHI' => array(
            'Id' => '8LHI',
            'Label' => 'Hindi',
        ),
        '8LHU' => array(
            'Id' => '8LHU',
            'Label' => 'Hungarian',
        ),
        '8LIC' => array(
            'Id' => '8LIC',
            'Label' => 'Icelandic',
        ),
        '8LIG' => array(
            'Id' => '8LIG',
            'Label' => 'Irish Gaelic',
        ),
        '8LIN' => array(
            'Id' => '8LIN',
            'Label' => 'Indonesian',
        ),
        '8LIT' => array(
            'Id' => '8LIT',
            'Label' => 'Italian',
        ),
        '8LJA' => array(
            'Id' => '8LJA',
            'Label' => 'Japanese',
        ),
        '8LKO' => array(
            'Id' => '8LKO',
            'Label' => 'Korean',
        ),
        '8LLA' => array(
            'Id' => '8LLA',
            'Label' => 'Latvian',
        ),
        '8LMG' => array(
            'Id' => '8LMG',
            'Label' => 'Mongolian',
        ),
        '8LNO' => array(
            'Id' => '8LNO',
            'Label' => 'Norwegian',
        ),
        '8LOL' => array(
            'Id' => '8LOL',
            'Label' => 'Any One Language',
        ),
        '8LOT' => array(
            'Id' => '8LOT',
            'Label' => 'Other Language',
        ),
        '8LPO' => array(
            'Id' => '8LPO',
            'Label' => 'Polish',
        ),
        '8LPR' => array(
            'Id' => '8LPR',
            'Label' => 'Portuguese',
        ),
        '8LRO' => array(
            'Id' => '8LRO',
            'Label' => 'Romanian',
        ),
        '8LRU' => array(
            'Id' => '8LRU',
            'Label' => 'Russian',
        ),
        '8LSE' => array(
            'Id' => '8LSE',
            'Label' => 'Serbian',
        ),
        '8LSG' => array(
            'Id' => '8LSG',
            'Label' => 'Scottish Gaelic',
        ),
        '8LSH' => array(
            'Id' => '8LSH',
            'Label' => 'Swahili',
        ),
        '8LSI' => array(
            'Id' => '8LSI',
            'Label' => 'Sindhi',
        ),
        '8LSL' => array(
            'Id' => '8LSL',
            'Label' => 'Slovenian',
        ),
        '8LSP' => array(
            'Id' => '8LSP',
            'Label' => 'Spanish',
        ),
        '8LSV' => array(
            'Id' => '8LSV',
            'Label' => 'Slovakian',
        ),
        '8LSW' => array(
            'Id' => '8LSW',
            'Label' => 'Swedish',
        ),
        '8LSZ' => array(
            'Id' => '8LSZ',
            'Label' => 'Swazi',
        ),
        '8LTA' => array(
            'Id' => '8LTA',
            'Label' => 'Tagalog',
        ),
        '8LTH' => array(
            'Id' => '8LTH',
            'Label' => 'Thai',
        ),
        '8LTU' => array(
            'Id' => '8LTU',
            'Label' => 'Turkish',
        ),
        '8LUL' => array(
            'Id' => '8LUL',
            'Label' => 'Any Languages',
        ),
        '8LUR' => array(
            'Id' => '8LUR',
            'Label' => 'Ukrainian',
        ),
        '8LXX' => array(
            'Id' => '8LXX',
            'Label' => 'Not Applicable or None',
        ),
        '8LYI' => array(
            'Id' => '8LYI',
            'Label' => 'Yiddish',
        ),
        '8RAA' => array(
            'Id' => '8RAA',
            'Label' => 'Worldwide',
        ),
        '8RAC' => array(
            'Id' => '8RAC',
            'Label' => 'Latin America and Caribbean|All Latin America and Caribbean',
        ),
        '8RAD' => array(
            'Id' => '8RAD',
            'Label' => 'Europe|Andorra',
        ),
        '8RAE' => array(
            'Id' => '8RAE',
            'Label' => 'Middle East|United Arab Emirates',
        ),
        '8RAF' => array(
            'Id' => '8RAF',
            'Label' => 'Middle East|Afghanistan',
        ),
        '8RAG' => array(
            'Id' => '8RAG',
            'Label' => 'Latin America and Caribbean|Antigua and Barbuda',
        ),
        '8RAH' => array(
            'Id' => '8RAH',
            'Label' => 'Broad International Region|All Spanish Speaking Countries',
        ),
        '8RAI' => array(
            'Id' => '8RAI',
            'Label' => 'Latin America and Caribbean|Anguilla',
        ),
        '8RAJ' => array(
            'Id' => '8RAJ',
            'Label' => 'Africa|All Africa',
        ),
        '8RAK' => array(
            'Id' => '8RAK',
            'Label' => 'Africa|All African Mediterranean Countries',
        ),
        '8RAL' => array(
            'Id' => '8RAL',
            'Label' => 'Europe|Albania',
        ),
        '8RAM' => array(
            'Id' => '8RAM',
            'Label' => 'Europe|Armenia',
        ),
        '8RAN' => array(
            'Id' => '8RAN',
            'Label' => 'Latin America and Caribbean|Netherlands Antilles',
        ),
        '8RAO' => array(
            'Id' => '8RAO',
            'Label' => 'Africa|Angola',
        ),
        '8RAP' => array(
            'Id' => '8RAP',
            'Label' => 'Africa|All Central Africa',
        ),
        '8RAQ' => array(
            'Id' => '8RAQ',
            'Label' => 'Africa|All Eastern Africa',
        ),
        '8RAR' => array(
            'Id' => '8RAR',
            'Label' => 'Latin America and Caribbean|Argentina',
        ),
        '8RAS' => array(
            'Id' => '8RAS',
            'Label' => 'Oceania|American Samoa',
        ),
        '8RAT' => array(
            'Id' => '8RAT',
            'Label' => 'Europe|Austria',
        ),
        '8RAU' => array(
            'Id' => '8RAU',
            'Label' => 'Oceania|Australia',
        ),
        '8RAV' => array(
            'Id' => '8RAV',
            'Label' => 'Asia|Up To 3 States Or Provinces',
        ),
        '8RAW' => array(
            'Id' => '8RAW',
            'Label' => 'Latin America and Caribbean|Aruba',
        ),
        '8RAX' => array(
            'Id' => '8RAX',
            'Label' => 'Europe|Aland Islands',
        ),
        '8RAY' => array(
            'Id' => '8RAY',
            'Label' => 'Asia|China-Northeast',
        ),
        '8RAZ' => array(
            'Id' => '8RAZ',
            'Label' => 'Europe|Azerbaijan',
        ),
        '8RBA' => array(
            'Id' => '8RBA',
            'Label' => 'Europe|Bosnia and Herzegovina',
        ),
        '8RBB' => array(
            'Id' => '8RBB',
            'Label' => 'Latin America and Caribbean|Barbados',
        ),
        '8RBC' => array(
            'Id' => '8RBC',
            'Label' => 'Africa|All Southern Africa',
        ),
        '8RBD' => array(
            'Id' => '8RBD',
            'Label' => 'Asia|Bangladesh',
        ),
        '8RBE' => array(
            'Id' => '8RBE',
            'Label' => 'Europe|Belgium',
        ),
        '8RBF' => array(
            'Id' => '8RBF',
            'Label' => 'Africa|Burkina Faso',
        ),
        '8RBG' => array(
            'Id' => '8RBG',
            'Label' => 'Europe|Bulgaria',
        ),
        '8RBH' => array(
            'Id' => '8RBH',
            'Label' => 'Middle East|Bahrain',
        ),
        '8RBI' => array(
            'Id' => '8RBI',
            'Label' => 'Africa|Burundi',
        ),
        '8RBJ' => array(
            'Id' => '8RBJ',
            'Label' => 'Africa|Benin',
        ),
        '8RBK' => array(
            'Id' => '8RBK',
            'Label' => 'Northern America|Canada-British Columbia',
        ),
        '8RBL' => array(
            'Id' => '8RBL',
            'Label' => 'Africa|All Western Africa',
        ),
        '8RBM' => array(
            'Id' => '8RBM',
            'Label' => 'Northern America|Bermuda',
        ),
        '8RBN' => array(
            'Id' => '8RBN',
            'Label' => 'Asia|Brunei Darussalam',
        ),
        '8RBO' => array(
            'Id' => '8RBO',
            'Label' => 'Latin America and Caribbean|Bolivia',
        ),
        '8RBP' => array(
            'Id' => '8RBP',
            'Label' => 'Africa|Ascension Island',
        ),
        '8RBQ' => array(
            'Id' => '8RBQ',
            'Label' => 'Other Regions|Antarctica',
        ),
        '8RBR' => array(
            'Id' => '8RBR',
            'Label' => 'Latin America and Caribbean|Brazil',
        ),
        '8RBS' => array(
            'Id' => '8RBS',
            'Label' => 'Latin America and Caribbean|Bahamas',
        ),
        '8RBT' => array(
            'Id' => '8RBT',
            'Label' => 'Asia|Bhutan',
        ),
        '8RBW' => array(
            'Id' => '8RBW',
            'Label' => 'Africa|Botswana',
        ),
        '8RBX' => array(
            'Id' => '8RBX',
            'Label' => 'Middle East|Up To 5 States Or Provinces',
        ),
        '8RBY' => array(
            'Id' => '8RBY',
            'Label' => 'Europe|Belarus',
        ),
        '8RBZ' => array(
            'Id' => '8RBZ',
            'Label' => 'Latin America and Caribbean|Belize',
        ),
        '8RCA' => array(
            'Id' => '8RCA',
            'Label' => 'Northern America|Canada',
        ),
        '8RCB' => array(
            'Id' => '8RCB',
            'Label' => 'Other Regions|All Arctic and Arctic Ocean Islands',
        ),
        '8RCC' => array(
            'Id' => '8RCC',
            'Label' => 'Oceania|Cocos, Keeling Islands',
        ),
        '8RCD' => array(
            'Id' => '8RCD',
            'Label' => 'Northern America|Up To 5 States Or Provinces',
        ),
        '8RCE' => array(
            'Id' => '8RCE',
            'Label' => 'Northern America|USA and Canada',
        ),
        '8RCF' => array(
            'Id' => '8RCF',
            'Label' => 'Africa|Central African Republic',
        ),
        '8RCG' => array(
            'Id' => '8RCG',
            'Label' => 'Africa|Congo',
        ),
        '8RCH' => array(
            'Id' => '8RCH',
            'Label' => 'Europe|Switzerland',
        ),
        '8RCI' => array(
            'Id' => '8RCI',
            'Label' => 'Africa|Cote D\'Ivoire',
        ),
        '8RCJ' => array(
            'Id' => '8RCJ',
            'Label' => 'Northern America|Canada-Ontario',
        ),
        '8RCK' => array(
            'Id' => '8RCK',
            'Label' => 'Oceania|Cook Islands',
        ),
        '8RCL' => array(
            'Id' => '8RCL',
            'Label' => 'Latin America and Caribbean|Chile',
        ),
        '8RCM' => array(
            'Id' => '8RCM',
            'Label' => 'Africa|Cameroon',
        ),
        '8RCN' => array(
            'Id' => '8RCN',
            'Label' => 'Asia|All China',
        ),
        '8RCO' => array(
            'Id' => '8RCO',
            'Label' => 'Latin America and Caribbean|Colombia',
        ),
        '8RCP' => array(
            'Id' => '8RCP',
            'Label' => 'Asia|All Eastern Asia',
        ),
        '8RCR' => array(
            'Id' => '8RCR',
            'Label' => 'Latin America and Caribbean|Costa Rica',
        ),
        '8RCS' => array(
            'Id' => '8RCS',
            'Label' => 'Europe|Serbia and Montenegro',
        ),
        '8RCT' => array(
            'Id' => '8RCT',
            'Label' => 'Oceania|All Oceania',
        ),
        '8RCU' => array(
            'Id' => '8RCU',
            'Label' => 'Latin America and Caribbean|Cuba',
        ),
        '8RCV' => array(
            'Id' => '8RCV',
            'Label' => 'Africa|Cape Verde',
        ),
        '8RCX' => array(
            'Id' => '8RCX',
            'Label' => 'Oceania|Christmas Island',
        ),
        '8RCY' => array(
            'Id' => '8RCY',
            'Label' => 'Europe|Cyprus',
        ),
        '8RCZ' => array(
            'Id' => '8RCZ',
            'Label' => 'Europe|Czech Republic',
        ),
        '8RDA' => array(
            'Id' => '8RDA',
            'Label' => 'Asia|Tibet',
        ),
        '8RDB' => array(
            'Id' => '8RDB',
            'Label' => 'Asia|All Asia',
        ),
        '8RDC' => array(
            'Id' => '8RDC',
            'Label' => 'Asia|All Central Asia',
        ),
        '8RDE' => array(
            'Id' => '8RDE',
            'Label' => 'Europe|Germany',
        ),
        '8RDG' => array(
            'Id' => '8RDG',
            'Label' => 'Asia|All Southern Asia',
        ),
        '8RDH' => array(
            'Id' => '8RDH',
            'Label' => 'Asia|All Southeastern Asia',
        ),
        '8RDJ' => array(
            'Id' => '8RDJ',
            'Label' => 'Africa|Djibouti',
        ),
        '8RDK' => array(
            'Id' => '8RDK',
            'Label' => 'Europe|Denmark',
        ),
        '8RDM' => array(
            'Id' => '8RDM',
            'Label' => 'Latin America and Caribbean|Dominica',
        ),
        '8RDO' => array(
            'Id' => '8RDO',
            'Label' => 'Latin America and Caribbean|Dominican Republic',
        ),
        '8RDQ' => array(
            'Id' => '8RDQ',
            'Label' => 'Other Regions|All British Indian Ocean Territories',
        ),
        '8RDR' => array(
            'Id' => '8RDR',
            'Label' => 'Europe|Croatia',
        ),
        '8RDS' => array(
            'Id' => '8RDS',
            'Label' => 'Europe|Faeroe Islands',
        ),
        '8RDT' => array(
            'Id' => '8RDT',
            'Label' => 'Europe|Vatican City State',
        ),
        '8RDU' => array(
            'Id' => '8RDU',
            'Label' => 'Europe|All Europe',
        ),
        '8RDW' => array(
            'Id' => '8RDW',
            'Label' => 'Europe|All Baltic States',
        ),
        '8RDX' => array(
            'Id' => '8RDX',
            'Label' => 'Europe|All Benelux',
        ),
        '8RDY' => array(
            'Id' => '8RDY',
            'Label' => 'Europe|All Caucasian States',
        ),
        '8RDZ' => array(
            'Id' => '8RDZ',
            'Label' => 'Africa|Algeria',
        ),
        '8REA' => array(
            'Id' => '8REA',
            'Label' => 'Europe|All Eastern Europe',
        ),
        '8REB' => array(
            'Id' => '8REB',
            'Label' => 'Europe|All European Mediterranean Countries',
        ),
        '8REC' => array(
            'Id' => '8REC',
            'Label' => 'Latin America and Caribbean|Ecuador',
        ),
        '8RED' => array(
            'Id' => '8RED',
            'Label' => 'Europe|All Northern Europe',
        ),
        '8REE' => array(
            'Id' => '8REE',
            'Label' => 'Europe|Estonia',
        ),
        '8REF' => array(
            'Id' => '8REF',
            'Label' => 'Europe|All Scandinavia',
        ),
        '8REG' => array(
            'Id' => '8REG',
            'Label' => 'Africa|Egypt',
        ),
        '8REH' => array(
            'Id' => '8REH',
            'Label' => 'Africa|Western Sahara',
        ),
        '8REI' => array(
            'Id' => '8REI',
            'Label' => 'Europe|All United Kingdom',
        ),
        '8REJ' => array(
            'Id' => '8REJ',
            'Label' => 'Europe|All Western Europe',
        ),
        '8REK' => array(
            'Id' => '8REK',
            'Label' => 'Broad International Region|Europe, Middle East and Africa',
        ),
        '8REL' => array(
            'Id' => '8REL',
            'Label' => 'Europe|All European Union Countries',
        ),
        '8REM' => array(
            'Id' => '8REM',
            'Label' => 'Europe|England',
        ),
        '8REN' => array(
            'Id' => '8REN',
            'Label' => 'Europe|Scotland',
        ),
        '8RER' => array(
            'Id' => '8RER',
            'Label' => 'Africa|Eritrea',
        ),
        '8RES' => array(
            'Id' => '8RES',
            'Label' => 'Europe|Spain',
        ),
        '8RET' => array(
            'Id' => '8RET',
            'Label' => 'Africa|Ethiopia',
        ),
        '8REU' => array(
            'Id' => '8REU',
            'Label' => 'Other Regions|All French Southern Territories',
        ),
        '8REV' => array(
            'Id' => '8REV',
            'Label' => 'Middle East|Palestinian Authority',
        ),
        '8REX' => array(
            'Id' => '8REX',
            'Label' => 'Middle East|All Middle East',
        ),
        '8REY' => array(
            'Id' => '8REY',
            'Label' => 'Middle East|All Middle Eastern Gulf States',
        ),
        '8RFB' => array(
            'Id' => '8RFB',
            'Label' => 'Other Regions|All Northern Atlantic Ocean Islands',
        ),
        '8RFF' => array(
            'Id' => '8RFF',
            'Label' => 'Oceania|Midway Islands',
        ),
        '8RFH' => array(
            'Id' => '8RFH',
            'Label' => 'Oceania|Rapa Nui, Easter Island',
        ),
        '8RFI' => array(
            'Id' => '8RFI',
            'Label' => 'Europe|Finland',
        ),
        '8RFJ' => array(
            'Id' => '8RFJ',
            'Label' => 'Oceania|Fiji',
        ),
        '8RFK' => array(
            'Id' => '8RFK',
            'Label' => 'Latin America and Caribbean|Falkland Islands, Malvinas',
        ),
        '8RFL' => array(
            'Id' => '8RFL',
            'Label' => 'Oceania|Tahiti',
        ),
        '8RFM' => array(
            'Id' => '8RFM',
            'Label' => 'Oceania|Micronesia',
        ),
        '8RFP' => array(
            'Id' => '8RFP',
            'Label' => 'Oceania|Wallis and Futuna',
        ),
        '8RFQ' => array(
            'Id' => '8RFQ',
            'Label' => 'Latin America and Caribbean|Patagonia',
        ),
        '8RFR' => array(
            'Id' => '8RFR',
            'Label' => 'Europe|France',
        ),
        '8RFS' => array(
            'Id' => '8RFS',
            'Label' => 'Latin America and Caribbean|All South America',
        ),
        '8RFT' => array(
            'Id' => '8RFT',
            'Label' => 'Latin America and Caribbean|All Andean Countries',
        ),
        '8RFU' => array(
            'Id' => '8RFU',
            'Label' => 'Latin America and Caribbean|All Southern Cone',
        ),
        '8RFV' => array(
            'Id' => '8RFV',
            'Label' => 'Latin America and Caribbean|All Amazonia',
        ),
        '8RFW' => array(
            'Id' => '8RFW',
            'Label' => 'Other Regions|All Southern Atlantic Ocean Islands',
        ),
        '8RFX' => array(
            'Id' => '8RFX',
            'Label' => 'Other Regions|All Southern Indian Ocean Islands',
        ),
        '8RFY' => array(
            'Id' => '8RFY',
            'Label' => 'Broad International Region|All Americas',
        ),
        '8RFZ' => array(
            'Id' => '8RFZ',
            'Label' => 'Latin America and Caribbean|All Caribbean',
        ),
        '8RGA' => array(
            'Id' => '8RGA',
            'Label' => 'Africa|Gabon',
        ),
        '8RGC' => array(
            'Id' => '8RGC',
            'Label' => 'Latin America and Caribbean|All Central America',
        ),
        '8RGD' => array(
            'Id' => '8RGD',
            'Label' => 'Latin America and Caribbean|Grenada',
        ),
        '8RGE' => array(
            'Id' => '8RGE',
            'Label' => 'Europe|Georgia',
        ),
        '8RGF' => array(
            'Id' => '8RGF',
            'Label' => 'Latin America and Caribbean|French Guiana',
        ),
        '8RGG' => array(
            'Id' => '8RGG',
            'Label' => 'Europe|Guernsey',
        ),
        '8RGH' => array(
            'Id' => '8RGH',
            'Label' => 'Africa|Ghana',
        ),
        '8RGI' => array(
            'Id' => '8RGI',
            'Label' => 'Europe|Gibraltar',
        ),
        '8RGJ' => array(
            'Id' => '8RGJ',
            'Label' => 'Northern America|All Northern American Countries',
        ),
        '8RGL' => array(
            'Id' => '8RGL',
            'Label' => 'Northern America|Greenland',
        ),
        '8RGM' => array(
            'Id' => '8RGM',
            'Label' => 'Africa|Gambia',
        ),
        '8RGN' => array(
            'Id' => '8RGN',
            'Label' => 'Africa|Guinea',
        ),
        '8RGP' => array(
            'Id' => '8RGP',
            'Label' => 'Latin America and Caribbean|Guadeloupe',
        ),
        '8RGQ' => array(
            'Id' => '8RGQ',
            'Label' => 'Africa|Equatorial Guinea',
        ),
        '8RGR' => array(
            'Id' => '8RGR',
            'Label' => 'Europe|Greece',
        ),
        '8RGT' => array(
            'Id' => '8RGT',
            'Label' => 'Latin America and Caribbean|Guatemala',
        ),
        '8RGU' => array(
            'Id' => '8RGU',
            'Label' => 'Oceania|Guam',
        ),
        '8RGW' => array(
            'Id' => '8RGW',
            'Label' => 'Africa|Guinea-Bissau',
        ),
        '8RGY' => array(
            'Id' => '8RGY',
            'Label' => 'Latin America and Caribbean|Guyana',
        ),
        '8RGZ' => array(
            'Id' => '8RGZ',
            'Label' => 'Latin America and Caribbean|Bequia',
        ),
        '8RHA' => array(
            'Id' => '8RHA',
            'Label' => 'Latin America and Caribbean|Bonaire',
        ),
        '8RHB' => array(
            'Id' => '8RHB',
            'Label' => 'Latin America and Caribbean|British Virgin Islands',
        ),
        '8RHC' => array(
            'Id' => '8RHC',
            'Label' => 'Latin America and Caribbean|Curacao',
        ),
        '8RHD' => array(
            'Id' => '8RHD',
            'Label' => 'Latin America and Caribbean|Saba',
        ),
        '8RHE' => array(
            'Id' => '8RHE',
            'Label' => 'Latin America and Caribbean|Saint Barthelemy',
        ),
        '8RHF' => array(
            'Id' => '8RHF',
            'Label' => 'Latin America and Caribbean|Saint Eustatius',
        ),
        '8RHG' => array(
            'Id' => '8RHG',
            'Label' => 'Latin America and Caribbean|Saint Martin',
        ),
        '8RHH' => array(
            'Id' => '8RHH',
            'Label' => 'Latin America and Caribbean|U.S. Virgin Islands',
        ),
        '8RHJ' => array(
            'Id' => '8RHJ',
            'Label' => 'Northern America|USA-Central',
        ),
        '8RHK' => array(
            'Id' => '8RHK',
            'Label' => 'Asia|Hong Kong',
        ),
        '8RHN' => array(
            'Id' => '8RHN',
            'Label' => 'Latin America and Caribbean|Honduras',
        ),
        '8RHP' => array(
            'Id' => '8RHP',
            'Label' => 'Northern America|USA-Midwest',
        ),
        '8RHQ' => array(
            'Id' => '8RHQ',
            'Label' => 'Northern America|USA',
        ),
        '8RHR' => array(
            'Id' => '8RHR',
            'Label' => 'Northern America|USA-Northeast',
        ),
        '8RHS' => array(
            'Id' => '8RHS',
            'Label' => 'Northern America|USA-Pacific Northwest',
        ),
        '8RHT' => array(
            'Id' => '8RHT',
            'Label' => 'Latin America and Caribbean|Haiti',
        ),
        '8RHU' => array(
            'Id' => '8RHU',
            'Label' => 'Europe|Hungary',
        ),
        '8RHW' => array(
            'Id' => '8RHW',
            'Label' => 'Northern America|USA-Southeast',
        ),
        '8RHX' => array(
            'Id' => '8RHX',
            'Label' => 'Northern America|USA-Southwest',
        ),
        '8RHY' => array(
            'Id' => '8RHY',
            'Label' => 'Northern America|USA-Minor Outlying Islands',
        ),
        '8RIA' => array(
            'Id' => '8RIA',
            'Label' => 'Northern America|USA-West',
        ),
        '8RIB' => array(
            'Id' => '8RIB',
            'Label' => 'Middle East|All Middle Eastern Mediterranean Countries',
        ),
        '8RID' => array(
            'Id' => '8RID',
            'Label' => 'Asia|Indonesia',
        ),
        '8RIE' => array(
            'Id' => '8RIE',
            'Label' => 'Europe|Ireland',
        ),
        '8RIL' => array(
            'Id' => '8RIL',
            'Label' => 'Middle East|Israel',
        ),
        '8RIM' => array(
            'Id' => '8RIM',
            'Label' => 'Europe|Isle Of Man',
        ),
        '8RIN' => array(
            'Id' => '8RIN',
            'Label' => 'Asia|India',
        ),
        '8RIQ' => array(
            'Id' => '8RIQ',
            'Label' => 'Middle East|Iraq',
        ),
        '8RIR' => array(
            'Id' => '8RIR',
            'Label' => 'Middle East|Iran',
        ),
        '8RIS' => array(
            'Id' => '8RIS',
            'Label' => 'Europe|Iceland',
        ),
        '8RIT' => array(
            'Id' => '8RIT',
            'Label' => 'Europe|Italy',
        ),
        '8RJE' => array(
            'Id' => '8RJE',
            'Label' => 'Europe|Jersey',
        ),
        '8RJM' => array(
            'Id' => '8RJM',
            'Label' => 'Latin America and Caribbean|Jamaica',
        ),
        '8RJO' => array(
            'Id' => '8RJO',
            'Label' => 'Middle East|Jordan',
        ),
        '8RJP' => array(
            'Id' => '8RJP',
            'Label' => 'Asia|Japan',
        ),
        '8RKE' => array(
            'Id' => '8RKE',
            'Label' => 'Africa|Kenya',
        ),
        '8RKG' => array(
            'Id' => '8RKG',
            'Label' => 'Asia|Kyrgyzstan',
        ),
        '8RKH' => array(
            'Id' => '8RKH',
            'Label' => 'Asia|Cambodia',
        ),
        '8RKI' => array(
            'Id' => '8RKI',
            'Label' => 'Oceania|Kiribati',
        ),
        '8RKM' => array(
            'Id' => '8RKM',
            'Label' => 'Oceania|Comoros',
        ),
        '8RKN' => array(
            'Id' => '8RKN',
            'Label' => 'Latin America and Caribbean|Saint Kitts and Nevis',
        ),
        '8RKP' => array(
            'Id' => '8RKP',
            'Label' => 'Asia|North Korea',
        ),
        '8RKR' => array(
            'Id' => '8RKR',
            'Label' => 'Asia|South Korea',
        ),
        '8RKW' => array(
            'Id' => '8RKW',
            'Label' => 'Middle East|Kuwait',
        ),
        '8RKY' => array(
            'Id' => '8RKY',
            'Label' => 'Latin America and Caribbean|Cayman Islands',
        ),
        '8RKZ' => array(
            'Id' => '8RKZ',
            'Label' => 'Asia|Kazakhstan',
        ),
        '8RLA' => array(
            'Id' => '8RLA',
            'Label' => 'Asia|Laos',
        ),
        '8RLB' => array(
            'Id' => '8RLB',
            'Label' => 'Middle East|Lebanon',
        ),
        '8RLC' => array(
            'Id' => '8RLC',
            'Label' => 'Latin America and Caribbean|Saint Lucia',
        ),
        '8RLI' => array(
            'Id' => '8RLI',
            'Label' => 'Europe|Liechtenstein',
        ),
        '8RLK' => array(
            'Id' => '8RLK',
            'Label' => 'Oceania|Sri Lanka',
        ),
        '8RLR' => array(
            'Id' => '8RLR',
            'Label' => 'Africa|Liberia',
        ),
        '8RLS' => array(
            'Id' => '8RLS',
            'Label' => 'Africa|Lesotho',
        ),
        '8RLT' => array(
            'Id' => '8RLT',
            'Label' => 'Europe|Lithuania',
        ),
        '8RLU' => array(
            'Id' => '8RLU',
            'Label' => 'Europe|Luxembourg',
        ),
        '8RLV' => array(
            'Id' => '8RLV',
            'Label' => 'Europe|Latvia',
        ),
        '8RLY' => array(
            'Id' => '8RLY',
            'Label' => 'Africa|Libyan Arab Jamahiriya',
        ),
        '8RMA' => array(
            'Id' => '8RMA',
            'Label' => 'Africa|Morocco',
        ),
        '8RMC' => array(
            'Id' => '8RMC',
            'Label' => 'Europe|Monaco',
        ),
        '8RMD' => array(
            'Id' => '8RMD',
            'Label' => 'Europe|Moldova',
        ),
        '8RMG' => array(
            'Id' => '8RMG',
            'Label' => 'Oceania|Madagascar',
        ),
        '8RMH' => array(
            'Id' => '8RMH',
            'Label' => 'Oceania|Marshall Islands',
        ),
        '8RMK' => array(
            'Id' => '8RMK',
            'Label' => 'Europe|Macedonia',
        ),
        '8RML' => array(
            'Id' => '8RML',
            'Label' => 'Africa|Mali',
        ),
        '8RMM' => array(
            'Id' => '8RMM',
            'Label' => 'Asia|Myanmar',
        ),
        '8RMN' => array(
            'Id' => '8RMN',
            'Label' => 'Asia|Mongolia',
        ),
        '8RMO' => array(
            'Id' => '8RMO',
            'Label' => 'Asia|Macao',
        ),
        '8RMP' => array(
            'Id' => '8RMP',
            'Label' => 'Oceania|Northern Mariana Islands',
        ),
        '8RMQ' => array(
            'Id' => '8RMQ',
            'Label' => 'Latin America and Caribbean|Martinique',
        ),
        '8RMR' => array(
            'Id' => '8RMR',
            'Label' => 'Africa|Mauritania',
        ),
        '8RMS' => array(
            'Id' => '8RMS',
            'Label' => 'Latin America and Caribbean|Montserrat',
        ),
        '8RMT' => array(
            'Id' => '8RMT',
            'Label' => 'Europe|Malta',
        ),
        '8RMU' => array(
            'Id' => '8RMU',
            'Label' => 'Oceania|Mauritius',
        ),
        '8RMV' => array(
            'Id' => '8RMV',
            'Label' => 'Asia|Maldives',
        ),
        '8RMW' => array(
            'Id' => '8RMW',
            'Label' => 'Africa|Malawi',
        ),
        '8RMX' => array(
            'Id' => '8RMX',
            'Label' => 'Latin America and Caribbean|Mexico',
        ),
        '8RMY' => array(
            'Id' => '8RMY',
            'Label' => 'Asia|Malaysia',
        ),
        '8RMZ' => array(
            'Id' => '8RMZ',
            'Label' => 'Africa|Mozambique',
        ),
        '8RNA' => array(
            'Id' => '8RNA',
            'Label' => 'Africa|Namibia',
        ),
        '8RNC' => array(
            'Id' => '8RNC',
            'Label' => 'Oceania|New Caledonia',
        ),
        '8RNE' => array(
            'Id' => '8RNE',
            'Label' => 'Africa|Niger',
        ),
        '8RNF' => array(
            'Id' => '8RNF',
            'Label' => 'Oceania|Norfolk Island',
        ),
        '8RNG' => array(
            'Id' => '8RNG',
            'Label' => 'Africa|Nigeria',
        ),
        '8RNI' => array(
            'Id' => '8RNI',
            'Label' => 'Latin America and Caribbean|Nicaragua',
        ),
        '8RNL' => array(
            'Id' => '8RNL',
            'Label' => 'Europe|Netherlands',
        ),
        '8RNO' => array(
            'Id' => '8RNO',
            'Label' => 'Europe|Norway',
        ),
        '8RNP' => array(
            'Id' => '8RNP',
            'Label' => 'Asia|Nepal',
        ),
        '8RNR' => array(
            'Id' => '8RNR',
            'Label' => 'Oceania|Nauru',
        ),
        '8RNU' => array(
            'Id' => '8RNU',
            'Label' => 'Oceania|Niue',
        ),
        '8RNZ' => array(
            'Id' => '8RNZ',
            'Label' => 'Oceania|New Zealand',
        ),
        '8ROM' => array(
            'Id' => '8ROM',
            'Label' => 'Middle East|Oman',
        ),
        '8RPA' => array(
            'Id' => '8RPA',
            'Label' => 'Latin America and Caribbean|Panama',
        ),
        '8RPE' => array(
            'Id' => '8RPE',
            'Label' => 'Latin America and Caribbean|Peru',
        ),
        '8RPF' => array(
            'Id' => '8RPF',
            'Label' => 'Oceania|French Polynesia',
        ),
        '8RPG' => array(
            'Id' => '8RPG',
            'Label' => 'Oceania|Papua New Guinea',
        ),
        '8RPH' => array(
            'Id' => '8RPH',
            'Label' => 'Asia|Philippines',
        ),
        '8RPK' => array(
            'Id' => '8RPK',
            'Label' => 'Asia|Pakistan',
        ),
        '8RPL' => array(
            'Id' => '8RPL',
            'Label' => 'Europe|Poland',
        ),
        '8RPM' => array(
            'Id' => '8RPM',
            'Label' => 'Northern America|Saint Pierre and Miquelon',
        ),
        '8RPN' => array(
            'Id' => '8RPN',
            'Label' => 'Oceania|Pitcairn Islands',
        ),
        '8RPR' => array(
            'Id' => '8RPR',
            'Label' => 'Latin America and Caribbean|Puerto Rico',
        ),
        '8RPT' => array(
            'Id' => '8RPT',
            'Label' => 'Europe|Portugal',
        ),
        '8RPW' => array(
            'Id' => '8RPW',
            'Label' => 'Oceania|Palau',
        ),
        '8RPY' => array(
            'Id' => '8RPY',
            'Label' => 'Latin America and Caribbean|Paraguay',
        ),
        '8RQA' => array(
            'Id' => '8RQA',
            'Label' => 'Middle East|Qatar',
        ),
        '8RQB' => array(
            'Id' => '8RQB',
            'Label' => 'Africa|One Metropolitan Area, Adjoining Cities',
        ),
        '8RQC' => array(
            'Id' => '8RQC',
            'Label' => 'Africa|One Minor City, Up To 250,000 Population',
        ),
        '8RQD' => array(
            'Id' => '8RQD',
            'Label' => 'Asia|One Major City, Over 250,000 Population',
        ),
        '8RQE' => array(
            'Id' => '8RQE',
            'Label' => 'Asia|One Metropolitan Area, Adjoining Cities',
        ),
        '8RQF' => array(
            'Id' => '8RQF',
            'Label' => 'Asia|One Minor City, Up To 250,000 Population',
        ),
        '8RQJ' => array(
            'Id' => '8RQJ',
            'Label' => 'Europe|One Major City, Over 250,000 Population',
        ),
        '8RQK' => array(
            'Id' => '8RQK',
            'Label' => 'Europe|One Metropolitan Area, Adjoining Cities',
        ),
        '8RQL' => array(
            'Id' => '8RQL',
            'Label' => 'Europe|One Minor City, Up To 250,000 Population',
        ),
        '8RQM' => array(
            'Id' => '8RQM',
            'Label' => 'Latin America and Caribbean|One Major City, Over 250,000 Population',
        ),
        '8RQN' => array(
            'Id' => '8RQN',
            'Label' => 'Latin America and Caribbean|One Metropolitan Area, Adjoining Cities',
        ),
        '8RQO' => array(
            'Id' => '8RQO',
            'Label' => 'Latin America and Caribbean|One Minor City, Up To 250,000 Population',
        ),
        '8RQP' => array(
            'Id' => '8RQP',
            'Label' => 'Middle East|One Major City, Over 250,000 Population',
        ),
        '8RQR' => array(
            'Id' => '8RQR',
            'Label' => 'Middle East|One Metropolitan Area, Adjoining Cities',
        ),
        '8RQS' => array(
            'Id' => '8RQS',
            'Label' => 'Middle East|One Minor City, Up To 250,000 Population',
        ),
        '8RQT' => array(
            'Id' => '8RQT',
            'Label' => 'Northern America|One Major City, Over 250,000 Population',
        ),
        '8RQU' => array(
            'Id' => '8RQU',
            'Label' => 'Northern America|One Metropolitan Area, Adjoining Cities',
        ),
        '8RQV' => array(
            'Id' => '8RQV',
            'Label' => 'Northern America|One Minor City, Up To 250,000 Population',
        ),
        '8RQW' => array(
            'Id' => '8RQW',
            'Label' => 'Oceania|One Major City, Over 250,000 Population',
        ),
        '8RQX' => array(
            'Id' => '8RQX',
            'Label' => 'Africa|One Major City, Over 250,000 Population',
        ),
        '8RQY' => array(
            'Id' => '8RQY',
            'Label' => 'Oceania|One Metropolitan Area, Adjoining Cities',
        ),
        '8RQZ' => array(
            'Id' => '8RQZ',
            'Label' => 'Oceania|One Minor City, Up To 250,000 Population',
        ),
        '8RRB' => array(
            'Id' => '8RRB',
            'Label' => 'Africa|One State Or Province',
        ),
        '8RRC' => array(
            'Id' => '8RRC',
            'Label' => 'Asia|One State Or Province',
        ),
        '8RRE' => array(
            'Id' => '8RRE',
            'Label' => 'Africa|Reunion',
        ),
        '8RRF' => array(
            'Id' => '8RRF',
            'Label' => 'Europe|One State Or Province',
        ),
        '8RRG' => array(
            'Id' => '8RRG',
            'Label' => 'Latin America and Caribbean|One State Or Province',
        ),
        '8RRH' => array(
            'Id' => '8RRH',
            'Label' => 'Middle East|One State Or Province',
        ),
        '8RRJ' => array(
            'Id' => '8RRJ',
            'Label' => 'Northern America|One State Or Province',
        ),
        '8RRK' => array(
            'Id' => '8RRK',
            'Label' => 'Oceania|One State Or Province',
        ),
        '8RRO' => array(
            'Id' => '8RRO',
            'Label' => 'Europe|Romania',
        ),
        '8RRU' => array(
            'Id' => '8RRU',
            'Label' => 'Europe|Russian Federation',
        ),
        '8RRW' => array(
            'Id' => '8RRW',
            'Label' => 'Africa|Rwanda',
        ),
        '8RSA' => array(
            'Id' => '8RSA',
            'Label' => 'Middle East|Saudi Arabia',
        ),
        '8RSB' => array(
            'Id' => '8RSB',
            'Label' => 'Oceania|Solomon Islands',
        ),
        '8RSC' => array(
            'Id' => '8RSC',
            'Label' => 'Oceania|Seychelles',
        ),
        '8RSD' => array(
            'Id' => '8RSD',
            'Label' => 'Africa|Sudan',
        ),
        '8RSE' => array(
            'Id' => '8RSE',
            'Label' => 'Europe|Sweden',
        ),
        '8RSG' => array(
            'Id' => '8RSG',
            'Label' => 'Asia|Singapore',
        ),
        '8RSH' => array(
            'Id' => '8RSH',
            'Label' => 'Africa|Saint Helena',
        ),
        '8RSI' => array(
            'Id' => '8RSI',
            'Label' => 'Europe|Slovenia',
        ),
        '8RSK' => array(
            'Id' => '8RSK',
            'Label' => 'Europe|Slovakia',
        ),
        '8RSL' => array(
            'Id' => '8RSL',
            'Label' => 'Africa|Sierra Leone',
        ),
        '8RSM' => array(
            'Id' => '8RSM',
            'Label' => 'Europe|San Marino',
        ),
        '8RSN' => array(
            'Id' => '8RSN',
            'Label' => 'Africa|Senegal',
        ),
        '8RSO' => array(
            'Id' => '8RSO',
            'Label' => 'Africa|Somalia',
        ),
        '8RSR' => array(
            'Id' => '8RSR',
            'Label' => 'Latin America and Caribbean|Suriname',
        ),
        '8RST' => array(
            'Id' => '8RST',
            'Label' => 'Africa|Sao Tome and Principe',
        ),
        '8RSV' => array(
            'Id' => '8RSV',
            'Label' => 'Latin America and Caribbean|El Salvador',
        ),
        '8RSY' => array(
            'Id' => '8RSY',
            'Label' => 'Middle East|Syria',
        ),
        '8RSZ' => array(
            'Id' => '8RSZ',
            'Label' => 'Africa|Swaziland',
        ),
        '8RTC' => array(
            'Id' => '8RTC',
            'Label' => 'Latin America and Caribbean|Turks and Caicos Islands',
        ),
        '8RTD' => array(
            'Id' => '8RTD',
            'Label' => 'Africa|Chad',
        ),
        '8RTG' => array(
            'Id' => '8RTG',
            'Label' => 'Africa|Togo',
        ),
        '8RTH' => array(
            'Id' => '8RTH',
            'Label' => 'Asia|Thailand',
        ),
        '8RTJ' => array(
            'Id' => '8RTJ',
            'Label' => 'Asia|Tajikistan',
        ),
        '8RTK' => array(
            'Id' => '8RTK',
            'Label' => 'Oceania|Tokelau',
        ),
        '8RTL' => array(
            'Id' => '8RTL',
            'Label' => 'Asia|Timor-Leste',
        ),
        '8RTM' => array(
            'Id' => '8RTM',
            'Label' => 'Asia|Turkmenistan',
        ),
        '8RTN' => array(
            'Id' => '8RTN',
            'Label' => 'Africa|Tunisia',
        ),
        '8RTO' => array(
            'Id' => '8RTO',
            'Label' => 'Oceania|Tonga',
        ),
        '8RTR' => array(
            'Id' => '8RTR',
            'Label' => 'Middle East|Turkey',
        ),
        '8RTT' => array(
            'Id' => '8RTT',
            'Label' => 'Latin America and Caribbean|Trinidad and Tobago',
        ),
        '8RTV' => array(
            'Id' => '8RTV',
            'Label' => 'Oceania|Tuvalu',
        ),
        '8RTW' => array(
            'Id' => '8RTW',
            'Label' => 'Asia|Taiwan',
        ),
        '8RTZ' => array(
            'Id' => '8RTZ',
            'Label' => 'Africa|Tanzania, United Republic Of',
        ),
        '8RUA' => array(
            'Id' => '8RUA',
            'Label' => 'Europe|Ukraine',
        ),
        '8RUB' => array(
            'Id' => '8RUB',
            'Label' => 'Asia|China-East',
        ),
        '8RUC' => array(
            'Id' => '8RUC',
            'Label' => 'Asia|China-North',
        ),
        '8RUD' => array(
            'Id' => '8RUD',
            'Label' => 'Asia|China-South Central',
        ),
        '8RUF' => array(
            'Id' => '8RUF',
            'Label' => 'Asia|China-Southwest',
        ),
        '8RUG' => array(
            'Id' => '8RUG',
            'Label' => 'Africa|Uganda',
        ),
        '8RUH' => array(
            'Id' => '8RUH',
            'Label' => 'Europe|Northern Ireland',
        ),
        '8RUI' => array(
            'Id' => '8RUI',
            'Label' => 'Europe|Wales',
        ),
        '8RUJ' => array(
            'Id' => '8RUJ',
            'Label' => 'Latin America and Caribbean|All Latin America',
        ),
        '8RUK' => array(
            'Id' => '8RUK',
            'Label' => 'Northern America|USA-All Territories, Protectorates, Dependencies, Outposts',
        ),
        '8RUL' => array(
            'Id' => '8RUL',
            'Label' => 'Any Regions',
        ),
        '8RUM' => array(
            'Id' => '8RUM',
            'Label' => 'Northern America|Canada-Prairies',
        ),
        '8RUN' => array(
            'Id' => '8RUN',
            'Label' => 'Northern America|Canada-Atlantic Provinces',
        ),
        '8RUP' => array(
            'Id' => '8RUP',
            'Label' => 'Northern America|Canada-Quebec',
        ),
        '8RUQ' => array(
            'Id' => '8RUQ',
            'Label' => 'Northern America|Canada-Northern Territories',
        ),
        '8RUR' => array(
            'Id' => '8RUR',
            'Label' => 'Oceania|All Australia and New Zealand',
        ),
        '8RUS' => array(
            'Id' => '8RUS',
            'Label' => 'Oceania|All Oceania excluding Australia and New Zealand',
        ),
        '8RUY' => array(
            'Id' => '8RUY',
            'Label' => 'Latin America and Caribbean|Uruguay',
        ),
        '8RUZ' => array(
            'Id' => '8RUZ',
            'Label' => 'Asia|Uzbekistan',
        ),
        '8RVC' => array(
            'Id' => '8RVC',
            'Label' => 'Latin America and Caribbean|Saint Vincent and The Grenadines',
        ),
        '8RVE' => array(
            'Id' => '8RVE',
            'Label' => 'Latin America and Caribbean|Venezuela',
        ),
        '8RVN' => array(
            'Id' => '8RVN',
            'Label' => 'Asia|Viet Nam',
        ),
        '8RVU' => array(
            'Id' => '8RVU',
            'Label' => 'Oceania|Vanuatu',
        ),
        '8RWA' => array(
            'Id' => '8RWA',
            'Label' => 'Broad International Region|Worldwide Excluding Northern America',
        ),
        '8RWB' => array(
            'Id' => '8RWB',
            'Label' => 'Broad International Region|Worldwide Excluding USA',
        ),
        '8RWC' => array(
            'Id' => '8RWC',
            'Label' => 'Broad International Region|Worldwide Excluding USA and Europe',
        ),
        '8RWD' => array(
            'Id' => '8RWD',
            'Label' => 'Broad International Region|Worldwide Excluding Europe',
        ),
        '8RWE' => array(
            'Id' => '8RWE',
            'Label' => 'Broad International Region|Worldwide Excluding USA and UK',
        ),
        '8RWF' => array(
            'Id' => '8RWF',
            'Label' => 'Broad International Region|Worldwide Excluding UK',
        ),
        '8RWG' => array(
            'Id' => '8RWG',
            'Label' => 'Broad International Region|All English Speaking Countries',
        ),
        '8RWH' => array(
            'Id' => '8RWH',
            'Label' => 'Broad International Region|All English Speaking Countries Excluding USA',
        ),
        '8RWI' => array(
            'Id' => '8RWI',
            'Label' => 'Broad International Region|All Spanish Speaking Countries Excluding USA',
        ),
        '8RWJ' => array(
            'Id' => '8RWJ',
            'Label' => 'Broad International Region|USA, Canada and Mexico',
        ),
        '8RWS' => array(
            'Id' => '8RWS',
            'Label' => 'Oceania|Samoa',
        ),
        '8RXX' => array(
            'Id' => '8RXX',
            'Label' => 'Not Applicable or None',
        ),
        '8RYB' => array(
            'Id' => '8RYB',
            'Label' => 'Africa|Up To 3 States Or Provinces',
        ),
        '8RYC' => array(
            'Id' => '8RYC',
            'Label' => 'Africa|Up To 5 States Or Provinces',
        ),
        '8RYD' => array(
            'Id' => '8RYD',
            'Label' => 'Asia|Up To 5 States Or Provinces',
        ),
        '8RYE' => array(
            'Id' => '8RYE',
            'Label' => 'Middle East|Yemen',
        ),
        '8RYF' => array(
            'Id' => '8RYF',
            'Label' => 'Europe|Up To 3 States Or Provinces',
        ),
        '8RYG' => array(
            'Id' => '8RYG',
            'Label' => 'Europe|Up To 5 States Or Provinces',
        ),
        '8RYH' => array(
            'Id' => '8RYH',
            'Label' => 'Latin America and Caribbean|Up To 3 States Or Provinces',
        ),
        '8RYI' => array(
            'Id' => '8RYI',
            'Label' => 'Latin America and Caribbean|Up To 5 States Or Provinces',
        ),
        '8RYJ' => array(
            'Id' => '8RYJ',
            'Label' => 'Middle East|Up To 3 States Or Provinces',
        ),
        '8RYK' => array(
            'Id' => '8RYK',
            'Label' => 'Northern America|Up To 3 States Or Provinces',
        ),
        '8RYL' => array(
            'Id' => '8RYL',
            'Label' => 'Oceania|Up To 3 States Or Provinces',
        ),
        '8RYM' => array(
            'Id' => '8RYM',
            'Label' => 'Oceania|Up To 5 States Or Provinces',
        ),
        '8RYT' => array(
            'Id' => '8RYT',
            'Label' => 'Africa|Mayotte',
        ),
        '8RZA' => array(
            'Id' => '8RZA',
            'Label' => 'Africa|South Africa',
        ),
        '8RZM' => array(
            'Id' => '8RZM',
            'Label' => 'Africa|Zambia',
        ),
        '8RZW' => array(
            'Id' => '8RZW',
            'Label' => 'Africa|Zimbabwe',
        ),
        '9EIN' => array(
            'Id' => '9EIN',
            'Label' => 'Exclusivity For Industry',
        ),
        '9ELA' => array(
            'Id' => '9ELA',
            'Label' => 'Exclusivity For Language',
        ),
        '9EME' => array(
            'Id' => '9EME',
            'Label' => 'Exclusivity For Media',
        ),
        '9ENE' => array(
            'Id' => '9ENE',
            'Label' => 'Non-Exclusive',
        ),
        '9ERE' => array(
            'Id' => '9ERE',
            'Label' => 'Exclusivity For Region',
        ),
        '9EXC' => array(
            'Id' => '9EXC',
            'Label' => 'All Exclusive',
        ),
        '9EXX' => array(
            'Id' => '9EXX',
            'Label' => 'Not Applicable or None',
        ),
    );

}
