<?php 

/**
 * LJ Import
 * 
 * Script to import RSS feed to LJ blog
 * @author Riateche <ri@idzaaus.ru>
 * 
 * Command line params:
 * -p    Ask for password
 * -q    Be quiet
 * -F    Forget old entries
 * -l    Print list of available blogs
 * If nor -F nor -l were mentioned, script imports feeds.
 * 
 */





include dirname(__FILE__)."/class_LJImport.php";
include dirname(__FILE__)."/config.php";

$imp = new LJImport();
$imp->store_path = $store_path;
$imp->log_file = $log_file;

array_shift($argv);
$cmd = " ".implode(" ", $argv);

if (strpos($cmd, "q") > 0) {
   $imp->verbose = false;
}
if (strpos($cmd, "F") > 0) {
   $imp->forget_old($user["login"]);
   die();
}
if (strpos($cmd, "p") > 0) {
   print "Input password:";
   $line = trim(fgets(STDIN));
   if (!$line) die();
   $user["pass"] = $line;   
}
if (strpos($cmd, "l") > 0) {
   $ids = $imp->list_blogs($user["login"], $user["pass"]);
   if ($ids == -1) {
      $imp->ilog("Wrong login or password");
   } else if ($imp == 0) {
      $imp->ilog("Failed");
   }
   foreach($ids as $id=>$ar) {
      $imp->ilog("  id=\"{$ar["blogid"]}\"\n    name: {$ar["blogName"]}");
      $imp->ilog("    url: {$ar["url"]}");
   }
   die();
}

$imp->do_import($user);


?>