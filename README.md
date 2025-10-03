# Sistem Prediksi Penjualan dengan Multiple Linear Regression

Aplikasi web PHP native yang menggunakan algoritma Multiple Linear Regression untuk memprediksi penjualan berdasarkan data historis. Aplikasi ini dilengkapi dengan fitur import Excel dan visualisasi grafik.

## Fitur Utama

- 📊 **Import Data Excel** - Import data penjualan dari file Excel (.xlsx/.xls)
- 🤖 **Multiple Linear Regression** - Algoritma prediksi penjualan yang akurat
- 📈 **Visualisasi Grafik** - Grafik perbandingan data aktual vs prediksi
- 📋 **Tabel Data** - Tampilan detail data dengan perhitungan akurasi
- 🎨 **Bootstrap UI** - Interface yang responsive dan modern

## Struktur Data

Aplikasi ini menggunakan data dengan kolom:
- **Date** (DATE) - Tanggal transaksi
- **Item Sales** (INT) - Jumlah item terjual
- **Void** (INT) - Jumlah void transaksi
- **Discount Bill** (INT) - Total diskon per bill
- **Discount Item** (INT) - Total diskon per item
- **Amount Redeem** (INT) - Jumlah redeem point
- **Net Sales** (INT) - Penjualan bersih
- **Gross Sales** (INT) - Penjualan kotor
- **Pembayaran DP** (INT) - Jumlah pembayaran DP
- **Omset** (INT) - Target prediksi (variabel dependent)
- **Average Sales** (INT) - Rata-rata penjualan

## Instalasi

### Prerequisites
- PHP 7.4 atau lebih baru
- MySQL 5.7 atau lebih baru
- Composer
- Web server (Apache/Nginx)

### Langkah Instalasi

1. **Clone atau Download Project**
   ```bash
   git clone [repository-url]
   cd sales-prediction-app
   ```

2. **Install Dependencies dengan Composer**
   ```bash
   composer install
   ```

3. **Setup Database**
   - Buat database MySQL baru
   - Import file `database_setup.sql` ke database Anda
   ```bash
   mysql -u root -p sales_prediction < database_setup.sql
   ```

4. **Konfigurasi Database**
   Edit konfigurasi database di file `index.php`:
   ```php
   $host = 'localhost';
   $dbname = 'sales_prediction';
   $username = 'root';
   $password = 'your_password';
   ```

5. **Jalankan Aplikasi**
   - Tempatkan folder project di web server directory (htdocs/www)
   - Akses melalui browser: `http://localhost/sales-prediction-app`

## Cara Penggunaan

### 1. Import Data Excel
- Siapkan file Excel dengan format yang sesuai (11 kolom sesuai struktur data)
- Header harus ada di baris pertama
- Klik "Pilih File Excel" dan upload file
- Klik "Import Data" untuk memproses

### 2. Melihat Prediksi
- Setelah data diimport, sistem akan otomatis menghitung prediksi
- Grafik akan menampilkan perbandingan data aktual vs prediksi
- Tabel akan menunjukkan detail akurasi per data

### 3. Analisis Hasil
- **Grafik Line Chart**: Visualisasi trend aktual vs prediksi
- **Tabel Akurasi**: Menampilkan persentase akurasi setiap prediksi
- **Statistik**: Informasi umum tentang dataset

## Format File Excel

Pastikan file Excel Anda memiliki struktur sebagai berikut:

| A (Date) | B (Item Sales) | C (Void) | D (Discount Bill) | E (Discount Item) | F (Amount Redeem) | G (Net Sales) | H (Gross Sales) | I (Pembayaran DP) | J (Omset) | K (Average Sales) |
|----------|----------------|----------|-------------------|-------------------|-------------------|---------------|-----------------|-------------------|-----------|-------------------|
| 2024-01-01 | 150 | 5 | 10000 | 5000 | 2000 | 280000 | 300000 | 50000 | 320000 | 2133 |

## Teknologi yang Digunakan

- **Backend**: PHP 7.4+ Native
- **Database**: MySQL
- **Frontend**: Bootstrap 5.1.3
- **Charts**: Chart.js
- **Excel Processing**: PhpSpreadsheet
- **Algoritma**: Multiple Linear Regression (implementasi custom)

## Algoritma Multiple Linear Regression

Aplikasi ini mengimplementasikan Multiple Linear Regression dengan:
- **9 variabel independen**: Item Sales, Void, Discount Bill, Discount Item, Amount Redeem, Net Sales, Gross Sales, Pembayaran DP, Average Sales
- **1 variabel dependen**: Omset
- **Metode**: Normal Equation (β = (X'X)⁻¹X'y)
- **Evaluasi**: Mean Absolute Percentage Error (MAPE)

## File Structure

```
sales-prediction-app/
├── index.php              # Main application file
├── composer.json           # Composer dependencies
├── database_setup.sql      # Database schema and sample data
├── README.md              # Documentation
└── vendor/                # Composer dependencies (after install)
```

## Troubleshooting

### Error "Connection failed"
- Periksa konfigurasi database di `index.php`
- Pastikan MySQL service berjalan
- Verifikasi username dan password database

### Error saat Import Excel
- Pastikan file Excel tidak corrupt
- Periksa format tanggal (gunakan format date Excel standar)
- Pastikan semua kolom terisi dengan data numerik (kecuali tanggal)

### Grafik tidak muncul
- Periksa koneksi internet (Chart.js dari CDN)
- Pastikan ada minimal 2 data untuk membuat prediksi
- Check browser console untuk error JavaScript

## Kontribusi

Silakan fork repository ini dan submit pull request untuk kontribusi. Pastikan mengikuti coding standards dan menambahkan dokumentasi yang sesuai.

## Lisensi

MIT License - Silakan gunakan untuk keperluan komersial maupun non-komersial.