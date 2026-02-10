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

// Directory to save uploaded images
$target_dir = "LVNAlatest/lvnaProducts/";

// Create the directory if it doesn't exist
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true); // Creates the directory with the appropriate permissions
}

// Add or update product if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = htmlspecialchars($_POST['product_id']);
    $product_type = htmlspecialchars($_POST['product_type']);
    $product_name = htmlspecialchars($_POST['product_name']);
    $product_quantity = htmlspecialchars($_POST['product_quantity']);
    $product_price = htmlspecialchars($_POST['product_price']);
    $product_details = htmlspecialchars($_POST['product_details']);
    $product_images = [];

    for ($i = 1; $i <= 4; $i++) {
        if (isset($_FILES["product_image_$i"]) && $_FILES["product_image_$i"]["name"]) {
            $original_file_name = basename($_FILES["product_image_$i"]["name"]);
            $target_file = $target_dir . $original_file_name;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            $uploadOk = 1;

            // Check if image file is an actual image or fake image
            $check = getimagesize($_FILES["product_image_$i"]["tmp_name"]);
            if ($check === false) {
                echo "<script>alert('File is not an image.');</script>";
                $uploadOk = 0;
            }

            // Rename the file if it already exists
            $counter = 1;
            while (file_exists($target_file)) {
                $target_file = $target_dir . pathinfo($original_file_name, PATHINFO_FILENAME) . '_' . $counter . '.' . $imageFileType;
                $counter++;
            }

            // Check file size
            if ($_FILES["product_image_$i"]["size"] > 500000) { // 500 KB limit
                echo "<script>alert('Sorry, your file is too large.');</script>";
                $uploadOk = 0;
            }

            // Allow certain file formats
            if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
                echo "<script>alert('Sorry, only JPG, JPEG, PNG & GIF files are allowed.');</script>";
                $uploadOk = 0;
            }

            if ($uploadOk && move_uploaded_file($_FILES["product_image_$i"]["tmp_name"], $target_file)) {
                $product_images[$i] = htmlspecialchars(basename($target_file));
            } else {
                echo "<script>alert('Sorry, there was an error uploading your file.');</script>";
            }
        } else {
            // If no new image is uploaded, retain the existing image
            if (!empty($_POST["existing_image_$i"])) {
                $product_images[$i] = htmlspecialchars($_POST["existing_image_$i"]);
            } else {
                $product_images[$i] = null;
            }
        }
    }

    if ($product_id) {
        // Update product
        $sql = "UPDATE products SET product_type=?, product_name=?, product_quantity=?, product_price=?, product_details=?, product_image_1=?, product_image_2=?, product_image_3=?, product_image_4=? WHERE product_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssissssssi", $product_type, $product_name, $product_quantity, $product_price, $product_details, $product_images[1], $product_images[2], $product_images[3], $product_images[4], $product_id);
    } else {
        // Insert new product
        $sql = "INSERT INTO products (product_type, product_name, product_quantity, product_price, product_details, product_image_1, product_image_2, product_image_3, product_image_4) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssissssss", $product_type, $product_name, $product_quantity, $product_price, $product_details, $product_images[1], $product_images[2], $product_images[3], $product_images[4]);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Product saved successfully');</script>";
    } else {
        echo "Error: " . htmlspecialchars($stmt->error);
    }

    $stmt->close();
}

// Delete product if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_action']) && $_POST['product_action'] === 'delete') {
    $product_id = htmlspecialchars($_POST['product_id']);
    $sql = "DELETE FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'product_id' => $product_id]);
        exit();
    } else {
        echo json_encode(['success' => false, 'error' => htmlspecialchars($stmt->error)]);
        exit();
    }

    $stmt->close();
}

// Fetch products
$sql = "SELECT * FROM products";
$products = $conn->query($sql);

