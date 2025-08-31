<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: vendor_signin.html");
    exit();
}

$buyer_id = $_SESSION['user_id'];

$conn = new mysqli("localhost", "root", "", "user_management");
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die(json_encode(array("status" => "error", "message" => "Database connection failed")));
}

$response = array("responses" => array());

$query = "SELECT product_id, vendor_id, attribute_name, attribute_value, timestamp FROM vendor_attribute_responses WHERE buyer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $buyer_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $response["responses"][] = array(
        "product_id" => $row['product_id'],
        "vendor_id" => $row['vendor_id'],
        "attribute_name" => $row['attribute_name'],
        "attribute_value" => $row['attribute_value'],
        "timestamp" => $row['timestamp']
    );
}

header('Content-Type: application/json');
echo json_encode(array("status" => "success", "data" => $response));
$conn->close();
?>
