// #include <string.h>
#include <stdio.h>
// #include <memory.h>
#include <expat.h>

// les headers mysql
#include <config-win.h>
#include <mysql.h>

#ifdef XML_LARGE_SIZE
#if defined(XML_USE_MSC_EXTENSIONS) && _MSC_VER < 1400
#define XML_FMT_INT_MOD "I64"
#else
#define XML_FMT_INT_MOD "ll"
#endif
#else
#define XML_FMT_INT_MOD "l"
#endif


#ifndef UINT8
	#define UINT8 unsigned char
#endif
#ifndef UINT6
	#define UINT16 unsigned short
#endif
#ifndef UINT32
	#define UINT32 unsigned long
#endif

#include "lownodiacritics_utf8.c"




typedef enum { INTO_UNKNOWN=0, INTO_TEXT, INTO_CDATA } STATE;

int Depth;
STATE State = INTO_UNKNOWN;


UINT32 indexStart = 0;
UINT32 indexEnd = 0;
unsigned char tokBin[400+4];
int tokBinLen = 0;

unsigned char lowtokBin[400+4];
int lowtokBinLen = 0;




unsigned char charFlags[256];
void setCharFlags()
{
//	unsigned char t[] = "\t\r\n !\"#$%&'()+,-./:;<=>@[\\]^_`{|}~£§¨°" ;
	unsigned char t[] = "\t\r\n !\"#$%&'()+,-./:;=@[\\]^_`{|}~£§¨°" ;
	size_t i;
	for(i=0; i<strlen(t); i++)
	{
		charFlags[t[i]] |= 1;
	}
}

void flushToken()
{
  unsigned char c, outc;
  int i;
	printf("flushToken [0x%04X - 0x%04X : l=%d] [binlen=%-2d] : ", indexStart, indexEnd, indexEnd-indexStart, tokBinLen);
	for(i=0; i<tokBinLen; i++)
	{
		outc = (c=tokBin[i]) < 32 ? '.' : tokBin[i];
		putchar(outc);
	}
	putchar('\n');
	for(i=0; i<lowtokBinLen; i++)
	{
		outc = (c=lowtokBin[i]) < 32 ? '.' : lowtokBin[i];
		putchar(outc);
	}
	putchar('\n');
}

/*
static void XMLCALL defaultHandler(void *userData, const XML_Char *s, int len)
{
  int i;
  XML_Parser *p = (XML_Parser *)(userData);
  XML_Index o = XML_GetCurrentByteIndex(*p);

	printf("Default @ 0x%X (len=%d) :\n", o, len);
	for(i=0; i<len; i++)
		printf("%-3d : '%c' (0x%02X)\n", i, s[i], s[i]);
	getchar();
}
*/
static void XMLCALL charHandler(void *userData, const XML_Char *xmls, int len)
{
  XML_Parser *p = (XML_Parser *)(userData);
  XML_Index index = XML_GetCurrentByteIndex(*p);
	if(State != INTO_CDATA)
	{
	  int i;
	  unsigned char c0, c;
//	  UINT32 u, c32, noacc_c32, msk;
	  UINT32 u, msk;
	  unsigned char n;
	  unsigned char *s = (unsigned char *)xmls;
//	  unsigned char *indexStart;
//	  UINT32 indexEnd;
	  unsigned char cbreak;
//	  static unsigned char noacc_
	  register unsigned char *p;
/*
	  unsigned char outc;
		printf("charHandler @0x%04X  [len=%d] :\n", index, len);
		for(i=0; i<len; i++)
		{
			outc = (c=s[i]) < 32 ? '.' : s[i];
			putchar(c);
		}
		putchar('\n');
*/
		i = 0;
//		indexStart = s;
		while(len)
		{
		//	indexEnd = index+i;
			if(indexStart == 0)
				indexStart = index+i;

			cbreak = 0;
			u = 0xFFFFFFFF;
			c0 = *s++;
			len--;
			i++;
			if(c0 & 0x80)
			{
				if(c0 & 0x40)
				{
					// 11xxxxxx
					tokBin[tokBinLen++] = c0;
					u = ((UINT32) c0) & 0x0000007F;
					msk = 0xFFFFFF7F;
					n = 1;
					while(len && ((c0 <<= 1) & 0x80) && (((c = *s) & 0xC0) == 0x80) && ++n <= 4)
					{
						tokBin[tokBinLen++] = c;
						u = u<<6 & (msk = (msk<<5) | 0x1F) | (UINT32)(c & 0x3F);
						len--;
						i++;
						s++;
					}
					if(n <= 4)
					{
						if(u >= 0x0080 && u<=0x07FF)
						{
							for(p = cmap_2[u - 0x0080]; *p; lowtokBin[lowtokBinLen++]=*p++)
								;
						}
						else
						{
							lowtokBin[lowtokBinLen++] = '?';
						}
					}
					else
					{
						u = 0xFFFFFFFF;
					}
				}
				else
				{
					// 10xxxxxx
					// caractère interdit en c0
				}
			}
			else
			{
				// 0xxxxxxx
				tokBin[tokBinLen++] = c0;
				lowtokBin[lowtokBinLen++] = cmap_1[c0];
				u = (UINT32) c0;
				n = 1;

				if(charFlags[c0] & 1)	// caractère de rupture
					cbreak = 1;
			}
			// printf("got U+%06X\n", u);

			indexEnd = index+i;


			if(cbreak || tokBinLen>=400)	// caractère de rupture ou buffer plein
			{
				if(cbreak)
				{
					tokBinLen -= n;	// on décompte le caractère de rupture
					lowtokBinLen -= 1;	// on décompte le caractère de rupture
				}
				if(tokBinLen > 0)
				{
					if(cbreak)
						indexEnd -= n;	// on décompte le caractère de rupture
					flushToken();
				}
				lowtokBinLen = tokBinLen = 0;
				indexStart = 0;
			}
			else	// caractère normal
			{
			}
		}
	}
}

