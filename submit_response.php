<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: vendor_signin.html");
    exit();
}


$conn = new mysqli("localhost", "root", "", "user_management");

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die(json_encode(array("status" => "error", "message" => "Database connection failed")));
}


$vendor_id = $_SESSION['user_id'];


$input = file_get_contents('php://input');
$data = json_decode($input, true);

$product_id = isset($data['productId']) ? $data['productId'] : null;
$buyer_id = isset($data['buyer_id']) ? $data['buyer_id'] : null;
$attribute_values = isset($data['attributeValues']) ? $data['attributeValues'] : array();
$final_bid_amount = isset($data['final_bid_amount']) ? $data['final_bid_amount'] : null;

if ($product_id === null || $buyer_id === null || $final_bid_amount === null) {
    die(json_encode(array("status" => "error", "message" => "Invalid product ID, buyer ID, or bid amount")));
}


$query = "INSERT INTO vendor_attribute_responses (vendor_id, buyer_id, product_id, attribute_name, attribute_value, timestamp) VALUES (?, ?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($query);

if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    die(json_encode(array("status" => "error", "message" => "Database query preparation failed")));
}


foreach ($attribute_values as $attribute_name => $attribute_value) {
    $stmt->bind_param("sssss", $vendor_id, $buyer_id, $product_id, $attribute_name, $attribute_value);
    $result = $stmt->execute();

    if (!$result) {
        error_log("Error inserting attribute value: " . $stmt->error);
        die(json_encode(array("status" => "error", "message" => "Error saving attribute values")));
    }
}


$attribute_name = "final_bid_amount";
$stmt->bind_param("sssss", $vendor_id, $buyer_id, $product_id, $attribute_name, $final_bid_amount);
$result = $stmt->execute();

if (!$result) {
    error_log("Error inserting final bid amount: " . $stmt->error);
    die(json_encode(array("status" => "error", "message" => "Error saving final bid amount")));
}


echo json_encode(array("status" => "success", "message" => "Values saved successfully"));


$stmt->close();
$conn->close();
?>
