<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods:  POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include "../config/db.php";
$connection = new Database();
$db = $connection->connect();

class Register
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function register()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === "POST") {

                $data = json_decode(file_get_contents("php://input"), true);

                if (!isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
                    echo json_encode(["status" => "error", "message" => "Incomplete Fields."]);
                    exit;
                } else {
                    $username = $data['username'];
                    $email = $data['email'];
                    $password = password_hash($data['password'], PASSWORD_DEFAULT);
                }


                $query = "SELECT id FROM users WHERE email=:email_find";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":email_find", $email, PDO::PARAM_STR);

                if ($stmt) {
                    if ($stmt->execute()) {
                        if ($stmt->rowCount() > 0) {
                            http_response_code(400);
                            echo json_encode(['status' => "emailexist", "message" => "EMAIL ALREADY EXISTS"]);
                            exit;
                        } else {
                            $sql = "INSERT INTO users (username,email,password) 
                                    VALUES (:username,:email,:password )";
                            $stmt2 = $this->conn->prepare($sql);
                            $stmt2->bindParam(":username", $username, PDO::PARAM_STR);
                            $stmt2->bindParam(":email", $email, PDO::PARAM_STR);
                            $stmt2->bindParam(":password", $password, PDO::PARAM_STR);

                            if ($stmt2->execute()) {
                                http_response_code(201);
                                echo json_encode(['status' => "success"]);
                                exit;
                            } else {
                                http_response_code(409);
                                echo json_encode(['status' => "unsuccessful", "message" => "FAILED TO REGISTER."]);
                                exit;
                            }
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

$obj_message = new Register($db);
$obj_message->register();

//newreshal123