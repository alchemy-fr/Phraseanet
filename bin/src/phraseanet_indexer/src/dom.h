#ifndef DOM_INCLUDED
#define DOM_INCLUDED 1

#include <memory.h>
// #include <malloc.h>
#include <string.h>
#include <stdio.h>

#include <expat.h>

#include "trace_memory.h"

// #include <expat_external.h>

#ifdef XML_LARGE_SIZE  // Use large integers for file/stream positions.
#if defined(XML_USE_MSC_EXTENSIONS) && _MSC_VER < 1400
typedef __int64 XML_Index;
typedef unsigned __int64 XML_Size;
#else
typedef long long XML_Index;
typedef unsigned long long XML_Size;
#endif
#else
typedef long XML_Index;
typedef unsigned long XML_Size;
#endif // XML_LARGE_SIZE

//#include <libxml/tree.h>
//#include <libxml/parser.h>
//#include <libxml/xpath.h>
//#include <libxml/xpathInternals.h>

#include "lownodiacritics_utf8.h"

/*
#ifndef UINT8
	#define UINT8 unsigned char
#endif
#ifndef UINT6
	#define UINT16 unsigned short
#endif
#ifndef UINT32
	#define UINT32 unsigned int
#endif
*/

#ifndef NULL
	#define NULL 0
#endif
#ifndef FALSE
	#define FALSE 0
#endif
#ifndef TRUE
	#define TRUE 0
#endif

class CDOMNode		// abstract class
{
	public:
		enum {XML_UNKNOWN_NODE=0, XML_ELEMENT_NODE=1};
		CDOMNode()
		{
			this->nodeType = CDOMNode::XML_UNKNOWN_NODE;
			this->previousSibling = this->nextSibling = NULL;
		};
		~CDOMNode()
		{
			// printf("~DOMNode()\n");
		};
		virtual void dump(int depth=0) = 0;		// pure virtual

		int nodeType;
		class CDOMDocument *ownerDocument;
		class CDOMElement *parentNode;
		class CDOMNode *previousSibling;
		class CDOMNode *nextSibling;
};

class CDOMElement:public CDOMNode
{
	public:
		class CDOMNode *firstChild;
		class CDOMNode *lastChild;
		char *tagName;
		int index;
		int pathoffset;
		int upathoffset;

		char *lowValue;
		int lowValue_length;

		char *value;
		int value_length;
		int value_end;			// place for ending \0 (rtrim)

//		unsigned char *xvalue;
//		int xvalue_length;

		int index_start;
		int index_end;

//		unsigned char *xqvalue;
//		int xqvalue_length;

//		unsigned char *xqkontext;
//		int xqkontext_length;

		int t0, t1, k0, k1;
		
		int ink;

		unsigned char lastFlags;
		// int openpar, closepar;

		class CStructField *field;		// ptr to the field in the structure

		CDOMElement(const char *s, class CDOMDocument *owner)
		{
			int l;
			this->ownerDocument = owner;
			this->firstChild = this->lastChild = NULL;
			this->nodeType = CDOMNode::XML_ELEMENT_NODE;
			if( (this->tagName = (char *)_MALLOC_WHY(l = strlen(s)+1, "dom.h:CDOMElement:tagName")) )
				memcpy(this->tagName, s, l);
			//if( !(this->value = (char*)(_MALLOC(this->value_buffer_size = 8))) )
			
			this->lowValue = NULL;
			this->lowValue_buffer_size = 0;
			this->lowValue_length = 0;

			this->value = NULL;
			this->value_buffer_size = 0;
			this->value_length = 0;
			this->value_end = 0;

//			this->xvalue = NULL;
//			this->xvalue_buffer_size = 0;
//			this->xvalue_length = 0;
			
//			this->xqvalue = NULL;
//			this->xqvalue_buffer_size = 0;
//			this->xqvalue_length = 0;
			
//			this->xqkontext = NULL;
//			this->xqkontext_buffer_size = 0;
//			this->xqkontext_length = 0;
			
			this->index = 0;
			this->pathoffset = 0;

			this->field = NULL;

			this->index_start = this->index_end = 0;

			this->t0 = this->t1 = this->k0 = this->k1 = -1;
			
			this->ink = 0;

			this->lastFlags = CFLAG_ENDCHAR;
		};
		~CDOMElement()
		{
			// printf("~DOMElement(%s)\n", this->tagName);
			CDOMNode *n;
			while( (n = this->firstChild) )
			{
				this->firstChild = n->nextSibling;
				switch(n->nodeType)
				{
					case CDOMNode::XML_ELEMENT_NODE:
						delete ((CDOMElement *)n);
						break;
				}
			}
			if(this->tagName)
				_FREE(this->tagName);

			if(this->lowValue)
				_FREE(this->lowValue);

			if(this->value)
				_FREE(this->value);

//			if(this->xvalue)
//				_FREE(this->xvalue);

//			if(this->xqvalue)
//				_FREE(this->xqvalue);
//			if(this->xqkontext)
//				_FREE(this->xqkontext);
		};

