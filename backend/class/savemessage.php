<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods:  POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 204 No Content");
    exit;
}

include "auth_middleware.php";
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
            if ($_SERVER['REQUEST_METHOD'] === "POST") {

                $headers = getallheaders();
                $token = $headers["Authorization"] ?? "";
                $user = verifyJWT(str_replace("Bearer ", "", $token));

                if (!$user) {
                    echo json_encode(["error" => "Unauthorized"]);
                    exit;
                }


                $data = json_decode(file_get_contents("php://input"), true);
                if (empty($data)) {
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'REQUEST BODY IS EMPTY'
                    ]);
                    exit;
                } else {
                    $username = $data["username"];
                    $message = $data["message"];
                }


                $query = "INSERT INTO messages (username, message) VALUES (:username ,  :message)";
                $stmt = $this->conn->prepare($query);
                if ($stmt) {
                    $stmt->bindParam(":username", $username, PDO::PARAM_STR);
                    $stmt->bindParam(":message", $message, PDO::PARAM_STR);

                    if ($stmt->execute()) {
                        http_response_code(200);
                        echo json_encode(["status" => "success"]);
                        exit;
                    } else {
                        http_response_code(203);
                        echo json_encode(['status' => 'error', 'message' => 'unsuccessful']);
                        exit;
                    }
                } else {
                    http_response_code(500);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'INTERNAL ERROR OCCUERED.'
                    ]);
                    exit;
                }
            } else {
                http_response_code(405);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Method Not Allowed. POST Permitted only'
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
