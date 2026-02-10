<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$designer_id = htmlspecialchars($_SESSION['user_id']);

include("connection.php");

// Handle status update request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['status'])) {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'designer') {
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }

    $designer_id = $_SESSION['user_id'];
    $new_status = $_POST['status'];

    $query = "UPDATE designer SET designer_status = ? WHERE designer_id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        echo json_encode(['error' => 'Database error: ' . htmlspecialchars($conn->error)]);
        exit();
    }

    $stmt->bind_param("si", $new_status, $designer_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to update status']);
    }

    $stmt->close();
    exit();
}

// Fetch unread agent messages
$sql_agent_unread = "SELECT COUNT(*) as unread_count FROM agent_designer_messages WHERE receiver_id = ? AND read_status = 0";
$stmt_agent = $conn->prepare($sql_agent_unread);
$stmt_agent->bind_param("i", $designer_id);
$stmt_agent->execute();
$result_agent = $stmt_agent->get_result();
$agent_unread_count = $result_agent->fetch_assoc()['unread_count'];
$stmt_agent->close();

// Total unread messages
$total_notread_count = $agent_unread_count;

// Read to-do items from JSON file
$todoFile = 'designer_todos.json';
if (!file_exists($todoFile)) {
    file_put_contents($todoFile, json_encode([]));
}
$todos = json_decode(file_get_contents($todoFile), true);

// Handle adding new to-do item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_todo'])) {
    $newTodo = htmlspecialchars($_POST['new_todo']);
    $todos[] = ['task' => $newTodo, 'status' => 'not-completed'];
    file_put_contents($todoFile, json_encode($todos));
    header("Location: designer.php");
    exit();
}

// Handle toggling the status of a to-do item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_todo'])) {
    $index = intval($_POST['toggle_todo']);
    $todos[$index]['status'] = $todos[$index]['status'] === 'not-completed' ? 'completed' : 'not-completed';
    file_put_contents($todoFile, json_encode($todos));
    header("Location: designer.php");
    exit();
}

// Fetch designer details
$sql_designer = "SELECT designer_username, designer_phoneNum, role, designer_status FROM designer WHERE designer_id=?";
$stmt_designer = $conn->prepare($sql_designer);
$stmt_designer->bind_param("i", $designer_id);
$stmt_designer->execute();
$result_designer = $stmt_designer->get_result();
$designer = $result_designer->fetch_assoc();

$designer_username = htmlspecialchars($designer['designer_username']);
$designer_phoneNum = htmlspecialchars($designer['designer_phoneNum']);
$designer_role = htmlspecialchars($designer['role']);
$designer_status = htmlspecialchars($designer['designer_status']);

