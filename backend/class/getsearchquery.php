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

class GetPrivateMessage
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getprivatemessage()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === "POST") {

                $headers = apache_request_headers();
                if (!isset($headers['Authorization'])) {
                    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
                    http_response_code(401);
                    exit;
                }

                $token = isset($headers['Authorization']) ? str_replace("Bearer ", "", $headers['Authorization']) : null;

                $decoded = verifyJWT($token); // access the middleware function

                if (!$decoded) {
                    echo json_encode(["status" => "unauthorized", "message" => "Unauthorized"]);
                    exit;
                }

                $decode = json_decode(file_get_contents("php://input"), true);

                if (!isset($decode)) {
                    http_response_code(400);
                    echo json_encode(["status" => "error", "message" => "REQ BODY IS EMPTY"]);
                    exit;
                } else {
                    $user_name = $decode['username'];
                }

                $query = "SELECT id,username,email FROM users   WHERE username LIKE :username  LIMIT 7";

                $username_param = "%" . $user_name . "%";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":username", $username_param, PDO::PARAM_STR);
                if ($stmt->execute()) {
                    if ($stmt->rowCount() === 0) {
                        // http_response_code(404);
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'NO USER FOUND OR EMPTY RECORD'
                        ]);
                        exit;
                    } else {
                        $user =  $stmt->fetchAll(PDO::FETCH_ASSOC);
                        http_response_code(200);
                        echo json_encode(["status" => "success", "users" => $user]);
                        exit;
                    }
                } else {
                    http_response_code(404);
                    echo json_encode(['status' => 'unsuccess', 'message' => 'unsuccessful']);
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

$obj_message = new GetPrivateMessage($db);
$obj_message->getprivatemessage();
