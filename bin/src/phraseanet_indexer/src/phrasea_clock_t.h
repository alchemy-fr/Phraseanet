/*
 * phrasea_clock_t.h
 *
 *  Created on: 4 mars 2010
 *      Author: gaulier
 */

#ifndef PHRASEA_CLOCK_T_H_
#define PHRASEA_CLOCK_T_H_


#include <sys/timeb.h>

#ifdef PHP_WIN32
	typedef DWORD CHRONO;
#else
	typedef struct timeval CHRONO;
#endif

void startChrono(CHRONO &chrono);
float stopChrono(CHRONO &chrono);


#ifndef WIN32
char *_strupr( char *string );
#endif

#endif /* PHRASEA_CLOCK_T_H_ */


