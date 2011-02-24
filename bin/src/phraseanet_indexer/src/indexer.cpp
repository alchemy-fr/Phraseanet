#include "_syslog.h"

#include "indexer.h"


extern CSyslog zSyslog;


// prototypes of external fcts
void saveCterms(CIndexer *indexer);

/*
xmlChar *my_xmlGetProp(xmlNodePtr node, const xmlChar *p)
{
	return(xmlGetProp(node, p));
}
*/

unsigned int hashKword(const char *s, int l)
{
    register unsigned int hash = 0;
	l &= KWORD_HASHLMAX;
 	while(*s && l--)
		hash = ((hash << 4) + hash) ^ (*s++); /* hash * 17 ^ c */
	return(hash & (KWORD_HASHSIZE - 1));
}


CIndexer::CIndexer(CConnbas_dbox *connbas)
{
	nrecsInBuff = 0;

	this->tRecord = NULL;
	this->tStructField = NULL;
	this->xmlparser = NULL;
	this->nNewKeywords = 0;
	this->current_rid = 0;
	this->nrecsIndexed = 0;
//			this->current_field = NULL;
	this->current_xpath = NULL;
	for(int i=0; i<KWORD_HASHSIZE; i++)
		this->tKeywords[i] = NULL;
	this->tXPaths = NULL;
	this->firstTHit = NULL;
	this->firstProp = NULL;

	// force the loading of the thesaurus
	this->firstLoad = true;				// force the loading of prefs at startup
	this->current_struct_moddate = this->current_thesaurus_moddate = this->current_cterms_moddate = 0L;

	this->DocThesaurus       = NULL;
	this->XPathCtx_thesaurus = NULL;

	this->DocCterms          = NULL;
	this->XPathCtx_cterms    = NULL;
	this->ctermsChanged      = false;

	this->XPathCtx_deleted   = NULL;
	this->xmlNodePtr_deleted = NULL;		// node to the branch 'deleted'

	this->connbas = connbas;
};


CIndexer::~CIndexer()
{
	CRecord *r;
	while( (r = this->tRecord) )
	{
		this->tRecord = r->next;
		delete r;
	}
	CKword *k;
	for(unsigned int hash=0; hash<KWORD_HASHSIZE; hash++)
	{
		while( (k = this->tKeywords[hash]) )
		{
			this->tKeywords[hash] = k->next;
			delete k;
		}
	}
	CXPath *x;
	while( (x = this->tXPaths) )
	{
		this->tXPaths = x->next;
		delete x;
	}

	if(this->tStructField)
		delete [] (this->tStructField);

	if(this->XPathCtx_deleted)
		xmlXPathFreeContext(XPathCtx_deleted); 

	if(this->XPathCtx_cterms)
		xmlXPathFreeContext(XPathCtx_cterms); 

	if(this->DocCterms)
		xmlFreeDoc(this->DocCterms);

	if(this->XPathCtx_thesaurus)
		xmlXPathFreeContext(XPathCtx_thesaurus); 

	if(this->DocThesaurus)
		xmlFreeDoc(this->DocThesaurus);

	CTHit *th;
	while( (th = this->firstTHit) )
	{
		this->firstTHit = th->next;
		delete th;
	}

	CProp *p;
	while( (p = this->firstProp) )
	{
		this->firstProp = p->next;
		delete p;
	}
};

