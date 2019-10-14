<?php
class MegaDatabaseConnection
{
    private static $DBObject;
    private static $DBConnection;

    public function __construct()
    {
        if(!isset(self::$DBObject))
        {
            self::$DBObject = $this;
        }
        return self::$DBObject;
    }
    public function connection()
    {
        if(self::$DBConnection instanceof MySQLi)
        {
            return self::$DBConnection;
        }
        else
        {
            self::$DBConnection = new mysqli('p:localhost', 'root', '', 'MEGA_TEST_DB'); // Persistant with a P! ;)
            if (self::$DBConnection->connect_error) 
            {
                die("Connection failed: " . self::$DBConnection->connect_error);
            }
            else
            {
                return self::$DBConnection; 
            }
        }
    }           
}
?>
