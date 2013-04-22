UPGRADE FROM 3.7 TO 3.8
-----------------------

Here are some release notes about upgrading from Phraseanet 3.7 and Phraseanet 3.8.

Phraseanet 3.8 is a new step in moving Phraseanet to a more decoupled design. We did
a lot of cleanup and now delegate some behavior to dedicated components. this brings
some new features, robustness and stability.

These enhancements are described in the CHANGELOG file. The purpose of this document 
is to provide a list a BC breaks / Changes.

Console
+++++++

Phraseanet 3.8 comes with a new command-line utility : `bin/setup`. This utility
brings commands that can be run when Phraseanet is not installed. 

    - `bin/console system:upgrade` is replaced by `bin/setup system:upgrade`
