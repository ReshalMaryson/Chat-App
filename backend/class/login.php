<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods:  POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 204 No Content");
    exit;
}

session_start();
include "../config/db.php";
require 'generate_jwt.php';

$connection = new Database();
$db = $connection->connect();

class Login
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function login()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === "POST") {
                $data = json_decode(file_get_contents("php://input"), true);

                if (!isset($data['email']) || !isset($data['password'])) {
                    echo json_encode(["message" => "Email and password required"]);
                    exit;
                } else {
                    $email = $data['email'];
                    $password = $data['password'];
                }

                $query = "SELECT id,password,username FROM users WHERE email=:email";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":email", $email, PDO::PARAM_STR);
                if ($stmt->execute()) {
                    if ($stmt->rowCount() === 0) {
                        http_response_code(404);
                        echo json_encode([
                            'status' => 'error',
                            'message' => 'USER NOT FOUND.'
                        ]);
                        exit;
                    } else {
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($user && password_verify($password, $user['password'])) {
                            $jwt = generateJWT($user['id'], $email);
                            $_SESSION['user'] = $user['id'];
                            http_response_code(200);
                            echo json_encode(["status" => "success", "token" => $jwt, "user" => $user]);
                            exit;
                        } else {
                            http_response_code(401);
                            echo json_encode(["status" => "unauthorized", "message" => "Invalid credentials"]);
                            exit;
                        }
                    }
                } else {
                    http_response_code(400);
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

$obj_message = new Login($db);
$obj_message->login();
