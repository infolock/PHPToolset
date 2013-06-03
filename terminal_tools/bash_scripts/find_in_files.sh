#!/bin/bash
#set -x

# @author Jonathon Hibbard

clear

if [ $# -ne 1 ]
then
  echo
  echo "    Usage: $0 <string to search for>"
  echo "       ie. $0 username"
  echo
  exit
fi

echo "Looking for \"$1\" in:" `pwd`

grep "$1" -rin * | grep -v \.svn | more
