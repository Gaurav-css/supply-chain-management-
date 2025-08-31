<?php

$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "user_management";


$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $errors = [];

    
    if (empty($email)) {
        $errors[] = "Email is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    if (!empty($errors)) {
        $_SESSION['login_errors'] = $errors;
        header("Location: vendor_signin.html"); 
        exit();
    }

  
    $stmt = $conn->prepare("SELECT * FROM vendors WHERE email = ?");
    if ($stmt === false) {
        die('MySQL prepare error: ' . $conn->error); 
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        
        if (password_verify($password, $user['password'])) {

           
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_type'] = 'vendor';

            
            header("Location: vendor_page.html");
            exit();
        } else {
            $_SESSION['login_errors'] = ["Invalid email or password."];
            header("Location: vendor_signin.html");
            exit();
        }
    } else {
        $_SESSION['login_errors'] = ["Invalid email or password."];
        header("Location: vendor_signin.html");
        exit();
    }

    if ($stmt) {
        $stmt->close();
    }
}

$conn->close();
?>
