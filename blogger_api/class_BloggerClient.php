<?php
/**
 * @author Beau Lebens <beau@dentedreality.com.au>
 * @author Bill Lazar <bill@billsaysthis.com>
 */

/***************************************************************************\
         ===========================
         PHP BLOG API IMPLEMENTATION
         ===========================
 This library of PHP code was produced by the work of two people;
   Beau Lebens
   Bill Lazar
 
 The intention was to create a set of classes which provide access to the
 APIs of the most common web log systems - Blogger.com and MoveableType.

 They are based on the PHP XML-RPC library which availabled from
 http://xmlrpc.usefulinc.com/ and which is included in this distribution.
 
 This all started when Beau Lebens, the Primary Consultant of DentedReality
 (probably where you downloaded this) created a set of functions so that
 he could access the Blogger.com API.
 
 Bill Lazar then came along and turned those functions into an OO class,
 making the code more usable, more portable, and more extensible...
 
 Please email the authors directly with comments/feedback/bugs regarding
 this library, and also to share your project with us!
 
   Beau Lebens: beau@dentedreality.com.au
   Bill Lazar: bill@billsaysthis.com
 
 Resources/Links;
 ----------------
 Blogger: http://www.blogger.com/
 PHP XML-RPC: http://xmlrpc.usefulinc.com/
 Blogger API: http://plant.blogger/api/
 Get a Blogger AppID: http://plant.blogger.com/api/register.html
 FireStarter Technologies: http://www.firestarter.com.au/
 
 DentedReality: http://www.dentedreality.com.au/
 BillSaysThis: http://www.billsaysthis.com/
 
\***************************************************************************/




require_once(dirname(__FILE__)."/xmlrpc.php");

class BloggerClient {

    var $appID = "Idzaaus LJ Importer";
    var $bServer = "livejournal.com";
    var $bPath = "/interface/blogger/";
    var $apiName = "blogger";
    var $blogClient;
    var $XMLappID;
    var $XMLusername;
    var $XMLpassword;

    function bloggerclient($username, $password)
    {
        // Connect to blogger server
            if (!$this->connectToBlogger()) {
                    return false;
            }

        // Create variables to send in the message
        $this->XMLappID    = new xmlrpcval($this->appID, "string");
        $this->XMLusername = new xmlrpcval($username, "string");
        $this->XMLpassword = new xmlrpcval($password, "string");
        
        return $this;
    }

    function getUsersBlogs()
    {
        // Construct query for the server
        $r = new xmlrpcmsg($this->apiName . ".getUsersBlogs", array($this->XMLappID, $this->XMLusername, $this->XMLpassword));
        // Send the query
        $r = $this->exec($r);
        return $r;
    }

    function getUserInfo()
    {
        $r = new xmlrpcmsg($this->apiName . ".getUserInfo", array($this->XMLappID, $this->XMLusername, $this->XMLpassword));
        $r = $this->exec($r);
        return $r;
    }
        
    function getRecentPosts($blogID, $numPosts)
    {
        $XMLblogid = new xmlrpcval($blogID, "string");
        $XMLnumPosts = new xmlrpcval($numPosts, "int");
        
        $r = new xmlrpcmsg($this->apiName . ".getRecentPosts", array($this->XMLappID, $XMLblogid, $this->XMLusername, $this->XMLpassword, $XMLnumPosts));
          $r = $this->exec($r);

        return $r;
    }
        
    function getPost($postID)
    {
        $XMLpostid = new xmlrpcval($postID, "string");
        $r = new xmlrpcmsg($this->apiName . ".getPost", array($this->XMLappID, $XMLpostid, $this->XMLusername, $this->XMLpassword));
          $r = $this->exec($r);
        return $r;
    }

    function newPost($blogID, $textPost, $publish=false)
    {
        $XMLblogid = new xmlrpcval($blogID, "string");
        $XMLcontent = new xmlrpcval($textPost, "string");
        $XMLpublish = new xmlrpcval($publish, "boolean");
        $r = new xmlrpcmsg($this->apiName . ".newPost", array($this->XMLappID, $XMLblogid, $this->XMLusername, $this->XMLpassword, $XMLcontent, $XMLpublish));
        $r = $this->exec($r);
        return $r;
    }
        
    function editPost($blogID, $textPost, $publish=false)
    {
        $XMLblogid = new xmlrpcval($blogID, "string");
        $XMLcontent = new xmlrpcval($textPost, "string");
        $XMLpublish = new xmlrpcval($publish, "boolean");
        $r = new xmlrpcmsg($this->apiName . ".editPost", array($this->XMLappID, $XMLblogid, $this->XMLusername, $this->XMLpassword, $XMLcontent, $XMLpublish));
        $r = $this->exec($r);
        return $r;
    }
        
    function deletePost($postID, $publish=false)
    {
        $XMLpostid = new xmlrpcval($postID, "string");
        $XMLpublish = new xmlrpcval($publish, "boolean");
        $r = new xmlrpcmsg($this->apiName . ".deletePost", array($this->XMLappID, $XMLpostid, $this->XMLusername, $this->XMLpassword, $XMLpublish));
        $r = $this->exec($r);
        return $r;
    }
        
