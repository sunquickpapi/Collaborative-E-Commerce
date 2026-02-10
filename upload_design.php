<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['design_image'])) {
    // Database connection
    include("connection.php");

    $design_title = htmlspecialchars($_POST['design_title']);
    $agent_username = htmlspecialchars($_POST['agent_username']);
    $design_description = htmlspecialchars($_POST['design_description']);
    
    // Handle image upload
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["design_image"]["name"]);
    move_uploaded_file($_FILES["design_image"]["tmp_name"], $target_file);

    $sql = "INSERT INTO designs (agent_username, design_image, design_title, design_description) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $agent_username, $target_file, $design_title, $design_description);
    
    if ($stmt->execute()) {
        echo "Design uploaded successfully";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: designer_draft.php");
    exit();
}
?>