// Fetch the total count of completed designs
$sql_design_count = "SELECT COUNT(*) as design_count FROM designs";
$result_design_count = $conn->query($sql_design_count);
$completed_design_count = $result_design_count->fetch_assoc()['design_count'];

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
    <title>Designer - Dashboard</title>
    <style>
        .status-available {
            color: blue;
        }
        .status-unavailable {
            color: red;
        }
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
        /* Dark Mode Styling */
        body.dark-mode .profile-popup, body.dark-mode .logout-popup {
            background-color: #1e1e1e;
            color: #ddd;
        }

        body.dark-mode #overlay, body.dark-mode #logout-overlay {
            background-color: rgba(0, 0, 0, 0.7);
        }
        /* Creativity Section Styling */
        .creativity-section {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .creativity-card {
            width: 180px;
            height: 180px;
            border: 1px solid #ddd;
            border-radius: 10px;
            overflow: hidden;
        }

        .creativity-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <i class='bx bxs-circle'></i>
            <span class="text">LVNADESIGNER</span>
        </a>
        <ul class="side-menu top">
            <li class="active">
                <a href="designer.php">
                    <i class='bx bxs-shopping-bag'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="designer_message.php">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Agent Message</span>
                </a>
            </li>
            <li>
                <a href="designer_draft.php">
                    <i class='bx bxs-group' ></i>
                    <span class="text">Design Draft</span>
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
            <a href="#" class="nav-link">DESIGNER</a>
            <form action="#">
                <div class="form-input">
                    <button type="submit" class="search-btn" hidden><i class='bx bxs-t-shirt'></i></button>
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
                            <a href="#">Designer</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">Dashboard</a>
                        </li>
                    </ul>
                </div>
            </div>

            <ul class="box-info">
                <li id="status-box" class="<?php echo $designer_status == 'Available' ? 'status-available' : 'status-unavailable'; ?>">
                    <i class='bx <?php echo $designer_status == 'Available' ? 'bxs-smile' : 'bxs-sad'; ?>'></i>
                    <span class="text">
                        <h3 id="status-text"><?php echo $designer_status; ?></h3>
                        <p>Your Status</p>
                    </span>
                </li>
                <li>
                    <i class='bx bxs-message'></i>
                    <span class="text">
                    <h3><?php echo $total_notread_count; ?></h3>
                        <p>New Message</p>
                    </span>
                </li>
                <li>
                    <i class='bx bxs-group'></i>
                    <span class="text">
                    <h3><?php echo $completed_design_count; ?></h3>
                        <p>Completed Design</p>
                    </span>
                </li>
            </ul>

            <div class="table-data">
                <div class="order">
                    <div class="head">
                        <h3>Creativity Section</h3>
                        <i class='bx bx-search'></i>
                        <i class='bx bx-filter'></i>
                    </div>
                    <div class="creativity-section">
                        <?php
                        $dir = 'creativity'; // Directory containing images
                        $images = glob($dir . '/*.jpg'); // Get all jpg files in the directory

                        foreach ($images as $image) {
                            echo '<div class="creativity-card">';
                            echo '<img src="' . $image . '" alt="Image">';
                            echo '</div>';
                        }
                        ?>
                    </div>
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
            <p><strong>ID:</strong> <?php echo $designer_id; ?></p>
            <p><strong>Username:</strong> <input type="text" name="designer_username" value="<?php echo $designer_username; ?>"></p>
            <p><strong>Phone Number:</strong> <input type="text" name="designer_phoneNum" value="<?php echo $designer_phoneNum; ?>"></p>
            <p><strong>Role:</strong> <?php echo $designer_role; ?></p>
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

        // Function to save a note for the selected date
        function saveNote() {
            const noteText = document.getElementById('note').value;
            if (noteText.trim() !== '') {
                // Save the note (for demonstration purposes, we just log it to the console)
                console.log('Note for the selected date:', noteText);
                alert('Note saved!');
                document.getElementById('note').value = '';
            }
        }

        // Function to toggle designer status
        document.getElementById('status-box').addEventListener('click', function() {
            const statusBox = this;
            const statusText = document.getElementById('status-text');
            const statusIcon = statusBox.querySelector('i');
            let newStatus = '';

            if (statusBox.classList.contains('status-available')) {
                statusBox.classList.remove('status-available');
                statusBox.classList.add('status-unavailable');
                statusIcon.classList.remove('bxs-smile');
                statusIcon.classList.add('bxs-sad');
                statusText.textContent = 'Unavailable';
                newStatus = 'Unavailable';
            } else {
                statusBox.classList.remove('status-unavailable');
                statusBox.classList.add('status-available');
                statusIcon.classList.remove('bxs-sad');
                statusIcon.classList.add('bxs-smile');
                statusText.textContent = 'Available';
                newStatus = 'Available';
            }

            // AJAX call to update status in the database
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'designer.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4) {
                    console.log('AJAX request completed with status: ' + xhr.status);
                    console.log('Response: ' + xhr.responseText);
                    if (xhr.status == 200) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.error) {
                            alert('Error updating status: ' + response.error);
                        } else {
                            alert('Status updated successfully!');
                        }
                    }
                }
            };
            xhr.send('status=' + newStatus);
        });
    </script>
    <script src="script.js"></script>
</body>
</html>
