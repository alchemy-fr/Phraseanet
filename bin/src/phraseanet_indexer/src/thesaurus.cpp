#include <time.h>
#include <stdio.h>
#include <sys/types.h>
#include <sys/timeb.h>
#include <string.h>
#include "_syslog.h"


#include "indexer.h"

//extern time_t current_struct_moddate;
//extern time_t current_thesaurus_moddate;
//extern time_t current_cterms_moddate;

extern CSyslog zSyslog;

/*
tidSetPtr findTids(xmlNodePtr root, unsigned char *w, unsigned char *k, int depth)
{
	return(NULL);
}
*/

bool isno(const char *v)
{
	static char buff[6];
	register int i;
	for(i=0; i<6 && *v; i++, v++)
		buff[i] = (*v>='A' && *v<='Z') ? (*v-'A')+'a' : *v;
	buff[i] = '\0';
	return((i==1 && (*buff=='0' || *buff=='n')) || strcmp(buff, "no")==0 || strcmp(buff, "non")==0  || strcmp(buff, "off")==0 || strcmp(buff, "false")==0);
}

void saveCterms(CIndexer *indexer)
{
  CConnbas_dbox *connbas = indexer->connbas;
	xmlNodePtr root = xmlDocGetRootElement(indexer->DocCterms);
	char moddate[16];
	
	// date de m�j
	time_t timer;
	time(&timer);
	struct tm *today;
	today = localtime(&timer);

	strftime((char *)moddate, 15, "%Y%m%d%H%M%S", today);

	xmlSetProp(root, (const xmlChar*)"modification_date", (const xmlChar *)moddate );

	xmlChar *out;
	int outsize;
	xmlKeepBlanksDefault(0);
	xmlDocDumpFormatMemory(indexer->DocCterms, &out, &outsize, 1);

//	zSyslog.log(CSyslog::LOG_INFO, "\n/=================================== Saving CTERMS =========================");
//	zSyslog.log(CSyslog::LOG_INFO, "root = %s", root->name);
//	for(int i=0; i<outsize && i<300; i++)
//	{
//		putchar(out[i]);
//	}
	zSyslog.log(CSyslog::LOGL_INFO, CSyslog::LOGC_THESAURUS, "#%d : CTERMS saved", connbas->sbas_id);

	connbas->updatePref_cterms((char *)out, outsize, moddate );

	xmlFree(out);

	indexer->current_cterms_moddate = timer;
}

