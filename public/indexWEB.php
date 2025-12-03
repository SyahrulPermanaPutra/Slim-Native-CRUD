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

// Initialize controller with database connection
try {
    echo "Loading JmeterTestController...<br>";
    // Pastikan class JmeterTestController ada di src/Controllers/JmeterTestController.php
    $jmeterController = new App\Controllers\JmeterTestController($dbConnection);
    echo " JmeterTestController loaded successfully<br>";
} catch (\Exception $e) {
    echo "⚠️ JmeterTestController not available: " . $e->getMessage() . "<br>";
    $jmeterController = null;
}

// Simple test route
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write("
        <!DOCTYPE html>
        <html>
        <head>
            <title>Slim API</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
        </head>
        <body>
            <div class='container mt-5'>
                <h1>🚀 Slim API with Database ✅</h1>
                <p>All systems are working properly!</p>
                
                <div class='row mt-4'>
                    <div class='col-md-6'>
                        <div class='card'>
                            <div class='card-header'>
                                <h5>API Testing</h5>
                            </div>
                            <div class='card-body'>
                                <p>Test with Postman:</p>
                                <ul>
                                    <li>POST /products/create</li>
                                    <li>GET /products</li>
                                    <li>GET /products/{id}</li>
                                    <li>PUT /products/update/{id}</li>
                                    <li>DELETE /products/delete/{id}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class='col-md-6'>
                        <div class='card'>
                            <div class='card-header'>
                                <h5>Web Interface</h5>
                            </div>
                            <div class='card-body'>
                                <p>Test CRUD operations in browser:</p>
                                <a href='/web/products' class='btn btn-primary'>Go to Product Management</a>
                                <br><br>
                                <a href='/test-db' class='btn btn-secondary'>Test Database Connection</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class='mt-4'>
                    <div class='card'>
                        <div class='card-header'>
                            <h5>JMeter Testing Ready</h5>
                        </div>
                        <div class='card-body'>
                            <p>All CRUD endpoints are working and ready for JMeter performance testing.</p>
                            <p><strong>5 Metrics to measure:</strong></p>
                            <ol>
                                <li>Response Time</li>
                                <li>Throughput</li>
                                <li>Error Rate</li>
                                <li>CPU Usage</li>
                                <li>Memory Usage</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
    ");
    return $response;
});

