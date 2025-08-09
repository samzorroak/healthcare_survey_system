<?php
session_start();
include '../config.php'; // Database connection

// If the user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey System</title>
    
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="dist/css/adminlte.min2.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <!-- jQuery Script -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    

    <!-- Custom CSS -->
    <style>
        .dashboard-card {
            margin-bottom: 20px;
        }

        .dashboard-card .card-header {
            background-color: #f4f6f9;
            font-weight: bold;
        }

        .card-footer {
            background-color: #f9f9f9;
        }

        .card-body p {
            font-size: 16px;
            line-height: 1.5;
        }
    </style>
</head>