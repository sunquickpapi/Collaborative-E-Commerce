<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'agent') {
    header("Location: login.php");
    exit();
}

$agent_id = htmlspecialchars($_SESSION['user_id']);

// Database connection
include("connection.php");

// Fetch unread customer messages
$sql_customer_unread = "SELECT COUNT(*) as unread_count FROM messages WHERE receiver_id = ? AND read_status = 0";
$stmt_customer = $conn->prepare($sql_customer_unread);
$stmt_customer->bind_param("i", $agent_id);
$stmt_customer->execute();
$result_customer = $stmt_customer->get_result();
$customer_unread_count = $result_customer->fetch_assoc()['unread_count'];
$stmt_customer->close();

// Fetch unread designer messages
$sql_designer_unread = "SELECT COUNT(*) as unread_count FROM agent_designer_messages WHERE receiver_id = ? AND receiver_role = 'agent' AND read_status = 0";
$stmt_designer = $conn->prepare($sql_designer_unread);
$stmt_designer->bind_param("i", $agent_id);
$stmt_designer->execute();
$result_designer = $stmt_designer->get_result();
$designer_unread_count = $result_designer->fetch_assoc()['unread_count'];
$stmt_designer->close();

// Fetch completed customs from session
$completed_customs = $_SESSION['completed_customs'] ?? [];

// Total unread messages
$total_unread_count = $customer_unread_count + $designer_unread_count;

// Read to-do items from JSON file
$todoFile = 'agent_todos.json';
if (!file_exists($todoFile)) {
    file_put_contents($todoFile, json_encode([]));
}
$todos = json_decode(file_get_contents($todoFile), true);

// Handle adding new to-do item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_todo'])) {
    $newTodo = htmlspecialchars($_POST['new_todo']);
    $todos[] = ['task' => $newTodo, 'status' => 'not-completed'];
    file_put_contents($todoFile, json_encode($todos));
    header("Location: agent.php");
    exit();
}

// Handle toggling the status of a to-do item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_todo'])) {
    $index = intval($_POST['toggle_todo']);
    $todos[$index]['status'] = $todos[$index]['status'] === 'not-completed' ? 'completed' : 'not-completed';
    file_put_contents($todoFile, json_encode($todos));
    header("Location: agent.php");
    exit();
}

// Fetch agent details
$sql_agent = "SELECT agent_username, agent_phoneNum, role FROM agent WHERE agent_id=?";
$stmt_agent = $conn->prepare($sql_agent);
$stmt_agent->bind_param("i", $agent_id);
$stmt_agent->execute();
$result_agent = $stmt_agent->get_result();
$agent = $result_agent->fetch_assoc();

$agent_username = htmlspecialchars($agent['agent_username']);
$agent_phoneNum = htmlspecialchars($agent['agent_phoneNum']);
$agent_role = htmlspecialchars($agent['role']);

