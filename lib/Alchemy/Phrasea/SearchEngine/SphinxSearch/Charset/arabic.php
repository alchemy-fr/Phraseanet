<?php

/*
 * SphinxSearch Unicode maps
 * Courtesy of http://speeple.com/unicode-maps.txt
 */

namespace Alchemy\Phrasea\SearchEngine\SphinxSearch\Charset;

use Alchemy\Phrasea\SearchEngine\SphinxSearch\AbstractCharset;

class arabic extends AbstractCharset
{
    protected $name = 'Arabic';
    protected $table = '
    ##################################################
    # Arabic
    U+0622->U+0627, U+0623->U+0627, U+0624->U+0648, U+0625->U+0627, U+0626->U+064A,
    U+06C0->U+06D5, U+06C2->U+06C1, U+06D3->U+06D2, U+FB50->U+0671, U+FB51->U+0671,
    U+FB52->U+067B, U+FB53->U+067B, U+FB54->U+067B, U+FB56->U+067E, U+FB57->U+067E,
    U+FB58->U+067E, U+FB5A->U+0680, U+FB5B->U+0680, U+FB5C->U+0680, U+FB5E->U+067A,
    U+FB5F->U+067A, U+FB60->U+067A, U+FB62->U+067F, U+FB63->U+067F, U+FB64->U+067F,
    U+FB66->U+0679, U+FB67->U+0679, U+FB68->U+0679, U+FB6A->U+06A4, U+FB6B->U+06A4,
    U+FB6C->U+06A4, U+FB6E->U+06A6, U+FB6F->U+06A6, U+FB70->U+06A6, U+FB72->U+0684,
    U+FB73->U+0684, U+FB74->U+0684, U+FB76->U+0683, U+FB77->U+0683, U+FB78->U+0683,
    U+FB7A->U+0686, U+FB7B->U+0686, U+FB7C->U+0686, U+FB7E->U+0687, U+FB7F->U+0687,
    U+FB80->U+0687, U+FB82->U+068D, U+FB83->U+068D, U+FB84->U+068C, U+FB85->U+068C,
    U+FB86->U+068E, U+FB87->U+068E, U+FB88->U+0688, U+FB89->U+0688, U+FB8A->U+0698,
    U+FB8B->U+0698, U+FB8C->U+0691, U+FB8D->U+0691, U+FB8E->U+06A9, U+FB8F->U+06A9,
    U+FB90->U+06A9, U+FB92->U+06AF, U+FB93->U+06AF, U+FB94->U+06AF, U+FB96->U+06B3,
    U+FB97->U+06B3, U+FB98->U+06B3, U+FB9A->U+06B1, U+FB9B->U+06B1, U+FB9C->U+06B1,
    U+FB9E->U+06BA, U+FB9F->U+06BA, U+FBA0->U+06BB, U+FBA1->U+06BB, U+FBA2->U+06BB,
    U+FBA4->U+06C0, U+FBA5->U+06C0, U+FBA6->U+06C1, U+FBA7->U+06C1, U+FBA8->U+06C1,
    U+FBAA->U+06BE, U+FBAB->U+06BE, U+FBAC->U+06BE, U+FBAE->U+06D2, U+FBAF->U+06D2,
    U+FBB0->U+06D3, U+FBB1->U+06D3, U+FBD3->U+06AD, U+FBD4->U+06AD, U+FBD5->U+06AD,
    U+FBD7->U+06C7, U+FBD8->U+06C7, U+FBD9->U+06C6, U+FBDA->U+06C6, U+FBDB->U+06C8,
    U+FBDC->U+06C8, U+FBDD->U+0677, U+FBDE->U+06CB, U+FBDF->U+06CB, U+FBE0->U+06C5,
    U+FBE1->U+06C5, U+FBE2->U+06C9, U+FBE3->U+06C9, U+FBE4->U+06D0, U+FBE5->U+06D0,
    U+FBE6->U+06D0, U+FBE8->U+0649, U+FBFC->U+06CC, U+FBFD->U+06CC, U+FBFE->U+06CC,
    U+0621, U+0627..U+063A, U+0641..U+064A, U+0660..U+0669, U+066E, U+066F,
    U+0671..U+06BF, U+06C1, U+06C3..U+06D2, U+06D5, U+06EE..U+06FC, U+06FF,
    U+0750..U+076D, U+FB55, U+FB59, U+FB5D, U+FB61, U+FB65, U+FB69, U+FB6D, U+FB71,
    U+FB75, U+FB79, U+FB7D, U+FB81, U+FB91, U+FB95, U+FB99, U+FB9D, U+FBA3, U+FBA9,
    U+FBAD, U+FBD6, U+FBE7, U+FBE9, U+FBFF
    ';

}
