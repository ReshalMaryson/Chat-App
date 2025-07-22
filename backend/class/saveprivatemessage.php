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
                if (count($data) === 0) {
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'REQUEST BODY IS EMPTY'
                    ]);
                    exit;
                } else {
                    $senderID = $data["s_id"];
                    $receiverID = $data["r_id"];
                    $message = $data["message"];
                }


                $query = "INSERT INTO private_messages (sender_id,receiver_id,message) VALUES (:s_id,:r_id,:message)";
                $stmt = $this->conn->prepare($query);
                if ($stmt) {
                    $stmt->bindParam(":s_id", $senderID, PDO::PARAM_STR);
                    $stmt->bindParam(":r_id", $receiverID, PDO::PARAM_STR);
                    $stmt->bindParam(":message", $message, PDO::PARAM_STR);

                    if ($stmt->execute()) {
                        $last_id = $this->conn->lastInsertId();

                        if ($last_id) {
                            $stmt2 = $this->conn->prepare('SELECT id,message,created_at FROM private_messages WHERE id=:id');
                            $stmt2->bindParam(":id", $last_id, PDO::PARAM_INT);
                            $stmt2->execute();
                            if ($stmt2->rowCount() === 0) {
                                echo json_encode(['status' => 'error', 'message' => 'failed to get data of last insert id']);
                                exit;
                            } else {
                                $data =  $stmt2->fetch(PDO::FETCH_ASSOC);
                                http_response_code(200);
                                echo json_encode(["status" => "success", "data" => $data]);
                                exit;
                            }
                        } else {
                            echo json_encode(["status" => "error", "message" => "LAST ID NOT FOUND."]);
                            exit;
                        }
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
