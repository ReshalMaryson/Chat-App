<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods:  POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 204 No Content");
    exit;
}

include "../config/db.php";
include "auth_middleware.php";


$connection = new Database();
$db = $connection->connect();

class GetUserChats
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }


    public function update_read_status()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === "POST") {
                // check auth
                $headers = getallheaders();
                $token = $headers["Authorization"] ?? "";
                $user = verifyJWT(str_replace("Bearer ", "", $token));

                if (!$user) {
                    echo json_encode(["error" => "Unauthorized"]);
                    exit;
                }

                // get json data
                $decode = json_decode(file_get_contents("php://input"), true);

                if (!isset($decode)) {
                    http_response_code(400);
                    echo json_encode(["status" => "error", "message" => "REQ BODY IS EMPTY"]);
                    exit;
                } else {
                    $senderid = isset($decode['sender_id']) ? (int) $decode['sender_id'] : null;
                    $receiverid = isset($decode['receiver_id']) ? (int) $decode['receiver_id'] : null;
                }


                // DB 
                $query = "UPDATE private_messages 
                         SET read_status = 1 
                         WHERE receiver_id = :receiver_id 
                         AND sender_id = :sender_id 
                         AND read_status = 0";


                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':receiver_id', $receiverid);
                $stmt->bindParam(':sender_id', $senderid);

                if ($stmt->execute()) {
                    echo json_encode(["success" => true]);
                    exit;
                } else {
                    http_response_code(500);
                    echo json_encode(["success" => false, "message" => "DB error"]);
                    exit;
                }
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                "status" => "Database_error",
                "message" => $e->getMessage()
            ]);
            exit;
        } finally {
            $this->conn = null;
        }
    }
}


$obj = new GetUserChats($db);
$obj->update_read_status();