    function getTemplate($blogID, $template="main")
    {
        $XMLblogid = new xmlrpcval($blogID, "string");
        $XMLtemplate = new xmlrpcval($template, "string");
        $r = new xmlrpcmsg($this->apiName . ".getTemplate", array($this->XMLappID, $XMLblogid, $this->XMLusername, $this->XMLpassword, $XMLtemplate));
        $r = $this->exec($r);
        return $r;
    }
        
    function setTemplate($blogID, $template="archiveIndex")
    {
        $XMLblogid = new xmlrpcval($blogID, "string");
        $XMLtemplate = new xmlrpcval($template, "string");
        $r = new xmlrpcmsg($this->apiName . ".setTemplate", array($this->XMLappID, $XMLblogid, $this->XMLusername, $this->XMLpassword, $XMLtemplate));
        $r = $this->exec($r);
        return $r;
    }

    // class helper functions
    // Returns a connection object to the blogger server
    function connectToBlogger() {
        if($this->blogClient = new xmlrpc_client($this->bPath, $this->bServer)) {
                return true;
        }
        else {
                return false;
        }
    }

    function exec($req)
    {
        // Send the query
        $result_struct = $this->blogClient->send($req);
        
        $r = $this->errTest($result_struct);
        
        return $r;
    }        
    
    function errTest($result_struct)
    {
        // Check the results for an error
        if (!$result_struct->faultCode()) {
                // Get the results in a value-array
                $values = $result_struct->value();
                
                // Compile results into PHP array
                $result_array = xmlrpc_decode2($values);
                
                // Check the result for error strings.
                $valid = blogger_checkFaultString($result_array);
                
                // Return something based on the check
                if ($valid == true) {
                        $r = $result_array;
                }
                else {
                        $r = $valid;
                }
        }
        else {
                 $r = $result_struct->faultString();
        }
        
        return $r;
    }

    // Added by Beau Lebens of DentedReality 2002-02-03
    // Return the HTML required to make a form select element which is made up in the form
    // $select[$blogid] = $blogName;
    // If the user only has one blog, then it returns a string containing the name of the blog
    // in plain text, with a hidden form input containing the blogid, using the same
    // $name as it would have for the select
    function getUsersBlogsSelect($name="blog", $selected="", $extra="")
    {
        $getUsersBlogsArray = $this->getUsersBlogs();
        
        foreach($getUsersBlogsArray as $blog) {
                if (is_string($blog)) {
                        return false;
                }
                $blogs_select[$blog["blogid"]] = str_replace("&lt;", "<", $blog["blogName"]);
        }
        if (sizeof($blogs_select) > 1) {
                return display_select($name, $blogs_select, $selected, $extra);
        }
        else {
                return $getUsersBlogsArray[0]["blogName"] . " <input type=\"hidden\" name=\"$name\" value=\"" . $getUsersBlogsArray[0]["blogid"] . "\">";
        }
    }

}

function blogger_checkFaultString($bloggerResult) {
        if ($bloggerResult["faultString"]) {
                return $bloggerResult["faultString"];
        }
        else if (strpos($bloggerResult, "java.lang.Exception") !== false) {
                return $bloggerResult;
        }
        else {
                return true;
        }
}

function display_select($name, $options, $value = 0, $misc = "unset") {
        $select = "<select";
        if (strlen($name)) {
                $select .= " name=\"" . $name . "\"";
        }
        if (is_array($misc)) {
                while (list($id, $val) = each($misc)) {
                        $select .= " " . $id . "=\"" . $val . "\"";
                }
        }
        $select .= ">";
        if (is_array($options)) {
                while (list($id, $val) = each($options)) {
                        $select .= "\n<option";
                        $select .= " value=\"" . $id . "\"";
                        if (strcmp($id, $value))
                                $select .= ">";
                        else
                                $select .= " selected>";
                        $select .= htmlspecialchars($val) . "</option>";
                }
        }
        $select .= "\n</select>\n";
        return $select;
}

// A generic debugging function, parses a string/int or array and displays contents
// in an easy-to-read format, good for checking values during a script's execution
function debug($value) {
        $counter = 0;
        echo "<table cellpadding=\"3\" cellspacing=\"0\" border=\"0\" style=\"border: solid 1px #000000; background: #EEEEEE; width: 95%; margin: 20px;\" align=\"center\">\n";
        echo "<tr>\n<td colspan=\"3\" style=\"font-family: Arial; font-size: 13pt; font-weight: bold; text-align: center;\">Debugging Information</td>\n</tr>\n";
        if ( is_array($value) ) {
                echo "<tr>\n<td>&nbsp;</td>\n<td><b>Array Key</b></td>\n<td><b>Array Value</b></td>\n</tr>\n";
                foreach($value as $key=>$val) {
                        if (is_array($val)) {
                                debug($val);
                        }
                        else {
                                echo "<tr>\n<td>$counter</td>\n<td>&nbsp;" . $key . "&nbsp;</td>\n<td>&nbsp;" . $val . "&nbsp;</td>\n</tr>\n";
                        }
                        $counter++;
                }
        }
        else {
                echo "<tr>\n<td colspan=\"3\">" . $value . "</td>\n</tr>\n";
        }
        echo "</table>\n";
}

// missing convenience function
// getUserRecentPosts($blogID, $numUserPosts, $checkInPosts);

?>