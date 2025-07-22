<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods:  GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 204 No Content");
    exit;
}

include "../config/db.php";

$connection = new Database();
$db = $connection->connect();

class SaveMessage
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function savemessage()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === "GET") {
                http_response_code(401);
                if (!isset($_SESSION['user'])) {
                    echo json_encode(["status" => "error", "message" => "USER NOT LOGGED IN."]);
                    exit;
                }

                $query = "SELECT * FROM messages ORDER BY created_at";
                $stmt = $this->conn->prepare($query);
                if ($stmt->execute()) {
                    if ($stmt->rowCount() === 0) {
                        http_response_code(404);
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'NO MESSAGE FOUND OR EMPTY RECORD'
                        ]);
                        exit;
                    } else {
                        $messages =  $stmt->fetchAll(PDO::FETCH_ASSOC);
                        http_response_code(200);
                        echo json_encode(["status" => "success", "data" => $messages]);
                        exit;
                    }
                } else {
                    http_response_code(203);
                    echo json_encode(['status' => 'error', 'message' => 'unsuccessful']);
                    exit;
                }
            } else {
                http_response_code(405);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Method Not Allowed. GET Permitted only'
                ]);
                exit;
            }
        } catch (Exception $e) {

            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
            exit;
        } finally {
            $this->conn = null;
        }
    }
}

$obj_message = new SaveMessage($db);
$obj_message->savemessage();