// Basic CRUD Routes untuk Products
$app->get('/products', function (Request $request, Response $response) use ($dbConnection) {
    try {
        $stmt = $dbConnection->query("SELECT * FROM products ORDER BY id DESC LIMIT 100");
        $products = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'products' => $products
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'message' => 'Failed to get products: ' . $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->get('/products/{id}', function (Request $request, Response $response, $args) use ($dbConnection) {
    try {
        $id = $args['id'];
        $stmt = $dbConnection->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($product) {
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'product' => $product
            ]));
        } else {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Product not found'
            ]));
            return $response->withStatus(404);
        }
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'message' => 'Failed to get product: ' . $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->post('/products/create', function (Request $request, Response $response) use ($dbConnection) {
    try {
        $data = $request->getParsedBody();
        
        // Validasi input
        if (empty($data['nama_produk']) || empty($data['kategori']) || empty($data['merek']) || empty($data['harga'])) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'All fields are required'
            ]));
            return $response->withStatus(400);
        }
        
        $stmt = $dbConnection->prepare("
            INSERT INTO products (nama_produk, kategori, merek, harga, stok, rating, deskripsi) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $data['nama_produk'],
            $data['kategori'],
            $data['merek'],
            $data['harga'],
            $data['stok'] ?? 0,
            $data['rating'] ?? 4.0,
            $data['deskripsi'] ?? 'No description'
        ]);
        
        $productId = $dbConnection->lastInsertId();
        
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Product created successfully',
            'product_id' => $productId
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'message' => 'Failed to create product: ' . $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->put('/products/update/{id}', function (Request $request, Response $response, $args) use ($dbConnection) {
    try {
        $id = $args['id'];
        $data = $request->getParsedBody();
        
        // Cek apakah product exists
        $checkStmt = $dbConnection->prepare("SELECT id FROM products WHERE id = ?");
        $checkStmt->execute([$id]);
        
        if (!$checkStmt->fetch()) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Product not found'
            ]));
            return $response->withStatus(404);
        }
        
        // Build dynamic update query
        $fields = [];
        $values = [];
        
        $allowedFields = ['nama_produk', 'kategori', 'merek', 'harga', 'stok', 'rating', 'deskripsi'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'No fields to update'
            ]));
            return $response->withStatus(400);
        }
        
        $values[] = $id; // Untuk WHERE clause
        
        $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $dbConnection->prepare($sql);
        $stmt->execute($values);
        
        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Product updated successfully'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'message' => 'Failed to update product: ' . $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->delete('/products/delete/{id}', function (Request $request, Response $response, $args) use ($dbConnection) {
    try {
        $id = $args['id'];
        
        $stmt = $dbConnection->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => 'Product deleted successfully'
            ]));
        } else {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Product not found'
            ]));
            return $response->withStatus(404);
        }
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'message' => 'Failed to delete product: ' . $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// Helper function untuk pagination
function generatePagination($currentPage, $totalPages) {
    if ($totalPages <= 1) return '';
    
    $html = '<nav><ul class="pagination">';
    
    // Previous button
    if ($currentPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="?page=' . ($currentPage - 1) . '">Previous</a></li>';
    }
    
    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i == $currentPage ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="?page=' . ($currentPage + 1) . '">Next</a></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}

