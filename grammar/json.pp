%skip   space           \s

%token  true            true
%token  false           false
%token  null            null
%token  quote_          "                             -> string
%token  string:escaped  \\(["\\/bfnrt]|u[0-9a-fA-F]{4})
%token  string:string   [^"\\]+
%token  string:_quote   "                             -> default
%token  brace_          {
%token _brace           }
%token  bracket_        \[
%token _bracket         \]
%token  colon           :
%token  comma           ,
%token  number          \-?(0|[1-9]\d*)(\.\d+)?([eE][\+\-]?\d+)?

value:
    <true> | <false> | <null> | string() | object() | array() | number()

string:
    ::quote_::
    <string>
    ::_quote::

number:
    <number>

#object:
    ::brace_:: pair() ( ::comma:: pair() )* ::_brace::

#pair:
    string() ::colon:: value()

#array:
    ::bracket_:: value() ( ::comma:: value() )* ::_bracket::