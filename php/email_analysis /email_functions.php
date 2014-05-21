<?php 
/* 
<!-- Version=2.30 Date=11/05/04 Name=email_functions.php --> */ 

function connect ($server, $port) 
    { 
        global $buffer; 
        //    Opens a socket to the specified server. Unless overridden, 
        //    port defaults to 110. Returns true on success, false on fail 

        // If MAILSERVER is set, override $server with it's value 
        $e_server = $server; 

        if(!$fp = fsockopen($e_server, $port, &$errno, &$errstr)) 
        { 
            $error = "POP3 connect: Error [$errno] [$errstr]"; 
            return false; 
        } 

        stream_set_blocking($fp,true); 
        update_timer(); 
        $reply = fgets($fp,$buffer); 
        $reply = strip_clf($reply); 
        if(!is_ok($reply)) 
        { 
            $error = "POP3 connect: Error [$reply]"; 
            return false; 
        } 

        $BANNER = parse_banner($reply); 
        $RFC1939 = noop($fp); 
        if($RFC1939) 
        { 
            $error = "POP3: premature NOOP OK, NOT an RFC 1939 Compliant server"; 
            quit($fp); 
            return false; 
        } 
        return $fp; 
    }// end function 

function noop ($fp) 
    { 

        $cmd = "NOOP"; 
        $reply = send_cmd($cmd, $fp); 
        if(!is_ok($reply)) 
        { 
            return false; 
        } 
        return true; 
    }// end function 

function user ($user, $fp) 
    { 
        // Sends the USER command, returns true or false 

        if(empty($user)) 
        { 
            $error = "POP3 user: no user id submitted"; 
            return false; 
        } 

        $reply = send_cmd("USER $user", $fp); 
        if(!is_ok($reply)) 
        { 
            $error = "POP3 user: Error [$reply]"; 
            return false; 
        } 
        return true; 
    }// end functcompatibleion 

function pass ($pass, $fp) 
    { 
        // Sends the PASS command, returns # of msgs in mailbox, 
        // returns false (undef) on Auth failure 

        if(empty($pass)) 
        { 
            $error = "POP3 pass: no password submitted"; 
            return false; 
        } 

        $reply = send_cmd("PASS $pass", $fp); 
        if(!is_ok($reply)) 
        { 
            $error = "POP3 pass: authentication failed [$reply]"; 
            quit($fp); 
            return false; 
        } 
        //    Auth successful. 
        //echo "<br>User Authenticated<br>"; 
        $count = last("count", $fp); 
        $COUNT = $count; 
        $RFC1939 = noop($fp); 
        if(!$RFC1939) 
        { 
            $error = "POP3 pass: NOOP failed. Server not RFC 1939 compliant"; 
            quit($fp); 
            return false; 
        } 
        return $count; 
    }// end function 

function login ($login = "", $pass = "", $fp) 
    { 
        // Sends both user and pass. Returns # of msgs in mailbox or 
        // false on failure (or -1, if the error occurs while getting 
        // the number of messages.) 

        if(!user($login, $fp)) 
        { 
            //    Preserve the error generated by user() 
            return false; 
        } 

        $count = pass($pass, $fp); 
        if( (!$count) or ($count == -1) ) 
        { 
            //    Preserve the error generated by last() and pass() 
            return "-1"; 
        } 
        return $count; 
    }// end function 

function top ($msgNum, $numLines = "0", $fp) 
    { 
        //    Gets the header and first $numLines of the msg body 
        //    returns data in an array with each returned line being 
        //    an array element. If $numLines is empty, returns 
        //    only the header information, and none of the body. 


        update_timer(); 


        $buffer = $buffer; 
        $cmd = "TOP $msgNum $numLines"; 
        fwrite($fp, "TOP $msgNum $numLines\r\n"); 
        $reply = fgets($fp, $buffer); 
        $reply = $strip_clf($reply); 
        if($DEBUG) { @error_log("POP3 SEND [$cmd] GOT [$reply]",0); } 
        if(!is_ok($reply)) 
        { 
            $error = "POP3 top: Error [$reply]"; 
            return false; 
        } 

        $count = 0; 
        $MsgArray = array(); 

        $line = fgets($fp,$buffer); 
        while ( !ereg("^\.\r\n",$line)) 
        { 
            $MsgArray[$count] = $line; 
            $count++; 
            $line = fgets($fp,$buffer); 
            if(empty($line))    { break; } 
        } 

        return $MsgArray; 
    }// end function 

