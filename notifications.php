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


$buyer_id_query = "SELECT DISTINCT buyer_id FROM buyer_requirements";
$buyer_id_result = $conn->query($buyer_id_query);

if ($buyer_id_result === false) {
    error_log("Error fetching buyer IDs: " . $conn->error);
    die(json_encode(array("status" => "error", "message" => "Error fetching buyer IDs")));
}

$buyer_ids = array();

while ($row = $buyer_id_result->fetch_assoc()) {
    $buyer_ids[] = $row['buyer_id'];
}


$response = array("notifications" => array(), "previous_entries" => array());

foreach ($buyer_ids as $buyer_id) {
    
    $product_query = "SELECT DISTINCT product_id, product_name 
                      FROM buyer_requirements 
                      WHERE buyer_id = ?";
    $stmt = $conn->prepare($product_query);
    if (!$stmt) {
        error_log("Prepare failed for product query: " . $conn->error);
        continue;
    }

    $stmt->bind_param("s", $buyer_id);
    $stmt->execute();
    $product_result = $stmt->get_result();

    if (!$product_result) {
        error_log("Error fetching products for buyer_id $buyer_id: " . $conn->error);
        continue;
    }

 
    $products = array();
    while ($row = $product_result->fetch_assoc()) {
        $products[] = array(
            "product_id" => $row['product_id'],
            "product_name" => $row['product_name']
        );
    }

    if (!empty($products)) {
    
        $attributes_query = "SELECT DISTINCT product_id, attribute_name 
                             FROM buyer_requirements 
                             WHERE buyer_id = ? AND attribute_name IS NOT NULL";
        $stmt = $conn->prepare($attributes_query);
        $stmt->bind_param("s", $buyer_id);
        $stmt->execute();
        $attributes_result = $stmt->get_result();

        $attributes = array();
        while ($row = $attributes_result->fetch_assoc()) {
            $attributes[$row['product_id']][] = $row['attribute_name'];
        }

        
        $technical_query = "SELECT product_id, attribute_name, attribute_value 
                            FROM technical_attributes 
                            WHERE buyer_id = ?";
        $stmt = $conn->prepare($technical_query);
        $stmt->bind_param("s", $buyer_id);
        $stmt->execute();
        $technical_result = $stmt->get_result();

        $technical_attributes = array();
        while ($row = $technical_result->fetch_assoc()) {
            $technical_attributes[$row['product_id']][] = array(
                "attribute_name" => $row['attribute_name'],
                "attribute_value" => $row['attribute_value']
            );
        }

       
        $non_technical_query = "SELECT product_id, attribute_name, attribute_value 
                                FROM non_technical_attributes 
                                WHERE buyer_id = ?";
        $stmt = $conn->prepare($non_technical_query);
        $stmt->bind_param("s", $buyer_id);
        $stmt->execute();
        $non_technical_result = $stmt->get_result();

        $non_technical_attributes = array();
        while ($row = $non_technical_result->fetch_assoc()) {
            $non_technical_attributes[$row['product_id']][] = array(
                "attribute_name" => $row['attribute_name'],
                "attribute_value" => $row['attribute_value']
            );
        }

        
        $response["notifications"][] = array(
            "buyer_id" => $buyer_id,
            "products" => $products,
            "attributes" => $attributes,
            "technical_attributes" => $technical_attributes,
            "non_technical_attributes" => $non_technical_attributes
        );
    }

   
    $previous_product_query = "SELECT DISTINCT product_id, product_name 
                               FROM buyer_requirements 
                               WHERE buyer_id = ?";
    $stmt = $conn->prepare($previous_product_query);
    $stmt->bind_param("s", $buyer_id);
    $stmt->execute();
    $previous_product_result = $stmt->get_result();

    $previous_products = array();
    while ($row = $previous_product_result->fetch_assoc()) {
        $previous_products[] = array(
            "product_id" => $row['product_id'],
            "product_name" => $row['product_name']
        );
    }

    $response["previous_entries"][] = array(
        "buyer_id" => $buyer_id,
        "products" => $previous_products
    );
}


header('Content-Type: application/json');
echo json_encode(array(
    "status" => "success",
    "data" => $response
));

$conn->close();
?>
