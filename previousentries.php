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


$response = array("previous_entries" => array());
$query = "SELECT vendor_id, buyer_id, product_id, attribute_name, attribute_value, timestamp
          FROM vendor_attribute_responses
          WHERE vendor_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $vendor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result === false) {
    error_log("Error fetching previous entries: " . $conn->error);
    die(json_encode(array("status" => "error", "message" => "Error fetching previous entries")));
}

while ($row = $result->fetch_assoc()) {
    $response["previous_entries"][] = array(
        "vendor_id" => $row['vendor_id'],
        "buyer_id" => $row['buyer_id'],
        "product_id" => $row['product_id'],
        "attribute_name" => $row['attribute_name'],
        "attribute_value" => $row['attribute_value'],
        "timestamp" => $row['timestamp']
    );
}


header('Content-Type: application/json');
echo json_encode(array(
    "status" => "success",
    "data" => $response
));

$conn->close();
?>
