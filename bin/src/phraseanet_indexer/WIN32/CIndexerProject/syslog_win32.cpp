#include "syslog_win32.h"

#include "messages.h"

#ifndef NULL
	#define NULL 0
#endif
#ifndef FALSE
	#define FALSE 0
#endif
#ifndef TRUE
	#define TRUE 0
#endif



namespace util
{
    //
    // Load a message resource fom the .exe and format it 
    // with the passed insertions
    //
    UINT LoadMessage( DWORD dwMsgId, PTSTR pszBuffer, UINT cchBuffer, ... )
    {
        va_list args;
        va_start( args, cchBuffer );
        
        return FormatMessage( 
          FORMAT_MESSAGE_FROM_HMODULE,
          NULL,         // Module (e.g. DLL) to search for the Message. NULL = own .EXE
          dwMsgId,      // Id of the message to look up (aus "Messages.h")
          LANG_NEUTRAL, // Language: LANG_NEUTRAL = current thread's language
          pszBuffer,    // Destination buffer
          cchBuffer,    // Character count of destination buffer
          &args         // Insertion parameters
        );
    }

    //
    // Installs our app as a source of events
    // under the name pszName into the registry
    //

	// PCTSTR undef ? 
    void AddEventSource(  const TCHAR *pszName, DWORD dwCategoryCount )
    {
        HKEY    hRegKey = NULL; 
        DWORD   dwError = 0;
        TCHAR   szPath[ MAX_PATH ];
        
        _stprintf( szPath, 
          _T("SYSTEM\\CurrentControlSet\\Services\\EventLog\\Application\\%s"), 
          pszName );

        // Create the event source registry key
        dwError = RegCreateKey( HKEY_LOCAL_MACHINE, szPath, &hRegKey );

        // Name of the PE module that contains the message resource
        GetModuleFileName( NULL, szPath, MAX_PATH );

        // Register EventMessageFile
        dwError = RegSetValueEx( hRegKey, 
                  _T("EventMessageFile"), 0, REG_EXPAND_SZ, 
                  (PBYTE) szPath, (_tcslen( szPath) + 1) * sizeof TCHAR ); 
        

        // Register supported event types
        DWORD dwTypes = EVENTLOG_ERROR_TYPE | 
              EVENTLOG_WARNING_TYPE | EVENTLOG_INFORMATION_TYPE; 
        dwError = RegSetValueEx( hRegKey, _T("TypesSupported"), 0, REG_DWORD, 
                                (LPBYTE) &dwTypes, sizeof dwTypes );

        // If we want to support event categories,
        // we have also to register the CategoryMessageFile.
        // and set CategoryCount. Note that categories
        // need to have the message ids 1 to CategoryCount!

        if( dwCategoryCount > 0 )
		{

            dwError = RegSetValueEx( hRegKey, _T("CategoryMessageFile"), 
                      0, REG_EXPAND_SZ, (PBYTE) szPath, 
                      (_tcslen( szPath) + 1) * sizeof TCHAR );

            dwError = RegSetValueEx( hRegKey, _T("CategoryCount"), 0, REG_DWORD, 
                      (PBYTE) &dwCategoryCount, sizeof dwCategoryCount );
        }
            
        RegCloseKey( hRegKey );
    }

}   // namespace util



unsigned short CSyslog::category[12] = {
						  MSGCAT_PROG_START
						, MSGCAT_PROG_END
						, MSGCAT_THREAD_START
						, MSGCAT_THREAD_END
						, MSGCAT_PRELOAD
						, MSGCAT_SQLERR
						, MSGCAT_XMLERR
						, MSGCAT_FLUSH
						, MSGCAT_INDEXING
						, MSGCAT_SIGNAL
						, MSGCAT_THESAURUS
						, MSGCAT_STRUCTURE
						};

char *CSyslog::libLevel[4] = {
								  "LOG_DEBUG"
								, "LOG_INFO"
								, "LOG_WARNING"
								, "LOG_ERR"
							};

char *CSyslog::libCategory[12] = {
								  "LOGC_PROG_START"
								, "LOGC_PROG_END"
								, "LOGC_THREAD_START"
								, "LOGC_THREAD_END"
								, "LOGC_PRELOAD"
								, "LOGC_SQLERR"
								, "LOGC_XMLERR"
								, "LOGC_FLUSH"
								, "LOGC_INDEXING"
								, "LOGC_SIGNAL"
								, "LOGC_THESAURUS"
								, "LOGC_STRUCTURE"
								};



CSyslog::CSyslog()
{
	this->ident = NULL;
	this->hEventLog = NULL;
	this->where = TOTTY;
}

CSyslog::~CSyslog()
{
	this->close();
}

void CSyslog::open(const TCHAR *ident, int where)
{
	int l;
	this->close();

	this->where = where;

	if(where == TOLOG)
	{
		this->install(ident);

		if(ident && (this->ident = (TCHAR *)(malloc(l=strlen(ident)+1)) ))	// strlen to count bytes !
		{
			memcpy(this->ident, ident, l);
			this->hEventLog = RegisterEventSource( NULL, this->ident);
		}
	}
}

