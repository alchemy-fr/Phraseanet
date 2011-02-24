#ifdef INSTALL_AS_NT_SERVICE
#include "nt_service.h"

////////////////////////////////////////////////////////////////////// 
//
// Uninstall
//
VOID UnInstall(char* pName)
{
	SC_HANDLE schSCManager = OpenSCManager( NULL, NULL, SC_MANAGER_ALL_ACCESS); 
	if (schSCManager==0) 
	{
		long nError = GetLastError();
		printf("OpenSCManager failed, error code = %d", nError);
	}
	else
	{
		SC_HANDLE schService = OpenService( schSCManager, pName, SERVICE_ALL_ACCESS);
		if (schService==0) 
		{
			long nError = GetLastError();
			printf("OpenService failed, error code = %d", nError);
		}
		else
		{
			if(!DeleteService(schService)) 
			{
				printf("Failed to delete service %s", pName);
			}
			else 
			{
				printf("Service %s removed",pName);
			}
			CloseServiceHandle(schService); 
		}
		CloseServiceHandle(schSCManager);	
	}
}

////////////////////////////////////////////////////////////////////// 
//
// Install
//
VOID Install(char* pPath, char* pName, char *pDesc) 
{  
	SC_HANDLE schSCManager = OpenSCManager( NULL, NULL, SC_MANAGER_CREATE_SERVICE); 
	if (schSCManager==0) 
	{
		long nError = GetLastError();
		printf("OpenSCManager failed, error code = %d", nError);
	}
	else
	{
		SC_HANDLE schService = CreateService
		( 
			schSCManager,	/* SCManager database      */ 
			pName,			/* name of service         */ 
			pName,			/* service name to display */ 
			SERVICE_ALL_ACCESS,        /* desired access          */ 
			SERVICE_WIN32_OWN_PROCESS|SERVICE_INTERACTIVE_PROCESS  , /* service type            */ 
			SERVICE_AUTO_START,      /* start type              */ 
			SERVICE_ERROR_NORMAL,      /* error control type      */ 
			pPath,			/* service's binary        */ 
			NULL,                      /* no load ordering group  */ 
			NULL,                      /* no tag identifier       */ 
			NULL,                      /* no dependencies         */ 
			NULL,                      /* LocalSystem account     */ 
			NULL                     /* no password             */ 
		);
		SERVICE_DESCRIPTION servicedesc;
		servicedesc.lpDescription = pDesc;
		ChangeServiceConfig2(schService, SERVICE_CONFIG_DESCRIPTION, (LPVOID)(&servicedesc));
		/*
		*/
		if (schService==0) 
		{
			long nError =  GetLastError();
			printf("Failed to create service %s, error code = %d", pName, nError);
		}
		else
		{
			printf("Service %s installed", pName);
			CloseServiceHandle(schService); 
		}
		CloseServiceHandle(schSCManager);
	}	
}
#endif
