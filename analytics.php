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
    // Handle case where admin data is not found
    $admin_username = '';
    $admin_email = '';
    $admin_phoneNum = '';
}

// Fetch new order count
$sql = "SELECT COUNT(*) AS new_order_count FROM orders";
$result = $conn->query($sql);
$new_order_count = $result->fetch_assoc()['new_order_count'];

// Fetch total sales
$sql = "SELECT SUM(order_totPrice) AS total_sales FROM orders";
$result = $conn->query($sql);
$total_sales = $result->fetch_assoc()['total_sales'];

// Read visitor count from file
$counterFile = 'visitor_count.txt';
if (!file_exists($counterFile)) {
    file_put_contents($counterFile, 0);
}
$visitor_count = file_get_contents($counterFile);

// Fetch product analysis data
$sql = "SELECT order_detail FROM orders";
$result = $conn->query($sql);
$product_counts = [];
$total_products_sold = 0;

while ($row = $result->fetch_assoc()) {
    $products = explode(", ", $row['order_detail']);
    foreach ($products as $product) {
        if (!isset($product_counts[$product])) {
            $product_counts[$product] = 0;
        }
        $product_counts[$product]++;
        $total_products_sold++;
    }
}

// Fetch monthly sales data
$monthly_sales = [];
for ($i = 1; $i <= 12; $i++) {
    $month = str_pad($i, 2, "0", STR_PAD_LEFT);
    $sql = "SELECT SUM(order_totPrice) AS monthly_sales FROM orders WHERE DATE_FORMAT(order_time, '%m') = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $month);
    $stmt->execute();
    $result = $stmt->get_result();
    $monthly_sales[$i] = $result->fetch_assoc()['monthly_sales'] ?: 0;
}

