<?php

/*
 * SphinxSearch Unicode maps
 * Courtesy of http://speeple.com/unicode-maps.txt
 */

class sphinx_charsetTable_latin extends sphinx_charsetTableAbstract
{
    protected $name = 'Latin';
    protected $table = '
    ##################################################
    # Latin
    # A
    U+00C0->a, U+00C1->a, U+00C2->a, U+00C3->a, U+00C4->a, U+00C5->a, U+00E0->a,
    U+00E1->a, U+00E2->a, U+00E3->a, U+00E4->a, U+00E5->a, U+0100->a, U+0101->a,
    U+0102->a, U+0103->a, U+010300->a, U+0104->a, U+0105->a, U+01CD->a, U+01CE->a,
    U+01DE->a, U+01DF->a, U+01E0->a, U+01E1->a, U+01FA->a, U+01FB->a, U+0200->a,
    U+0201->a, U+0202->a, U+0203->a, U+0226->a, U+0227->a, U+023A->a, U+0250->a,
    U+04D0->a, U+04D1->a, U+1D2C->a, U+1D43->a, U+1D44->a, U+1D8F->a, U+1E00->a,
    U+1E01->a, U+1E9A->a, U+1EA0->a, U+1EA1->a, U+1EA2->a, U+1EA3->a, U+1EA4->a,
    U+1EA5->a, U+1EA6->a, U+1EA7->a, U+1EA8->a, U+1EA9->a, U+1EAA->a, U+1EAB->a,
    U+1EAC->a, U+1EAD->a, U+1EAE->a, U+1EAF->a, U+1EB0->a, U+1EB1->a, U+1EB2->a,
    U+1EB3->a, U+1EB4->a, U+1EB5->a, U+1EB6->a, U+1EB7->a, U+2090->a, U+2C65->a,

    # B
    U+0180->b, U+0181->b, U+0182->b, U+0183->b, U+0243->b, U+0253->b, U+0299->b,
    U+16D2->b, U+1D03->b, U+1D2E->b, U+1D2F->b, U+1D47->b, U+1D6C->b, U+1D80->b,
    U+1E02->b, U+1E03->b, U+1E04->b, U+1E05->b, U+1E06->b, U+1E07->b,

    # C
    U+00C7->c, U+00E7->c, U+0106->c, U+0107->c, U+0108->c, U+0109->c, U+010A->c,
    U+010B->c, U+010C->c, U+010D->c, U+0187->c, U+0188->c, U+023B->c, U+023C->c,
    U+0255->c, U+0297->c, U+1D9C->c, U+1D9D->c, U+1E08->c, U+1E09->c, U+212D->c,
    U+2184->c,

    # D
    U+010E->d, U+010F->d, U+0110->d, U+0111->d, U+0189->d, U+018A->d, U+018B->d,
    U+018C->d, U+01C5->d, U+01F2->d, U+0221->d, U+0256->d, U+0257->d, U+1D05->d,
    U+1D30->d, U+1D48->d, U+1D6D->d, U+1D81->d, U+1D91->d, U+1E0A->d, U+1E0B->d,
    U+1E0C->d, U+1E0D->d, U+1E0E->d, U+1E0F->d, U+1E10->d, U+1E11->d, U+1E12->d,
    U+1E13->d,

    # E
    U+00C8->e, U+00C9->e, U+00CA->e, U+00CB->e, U+00E8->e, U+00E9->e, U+00EA->e,
    U+00EB->e, U+0112->e, U+0113->e, U+0114->e, U+0115->e, U+0116->e, U+0117->e,
    U+0118->e, U+0119->e, U+011A->e, U+011B->e, U+018E->e, U+0190->e, U+01DD->e,
    U+0204->e, U+0205->e, U+0206->e, U+0207->e, U+0228->e, U+0229->e, U+0246->e,
    U+0247->e, U+0258->e, U+025B->e, U+025C->e, U+025D->e, U+025E->e, U+029A->e,
    U+1D07->e, U+1D08->e, U+1D31->e, U+1D32->e, U+1D49->e, U+1D4B->e, U+1D4C->e,
    U+1D92->e, U+1D93->e, U+1D94->e, U+1D9F->e, U+1E14->e, U+1E15->e, U+1E16->e,
    U+1E17->e, U+1E18->e, U+1E19->e, U+1E1A->e, U+1E1B->e, U+1E1C->e, U+1E1D->e,
    U+1EB8->e, U+1EB9->e, U+1EBA->e, U+1EBB->e, U+1EBC->e, U+1EBD->e, U+1EBE->e,
    U+1EBF->e, U+1EC0->e, U+1EC1->e, U+1EC2->e, U+1EC3->e, U+1EC4->e, U+1EC5->e,
    U+1EC6->e, U+1EC7->e, U+2091->e,

