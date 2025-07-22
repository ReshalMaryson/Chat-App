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

    public function getcuserchat()
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
                    $userid = isset($decode['user_id']) ? (int) $decode['user_id'] : null;
                    $receiverId = isset($decode['receiver_id']) ? (int) $decode['receiver_id'] : null;
                }


                if (count($decode) > 1) {
                    // $query = "SELECT chat_id FROM userchat WHERE 
                    // initial_user=:u_id AND second_user=:r_id ";

                    $query = "SELECT chat_id FROM userchat WHERE 
                            (initial_user = :u_id AND second_user = :r_id)
                            OR (initial_user = :r_id AND second_user = :u_id)";

                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(":u_id", $userid, PDO::PARAM_INT);
                    $stmt->bindParam(":r_id", $receiverId, PDO::PARAM_INT);

                    $stmt->execute();
                    if ($stmt->rowCount() === 0) {
                        echo json_encode([
                            'status' => 'notfound',
                            'message' => 'NO CHATS AVAIALABLE FOR USER OR EMPTY RECORD'
                        ]);
                        exit;
                    } else {
                        $chats =  $stmt->fetch(PDO::FETCH_ASSOC);
                        http_response_code(200);
                        echo json_encode(["status" => "success", "data" => $chats]);
                        exit;
                    }
                } else {
                    $query = "SELECT 
                        uc.chat_id,
                        CASE 
                            WHEN uc.initial_user = :u_id THEN uc.second_user
                            ELSE uc.initial_user
                        END AS chat_partner_id,
                        u.username AS chat_partner_name,
                        pm.message AS last_message,
                        pm.read_status AS is_read,
                        pm.created_at AS last_message_time,
                        pm.is_deleted AS deleted,
                        pm.deleted_by AS deleted_by,
                        pm.sender_id AS senderID,
                         pm.receiver_id AS reciverID
                    FROM (
                        SELECT 
                            chat_id,
                            initial_user,
                            second_user,
                            LEAST(initial_user, second_user) AS user1,
                            GREATEST(initial_user, second_user) AS user2
                        FROM userchat
                        WHERE :u_id IN (initial_user, second_user)
                    ) AS uc
                    JOIN users u 
                        ON u.id = CASE 
                            WHEN uc.initial_user = :u_id THEN uc.second_user
                            ELSE uc.initial_user
                        END
                    LEFT JOIN private_messages pm 
                         ON pm.id = (
                        SELECT p1.id
                        FROM private_messages p1
                        WHERE 
                            (p1.sender_id = uc.initial_user AND p1.receiver_id = uc.second_user)
                            OR 
                            (p1.sender_id = uc.second_user AND p1.receiver_id = uc.initial_user)
                        ORDER BY p1.created_at DESC 
                        LIMIT 1
                    )
                    GROUP BY uc.user1, uc.user2
                    ORDER BY last_message_time DESC";

                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(":u_id", $userid, PDO::PARAM_INT);
                    $stmt->bindParam(":u_id2", $userid, PDO::PARAM_INT);
                    $stmt->bindParam(":u_id3", $userid, PDO::PARAM_INT);
                    $stmt->execute();

                    if ($stmt->rowCount() === 0) {
                        echo json_encode([
                            'status' => 'notfound',
                            'message' => 'NO CHATS AVAILABLE FOR USER OR EMPTY RECORD'
                        ]);
                        exit;
                    } else {
                        $chats =  $stmt->fetchAll(PDO::FETCH_ASSOC);
                        http_response_code(200);
                        echo json_encode([
                            "status" => "success",
                            "data" => $chats
                        ]);
                        exit;
                    }
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
                "status" => "Database_error",
                "message" => $e->getMessage()
            ]);
            exit;
        } finally {
            $this->conn = null;
        }
    }
}

$obj_message = new GetUserChats($db);
$obj_message->getcuserchat();
