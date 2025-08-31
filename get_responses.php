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

$product_id = isset($_GET['product_id']) ? $_GET['product_id'] : null;

if (!$product_id) {
    error_log("Invalid product ID: " . $product_id); 
    die(json_encode(array("status" => "error", "message" => "Invalid product ID")));
}

$query = "SELECT vendor_id, attribute_name, attribute_value FROM vendor_attribute_responses WHERE product_id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die(json_encode(array("status" => "error", "message" => "Database query preparation failed")));
}

$stmt->bind_param("s", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    error_log("Error fetching responses: " . $conn->error);
    die(json_encode(array("status" => "error", "message" => "Error fetching responses")));
}

$responses = array();
while ($row = $result->fetch_assoc()) {
    $responses[] = array(
        "vendor_id" => $row['vendor_id'],
        "attribute_name" => $row['attribute_name'],
        "attribute_value" => $row['attribute_value']
    );
}

header('Content-Type: application/json');
echo json_encode(array(
    "status" => "success",
    "data" => array("responses" => $responses)
));

$conn->close();
?>
