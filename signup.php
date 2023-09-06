<?php
session_start();
try {
    // Database connection
    $db = new PDO('sqlite:user_data.db');

    // Create the table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS users (
               id INTEGER PRIMARY KEY AUTOINCREMENT,
               username TEXT,
               password TEXT,
               email TEXT,
               type TEXT)");

    // Data from the form
    $type = $_POST['type'];
    $username = $_POST[$type . '-signup-username'];
    $password = $_POST[$type . '-signup-password'];
    $email = $_POST[$type . '-signup-email'];

    // Inserting data into the table
    $query = "INSERT INTO users (username, password, email, type) VALUES (:username, :password, :email, :type)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $password); // Using the hashed password
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':type', $type);

    $result = $stmt->execute();

    if ($result) {
        echo "Account created successfully!";
         // Fetch the last inserted id
         $last_id = $db->lastInsertId();
        
         // Store the ID in the session
         $_SESSION['user_id'] = $last_id;
         $_SESSION['user_type'] = $type; 
         // Redirect based on the type
    if ($type == "vendor") {
        header("Location: vendor-dashboard.html");
    } elseif ($type == "customer") {
        header("Location: customer-dashboard.html");
    }
    } else {
        echo "Failed to create account.";
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
