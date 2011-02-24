#ifndef INDEXER_INCLUDED
#define INDEXER_INCLUDED 1

#define KWORD_HASHSIZE 2048
#define KWORD_HASHLMAX 15		// l & 15 : 15 premiers c


#include <stdio.h>
#include <stdlib.h>

#ifdef WIN32
# include <basetsd.h>
#endif

#include <libxml/tree.h>
#include <libxml/parser.h>
#include <libxml/xpath.h>
#include <libxml/xpathInternals.h>

#if defined(LIBXML_XPATH_ENABLED) && defined(LIBXML_SAX1_ENABLED) && defined(LIBXML_OUTPUT_ENABLED)
#else
	#error XPath support not compiled in libxml
#endif


#include "consts.h"
#include "phrasea_clock_t.h"
#include "dom.h"
#include "connbas_dbox.h"

// inline
unsigned int hashKword(const char *s, int l);

extern int debug_flag;
// xmlChar *my_xmlGetProp(xmlNodePtr node, const xmlChar *p);

class CtidSet
{
	private:
		xmlChar *prop;
		unsigned char found;
	public:
		int idNr;
		char **idTab;
		CtidSet()
		{
			this->idNr = 0;
			this->idTab = NULL;
		}
		~CtidSet()
		{
			if(this->idTab)
			{
				for(int i=0; i<this->idNr; i++)
				{
					if(this->idTab[i])
						_FREE(this->idTab[i]);
				}
				_FREE(this->idTab);
			}
		}
		// recursive search of nodes "sy" by w and k;
		// if w is null, the node must not have w attribute ( [not(@w)] in xquery )
		// else the w attribute must exist and be equal to w ( [@w='..w..'] in xquery )
		// same thing for k.
		void find(xmlNodePtr node, char *w, char *k, int depth=0)
		{
			if(node->name && strcmp((const char *)(node->name), "sy")==0)
			{
				this->found=1;

				// compare w
				this->prop = xmlGetProp(node, (const xmlChar *)"w");
				if((w && !this->prop) || (!w && this->prop))
					this->found = false;
				if(this->found && this->prop)
				{
					if(strcmp((const char *)(this->prop), (const char *)w) != 0)
						this->found = 0;
				}
				if(this->prop)
					xmlFree(this->prop);

				// compare k if still needed
				if(this->found)
				{
					this->prop = xmlGetProp(node, (const xmlChar *)"k");
					if((k && !this->prop) || (!k && this->prop))
						this->found = false;
					if(this->found && this->prop)
					{
						if(strcmp((const char *)(this->prop), (const char *)k) != 0)
							this->found = 0;
					}
					if(this->prop)
						xmlFree(this->prop);
				}

				// if the node match, get his id
				if(this->found && (this->prop = xmlGetProp(node, (const xmlChar *)"id")))
				{
					// realloc the table of results;
					if( (this->idTab = (char **)_REALLOC(this->idTab, (this->idNr+1)*sizeof(char *)) ) )
					{
						int l = strlen((const char *)(this->prop))+1;
						if( (this->idTab[this->idNr] = (char *)_MALLOC_WHY(l, "indexer.h:find:idTab[]")) )
							memcpy(this->idTab[this->idNr], this->prop, l);
						this->idNr++;
					}
					xmlFree(this->prop);
				}
				// no recurs after sy
			}
			else
			{
				// recurs
				for(xmlNodePtr n = node->children; n ; n = n->next)
					this->find(n, w, k, depth+1);
			}
		}
};



// --------------------------------------------------------------
// object describing a field of the structure
//  TABLE in CIndexer
// --------------------------------------------------------------
class CStructField
{
	public:
		char *fullpath;	// path as /record/description/MotsCles
		char *name;		// name of the field, ex:MotsCles
		char *uname;		// name of the field, upercase, ex:MOTSCLES
		enum { TYPE_NONE=0, TYPE_TEXT, TYPE_INT, TYPE_FLOAT, TYPE_DATE };
		int type;					// type of the field as enum inside known types 'text', 'number', 'date'
		bool index;
		char *tbranch;				// attribut as /thesaurus/te[@id='T4'] | /thesaurus/te[@id='T6'] ; NULL if not set
		char *cbranch;				// attribut as /cterms/te[@field='MotsCles'] ; NULL if not set

		xmlXPathContextPtr *tXPathCtxThesaurus;	// TABLE of contexts (reachable branches from tbranch) to the thesaurus
		int nXPathCtxThesaurus;					// number of entries in this table

		xmlNodePtr *tNodesThesaurus;			// TABLE of nodes (reachable branches from tbranch) to the thesaurus
		int nNodesThesaurus;					// number of entries in this table

		xmlNodePtr xmlNodeCterms;				// node to the branch field='zname'
		xmlXPathContextPtr XPathCtxCterms;		// context to the branche field='zname'

		bool candidatesStrings;
		bool candidatesDates;
		bool candidatesIntegers;
		bool candidatesFirstDigit;
		bool candidatesMultiDigits;

