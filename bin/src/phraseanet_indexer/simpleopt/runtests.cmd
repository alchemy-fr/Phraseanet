@echo off
goto main

REM -------------------------------------------------------
REM procedure: run all test cases
:alltestcase
call :testcase -d -e -f -g -flag --flag
call :testcase -s SEP1 -sep SEP2 --sep SEP3
call :testcase -s -s SEP1 -sep SEP2 --sep SEP3
call :testcase --noerr -s -s SEP1 -sep SEP2 --sep SEP3
call :testcase FILE0 -s SEP1 FILE1 -sep SEP2 FILE2 --sep SEP3 FILE3 
call :testcase FILE0 -s=SEP1 FILE1 -sep=SEP2 FILE2 --sep=SEP3 FILE3 
call :testcase --pedantic FILE0 -s=SEP1 FILE1 -sep=SEP2 FILE2 --sep=SEP3 FILE3 
call :testcase -c=COM1 -com=COM2 --com=COM3
call :testcase --shortarg -cCOM 
call :testcase --shortarg -cCOM1 -c=COM2 
call :testcase --shortarg --clump -defgcCOM1 -c=COM2 
call :testcase -o -opt --opt -o=OPT1 -opt=OPT2 --opt=OPT3
call :testcase --shortarg -oOPT1 
call :testcase -man -mand -mandy -manda -mandat -mandate
call :testcase --man --mand --mandy --manda --mandat --mandate
call :testcase --exact -man -mand -mandy -manda -mandat -mandate 
call :testcase FILE0 FILE1
call :testcase --multi0 --multi1 ARG1 --multi2 ARG1 ARG2
call :testcase FILE0 --multi0 FILE1 --multi1 ARG1 FILE2 --multi2 ARG1 ARG2 FILE3
call :testcase FILE0 --multi 0 FILE1 --multi 4 ARG1 ARG2 ARG3 ARG4 FILE3
call :testcase --multi 0
call :testcase --multi 1
call :testcase FILE0 --multi 1
call :testcase /-sep SEP1
call :testcase /sep SEP1
call :testcase --noslash /sep SEP1
call :testcase --multi 1 -sep
call :testcase --noerr --multi 1 -sep
call :testcase open file1 read file2 write file3 close file4 zip file5 unzip file6
call :testcase upcase
call :testcase UPCASE
call :testcase --icase upcase
call :testcase -E -F -S sep1 -SEP sep2 --SEP sep3
call :testcase --icase -E -F -S sep1 -SEP sep2 --SEP sep3 upcase
call :testcase --icase-short -E -F -S sep1 -SEP sep2 --SEP sep3 upcase
call :testcase --icase-long  -E -F -S sep1 -SEP sep2 --SEP sep3 upcase
call :testcase --icase-word  -E -F -S sep1 -SEP sep2 --SEP sep3 upcase
exit /b 0

REM -------------------------------------------------------
REM procedure: run a single test case 
:testcase
echo. >> %OUTPUT%
echo fullSample %* >> %OUTPUT%
%TESTDIR%\fullSample %* >> %OUTPUT%
exit /b 0

REM -------------------------------------------------------
REM procedure: run all test cases for specific directory
:runtests
set TESTDIR=%1

REM skip it there is no directory or exec to run
if not exist %TESTDIR% (
    echo Skipping %TESTDIR%
    exit /b 0
)
if not exist %TESTDIR%\fullSample.exe (
    echo Skipping %TESTDIR%
    exit /b 0
)

set TESTNAME=%TESTDIR%
set OUTPUT=runtests.%TESTNAME%.txt

REM special case running tests for the current directory
if %TESTNAME%.==.. (
    set TESTNAME=CurrentDir
    set OUTPUT=runtests.current.txt
)

REM get rid of any old test results
if exist %OUTPUT% del %OUTPUT%

REM run the actual test cases
call :alltestcase

REM check to see if we have our desired results
fc /A %OUTPUT% %EXPECTED% > nul
if errorlevel 1 (
    echo %TESTNAME% : Test results dont match expected
    echo. > runtests.error
    exit /b 1
)    
echo %TESTNAME%: All tests passed!
exit /b 0

REM -------------------------------------------------------
REM main program, exit on no error, pause on error
:main
set TESTDIR=
set EXPECTED=
set OUTPUT=

REM this file flags if there was an error
if exist runtests.error del runtests.error

if exist .\fullSample.exe (
    set EXPECTED=..\runtests.txt
    call :runtests .
) else (
    set EXPECTED=runtests.txt
    for %%d in (fullDebug fullDebugUnicode fullRelease fullReleaseUnicode) do call :runtests %%d
)
if exist runtests.error (
    del runtests.error
    pause
    exit /b 1
)    
exit /b 0
