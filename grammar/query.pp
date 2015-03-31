%token  space           \s+

// Symbols
%token  parenthese_     \(
%token _parenthese      \)
%token  bracket_        \[
%token _bracket         \]
%token  colon           :

// Strings
%token  quote_          "        -> string
%token  string:quoted   [^"]+
%token  string:_quote   "        -> default
%token  raw_quote_      r"       -> raw
%token  raw:raw_quoted  (?:(?>[^"\\]+)|\\.)+
%token  raw:_raw_quote  "        -> default

// Operators (too bad we can't use preg "i" flag)
%token  in              [Ii][Nn]|[Dd][Aa][Nn][Ss]
%token  and             [Aa][Nn][Dd]|[Ee][Tt]
%token  or              [Oo][Rr]|[Oo][Uu]
%token  except          [Ee][Xx][Cc][Ee][Pp][Tt]|[Ss][Aa][Uu][Ff]

// Rest
%token  collection      collection
%token  word            [^\s\(\)\[\]]+

// relative order of precedence is NOT > XOR > AND > OR

#query:
    ::space::? primary()? ::space::?


// Boolean operators

primary:
    secondary() ( ::space:: ::except:: ::space:: primary() #except )?

secondary:
    ternary() ( ::space:: ::or:: ::space:: primary() #or )?

ternary:
    quaternary() ( ::space:: ::and:: ::space:: primary() #and )?


// Collection matcher

quaternary:
    ::collection:: ::colon:: string() #collection
  | quinary()


// Field narrowing

quinary:
    senary() ( ::space:: ::in:: ::space:: field() #in )?

field:
    <word>
  | keyword()
  | quoted_string()


// Field level matchers (*may* be restricted to a field subset)

senary:
    group() #group
  | term()

group:
    ::space::? ::parenthese_:: primary() ::_parenthese:: ::space::?


// Thesaurus terms

term:
    ::bracket_:: text() ::_bracket:: #thesaurus_term
  | text() #text


// Free text handling

text:
    string_keyword_symbol()
  ( <space>? string_keyword_symbol() )*
  ( ::space::? context_block() )?

string_keyword_symbol:
    string()
  | symbol()

context_block:
    ::parenthese_:: ::space::? context() ::space::? ::_parenthese:: #context

context:
    word_or_keyword() ( <space>? word_or_keyword() )*


// Generic helpers

string:
    word_or_keyword()+
  | quoted_string()
  | raw_quoted_string()

word_or_keyword:
    <word> | keyword()

quoted_string:
    ::quote_:: <quoted> ::_quote::

raw_quoted_string:
    ::raw_quote_:: <raw_quoted> ::_raw_quote::

keyword:
    <in>
  | <except>
  | <and>
  | <or>
  | <collection>

symbol:
    <parenthese_>
  | <_parenthese>
  | <bracket_>
  | <_bracket>
  | <colon>
