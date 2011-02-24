// #include "base_header.h"
#include "php.h"
#define RAMLOG "/home/gaulier/ramlog.txt"
#define K (sizeof(int))

int allocated = 0;

void tracelog(const char *s, const char *file, int line)
{
   FILE *fp;
	if(fp = fopen(RAMLOG, "a"))
	{
//		fprintf(fp, "%s (%i) : %s\n", file, line, s);
		fclose(fp);
	}
}

void *my_emalloc(int s, const char *file, int line)
{
  void *p, *p2;
  FILE *fp;
	p  = emalloc(K + s);
	p2 = (unsigned char *)p + K;
	if(p)
		*(int *)p = s;
	allocated += s;
	//	zend_printf("(%i):S=%i%s   p=%i  p2=%i\n", s, allocated, p?"":" ERROR ", p, p2);
	if(fp = fopen(RAMLOG, "a"))
	{
//		fprintf(fp, "%s (%i)	+%i	S=%i%s   p=0x%p  p2=0x%p\n", file, line, s, allocated, p?"":" ERROR ", p, p2);
		//fprintf(fp, "(%i):S=%i%s   p=0x%p  p2=0x%p\n", s, allocated, p?"":" ERROR ", p, p2);
		fclose(fp);
	}
	return(p2);
}

void my_efree(void *p2, const char *file, int line)
{
  int s=0;
  void *p = ((unsigned char*)p2) - K;
  FILE *fp;
	if(p2)
	{
		s = *((int *)p);
		allocated -= s;
		//		zend_printf("efree(%i):S=%i   p2=%i  p=%i\n", s, allocated, p2, p);
		if(fp = fopen(RAMLOG, "a"))
		{
//			fprintf(fp, "%s (%i)	-%i	S=%i	p2=0x%p	p=0x%p\n", file, line, s, allocated, p2, p);
			//fprintf(fp, "efree(%i):S=%i   p2=0x%p  p=0x%p\n", s, allocated, p2, p);
			fclose(fp);
		}
		efree(p);
		tracelog("freeded", file, line);
	}
	else
	{
		if(fp = fopen(RAMLOG, "a"))
		{
//			fprintf(fp, "%s (%i)	efree(ERROR)\n", file, line);
			//fprintf(fp, "efree(ERROR)\n");
			fclose(fp);
		}
	}
}

char *my_estrdup(char *s) //, const char *file, int line)
{
  char *p;
  int l=strlen(s)+1;
	if(p = (char *)emalloc(l))
	{
		strcpy(p, s);
	}
	return(p);
}


/*
#define emalloc(s) my_emalloc(s)
#define efree(s) my_efree(s)
#define estrdup(s) my_estrdup(s)
*/