    # F
    U+0191->f, U+0192->f, U+1D6E->f, U+1D82->f, U+1DA0->f, U+1E1E->f, U+1E1F->f,

    # G
    U+011C->g, U+011D->g, U+011E->g, U+011F->g, U+0120->g, U+0121->g, U+0122->g,
    U+0123->g, U+0193->g, U+01E4->g, U+01E5->g, U+01E6->g, U+01E7->g, U+01F4->g,
    U+01F5->g, U+0260->g, U+0261->g, U+0262->g, U+029B->g, U+1D33->g, U+1D4D->g,
    U+1D77->g, U+1D79->g, U+1D83->g, U+1DA2->g, U+1E20->g, U+1E21->g,

    # H
    U+0124->h, U+0125->h, U+0126->h, U+0127->h, U+021E->h, U+021F->h, U+0265->h,
    U+0266->h, U+029C->h, U+02AE->h, U+02AF->h, U+02B0->h, U+02B1->h, U+1D34->h,
    U+1DA3->h, U+1E22->h, U+1E23->h, U+1E24->h, U+1E25->h, U+1E26->h, U+1E27->h,
    U+1E28->h, U+1E29->h, U+1E2A->h, U+1E2B->h, U+1E96->h, U+210C->h, U+2C67->h,
    U+2C68->h, U+2C75->h, U+2C76->h,

    # I
    U+00CC->i, U+00CD->i, U+00CE->i, U+00CF->i, U+00EC->i, U+00ED->i, U+00EE->i,
    U+00EF->i, U+010309->i, U+0128->i, U+0129->i, U+012A->i, U+012B->i, U+012C->i,
    U+012D->i, U+012E->i, U+012F->i, U+0130->i, U+0131->i, U+0197->i, U+01CF->i,
    U+01D0->i, U+0208->i, U+0209->i, U+020A->i, U+020B->i, U+0268->i, U+026A->i,
    U+040D->i, U+0418->i, U+0419->i, U+0438->i, U+0439->i, U+0456->i, U+1D09->i,
    U+1D35->i, U+1D4E->i, U+1D62->i, U+1D7B->i, U+1D96->i, U+1DA4->i, U+1DA6->i,
    U+1DA7->i, U+1E2C->i, U+1E2D->i, U+1E2E->i, U+1E2F->i, U+1EC8->i, U+1EC9->i,
    U+1ECA->i, U+1ECB->i, U+2071->i, U+2111->i,

    # J
    U+0134->j, U+0135->j, U+01C8->j, U+01CB->j, U+01F0->j, U+0237->j, U+0248->j,
    U+0249->j, U+025F->j, U+0284->j, U+029D->j, U+02B2->j, U+1D0A->j, U+1D36->j,
    U+1DA1->j, U+1DA8->j,

    # K
    U+0136->k, U+0137->k, U+0198->k, U+0199->k, U+01E8->k, U+01E9->k, U+029E->k,
    U+1D0B->k, U+1D37->k, U+1D4F->k, U+1D84->k, U+1E30->k, U+1E31->k, U+1E32->k,
    U+1E33->k, U+1E34->k, U+1E35->k, U+2C69->k, U+2C6A->k,

    # L
    U+0139->l, U+013A->l, U+013B->l, U+013C->l, U+013D->l, U+013E->l, U+013F->l,
    U+0140->l, U+0141->l, U+0142->l, U+019A->l, U+01C8->l, U+0234->l, U+023D->l,
    U+026B->l, U+026C->l, U+026D->l, U+029F->l, U+02E1->l, U+1D0C->l, U+1D38->l,
    U+1D85->l, U+1DA9->l, U+1DAA->l, U+1DAB->l, U+1E36->l, U+1E37->l, U+1E38->l,
    U+1E39->l, U+1E3A->l, U+1E3B->l, U+1E3C->l, U+1E3D->l, U+2C60->l, U+2C61->l,
    U+2C62->l,

    # M
    U+019C->m, U+026F->m, U+0270->m, U+0271->m, U+1D0D->m, U+1D1F->m, U+1D39->m,
    U+1D50->m, U+1D5A->m, U+1D6F->m, U+1D86->m, U+1DAC->m, U+1DAD->m, U+1E3E->m,
    U+1E3F->m, U+1E40->m, U+1E41->m, U+1E42->m, U+1E43->m,

