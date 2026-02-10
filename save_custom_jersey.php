<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include("connection.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_id = $_SESSION['user_id'];
    $jersey_fabric = $_POST['jersey_fabric'];
    $jersey_colour = $_POST['jersey_colour'];
    $front_logo_position = $_POST['front_logo_position'];
    $back_logo_position = $_POST['back_logo_position'];
    $design_description = $_POST['design_description'];
    $quantity_s = intval($_POST['quantity_s']);
    $quantity_m = intval($_POST['quantity_m']);
    $quantity_l = intval($_POST['quantity_l']);
    $quantity_xl = intval($_POST['quantity_xl']);
    $quantity_2xl = intval($_POST['quantity_2xl']);
    $quantity_3xl = intval($_POST['quantity_3xl']);
    $quantity_4xl = intval($_POST['quantity_4xl']);

    $fabric_price = ($jersey_fabric == 'Lycra') ? 10 : 20;
    $prices = [
        'S' => 60,
        'M' => 60,
        'L' => 60,
        'XL' => 60,
        '2XL' => 65,
        '3XL' => 70,
        '4XL' => 75
    ];
    $total_price = $fabric_price + ($quantity_s * $prices['S']) + ($quantity_m * $prices['M']) +
                   ($quantity_l * $prices['L']) + ($quantity_xl * $prices['XL']) +
                   ($quantity_2xl * $prices['2XL']) + ($quantity_3xl * $prices['3XL']) +
                   ($quantity_4xl * $prices['4XL']);
    $total_quantity = $quantity_s + $quantity_m + $quantity_l + $quantity_xl + $quantity_2xl + $quantity_3xl + $quantity_4xl;

    // Minimum quantity check
    if ($total_quantity < 30) {
        echo json_encode(['error' => 'Minimum order quantity is 30.']);
        exit();
    }

    // Save logos
    $front_logo_path = 'uploads/' . basename($_FILES['front_logo']['name']);
    $back_logo_path = 'uploads/' . basename($_FILES['back_logo']['name']);
    move_uploaded_file($_FILES['front_logo']['tmp_name'], $front_logo_path);
    move_uploaded_file($_FILES['back_logo']['tmp_name'], $back_logo_path);

    // Insert into the database
    $stmt = $conn->prepare("INSERT INTO custom (customer_id, jersey_fabric, jersey_color, front_logo, front_logo_position, back_logo, back_logo_position, design_description, quantity_s, quantity_m, quantity_l, quantity_xl, quantity_2xl, quantity_3xl, quantity_4xl, total_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("issssssssiiiiiii", $customer_id, $jersey_fabric, $jersey_colour, $front_logo_path, $front_logo_position, $back_logo_path, $back_logo_position, $design_description, $quantity_s, $quantity_m, $quantity_l, $quantity_xl, $quantity_2xl, $quantity_3xl, $quantity_4xl, $total_price);
        if ($stmt->execute()) {
            $custom_id = $stmt->insert_id;
            echo json_encode(['custom_id' => $custom_id, 'total_price' => $total_price]);
        } else {
            echo json_encode(['error' => 'Database error: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['error' => 'Database error: ' . $conn->error]);
    }

    $conn->close();
} else {
    echo json_encode(['error' => 'Invalid request method.']);
}
?>
