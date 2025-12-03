<?php
// controllers/ProductController.php
class ProductController {
    private $productModel;

    public function __construct($dbConnection) {
        $this->productModel = new ProductModel($dbConnection);
    }

    /* CREATE ITEM */
    public function createItem() {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];

            // Validasi required
            $required = ['nama_produk', 'kategori', 'merek'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field {$field} harus diisi");
                }
            }

            $productData = [
                'nama_produk' => $data['nama_produk'],
                'kategori'    => $data['kategori'],
                'merek'       => $data['merek'],
                'harga'       => isset($data['harga']) ? (float)$data['harga'] : 0,
                'stok'        => isset($data['stok']) ? (int)$data['stok'] : 0,
                'rating'      => isset($data['rating']) ? (float)$data['rating'] : null,
                'deskripsi'   => $data['deskripsi'] ?? ''
            ];

            $productId = $this->productModel->create($productData);

            if (!$productId) {
                throw new Exception("Gagal membuat produk");
            }

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Produk berhasil dibuat',
                'id' => $productId,
                'data' => $productData
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /* GET ALL ITEMS */
    public function getItems() {
        try {
            $limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;

            $offset = ($page - 1) * $limit;

            $products = $this->productModel->getAll($limit, $offset);
            $total    = $this->productModel->count();

            http_response_code(200);
            echo json_encode([
                'status'   => 'success',
                'products' => $products,
                'total'    => $total,
                'page'     => $page,
                'limit'    => $limit
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /* GET ITEM BY ID */
    public function getItemById($id) {
        try {
            if (!is_numeric($id)) {
                throw new Exception("ID tidak valid");
            }

            $product = $this->productModel->getById($id);

            if (!$product) {
                http_response_code(404);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Produk tidak ditemukan'
                ]);
                return;
            }

            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'product' => $product
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /* UPDATE ITEM */
    public function updateItem($id) {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];

            if (!is_numeric($id)) {
                throw new Exception("ID tidak valid");
            }

            $existing = $this->productModel->getById($id);
            if (!$existing) {
                http_response_code(404);
                echo json_encode([
                    'status' => 'error',
                    'message' => "Produk tidak ditemukan"
                ]);
                return;
            }

            // Only valid fields
            $allowedFields = ['nama_produk', 'kategori', 'merek', 'harga', 'stok', 'rating', 'deskripsi'];
            $updateData = [];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                } else {
                    // keep old value
                    $updateData[$field] = $existing[$field];
                }
            }

            // type casting numeric fields
            $updateData['harga']  = (float)$updateData['harga'];
            $updateData['stok']   = (int)$updateData['stok'];
            $updateData['rating'] = $updateData['rating'] !== null ? (float)$updateData['rating'] : null;

            $this->productModel->update($id, $updateData);

            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Produk berhasil diperbarui',
                'id'      => $id,
                'data'    => $updateData
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /* DELETE ITEM */
    public function deleteItem($id) {
        try {
            if (!is_numeric($id)) {
                throw new Exception("ID tidak valid");
            }

            $existing = $this->productModel->getById($id);
            if (!$existing) {
                throw new Exception("Produk tidak ditemukan");
            }

            $this->productModel->delete($id);

            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Produk berhasil dihapus',
                'id'      => $id
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
?>