void CSyslog::install(const TCHAR *ident)
{
	util::AddEventSource(ident, 12);
}


void CSyslog::log(PHRASEA_LOG_LEVEL level, PHRASEA_LOG_CATEGORY category, TCHAR *fmt, ...)
{
	va_list vl;
	char buff[5000];
	va_start(vl, fmt);
#ifdef UNICODE
	_vsnwprintf(buff, 5000, fmt, vl);
#else
	_vsnprintf(buff, 5000, fmt, vl);
#endif

//	printf("%s\n", buff);

	if(where == TOLOG)
	{
		if(!this->hEventLog)
			return;

		const TCHAR *aInsertions[] = { buff };
		
		switch(level)
		{
			case CSyslog::LOGL_DEBUG:
			case CSyslog::LOGL_INFO:
				ReportEvent(
								this->hEventLog,                  // Handle to the eventlog
								EVENTLOG_INFORMATION_TYPE,      // Type of event
								this->category[category],               // Category (could also be 0)
								EVENT_ALL,               // Event id
								NULL,                       // User's sid (NULL for none)
								1,                          // Number of insertion strings
								0,                          // Number of additional bytes
								aInsertions,                       // Array of insertion strings
								NULL                        // Pointer to additional bytes
							);
				break;
			case CSyslog::LOGL_WARNING:
				ReportEvent(
								this->hEventLog,                  // Handle to the eventlog
								EVENTLOG_WARNING_TYPE,      // Type of event
								this->category[category],               // Category (could also be 0)
								EVENT_ALL,               // Event id
								NULL,                       // User's sid (NULL for none)
								1,                          // Number of insertion strings
								0,                          // Number of additional bytes
								aInsertions,                       // Array of insertion strings
								NULL                        // Pointer to additional bytes
							);
				break;
			case CSyslog::LOGL_ERR:
				ReportEvent(
								this->hEventLog,                  // Handle to the eventlog
								EVENTLOG_ERROR_TYPE,      // Type of event
								this->category[category],               // Category (could also be 0)
								EVENT_ALL,               // Event id
								NULL,                       // User's sid (NULL for none)
								1,                          // Number of insertion strings
								0,                          // Number of additional bytes
								aInsertions,                       // Array of insertion strings
								NULL                        // Pointer to additional bytes
							);
				break;
			default:
				break;	
		}
	}
	else
	{
		// TOTTY
//		printf("[%s].[%s] :\n%s\n", this->libLevel[level], this->libCategory[category], buff);
		printf("%s\n", buff);
	}
}

void CSyslog::close()
{
	if(this->hEventLog)
	{
		DeregisterEventSource( this->hEventLog );
		this->hEventLog = NULL;
	}
	if(this->ident)
	{
		free((void *)this->ident);
		this->ident = NULL;
	}
	this->where = TOTTY;
}


