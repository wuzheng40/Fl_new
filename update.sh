#!/bin/sh
cp -r /home/welefen/Document/www/develop/Fl/* .
git add *
git commit -a -m "update"
git push -u origin master; 
