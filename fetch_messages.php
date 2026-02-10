<?php
session_start();
include("connection.php");

$receiver_id = htmlspecialchars($_GET['receiver_id']);
$receiver_role = htmlspecialchars($_GET['receiver_role']);
$sender_id = $_SESSION['user_id'];
$sender_role = $_SESSION['user_role'];

// Log the parameters
file_put_contents('debug.log', "Receiver ID: $receiver_id, Receiver Role: $receiver_role, Sender ID: $sender_id, Sender Role: $sender_role\n", FILE_APPEND);

// Fetch messages where the current user is either the sender or the receiver
$sql = "SELECT m.message, m.sender_id, m.sender_role, c.c_username AS sender_name, a.agent_username AS agent_name 
        FROM messages m
        LEFT JOIN customers c ON m.sender_id = c.customer_id AND m.sender_role = 'customer'
        LEFT JOIN agent a ON m.sender_id = a.agent_id AND m.sender_role = 'agent'
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.receiver_id = ? AND m.sender_id = ?)
        ORDER BY m.timestamp";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $sender_id, $receiver_id, $sender_id, $receiver_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'message' => $row['message'],
        'sender_name' => $row['sender_role'] == 'customer' ? $row['sender_name'] : $row['agent_name'],
        'sender_role' => $row['sender_role']
    ];
}

$stmt->close();
$conn->close();

// Log the messages
file_put_contents('debug.log', "Messages: " . json_encode($messages) . "\n", FILE_APPEND);

echo json_encode($messages);
?>
