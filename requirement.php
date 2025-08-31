<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: buyer_signin.html");
    exit();
}

$buyer_id = $_SESSION['user_id'];

$conn = new mysqli("localhost", "root", "", "user_management");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT id FROM buyers WHERE id = '$buyer_id'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "Error: Invalid Buyer ID.";
    exit();
}

$product_name = isset($_POST['product_name']) ? $_POST['product_name'] : '';
$requirements = isset($_POST['requirement_name']) ? $_POST['requirement_name'] : [];
$values = isset($_POST['requirement_value']) ? $_POST['requirement_value'] : [];
$weights = isset($_POST['requirement_weight']) ? $_POST['requirement_weight'] : [];

$technical_names = isset($_POST['technical_name']) ? $_POST['technical_name'] : [];
$technical_values = isset($_POST['technical_value']) ? $_POST['technical_value'] : [];

$non_technical_names = isset($_POST['non_technical_name']) ? $_POST['non_technical_name'] : [];
$non_technical_values = isset($_POST['non_technical_value']) ? $_POST['non_technical_value'] : [];
$non_technical_weights = isset($_POST['non_technical_weight']) ? $_POST['non_technical_weight'] : [];

$product_id = uniqid('prod_');

$sql = "INSERT INTO products (product_id, product_name) VALUES ('$product_id', '$product_name')";
if (!$conn->query($sql)) {
    echo "Error: " . $conn->error;
    exit();
}

foreach ($requirements as $key => $requirement) {
    $value = $values[$key];
    $weight = $weights[$key];

    $sql = "INSERT INTO buyer_requirements (buyer_id, product_id, product_name, attribute_name, attribute_value, requirement_weight)
            VALUES ('$buyer_id', '$product_id', '$product_name', '$requirement', '$value', '$weight')";

    if (!$conn->query($sql)) {
        echo "Error: " . $conn->error;
        exit();
    }
}

foreach ($technical_names as $key => $technical_name) {
    $technical_value = $technical_values[$key];

    $sql = "INSERT INTO technical_attributes (buyer_id, product_id, product_name, attribute_name, attribute_value)
            VALUES ('$buyer_id', '$product_id', '$product_name', '$technical_name', '$technical_value')";

    if (!$conn->query($sql)) {
        echo "Error: " . $conn->error;
        exit();
    }
}

foreach ($non_technical_names as $key => $non_technical_name) {
    $non_technical_value = $non_technical_values[$key];
    $non_technical_weight = $non_technical_weights[$key];

    $sql = "INSERT INTO non_technical_attributes (buyer_id, product_id, product_name, attribute_name, attribute_value, attribute_weight)
            VALUES ('$buyer_id', '$product_id', '$product_name', '$non_technical_name', '$non_technical_value', '$non_technical_weight')";

    if (!$conn->query($sql)) {
        echo "Error: " . $conn->error;
        exit();
    }
}

echo "<div class='alert alert-success' role='alert'>
        Your requirements have been successfully submitted!
      </div>";

header("Location: requirement.html"); 
?>
