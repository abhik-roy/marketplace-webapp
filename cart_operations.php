<?php
session_start();
header('Content-Type: application/json');


/* BACKEND FUNCTIONS THAT HANDLE LOGIC FOR CART AND ORDERS PAGES */

/* Function to connect to the cart and orders database
   If database doesn't exist create a new one*/
function connectDatabase() {
    try {
        $db = new PDO('sqlite:cart_data.db');
        $query = "CREATE TABLE IF NOT EXISTS cart (
            customer_id INTEGER,
            item_id INTEGER,
            quantity INTEGER,
            PRIMARY KEY(customer_id, item_id)
        )";
        $db->exec($query);

        // initialize orders table
        $ordersTableQuery = "CREATE TABLE IF NOT EXISTS orders (
            order_id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_number TEXT,
            customer_id INTEGER,
            item_id INTEGER,
            quantity INTEGER,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        $db->exec($ordersTableQuery);

        return $db;
    } catch (PDOException $e) {
        die("Database Connection Failed: " . $e->getMessage());
    }
}

$conn = connectDatabase();

/* Function to fetch items in active customer's cart
   Used to populate the cart page in the customer dashboard
   Connects to the items db to get details of items in user's cart*/
function fetchCartItems($conn, $customer_id) {
    // get cart items from cart_data.db
    $query = "SELECT * FROM cart WHERE customer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$customer_id]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // error message for when cart is empty
    if (empty($cartItems)) {
        echo json_encode(['message' => 'No items found in cart']);
        return;
    }
    // connect to items database
    $itemConn = new PDO('sqlite:item_data.db');

    // initialize an array to hold the full item details
    $fullItems = [];
    // loop through each cart item to fetch full cart details 
    foreach ($cartItems as $cartItem) {
        $query = "SELECT * FROM items WHERE item_id = ?";
        $stmt = $itemConn->prepare($query);
        $stmt->execute([$cartItem['item_id']]);
        $itemDetail = $stmt->fetch(PDO::FETCH_ASSOC);

        // merge cart item data with full item details
        $fullItem = array_merge($cartItem, $itemDetail);

        $fullItems[] = $fullItem;
    }

    // the full item details as JSON
    echo json_encode($fullItems);
}


/* Function to add items in the home page to active customer user's cart
   Used in the add to cart button logic in the customer dashboard
   Adds a record to the cart table that keeps track of customer_id and item_id*/
function addItemToCart($conn, $customer_id, $item_id, $quantity) {
    // check if the item already exists in the cart for the given customer
    $checkQuery = "SELECT quantity FROM cart WHERE customer_id = ? AND item_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute([$customer_id, $item_id]);
    $existingQuantityRow = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existingQuantityRow) {
        // if item already exists, update the quantity
        $newQuantity = $existingQuantityRow['quantity'] + $quantity;
        $updateQuery = "UPDATE cart SET quantity = ? WHERE customer_id = ? AND item_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([$newQuantity, $customer_id, $item_id]);
    } else {
        // if the item does not exist, insert a new row
        $insertQuery = "INSERT INTO cart (customer_id, item_id, quantity) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->execute([$customer_id, $item_id, $quantity]);
    }

    echo json_encode(['status' => 'Item added to cart']);
}

/* Function to remove item from customer's cart
   Hard deletes the record containing the item_id and active customer_id
   Used in the remove item button in the cart page of the customer dahsboard*/
function removeItemFromCart($conn, $customer_id, $item_id) {
    $query = "DELETE FROM cart WHERE customer_id = ? AND item_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$customer_id, $item_id]);
    echo json_encode(['status' => 'Item removed from cart']);
}


/* Function to implement checkout logic from the cart page
   Creates entries in the orders table keeping track of item, customer and ordernumber
   Removes all entries corresponding to active customer_id from the cart table*/
function placeOrder($conn, $customer_id) {
    // rnadomly generated order number 
    $order_number = rand(1000, 9999);  

    // fetch cart items for the customer with session customerID
    $query = "SELECT * FROM cart WHERE customer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$customer_id]);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cartItems)) {
        echo json_encode(['status' => 'Cart is empty']);
        return;
    }

    // inser all cart items into the orders table once the order is placed
    foreach ($cartItems as $item) {
        $query = "INSERT INTO orders (order_number, customer_id, item_id, quantity) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$order_number, $customer_id, $item['item_id'], $item['quantity']]);
    }

    // empty cart after placing order
    $query = "DELETE FROM cart WHERE customer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$customer_id]);
    
    echo json_encode(['status' => 'Checkout successful']);
}

/* Function to fetch previous orders to populate the your orders page
   Fetches all orders corresponding to active customer_id*/
function fetchPreviousOrders($conn, $customer_id) {
    
    $query = "SELECT * FROM orders WHERE customer_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$customer_id]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // if no previous orders echo message
    if (empty($orderItems)) {
        echo json_encode(['message' => 'No previous orders found']);
        return;
    }

    // items db connection
    $itemConn = new PDO('sqlite:item_data.db');

    // array to hold full order details
    $fullOrders = [];

    // populate the array by looping over items database
    foreach ($orderItems as $orderItem) {
        $query = "SELECT * FROM items WHERE item_id = ?";
        $stmt = $itemConn->prepare($query);
        $stmt->execute([$orderItem['item_id']]);
        $itemDetail = $stmt->fetch(PDO::FETCH_ASSOC);

        // merge order wirh item details
        $fullOrder = array_merge($orderItem, $itemDetail);

        $fullOrders[] = $fullOrder;
    }

    // full order details
    echo json_encode($fullOrders);
}


function fetchAllItems($conn) {
    $itemConn = new PDO('sqlite:item_data.db');
    $query = "SELECT * FROM items";
    $stmt = $itemConn->prepare($query);
    $stmt->execute();
    $allItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($allItems);
}


/* BACKEND LOGIC TO HANDLE GET AND POST REQUESTS FOR CART OPERATIONS */


if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    return;
}

$customer_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$item_id = $data['item_id'] ?? null;
$quantity = $data['quantity'] ?? 1;

// using a switch case block to handle POST and GET requests based on the action value
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $action = $_GET['action'] ?? null;
        if ($action === 'previousOrders') {
            fetchPreviousOrders($conn, $customer_id);
        } else if ($action === 'getAllItems') {
            fetchAllItems($conn);
        } else {
            fetchCartItems($conn, $customer_id);
        }
        break;

    case 'POST':
        $action = $data['action'] ?? null;
        if ($action === 'checkout') {
            placeOrder($conn, $customer_id);
        } elseif ($item_id) {
            addItemToCart($conn, $customer_id, $item_id, $quantity);
        }
        break;

    case 'DELETE':
        if ($item_id) {
            removeItemFromCart($conn, $customer_id, $item_id);
        }
        break;

    case 'PUT':
        if ($item_id) {
            updateCartItem($conn, $customer_id, $item_id, $quantity);
        }
        break;

    default:
        echo json_encode(['error' => 'Invalid request method']);
        break;
}
?>
