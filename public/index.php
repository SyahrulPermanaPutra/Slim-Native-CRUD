<?php
// public/index.php
header('Content-Type: application/json');

// Load dependencies
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../controllers/ProductController.php';

// Setup database
$database = new Database();
$dbConnection = $database->getConnection();

// Initialize controller
$productController = new ProductController($dbConnection);

// Get request method and URI
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($uri, '/');

// Simple routing
switch (true) {
    // Test database connection
    case $uri === 'test-db' && $method === 'GET':
        try {
            $stmt = $dbConnection->query("SELECT 1 as test");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Database connection test passed',
                'data' => $result
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Database test failed: ' . $e->getMessage()
            ]);
        }
        break;

    // CREATE Product
    case $uri === 'products/create' && $method === 'POST':
        $productController->createItem();
        break;

    // GET All Products
    case $uri === 'products' && $method === 'GET':
        $productController->getItems();
        break;

    // GET Product by ID
    case preg_match('/^products\/(\d+)$/', $uri, $matches) && $method === 'GET':
        $productController->getItemById($matches[1]);
        break;

    // UPDATE Product
    case preg_match('/^products\/update\/(\d+)$/', $uri, $matches) && $method === 'PUT':
        $productController->updateItem($matches[1]);
        break;

    // DELETE Product
    case preg_match('/^products\/delete\/(\d+)$/', $uri, $matches) && $method === 'DELETE':
        $productController->deleteItem($matches[1]);
        break;

    // Home page
    case $uri === '' || $uri === 'index.php':
        header('Content-Type: text/html');
        echo "
        <h1>PHP Native API with Database ✅</h1>
        <p>Available endpoints:</p>
        <ul>
            <li><a href='/test-db'>GET /test-db</a> - Test database connection</li>
            <li><a href='/products'>GET /products</a> - Lihat semua produk</li>
            <li><a href='/products/1'>GET /products/1</a> - Lihat produk by ID</li>
            <li>POST /products/create - Buat produk baru</li>
            <li>PUT /products/update/1 - Update produk</li> 
            <li>DELETE /products/delete/1 - Hapus produk</li>
        </ul>
        ";
        break;

    // 404 Not Found
    default:
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'message' => 'Endpoint not found'
        ]);
        break;
}
?>