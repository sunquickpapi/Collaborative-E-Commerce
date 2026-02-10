<?php
include("connection.php");

$custom_id = htmlspecialchars($_GET['custom_id']);

$sql = "SELECT * FROM custom WHERE custom_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $custom_id);
$stmt->execute();
$result = $stmt->get_result();
$custom_info = $result->fetch_assoc();
$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($custom_info);
?>
