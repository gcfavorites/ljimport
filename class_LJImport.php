<?php 
/**
 * @author Riateche <ri@idzaaus.ru>
 */

include dirname(__FILE__)."/blogger_api/class_BloggerClient.php";


class LJImport {
   var $log_file = "ljimport.log"; //filename of log
   var $verbose = true; //if true ilog() will print messages in stdout
   var $store_path = "store/"; //path to folder with write perms (ending with "/")
   
   /*
    * function for all logging
    * @param string $text message to be logged
    */
   function ilog($text) {
      if (!$this->logfd) $this->logfd = fopen($this->log_file, "a");
      $date = date("d.m.y H:i:s");
      fputs($this->logfd, "[$date] $text\n");   
      if ($this->verbose) print $text."\n";
   }
   
   /*
    * @param string $u user login
    * @param string $p user password
    * @return array|int 0 if fail, -1 if wrong password, else array of available blogs
    */
   function list_blogs($u, $p) {
      $this->ilog("  List blogs for user $u");
      $blog = new BloggerClient($u, $p);
      $list = $blog->getUsersBlogs();
      if (!$list) return 0;
      if (strpos($list["faultString"], "Invalid login") !== false) return -1;
      return $list;
   }
   
   /* doing import for one user
    * @param array $row user information
    * 
    */
   function do_import($row) {
      $rss = $row["rss"];
      if (gettype($row["rss"]) == "string") $rss = split("\n", $rss);
      $this->ilog("  Import RSS for user {$row["login"]}");
      $l = $row["login"];
      $p = $row["pass"];
      $blogid = $row["blogid"];
      $last = $this->get_last();
      //$this->ilog("    Last entry was at $last");
      $blog = new BloggerClient($l, $p);
      $newlast = $last;   
      $feednum = 0;
      $now = time();
      foreach ($rss as $url) {
         $feednum++;
         $url = trim($url);
         if (!strlen($url)) continue;
         $result = $this->parse($url);
         if ($result < 0) {
            $this->ilog("    Can't fetch RSS: $url");
         } else {
            $this->ilog("    Fetched: $url");
            for ($i = count($result) - 1; $i >= 0; $i--) {
               $item = $result[$i];
               if ($item["date"] > $last) {
                  if ($ver) print "New entry: {$item["title"]}<br>";
                  $this->ilog("      New entry: {$item["title"]} at {$item["date"]}");
                  //$item["text"] = mb_substr($item["text"], 0, 2000, 'UTF-8');
                  $item["text"] = $this->process_text($item["text"]);
                  $link = $item["link"];
                  $linktext = $row["linktext"];
                  if (strlen($linktext) > 0) {
                     $item["text"] .= "<br><br><a class='rss_link' href='$link'>$linktext</a>";
                  }
                  
                  $upped = strpos($item["text"], "запись создана");
                  if ($upped !== false && $row["nolift"] == 1) {
                     if ($ver) print "Skip<br>";                  
                     $this->ilog("      Skipping (lifted entry)");
                     $imp = "Lifted entry";
                  } else {
                     $post = "<title>{$item["title"]}</title>{$item["text"]}";
                     if ($item["title"] == "Без заголовка") $post = $item["text"];
                     $imp = $blog->newPost($blogid, $post, true);
                     $this->ilog("      Server answer: $imp");
                     if (strpos($imp, "Invalid password") !== false || strpos($imp, "temporarily banned") !== false) {
                        //to interrupt is better than to be banned
                        $this->ilog("do_import interrupting");
                        return;
                     } else if (strpos($imp, "Client error:") == 0) {
                        //we think it was successful
                        if ($newlast < $item["date"]) $newlast = $item["date"];
                     }
                  }
               }
            }
         }
      }
      if ($last < $newlast) $last = $newlast;
      $this->set_last($last);
      //$this->ilog("    Setting last=$last");
      $this->ilog("  Import finished");
   }
   
