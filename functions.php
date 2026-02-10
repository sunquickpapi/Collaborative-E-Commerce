<?php
function check_login($con) {
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
        $id = $_SESSION['user_id'];
        $role = $_SESSION['user_role'];
        
        // Determine the correct table and ID field based on user role
        $query = "";
        $id_field = "";
        switch ($role) {
            case 'admin':
                $query = "SELECT * FROM admin WHERE admin_id = ? LIMIT 1";
                $id_field = 'admin_id';
                break;
            case 'customer':
                $query = "SELECT * FROM customers WHERE customer_id = ? LIMIT 1";
                $id_field = 'customer_id';
                break;
            case 'agent':
                $query = "SELECT * FROM agent WHERE agent_id = ? LIMIT 1";
                $id_field = 'agent_id';
                break;
            case 'designer':
                $query = "SELECT * FROM designer WHERE designer_id = ? LIMIT 1";
                $id_field = 'designer_id';
                break;
            default:
                // Redirect to login if role is invalid
                header("Location: login.php");
                die;
        }

        // Use prepared statement to prevent SQL injection
        if ($stmt = $con->prepare($query)) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $user_data = $result->fetch_assoc();
                $stmt->close();
                return $user_data;
            } else {
                $stmt->close();
            }
        }
    }

    // Redirect to login if no valid session or user not found
    header("Location: login.php");
    die;
}
?>
