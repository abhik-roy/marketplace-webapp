<?php
session_start();
try {
    // Database connection
    $db = new PDO('sqlite:user_data.db');

    // Data from the form
    $type = $_POST['type'];
    $username = $_POST[$type . '-username'];
    $password = $_POST[$type . '-password'];

    // Fetching the user from the database
    $query = "SELECT * FROM users WHERE username = :username AND type = :type";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':type', $type);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // If the user exists and the password is correct
    if ($user && $password === $user['password']) {
        // Login successful.
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = $type; 

    // Redirect based on the type
        if ($type == "vendor") {
            header("Location: vendor-dashboard.html");
        } elseif ($type == "customer") {
            header("Location: customer-dashboard.html");
        }

    exit();

    } 
        
    else {
        // Login failed
        echo "Invalid username or password.";
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
