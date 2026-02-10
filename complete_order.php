<?php
include("connection.php");

if (isset($_GET['custom_id']) && isset($_GET['design_id'])) {
    $custom_id = htmlspecialchars($_GET['custom_id']);
    $design_id = htmlspecialchars($_GET['design_id']);

    $sql_custom = "SELECT * FROM custom WHERE custom_id = ?";
    $stmt_custom = $conn->prepare($sql_custom);
    $stmt_custom->bind_param("i", $custom_id);
    $stmt_custom->execute();
    $result_custom = $stmt_custom->get_result();
    $custom = $result_custom->fetch_assoc();

    $sql_design = "SELECT * FROM designs WHERE id = ?";
    $stmt_design = $conn->prepare($sql_design);
    $stmt_design->bind_param("i", $design_id);
    $stmt_design->execute();
    $result_design = $stmt_design->get_result();
    $design = $result_design->fetch_assoc();

    $sql_cart = "SELECT * FROM cart WHERE custom_id = ?";
    $stmt_cart = $conn->prepare($sql_cart);
    $stmt_cart->bind_param("i", $custom_id);
    $stmt_cart->execute();
    $result_cart = $stmt_cart->get_result();
    $cart_items = [];
    while ($row = $result_cart->fetch_assoc()) {
        $cart_items[] = $row;
    }

    $response = [
        'customer_name' => $custom['customer_name'],
        'customer_phone' => $custom['customer_phone'],
        'customer_address' => $custom['customer_address'],
        'cart_items' => $cart_items,
        'cart_subtotal' => array_sum(array_column($cart_items, 'subtotal')),
        'total' => array_sum(array_column($cart_items, 'subtotal'))
    ];

    echo json_encode($response);
} else {
    echo json_encode(["error" => "Invalid input"]);
}

$conn->close();
?>
