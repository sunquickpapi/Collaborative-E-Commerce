<?php
session_start();
include("connection.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sender_id = $_SESSION['user_id'];
    $sender_role = $_SESSION['user_role'];
    $receiver_id = htmlspecialchars($_POST['receiver_id']);
    $receiver_role = htmlspecialchars($_POST['receiver_role']);
    $message = htmlspecialchars($_POST['message']);

    if ($sender_id && $sender_role && $receiver_id && $receiver_role && $message) {
        $sql = "INSERT INTO agent_designer_messages (sender_id, sender_role, receiver_id, receiver_role, message, timestamp, read_status) 
                VALUES (?, ?, ?, ?, ?, NOW(), 0)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $sender_id, $sender_role, $receiver_id, $receiver_role, $message);

        if ($stmt->execute()) {
            echo "Message sent successfully";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error: Missing required fields.";
    }

    $conn->close();
}
