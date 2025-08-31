<?php
require 'config.php'; 
session_start();
$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $user_type = 'vendor'; 

    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (empty($email)) {
        $errors[] = "Email is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        
        $stmt = $conn->prepare("SELECT * FROM vendors WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors[] = "Email is already taken.";
        } else {
            
            $unique_code = generateUniqueCode();

           
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            
            $stmt = $conn->prepare("INSERT INTO vendors (name, email, password, user_type, unique_code) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $hashed_password, $user_type, $unique_code);
            if ($stmt->execute()) {
                $_SESSION['signup_success'] = "Registration successful!";
                $_SESSION['unique_code'] = $unique_code;  
                header("Location: vendor_signin.html"); 
                exit();
            } else {
                $errors[] = "Error occurred during registration.";
            }
        }
        $stmt->close();
    }
}


$_SESSION['signup_errors'] = $errors;
header("Location: vendor_signup.html"); 
exit();

$conn->close();


function generateUniqueCode() {
    return strtoupper(bin2hex(random_bytes(6)));  
}
?>
