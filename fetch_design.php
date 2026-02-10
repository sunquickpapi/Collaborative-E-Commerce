<?php
// fetch_design.php
include("connection.php");

header('Content-Type: application/json');

if (isset($_GET['design_id'])) {
    $design_id = htmlspecialchars($_GET['design_id']);
    
    $sql = "SELECT * FROM designs WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $design_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $design = $result->fetch_assoc();
            echo json_encode($design);
        } else {
            echo json_encode(["error" => "Design not found"]);
        }
        $stmt->close();
    } else {
        echo json_encode(["error" => "Error preparing statement: " . $conn->error]);
    }
} else {
    echo json_encode(["error" => "No design ID provided"]);
}

$conn->close();
?>