   /** Save time of last imported entry to a file
    * (replasement for working with database).
    * do_import() will not import entry if its time is less than saved.
    * do_import() saves the bigger of dates of imported entries.
    * @param integer $value time in UTC
    */
   function set_last($value) {
      return file_put_contents($this->store_path."lasttime", $value);
   }
   
   /**
    * read last time from file (see set_last)
    */
   function get_last() {
      return @file_get_contents($this->store_path."lasttime") * 1;
   }

   /**
    * Function sets max length of string to 50000 bytes.
    * Also, converts cut tag from diary.ru style to lj style.
    * @param string $t text to verify
    * @return string true text
    */
   function process_text($t) {
      if (strlen($t) > 50000) $t = substr($t, 0, 50000)." (...)";
      $morenum = 0;
      while (1) {
         $morenum++;
         $more = "<a name='more$morenum'></a>";
         $moreend = "<a name='more{$morenum}end'></a>";
         $pos = strpos($t, $more);
         $pose = strpos($t, $moreend);
   //      print htmlspecialchars("debug\n".$more."\n".$moreend."\n".$pos."\n".$pose."\n");
         if ($pos === false || $pose === false) break;
         $t = str_replace($more, "<lj-cut text='читать далее'>", $t);
         $t = str_replace($moreend, "</lj-cut>", $t);         
      }
      return $t;
   }

   /**
    * Prevent script from adding too old entries to journal. It sets "last"
    * to now (see set_last() for details).
    * After running this function, do_import will think that old entries
    * was alreay added.
    * @param string $login
    */
   function forget_old($login) {
      $this->ilog("  Forget old for user $login");
      $this->set_last(time());
   }

   /** parse rss feed
    * Returns array of entries if success, 0 if cannot fetch, -1 if 
    * fetched file isn't RSS.
    * Each entry is array with keys: title, text, link, date
    * Date is converting to UTC.
    * Text is html with some modifications for diary.ru.
    * Function recognize cp1251 and utf8 encodings.
    * @param string $url feed address
    * @return array|integer
    */
   function parse($url) {
      if (substr($url, 0, 7) != "http://") $url = "http://$url";
      $rss = file_get_contents($url);
      if (!$rss) return -1;
      if (strpos($rss, "<rss") === false) return -1;
      $rss = str_replace("&lt;/param&gt;</description>", "</description>", $rss); //diary.ru fix
      $rss = str_replace("<![CDATA[", "", $rss); 
      $rss = str_replace("]]>", "", $rss); 
      $test = substr_count($rss, chr(208));
      if ($test > strlen($rss) / 30) { 
         //it's utf-8
      } else {
         //we think it's windows-1251 encoding
         $rss = mb_convert_encoding($rss, "UTF-8", "CP-1251");      
      }
      $s = strpos($rss, "<item");
      $rss = substr($rss, $s, strlen($rss)-$s);
      preg_match_all("/<title>([^<]+)<\/title>/", $rss, $r["title"]);
      preg_match_all("/<description>([^<]+)<\/description>/", $rss, $r["text"]);
      preg_match_all("/<pubDate>([^<]+)<\/pubDate>/", $rss, $r["date"]);
      preg_match_all("/<link>([^<]+)<\/link>/", $rss, $r["link"]);
      $result = array();
      foreach ($r["title"][1] as $i=>$e) {
         $txt = $r["text"][1][$i];
         $txt = str_replace("&gt;", ">", $txt);
         $txt = str_replace("&lt;", "<", $txt);
         $txt = str_replace('<a "', '<a', $txt); //diary.ru fix
         $txt = str_replace("&amp;", "&", $txt);
         $txt = str_replace('a style="color:#000000;font-weight:bold;"', 'a class="diary_user"', $txt); //diary.ru fix
         $item = array(
            "title"=>$r["title"][1][$i], 
            "link"=>$r["link"][1][$i], 
            "text"=>$txt, 
            "date"=>strtotime($r["date"][1][$i])
         );
         $result[] = $item;
      }
      return $result;
   }


   
}

?>