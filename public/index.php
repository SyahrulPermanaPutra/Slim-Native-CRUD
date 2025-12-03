<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Debug: Tampilkan pesan loading
echo "Loading application...<br>";

// Load database configuration
require __DIR__ . '/../config/database.php';

// Setup database
$database = new Database();
$dbConnection = $database->getConnection();

// Check if database connection is successful
if (!$dbConnection) {
    die("❌ Failed to connect to database");
}

echo "✅ Database connected<br>";

// MANUAL INCLUDE CONTROLLER - Test dulu
echo "Loading JmeterTestController manually...<br>";
require __DIR__ . '/../src/Controllers/JmeterTestController.php';
echo "✅ JmeterTestController manually loaded<br>";

$app = AppFactory::create();

// Add middleware
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// Test database connection route
$app->get('/test-db', function (Request $request, Response $response) use ($dbConnection) {
    try {
        $stmt = $dbConnection->query("SELECT 1 as test");
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Database connection test passed',
            'data' => $result
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'message' => 'Database test failed: ' . $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// Simple test route
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write("
        <h1>Slim API with Database ✅</h1>
        <p>Available endpoints:</p>
        <ul>
            <li><a href='/test-db'>GET /test-db</a> - Test database connection</li>
            <li><a href='/products'>GET /products</a> - Lihat semua produk</li>
            <li><a href='/products/1'>GET /products/1</a> - Lihat produk by ID</li>
            <li>POST /products/create - Buat produk baru</li>
            <li>PUT /products/update/1 - Update produk</li> 
            <li>DELETE /products/delete/1 - Hapus produk</li>
        </ul>
    ");
    return $response;
});

// Initialize controller
try {
    echo "Initializing JmeterTestController...<br>";
    $jmeterController = new App\Controllers\JmeterTestController($dbConnection);
    echo "✅ JmeterTestController initialized successfully<br>";
} catch (\Exception $e) {
    die("❌ Failed to initialize JmeterTestController: " . $e->getMessage());
}

// Products CRUD Routes
$app->post('/products/create', [$jmeterController, 'createItem']);
$app->get('/products', [$jmeterController, 'getItems']);
$app->get('/products/{id}', [$jmeterController, 'getItemById']);
$app->put('/products/update/{id}', [$jmeterController, 'updateItem']);
$app->delete('/products/delete/{id}', [$jmeterController, 'deleteItem']);

echo "✅ Routes registered successfully<br>";
echo "🚀 Application ready!<br>";

$app->run();