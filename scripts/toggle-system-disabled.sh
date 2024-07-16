#!/bin/bash

# There is no way to detect if script is called via composer so at least adding this small check to ensure if arg is added to call
if [ "1" != "$1" ]
then
    echo "Do not call this script directly!!"
    exit 1;
fi

# File path must be in sync with the one in "./config/parameters/files.yaml"
FILE_PATH='.is-disabled';

if [ -f "$FILE_PATH" ]; then
  echo -e "Removing the file - system will be Enabled \n";
  rm "$FILE_PATH";
else
  echo -e "Creating the file - system will be Disabled \n";
  touch "$FILE_PATH";
fi;