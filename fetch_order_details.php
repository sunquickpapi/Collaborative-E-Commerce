<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "User not logged in"]);
    exit();
}

$user_id = htmlspecialchars($_SESSION['user_id']);

// Database connection
$conn = new mysqli("localhost", "root", "", "lunadb");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['custom_id']) && isset($_GET['design_id'])) {
    $custom_id = intval($_GET['custom_id']);
    $design_id = intval($_GET['design_id']);

    // Fetch custom order details
    $custom_sql = "SELECT * FROM custom WHERE custom_id = ?";
    $stmt = $conn->prepare($custom_sql);
    $stmt->bind_param("i", $custom_id);
    $stmt->execute();
    $custom_result = $stmt->get_result();

    // Fetch design details
    $design_sql = "SELECT * FROM designs WHERE id = ?";
    $stmt = $conn->prepare($design_sql);
    $stmt->bind_param("i", $design_id);
    $stmt->execute();
    $design_result = $stmt->get_result();

    // Fetch customer details
    $customer_sql = "SELECT c_username as customer_name, customer_phoneNum as customer_phone, customer_address as customer_address FROM customers WHERE customer_id = ?";
    $stmt = $conn->prepare($customer_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $customer_result = $stmt->get_result();

    if ($custom_result->num_rows > 0 && $design_result->num_rows > 0 && $customer_result->num_rows > 0) {
        $custom_data = $custom_result->fetch_assoc();
        $design_data = $design_result->fetch_assoc();
        $customer_data = $customer_result->fetch_assoc();

        // Merge custom, design, and customer data
        $data = array_merge($custom_data, $design_data, $customer_data);
        echo json_encode($data);
    } else {
        echo json_encode(["error" => "Custom ID or Design ID not found"]);
    }
    $stmt->close();
} else {
    echo json_encode(["error" => "Invalid parameters"]);
}

$conn->close();
?>
