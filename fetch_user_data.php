<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include("connection.php");

$user_id = htmlspecialchars($_SESSION['user_id']);
$user_role = htmlspecialchars($_SESSION['user_role']);

// Based on the role, fetch the relevant user data
$table = '';
$id_column = '';
$username_column = '';
$phone_column = '';
$address_column = '';

switch ($user_role) {
    case 'admin':
        $table = 'admin';
        $id_column = 'admin_id';
        $username_column = 'admin_username';
        $phone_column = 'admin_phoneNum';
        $address_column = 'admin_address';
        break;
    case 'customer':
        $table = 'customers';
        $id_column = 'customer_id';
        $username_column = 'c_username';
        $phone_column = 'customer_phoneNum';
        $address_column = 'customer_address';
        break;
    case 'agent':
        $table = 'agent';
        $id_column = 'agent_id';
        $username_column = 'agent_username';
        $phone_column = 'agent_phoneNum';
        $address_column = 'agent_address';
        break;
    case 'designer':
        $table = 'designer';
        $id_column = 'designer_id';
        $username_column = 'designer_username';
        $phone_column = 'designer_phoneNum';
        $address_column = 'designer_address';
        break;
    default:
        header("Location: login.php");
        exit();
}

$sql = "SELECT $username_column, $phone_column, $address_column FROM $table WHERE $id_column=?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_data = [
            'id' => $user_id,
            'username' => htmlspecialchars($row[$username_column]),
            'phone' => htmlspecialchars($row[$phone_column]),
            'address' => htmlspecialchars($row[$address_column]),
            'role' => $user_role
        ];
        echo json_encode($user_data);
    } else {
        echo json_encode(['error' => 'No user data found']);
    }
    $stmt->close();
} else {
    echo json_encode(['error' => 'Error preparing statement']);
}

$conn->close();
?>