// Fetch yearly sales data
$yearly_sales = [];
for ($i = 2024; $i <= 2030; $i++) {
    $sql = "SELECT SUM(order_totPrice) AS yearly_sales FROM orders WHERE DATE_FORMAT(order_time, '%Y') = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $i);
    $stmt->execute();
    $result = $stmt->get_result();
    $yearly_sales[$i] = $result->fetch_assoc()['yearly_sales'] ?: 0;
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
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- My CSS -->
    <link rel="stylesheet" href="style.css">
    <title>Admin - Analytics</title>
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

        /* Chart Container */
        .chart-container {
            position: relative;
            margin: auto;
            height: 40vh;
            width: 100%;
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

        /* Title Spacing */
        .chart-title {
            margin-bottom: 20px;
        }

        .report-section {
            margin-top: 20px;
            text-align: center;
        }

        .report-buttons {
            text-align: center;
        }

        .report-buttons button {
            padding: 10px 20px;
            margin: 10px;
            cursor: pointer;
            width: 200px;
        }

        .report-charts {
            display: none;
            margin-top: 20px;
        }

        .chart-container {
            margin-top: 20px;
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
            <li class="active">
                <a href="analytics.php">
                    <i class='bx bxs-doughnut-chart'></i>
                    <span class="text">Analytics</span>
                </a>
            </li>
            <li>
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
                <input type="hidden" name="admin_id" value="<?php echo $admin_id; ?>">
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
                    <h1>Analytics</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="#">Admin</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">Analytics</a>
                        </li>
                    </ul>
                </div>
            </div>

            <ul class="box-info">
                <li>
                    <i class='bx bxs-calendar-check'></i>
                    <span class="text">
                        <h3><?php echo $new_order_count; ?></h3>
                        <p>New Order</p>
                    </span>
                </li>
                <li>
                    <i class='bx bxs-group'></i>
                    <span class="text">
                        <h3><?php echo $visitor_count; ?></h3>
                        <p>Visitors</p>
                    </span>
                </li>
                <li>
                    <i class='bx bxs-dollar-circle'></i>
                    <span class="text">
                        <h3>RM <?php echo number_format($total_sales, 2); ?></h3>
                        <p>Total Sales</p>
                    </span>
                </li>
            </ul>

            <div class="table-data">
                <div class="order">
                    <div class="head">
                        <h3>Product Analysis</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="productChart"></canvas>
                    </div>
                </div>

                <!-- Report Section -->
                <div class="report-section">
                    <div class="head">
                        <h3>Sales Report</h3>
                    </div>
                    <div class="report-buttons">
                        <button id="monthlyReportBtn">Monthly Report</button>
                        <button id="yearlyReportBtn">Yearly Report</button>
                    </div>
                    <div class="report-charts" id="monthlyChartContainer">
                        <div class="chart-container">
                            <canvas id="monthlyReportChart"></canvas>
                        </div>
                    </div>
                    <div class="report-charts" id="yearlyChartContainer">
                        <div class="chart-container">
                            <canvas id="yearlyReportChart"></canvas>
                        </div>
                    </div>
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

        const productData = {
            labels: <?php echo json_encode(array_map(function($product, $count) use ($total_products_sold) {
                return $product . ' (' . $count . ' - ' . round(($count / $total_products_sold) * 100, 2) . '%)';
            }, array_keys($product_counts), $product_counts)); ?>,
            datasets: [{
                label: 'Product Analysis',
                data: <?php echo json_encode(array_values($product_counts)); ?>,
                backgroundColor: [
                    '#ff6384',
                    '#36a2eb',
                    '#ffce56',
                    '#4bc0c0',
                    '#9966ff',
                    '#ff9f40'
                ],
                hoverOffset: 4
            }]
        };

        const productConfig = {
            type: 'doughnut',
            data: productData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Product Analysis'
                    }
                }
            },
        };

        const monthlySalesData = {
            labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            datasets: [{
                label: 'Monthly Sales (RM)',
                data: <?php echo json_encode(array_values($monthly_sales)); ?>,
                backgroundColor: '#36a2eb'
            }]
        };

        const yearlySalesData = {
            labels: ['2024', '2025', '2026', '2027', '2028', '2029', '2030'],
            datasets: [{
                label: 'Yearly Sales (RM)',
                data: <?php echo json_encode(array_values($yearly_sales)); ?>,
                backgroundColor: '#ff6384'
            }]
        };

        const monthlyConfig = {
            type: 'bar',
            data: monthlySalesData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Monthly Sales Report'
                    }
                }
            }
        };

        const yearlyConfig = {
            type: 'bar',
            data: yearlySalesData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Yearly Sales Report'
                    }
                }
            }
        };

        window.onload = function() {
            const productCtx = document.getElementById('productChart').getContext('2d');
            new Chart(productCtx, productConfig);

            const monthlyCtx = document.getElementById('monthlyReportChart').getContext('2d');
            const yearlyCtx = document.getElementById('yearlyReportChart').getContext('2d');

            let monthlyChart;
            let yearlyChart;

            document.getElementById('monthlyReportBtn').addEventListener('click', function () {
                const monthlyContainer = document.getElementById('monthlyChartContainer');
                if (monthlyContainer.style.display === 'block') {
                    monthlyContainer.style.display = 'none';
                    if (monthlyChart) {
                        monthlyChart.destroy();
                        monthlyChart = null;
                    }
                } else {
                    monthlyContainer.style.display = 'block';
                    if (monthlyChart) {
                        monthlyChart.destroy();
                    }
                    monthlyChart = new Chart(monthlyCtx, monthlyConfig);
                }
            });

            document.getElementById('yearlyReportBtn').addEventListener('click', function () {
                const yearlyContainer = document.getElementById('yearlyChartContainer');
                if (yearlyContainer.style.display === 'block') {
                    yearlyContainer.style.display = 'none';
                    if (yearlyChart) {
                        yearlyChart.destroy();
                        yearlyChart = null;
                    }
                } else {
                    yearlyContainer.style.display = 'block';
                    if (yearlyChart) {
                        yearlyChart.destroy();
                    }
                    yearlyChart = new Chart(yearlyCtx, yearlyConfig);
                }
            });
        };
    </script>

    <script src="script.js"></script>
</body>
</html>