		CStructField()
		{
			this->fullpath = NULL;
			this->name = this->uname = NULL;
			this->tbranch = NULL;
			this->cbranch = NULL;
			this->type = CStructField::TYPE_NONE;
			this->index = true;

			this->tXPathCtxThesaurus = NULL;
			this->nXPathCtxThesaurus = 0;

			this->xmlNodeCterms = NULL;
			this->XPathCtxCterms = NULL;

			this->candidatesStrings = this->candidatesDates = this->candidatesIntegers = this->candidatesFirstDigit = this->candidatesMultiDigits = true;
		};

		~CStructField()
		{
			if(this->fullpath)
				_FREE(this->fullpath);
			if(this->uname)
				_FREE(this->uname);
			if(this->cbranch)
				_FREE(this->cbranch);
			if(this->tbranch)
				_FREE(this->tbranch);
			if(this->tXPathCtxThesaurus)
			{
				for(int i=0; i<this->nXPathCtxThesaurus; i++)
				{
					if(this->tXPathCtxThesaurus[i])
						xmlXPathFreeContext(this->tXPathCtxThesaurus[i]);
				}
				delete [] (this->tXPathCtxThesaurus);
			}
			if(this->XPathCtxCterms)
			{
				xmlXPathFreeContext(this->XPathCtxCterms);
			}
		};

		void set(const char *path, const char *name, const char *tbranch)
		{
			size_t l, lp, ln;
			if( (this->fullpath = (char *)_MALLOC_WHY((lp=strlen((char *)path)) + (ln=strlen((char *)name)) + 1, "indexer.h:set:fullpath" )) )
			{
				memcpy(this->fullpath, path, lp);
				memcpy(this->name = this->fullpath+lp, (const char *)name, ln);
				this->fullpath[lp+ln] = '\0';
			}
			if( (this->uname = (char *)_MALLOC_WHY(ln+1, "indexer.h:set:uname")) )
			{
				memcpy(this->uname, name, ln+1);
				_strupr((char *)(this->uname));
			}

			if(tbranch && (this->tbranch = (char *)_MALLOC_WHY(l = strlen((const char *)tbranch)+1, "indexer.h:set:tbranch")))
				memcpy(this->tbranch, tbranch, l);

			if(tbranch && (this->cbranch = (char *)_MALLOC_WHY(19 + ln + 2 + 1, "indexer.h:set:cbranch")))
			{
				memcpy(this->cbranch,       "/cterms/te[@field='", 19);
				memcpy(this->cbranch+19,    name,                  ln);
				memcpy(this->cbranch+19+ln, "']",                  2 + 1);
			}
		};
};


// --------------------------------------------------------------
// objet describing a record in the table 'xpath'
//  chained list in CIndexer
// --------------------------------------------------------------
class CXPath
{
	public:
		// ----- fields of the table ---
		unsigned int id;		// id or 0 if unknown ; sql=xpath.xpath_id
		char *upath;	// path as /RECORD[0]/DESCRIPTION[0]/MOTSCLES[5] ; sql=xpath.xpath
		// -----------------------------

		CStructField *field;	// ptr vers le tableau de la structure
		CXPath *next;

		CXPath(const char *up, unsigned long l)
		{
			if( (this->upath = (char *)_MALLOC_WHY(l+1, "indexer.h:CXPath:upath")) )
			{
				memcpy(this->upath, up, l+1);
				if(debug_flag & DEBUG_ALLOC)
					printf("%s(%d) new CXPath('%s', %ld)\n", __FILE__, __LINE__, this->upath, l);
			}
			else
			{
				if(debug_flag & DEBUG_ALLOC)
				{
					printf("%s(%d) MALLOC ERROR CXPath('%s')\n", __FILE__, __LINE__, up);
				}
			}
			this->id = 0;
			this->next = NULL;
			this->field = NULL;
		}

		~CXPath()
		{
			if(this->upath)
				_FREE(this->upath);
		}
};


// --------------------------------------------------------------
// objet describing a record in the table 'thit'
//  chained list in CIndexer
// --------------------------------------------------------------
class CTHit
{
	public:
		// ----- fields of the table ---
		unsigned int record_id;				// record_id
		char *value;				// value du genre T2d56d7d1d
		unsigned int hitstart;				// hitstart
		unsigned int hitlen;				// hitlen
		// ----------------------------

		CXPath *pxpath;				// ptr vers un cxpath : donne le xpath_id et le name par indirection cxpath->field

		class CTHit *next;
		CTHit(const char *v)
		{
			size_t l;
			if( (this->value = (char *)_MALLOC_WHY(l = strlen((const char *)v)+2, "indexer.h:CTHit:CTHit")) )
			{
				unsigned int i;
				for(i=0; i<l-2; i++)
				{
					if(	(this->value[i] = v[i]) == '.')
						this->value[i] = 'd';
				}
				this->value[i++] = 'd';
				this->value[i++] = '\0';
			}
			this->pxpath = NULL;
			this->next = NULL;
		}
		~CTHit()
		{
			if(this->value)
				_FREE(this->value);
		}
};


