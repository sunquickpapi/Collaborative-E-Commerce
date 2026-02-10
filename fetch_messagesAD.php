<?php
session_start();
include("connection.php");

$receiver_id = htmlspecialchars($_GET['receiver_id']);
$receiver_role = htmlspecialchars($_GET['receiver_role']);
$sender_id = htmlspecialchars($_SESSION['user_id']);
$sender_role = $_SESSION['user_role'];

// Mark messages as read
$sql = "UPDATE agent_designer_messages SET read_status = 1 
        WHERE receiver_id = ? AND sender_id = ? AND receiver_role = ? AND sender_role = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiss", $sender_id, $receiver_id, $sender_role, $receiver_role);
$stmt->execute();
$stmt->close();

// Fetch messages
$sql = "SELECT m.message_id, m.sender_id, m.receiver_id, m.sender_role, 
        CASE 
            WHEN m.sender_role = 'agent' THEN a.agent_username 
            WHEN m.sender_role = 'designer' THEN d.designer_username 
        END AS sender_name, 
        m.message, m.timestamp 
        FROM agent_designer_messages m 
        LEFT JOIN agent a ON m.sender_id = a.agent_id AND m.sender_role = 'agent' 
        LEFT JOIN designer d ON m.sender_id = d.designer_id AND m.sender_role = 'designer'
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
        OR (m.receiver_id = ? AND m.sender_id = ?)
        ORDER BY m.timestamp DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $sender_id, $receiver_id, $sender_id, $receiver_id);
$stmt->execute();
$result = $stmt->get_result();
$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($messages);
?>
