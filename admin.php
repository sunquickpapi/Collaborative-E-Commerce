<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$admin_id = htmlspecialchars($_SESSION['user_id']);

// Database connection
$conn = new mysqli("localhost", "root", "", "lunadb");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update admin data if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $admin_username = htmlspecialchars($_POST['admin_username']);
    $admin_email = htmlspecialchars($_POST['admin_email']);
    $admin_phoneNum = htmlspecialchars($_POST['admin_phoneNum']);
    
    $sql = "UPDATE admin SET admin_username=?, admin_email=?, admin_phoneNum=? WHERE admin_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $admin_username, $admin_email, $admin_phoneNum, $admin_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Profile updated successfully');</script>";
    } else {
        echo "<script>alert('Error updating profile');</script>";
    }
}

// Fetch admin data
$sql = "SELECT admin_username, admin_email, admin_phoneNum FROM admin WHERE admin_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    $admin_username = htmlspecialchars($admin['admin_username']);
    $admin_email = htmlspecialchars($admin['admin_email']);
    $admin_phoneNum = htmlspecialchars($admin['admin_phoneNum']);
} else {
    // Handle case where admin data is not found
    $admin_username = '';
    $admin_email = '';
    $admin_phoneNum = '';
}

// Calculate new order count
$sql = "SELECT COUNT(*) AS new_order_count FROM orders";
$result = $conn->query($sql);
$new_order_count = $result->fetch_assoc()['new_order_count'];

// Calculate total sales
$sql = "SELECT SUM(order_totPrice) AS total_sales FROM orders";
$result = $conn->query($sql);
$total_sales = $result->fetch_assoc()['total_sales'];

// Read visitor count from file
$counterFile = 'visitor_count.txt';
if (!file_exists($counterFile)) {
    file_put_contents($counterFile, 0);
}
$visitor_count = file_get_contents($counterFile);

// Fetch recent orders
$sql = "SELECT o.order_id, c.c_username, o.order_time, s.timeArrival 
        FROM orders o 
        JOIN customers c ON o.customer_id = c.customer_id 
        JOIN shipment s ON o.shipment_id = s.shipment_id
        ORDER BY o.order_time DESC LIMIT 5";
$recent_orders = $conn->query($sql);

// Read to-do items from JSON file
$todoFile = 'todos.json';
if (!file_exists($todoFile)) {
    file_put_contents($todoFile, json_encode([]));
}
$todos = json_decode(file_get_contents($todoFile), true);

// Handle adding new to-do item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_todo'])) {
    $newTodo = htmlspecialchars($_POST['new_todo']);
    $todos[] = ['task' => $newTodo, 'status' => 'not-completed'];
    file_put_contents($todoFile, json_encode($todos));
    header("Location: admin.php");
    exit();
}

// Handle toggling the status of a to-do item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_todo'])) {
    $index = intval($_POST['toggle_todo']);
    $todos[$index]['status'] = $todos[$index]['status'] === 'not-completed' ? 'completed' : 'not-completed';
    file_put_contents($todoFile, json_encode($todos));
    header("Location: admin.php");
    exit();
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
	<script src="https://kit.fontawesome.com/1b07de51b4.js"></script>
	<!-- My CSS -->
	<link rel="stylesheet" href="style.css">

	<title>Admin - Dashboard</title>
    <style>
        /* Overlay */
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

        /* Profile Popup */
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

        /* Close Button */
        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 20px;
            cursor: pointer;
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

        /* Form Fields */
        .profile-popup form input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

		/* Status Colors */
		.status.shipping {
			background-color: orange;
		}

		.status.delivered {
			background-color: green;
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
            color: blue;
        }

        .todo-item.not-completed p {
            color: orange;
        }

    </style>
