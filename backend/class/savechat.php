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

class SaveChat
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function savechat()
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
                    $senderID = (int) $data["s_id"];
                    $receiverID = (int) $data["r_id"];
                }


                $query = "SELECT chat_id FROM userchat WHERE
                 initial_user=:s_id AND second_user= :r_id ";

                $stmt = $this->conn->prepare($query);
                if ($stmt) {
                    $stmt->bindParam(":s_id", $senderID, PDO::PARAM_INT);
                    $stmt->bindParam(":r_id", $receiverID, PDO::PARAM_INT);
                    $stmt->execute();

                    //if no chat exist.
                    if ($stmt->rowCount() === 0) {
                        http_response_code(200);
                        $stmt2 = $this->conn->prepare('INSERT INTO userchat (initial_user,second_user) VALUES( :s_id ,:r_id)');
                        $stmt2->bindParam(":s_id", $senderID, PDO::PARAM_INT);
                        $stmt2->bindParam(":r_id", $receiverID, PDO::PARAM_INT);
                        $stmt2->execute();
                        if ($stmt2->rowCount() === 0) {
                            echo json_encode(['status' => 'error', 'message' => 'failed to insert chat']);
                            exit;
                        } else {
                            $data = $this->conn->LastInsertId();
                            http_response_code(200);
                            echo json_encode(["status" => "success", "data" => $data]);
                            exit;
                        }
                    } else {
                        http_response_code(400);
                        echo json_encode(['status' => 'chatexists', 'message' => 'CHAT EXISTS ALREADY.']);
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

$obj_message = new SaveChat($db);
$obj_message->savechat();
