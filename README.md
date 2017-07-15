# craigscan

A very crude and simple craigslist scanner built in PHP. It basically queries Craigslist given a list of terms and parses the result DOM. It keeps track of results in a mysql database so that repeat listings are identified and ignored. 

This is running as a 1 hour cron on a raspberry pi and searches for anything of interest from my local craigslist regions. 

Originally hacked together in about an hour or two in a fit of rage after missing out on a good deal because I didn't see it in time. 
