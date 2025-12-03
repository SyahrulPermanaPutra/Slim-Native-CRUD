<?php
// seed.php 

// Konfigurasi database
$host = 'localhost';
$dbname = 'slim_and_native';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Koneksi database berhasil...\n";
    
    // Data dummy
    $kategori = ['Smartphone', 'Laptop', 'Tablet', 'Smartwatch', 'Headphone', 'Speaker', 'Camera', 'TV', 'Monitor', 'Printer'];
    $merek = ['Samsung', 'Apple', 'Xiaomi', 'Sony', 'LG', 'Asus', 'Dell', 'HP', 'Canon', 'Nikon', 'JBL', 'Bose'];
    
    // Hapus data lama jika ada
    $pdo->exec("TRUNCATE TABLE products");
    echo "Data lama dihapus...\n";
    
    // Generate 1000 data
    for ($i = 1; $i <= 1000; $i++) {
        $kategori_random = $kategori[array_rand($kategori)];
        $merek_random = $merek[array_rand($merek)];
        
        $data = [
            ':nama_produk' => $merek_random . ' ' . $kategori_random . ' ',
            ':kategori' => $kategori_random,
            ':merek' => $merek_random,
            ':harga' => rand(500000, 25000000),
            ':stok' => rand(0, 100),
            ':rating' => round(rand(30, 50) / 10, 1), // Rating 3.0 - 5.0
            ':deskripsi' => 'Ini adalah deskripsi untuk ' . $merek_random . ' ' . $kategori_random . ' model ' . $i
        ];
        
        $sql = "INSERT INTO products (nama_produk, kategori, merek, harga, stok, rating, deskripsi) 
                VALUES (:nama_produk, :kategori, :merek, :harga, :stok, :rating, :deskripsi)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        
        if ($i % 100 == 0) {
            echo "Data ke-$i berhasil ditambahkan...\n";
        }
    }
    
    echo "\n✅ SUKSES! 1000 data berhasil ditambahkan ke database!\n";
    
    // Tampilkan statistik
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products");
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT kategori, COUNT(*) as jumlah FROM products GROUP BY kategori ORDER BY jumlah DESC");
    $kategori_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n=== STATISTIK DATA ===\n";
    echo "Total produk: " . $total['total'] . "\n";
    echo "\nPer Kategori:\n";
    foreach ($kategori_stats as $stat) {
        echo "  " . str_pad($stat['kategori'], 15) . ": " . $stat['jumlah'] . " produk\n";
    }
    
    // Statistik tambahan
    $stmt = $pdo->query("SELECT merek, COUNT(*) as jumlah FROM products GROUP BY merek ORDER BY jumlah DESC");
    $merek_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nPer Merek:\n";
    foreach ($merek_stats as $stat) {
        echo "  " . str_pad($stat['merek'], 15) . ": " . $stat['jumlah'] . " produk\n";
    }
    
    // Harga tertinggi dan terendah
    $stmt = $pdo->query("SELECT MIN(harga) as min_harga, MAX(harga) as max_harga, AVG(harga) as avg_harga FROM products");
    $harga_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nStatistik Harga:\n";
    echo "  Harga Terendah  : Rp " . number_format($harga_stats['min_harga'], 0, ',', '.') . "\n";
    echo "  Harga Tertinggi : Rp " . number_format($harga_stats['max_harga'], 0, ',', '.') . "\n";
    echo "  Harga Rata-rata : Rp " . number_format($harga_stats['avg_harga'], 0, ',', '.') . "\n";
    
    echo "\n🎉 Seeder selesai dijalankan!\n";
    
} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
}
?>