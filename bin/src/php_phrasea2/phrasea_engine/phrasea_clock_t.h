/*
 * phrasea_clock_t.h
 *
 *  Created on: 4 mars 2010
 *      Author: gaulier
 */

#ifndef PHRASEA_CLOCK_T_H_
#define PHRASEA_CLOCK_T_H_

#ifdef PHP_WIN32
	typedef DWORD CHRONO;
#else
	typedef struct timeval CHRONO;
#endif

void startChrono(CHRONO &chrono);
double stopChrono(CHRONO &chrono);

#endif /* PHRASEA_CLOCK_T_H_ */
