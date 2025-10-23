#!/bin/bash

# Read the file produced by create_aliases.sh and group by class name
# Shows class name and count of files using that class

cut -f2 aliases_waiting_to_be_changed.txt | sort | uniq -c | sort -rn | awk '{print $2 "\t" $1}'

