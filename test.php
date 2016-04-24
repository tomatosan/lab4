Skip to content
This repository
Search
Pull requests
Issues
Gist
<?php
    //phpinfo();
    
    error_reporting(E_ALL);
    ini_set('display_startup_errors',1);
    ini_set('display_errors',1);
    error_reporting(-1);
    
    echo "Testing database PDO connection...<br>";
    
    $SECRET = "diu7ajksf8sj,vKLDHliewudksfj"; //  place this in WebApp settings
    
    
    $connenv = getenv("SQLAZURECONNSTR_defaultConnection");
    parse_str(str_replace(";", "&", $connenv), $connarray);
    
    $connstring = "sqlsrv:Server=".$connarray["Data_Source"].";Database=".$connarray["Initial_Catalog"];
    $user = $connarray["User_Id"];
    $pass = $connarray["Password"];
    
    //var_dump($connarray);
    //var_dump($connstring);
    //var_dump($user);
    //var_dump($pass);
    
    function printCollations($conn)
    {
        $sql = "SELECT name, description FROM sys.fn_helpcollations()";
        foreach ($conn->query($sql) as $row)
        {
            print $row['name'] . "\t";
            print $row['description'] . "<br>";
        }
    }
    try
    {
        $conn = new PDO( $connstring, $user, $pass );
        
        $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        
        //printCollations($conn);
        
        
        $sqlcreate ="CREATE TABLE users( ID INT NOT NULL IDENTITY(1,1) PRIMARY KEY,".
                 "login         VARCHAR( 250 ) NOT NULL,".
                 "password      VARCHAR( 128 ) NOT NULL,".
                 "admin         BIT);";
        
        try { $conn->exec($sqlcreate); } catch ( PDOException $e ) { echo "Create table error. May be it exists."; }
        
        print("The table was created.<br>");
        
        $sqlinsert = "insert into users (login,password,admin) values (?, ?, ?)";
        $insertquery = $conn->prepare($sqlinsert);
      
        // test set of users
        $myusers = array(
            array("admin", "adminpassword", 1),
            array("user1", "user1password", 0),
            array("user2", "user1password", 0) );
        
        foreach($myusers as $user)
        {
            $username = $user[0];
            $userpasshash = hash( "whirlpool", $SECRET.$user[1].$SECRET, false );
            $isAdmin=$user[2];
            $insertquery->execute(array($username, $userpasshash, $isAdmin));
            
            echo "Insert error code = ".$insertquery->errorCode()." "; // Five zeros are good like this 00000 but HY001 is a common error
            echo "Number of rows inserted = ".$insertquery->rowCount()."<br>";
        }
        
        print "<br>Selecting rows from the table...<br>";
        
        $sqlselect = "SELECT login,password,admin FROM users";
        foreach ($conn->query($sqlselect) as $row)
        {
            print   htmlspecialchars($row['login'])." ".
                    htmlspecialchars($row['password'])." ".
                    "admin=".htmlspecialchars($row['admin'])."<br>";
        }
        
        print "Dropping the table...<br>";
        
        $sqldrop ="DROP TABLE users";
        
        $conn->exec($sqldrop);
        
        print "The table was dropped <br>";
    }
    catch ( PDOException $e )
    {
        // TODO: There is a security problem here. Do not do this in production!!!
        print( "PDO Error : " );
        die(print_r($e));
    }
?>

