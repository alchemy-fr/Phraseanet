// File:    basicSample.cpp
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
#define _CRT_SECURE_NO_DEPRECATE

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

// define the ID values to indentify the option
enum { OPT_HELP, OPT_FLAG, OPT_ARG };

// declare a table of CSimpleOpt::SOption structures. See the SimpleOpt.h header
// for details of each entry in this structure. In summary they are:
//  1. ID for this option. This will be returned from OptionId() during processing.
//     It may be anything >= 0 and may contain duplicates.
//  2. Option as it should be written on the command line
//  3. Type of the option. See the header file for details of all possible types.
//     The SO_REQ_SEP type means an argument is required and must be supplied
//     separately, e.g. "-f FILE"
//  4. The last entry must be SO_END_OF_OPTIONS.
//
CSimpleOpt::SOption g_rgOptions[] = {
    { OPT_FLAG, _T("-a"),     SO_NONE    }, // "-a"
    { OPT_FLAG, _T("-b"),     SO_NONE    }, // "-b"
    { OPT_ARG,  _T("-f"),     SO_REQ_SEP }, // "-f ARG"
    { OPT_HELP, _T("-?"),     SO_NONE    }, // "-?"
    { OPT_HELP, _T("--help"), SO_NONE    }, // "--help"
    SO_END_OF_OPTIONS                       // END
};

// show the usage of this program
void ShowUsage() {
    _tprintf(_T("Usage: basicSample [-a] [-b] [-f FILE] [-?] [--help] FILES\n"));
}

int _tmain(int argc, TCHAR * argv[]) {
    // declare our options parser, pass in the arguments from main
    // as well as our array of valid options.
    CSimpleOpt args(argc, argv, g_rgOptions);

    // while there are arguments left to process
    while (args.Next()) {
        if (args.LastError() == SO_SUCCESS) {
            if (args.OptionId() == OPT_HELP) {
                ShowUsage();
                return 0;
            }
            _tprintf(_T("Option, ID: %d, Text: '%s', Argument: '%s'\n"),
                args.OptionId(), args.OptionText(),
                args.OptionArg() ? args.OptionArg() : _T(""));
        }
        else {
            _tprintf(_T("Invalid argument: %s\n"), args.OptionText());
            return 1;
        }
    }

    // process any files that were passed to us on the command line.
    // send them to the globber so that all wildcards are expanded
    // into valid filenames (e.g. *.cpp -> a.cpp, b.cpp, c.cpp, etc)
    // See the SimpleGlob.h header file for details of the flags.
    CSimpleGlob glob(SG_GLOB_NODOT|SG_GLOB_NOCHECK);
    if (SG_SUCCESS != glob.Add(args.FileCount(), args.Files())) {
        _tprintf(_T("Error while globbing files\n"));
        return 1;
    }

    // dump all of the details, the script that was passed on the
    // command line and the expanded file names
    for (int n = 0; n < glob.FileCount(); ++n) {
        _tprintf(_T("file %d: '%s'\n"), n, glob.File(n));
    }

    return 0;
}
