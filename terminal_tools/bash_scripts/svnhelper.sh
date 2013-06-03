#!/bin/bash

# =======================================================================================================================================================
#
# @author: Jonathon Hibbard
#
# Some helpful hints...
#
# ===== Add this to your .bashrc file in your home directory to make a shortcut to this script =======
# alias svnhelper='/path/to/svnhelper.sh'
#
clear;

# All of our options available
OPTIONS="MergeFromBranchToTrunk MergeFromTrunkToBranch Switch Quit"

# Show Available Options Menu
show_available_options () {
  clear
  if [ -n "$1" ]; then
    COUNTER=1
    for i in $OPTIONS; do
      echo -e "$COUNTER : $i"
      let COUNTER=COUNTER+1
    done
  fi
}

# Method for comparing versions... (used mainly to check SVN version for new actions...)
vercomp () {
  if [[ $1 == $2 ]]
  then
      return 0
  fi
  local IFS=.
  local i ver1=($1) ver2=($2)
  # fill empty fields in ver1 with zeros
  for ((i=${#ver1[@]}; i<${#ver2[@]}; i++))
  do
      ver1[i]=0
  done
  for ((i=0; i<${#ver1[@]}; i++))
  do
      if [[ -z ${ver2[i]} ]]
      then
          # fill empty fields in ver2 with zeros
          ver2[i]=0
      fi
      if ((10#${ver1[i]} > 10#${ver2[i]}))
      then
          return 1
      fi
      if ((10#${ver1[i]} < 10#${ver2[i]}))
      then
          return 2
      fi
  done
  return 0
}

# Basic "Y/N" message system.
ask_yes_or_no() {
  echo -e "$1"
  while true; do
    read -p "Answer with Y or N:" yn
    case $yn in
        [Yy]* ) return 1;;
        [Nn]* ) return 0;;
        * ) echo "Please answer Y or N.";;
    esac
  done
}

# Checks (and executes) if the user would want an SVN UP of the current working directory.
run_svn_up() {
  if [ -z "$1" ]; then
    echo "FATAL ERROR Parameter cannot be blank!"
    exit
  fi

  dirOKCheck=$1
  ask_yes_or_no "Is it ok to run SVN UP for the directory $dirOKCheck ?"
  if [ "$?" -eq 1 ]; then
    ask_yes_or_no "Would you want to exclude all EXTERNALS from this SVN UP?"
    if [ "$?" -eq 1 ]; then
      svn up $dirOKCheck --ignore-externals
    else
      svn up $dirOKCheck
    fi

    echo "Update Successful!"
  else
    echo "Canceled.  WARNING: Not running SVN UP on your directory can cause more conflict errors and outside revision issues!"
  fi
}

CURRENT_SVN_VERSION=`svn help | grep '^Subversion command' | sed -e 's/^Subversion command-line client, version //'`

# Check status of SVN VERSION on the server compared to 1.6.17 (which has the latest and greatest commands)
vercomp $CURRENT_SVN_VERSION "1.6.17"

# 2 == Older, 1 == Newer Version, 0 == Same Version
NEW_SVN_ACTIONS_AVAILABLE="$?"

STRIP_TRAILING_SLASHES_PATTERN='^(.*[^/])/*$'

WORKING_DIRECTORY=`pwd`

ask_yes_or_no "The Current working directory is $WORKING_DIRECTORY  Is this Correct?"
if [ "$?" -eq 0 ]; then
  echo "OK.  Please tell me the correct working directory to use."
  read WORKING_DIRECTORY

  if [ -d "$WORKING_DIRECTORY" ]; then
    echo "Using $WORKING_DIRECTORY"
  else
    echo "Invalid working directory!  Quitting.."
    exit
  fi
fi

[[ $WORKING_DIRECTORY =~ STRIP_TRAILING_SLASHES_PATTERN ]]

# Change to the working directory
cd $WORKING_DIRECTORY

# Show the initial options to choose from..
select opt in $OPTIONS; do
   # Exit the script
   if [ "$opt" = "Quit" ]; then
    echo "Goodbye."
    exit
   # Merge from a BRANCH to the TRUNK - Needs to be done on the TRUNK Server
   elif [ "$opt" = "MergeFromBranchToTrunk" ]; then
    echo ":: WARNING :: If you are NOT on the server where your TRUNK exists, please exit now!"

    echo "Please enter the URL to the SVN Branch you would like to Merge your Trunk with?"
    read SVN_BRANCH

    # Kill trailing slashes...
    [[ $SVN_BRANCH =~ STRIP_TRAILING_SLASHES_PATTERN ]]

    # Run method to ask if we want to update the Trunk's local working copy (this is usually true)
    run_svn_up $WORKING_DIRECTORY

    # Are we on an older version of SVN without the new merge commands?  If so, we need to get the TRUNK URL/Revision Info to merge with
    if [ "$NEW_SVN_ACTIONS_AVAILABLE" -eq 2 ]; then
      SVN_TRUNK=`svn info $WORKING_DIRECTORY | grep '^URL' | sed -e 's/^URL: //'`

      ask_yes_or_no "I found that the current URL for Trunk in $WORKING_DIRECTORY is $SVN_TRUNK .  Is this Correct?"
      if [ "$?" -eq 0 ]; then
        echo "No?  Please supply the correct SVN URL to the Trunk"
        echo "Example:: https://mysvnserver.com/svn/trunk"
        read SVN_TRUNK
      fi

      # Kill trailing slashes...
      [[ $SVN_TRUNK =~ STRIP_TRAILING_SLASHES_PATTERN ]]

      # SVN_TRUNK_REVISION=`svn info $SVN_TRUNK | grep '^Revision:' | sed -e 's/^Revision: //'`
      SVN_TRUNK_REVISION='HEAD'

      # This is normally the default for merges, but just in case we don't want to merge with the HEAD for whatever reason...
      ask_yes_or_no "The default revision for the TRUNK to merge a Branch with is $SVN_TRUNK_REVISION . Would you like to change this?"
      if [ "$?" -eq 1 ]; then
        echo "OK.  Please tell me the correct LAST REVISION then: "
        read SVN_TRUNK_REVISION
      fi

      # Get the Revision # for which the Branch was cut.
      SVN_BRANCH_REVISION=`svn log $SVN_BRANCH --stop-on-copy -r0:HEAD --limit 1 | grep '^r' | cut -d"r" -f2 | cut -d" " -f1`

      ask_yes_or_no "The revision at which this Branch was created was found to be Revision $SVN_BRANCH_REVISION . Is this Correct?"
      if [ "$?" -eq 0 ]; then
        echo "OK.  Please tell me the correct LAST REVISION then: "
        read SVN_BRANCH_REVISION
      fi
    fi

    ask_yes_or_no "Would you like to do a DRY RUN Merge Test first?  This is VERBOSE ONLY and does not actually merge"
    if [ "$?" -eq 1 ]; then
      # If we are on an older version of SVN, use the old merge commands.  Otherwise, use the new reintegrate...
      if [ "$NEW_SVN_ACTIONS_AVAILABLE" -eq 2 ]; then
        echo -e "RUNNING: svn merge --dry-run -r$SVN_BRANCH_REVISION:$SVN_TRUNK_REVISION $SVN_BRANCH $WORKING_DIRECTORY"
        svn merge --dry-run -r$SVN_BRANCH_REVISION:$SVN_TRUNK_REVISION $SVN_BRANCH .
      else
        echo -e "RUNNING: svn merge --dry-run --reintegrate $SVN_BRANCH $WORKING_DIRECTORY"
        svn merge --dry-run --reintegrate $SVN_BRANCH
      fi
    fi

    ask_yes_or_no "Would you like to run the MERGE now?"
    if [ "$?" -eq 1 ]; then
      # If we are on an older version of SVN, use the old merge commands.  Otherwise, use the new reintegrate...
      if [ "$NEW_SVN_ACTIONS_AVAILABLE" -eq 2 ]; then
        echo -e "RUNNING: svn merge -r$SVN_BRANCH_REVISION:$SVN_TRUNK_REVISION $SVN_BRANCH $WORKING_DIRECTORY"
        svn merge -r$SVN_BRANCH_REVISION:$SVN_TRUNK_REVISION $SVN_BRANCH .
      else
        echo -e "RUNNING: svn merge --reintegrate $SVN_BRANCH $WORKING_DIRECTORY"
        svn merge --reintegrate $SVN_BRANCH
      fi
      show_available_options "Merge Complete!"
    else
      show_available_options "Canceled Merge !"
    fi
   elif [ "$opt" = "MergeFromTrunkToBranch" ]; then
    echo ":: WARNING :: If you are NOT on the server where your BRANCH exists, please exit now!"

    echo "Please, enter the full SVN URL to the Trunk"
    echo "Example:: https://mysvnserver.com/svn/trunk"
    read SVN_TRUNK

    # Kill trailing slashes...
    [[ $SVN_TRUNK =~ STRIP_TRAILING_SLASHES_PATTERN ]]

    run_svn_up $WORKING_DIRECTORY

    if [ $NEW_SVN_ACTIONS_AVAILABLE -eq 2 ]; then
      echo "Please enter the Revision Number at which this Branch last merged with the Trunk.  NOTE: If you do not have this number yet, PLEASE STOP NOW!"
      read SVN_TRUNK_REVISION

      SVN_BRANCH=`svn info $WORKING_DIRECTORY | grep '^URL' | sed -e 's/^URL: //'`
      ask_yes_or_no "I found that the current URL for Branch in $WORKING_DIRECTORY is $SVN_BRANCH .  Is this Correct?"
      if [ "$?" -eq 0 ]; then
        echo "No?  Please supply the correct SVN URL to the Branch"
        echo "Example:: https://mysvnserver.com/svn/branch/myAwesomeBranch"
        read SVN_BRANCH
      fi

      # Kill trailing slashes...
      [[ $SVN_BRANCH =~ STRIP_TRAILING_SLASHES_PATTERN ]]

      echo "What REVISION NUMBER for this Branch would you want to merge with the Trunk?  NOTE: If you do not have this number yet, PLEASE STOP NOW!"
      read SVN_BRANCH_REVISION
    fi

    ask_yes_or_no "Would you like to do a DRY RUN test first?"
    if [ "$?" -eq 1 ]; then
      if [ "$NEW_SVN_ACTIONS_AVAILABLE" -eq 2 ]; then
        echo -e "RUNNING: svn merge --dry-run -r$SVN_BRANCH_REVISION:$SVN_TRUNK_REVISION $SVN_TRUNK $WORKING_DIRECTORY"
        svn merge --dry-run -r$SVN_BRANCH_REVISION:$SVN_TRUNK_REVISION $SVN_TRUNK .
      else
        echo -e "RUNNING: svn merge --dry-run $SVN_TRUNK $WORKING_DIRECTORY"
        svn merge --dry-run $SVN_TRUNK
      fi
    fi

    ask_yes_or_no "Would you like to run the MERGE now?"
    if [ "$?" -eq 1 ]; then
      if [ "$NEW_SVN_ACTIONS_AVAILABLE" -eq 2 ]; then
        echo -e "RUNNING: svn merge -r$SVN_BRANCH_REVISION:$SVN_TRUNK_REVISION $SVN_TRUNK $WORKING_DIRECTORY"
        svn merge --dry-run -r$SVN_BRANCH_REVISION:$SVN_TRUNK_REVISION $SVN_TRUNK .
      else
        echo -e "RUNNING: svn merge $SVN_TRUNK"
        svn merge --dry-run $SVN_TRUNK
      fi
      show_available_options "Merge Complete!"
    else
      show_available_options "Canceled Merge!"
    fi
   elif [ "$opt" = "Switch" ]; then
    echo "WARNING: If you are NOT on the server where you need to switch from 1 tag to another, please exit now!"

    echo "Please supply the URL to the Tag want to switch to."
    echo "Example:: https://mysvnserver.com/svn/tags/someAwesomeTag"
    read SVN_TAG

    # Kill trailing slashes...
    [[ $SVN_TAG =~ STRIP_TRAILING_SLASHES_PATTERN ]]

    ask_yes_or_no "Is this correct?  I am about to run switch with the following command: svn switch $SVN_TAG $WORKING_DIRECTORY"
    if [ "$?" -eq 0 ]; then
      show_available_options "Canceled SWITCH Command!"
    else
      ask_yes_or_no "Would you like me to run the switch command now?"
      if [ "$?" -eq 1 ]; then
        echo -e "RUNNING: svn switch $SVN_TAG $WORKING_DIRECTORY"

        svn switch $SVN_TAG
        show_available_options "Switch Successful!"
      else
        show_available_options "Canceled Switch!"
      fi
    fi
   else
    show_available_options "Invalid Option!  Please choose one of the following:"
   fi
done