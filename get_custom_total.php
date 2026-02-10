<?php
include("connection.php");

if (isset($_GET['custom_id'])) {
    $custom_id = intval($_GET['custom_id']);
    $sql = "SELECT total_price FROM custom WHERE custom_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $custom_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(['success' => true, 'total_price' => $row['total_price']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Custom ID not found']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'No Custom ID provided']);
}

$conn->close();
?>
