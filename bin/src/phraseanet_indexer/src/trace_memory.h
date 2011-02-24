// -------------------------------------------------------------------------------------------------
// ------------------------------------------------ allocation memoire tracee ----------------------
#if !defined( TRACE_MEMORY_INCLUDED )
#define TRACE_MEMORY_INCLUDED 1
#if TRACE_ERAM==1
	void *_MALLOC(int s);
	void *_MALLOC_WHY(int s, char *why);
	void *_REALLOC(void *p2, int new_s);
	void _FREE(void *p2);
	char *_STRDUP(char *s, char *why=NULL);
#else
//	#pragma warning(push)
//	#pragma warning(disable : 4003)	// disable warning " not enough actual parameters for macro '_EMALLOC' "
//	#define _EMALLOC(s, why) emalloc(s)
//	#define _MALLOC(s, why) malloc(s)
	#define _MALLOC_WHY(s, why) malloc(s)
	#define _MALLOC(s) malloc(s)
	#define _REALLOC(p, s) realloc((p), (s))
	#define _FREE(p) free(p)
//	#define _ESTRDUP(s, why) estrdup(s)
	#define _STRDUP(s, ...) strdup(s)
//	#pragma warning(pop)
#endif
#endif
// -------------------------------------------------------------------------------------------------

