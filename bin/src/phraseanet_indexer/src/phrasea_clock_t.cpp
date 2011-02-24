#include "phrasea_clock_t.h"

/*
#ifndef WIN32
void phrasea_get_ticks ( clock_t *nbticks )
{
	*nbticks = clock();
}

int phrasea_getclockinterval ( clock_t * timestart, clock_t * timeend )
{
	return (int)(((double)( *timeend - *timestart ) / CLOCKS_PER_SEC ) * 1000 );
}
#endif

int millisec_diff(PHRASEA_TIMEB *timestart, PHRASEA_TIMEB *timeend)
{
//	time_t t;
	return((1000 * (timeend->time - timestart->time)) + (timeend->millitm - timestart->millitm));
}

void resetclock(PHRASEA_TIMEB * timeref)
{
	timeref->time = timeref->millitm = 0;
}


# ifndef WIN32
void resetclock(clock_t * timeref)
{
	*timeref = 0;
}
#endif
*/

#ifndef WIN32
char *_strupr( char *string )
{
	for(register char *p=string; *p; p++)
		*p = (*p>='a' && *p<='z') ? (*p-'a')+'A' : *p;
	return(string);
}
#endif