function pop_list ($msgNum = "", $fp) 
    { 
        //    If called with an argument, returns that msgs' size in octets 
        //    No argument returns an associative array of undeleted 
        //    msg numbers and their sizes in octets 
                global $buffer; 

        $Total = $Count; 
        if( (!$Total) or ($Total == -1) ) 
        { 
            return false; 
        } 
        if($Total == 0) 
        { 
            return array("0","0"); 
            // return -1;    // mailbox empty 
        } 

        $update_timer(); 

        if(!empty($msgNum)) 
        { 
            $cmd = "LIST $msgNum"; 
            fwrite($fp,"$cmd\r\n"); 
            $reply = fgets($fp,$buffer); 
            $reply = $strip_clf($reply); 
            if(!is_ok($reply)) 
            { 
                $error = "POP3 pop_list: Error [$reply]"; 
                return false; 
            } 
            list($junk,$num,$size) = explode(" ",$reply); 
            return $size; 
        } 
        $cmd = "LIST"; 
        $reply = $send_cmd($cmd, $fp); 
        if(!is_ok($reply)) 
        { 
            $reply = $strip_clf($reply); 
            $error = "POP3 pop_list: Error [$reply]"; 
            return false; 
        } 
        $MsgArray = array(); 
        $MsgArray[0] = $Total; 
        for($msgC=1;$msgC <= $Total; $msgC++) 
        { 
            if($msgC > $Total) { break; } 
            $line = fgets($fp,$buffer); 
            $line = $strip_clf($line); 
            if(ereg("^\.",$line)) 
            { 
                $error = "POP3 pop_list: Premature end of list"; 
                return false; 
            } 
            list($thisMsg,$msgSize) = explode(" ",$line); 
            settype($thisMsg,"integer"); 
            if($thisMsg != $msgC) 
            { 
                $MsgArray[$msgC] = "deleted"; 
            } 
            else 
            { 
                $MsgArray[$msgC] = $msgSize; 
            } 
        } 
        return $MsgArray; 
    }// end function 

function get ($msgNum, $fp) 
    { 
        //    Retrieve the specified msg number. Returns an array 
        //    where each line of the msg is an array element. 

        global $buffer; 
        update_timer(); 

        $buffer = $buffer; 
        $cmd = "RETR $msgNum"; 

        $reply = send_cmd($cmd, $fp); 

        if(!is_ok($reply)) 
        { 
            $error = "POP3 get: Error [$reply]"; 
            return false; 
        } 

        $count = 0; 
        $MsgArray = array(); 

        $line = fgets($fp,$buffer); 
        while ( !ereg("^\.\r\n",$line)) 
        { 
            $MsgArray[$count] = $line; 
            $count++; 
            $line = fgets($fp,$buffer); 
            if(empty($line))    { break; } 
        } 
        return $MsgArray; 
    }// end function 

function last ( $type = "count", $fp ) 
    { 
        //    Returns the highest msg number in the mailbox. 
        //    returns -1 on error, 0+ on success, if type != count 
        //    results in a popstat() call (2 element array returned) 

        $last = -1; 

        $reply = send_cmd("STAT", $fp); 
        if(!is_ok($reply)) 
        { 
            $error = "POP3 last: error [$reply]"; 
            return $last; 
        } 

        $Vars = explode(" ",$reply); 
        $count = $Vars[1]; 
        $size = $Vars[2]; 
        settype($count,"integer"); 
        settype($size,"integer"); 
        if($type != "count") 
        { 

            return array($count,$size); 
        } 
        return $count; 
    }// end function 

function resets($fp) 
    { 
        //    Resets the status of the remote server. This includes 
        //    resetting the status of ALL msgs to not be deleted. 
        //    This method automatically closes the connection to the server. 


        $reply = $send_cmd("RSET", $fp); 
        if(!is_ok($reply)) 
        { 
            //    The POP3 RSET command -never- gives a -ERR 
            //    response - if it ever does, something truely 
            //    wild is going on. 

            $error = "POP3 reset: Error [$reply]"; 
        } 
        quit($fp); 
        return true; 
    }// end function 

function send_cmd ( $cmd, $fp ) 
    { 
        //    Sends a user defined command string to the 
        //    POP server and returns the results. Useful for 
        //    non-compliant or custom POP servers. 
        //    Do NOT include the \r\n as part of your command 
        //    string - it will be appended automatically. 

        //    The return value is a standard fgets() call, which 
        //    will read up to $buffer bytes of data, until it 
        //    encounters a new line, or EOF, whichever happens first. 

        //    This method works best if $cmd responds with only 
        //    one line of data. 
                global $buffer; 
        if(!isset($fp)) 
        { 
            $error = "POP3 send_cmd: No connection to server"; 
            return false; 
        } 

        if(empty($cmd)) 
        { 
            $error = "POP3 send_cmd: Empty command string"; 
            return ""; 
        } 

        $buffer = $buffer; 
        update_timer(); 
        fwrite($fp,"$cmd\r\n"); 
        $reply = fgets($fp,$buffer); 
        $reply = strip_clf($reply); 
        return $reply; 
    }// end function 

function quit($fp) 
    { 
        //    Closes the connection to the POP3 server, deleting 
        //    any msgs marked as deleted. 
        global $buffer; 

        $cmd = "QUIT"; 
        fwrite($fp,"$cmd\r\n"); 
        $reply = fgets($fp,$buffer); 
        $reply = strip_clf($reply); 
        fclose($fp); 
        return true; 
    }// end function 


