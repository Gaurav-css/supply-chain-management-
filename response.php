<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: vendor_signin.html");
    exit();
}


ini_set('display_errors', 0);
error_reporting(E_ALL);


ini_set('log_errors', 1);
ini_set('error_log', 'php-error.log');


$conn = new mysqli("localhost", "root", "", "user_management");

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    echo json_encode(array("status" => "error", "message" => "Database connection failed"));
    exit();
}


$responses_query = "SELECT vendor_id, buyer_id, product_id, attribute_name, attribute_value 
                    FROM vendor_attribute_responses";
$responses_result = $conn->query($responses_query);

if ($responses_result === false) {
    error_log("Error fetching responses: " . $conn->error);
    echo json_encode(array("status" => "error", "message" => "Error fetching responses"));
    exit();
}

$responses = array();

while ($row = $responses_result->fetch_assoc()) {
    $product_id = $row['product_id'];
    $vendor_id = $row['vendor_id'];
    $buyer_id = $row['buyer_id'];

    if (!isset($responses[$product_id])) {
        $responses[$product_id] = array(
            "product_id" => $product_id,
            "vendors" => array()
        );
    }

    if (!isset($responses[$product_id]['vendors'][$vendor_id])) {
        $responses[$product_id]['vendors'][$vendor_id] = array(
            "vendor_id" => $vendor_id,
            "buyer_id" => $buyer_id,
            "attributes" => array(),
            "final_bid_amount" => "",
            "response_status" => ""
        );
    }

    if ($row['attribute_name'] == 'final_bid_amount') {
        $responses[$product_id]['vendors'][$vendor_id]['final_bid_amount'] = $row['attribute_value'];
    } else {
        $responses[$product_id]['vendors'][$vendor_id]['attributes'][$row['attribute_name']] = $row['attribute_value'];
    }
}


header('Content-Type: application/json');
echo json_encode(array(
    "status" => "success",
    "data" => $responses
));

$conn->close();
?>