void loadThesaurus(CIndexer *indexer)
{
  CConnbas_dbox *connbas = indexer->connbas;
  time_t struct_moddate, thesaurus_moddate, cterms_moddate;

  // ----------------------- load structure and thesaurus
  char *xmlstruct;
  char **pxmlstruct = NULL;
  unsigned long  xmlstruct_length;

  char *xmlthesaurus;
  char **pxmlthesaurus = NULL;
  unsigned long  xmlthesaurus_length;

  char *xmlcterms;
  char **pxmlcterms = NULL;
  unsigned long  xmlcterms_length;

  bool struct_changed, thesaurus_changed, cterms_changed;

  extern int debug_flag;
//printf("loadThesaurus ? \n");
	// read the 3 moddates
	connbas->selectPref_moddates(&struct_moddate, &thesaurus_moddate, &cterms_moddate);

	// what has changed
	struct_changed    = indexer->firstLoad || (struct_moddate    > indexer->current_struct_moddate);
	thesaurus_changed = indexer->firstLoad || (thesaurus_moddate > indexer->current_thesaurus_moddate);
	cterms_changed    = indexer->firstLoad || (cterms_moddate    > indexer->current_cterms_moddate);

	indexer->firstLoad = false;	

	if(!struct_changed && !thesaurus_changed && !cterms_changed)
	{
		// nothing changed in the prefs
		return;
	}

	if(struct_changed)
	{
		// the structure changed : reload
		pxmlstruct    = &xmlstruct;
	}
	if(thesaurus_changed)
	{
		// the thesaurus changed
		pxmlthesaurus = &xmlthesaurus;
	}
	if(cterms_changed)
	{
		// the cterms changed
		pxmlcterms    = &xmlcterms;
	}

	// read useful fields
	if(connbas->selectPrefs(pxmlstruct, &xmlstruct_length, pxmlthesaurus, &xmlthesaurus_length, pxmlcterms, &xmlcterms_length) != 0)
	{
		// erreur sql
		return;
	}


	// ============================ load thesaurus
	if(thesaurus_changed)
	{
		if(indexer->DocThesaurus)
		{
			xmlFreeDoc(indexer->DocThesaurus);
			indexer->DocThesaurus = NULL;
		}

		if(indexer->XPathCtx_thesaurus)
		{
			xmlXPathFreeContext(indexer->XPathCtx_thesaurus);
			indexer->XPathCtx_thesaurus = NULL;
		}

		// we have the thesaurus, load in libxml
		indexer->DocThesaurus = xmlParseMemory(xmlthesaurus, xmlthesaurus_length);
		if(indexer->DocThesaurus != NULL)
		{
			// Create xpath evaluation context 
			indexer->XPathCtx_thesaurus = xmlXPathNewContext(indexer->DocThesaurus);
			if(indexer->XPathCtx_thesaurus != NULL)
			{
			}
		}
		zSyslog.log(CSyslog::LOGL_INFO, CSyslog::LOGC_THESAURUS, "#%ld : thesaurus loaded", connbas->sbas_id);
	}


	// ============================ load cterms
	if(cterms_changed)
	{
		if(indexer->DocCterms)
		{
			xmlFreeDoc(indexer->DocCterms);
			indexer->DocCterms = NULL;
		}

		if(indexer->XPathCtx_cterms)
		{
			xmlXPathFreeContext(indexer->XPathCtx_cterms);
			indexer->XPathCtx_cterms = NULL;
		}

		if(indexer->XPathCtx_deleted)
		{
			xmlXPathFreeContext(indexer->XPathCtx_deleted);
			indexer->XPathCtx_deleted = NULL;
		}
		indexer->xmlNodePtr_deleted = NULL;

		// we have the cterms, load in libxml
		indexer->DocCterms = xmlParseMemory(xmlcterms, xmlcterms_length);
		if(indexer->DocCterms != NULL)
		{
			// Create xpath evaluation context 
			indexer->XPathCtx_cterms = xmlXPathNewContext(indexer->DocCterms);
			if(indexer->XPathCtx_cterms != NULL)
			{

				xmlXPathObjectPtr  xpathObj_cterms = NULL; 

//				zSyslog.log(CSyslog::LOG_DEBUG, "|    searching tbranch ' /cterms/te[@delbranch='1'] ' in cterms");
				xpathObj_cterms = xmlXPathEvalExpression((const xmlChar*)("/cterms/te[@delbranch='1']"), indexer->XPathCtx_cterms);
				if(xpathObj_cterms)
				{
					if(xpathObj_cterms->nodesetval)
					{
						xmlNodeSetPtr nodes_cterms = xpathObj_cterms->nodesetval;

						if(nodes_cterms->nodeNr > 0)
						{
//							zSyslog.log(CSyslog::LOG_DEBUG, "|    -> found %d nodes (keeping the first)", nodes_cterms->nodeNr);
							xmlNodePtr node_cterms = nodes_cterms->nodeTab[0];
							indexer->XPathCtx_deleted = xmlXPathNewContext((xmlDocPtr)node_cterms);

							// in the indexer, we keep the node to the deleted
							indexer->xmlNodePtr_deleted = nodes_cterms->nodeTab[0];
						}
						else
						{
//							zSyslog.log(CSyslog::LOG_DEBUG, "|    -> found 0 node");
						}
					}
					xmlXPathFreeObject(xpathObj_cterms);
				}

			}
		}
		indexer->ctermsChanged = false;
		zSyslog.log(CSyslog::LOGL_DEBUG, CSyslog::LOGC_THESAURUS, "#%ld : cterms loaded", connbas->sbas_id);
	}


// printf(" 0 ------------------------\n");
	// ============================ load structure
	if(struct_changed)
	{
// printf(" 1 ------------------------\n");
		xmlDocPtr          doc_struct;
		xmlXPathContextPtr xpathCtx_struct; 
		xmlXPathObjectPtr  xpathObj_struct;

		if(indexer->tStructField)
		{
			delete [] (indexer->tStructField);
			indexer->tStructField = NULL;
		}

		// load in libxml
		doc_struct = xmlParseMemory(xmlstruct, xmlstruct_length);
		if(doc_struct != NULL)
		{
// printf(" 2 ------------------------\n");
			// Create xpath evaluation context
			xpathCtx_struct = xmlXPathNewContext(doc_struct);
			if(xpathCtx_struct != NULL)
			{
// printf(" 3 ------------------------\n");
				// ----- search every fields of the structure
				// Evaluate xpath expression 
				xpathObj_struct = xmlXPathEvalExpression((const xmlChar*)"/record/description/*", xpathCtx_struct);
				if(xpathObj_struct != NULL)
				{
// printf(" 4 ------------------------\n");
					if(xpathObj_struct->nodesetval)
					{
// printf(" 5 ------------------------\n");
						xmlNodeSetPtr nodes_struct = xpathObj_struct->nodesetval;

						indexer->nStructFields = nodes_struct->nodeNr;
						if(indexer->nStructFields > 0)
						{
							// allocate a TABLE of fields
							indexer->tStructField = new CStructField[indexer->nStructFields];
						}

						// ---- scan every nodes of the result on struct
						if(debug_flag)
							printf("/-------------------------------- Loading structure  -----\n");

						for(int i=0; i<indexer->nStructFields; i++)
						{
							xmlNodePtr node_struct = nodes_struct->nodeTab[i];

							if(debug_flag)
								printf("|  Field '%s' ", node_struct->name);

							// ---- get attribute 'type' if it exists
							xmlChar *type;
							if( (type = xmlGetProp(node_struct, (const xmlChar *)"type")) )
							{
								if(strcmp((const char *)type, "text")==0)
									indexer->tStructField[i].type = CStructField::TYPE_TEXT;	// <... type="text"

								else if(strcmp((const char *)type, "number")==0)
									indexer->tStructField[i].type = CStructField::TYPE_INT;		// <... type="number"

								else if(strcmp((const char *)type, "float")==0)
									indexer->tStructField[i].type = CStructField::TYPE_FLOAT;	// <... type="float"

								else if(strcmp((const char *)type, "date")==0)
									indexer->tStructField[i].type = CStructField::TYPE_DATE;	// <... type="date"

								if(debug_flag)
									printf(" { type='%s' (%d) }", type, indexer->tStructField[i].type );

								xmlFree(type);
							}

							// ---- get attribute 'escape' if it exists
							xmlChar *escape;
							if( (escape = xmlGetProp(node_struct, (const xmlChar *)"escape")) )
							{
								if(debug_flag)
									printf(" { escape='%s' }", escape );

								xmlFree(escape);
							}

							// ---- get attribute 'index' if it exists
							xmlChar *index;
							if( (index = xmlGetProp(node_struct, (const xmlChar *)"index")) )
							{
								if( isno((const char *)index) )
									indexer->tStructField[i].index = false;

								if(debug_flag)
									printf(" { index=%d }", indexer->tStructField[i].index );

								xmlFree(index);
							}

							if(debug_flag)
								putchar('\n');

							// ---- get attribute 'tbranch' if it exists
							xmlChar *tbranch;
							if( (tbranch = xmlGetProp(node_struct, (const xmlChar *)"tbranch")) )
							{
								// --- copy the full path into the field
								indexer->tStructField[i].set("/record/description/", (const char *)(node_struct->name), (const char *)tbranch);
								xmlFree(tbranch);
							}
							else
							{
								// no 'tbranch' attribute
								indexer->tStructField[i].set("/record/description/", (const char *)(node_struct->name), NULL);
							}

							// ---- get attribute 'candidates' if it exists
							xmlChar *candidates;
							if( (candidates = xmlGetProp(node_struct, (const xmlChar *)"candidates")) )
							{
								indexer->tStructField[i].candidatesStrings
									= indexer->tStructField[i].candidatesDates
									= indexer->tStructField[i].candidatesIntegers
									= indexer->tStructField[i].candidatesFirstDigit
									= indexer->tStructField[i].candidatesMultiDigits
									= false;
								for(char *p=(char*)candidates; *p; p++)
								{
									switch(*p)
									{
										case 'S':
										case 's':
											indexer->tStructField[i].candidatesStrings = true;
											break;
										case 'D':
										case 'd':
											indexer->tStructField[i].candidatesDates = true;
											break;
										case 'I':
										case 'i':
											indexer->tStructField[i].candidatesIntegers = true;
											break;
										case '0':
											indexer->tStructField[i].candidatesFirstDigit = true;
											break;
										case '9':
											indexer->tStructField[i].candidatesMultiDigits = true;
											break;
									}
								}
								xmlFree(candidates);
							}

							//	if (nodes_struct->nodeTab[i]->type != XML_NAMESPACE_DECL)
							//		nodes_struct->nodeTab[i] = NULL;

						} // FIN : boucle sur les nodes du result sur struc


						if(debug_flag)
							printf("\\-------------------------------- structure loaded  ------\n");


					} // FIN : if(xpathObj_struct->nodesetval)

					xmlXPathFreeObject(xpathObj_struct);

				} // FIN : if(xpathObj_struct != NULL)

				xmlXPathFreeContext(xpathCtx_struct); 

			} // FIN : if(xpathCtx_struct != NULL)
		}
	}


	// search branches pointed by the tbranch into the thesaurus

	if(debug_flag)
		printf("/-------------------------------- Linking fields to thesaurus  ----- \n");

	for(int i=0; i<indexer->nStructFields; i++)
	{
		if(indexer->tStructField[i].tbranch)
		{
			// this field has a tbranch, it's linked to the thesaurus
			if(debug_flag)
					printf("|  Field '%s'\n", indexer->tStructField[i].name);

			if(indexer->XPathCtx_thesaurus != NULL)
			{
				// build links to the thesaurus
				if(debug_flag)
					printf("|    searching tbranch ' %s ' in thesaurus \n", indexer->tStructField[i].tbranch);
									
				xmlXPathObjectPtr  xpathObj_thesaurus = NULL; 
				xpathObj_thesaurus = xmlXPathEvalExpression((const xmlChar*)(indexer->tStructField[i].tbranch), indexer->XPathCtx_thesaurus);
				if(xpathObj_thesaurus != NULL)
				{
					if(xpathObj_thesaurus->nodesetval)
					{
						xmlNodeSetPtr nodes_thesaurus = xpathObj_thesaurus->nodesetval;

						if(debug_flag)
							printf("|    -> found %d nodes \n", nodes_thesaurus->nodeNr);

						if(nodes_thesaurus->nodeNr > 0)
						{
							// in this field, allocate an array of xpathcontext
							indexer->tStructField[i].tXPathCtxThesaurus = new xmlXPathContextPtr[nodes_thesaurus->nodeNr];
							// in this field, allocate an array of nodes
							indexer->tStructField[i].tNodesThesaurus = new xmlNodePtr[nodes_thesaurus->nodeNr];

							if(indexer->tStructField[i].tXPathCtxThesaurus && indexer->tStructField[i].tNodesThesaurus)
							{
								indexer->tStructField[i].nXPathCtxThesaurus = nodes_thesaurus->nodeNr;
								indexer->tStructField[i].nNodesThesaurus    = nodes_thesaurus->nodeNr;
								for(int j=0; j<nodes_thesaurus->nodeNr; j++)
								{
									xmlNodePtr node_thesaurus = nodes_thesaurus->nodeTab[j];
									indexer->tStructField[i].tXPathCtxThesaurus[j] = xmlXPathNewContext((xmlDocPtr)node_thesaurus);
									indexer->tStructField[i].tNodesThesaurus[j]    = node_thesaurus;
								}
							}
						}
					}
					xmlXPathFreeObject(xpathObj_thesaurus);
				}
			}
		}

		if(indexer->tStructField[i].cbranch)
		{
			// this field has a cbranch: it's linked to cterms

			if(indexer->XPathCtx_cterms != NULL)
			{
				// build a link to cterms
				if(debug_flag)
					printf("|    searching cbranch ' %s ' in cterms \n", indexer->tStructField[i].cbranch);
									
				
				// check if cterms has a branch '...field='..zfname..'...
				xmlXPathObjectPtr  xpathObj_cterms = NULL; 

				xpathObj_cterms = xmlXPathEvalExpression((const xmlChar*)(indexer->tStructField[i].cbranch), indexer->XPathCtx_cterms);
				if(xpathObj_cterms != NULL)
				{
					if(!xpathObj_cterms->nodesetval || xpathObj_cterms->nodesetval->nodeNr == 0)
					{
						// the branch does not exists, create it

						if(debug_flag)
							printf("|    -> nodes not found, creating \n");

						xmlNodePtr root = xmlDocGetRootElement(indexer->DocCterms);

						// get nextid
						xmlChar *nextid;
						if( (nextid = xmlGetProp(root, (const xmlChar *)"nextid")) )
						{
							int l = strlen((const char *)nextid);
							if(l > 32)
								l = 32;
							xmlNodePtr te;
							if((te = xmlNewChild(root, NULL, (const xmlChar*)"te", NULL)) != NULL)
							{
								char ibuff[33];

								// prop 'id'
								ibuff[0] = 'C';
								memcpy(ibuff+1, nextid, l+1);
								xmlSetProp(te, (const xmlChar*)"id", (const xmlChar *)ibuff);

								// prop 'field'
								xmlSetProp(te, (const xmlChar*)"field", (const xmlChar *)(indexer->tStructField[i].name));

								// prop 'nextid'
								xmlSetProp(te, (const xmlChar*)"nextid", (const xmlChar *)"0");

								// inc nextid
								sprintf(ibuff, "%d", atoi((const char *)nextid) + 1);
								xmlSetProp(root, (const xmlChar*)"nextid", (const xmlChar *)ibuff );

								// put a xpathcontext into the field
								indexer->tStructField[i].xmlNodeCterms = te;
								indexer->tStructField[i].XPathCtxCterms = xmlXPathNewContext((xmlDocPtr)te);
							}
							xmlFree(nextid);

							time(&cterms_moddate);
						}
					}
					else
					{
						xmlNodeSetPtr nodes_cterms = xpathObj_cterms->nodesetval;

						if(debug_flag)
							printf("|    -> found %d nodes (keeping the first) \n", nodes_cterms->nodeNr);

						// in the field, keep the first xpathcontext
						indexer->tStructField[i].xmlNodeCterms = nodes_cterms->nodeTab[0];
						indexer->tStructField[i].XPathCtxCterms = xmlXPathNewContext((xmlDocPtr)(nodes_cterms->nodeTab[0]));
					}

					xmlXPathFreeObject(xpathObj_cterms);
				}
			}
		}
	}
	if(debug_flag)
		printf("\\-------------------------------- fields linked to thesaurus  ------ \n");



	// ------------------ end loading structure
	indexer->current_struct_moddate = struct_moddate;
	indexer->current_thesaurus_moddate = thesaurus_moddate;
	indexer->current_cterms_moddate = cterms_moddate;
}
