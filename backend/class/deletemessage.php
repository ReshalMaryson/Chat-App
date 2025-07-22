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

class DeleteMessage
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function deletemessage()
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
                if (count($data) === 0) {
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'REQUEST BODY IS EMPTY'
                    ]);
                    exit;
                } else {
                    $msg_id = (int) $data["m_id"];
                    $sdr_id = (int)$data['sdr_id'];
                }

                $query = "UPDATE private_messages SET is_deleted = 1, deleted_by=:sdr_id WHERE id=:m_id AND sender_id=:sdr2_id";
                $stmt = $this->conn->prepare($query);
                if ($stmt) {
                    $stmt->bindParam(":sdr_id", $sdr_id, PDO::PARAM_INT);
                    $stmt->bindParam(":m_id", $msg_id, PDO::PARAM_INT);
                    $stmt->bindParam(":sdr2_id", $sdr_id, PDO::PARAM_INT);
                    if ($stmt->execute()) {
                        http_response_code(200);
                        echo json_encode(["status" => "success"]);
                        exit;
                    } else {
                        http_response_code(400);
                        echo json_encode(["status" => "unsuccessful"]);
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

$obj_message = new DeleteMessage($db);
$obj_message->deletemessage();