void CIndexer::flush()
{
	if(!this->tRecord)
	{
//		printf("| nothing to flush.\n");
	}
	else
	{
//		zSyslog.log(CSyslog::LOG_INFO, CSyslog::LOGC_FLUSH, "/=================================== FLUSH =========================");

		int nrecs_flushed = 0;
		// -----------------------------------------------------------------------------
		// warning : before flushing thits, we check that the thesaurus/cterms hasn't changed
		// else we will set the records as 'to-reindex-thesaurus' again
		// -----------------------------------------------------------------------------

		bool thesaurusChanged = false;

		// --------------------------------------------------- flush xpath
		CXPath *xp;
		//start by counting unknown xpath
		int nNewXPath = 0;
		for(xp = this->tXPaths; xp; xp = xp->next)
		{
			if(xp->id == 0)
				nNewXPath++;
		}
		if(nNewXPath > 0)
		{
			// there is some unknown, we get a uid and we write
			unsigned long xpath_new_uid;
			xpath_new_uid = this->connbas->getID("XPATH", nNewXPath);

			for(xp = this->tXPaths; !this->connbas->crashed && xp; xp = xp->next)
			{
				if(xp->id == 0)
				{
					xp->id = xpath_new_uid++;
					int r;
					if((r = this->connbas->insertXPath(xp->upath, &(xp->id))) == 0)
					{
						// ok : we have created the xpath, or if it was existing the id is returned in xp->id
					}
					else
					{
						// err : no way to create the xpath neither finding the existing one
					}
				}
			}
		}

		// --------------------------------------------------- flush the record (delete idx, prop, thits)
		// create a list of rids
		CRecord *r;
		int lrids_len = 0;
		char *pbuff, *lrids_buff;
		for(r=this->tRecord; r; r=r->next)
			lrids_len += 34;	// 33=lmax of itoa() + comma delimiter.
		pbuff = lrids_buff = (char *)(_MALLOC_WHY(lrids_len, "indexer.cpp:flush:pbuff"));
		while( (r = this->tRecord) )
		{
			if(pbuff)
			{
				pbuff += sprintf(pbuff, "%d", r->id);
				if(r->next)
					*pbuff++ = ',';
			}
			this->tRecord = r->next;
			delete r;

			nrecs_flushed++;
		}
		lrids_len = pbuff-lrids_buff;	// ajuste la longueur

		if(lrids_buff)
		{
			// delete idx, prop, thits for those records
			this->connbas->delRecRefs2(lrids_buff, lrids_len);

			// lock prefs and thits
			if(!this->connbas->crashed && (this->connbas->lockPref() == 0))
			{
				// check if something has changed in the thesaurus
				time_t struct_moddate, thesaurus_moddate, cterms_moddate;

				this->connbas->selectPref_moddates(&struct_moddate, &thesaurus_moddate, &cterms_moddate);

				thesaurusChanged = (thesaurus_moddate > this->current_thesaurus_moddate || cterms_moddate > this->current_cterms_moddate);

				if(!thesaurusChanged)
				{
					// thesaurus hasn't change
					if(!this->connbas->crashed && this->ctermsChanged)
					{
						// cterms has changed, let's save
						saveCterms(this);
						this->ctermsChanged = false;
					}
				}

				// --------------------------------------------------- flush thit
				CTHit *th;
				while(!this->connbas->crashed && (th = this->firstTHit) )
				{
					if(!thesaurusChanged)
					{
						// if the th/ct hasn't chnaged, we can flush thits
						this->connbas->insertTHit(th->record_id, th->pxpath->id, th->pxpath->field->name, th->value, th->hitstart, th->hitlen);
					}
					this->firstTHit = th->next;
					delete th;
				}

				// we can unlock
				this->connbas->unlockTables();

				// flag records 'to-reindex-thesaurus'
				if(!this->connbas->crashed && thesaurusChanged)
				{
					// this->connbas->execute(ibuf, pibuf-ibuf);
					// _FREE(ibuf);
					this->connbas->setRecordsToReindexTh2(lrids_buff, lrids_len);
				}
			}

			// --------------------------------------------------- flush prop
			CProp *p;
			while(!this->connbas->crashed && (p = this->firstProp) )
			{
				this->connbas->insertProp(p->record_id, p->pxpath->id, p->pxpath->field->uname, p->value, p->type);

				this->firstProp = p->next;
				delete p;
			}


			// --------------------------------------------------- flush kword and idx
			unsigned long kword_new_uid = 0;
			if(this->nNewKeywords > 0)
				kword_new_uid = this->connbas->getID("KEYWORDS", this->nNewKeywords);
			
			CKword *k;
			CHit *h;

			for(int hash=0; !this->connbas->crashed && (hash<KWORD_HASHSIZE); hash++)
			{
				for(k=this->tKeywords[hash]; !this->connbas->crashed && k; k=k->next)
				{
					// save new kwords
					if(k->id == 0)
					{
						k->id = kword_new_uid++;
						int r;
						if((r = this->connbas->insertKword(k->kword, k->l, &(k->id))) == 0)
						{
							// ok : we have created the kword, or if it was existing the id is returned in k->id
						}
						else
						{
						}
					}

		//			printf("indexing kword '%s' (id=%ld) : ", k->kword, k->id);

					// on save les hits
					if(k->id > 0)
					{
						while(!this->connbas->crashed && (h = k->firsthit) )
						{
							this->connbas->insertIdx(h->record_id, k->id, h->index, h->pxpath->id, h->pos, h->len);
		//					printf("[%ld, %ld, %ld, %ld] ", h->record_id, h->pos, h->len, h->index);
		//					putchar('.');
							k->firsthit = h->next;
							delete h;
						}
					}
		//			putchar('\n');
				}
			}
			this->nNewKeywords = 0;

			// flag the records as 'indexed' (status-bit 2,1,0 to '1')
			this->connbas->updateRecord_unlock2(lrids_buff, lrids_len);

//			zSyslog.log(CSyslog::LOG_INFO, "| %d records flushed", nrecs_flushed);

			this->nrecsIndexed += nrecs_flushed;

			_FREE(lrids_buff);
		}
		zSyslog.log(CSyslog::LOGL_INFO, CSyslog::LOGC_FLUSH, "#%d : %d records flushed", this->connbas->sbas_id, nrecs_flushed);
	}
}
