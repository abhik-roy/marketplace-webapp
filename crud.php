<?php
session_start();

/* BACKEND FUNCTIONS THAT HANDLE LOGIC FOR VENDOR SIDE ITEM CRUD */

// function to connect to the database
function connectDatabase() {
    try {
        $db = new PDO('sqlite:item_data.db');
        $query = "CREATE TABLE IF NOT EXISTS items (
            item_id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            price REAL NOT NULL,
            image TEXT,
            stock INTEGER NOT NULL,
            vendor_id INTEGER NOT NULL,
            deleted INTEGER DEFAULT 0
        )";
        $db->exec($query);
        return $db;
    } catch (PDOException $e) {
        die("Database Connection Failed: " . $e->getMessage());
    }
}


function throwError($message) {
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit();
}


// Function to create a new item
function createItem($db, $vendor_id, $postData) {
    $name = $postData['name'];
    $description = $postData['description'];
    $price = $postData['price'];
    $stock = $postData['stock'];
    $targetDir = "uploads/";
    $targetFile = $targetDir . basename($_FILES["image"]["name"]);
    move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile);

    $query = "INSERT INTO items (name, description, price, image, stock, vendor_id) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$name, $description, $price, $targetFile, $stock, $vendor_id]);

    header("Location: vendor-dashboard.html");
    exit();
}

// Function to read items
function readItems($db, $vendor_id) {
    
    $query = "SELECT * FROM items WHERE vendor_id = ? AND deleted = 0";
    $stmt = $db->prepare($query);
    $stmt->execute([$vendor_id]);
    

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($items) {
        echo json_encode($items);
    } else {
        echo json_encode(['message' => 'No items found']);
    }
}

// Function to get a single item
function getItem($db, $item_id) {
    $query = "SELECT * FROM items WHERE item_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        echo json_encode($item);
    } else {
        echo json_encode(['message' => 'Item not found']);
    }
}


// Function to update an existing item
function updateItem($db, $postData) {
    $item_id = $postData['item_id'];
    $name = $postData['name'];
    $description = $postData['description'];
    $price = $postData['price'];
    $stock = $postData['stock'];

    $targetDir = "uploads/";
    $targetFile = $targetDir . basename($_FILES["image"]["name"]);
    
    // Check if a new image is uploaded
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
        // Update all fields including image
        $query = "UPDATE items SET name = ?, description = ?, price = ?, stock = ?, image = ? WHERE item_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $description, $price, $stock, $targetFile, $item_id]);
    } else {
        // Update all fields except image
        $query = "UPDATE items SET name = ?, description = ?, price = ?, stock = ? WHERE item_id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$name, $description, $price, $stock, $item_id]);
    }

    header("Location: vendor-dashboard.html");
    exit();
}

function deleteItem($db, $item_id) {
    $query = "UPDATE items SET deleted = 1 WHERE item_id = ?";
    $stmt = $db->prepare($query);
    $result = $stmt->execute([$item_id]);
    if ($result) {
        echo json_encode(['status' => 'success']);
    } else {
        throwError('Failed to delete item');
    }
}



// Function to read all items
function getAllItems($db, $searchQuery = null) {
    if ($searchQuery) {
        $query = "SELECT * FROM items WHERE name LIKE ? AND deleted = 0";
        $stmt = $db->prepare($query);
        $stmt->execute(["%$searchQuery%"]);
    } else {
        $query = "SELECT * FROM items WHERE deleted = 0";
        $stmt = $db->prepare($query);
        $stmt->execute();
    }

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($items) {
        echo json_encode($items);
    } else {
        echo json_encode(['message' => 'No items found']);
    }
}



/* BACKEND LOGIC TO HANDLE GET AND POST REQUESTS FOR VENDOR SIDE OPERATIONS */

//get the vendor_id which will be used to keep track of the vendor's posted items for sal
if (!isset($_SESSION['user_id'])) {
    throwError('Unauthorized: No user ID found.');
}

$vendor_id = $_SESSION['user_id'];
$db = connectDatabase();

$action = $_REQUEST['action'] ?? null;

// using a switch case block to handle POST and GET requests based on the action value
switch ($action) {
    case 'create':
        createItem($db, $vendor_id, $_POST);
        break;

    case 'read':
        readItems($db, $vendor_id);
        break;
    case 'getItem':
        $item_id = $_GET['item_id'] ?? null;
        if ($item_id) {
            getItem($db, $item_id);
        } else {
            throwError('Invalid item ID');
        }
        break;
    case 'update':
        updateItem($db, $_POST);
        break;
    case 'delete':
        $item_id = $_POST['item_id'] ?? null;
        if ($item_id) {
            deleteItem($db, $item_id);
        } else {
            throwError('Invalid item ID');
        }
        break;
    case'getAllItems':
        $searchQuery = isset($_GET['query']) ? $_GET['query'] : null;
        getAllItems($db, $searchQuery);
        break;
          

    default:
        throwError('Invalid action');
        break;
}
?>