		void dump(int depth=0)
		{
			int i;
			for(i=0; i<depth; i++)
				putchar('\t');
			if(!this->firstChild)
			{
				printf("<%s/> [%d]\n", this->tagName, this->index);
			}
			else
			{
				CDOMNode *n;
				printf("<%s> [%d]\n", this->tagName, this->index);
				for(n=this->firstChild; n; n=n->nextSibling)
					n->dump(depth+1);
				for(i=0; i<depth; i++)
					putchar('\t');
				printf("</%s>\n", this->tagName);
			}
		};

		// add a byte to the 'value' of the current field
		void addValueC(char c, unsigned char flags)
		{
			// increase size anyway
			if(this->value_length+1 >= this->value_buffer_size)
				this->value = (char *)_REALLOC(this->value, (this->value_buffer_size+=128)) ;

			if(c == '\0')
			{
				// special case : sent by the end of the tag
				this->value[this->value_length = this->value_end] = '\0';	// rtrim
// printf("addvalue(c=0x%02x, flags=0x%02x) length=%i \n", c, flags, this->value_length);
				return;
			}

			// left trim
			if(this->value_length == 0 && flags & CFLAG_SPACECHAR)
				return;

			if(this->value)
			{
				this->value[this->value_length++] = c;
				if(!(flags & CFLAG_SPACECHAR))
					this->value_end = this->value_length;
			}
// printf("addvalue(c=0x%02x, flags=0x%02x) length=%i \n", c, flags, this->value_length);
		}


		// add a byte to the 'lowed value' of the current field
		void addLowValueC(char c, unsigned char flags)
		{
			if(this->lowValue_length+1 >= this->lowValue_buffer_size)
				this->lowValue = (char *)_REALLOC(this->lowValue, (this->lowValue_buffer_size+=128)) ;

			if(c == '\0')
			{
				// special case : sent by the end of the tag
				if(this->lastFlags & CFLAG_ENDCHAR && this->lowValue_length > 0)
				{
					// last char was a endchar : delete it
					this->lowValue[this->lowValue_length-1] = '\0';
				}
				else
				{
					// last char was ok (or value is empty) : add nul at end
					this->lowValue[this->lowValue_length++] = '\0';
				}
				return;
			}

//			if(this->xvalue_length+1 >= this->xvalue_buffer_size)
//				this->xvalue = (unsigned char *)realloc(this->xvalue, (this->xvalue_buffer_size+=128)) ;

			if(this->ink==0)
			{
				if(c=='(')
					this->ink = 1;
			}
			else
			{
				if(this->ink==1)
				{
					if(c==')')
						this->ink = 2;
				}
			}

			if(flags & CFLAG_ENDCHAR)
			{
				if(this->lastFlags & CFLAG_ENDCHAR)
				{
					// it's a nth endchar : ignore it
				}
				else
				{
					// it's the first endchar : replace it by space
					if(this->lowValue)
						this->lowValue[this->lowValue_length++] = ' ';
				}
			}
			else
			{
				// normal char
				if(this->ink==0)
				{
					if(this->t0 == -1)
						this->t0 = this->lowValue_length;
					this->t1 = this->lowValue_length;
				}
				else if(this->ink==1)
				{
					if(this->k0 == -1)
						this->k0 = this->lowValue_length;
					this->k1 = this->lowValue_length;
				}
				if(this->lowValue)
					this->lowValue[this->lowValue_length++] = c;
			}

			this->lastFlags = flags;
		};
	private:
		int lowValue_buffer_size;
		int value_buffer_size;
};

class CDOMDocument
{
	public:
		CDOMDocument();
		~CDOMDocument();
		CDOMElement *documentElement;
		bool load(char *filename);
		bool loadXML(char *xml, unsigned long len);
		void dump();
		void (*onKeyword)(CDOMDocument *xmlparser, const char *lowKeyword, unsigned int lowKeywordLen, unsigned int pos, unsigned int len, unsigned int idx);
		void (*onStart)(CDOMDocument *xmlparser, const char *name, const char *path, const char *upath);
		void (*onEnd)(CDOMDocument *xmlparser);
		void *userData;	// to pass/get data to/from callback
		CDOMElement *createElement(const char *name)
		{
			return(new CDOMElement(name, this));
		};
		char *path;
		char *upath;

		bool parseText;
		bool getContent;

		CDOMElement *currentNode;
	private:
		XML_Parser parser;
		static void   start(void *userData, const char *el, const char **attr);
		static void XMLCALL end(void *userData, const char *el);
		static void XMLCALL charHandler(void *userData, const XML_Char *xmls, int len);
		static void XMLCALL startCdata(void *userData);
		static void XMLCALL endCdata(void *userData);

		int depth;
		enum { INTO_UNKNOWN=0, INTO_TEXT, INTO_CDATA };
		int State;

		// malloc size of path and upath
		int path_msize;
		int upath_msize;

		int freepathoffset;
		int freeupathoffset;

		unsigned int indexStart;
		unsigned int indexEnd;
		unsigned int wordIndex;
		char tokBin[400+4];
		int tokBinLen;

		char lowtokBin[400+4];
		int lowtokBinLen;

//		unsigned char charFlags[256];
		void flushToken();
};

#endif

