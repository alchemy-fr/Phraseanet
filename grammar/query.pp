%token  space           \s+

// Symbols
%token  parenthese_     \(
%token _parenthese      \)
%token  bracket_        \[
%token _bracket         \]
%token  colon           :
%token  lte             <=|≤
%token  gte             >=|≥
%token  lt              <
%token  gt              >
%token  equal           =

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
%token  database        database
%token  collection      collection
%token  type            type
%token  id              id|recordid
%token  field_prefix    field.
%token  flag_prefix     flag.
%token  true            true|1
%token  false           false|0
%token  word            [^\s\(\)\[\]:<>≤≥=]+

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


// Key value pairs & field level matchers (restricted to a single field)

quaternary:
    native_key()             ::colon:: ::space::? value()   #native_key_value
  | ::flag_prefix::  flag()  ::colon:: ::space::? boolean() #flag_statement
  | ::field_prefix:: field() ::colon:: ::space::? term()    #field_statement
  |                  field() ::colon:: ::space::? term()    #field_statement
  | field() ::space::?       ::lt::    ::space::? value()   #less_than
  | field() ::space::?       ::gt::    ::space::? value()   #greater_than
  | field() ::space::?       ::lte::   ::space::? value()   #less_than_or_equal_to
  | field() ::space::?       ::gte::   ::space::? value()   #greater_than_or_equal_to
  | field() ::space::?       ::equal:: ::space::? value()   #equal_to
  | group() #group
  | term()

#flag:
  word_or_keyword()+

#native_key:
    <database>
  | <collection>
  | <type>
  | <id>

#field:
    word_or_keyword()+
  | quoted_string()

#value:
    word_or_keyword()+
  | quoted_string()

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

boolean:
    <true>
  | <false>

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
  | <database>
  | <collection>
  | <type>
  | <id>
  | <field_prefix>
  | <flag_prefix>
  | <true>
  | <false>

symbol:
    <parenthese_>
  | <_parenthese>
  | <bracket_>
  | <_bracket>
  | <colon>
