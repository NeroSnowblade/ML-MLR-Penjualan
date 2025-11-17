# 📁 Struktur Project Sales Prediction

## Struktur Folder
```
sales_prediction/
│
├── assets/
│   └── style.css                 # Custom CSS styling
│
├── includes/                     # Komponen UI (View Components)
│   ├── navbar.php                # Navbar untuk Multiple Regression
│   ├── navbar_slr.php            # Navbar untuk Single Regression
│   ├── alert.php                 # Alert notifications
│   ├── sidebar_multiple.php      # Sidebar Multiple Regression
│   ├── sidebar_slr.php           # Sidebar Single Regression
│   ├── chart_multiple.php        # Chart container Multiple
│   ├── chart_slr.php             # Chart container Single (placeholder)
│   ├── table_multiple.php        # Data table Multiple Regression
│   ├── table_slr.php             # Data table Single Regression
│   ├── predict_form_multiple.php # Form prediksi Multiple
│   ├── predict_form_slr.php      # Form prediksi Single
│   ├── feature_selector.php      # Selector variabel untuk SLR
│   ├── empty_state.php           # Empty state UI
│   ├── footer.php                # Footer
│   ├── chart_script_multiple.php # JavaScript chart Multiple
│   └── chart_script_slr.php      # JavaScript chart Single
│
├── vendor/                       # Composer dependencies
│
├── config.php                    # Database configuration
├── auth_check.php                # Authentication middleware
├── login.php                     # Login page
├── register.php                  # Registration page
├── logout.php                    # Logout handler
│
├── process_index.php             # Business Logic - Multiple Regression
├── process_slr.php               # Business Logic - Single Regression
│
├── index.php                     # View - Multiple Linear Regression
├── SLR.php                       # View - Single Linear Regression
│
├── composer.json                 # Composer dependencies
└── db.sql                        # Database schema & sample data
```

## 🔐 Sistem Autentikasi

### File Autentikasi:
1. **config.php** - Konfigurasi database
2. **auth_check.php** - Middleware untuk validasi login
3. **login.php** - Halaman login
4. **register.php** - Halaman registrasi
5. **logout.php** - Handler logout

### Tabel Database:
- **users** - Menyimpan data user dengan password hash
- **sales_data** - Menyimpan data penjualan

## 📊 Alur Aplikasi

### 1. Multiple Linear Regression (index.php)
```
User Login → index.php → process_index.php → includes/components
                              ↓
                         Business Logic:
                         - Import Excel
                         - Train Model (9 features)
                         - Calculate Metrics
                         - Make Predictions
```

### 2. Single Linear Regression (SLR.php)
```
User Login → SLR.php → process_slr.php → includes/components
                           ↓
                      Business Logic:
                      - Import Excel
                      - Select Feature
                      - Train Model (1 feature)
                      - Calculate Metrics
                      - Make Predictions
```

## 🎯 Fitur Utama

### Autentikasi
- ✅ Login dengan username/email
- ✅ Register user baru
- ✅ Password hashing (bcrypt)
- ✅ Role management (admin/user)
- ✅ Session management
- ✅ Auto redirect jika belum login

### Multiple Linear Regression
- ✅ Import data dari Excel
- ✅ Training model dengan 9 variabel
- ✅ Visualisasi grafik prediksi vs aktual
- ✅ Metrics: R², RMSE, MAE, MAPE
- ✅ Feature correlation analysis
- ✅ Prediksi data baru

### Single Linear Regression
- ✅ Import data dari Excel
- ✅ Pilih variabel independen
- ✅ Scatter plot dengan regression line
- ✅ Metrics: R², Correlation, RMSE, MAPE
- ✅ Prediksi berdasarkan 1 variabel

## 🔧 Setup & Instalasi

### 1. Install Dependencies
```bash
composer install
```

### 2. Setup Database
```bash
# Import db.sql ke MySQL
mysql -u root -p < db.sql
```

### 3. Konfigurasi
Edit `config.php` sesuai environment Anda:
```php
$host = 'localhost';
$dbname = 'sales_prediction';
$username = 'root';
$password = '';
```

