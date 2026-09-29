<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wiannora Admin</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f8f3f1;
            color: #332526;
        }

        .sidebar {
            position: fixed;
            width: 240px;
            height: 100vh;
            background: #5f3035;
            color: white;
            padding: 30px 20px;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 40px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 14px 15px;
            margin-bottom: 8px;
            border-radius: 8px;
        }

        .sidebar a:hover {
            background: #7e3e43;
        }

        .main {
            margin-left: 240px;
            padding: 40px;
        }

        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
        }

        .top h1 {
            font-size: 32px;
        }

        .logout {
            background: #a65b61;
            color: white;
            padding: 10px 18px;
            border-radius: 7px;
            text-decoration: none;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
        }

        .card h3 {
            color: #786a6b;
            margin-bottom: 12px;
        }

        .card p {
            font-size: 30px;
            font-weight: bold;
            color: #5f3035;
        }

        @media (max-width: 800px) {
            .sidebar {
                width: 190px;
            }

            .main {
                margin-left: 190px;
                padding: 25px;
            }

            .cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="sidebar">

    <div class="logo">
        Wiannora
    </div>

    <a href="index.php">Dashboard</a>
    <a href="products.php">Products</a>
    <a href="add-product.php">Add Product</a>
    <a href="logout.php">Logout</a>

</div>

<div class="main">

    <div class="top">
        <h1>Admin Dashboard</h1>

        <a href="logout.php" class="logout">
            Logout
        </a>
    </div>

    <div class="cards">

        <div class="card">
            <h3>Total Products</h3>
            <p>0</p>
        </div>

        <div class="card">
            <h3>Total Orders</h3>
            <p>0</p>
        </div>

        <div class="card">
            <h3>Total Customers</h3>
            <p>0</p>
        </div>

    </div>

</div>

</body>
</html>