// --------------------------------------------------------------
// objet describing a record in the table 'idx'
//  chained list in CKword (idx for this kword)
// --------------------------------------------------------------
class CHit
{
	public:
		// ----- fields of the table ---
		unsigned int record_id;		// record_id
		unsigned int index;			// iw (index of the kword in xml)
		unsigned int pos;			// hitstart
		unsigned int len;			// hitlen
		// ----------------------------

		CXPath *pxpath;		// ptr to the xpath, allow to get the xpath_id

		CHit *next;
		CHit(unsigned int record_id, unsigned int pos, unsigned int len, unsigned int index)
		{
			this->record_id = record_id;
			this->pos       = pos;
			this->len       = len;
			this->index     = index;
			this->next      = NULL;
			this->pxpath    = NULL;
		};
};


// --------------------------------------------------------------
// objet describing a record in the table 'kword'
//  table of 1024 chained lists in CIndexer
// --------------------------------------------------------------
class CKword
{
	public:
		// ----- fields of the table ---
		unsigned int id;					// kword_id
		char *kword;				// keyword
		// ----------------------------

		unsigned long l;		// length of keyword, without the ending '\0'
		CHit *firsthit;			// list of hits for this kword
		CKword *next;

		CKword(const char *k, unsigned int l)
		{
			this->id = 0;
			this->l = 0;
			if( (this->kword = (char *)(malloc(l+1))) )
			{
				memcpy(this->kword, k, this->l=l);
				this->kword[l] = '\0';
				if(debug_flag & DEBUG_ALLOC)
					printf("%s(%d) new CKword('%s', %d)\n", __FILE__, __LINE__, this->kword, l);
			}
			else
			{
				if(debug_flag & DEBUG_ALLOC)
				{
					char buff[100];
					if(l>99)
						l=99;
					memcpy(buff, k, l);
					buff[l] = '\0';
					printf("%s(%d) MALLOC ERROR CKword('%s')\n", __FILE__, __LINE__, buff);
				}
			}
			this->firsthit = NULL;
			this->next = NULL;
		};
		~CKword()
		{
			if(this->kword)
			{
				// _FREE(this->kword);
				free(this->kword);
			}
			CHit *h;
			while( (h = this->firsthit) )
			{
				this->firsthit = h->next;
				delete h;
			}
		};
};


// --------------------------------------------------------------
// objet describing a record of the table 'prop'
//  chained list in CIndexer
// --------------------------------------------------------------
class CProp
{
	public:
		// ----- fields of the table ---
		unsigned int record_id;		// record_id
		int type;
		char *value;	// value
		// -----------------------------

		CXPath *pxpath;				// ptr to a cxpath : give the xpath_id and the name by indirection cxpath->field

		CProp *next;

		CProp(const char *v)
		{
			size_t l;
			if( (this->value = (char *)_MALLOC_WHY(l = strlen((char *)v)+1, "indexer.h:CProp:CProp")) )
				memcpy(this->value, v, l);
			this->pxpath = NULL;
			this->next = NULL;
		}

		~CProp()
		{
			if(this->value)
				_FREE(this->value);
		}
};


class CRecord
{
	public:
		unsigned int id;
		class CRecord *next;
		CRecord()
		{
			this->next = NULL;
		};
};


// --------------------------------------------------------------
//
//       the main object CIndexer
//
// --------------------------------------------------------------

class CIndexer
{
	public:
		int nrecsInBuff;

		CRecord *tRecord;					// chained list of records

		CConnbas_dbox *connbas;				// cnx to the base
		CDOMDocument *xmlparser;			// parser

		int nStructFields;					// number of fields in the structure
		CStructField *tStructField;			// table of fields in the structure

		CKword *tKeywords[KWORD_HASHSIZE];	// chained list(s) of keywords
		unsigned long nNewKeywords;			// number of NEW (unknown id) keywords in this list

		CXPath *tXPaths;					// chained liste of xpath
		CXPath *current_xpath;
		
		unsigned int current_rid;			// current record_id
		unsigned long nrecsIndexed;			// nbr of records treated by this indexer


		// dates of the first changing of prefs
		bool firstLoad;							// force the loading of prefs at startup
		time_t current_struct_moddate;
		time_t current_thesaurus_moddate;
		time_t current_cterms_moddate;

		xmlDocPtr          DocThesaurus;		// thesaurus libxml
		xmlXPathContextPtr XPathCtx_thesaurus;	// thesaurus xpath 

		xmlDocPtr          DocCterms;			// cterms libxml
		xmlXPathContextPtr XPathCtx_cterms;		// cterms xpath 

		xmlXPathContextPtr XPathCtx_deleted;	// cterms/deleted xpath 

		xmlNodePtr xmlNodePtr_deleted;			// cterms/xpath node
		
		bool ctermsChanged;

		
		CTHit *firstTHit;

		CProp *firstProp;

		// propotypes

		CIndexer(CConnbas_dbox *connbas);
		~CIndexer();

		void flush();
};

#endif

