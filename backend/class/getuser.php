<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods:  GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 204 No Content");
    exit;
}

include "../config/db.php";
require 'auth_middleware.php';

$connection = new Database();
$db = $connection->connect();

class GetUser
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getUser()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === "GET") {

                $headers = apache_request_headers();
                $token = isset($headers['Authorization']) ? str_replace("Bearer ", "", $headers['Authorization']) : null;

                $decoded = verifyJWT($token);

                if (!$decoded) {
                    echo json_encode(["status" => "unauthorized", "message" => "Unauthorized"]);
                    exit;
                }


                //get data from req body
                $decode = json_decode(file_get_contents("php://input"), true);
                if (!isset($decode)) {
                    http_response_code(400);
                    echo json_encode(["status" => "error", "message" => "REQ BODY IS EMPTY"]);
                    exit;
                } else {
                    $id = $decode['userID'];
                }

                $query = "SELECT id,email,username FROM users WHERE id=:id ";

                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":id", $id, PDO::PARAM_INT);

                if ($stmt) {
                    if ($stmt->execute()) {
                        if ($stmt->rowCount() === 0) {
                            http_response_code(404);
                            echo json_encode(['status' => "unsuccessfull", "message" => "USER NOT FOUND"]);
                            exit;
                        } else {
                            $result = $stmt->fetch(PDO::FETCH_ASSOC);
                            http_response_code(200);
                            echo json_encode(['status' => "success", "data" => $result]);
                            exit;
                        }
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(['status' => "error", "message" => "STMT ERROR."]);
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

$obj_message = new GetUser($db);
$obj_message->getUser();
