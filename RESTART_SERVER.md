# Restart PHP Dev Server dengan Upload Limit 32MB

## Masalah
Default PHP `upload_max_filesize = 2M` — file CSV besar gagal upload dengan error:
```
"The file failed to upload."
```

## Perintah

```bash
# 1. Kill server lama
kill $(lsof -t -i:8000) 2>/dev/null

# 2. Start server baru dengan upload limit 32MB
cd /var/www/awannaweb/public && nohup /usr/bin/php8.3 -d upload_max_filesize=32M -d post_max_size=40M -d memory_limit=256M -S 127.0.0.1:8000 /var/www/awannaweb/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php > /tmp/php-server.log 2>&1 &
```

## Verifikasi
```bash
# Cek server running
curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8000/login
# Harus return 200
```

## Alternatif: Ubah永久 di php.ini
Jika ingin setting permanen (tanpa flag tiap kali start):
```bash
sudo nano /etc/php/8.3/cli/php.ini
```
Cari dan ubah:
```
upload_max_filesize = 32M
post_max_size = 40M
memory_limit = 256M
```
