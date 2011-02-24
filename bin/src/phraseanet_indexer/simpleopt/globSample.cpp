// File:    globSample.cpp
// Library: SimpleOpt
// Author:  Brodie Thiesfield <code@jellycan.com>
// Source:  http://code.jellycan.com/simpleopt/
//
// MIT LICENCE
// ===========
// The licence text below is the boilerplate "MIT Licence" used from:
// http://www.opensource.org/licenses/mit-license.php
//
// Copyright (c) 2006, Brodie Thiesfield
//
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is furnished
// to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
// FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
// COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
// IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
// CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

#if defined(_MSC_VER)
# include <windows.h>
# include <tchar.h>
#else
# define TCHAR		char
# define _T(x)		x
# define _tprintf	printf
# define _tmain		main
#endif

#include <stdio.h>
#include <locale.h>
#include "SimpleOpt.h"
#include "SimpleGlob.h"

static void ShowUsage()
{
    _tprintf(
        _T("Usage: globSample [OPTIONS] [FILES]\n")
        _T("\n")
        _T("  -e  Return upon read error (e.g. directory does not have\n")
        _T("      read permission)\n")
        _T("  -m  Append a slash (backslash in Windows) to each path which\n")
        _T("      corresponds to a directory\n")
        _T("  -s  Don't sort the returned pathnames\n")
        _T("  -o  Sort all pathnames as a single group instead of in filespec groups\n")
        _T("  -c  If no pattern matches, return the original pattern\n")
        _T("  -t  Tilde expansion is carried out (on Unix platforms)\n")
        _T("  -d  Return only directories (not compatible with --only-file)\n")
        _T("  -f  Return only files (not compatible with --only-dir)\n")
        _T("  -n  Do not return the \".\" or \"..\" special files\n")
        _T("\n")
        _T("  -?  Output this help.\n")
        );
}

CSimpleOpt::SOption g_rgOptions[] =
{
    { SG_GLOB_ERR,      _T("-e"),   SO_NONE },
    { SG_GLOB_MARK,     _T("-m"),   SO_NONE },
    { SG_GLOB_NOSORT,   _T("-s"),   SO_NONE },
    { SG_GLOB_NOCHECK,  _T("-c"),   SO_NONE },
    { SG_GLOB_TILDE,    _T("-t"),   SO_NONE },
    { SG_GLOB_ONLYDIR,  _T("-d"),   SO_NONE },
    { SG_GLOB_ONLYFILE, _T("-f"),   SO_NONE },
    { SG_GLOB_NODOT,    _T("-n"),   SO_NONE },
    { SG_GLOB_FULLSORT, _T("-o"),   SO_NONE },
    { 0,                _T("-?"),   SO_NONE },
    { 0,                _T("-h"),   SO_NONE },

    SO_END_OF_OPTIONS
};

static const TCHAR * 
GetLastErrorText(
    int a_nError
    ) 
{
    switch (a_nError) {
    case SO_SUCCESS:            return _T("Success");
    case SO_OPT_INVALID:        return _T("Unrecognized option");
    case SO_OPT_MULTIPLE:       return _T("Option matched multiple strings");
    case SO_ARG_INVALID:        return _T("Option does not accept argument");
    case SO_ARG_INVALID_TYPE:   return _T("Invalid argument format");
    case SO_ARG_MISSING:        return _T("Required argument is missing");
    case SO_ARG_INVALID_DATA:   return _T("Invalid argument data");
    default:                    return _T("Unknown error");
    }
}

int _tmain(int argc, TCHAR * argv[]) {
    unsigned int uiFlags = 0;

    CSimpleOpt args(argc, argv, g_rgOptions, true);
    while (args.Next()) {
        if (args.LastError() != SO_SUCCESS) {
            _tprintf(
                _T("%s: '%s' (use --help to get command line help)\n"),
                GetLastErrorText(args.LastError()), args.OptionText());
            continue;
        }

        if (args.OptionId() == 0) {
            ShowUsage();
            return 0;
        }

        uiFlags |= (unsigned int) args.OptionId();
    }

    CSimpleGlob glob(uiFlags);
    if (glob.Add(args.FileCount(), args.Files()) < SG_SUCCESS) {
        _tprintf(_T("Error while globbing files\n"));
        return 1;
    }

    for (int n = 0; n < glob.FileCount(); ++n)
        _tprintf(_T("file %2d: '%s'\n"), n, glob.File(n));

	return 0;
}
