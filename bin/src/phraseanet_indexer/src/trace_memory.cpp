#if TRACE_ERAM==1

// #include "base_header.h"
#include <string.h>
#include <stdio.h>
#include <malloc.h>

#include "trace_memory.h"

int allocated = 0;

void *_MALLOC(int s)
{
  unsigned char *p;
  unsigned char *p2=NULL;
	p  = (unsigned char *)malloc(sizeof(int) + sizeof(int) + s + 0 + 1 );
	if(p)
	{
		*(int *)(p + 0) = s;
		*(int *)(p + sizeof(int)) = 0;
		*(char *)(p + sizeof(int) + sizeof(int) + s + 0) = '\0';
		p2 = p + sizeof(int) + sizeof(int) ;
		allocated += s;
	}
	printf("ALLOC\t%i\t%i\t0x%08lX\n", s, allocated, p2);
	return(p2);
}
void *_MALLOC_WHY(int s, char *why)
{
  unsigned char *p;
  unsigned char *p2=NULL;
  int l = why ? strlen(why) : 0;
	if(l>500)
		l = 500;
	p  = (unsigned char *)malloc(sizeof(int) + sizeof(int) + s + l + 1 );
	if(p)
	{
		*(int *)(p + 0) = s;
		*(int *)(p + sizeof(int)) = l;
		if(l > 0)
		{
			memcpy(p + sizeof(int) + sizeof(int) + s, why, l);
		}
		*(char *)(p + sizeof(int) + sizeof(int) + s + l) = '\0';
		p2 = p + sizeof(int) + sizeof(int) ;
		allocated += s;
	}
	printf("ALLOC_WHY\t%i\t%i\t0x%08lX\t%s\n", s, allocated, p2, why ? why : "");
	return(p2);
}

void *_REALLOC(void *p2, int new_s)
{
	printf("REALLOC\t%i\t%i\t0x%08lX\t---------- begin -----------\n", new_s, allocated, p2);
	void *new_p2 = NULL;
	if(p2)
	{
		// realloc
		unsigned char *p = (unsigned char *)p2 - sizeof(int) - sizeof(int);
		int s = *(int *)(p + 0);
		int l = *(int *)(p + sizeof(int));
		if(l > 0)
		{
			char *why = (char *)p2 + s;
			new_p2 = _MALLOC_WHY(new_s, why);
		}
		else
		{
			new_p2 = _MALLOC(new_s);
		}
		if(new_p2)
		{
			memcpy(new_p2, p2, s);
		}
		_FREE(p2);
	}
	else
	{
		// alloc
		new_p2 = _MALLOC(new_s);
	}
	printf("REALLOC\t%i\t%i\t0x%08lX\t----------- end ------------\n", new_s, allocated, new_p2);
	return(new_p2);
}

/*
*/
void _FREE(void *p2)
{
  int s=0, l=0;
	if(p2)
	{
		unsigned char *p = (unsigned char *)p2 - sizeof(int) - sizeof(int);
		s = *(int *)(p + 0);
		l = *(int *)(p + sizeof(int));
		allocated -= s;
		printf("FREE\t%i\t%i\t0x%08lX\t%s\n", s, allocated, p2, l ? ((char *)p2+s) : "");
		free(p);
	}
	else
		printf("free(ERROR)\n");
}

char *_STRDUP(char *s, char *why)
{
  char *p;
  int l=strlen(s)+1;
	printf("STRDUP\t%i\t-\t0x%08lX\t%s\n", l, s, why?why:"");
	if(p = (char *)_MALLOC_WHY(l, why))
	{
		strcpy(p, s);
	}
	return(p);
}
#endif

/*
void * ::operator new( size_t stAllocateBlock)
{
	zend_printf("++++ new(%d)\n", stAllocateBlock);
    void *pvTemp = malloc( stAllocateBlock );
    if( pvTemp != 0 )
        memset( pvTemp, 0, stAllocateBlock );
    return pvTemp;
}

void * operator new(size_t, void *p)
{
 	zend_printf("++++ new2() : 0x%0X\n", p);
	return p;
}


void ::operator delete ( void *ptr)
{
	zend_printf("++++ delete() (0x%0X)\n", ptr);
	free(ptr);
}
*/

