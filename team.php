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
    $admin_username = '';
    $admin_email = '';
    $admin_phoneNum = '';
}

// Fetch agents
$sql = "SELECT agent_id, agent_username FROM agent";
$agents_result = $conn->query($sql);

// Fetch designers
$sql = "SELECT designer_id, designer_username FROM designer";
$designers_result = $conn->query($sql);


if (isset($_GET['profile_id']) && isset($_GET['type'])) {
    $profile_id = intval($_GET['profile_id']);
    $type = $_GET['type'];

    switch ($type) {
        case 'agent':
            $sql = "SELECT agent_id as id, agent_username as username, agent_email as email, agent_phoneNum as phone FROM agent WHERE agent_id = ?";
            break;
        case 'designer':
            $sql = "SELECT designer_id as id, designer_username as username, designer_email as email, designer_phoneNum as phone, designer_status as status FROM designer WHERE designer_id = ?";
            break;
        default:
            echo json_encode(['error' => 'Invalid type']);
            exit();
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $profile_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();
    echo json_encode($profile);
    $stmt->close();
    $conn->close();
    exit();
}

if (isset($_GET['agent_performance_id'])) {
    $agent_id = intval($_GET['agent_performance_id']);

    $sql = "SELECT order_detail, order_totPrice FROM orders WHERE agent_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    echo json_encode(['orders' => $orders]);
    $stmt->close();
    $conn->close();
    exit();
}

if (isset($_GET['sales_data'])) {
    $sql = "SELECT a.agent_id as id, a.agent_username as username, SUM(o.order_totPrice) as total_sales 
            FROM agent a 
            LEFT JOIN orders o ON a.agent_id = o.agent_id 
            GROUP BY a.agent_id";
    $result = $conn->query($sql);

    $sales_data = [];
    while ($row = $result->fetch_assoc()) {
        $sales_data[] = $row;
    }

    echo json_encode($sales_data);
    $conn->close();
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
    <!-- My CSS -->
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Admin - Team</title>
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

        .team-section {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .team-column, .profile-section {
            width: 30%;
            padding: 10px;
            background-color: #f1f1f1;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .team-column h3, .profile-section h3 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }

        .team-column ul {
            list-style: none;
            padding: 0;
        }

        .team-column li {
            padding: 10px;
            margin-bottom: 10px;
            background-color: #fff;
            border-radius: 5px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .team-column li:hover {
            background-color: #e0e0e0;
            transform: translateY(-5px);
        }

        .team-column li:last-child {
            margin-bottom: 0;
        }


        .profile-section p {
            margin-bottom: 10px;
            font-size: 18px;
        }

        .agent-performance {
            margin-top: 20px;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .agent-performance h4 {
            margin-bottom: 10px;
            font-size: 20px;
        }

        .agent-performance ul {
            list-style: none;
            padding: 0;
        }

        .agent-performance li {
            margin-bottom: 5px;
        }

        .sales-chart-container {
            margin-top: 20px;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        /* Dark mode styles */
        body.dark .team-column,
        body.dark .profile-section,
        body.dark .agent-performance,
        body.dark .sales-chart-container {
            background-color: #333;
            color: #fff;
        }

        body.dark .team-column h3,
        body.dark .profile-section h3,
        body.dark .agent-performance h4 {
            color: #fff;
        }

        body.dark .team-column li {
            background-color: #444;
            color: #fff;
        }

        body.dark .team-column li:hover {
            background-color: #555;
        }

        body.dark .profile-section p,
        body.dark .agent-performance li {
            color: #ccc;
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
            <li>
                <a href="admin.php">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="mystore.php">
                    <i class='bx bxs-shopping-bag-alt'></i>
                    <span class="text">My Store</span>
                </a>
            </li>
            <li>
                <a href="analytics.php">
                    <i class='bx bxs-doughnut-chart'></i>
                    <span class="text">Analytics</span>
                </a>
            </li>
            <li class="active">
                <a href="team.php">
                    <i class='bx bxs-group'></i>
                    <span class="text">Team</span>
                </a>
            </li>
        </ul>
        <ul class="side-menu">
            <li>
                <a href="#" class="logout-link">
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
                    <h1>Team</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="#">Admin</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">Team</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="team-section">
                <div class="team-column">
                    <h3>Agents</h3>
                    <ul>
                        <?php while ($agent = $agents_result->fetch_assoc()): ?>
                            <li data-id="<?php echo htmlspecialchars($agent['agent_id']); ?>" data-type="agent" class="team-member"><?php echo htmlspecialchars($agent['agent_username']); ?></li>
                        <?php endwhile; ?>
                    </ul>
                </div>

                <div class="profile-section" id="profile-section">
                    <h3>Profile</h3>
                    <p><strong>ID:</strong> <span id="profile-id"></span></p>
                    <p><strong>Username:</strong> <span id="profile-username"></span></p>
                    <p id="profile-email-container"><strong>Email:</strong> <span id="profile-email"></span></p>
                    <p id="profile-phone-container"><strong>Phone:</strong> <span id="profile-phone"></span></p>
                    <p id="profile-status-container"><strong>Status:</strong> <span id="profile-status"></span></p>
                </div>

                <div class="team-column">
                    <h3>Designers</h3>
                    <ul>
                        <?php while ($designer = $designers_result->fetch_assoc()): ?>
                            <li data-id="<?php echo htmlspecialchars($designer['designer_id']); ?>" data-type="designer" class="team-member"><?php echo htmlspecialchars($designer['designer_username']); ?></li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>


            <div class="agent-performance" id="agent-performance" style="display: none;">
                <h4>Agent Performance</h4>
                <ul id="agent-performance-list"></ul>
            </div>

            <div class="sales-chart-container">
            <h4>Agents Performance Bar Chart</h4>
                <canvas id="sales-chart"></canvas>
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

    // Fetch and display profile information
    document.querySelectorAll('.team-member').forEach(member => {
        member.addEventListener('click', function () {
            const memberId = this.getAttribute('data-id');
            const type = this.getAttribute('data-type');
            fetchProfile(memberId, type);
            if (type === 'agent') {
                fetchAgentPerformance(memberId);
            } else {
                document.getElementById('agent-performance').style.display = 'none';
            }
        });
    });

    function fetchProfile(memberId, type) {
        fetch(`team.php?profile_id=${memberId}&type=${type}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('profile-id').textContent = data.id;
                document.getElementById('profile-username').textContent = data.username;

                if (type === 'agent' || type === 'designer') {
                    document.getElementById('profile-email-container').style.display = 'block';
                    document.getElementById('profile-phone-container').style.display = 'block';
                    document.getElementById('profile-email').textContent = data.email;
                    document.getElementById('profile-phone').textContent = data.phone;
                } else {
                    document.getElementById('profile-email-container').style.display = 'none';
                    document.getElementById('profile-phone-container').style.display = 'none';
                }

                if (type === 'designer') {
                    document.getElementById('profile-status-container').style.display = 'block';
                    document.getElementById('profile-status').textContent = data.status;
                } else {
                    document.getElementById('profile-status-container').style.display = 'none';
                }

                document.getElementById('profile-section').style.display = 'block';
            });
    }

    function fetchAgentPerformance(agentId) {
        fetch(`team.php?agent_performance_id=${agentId}`)
            .then(response => response.json())
            .then(data => {
                const performanceList = document.getElementById('agent-performance-list');
                performanceList.innerHTML = '';
                data.orders.forEach(order => {
                    const listItem = document.createElement('li');
                    listItem.textContent = `Order Detail: ${order.order_detail}, Total Price: RM${order.order_totPrice}`;
                    performanceList.appendChild(listItem);
                });
                document.getElementById('agent-performance').style.display = 'block';
            });
    }

    function fetchSalesData() {
        fetch('team.php?sales_data=true')
            .then(response => response.json())
            .then(data => {
                const ctx = document.getElementById('sales-chart').getContext('2d');
                const chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.map(agent => agent.username),
                        datasets: [{
                            label: 'Total Sales',
                            data: data.map(agent => agent.total_sales),
                            backgroundColor: data.map(agent => agent.id === selectedAgentId ? 'pink' : 'blue')
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                document.querySelectorAll('.team-member').forEach(member => {
                    member.addEventListener('click', function () {
                        const memberId = parseInt(this.getAttribute('data-id'));
                        selectedAgentId = memberId;
                        chart.data.datasets[0].backgroundColor = data.map(agent => agent.id === selectedAgentId ? 'pink' : 'blue');
                        chart.update();
                    });
                });
            });
    }

    let selectedAgentId = null;
    fetchSalesData();

    // Dark mode switch functionality
    const switchMode = document.getElementById('switch-mode');
    switchMode.addEventListener('change', function () {
        document.body.classList.toggle('dark', switchMode.checked);
    });

    // Check if dark mode should be enabled on page load
    document.addEventListener('DOMContentLoaded', function () {
        if (switchMode.checked) {
            document.body.classList.add('dark');
        }
    });
</script>

</body>
</html>
