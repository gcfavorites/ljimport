<?php 

$user = array();
$user["login"] = "login";      //login for LJ
$user["pass"] = "password";               //you can input password manually, run with "-p" for it
$user["blogid"] = "login";     //run with "-l" to view ids of blogs
$user["rss"] = array(             //URLs of RSS to import
   "http://habrahabr.ru/rss", 
   "http://ubuntu.ru/rss/news",
);
$user["nolift"] = 1;              //1 means deprecating import of lifted entries (for diary.ru)
$user["linktext"] = "URL"; //text of link adding in every post (if empty, link will not be added)
$log_file = "store/log";          //path for log file
$store_path = "store/";           //folder for saving files (must exist and be writable)


?>