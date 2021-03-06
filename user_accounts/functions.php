<?php
    // connect to database
    function connect(){
        $conn = mysql_connect('localhost', 'csc325generic', 'password');
        $db = mysql_select_db('CSC325', $conn);
        return ($conn && $db) ? $conn : false;
    }
    
    // simple mysql error handling
    function have_error($q,$r) {
        if (!$r) {
            $message = "Error in query ($q) -- mysql_error()";
            return true;
        } else {
            return false;
        }
    }

    // disconnect from database and possibly free a result
    function disconnect ($db, $result) {
        if ($result) $freed = mysql_free_result($result);
        $disconn = mysql_close($db);
        return ($disconn) ? 0 : -1;
    }
    
    // Check if is user exists
    function user_exists ($email) {
        global $db;
        $db = isset($db) ? $db : connect();
        $query = "SELET userID FROM users WHERE email = $email";
        $result = mysql_query ($query);
        if (!have_error($query,$result)) return (mysql_num_rows($result) > 0) ? true : false;
    }

    // Check that a username with corresponding password exists in the db
    function chk_user_pw ($user, $pw) {
        global $db;
        $db = (isset($db)) ? $db : connect();
        
        $query = "SELECT password FROM users
                  WHERE email = '$user'
                  AND password = '$pw';";
        
        $result = mysql_query($query, $db);

        // exit and send error message if query was unsuccessful
        if (!$result){
            $message = "Error in query ($query): mysql_error()";
            disconnect($db, NULL);
            return 0;
        }
        
        return (mysql_num_rows($result) > 0) ? true : false;
    }

    // Function for generating a random password
    function randomPasswordGen($alpha=true) {
        $salt = '0123456789'.(($alpha) ? 'abchefghjkmnpqrstuvwxyz' : '');
        for($i=0;$i<9; $i++)
            $password .= substr($salt, mt_rand(0,strlen($salt)), 1);
        return $password; 
    }

    /* PROCEDURE - bool passwordReset (string $email)
     *
     * parameters - $email: a valid username@grinnell.edu email address
     *
     * purpose - this function assigns a random password to the user
     *	      then sends out an e-mail containing the password.
     *
     * preconditions - the username must have an account in the database
     *
     * postconditions - the user's password is changed and an e-mail is sent
     *
     * produces - a boolean: TRUE if successful, FALSE if unsuccessful
     */

    function passwordReset ($email){
        // create password and connect to database
        $new_password = randomPasswordGen();
        $db = connect();
        $email = mysql_real_escape_string($email);

        // Query for email in database
        $query = "SELECT * FROM users WHERE email = '".$email."';";
        $result = mysql_query($query, $db);

        //if query was unsuccessful, print error and die
        if (!$result) {
            $message = "Error in query ($query): " . mysql_error();
            mysql_close($db);
            disconnect($db, NULL);
            die($message);
        }

        // if email exists in database, change their password
        if ($result) {
            $np_md5 = md5($new_password);
            $query2  = "UPDATE users
                        SET password = '$np_md5'
                        WHERE email = '$email';";
            $result2 = mysql_query($query2, $db);
        }


        // If successful, send e-mail to user informing them of their new password
        if ($result && $result2) {
            // Message
            $message = '<html><body>
                        <p>Your password for Grinnell Open Calender has been reset <br /><br />
                        Your new password is: '.$new_password.'</p> <br /> <br />
                        </body></html>';

            // Headers
            $header = 'MIME-Version: 1.0' . "\r\n" .
                      'Content-type: text/html; charset=iso-8859-1' . "\r\n" .
                      'From: webmaster@grinnellopencalender.com' . "\r\n" .
                      'Reply-To: webmaster@grinnellopencalender.com' . "\r\n" .
                      'X-Mailer: PHP/' . phpversion();

            // Send Message
            $sent = mail( $email, 'GOC Password Reset', $message, $header);
        } else { 
            $sent = FALSE;
        }

        disconnect($db, $sql_result);  
        return $sent;
    }

    function changePassword($email, $old_pw, $new_pw, $new2_pw){
        if ($new2_pw != $new_pw) return 0;
        
        // Connect to database, store variables to prevent sql injections, encrypt
        // password data.
        global $db;
        $db = (isset($db)) ? $db : connect();
        $old_pw = md5($old_pw);
        $new_pw = md5($new_pw);
        $email = mysql_real_escape_string(strtolower($email));

        chk_user_pw($email, $old_pw);

        if ($db) {
            // Store new password if old password and email are correct
            $query2 = "UPDATE users
                       SET password = '$new_pw'
                       WHERE email = '$email' AND password = '$old_pw';";
            
            if (mysql_num_rows($result)) {
                $result2 = mysql_query($query2, $db);
                
                if (!$result2) {
                    // exit and send error message if query2 was unsuccessful
                    $message = "Error in query ($query2): " . mysql_error();
                    disconnect($db, $result);
                    die($message);
                } elseif ($result2 && mysql_num_rows($result)){
                    // exit and return TRUE if password was successfully changed
                    disconnect($db, $result);
                    return true;
                }
            } else { 
                return 0;
            }
        }
    }
?>



