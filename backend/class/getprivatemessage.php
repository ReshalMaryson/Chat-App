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


                $headers = getallheaders();
                $token = $headers["Authorization"] ?? "";
                $user = verifyJWT(str_replace("Bearer ", "", $token));

                if (!$user) {
                    echo json_encode(["error" => "Unauthorized"]);
                    exit;
                }

                $decode = json_decode(file_get_contents("php://input"), true);

                if (!isset($decode)) {
                    http_response_code(400);
                    echo json_encode(["status" => "error", "message" => "REQ BODY IS EMPTY"]);
                    exit;
                } else {
                    $senderID = $decode['sender_id'];
                    $receiver_ID = $decode['receiver_id'];
                }

                // $query = "SELECT * FROM private_messages WHERE 
                // sender_id=:s_id AND receiver_id=:r_id
                //   OR 
                //  (sender_id = :r_id AND receiver_id = :s_id) 
                //  ORDER BY created_at";
                $query = "SELECT 
                            id, 
                            sender_id, 
                            receiver_id,
                            CASE 
                                WHEN is_deleted = 1 AND deleted_by = :user_id THEN 'You deleted this message'
                                WHEN is_deleted = 1 THEN 'This message was deleted'
                                ELSE message
                            END AS message,
                            created_at
                                FROM private_messages
                                 WHERE 
                         (sender_id = :user_id AND receiver_id = :chat_partner_id)
                                        OR 
                     (sender_id = :chat_partner_id AND receiver_id = :user_id)
                                        ORDER BY created_at ASC";

                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":user_id", $senderID, PDO::PARAM_INT);
                $stmt->bindParam(":chat_partner_id", $receiver_ID, PDO::PARAM_INT);
                if ($stmt->execute()) {
                    if ($stmt->rowCount() === 0) {
                        echo json_encode([
                            'status' => 'notfound',
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
                    echo json_encode(['status' => 'unsuccess', 'message' => 'ERROR GETIN G MESSAGES']);
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
