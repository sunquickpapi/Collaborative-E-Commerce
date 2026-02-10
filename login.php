<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("connection.php");

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $email = filter_var($_POST['email_field'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password_field'];
    $errors = array();

    if (empty($email) || empty($password)) {
        array_push($errors, "All fields are required");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        array_push($errors, "Email is not valid");
    }

    if (count($errors) === 0) {
        $user = null;

        // Array of tables and their respective email column names
        $tables = [
            ['table' => 'customers', 'email_column' => 'customer_email', 'password_column' => 'c_password', 'id_column' => 'customer_id', 'username_column' => 'c_username', 'role' => 'customers'],
            ['table' => 'agent', 'email_column' => 'agent_email', 'password_column' => 'agent_password', 'id_column' => 'agent_id', 'username_column' => 'agent_username', 'role' => 'agent'],
            ['table' => 'admin', 'email_column' => 'admin_email', 'password_column' => 'admin_password', 'id_column' => 'admin_id', 'username_column' => 'admin_username', 'role' => 'admin'],
            ['table' => 'designer', 'email_column' => 'designer_email', 'password_column' => 'designer_password', 'id_column' => 'designer_id', 'username_column' => 'designer_username', 'role' => 'designer']
        ];

        foreach ($tables as $table) {
            $sql = "SELECT * FROM " . $table['table'] . " WHERE " . $table['email_column'] . " = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                die('prepare() failed: ' . htmlspecialchars($conn->error));
            }

            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user) {
                $password_column = $table['password_column'];
                $id_column = $table['id_column'];
                $username_column = $table['username_column'];
                $role = $table['role'];
                break;
            }
        }

        if ($user) {
            if (password_verify($password, $user[$password_column])) {
                // Set session variables
                $_SESSION['user_id'] = $user[$id_column];
                $_SESSION['user_username'] = $user[$username_column];
                $_SESSION['user_role'] = $role;

                
                // // Assign user_id based on role
                // switch ($role) {
                //     case 'admin':
                //         $_SESSION['user_id'] = $user['admin_id'];
                //         break;
                //     case 'agent':
                //         $_SESSION['user_id'] = $user['agent_id'];
                //         break;
                //     case 'designer':
                //         $_SESSION['user_id'] = $user['designer_id'];
                //         break;
                //     case 'customers':
                //         $_SESSION['user_id'] = $user['customer_id'];
                //         break;
                //     default:
                //         $_SESSION['user_id'] = $user[$id_column];
                // }

                // Debugging
                echo "<div class='alert alert-success'>Login successful. Role: " . $role . "</div>";
                // Ensure no output before header
                ob_start();

                // Redirect to the dashboard or another page based on role
                switch ($role) {
                    case 'admin':
                        header("Location: admin.php");
                        break;
                    case 'agent':
                        header("Location: agent.php");
                        break;
                    case 'designer':
                        header("Location: designer.php");
                        break;
                    case 'customers': // Match the table name for customers
                        header("Location: index.php");
                        break;
                    default:
                        header("Location: index.php");
                }
                exit();
                ob_end_flush();
            } else {
                echo "<div class='alert alert-danger'>Password is incorrect.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>No user found with this email.</div>";
        }
    } else {
        foreach ($errors as $error) {
            echo "<div class='alert alert-danger'>$error</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../Loginpage/styleLogin.css">
    <title>Login</title>
    <style>
        @import url('https://fonts.googleapis.com/css?family=Tangerine');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: url('home1.jpg') no-repeat;
            background-size: 40%;
            background-position: center;
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 20px 100px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 99;
        }

        .logo {
            font-size: 2em;
            color: #060606;
            user-select: none;
        }

        .wrapper {
            position: relative;
            width: 400px;
            height: 440px;
            background: transparent;
            border: 2px solid rgba(255, 255, 255, .5);
            border-radius: 20px;
            backdrop-filter: blur(40px);
            box-shadow: 0 0 30px rgba(0, 0, 0, .5);
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .wrapper .form-box {
            width: 100%;
            padding: 40px;
        }

        .form-box h2 {
            font-size: 2em;
            color: #060606;
            text-align: center;
        }

        .input-box {
            position: relative;
            width: 100%;
            height: 50px;
            border-bottom: 2px solid #989da0;
            margin: 30px 0; 
        }

        .input-box label {
            position: absolute;
            top: 50%;
            left: 5px;
            transform: translateY(-50%);
            font-size: 1em;
            color: #989da0;
            font-weight: 510;
            pointer-events: none;
            transition: .5s;
        }

        .input-box input:focus~label,
        .input-box input:valid~label {
            top: -5px;
        }

        .input-box input {
            width: 100%;
            height: 100%;
            background: transparent;
            border: none;
            outline: none;
            font-size: 1em;
            color: #060606;
            font-weight: 500;
            padding: 0 35px 0 5px;
        }

        .input-box .icon {
            position: absolute;
            right: 8px;
            font-size: 1.2em;
            color: #989da0;
            line-height: 57px;
        }

        .remember-forgot {
            font-size: .9em;
            color: #060606;
            font-weight: 540;
            margin: -15px 0 15px;
            display: flex;
            justify-content: center;
        }

        .remember-forgot label input {
            accent-color: #060606;
            margin-right: 4px;
            text-shadow: 2px black; 
            color: white; 
        }

        .remember-forgot a {
            color: #989da0;
            text-decoration: none;
        }

        .remember-forgot a:hover {
            text-decoration: underline;
        }

        .btn {
            width: 100%;
            height: 45px;
            background: #989da0;
            border: none;
            outline: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            color: #fff;
            font-weight: 500;
        }

        .login-register {
            font-size: .9em;
            color: #989da0;
            text-align: center;
            font-weight: 500;
            margin: 25px 0 10px;
        }

        .login-register p a {
            color: #989da0;
            text-decoration: none;
            font-weight: 600;
        }

        .login-register p a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header>
        <h2 class="logo">LVNASTUDIO</h2>
    </header>
    <div class="wrapper">
        <div class="form-box login">
            <h2>Login</h2>
            <form action="" method="POST" autocomplete="off">
                <div class="input-box">
                    <span class="icon"><ion-icon name="mail"></ion-icon></span>
                    <input type="email" name="email_field" required autocomplete="new-email" value="">
                    <label>Email</label>
                </div>
                <div class="input-box">
                    <span class="icon"><ion-icon name="lock"></ion-icon></span>
                    <input type="password" name="password_field" required autocomplete="new-password" value="">
                    <label>Password</label>
                </div>
                <button type="submit" class="btn" name="submit">Login</button>
                <div class="login-register">
                    <p>Don't have an account?<a href="signup.php" class="register-link"> Sign Up</a></p>
                </div>
            </form>
        </div>
    </div>
    <script src="https://unpkg.com/ionicons@4.5.10-0/dist/ionicons.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var inputs = document.querySelectorAll('.input-box input');
            inputs.forEach(function(input) {
                input.value = ''; // Clear the input value on page load
            });
        });
    </script>
</body>
</html>
