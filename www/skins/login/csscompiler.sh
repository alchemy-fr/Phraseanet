#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

for i in $( ls less ); do
lessc $DIR/less/$i $DIR/css/`echo $i | sed -e 's/less/css/g'`
done