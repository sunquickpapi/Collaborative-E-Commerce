<?php
session_start();
include("connection.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = intval($_POST['receiver_id']);
    $message = htmlspecialchars($_POST['message']);
    $sender_role = 'agent'; // Or 'customer' depending on the user's role in your application
    $receiver_role = 'customer'; // Or 'agent' depending on the context

    $sql = "INSERT INTO messages (sender_id, receiver_id, receiver_role, message, timestamp, sender_role) VALUES (?, ?, ?, ?, NOW(), ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iisss', $sender_id, $receiver_id, $receiver_role, $message, $sender_role);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
