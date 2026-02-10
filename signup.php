<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("connection.php");

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $passwordConfirm = $_POST['passwordConfirm'];
    $role = $_POST['role'];

    // Validate form data
    if (!empty($username) && !empty($email) && !empty($password) && $password === $passwordConfirm && !is_numeric($username)) {
        // Check if username or email already exists
        $user_exists = false;
        $query = "";
        if ($role === 'customer') {
            $query = "SELECT * FROM customers WHERE c_username = ? OR customer_email = ? LIMIT 1";
        } elseif ($role === 'admin') {
            $query = "SELECT * FROM admin WHERE admin_username = ? OR admin_email = ? LIMIT 1";
        } elseif ($role === 'designer') {
            $query = "SELECT * FROM designer WHERE designer_username = ? OR designer_email = ? LIMIT 1";
        } else {
            $query = "SELECT * FROM agent WHERE agent_username = ? OR agent_email = ? LIMIT 1";
        }

        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            die('prepare() failed: ' . htmlspecialchars($conn->error));
        }

        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user_exists = true;
        }

        $stmt->close();

        if ($user_exists) {
            echo "<script>alert('Username or email already exists!');</script>";
        } else {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert into the appropriate table based on the role
            if ($role === 'admin') {
                $query = "INSERT INTO admin (admin_username, admin_email, admin_phoneNum, admin_password) VALUES (?, ?, '', ?)";
            } elseif ($role === 'agent') {
                $query = "INSERT INTO agent (agent_username, agent_email, agent_phoneNum, agent_password) VALUES (?, ?, '', ?)";
            } elseif ($role === 'customer') {
                $query = "INSERT INTO customers (c_username, customer_email, customer_phoneNum, c_password) VALUES (?, ?, '', ?)";
            } elseif ($role === 'designer') {
                $query = "INSERT INTO designer (designer_username, designer_email, designer_phoneNum, designer_password) VALUES (?, ?, '', ?)";
            }

            $stmt = $conn->prepare($query);
            if ($stmt === false) {
                die('prepare() failed: ' . htmlspecialchars($conn->error));
            }

            if ($role === 'admin' || $role === 'agent' || $role === 'customer'|| $role === 'designer') {
                $stmt->bind_param("sss", $username, $email, $hashedPassword);
            }

            if ($stmt->execute()) {
                echo "<script>alert('Registration successful!'); window.location.href = 'login.php';</script>";
                exit();
            } else {
                echo "<script>alert('Registration failed. Please try again.');</script>";
            }

            $stmt->close();
        }
    } else {
        echo "<script>alert('Please enter valid information and make sure passwords match.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        @import url('https://fonts.googleapis.com/css?family=Tangerine');
        * { 
            margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif;
         }
        body {
             display: flex; justify-content: center; align-items: center; min-height: 100vh; background: url('home1.jpg') no-repeat; background-size: 40%; background-position: center; 
            }
        header {
             position: fixed; top: 0; left: 0; width: 100%; padding: 20px 100px; display: flex; justify-content: space-between; align-items: center; z-index: 99; 
            }
        .logo {
             font-size: 2em; color: #060606; user-select: none; 
            }
        .wrapper { 
            position: relative; width: 400px; height: 650px; background: transparent; border: 2px solid rgba(255, 255, 255, .5); border-radius: 20px; backdrop-filter: blur(40px); box-shadow: 0 0 30px rgba(0, 0, 0, .5); display: flex; justify-content: center; align-items: center; overflow: hidden;
         }
        .wrapper .form-box {
             width: 100%; padding: 40px; 
            }
        .form-box h2 {
             font-size: 2em; color: #060606; text-align: center;
             }
        .input-box {
             position: relative; width: 100%; height: 50px; border-bottom: 2px solid #989da0; margin: 30px 0;
             }
        .input-box label {
             position: absolute; top: 50%; left: 5px; transform: translateY(-50%); font-size: 1em; color: #989da0; font-weight: 510; pointer-events: none; transition: .5s; 
            }
        .input-box input:focus~label, .input-box input:valid~label { 
            top: -5px;
         }
        .input-box input 
        { 
            width: 100%; height: 100%; background: transparent; border: none; outline: none; font-size: 1em; color: #060606; font-weight: 500; padding: 0 35px 0 5px; 
        }
        .input-box .icon 
        { 
            position: absolute; right: 8px; font-size: 1.2em; color: #989da0; line-height: 57px; 
        }
        .remember-forgot 
        { 
            font-size: .9em; color: #060606; font-weight: 540; margin: -15px 0 15px; display: flex; justify-content: center; 
        }
        .remember-forgot label input 
        {
            accent-color: #060606; margin-right: 4px; text-shadow: 2px black; color: white; 
        }
        .remember-forgot a 
        { color: #989da0; text-decoration: none; 
        }
        .remember-forgot a:hover {
             text-decoration: underline; 
            }
        .btn {
             width: 100%; height: 45px; background: #989da0; border: none; outline: none; border-radius: 6px; cursor: pointer; font-size: 1em; color: #fff; font-weight: 500; 
            }
        .login-register {
             font-size: .9em; color: #989da0; text-align: center; font-weight: 500; margin: 25px 0 10px; 
            }
        .login-register p a {
             color: #989da0; text-decoration: none; font-weight: 600; 
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
        <div class="form-box register">
            <h2>LVNASTUDIO DASHBOARD - signup</h2>
            <form action="" method="POST">
                <div class="input-box">
                    <span class="icon"><ion-icon name="person"></ion-icon></span>
                    <input type="text" name="username" required>
                    <label>Username</label>
                </div>
                <div class="input-box">
                    <span class="icon"><ion-icon name="mail"></ion-icon></span>
                    <input type="email" name="email" required>
                    <label>Email</label>
                </div>
                <div class="input-box">
                    <span class="icon"><ion-icon name="lock"></ion-icon></span>
                    <input type="password" name="password" required>
                    <label>Password</label>
                </div>
                <div class="input-box">
                    <span class="icon"><ion-icon name="lock"></ion-icon></span>
                    <input type="password" name="passwordConfirm" required>
                    <label>Confirm Password</label>
                </div>
                <div class="input-box">
                   <span class="icon"><ion-icon name="role"></ion-icon></span>
                    <input type="text" name="role" required>
                    <label>Role</label> 
                </div>
                <div class="remember-forgot">
                    <label><input type="checkbox" required> I agree to the terms & conditions</label>
                </div>
                <button type="submit" class="btn" name="submit">Sign Up</button>
                <div class="login-register">
                    <p>Already have an account?<a href="login.php" class="login-link"> Sign In</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://unpkg.com/ionicons@4.5.10-0/dist/ionicons.js"></script>
</body>
</html>