    # N
    U+00D1->n, U+00F1->n, U+0143->n, U+0144->n, U+0145->n, U+0146->n, U+0147->n,
    U+0148->n, U+0149->n, U+019D->n, U+019E->n, U+01CB->n, U+01F8->n, U+01F9->n,
    U+0220->n, U+0235->n, U+0272->n, U+0273->n, U+0274->n, U+1D0E->n, U+1D3A->n,
    U+1D3B->n, U+1D70->n, U+1D87->n, U+1DAE->n, U+1DAF->n, U+1DB0->n, U+1E44->n,
    U+1E45->n, U+1E46->n, U+1E47->n, U+1E48->n, U+1E49->n, U+1E4A->n, U+1E4B->n,
    U+207F->n,

    # O
    U+00D2->o, U+00D3->o, U+00D4->o, U+00D5->o, U+00D6->o, U+00D8->o, U+00F2->o,
    U+00F3->o, U+00F4->o, U+00F5->o, U+00F6->o, U+00F8->o, U+01030F->o, U+014C->o,
    U+014D->o, U+014E->o, U+014F->o, U+0150->o, U+0151->o, U+0186->o, U+019F->o,
    U+01A0->o, U+01A1->o, U+01D1->o, U+01D2->o, U+01EA->o, U+01EB->o, U+01EC->o,
    U+01ED->o, U+01FE->o, U+01FF->o, U+020C->o, U+020D->o, U+020E->o, U+020F->o,
    U+022A->o, U+022B->o, U+022C->o, U+022D->o, U+022E->o, U+022F->o, U+0230->o,
    U+0231->o, U+0254->o, U+0275->o, U+043E->o, U+04E6->o, U+04E7->o, U+04E8->o,
    U+04E9->o, U+04EA->o, U+04EB->o, U+1D0F->o, U+1D10->o, U+1D11->o, U+1D12->o,
    U+1D13->o, U+1D16->o, U+1D17->o, U+1D3C->o, U+1D52->o, U+1D53->o, U+1D54->o,
    U+1D55->o, U+1D97->o, U+1DB1->o, U+1E4C->o, U+1E4D->o, U+1E4E->o, U+1E4F->o,
    U+1E50->o, U+1E51->o, U+1E52->o, U+1E53->o, U+1ECC->o, U+1ECD->o, U+1ECE->o,
    U+1ECF->o, U+1ED0->o, U+1ED1->o, U+1ED2->o, U+1ED3->o, U+1ED4->o, U+1ED5->o,
    U+1ED6->o, U+1ED7->o, U+1ED8->o, U+1ED9->o, U+1EDA->o, U+1EDB->o, U+1EDC->o,
    U+1EDD->o, U+1EDE->o, U+1EDF->o, U+1EE0->o, U+1EE1->o, U+1EE2->o, U+1EE3->o,
    U+2092->o, U+2C9E->o, U+2C9F->o,

    # P
    U+01A4->p, U+01A5->p, U+1D18->p, U+1D3E->p, U+1D56->p, U+1D71->p, U+1D7D->p,
    U+1D88->p, U+1E54->p, U+1E55->p, U+1E56->p, U+1E57->p, U+2C63->p,

    # Q
    U+024A->q, U+024B->q, U+02A0->q,

    # R
    U+0154->r, U+0155->r, U+0156->r, U+0157->r, U+0158->r, U+0159->r, U+0210->r,
    U+0211->r, U+0212->r, U+0213->r, U+024C->r, U+024D->r, U+0279->r, U+027A->r,
    U+027B->r, U+027C->r, U+027D->r, U+027E->r, U+027F->r, U+0280->r, U+0281->r,
    U+02B3->r, U+02B4->r, U+02B5->r, U+02B6->r, U+1D19->r, U+1D1A->r, U+1D3F->r,
    U+1D63->r, U+1D72->r, U+1D73->r, U+1D89->r, U+1DCA->r, U+1E58->r, U+1E59->r,
    U+1E5A->r, U+1E5B->r, U+1E5C->r, U+1E5D->r, U+1E5E->r, U+1E5F->r, U+211C->r,
    U+2C64->r,

    # S
    U+00DF->s, U+015A->s, U+015B->s, U+015C->s, U+015D->s, U+015E->s, U+015F->s,
    U+0160->s, U+0161->s, U+017F->s, U+0218->s, U+0219->s, U+023F->s, U+0282->s,
    U+02E2->s, U+1D74->s, U+1D8A->s, U+1DB3->s, U+1E60->s, U+1E61->s, U+1E62->s,
    U+1E63->s, U+1E64->s, U+1E65->s, U+1E66->s, U+1E67->s, U+1E68->s, U+1E69->s,
    U+1E9B->s,

