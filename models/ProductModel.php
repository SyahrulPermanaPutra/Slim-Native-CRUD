<?php
// models/ProductModel.php
class ProductModel {
    private $conn;
    private $table = 'products';

    public function __construct($db) {
        $this->conn = $db;
    }

    /* CREATE / INSERT */
    public function create($data) {
        $query = "INSERT INTO {$this->table}
            (nama_produk, kategori, merek, harga, stok, rating, deskripsi)
            VALUES (:nama_produk, :kategori, :merek, :harga, :stok, :rating, :deskripsi)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(':nama_produk', $data['nama_produk']);
        $stmt->bindValue(':kategori', $data['kategori']);
        $stmt->bindValue(':merek', $data['merek']);
        $stmt->bindValue(':harga', $data['harga']);
        $stmt->bindValue(':stok', $data['stok'], PDO::PARAM_INT);

        if ($data['rating'] === null) {
            $stmt->bindValue(':rating', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':rating', $data['rating']);
        }

        $stmt->bindValue(':deskripsi', $data['deskripsi']);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /* GET ALL */
    public function getAll($limit = 50, $offset = 0) {
        $query = "SELECT * FROM {$this->table}
                  ORDER BY created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* GET BY ID */
    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* UPDATE */
    public function update($id, $data) {
        try {
            $query = "UPDATE {$this->table}
                      SET nama_produk = :nama_produk,
                          kategori = :kategori,
                          merek = :merek,
                          harga = :harga,
                          stok = :stok,
                          rating = :rating,
                          deskripsi = :deskripsi
                      WHERE id = :id";

            $stmt = $this->conn->prepare($query);

            $stmt->bindValue(':nama_produk', $data['nama_produk']);
            $stmt->bindValue(':kategori', $data['kategori']);
            $stmt->bindValue(':merek', $data['merek']);
            $stmt->bindValue(':harga', $data['harga']);
            $stmt->bindValue(':stok', $data['stok'], PDO::PARAM_INT);

            if ($data['rating'] === null) {
                $stmt->bindValue(':rating', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':rating', $data['rating']);
            }

            $stmt->bindValue(':deskripsi', $data['deskripsi']);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);

            $stmt->execute();
            return true;

        } catch (PDOException $e) {
            error_log("PDO Error in update: " . $e->getMessage());
            return false;
        }
    }

    /* DELETE */
    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /* COUNT ROWS */
    public function count() {
        $query = "SELECT COUNT(*) AS total FROM {$this->table}";
        $stmt = $this->conn->prepare($query);

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)$row['total'];
    }
}
?>