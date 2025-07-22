<?php

class Database
{
    public $conn;

    public function connect()
    {

        try {
            $this->conn = new PDO('mysql:host=localhost;dbname=chat_app;', "root", "");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if ($this->conn) {
                return $this->conn;
            }
        } catch (Exception $e) {
            echo json_encode("DB Connection Error: " . $e->getMessage());
            return null;
        }
    }
}
