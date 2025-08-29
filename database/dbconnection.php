<?php
//db Connection
class DbConnection
{
    public function getConnection()
    {
        try {
            $host = "mindconnectserver.mysql.database.azure.com";
            $db = "mindconnect";
            $user = "mindconnect";
            $pass = "h}3:Fhub39A)";

            $conn = new mysqli($host, $user, $pass, $db);
            if (!$conn) {
                die("Connection failed: " . mysqli_connect_error());
            }
            return $conn;
        } catch (Exception $err) {
            echo json_encode($err->getMessage());
            return null;
        }
    }
}