// Web Interface untuk Testing CRUD dengan Pagination
$app->get('/web/products', function (Request $request, Response $response) use ($dbConnection) {
    try {
        $queryParams = $request->getQueryParams();
        $currentPage = max(1, (int)($queryParams['page'] ?? 1));
        $limit = 20;
        $offset = ($currentPage - 1) * $limit;
        
        // Get total count
        $countStmt = $dbConnection->query("SELECT COUNT(*) as total FROM products");
        $totalProducts = $countStmt->fetch(\PDO::FETCH_ASSOC)['total'];
        $totalPages = ceil($totalProducts / $limit);
        
        // Get products dengan pagination
        $stmt = $dbConnection->prepare("SELECT * FROM products ORDER BY id DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Product Management</title>
            <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
            <style>
                .container { margin-top: 20px; }
                .product-card { 
                    margin-bottom: 10px; 
                    padding: 12px; 
                    border: 1px solid #dee2e6; 
                    border-radius: 5px;
                    background: #f8f9fa;
                }
                .pagination { margin-top: 20px; }
                .stats { background: #e9ecef; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h1>📦 Product Management</h1>
                
                <!-- Statistics -->
                <div class='stats'>
                    <strong>Total Products:</strong> {$totalProducts} | 
                    <strong>Page:</strong> {$currentPage}/{$totalPages} | 
                    <strong>Showing:</strong> " . count($products) . " products
                </div>
                
                <!-- Create Product Form -->
                <div class='card mb-4'>
                    <div class='card-header bg-success text-white'>
                        <h5>➕ Create New Product</h5>
                    </div>
                    <div class='card-body'>
                        <form id='createForm'>
                            <div class='row g-2'>
                                <div class='col-md-2'>
                                    <input type='text' name='nama_produk' class='form-control' placeholder='Nama Produk' required>
                                </div>
                                <div class='col-md-2'>
                                    <input type='text' name='kategori' class='form-control' placeholder='Kategori' required>
                                </div>
                                <div class='col-md-2'>
                                    <input type='text' name='merek' class='form-control' placeholder='Merek' required>
                                </div>
                                <div class='col-md-2'>
                                    <input type='number' name='harga' class='form-control' placeholder='Harga' required>
                                </div>
                                <div class='col-md-1'>
                                    <input type='number' name='stok' class='form-control' placeholder='Stok' required>
                                </div>
                                <div class='col-md-2'>
                                    <input type='number' name='rating' class='form-control' placeholder='Rating' step='0.1' min='1' max='5'>
                                </div>
                                <div class='col-md-1'>
                                    <button type='submit' class='btn btn-success w-100'>Create</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Products List -->
                <div class='card'>
                    <div class='card-header bg-primary text-white'>
                        <h5>📋 Products List ({$totalProducts} total products)</h5>
                    </div>
                    <div class='card-body'>
                        <!-- Pagination Top -->
                        " . generatePagination($currentPage, $totalPages) . "
                        
                        <div id='productsList'>";
        
        if (count($products) > 0) {
            foreach ($products as $product) {
                $createdAt = isset($product['created_at']) ? date('d M Y H:i', strtotime($product['created_at'])) : 'N/A';
                $html .= "
                <div class='product-card'>
                    <div class='row align-items-center'>
                        <div class='col-md-2'><strong>{$product['nama_produk']}</strong></div>
                        <div class='col-md-1'><span class='badge bg-secondary'>{$product['kategori']}</span></div>
                        <div class='col-md-1'><small>{$product['merek']}</small></div>
                        <div class='col-md-2'>💰 Rp " . number_format($product['harga'], 0, ',', '.') . "</div>
                        <div class='col-md-1'>📦 {$product['stok']} pcs</div>
                        <div class='col-md-1'>⭐ " . ($product['rating'] ?? 'N/A') . "</div>
                        <div class='col-md-2'><small>Created: {$createdAt}</small></div>
                        <div class='col-md-2'>
                            <button class='btn btn-warning btn-sm' onclick='updateProduct({$product['id']})'>✏️ Update</button>
                            <button class='btn btn-danger btn-sm' onclick='deleteProduct({$product['id']})'>🗑️ Delete</button>
                        </div>
                    </div>
                </div>";
            }
        } else {
            $html .= "
                <div class='text-center py-4'>
                    <h4>😴 No products found</h4>
                    <p>Create your first product using the form above!</p>
                </div>";
        }
        
        $html .= "
                        </div>
                        
                        <!-- Pagination Bottom -->
                        " . generatePagination($currentPage, $totalPages) . "
                    </div>
                </div>
            </div>

            <script>
                // Handle Create Form
                document.getElementById('createForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const data = Object.fromEntries(formData);
                    data.rating = data.rating || 4.0;
                    
                    fetch('/products/create', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert('✅ Product created successfully! ID: ' + data.product_id);
                            location.reload();
                        } else {
                            alert('❌ Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('❌ Error creating product: ' + error);
                    });
                });

                // Delete Product
                function deleteProduct(id) {
                    if (confirm('Are you sure you want to delete product ID ' + id + '?')) {
                        fetch('/products/delete/' + id, {
                            method: 'DELETE'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                alert('✅ Product deleted successfully!');
                                location.reload();
                            } else {
                                alert('❌ Error: ' + data.message);
                            }
                        })
                        .catch(error => {
                            alert('❌ Error deleting product: ' + error);
                        });
                    }
                }

                // Update Product
                function updateProduct(id) {
                    const newName = prompt('Enter new product name:');
                    const newPrice = prompt('Enter new price:');
                    
                    if (newName && newPrice) {
                        fetch('/products/update/' + id, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                nama_produk: newName,
                                harga: parseInt(newPrice)
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                alert('✅ Product updated successfully!');
                                location.reload();
                            } else {
                                alert('❌ Error: ' + data.message);
                            }
                        })
                        .catch(error => {
                            alert('❌ Error updating product: ' + error);
                        });
                    }
                }
            </script>
        </body>
        </html>";
        
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    } catch (\Exception $e) {
        $response->getBody()->write("Error: " . $e->getMessage());
        return $response->withStatus(500);
    }
});

echo "✅ Routes registered successfully<br>";

$app->run();