// Update agent data if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $agent_username = htmlspecialchars($_POST['agent_username']);
    $agent_phoneNum = htmlspecialchars($_POST['agent_phoneNum']);
    
    $sql = "UPDATE agent SET agent_username=?, agent_phoneNum=? WHERE agent_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $agent_username, $agent_phoneNum, $agent_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Profile updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating profile');</script>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Boxicons -->
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <!-- FontAwesome -->
    <script src="https://kit.fontawesome.com/1b07de51b4.js"></script>
    <!-- My CSS -->
    <link rel="stylesheet" href="style.css">

    <title>Agent Dashboard</title>
    <style>
        /* Status Colors */
        .status.completed {
            background-color: green;
        }

        .status.not-completed {
            background-color: orange;
        }

        /* To-Do Styles */
        .todo-list {
            list-style: none;
            padding: 0;
        }

        .todo-item {
            padding: 10px;
            margin-bottom: 5px;
            background-color: #f2f2f2;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .todo-item.completed p {
            color: green;
            text-decoration: line-through;
        }

        .todo-item.not-completed p {
            color: orange;
        }

        .todo-item i {
            cursor: pointer;
        }

        /* New Todo Form */
        #add-todo-form {
            display: flex;
            gap: 10px;
        }

        #add-todo-form input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        #add-todo-form button {
            padding: 10px;
            border: none;
            background-color: #4caf50;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }

        #add-todo-form button:hover {
            background-color: #45a049;
        }

        /* Profile Popup */
        #overlay, #logout-overlay {
            position: fixed;
            display: none;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2;
        }

        .profile-popup, .logout-popup {
            display: none;
            position: fixed;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            padding: 20px;
            z-index: 3;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 300px;
            text-align: left;
        }
        .logout-popup h2{
            font-size: 20px;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 20px;
            cursor: pointer
            ;
        }
        #profile-popup input[type="submit"] {
            padding: 10px 20px;
            border: none;
            background-color: #4caf50;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .logout-btn {
            background-color: red;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }

        .cancel-btn {
            background-color: gray;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .profile-popup form input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <i class='bx bxs-circle'></i>
            <span class="text">LVNAAGENT</span>
        </a>
        <ul class="side-menu top">
            <li class="active">
                <a href="agent.php">
                    <i class='bx bxs-shopping-bag'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="agent_message.php">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Customer Message</span>
                </a>
            </li>
            <li>
                <a href="agent_designer.php">
                    <i class='bx bxs-group'></i>
                    <span class="text">Designer Message</span>
                </a>
            </li>
        </ul>
        <ul class="side-menu">
            <li>
                <a href="#" class="logout">
                    <i class='bx bxs-log-out-circle'></i>
                    <span class="text">Logout</span>
                </a>
            </li>
        </ul>
    </section>
    <!-- SIDEBAR -->

    <!-- CONTENT -->
    <section id="content">
        <!-- NAVBAR -->
        <nav>
            <i class='bx bx-menu'></i>
            <a href="#" class="nav-link">AGENT</a>
            <form action="#">
                <div class="form-input">
                    <button type="submit" class="search-btn" hidden><i class='bx bx-briefcase-alt'></i></button>
                </div>
            </form>
            <input type="checkbox" id="switch-mode" hidden>
            <label for="switch-mode" class="switch-mode"></label>
            <div class="profile">
                <i class="fa-regular fa-address-card" id="profile-icon"></i>
            </div>
        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <div class="head-title">
                <div class="left">
                    <h1>Dashboard</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="#">Agents</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">Dashboard</a>
                        </li>
                    </ul>
                </div>
            </div>

            <ul class="box-info">
                <li>
                    <i class='bx bxs-message'></i>
                    <span class="text">
                        <h3><?php echo $total_unread_count; ?></h3>
                        <p>New Message</p>
                    </span>
                </li>
                <li>
                    <i class='bx bxs-group'></i>
                    <span class="text">
                        <h3><?php echo isset($_SESSION['completed_customs_count']) ? $_SESSION['completed_customs_count'] : 0; ?></h3>
                        <p>Complete Custom</p>
                    </span>
                </li>
            </ul>

            <div class="table-data">
                <div class="order">
                    <div class="head">
                        <h3>Completed Custom</h3>
                        <i class='bx bx-search'></i>
                        <i class='bx bx-filter'></i>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Customer Name</th>
                                <th>Total Price</th>
                                <th>Design Image</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($completed_customs as $custom) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($custom['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($custom['total_price']); ?></td>
                                    <td><img src="<?php echo htmlspecialchars($custom['design_image']); ?>" alt="Design Image" style="max-width: 100px;"></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="todo">
                    <div class="head">
                        <h3>Todos</h3>
                        <i class='bx bx-plus' onclick="showAddTodoForm()"></i>
                        <i class='bx bx-filter' onclick="showAllTodos()"></i>
                    </div>
                    <ul class="todo-list">
                        <?php foreach ($todos as $index => $todo): ?>
                            <li class="todo-item <?php echo $todo['status']; ?>">
                                <p><?php echo htmlspecialchars($todo['task']); ?></p>
                                <i class="fa-regular fa-<?php echo $todo['status'] === 'completed' ? 'square-check' : 'square'; ?>" onclick="toggleTodoStatus(<?php echo $index; ?>)"></i>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <form method="POST" id="add-todo-form" style="display:none;">
                        <input type="text" name="new_todo" placeholder="New Todo">
                        <button type="submit">Add</button>
                    </form>
                </div>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <!-- Profile Popup -->
    <div id="overlay"></div>
    <div id="profile-popup" class="profile-popup">
        <span class="close-btn">&times;</span>
        <form method="POST">
            <p><strong>ID:</strong> <?php echo $agent_id; ?></p>
            <p><strong>Username:</strong> <input type="text" name="agent_username" value="<?php echo $agent_username; ?>"></p>
            <p><strong>Phone Number:</strong> <input type="text" name="agent_phoneNum" value="<?php echo $agent_phoneNum; ?>"></p>
            <p><strong>Role:</strong> <?php echo $agent_role; ?></p>
            <button type="submit" name="update_profile">Save Changes</button>
        </form>
    </div>

     <!-- Logout Popup -->
     <div id="logout-overlay"></div>
    <div class="logout-popup">
        <h2>Confirm Logout</h2>
        <button class="logout-btn">Logout</button>
        <button class="cancel-btn">Cancel</button>
    </div>

    <script>
        // Function to show the add todo form
        function showAddTodoForm() {
            document.getElementById('add-todo-form').style.display = 'block';
        }

        // Function to show all todos
        function showAllTodos() {
            document.querySelector('.todo-list').style.display = 'block';
        }

        // Function to toggle the status of a todo
        function toggleTodoStatus(index) {
            let form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            let input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'toggle_todo';
            input.value = index;

            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }

        // Profile popup scripts
        document.getElementById('profile-icon').addEventListener('click', function () {
            document.getElementById('overlay').style.display = 'block';
            document.getElementById('profile-popup').style.display = 'block';
        });

        document.querySelector('.close-btn').addEventListener('click', function () {
            document.getElementById('overlay').style.display = 'none';
            document.getElementById('profile-popup').style.display = 'none';
        });

        document.getElementById('overlay').addEventListener('click', function () {
            document.getElementById('overlay').style.display = 'none';
            document.getElementById('profile-popup').style.display = 'none';
        });
        // Handle switch mode
        document.getElementById('switch-mode').addEventListener('change', function() {
            document.body.classList.toggle('dark-mode');
        });

        // Handle logout click
        document.querySelector('.logout').addEventListener('click', function() {
            document.getElementById('logout-overlay').style.display = 'block';
            document.querySelector('.logout-popup').style.display = 'block';
        });

        // Handle logout confirmation
        document.querySelector('.logout-btn').addEventListener('click', function() {
            window.location.href = 'login.php';
        });

        // Handle logout cancellation
        document.querySelector('.cancel-btn').addEventListener('click', function() {
            document.getElementById('logout-overlay').style.display = 'none';
            document.querySelector('.logout-popup').style.display = 'none';
        });

    </script>
    <script src="script.js"></script>
</body>
</html>