/*

TCHAR *_ident = NULL;
HANDLE _hEventLog = NULL;

DWORD LogEvent(LPCSTR pszEvSrc, WORD wType, DWORD dwEventID, LPCSTR pszFormat,...);



static LPSTR DupeString(LPSTR pszIn)
{
	LPSTR pszOut = (LPSTR)LocalAlloc(LMEM_FIXED,strlen(pszIn)+1);
	strcpy(pszOut,pszIn); return pszOut;
}

static BOOL GetSystemString(DWORD dwError,LPSTR *ppszOut)
{
	DWORD rc = FormatMessage(FORMAT_MESSAGE_ALLOCATE_BUFFER | 
		  FORMAT_MESSAGE_IGNORE_INSERTS | 
		  FORMAT_MESSAGE_FROM_SYSTEM, NULL, dwError,
		  MAKELANGID(LANG_NEUTRAL, SUBLANG_DEFAULT),
		  (LPSTR)ppszOut,0,NULL); 
	if (rc == 0) 
	{
		*ppszOut = DupeString(""); 
		if (*ppszOut == NULL)
			return FALSE;
	}
	return TRUE;
}

DWORD LogEvent(LPCSTR pszEvSrc, WORD wType, DWORD dwEventID, LPCSTR pszFormat,...)
{
	DWORD dwReturn = NOERROR;                
	LPSTR *ppszSubst = NULL;
	WORD nSubst = 0;
	DWORD dwLastErrorOnInput = GetLastError();

	// --- (1) CREATE nSubst AND pszSubst FOR ReportEvent CALL ---
	if (pszFormat != NULL && *pszFormat != '\0')
	{
		// -- ALLOCATE AN ARRAY OF POINTERS FOR ReportEvent ---
		nSubst = strlen(pszFormat);
		ppszSubst= (LPSTR*)LocalAlloc(LMEM_FIXED, sizeof(LPSTR)*nSubst);
		if (ppszSubst == NULL)
			goto return_last_error; 
		for (ULONG k=0; k < nSubst; k++)
			ppszSubst[k] = NULL;

		// --- FILL IN THE ARRAY OF POINTERS FROM va_list --
		va_list vl;
		va_start(vl,pszFormat);
		for (ULONG i=0; i < nSubst && dwReturn == NOERROR ;i++)
		{
			LPVOID pArg = va_arg(vl, LPVOID);
			switch (tolower(pszFormat[i])) 
			{
				case 's':
					ppszSubst[i]=DupeString((LPSTR)pArg);
					if (ppszSubst[i]==NULL)
						dwReturn = GetLastError();
					break;
				case 'd':
					{  // --- FORMAT A DECIMAL DIGIT ---
						char szDigits[20];  
						ppszSubst[i] =DupeString(itoa((INT)pArg,szDigits,10));
						if (ppszSubst[i]==NULL) 
							dwReturn = GetLastError();
						break;
					}
				case 'x':
					{  // -- FORMAT A HEXIDECIMAL DIGIT, C STYLE ---
						char szDigits[22] = "0x";
						ppszSubst[i]=
						DupeString(itoa((INT)pArg, szDigits+2, 16)-2);
						if (ppszSubst[i]==NULL) 
							dwReturn = GetLastError();
						break;
					}
				case 'm':
					{
						if (!GetSystemString(((DWORD)pArg & 0xFFFF), &ppszSubst[i])) 
							dwReturn = GetLastError();
						break;
					}
				default: 
					dwReturn = ERROR_INVALID_PARAMETER;
			}
		}
		va_end(vl);
		if (dwReturn != NOERROR)
			goto done;
	}

	// --- (2) GET THE EVENT TYPE FROM THE EVENT ID PARAMETER ---
//	WORD wType;
//	wType = EVENTLOG_INFORMATION_TYPE;
//	if ((dwEventID & ERROR_SEVERITY_WARNING) == ERROR_SEVERITY_WARNING)
//		wType = EVENTLOG_WARNING_TYPE;
//	if ((dwEventID & ERROR_SEVERITY_ERROR) == ERROR_SEVERITY_ERROR)
//	{
//		wType = EVENTLOG_ERROR_TYPE;
//	}

	// -- (3) WRITE THE EVENT TO THE EVENT LOG ---
	HANDLE hEventSource;
	hEventSource = RegisterEventSource(NULL,pszEvSrc);
	if (hEventSource == NULL)
		goto return_last_error;

	BOOL fSuccess;
	fSuccess = ReportEvent(hEventSource
							, wType
							, 0				// -- USE THE DEFAULT CATEGORY ---
							, dwEventID
							, NULL			// --- USE THE DEFAULT SID ---
							, nSubst
							, 0				// ---- NO DATA ---
							, (LPCSTR*)ppszSubst
							, NULL			// ---- NO DATA ----
						);
	if (!fSuccess) 
	{
		dwReturn = GetLastError();
		DeregisterEventSource(hEventSource);
		goto done; 
	}
	DeregisterEventSource(hEventSource);
	// --- END OF STEP (3) ----

	goto done; // -- ALL EXIT POINTS ARE BELOW ---

return_last_error:
	dwReturn = GetLastError();

done:
	if (ppszSubst != NULL)
	{     // -- FREE ALL THE MEMORY ---
		for (ULONG i=0; i < nSubst && ppszSubst[i] != NULL; i++)
			LocalFree(ppszSubst[i]);
		LocalFree(ppszSubst);
	}

	// -- RESTORE THE GetLastError ON INPUT --
	SetLastError(dwLastErrorOnInput); 
	return dwReturn; 
}


*/




/*

void openlog(const TCHAR *ident, _OPTION option, _FACILITY facility)
{
	int l;
	closelog();
	if(ident && (_ident = (TCHAR *)(malloc(l=strlen(ident)+1)) ))	// strlen to count bytes !
	{
		memcpy(_ident, ident, l);
		util::AddEventSource(_ident, 0);
		_hEventLog = RegisterEventSource( NULL, _ident);
	}
}
void closelog(void)
{
	if(_hEventLog)
	{
		DeregisterEventSource( _hEventLog );
		_hEventLog = NULL;
	}
	if(_ident)
	{
		free((void *)_ident);
		_ident = NULL;
	}
}
void syslog(_PRIORITY priority, char *format, ...)
{
	switch(priority)
	{
		case LOG_DEBUG:
//			LogEvent(_ident, EVENTLOG_INFORMATION_TYPE, 0, "s", "whazaaa");
 ReportEvent(
        _hEventLog,                  // Handle to the eventlog
        EVENTLOG_WARNING_TYPE,      // Type of event
        CATEGORY_ONE,               // Category (could also be 0)
        EVENT_BACKUP,               // Event id
        NULL,                       // User's sid (NULL for none)
        0,                          // Number of insertion strings
        0,                          // Number of additional bytes
        NULL,                       // Array of insertion strings
        NULL                        // Pointer to additional bytes
    );			break;
		default:
			break;	
	}
}


*/