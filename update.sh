#!/bin/sh
cp -r /home/welefen/Documents/www/develop/Fl/* .
git add *
git commit -a -m "update"
git push -u origin master; 