    # T
    U+0162->t, U+0163->t, U+0164->t, U+0165->t, U+0166->t, U+0167->t, U+01AB->t,
    U+01AC->t, U+01AD->t, U+01AE->t, U+021A->t, U+021B->t, U+0236->t, U+023E->t,
    U+0287->t, U+0288->t, U+1D1B->t, U+1D40->t, U+1D57->t, U+1D75->t, U+1DB5->t,
    U+1E6A->t, U+1E6B->t, U+1E6C->t, U+1E6D->t, U+1E6E->t, U+1E6F->t, U+1E70->t,
    U+1E71->t, U+1E97->t, U+2C66->t,

    # U
    U+00D9->u, U+00DA->u, U+00DB->u, U+00DC->u, U+00F9->u, U+00FA->u, U+00FB->u,
    U+00FC->u, U+010316->u, U+0168->u, U+0169->u, U+016A->u, U+016B->u, U+016C->u,
    U+016D->u, U+016E->u, U+016F->u, U+0170->u, U+0171->u, U+0172->u, U+0173->u,
    U+01AF->u, U+01B0->u, U+01D3->u, U+01D4->u, U+01D5->u, U+01D6->u, U+01D7->u,
    U+01D8->u, U+01D9->u, U+01DA->u, U+01DB->u, U+01DC->u, U+0214->u, U+0215->u,
    U+0216->u, U+0217->u, U+0244->u, U+0289->u, U+1D1C->u, U+1D1D->u, U+1D1E->u,
    U+1D41->u, U+1D58->u, U+1D59->u, U+1D64->u, U+1D7E->u, U+1D99->u, U+1DB6->u,
    U+1DB8->u, U+1E72->u, U+1E73->u, U+1E74->u, U+1E75->u, U+1E76->u, U+1E77->u,
    U+1E78->u, U+1E79->u, U+1E7A->u, U+1E7B->u, U+1EE4->u, U+1EE5->u, U+1EE6->u,
    U+1EE7->u, U+1EE8->u, U+1EE9->u, U+1EEA->u, U+1EEB->u, U+1EEC->u, U+1EED->u,
    U+1EEE->u, U+1EEF->u, U+1EF0->u, U+1EF1->u,

    # V
    U+01B2->v, U+0245->v, U+028B->v, U+028C->v, U+1D20->v, U+1D5B->v, U+1D65->v,
    U+1D8C->v, U+1DB9->v, U+1DBA->v, U+1E7C->v, U+1E7D->v, U+1E7E->v, U+1E7F->v,
    U+2C74->v,

    # W
    U+0174->w, U+0175->w, U+028D->w, U+02B7->w, U+1D21->w, U+1D42->w, U+1E80->w,
    U+1E81->w, U+1E82->w, U+1E83->w, U+1E84->w, U+1E85->w, U+1E86->w, U+1E87->w,
    U+1E88->w, U+1E89->w, U+1E98->w,

    # X
    U+02E3->x, U+1D8D->x, U+1E8A->x, U+1E8B->x, U+1E8C->x, U+1E8D->x, U+2093->x,

    # Y
    U+00DD->y, U+00FD->y, U+00FF->y, U+0176->y, U+0177->y, U+0178->y, U+01B3->y,
    U+01B4->y, U+0232->y, U+0233->y, U+024E->y, U+024F->y, U+028E->y, U+028F->y,
    U+02B8->y, U+1E8E->y, U+1E8F->y, U+1E99->y, U+1EF2->y, U+1EF3->y, U+1EF4->y,
    U+1EF5->y, U+1EF6->y, U+1EF7->y, U+1EF8->y, U+1EF9->y,

    # Z
    U+0179->z, U+017A->z, U+017B->z, U+017C->z, U+017D->z, U+017E->z, U+01B5->z,
    U+01B6->z, U+0224->z, U+0225->z, U+0240->z, U+0290->z, U+0291->z, U+1D22->z,
    U+1D76->z, U+1D8E->z, U+1DBB->z, U+1DBC->z, U+1DBD->z, U+1E90->z, U+1E91->z,
    U+1E92->z, U+1E93->z, U+1E94->z, U+1E95->z, U+2128->z, U+2C6B->z, U+2C6C->z,

    # Latin Extras:
    U+00C6->U+00E6, U+01E2->U+00E6, U+01E3->U+00E6, U+01FC->U+00E6, U+01FD->U+00E6,
    U+1D01->U+00E6, U+1D02->U+00E6, U+1D2D->U+00E6, U+1D46->U+00E6, U+00E6,
    ';

}