// Handle AJAX request for fetching product details
if (isset($_GET['action']) && $_GET['action'] == 'fetch_product_details' && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $sql = "SELECT * FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    header('Content-Type: application/json');
    echo json_encode($product);

    $stmt->close();
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

    <title>Admin - My Store</title>
    <style>
        #overlay, #logout-overlay, #delete-overlay {
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
        .profile-popup, .logout-popup, .delete-popup {
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
        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 20px;
            cursor: pointer;
        }
        .logout-btn, .delete-btn {
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
        .side-menu .logout {
            color: red;
        }
        .product-form-container {
            background-color: var(--light);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .product-form-container h3 {
            margin-bottom: 20px;
        }
        .product-form-container table {
            width: 100%;
            border-collapse: collapse;
        }
        .product-form-container table th,
        .product-form-container table td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        .product-form-container table th {
            background-color: var(--grey);
        }
        .product-form-container table td input[type="text"],
        .product-form-container table td input[type="number"],
        .product-form-container table td input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            outline: none;
        }
        .product-form-container table td button {
            background-color: var(--blue);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .product-form-container table td button:hover {
            background-color: var(--dark-blue);
        }
        .product-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        .product {
            width: 200px;
            background-color: var(--light);
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .product img {
            max-width: 100%;
            border-radius: 10px;
        }
        .product-details {
            margin-top: 10px;
        }
        .product-details h4 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        .product-details p {
            font-size: 14px;
            color: var(--dark-grey);
        }
        .product-details button {
            background-color: var(--blue);
            color: #fff;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        .product-details button:hover {
            background-color: var(--dark-blue);
        }
        /* Dark mode styles */
        body.dark .product-form-container,
        body.dark .product {
            background-color: var(--dark);
            color: var(--light);
        }
        body.dark .product-form-container table th,
        body.dark .product-form-container table td {
            border: 1px solid var(--grey);
        }
        body.dark .product-form-container table th {
            background-color: var(--dark-grey);
        }
        body.dark .product-form-container table td input[type="text"],
        body.dark .product-form-container table td input[type="number"],
        body.dark .product-form-container table td input[type="file"] {
            background-color: var(--grey);
            color: var(--light);
            border: 1px solid var(--dark-grey);
        }
        body.dark .product-form-container table td button,
        body.dark .product-details button {
            background-color: var(--blue);
            color: var(--light);
        }
        body.dark .product-form-container table td button:hover,
        body.dark .product-details button:hover {
            background-color: var(--dark-blue);
        }
        body.dark .product-details p {
            color: var(--grey);
        }
        /* Ensure input text is visible in dark mode */
        body.dark .product-form-container table td input[type="text"],
        body.dark .product-form-container table td input[type="number"],
        body.dark .product-form-container table td input[type="file"]::placeholder {
            color: var(--light);
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
            <li class="hidden">
                <a href="admin.php">
                    <i class='bx bxs-dashboard' ></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li class="active">
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
        <div id="delete-overlay"></div>
        <div id="delete-popup" class="delete-popup">
            <p>Are you sure you want to delete this product?</p>
            <form method="POST" id="delete-form">
                <input type="hidden" name="product_id" id="delete-product-id">
                <input type="hidden" name="product_action" value="delete">
                <button type="submit" class="delete-btn">Delete</button>
                <button type="button" class="cancel-btn" id="cancel-delete">Cancel</button>
            </form>
        </div>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <div class="head-title">
                <div class="left">
                    <h1>My Store</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="#">Admin</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">My Store</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="product-form-container">
                <div class="form-title"><h3>Add or Edit Product</h3></div>
                <form id="product-form" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" id="product_id">
                    <table class="form-table">
                        <tr>
                            <th>Product Type</th>
                            <td><input type="text" name="product_type" placeholder="Product Type" required></td>
                        </tr>
                        <tr>
                            <th>Product Name</th>
                            <td><input type="text" name="product_name" placeholder="Product Name" required></td>
                        </tr>
                        <tr>
                            <th>Quantity</th>
                            <td><input type="number" name="product_quantity" placeholder="Quantity" required></td>
                        </tr>
                        <tr>
                            <th>Price (RM)</th>
                            <td><input type="number" name="product_price" placeholder="Price (RM)" required></td>
                        </tr>
                        <tr>
                            <th>Product Details</th>
                            <td><input type="text" name="product_details" placeholder="Product Details" required></td>
                        </tr>
                        <?php for ($i = 1; $i <= 4; $i++): ?>
                            <tr>
                                <th>Product Image <?php echo $i; ?></th>
                                <td>
                                    <input type="file" name="product_image_<?php echo $i; ?>" accept="image/*">
                                    <input type="hidden" name="existing_image_<?php echo $i; ?>" id="existing_image_<?php echo $i; ?>">
                                </td>
                            </tr>
                        <?php endfor; ?>
                        <tr>
                            <td colspan="2" style="text-align: center;"><button type="submit">Save Product</button></td>
                        </tr>
                    </table>
                </form>
            </div>

            <div class="list-title"><h3>Product List</h3></div>
            <div id="product-list" class="product-container">
                <?php if ($products->num_rows > 0): ?>
                    <?php while($row = $products->fetch_assoc()): ?>
                        <div class="product" id="product-<?php echo htmlspecialchars($row['product_id']); ?>">
                            <img src="LVNAlatest/lvnaProducts/<?php echo htmlspecialchars($row['product_image_1']); ?>" alt="">
                            <div class="product-details">
                                <h4><?php echo htmlspecialchars($row['product_name']); ?></h4>
                                <p>Type: <?php echo htmlspecialchars($row['product_type']); ?></p>
                                <p>Quantity: <?php echo htmlspecialchars($row['product_quantity']); ?></p>
                                <p>Price: RM<?php echo htmlspecialchars($row['product_price']); ?></p>
                                <p><?php echo htmlspecialchars($row['product_details']); ?></p>
                                <button class="edit-btn" data-id="<?php echo htmlspecialchars($row['product_id']); ?>">Edit</button>
                                
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No products found</p>
                <?php endif; ?>
            </div>

        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <script>
        // Profile popup script
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

        // Edit button functionality
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function () {
                const productId = this.getAttribute('data-id');
                fetchProductDetails(productId);
            });
        });

        function fetchProductDetails(productId) {
            fetch('mystore.php?action=fetch_product_details&id=' + productId)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('product_id').value = data.product_id;
                    document.querySelector('[name="product_type"]').value = data.product_type;
                    document.querySelector('[name="product_name"]').value = data.product_name;
                    document.querySelector('[name="product_quantity"]').value = data.product_quantity;
                    document.querySelector('[name="product_price"]').value = data.product_price;
                    document.querySelector('[name="product_details"]').value = data.product_details;

                    for (let i = 1; i <= 4; i++) {
                        document.getElementById('existing_image_' + i).value = data['product_image_' + i];
                    }
                });
        }
        
        // Delete button functionality
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function () {
                const productId = this.getAttribute('data-id');
                document.getElementById('delete-product-id').value = productId;
                document.getElementById('delete-overlay').style.display = 'block';
                document.getElementById('delete-popup').style.display = 'block';
            });
        });

        document.getElementById('cancel-delete').addEventListener('click', function () {
            document.getElementById('delete-overlay').style.display = 'none';
            document.getElementById('delete-popup').style.display = 'none';
        });

        document.getElementById('delete-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('mystore.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('product-' + data.product_id).remove();
                    document.getElementById('delete-overlay').style.display = 'none';
                    document.getElementById('delete-popup').style.display = 'none';
                } else {
                    alert('Error: ' + data.error);
                }
            });
        });
    </script>

    <script src="script.js"></script>
</body>
</html>
