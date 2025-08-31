<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: buyer_signin.html");
    exit();
}

$conn = new mysqli("localhost", "root", "", "user_management");

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die(json_encode(array("status" => "error", "message" => "Database connection failed")));
}

$buyer_id = $_SESSION['user_id'];
if (!$buyer_id) {
    die(json_encode(array("status" => "error", "message" => "Buyer ID not found in session")));
}

$query = "SELECT DISTINCT product_id, product_name FROM buyer_requirements WHERE buyer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $buyer_id);
$stmt->execute();
$result = $stmt->get_result();

$products = array();
while ($row = $result->fetch_assoc()) {
    $products[] = array(
        "product_id" => $row['product_id'],
        "product_name" => $row['product_name']
    );
}

header('Content-Type: application/json');
echo json_encode(array(
    "status" => "success",
    "data" => array("products" => $products)
));

$conn->close();
?>
