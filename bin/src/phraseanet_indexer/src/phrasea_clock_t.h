/* définition portable des structures système à dénomination variable */
#ifndef PHRASEA_CLOCK_T__INCLUDED
#define PHRASEA_CLOCK_T__INCLUDED 1

#include <sys/timeb.h>

/*
#ifndef PHRASEA_TIMEB
//# ifdef WIN32
#  define PHRASEA_TIMEB struct _timeb
#  define PHRASEA_FTIME ftime
#  define PHRASEA_GET_MS millisec_diff
//# else
//#  define PHRASEA_TIMEB clock_t
//#  define PHRASEA_FTIME phrasea_get_ticks
//#  define PHRASEA_GET_MS phrasea_getclockinterval
// ******* tosee : ligne mise en remarque pour warning win32
//void PHRASEA_FTIME(PHRASEA_TIMEB *);
//# endif
#endif



int PHRASEA_GET_MS(PHRASEA_TIMEB *, PHRASEA_TIMEB *);
// ******* tosee : resetclock est implÈmentÈ en void
void resetclock(PHRASEA_TIMEB *);
*/

#ifndef WIN32
char *_strupr( char *string );
#endif

#endif // PHRASEA_CLOCK_T__INCLUDED


