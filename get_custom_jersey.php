<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include("connection.php");

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $custom_id = intval($_GET['custom_id']);
    $customer_id = $_SESSION['user_id'];

    // Fetch custom jersey data
    $stmt = $conn->prepare("SELECT * FROM custom WHERE custom_id = ? AND customer_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $custom_id, $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();
            echo json_encode($data);
        } else {
            echo json_encode(['error' => 'Custom jersey not found.']);
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