</head>
<body> 

	<!-- SIDEBAR -->
	<section id="sidebar">
		<a href="#" class="brand">
			<i class='bx bxs-circle'></i>
			<span class="text">LVNASTUDIO</span>
		</a>
		<ul class="side-menu top">
			<li class="active">
				<a href="admin.php">
					<i class='bx bxs-dashboard' ></i>
					<span class="text">Dashboard</span>
				</a>
			</li>
			<li>
				<a href="mystore.php">
					<i class='bx bxs-shopping-bag-alt' ></i>
					<span class="text">My Store</span>
				</a>
			</li>
			<li>
				<a href="analytics.php">
					<i class='bx bxs-doughnut-chart' ></i>
					<span class="text">Analytics</span>
				</a>
			</li>
			<li>
				<a href="team.php">
					<i class='bx bxs-group' ></i>
					<span class="text">Team</span>
				</a>
			</li>
		</ul>
		<ul class="side-menu">
			<li>
                <a href="#" class="logout-link">
                    <i class='bx bxs-log-out-circle' ></i>
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
			<i class='bx bx-menu' ></i>
			<a href="#" class="nav-link">Admin</a>
			<form action="#">
                <div class="form-input">
                    <button type="submit" class="search-btn" hidden><i class='bx bx-shield-alt-2'></i></button>
                </div>
			</form>
			<input type="checkbox" id="switch-mode" hidden>
			<label for="switch-mode" class="switch-mode"></label>
			<div class="profile">
                <img src="img/admin/login.png" alt="Profile" id="profile-icon">
            </div>
		</nav>

        <div id="overlay"></div>
        <div id="profile-popup" class="profile-popup">
            <span class="close-btn">&times;</span>
            <form method="POST">
                <p><strong>ID:</strong> <?php echo $admin_id; ?></p>
                <p><strong>Username:</strong> <input type="text" name="admin_username" value="<?php echo $admin_username; ?>"></p>
                <p><strong>Email:</strong> <input type="email" name="admin_email" value="<?php echo $admin_email; ?>"></p>
                <p><strong>Phone:</strong> <input type="text" name="admin_phoneNum" value="<?php echo $admin_phoneNum; ?>"></p>
                <button type="submit">Save Changes</button>
            </form>
        </div>
        <div id="logout-overlay"></div>
        <div id="logout-popup" class="logout-popup">
            <p>Are you sure you want to logout?</p>
            <button class="logout-btn" id="confirm-logout">Logout</button>
            <button class="cancel-btn" id="cancel-logout">Cancel</button>
        </div>
		<!-- NAVBAR -->

		<!-- MAIN -->
		<main>
			<div class="head-title">
				<div class="left">
					<h1>Dashboard</h1>
					<ul class="breadcrumb">
						<li>
							<a href="#">Admin</a>
						</li>
						<li><i class='bx bx-chevron-right' ></i></li>
						<li>
							<a class="active" href="#">Dashboard</a>
						</li>
					</ul>
				</div>
			</div>

			<ul class="box-info">
                <li>
                    <i class='bx bxs-calendar-check' ></i>
                    <span class="text">
                        <h3><?php echo $new_order_count; ?></h3>
                        <p>New Order</p>
                    </span>
                </li>
                <li>
                    <i class='bx bxs-group' ></i>
                    <span class="text">
                        <h3><?php echo $visitor_count; ?></h3>
                        <p>Visitors</p>
                    </span>
                </li>
                <li>
                    <i class='bx bxs-dollar-circle' ></i>
                    <span class="text">
                        <h3>RM <?php echo number_format($total_sales, 2); ?></h3>
                        <p>Total Sales</p>
                    </span>
                </li>
            </ul>

			<div class="table-data">
				<div class="order">
					<div class="head">
						<h3>Recent Orders</h3>
					</div>
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>User</th>
                                <th>Date Order</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = $recent_orders->fetch_assoc()): ?>
                                <?php
                                $statusClass = (new DateTime() >= new DateTime($order['timeArrival'])) ? 'status delivered' : 'status shipping';
                                $statusText = (new DateTime() >= new DateTime($order['timeArrival'])) ? 'Delivered' : 'Shipping';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                    <td><?php echo htmlspecialchars($order['c_username']); ?></td>
                                    <td><?php echo htmlspecialchars($order['order_time']); ?></td>
                                    <td><span class="<?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                                </tr>
                            <?php endwhile; ?>
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
	
    <script>
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

		// Logout confirmation script
        document.querySelector('.logout-link').addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('logout-overlay').style.display = 'block';
            document.getElementById('logout-popup').style.display = 'block';
        });

        document.getElementById('cancel-logout').addEventListener('click', function () {
            document.getElementById('logout-overlay').style.display = 'none';
            document.getElementById('logout-popup').style.display = 'none';
        });

        document.getElementById('confirm-logout').addEventListener('click', function () {
            window.location.href = 'login.php';
        });

		function showAddTodoForm() {
            document.getElementById('add-todo-form').style.display = 'block';
        }

        function showAllTodos() {
			document.querySelector('.todo-list').style.display = 'block';
         }

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

    </script>

	<script src="script.js"></script>
</body>
</html>
