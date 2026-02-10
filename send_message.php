<?php
session_start();
include("connection.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        die('Session variables are not set.');
    }

    $sender_id = $_SESSION['user_id'];
    $sender_role = 'customer'; // Or 'customer' depending on the user's role in your application
    $receiver_id = htmlspecialchars($_POST['receiver_id']);
    $receiver_role = 'agent'; // Or 'agent' depending on the context
    $message = htmlspecialchars($_POST['message']);

    // Debugging
    error_log("Sender ID: " . $sender_id);
    error_log("Sender Role: " . $sender_role);
    error_log("Receiver ID: " . $receiver_id);
    error_log("Receiver Role: " . $receiver_role);
    error_log("Message: " . $message);

    // Check if all values are set correctly
    if ($sender_id && $sender_role && $receiver_id && $receiver_role && $message) {
        $sql = "INSERT INTO messages (sender_id, sender_role, receiver_id, receiver_role, message, timestamp) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die('Prepare failed: ' . htmlspecialchars($conn->error));
        }
        $bind = $stmt->bind_param("issss", $sender_id, $sender_role, $receiver_id, $receiver_role, $message);
        if ($bind === false) {
            die('Bind failed: ' . htmlspecialchars($stmt->error));
        }
        $exec = $stmt->execute();
        if ($exec) {
            echo "Message sent successfully";
        } else {
            echo "Execute failed: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    } else {
        echo "Error: Missing required fields.";
    }

    $conn->close();
}