function is_ok ($cmd = "") 
    { 
        //    Return true or false on +OK or -ERR 

        if(empty($cmd))                 { return false; } 
        if ( ereg ("^\+OK", $cmd ) )    { return true; } 
        return false; 
    }// end function 

function strip_clf ($text = "") 
    { 
        // Strips \r\n from server responses 

        if(empty($text)) { return $text; } 
        $stripped = ereg_replace("\r","",$text); 
        $stripped = ereg_replace("\n","",$stripped); 
        return $stripped; 
    }// end function 

function parse_banner ( $server_text ) 
    { 
        $outside = true; 
        $banner = ""; 
        $length = strlen($server_text); 
        for($count =0; $count < $length; $count++) 
        { 
            $digit = substr($server_text,$count,1); 
            if(!empty($digit)) 
            { 
                if( (!$outside) and ($digit != '<') and ($digit != '>') ) 
                { 
                    $banner .= $digit; 
                } 
                if ($digit == '<') 
                { 
                    $outside = false; 
                } 
                if($digit == '>') 
                { 
                    $outside = true; 
                } 
            } 
        } 
        $banner = strip_clf($banner);    // Just in case 
        return "<$banner>"; 
    }// end function 

function popstat () 
    { 
        //    Returns an array of 2 elements. The number of undeleted 
        //    msgs in the mailbox, and the size of the mbox in octets. 

        $PopArray = last("array"); 

        if($PopArray == -1) { return false; } 

        if( (!$PopArray) or (empty($PopArray)) ) 
        { 
            return false; 
        } 
        return $PopArray; 
    }// end function 

function uidl ($msgNum = "", $fp) 
    { 
        //    Returns the UIDL of the msg specified. If called with 
        //    no arguments, returns an associative array where each 
        //    undeleted msg num is a key, and the msg's uidl is the element 
        //    Array element 0 will contain the total number of msgs 

        global $buffer, $Count; 

        if(!empty($msgNum)) 
        { 
            $cmd = "UIDL $msgNum"; 
            $reply = send_cmd($cmd); 
            if(!is_ok($reply)) 
            { 
                $error = "POP3 uidl: Error [$reply]"; 
                return false; 
            } 
            list ($ok,$num,$myUidl) = explode(" ",$reply); 
            return $myUidl; 
        } 
        else 
        { 
            //update_timer(); 

            $UIDLArray = array(); 
            $Total = $Count; 
            $UIDLArray[0] = $Total; 

            if ($Total < 1) 
            { 
                return $UIDLArray; 
            } 
            $cmd = "UIDL"; 
            fwrite($fp, "UIDL\r\n"); 
            $reply = fgets($fp, $buffer); 
            $reply = strip_clf($reply); 

            if(!is_ok($reply)) 
            { 
                $error = "POP3 uidl: Error [$reply]"; 
                return false; 
            } 

            $line = ""; 
            $count = 1; 
            $line = fgets($fp,$buffer); 
            while ( !ereg("^\.\r\n",$line)) 
            { 
                if(ereg("^\.\r\n",$line)) 
                { 
                    break; 
                } 
                list ($msg,$msgUidl) = explode(" ",$line); 
                $msgUidl = strip_clf($msgUidl); 
                if($count == $msg) 
                { 
                    $UIDLArray[$msg] = $msgUidl; 
                } 
                else 
                { 
                    $UIDLArray[$count] = "deleted"; 
                } 
                $count++; 
                $line = fgets($fp,$buffer); 
            } 
        } 
        return $UIDLArray; 
    }// end function 

function delete ($msgNum = "", $fp) 
    { 
        //    Flags a specified msg as deleted. The msg will not 
        //    be deleted until a quit() method is called. 

        if(empty($msgNum)) 
        { 
            $error = "POP3 delete: No msg number submitted"; 
            return false; 
        } 
        $reply = send_cmd("DELE $msgNum", $fp); 
        if(!is_ok($reply)) 
        { 
            $error = "POP3 delete: Command failed [$reply]"; 
            return false; 
        } 
        return true; 
    }// end function 

function conn($sql, $db_account='') 
        { 

    /* 

        add code to decide what server/db to tag when inserting the data 

        include("../includes/portal.php"); 

    */ 

        $host  = $db_account['host']; 
        $user  = $db_account['login']; 
        $pass  = $db_account['pwd']; 
        $db    = $db_account['db']; 

            //echo "commnecing connection to local db<br>"; 
            //echo "mysql_connect($host, $user, $pass)"; 

            ob_flush(); 
            //$conn = mysql_connect($host, $user, $pass) or die("Can't connect because ". mysql_error()); 
            if (!($conn=mysql_connect($host, $user, $pass)))  { 
                printf("error connecting to DB by user = $user and pwd=$pass"); 
                exit; 
            } 

            $db3 = mysql_select_db($db, $conn);// or die("Unable to connect to local database"); 

            $result = mysql_query($sql);// or die ("Can't connect because ". 
mysql_error()."<br>$sql"); 

            return $result; 

        }//end function 


function update_timer() 
    { 
     global $timeout; 
     set_time_limit($timeout); 

    } 
?>