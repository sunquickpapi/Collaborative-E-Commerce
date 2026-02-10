<?php
session_start();
include("connection.php");

$user_id = $_SESSION['user_id'];
$username = $_POST['username'];
$phone = $_POST['phone'];
$address = $_POST['address'];

$sql = "UPDATE customers SET c_username=?, customer_phoneNum=?, customer_address=? WHERE customer_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $username, $phone, $address, $user_id);

if ($stmt->execute()) {
    $_SESSION['user_username'] = $username;
    echo "Profile updated successfully";
} else {
    echo "Error updating profile: " . $conn->error;
}
?>