### 4. Default Login
- Username: `admin`
- Password: `admin123`

## 📝 Cara Menggunakan

### Langkah 1: Login
1. Akses `login.php`
2. Masukkan username/email dan password
3. Klik Login

### Langkah 2: Import Data
1. Pilih file Excel (.xlsx atau .xls)
2. Format harus sesuai dengan template (kolom A-K, mulai row 10)
3. Klik "Import Data"

### Langkah 3: Analisis
**Multiple Regression:**
- Data otomatis diproses dengan 9 variabel
- Lihat grafik, metrics, dan tabel prediksi
- Isi form untuk prediksi data baru

**Single Regression:**
- Pilih variabel independen dari dropdown
- Klik "Analisis"
- Lihat scatter plot dan regression line
- Isi nilai variabel untuk prediksi

## 🛠️ Modifikasi & Maintenance

### Menambah Fitur Baru di View
1. Buat file baru di folder `includes/`
2. Include file di `index.php` atau `SLR.php`

### Menambah Business Logic
1. Edit `process_index.php` atau `process_slr.php`
2. Variabel otomatis tersedia di view

### Mengubah Styling
1. Edit `assets/style.css`
2. Gunakan CSS variables yang sudah didefinisikan

### Menambah User Role
1. Edit tabel `users` di database (tambah role baru)
2. Update logic di `auth_check.php`
3. Tambahkan conditional display di navbar

## 🔒 Keamanan

### Implementasi:
- ✅ Password hashing dengan `password_hash()`
- ✅ Session-based authentication
- ✅ SQL prepared statements (prevent SQL injection)
- ✅ File upload validation
- ✅ Input sanitization dengan `htmlspecialchars()`
- ✅ CSRF protection via session

### Best Practices:
- Selalu gunakan `auth_check.php` di setiap protected page
- Jangan simpan password plain text
- Validasi semua input dari user
- Gunakan HTTPS di production

## 📦 Dependencies

### Composer Packages:
- **php-ai/php-ml**: Machine Learning library
- **phpoffice/phpspreadsheet**: Excel file processing

### Frontend Libraries:
- **Bootstrap 5.1.3**: UI Framework
- **Chart.js**: Data visualization

## 🎨 Customization

### Warna Theme:
Edit CSS variables di `assets/style.css`:
```css
:root {
    --primary-dark: #2b4738;
    --secondary-cream: #e3dab1;
    --accent-green: #4a7c59;
    /* ... */
}
```

### Layout:
- Sidebar: `includes/sidebar_*.php`
- Navbar: `includes/navbar*.php`
- Footer: `includes/footer.php`

## 📊 Format Excel

### Kolom yang Diperlukan (A-K):
| Kolom | Field | Type |
|-------|-------|------|
| A | Date | Date |
| B | Item Sales | Integer |
| C | Void | Integer |
| D | Discount Bill | Integer |
| E | Discount Item | Integer |
| F | Amount Redeem | Integer |
| G | Net Sales | Integer |
| H | Gross Sales | Integer |
| I | Pembayaran DP | Integer |
| J | Omset | Integer |
| K | Average Sales | Integer |

**Catatan**: Data mulai dari baris 10 (row 10)

## 🐛 Troubleshooting

### Error: "Connection failed"
- Cek konfigurasi database di `config.php`
- Pastikan MySQL service running
- Pastikan database sudah dibuat

### Error: "Class not found"
- Jalankan `composer install`
- Cek autoload di `vendor/`

### Upload Excel gagal
- Cek permission folder `tmp/`
- Cek format Excel (harus .xlsx atau .xls)
- Pastikan data mulai dari row 10

### Session tidak tersimpan
- Pastikan `session_start()` dipanggil
- Cek permission folder session PHP
- Cek konfigurasi `php.ini`

---

**Version**: 1.0  
**Last Updated**: 2025  
**Tech Stack**: PHP 7.4+, MySQL, Bootstrap 5, Chart.js, PHP-ML