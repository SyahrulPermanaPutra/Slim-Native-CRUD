<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\ProductModel;

class JmeterTestController
{
    private $productModel;

    public function __construct($dbConnection) {
        $this->productModel = new ProductModel($dbConnection);
    }

    /* ================================
       CREATE ITEM
    =================================*/
    public function createItem(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody() ?? [];

            // Validasi required
            $required = ['nama_produk', 'kategori', 'merek'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $response->getBody()->write(json_encode([
                        'status' => 'error',
                        'message' => "Field {$field} harus diisi"
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')
                                   ->withStatus(400);
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
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => "Gagal membuat produk"
                ]));
                return $response->withHeader('Content-Type', 'application/json')
                               ->withStatus(500);
            }

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => 'Produk berhasil dibuat',
                'id' => $productId,
                'data' => $productData
            ]));

            return $response->withHeader('Content-Type', 'application/json')
                           ->withStatus(201);

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')
                           ->withStatus(500);
        }
    }

    /* ================================
       GET ALL ITEMS
    =================================*/
    public function getItems(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $limit  = isset($params['limit']) ? (int)$params['limit'] : 10;
            $page   = isset($params['page']) ? (int)$params['page'] : 1;

            // Validasi limit dan page
            if ($limit < 1 || $limit > 100) {
                $limit = 10;
            }
            if ($page < 1) {
                $page = 1;
            }

            $offset = ($page - 1) * $limit;

            $products = $this->productModel->getAll($limit, $offset);
            $total    = $this->productModel->count();

            $response->getBody()->write(json_encode([
                'status'   => 'success',
                'products' => $products,
                'total'    => $total,
                'page'     => $page,
                'limit'    => $limit,
                'total_pages' => ceil($total / $limit)
            ]));

            return $response->withHeader('Content-Type', 'application/json')
                           ->withStatus(200);

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')
                           ->withStatus(500);
        }
    }

    /* ================================
       GET ITEM BY ID
    =================================*/
    public function getItemById(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;

            if (!is_numeric($id) || $id < 1) {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => 'ID tidak valid'
                ]));
                return $response->withHeader('Content-Type', 'application/json')
                               ->withStatus(400);
            }

            $product = $this->productModel->getById((int)$id);

            if (!$product) {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => 'Produk tidak ditemukan'
                ]));
                return $response->withHeader('Content-Type', 'application/json')
                               ->withStatus(404);
            }

            $response->getBody()->write(json_encode([
                'status'  => 'success',
                'product' => $product
            ]));

            return $response->withHeader('Content-Type', 'application/json')
                           ->withStatus(200);

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')
                           ->withStatus(500);
        }
    }

    /* ================================
       UPDATE ITEM
    =================================*/
    public function updateItem(Request $request, Response $response, array $args): Response
    {
        try {
            $id = $args['id'] ?? null;
            $data = $request->getParsedBody() ?? [];

            if (!is_numeric($id) || $id < 1) {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => 'ID tidak valid'
                ]));
                return $response->withHeader('Content-Type', 'application/json')
                               ->withStatus(400);
            }

            $existing = $this->productModel->getById((int)$id);
            if (!$existing) {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => "Produk tidak ditemukan"
                ]));
                return $response->withHeader('Content-Type', 'application/json')
                               ->withStatus(404);
            }

            // Validasi data tidak kosong
            if (empty($data)) {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => 'Data update tidak boleh kosong'
                ]));
                return $response->withHeader('Content-Type', 'application/json')
                               ->withStatus(400);
            }

            // Only valid fields
            $allowedFields = ['nama_produk', 'kategori', 'merek', 'harga', 'stok', 'rating', 'deskripsi'];
            $updateData = [];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            // Jika tidak ada field yang valid untuk diupdate
            if (empty($updateData)) {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => 'Tidak ada data yang valid untuk diupdate'
                ]));
                return $response->withHeader('Content-Type', 'application/json')
                               ->withStatus(400);
            }

            // Type casting numeric fields
            if (isset($updateData['harga'])) {
                $updateData['harga'] = (float)$updateData['harga'];
            }
            if (isset($updateData['stok'])) {
                $updateData['stok'] = (int)$updateData['stok'];
            }
            if (isset($updateData['rating'])) {
                $updateData['rating'] = $updateData['rating'] !== null ? (float)$updateData['rating'] : null;
            }

            $success = $this->productModel->update((int)$id, $updateData);

            if (!$success) {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => "Gagal memperbarui produk"
                ]));
                return $response->withHeader('Content-Type', 'application/json')
                               ->withStatus(500);
            }

            $response->getBody()->write(json_encode([
                'status'  => 'success',
                'message' => 'Produk berhasil diperbarui',
                'id'      => $id,
                'data'    => $updateData
            ]));

            return $response->withHeader('Content-Type', 'application/json')
                           ->withStatus(200);

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')
                           ->withStatus(500);
        }
    }

    /* ================================
       DELETE ITEM
    =================================*/
    public function deleteItem(Request $request, Response $response, array $args): Response
    {
         try {
        $id = $args['id'] ?? null;

        if (!is_numeric($id) || $id < 1) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'ID tidak valid'
            ]));
            return $response->withHeader('Content-Type', 'application/json')
                           ->withStatus(400);
        }

        $existing = $this->productModel->getById((int)$id);
        if (!$existing) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Produk tidak ditemukan'
            ]));
            return $response->withHeader('Content-Type', 'application/json')
                           ->withStatus(404);
        }

        $success = $this->productModel->delete((int)$id);

        if (!$success) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => "Gagal menghapus produk"
            ]));
            return $response->withHeader('Content-Type', 'application/json')
                           ->withStatus(500);
        }

        $response->getBody()->write(json_encode([
            'status'  => 'success',
            'message' => 'Produk berhasil dihapus',
            'id'      => $id
        ]));

        return $response->withHeader('Content-Type', 'application/json')
                       ->withStatus(200);

    } catch (\Exception $e) {
        $response->getBody()->write(json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]));
        return $response->withHeader('Content-Type', 'application/json')
                       ->withStatus(500);
        }
    }
}