static void XMLCALL start(void *data, const char *el, const char **attr)
{
/*
  int i;
	for (i = 0; i < Depth; i++)
		printf("  ");

	printf("<%s", el);

	for (i = 0; attr[i]; i += 2)
	{
		printf(" %s='%s'", attr[i], attr[i + 1]);
	}
	printf(">\n");
*/

	Depth++;

	tokBinLen = 0;
	indexStart = 0;
}

static void XMLCALL end(void *data, const char *el)
{
	if(tokBinLen > 0)
	{
		flushToken();
	}
	lowtokBinLen = tokBinLen = 0;
	indexStart = 0;
	Depth--;
}

static void XMLCALL startCdata(void *data)
{
	State = INTO_CDATA;
}

static void XMLCALL endCdata(void *data)
{
	State = INTO_UNKNOWN;
}


char *files[] =
{
	"E:\\projets\\libbxml_expat_etc\\Expat-2.0.0\\Source\\examples\\Debug\\x.xml",
	"E:\\projets\\libbxml_expat_etc\\Expat-2.0.0\\Source\\examples\\Debug\\test.xml",
	"E:\\projets\\libbxml_expat_etc\\Expat-2.0.0\\Source\\examples\\Debug\\10929_06.xml"
};

#ifdef AMIGA_SHARED_LIB
	#include <proto/expat.h>
	int amiga_main(int argc, char *argv[])
#else
	int main(int argc, char *argv[])
#endif
{
  XML_Parser p = XML_ParserCreate(NULL);
  FILE *fp;
	if (! p)
	{
		fprintf(stderr, "Couldn't allocate memory for parser\n");
		exit(-1);
	}

	// XML_UseParserAsHandlerArg(p);
	XML_SetUserData(p, (void *)(&p));

	XML_SetCdataSectionHandler(p, startCdata, endCdata);

	XML_SetElementHandler(p, start, end);

	XML_SetCharacterDataHandler(p, charHandler);

//	XML_ExternalEntityRefHandler(p, entityHandler);

//	XML_SetDefaultHandler(p, defaultHandler);

//	XML_SetParamEntityParsing(p, XML_PARAM_ENTITY_PARSING_NEVER);
//	XML_SetParamEntityParsing(p, XML_PARAM_ENTITY_PARSING_UNLESS_STANDALONE);
	XML_SetParamEntityParsing(p, XML_PARAM_ENTITY_PARSING_ALWAYS);

	if( fp=fopen(files[2], "rb" ) )
	{
		long filesize;
		int bytes_read;
		void *buff;

		setCharFlags();		// charge la table des caractères délimiteurs

		fseek(fp, 0, SEEK_END);
		filesize = ftell(fp);
		rewind(fp);

		buff = XML_GetBuffer(p, filesize);

		if (buff == NULL)
		{
			/* handle error */
			fclose(fp);
			exit(-1);
		}

		bytes_read = fread(buff, 1, filesize, fp);
		if (bytes_read < 0)
		{
			/* handle error */
			fclose(fp);
			exit(-1);
		}

		if(! XML_ParseBuffer(p, bytes_read, bytes_read == XML_STATUS_ERROR))
		{
			/* handle parse error */
			fprintf(stderr, "Parse error at line %" XML_FMT_INT_MOD "u:\n%s\n",
              XML_GetCurrentLineNumber(p),
              XML_ErrorString(XML_GetErrorCode(p)));
			fclose(fp);

			printf("This is the end, press ENTER.");
			getchar();
			exit(-1);
		}
		fclose(fp);
	}

	getchar();

	exit(0);
}
