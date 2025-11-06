# Detail Peran, Menu, dan Fungsi Aplikasi Sistem Penomoran Surat Akreditasi RSPI

## User Admin

### Menu yang Diakses Admin

- **Dashboard**: Melihat statistik pengajuan dokumen, status dokumen, dan rekap aktivitas seluruh pokja.
- **Verifikasi Pengajuan Dokumen**: Melihat daftar pengajuan dokumen dari semua pokja, melakukan verifikasi (setuju/tolak), memberi nomor dokumen secara otomatis jika dokumen di setujui, dan menambahkan catatan admin.
- **Dokumen Sah**: Melihat daftar dokumen yang sudah sah/final, mengelola file final (PDF), dan melakukan pengesahan dokumen.
- **User Pokja**: Mengelola data user pokja: tambah, edit, hapus user pokja.
- **Rekap Dokumen**: Melihat rekap seluruh dokumen yang pernah diajukan, status, dan statistik per pokja.
- **Logout**: Keluar dari aplikasi.

### Fungsi Utama Admin

- melihat daftar pengajuan dokumen.
- mendownload dan memprint dokument yang di ajukan oleh pokja dan diserahkan ke direktur
- Melakukan verifikasi pengajuan dokumen dari pokja.
- Memberi nomor dokumen dan catatan admin pada dokumen yang disetujui.
- Menolak pengajuan dokumen jika tidak sesuai, serta memberi alasan penolakan. dan user pokja  
  dapat melihat alasan penolakan (admin memberikan catatan jika ada yang ingin di sampaikan).
- Melihat dan mengelola dokumen final (PDF) yang diupload pokja.
- Mengelola data user pokja (akses, data, dll).
- Melihat statistik dan rekap seluruh dokumen.

## User Pokja

### Menu yang Diakses Pokja

- **Dashboard**: Melihat statistik pengajuan dokumen miliknya, status dokumen, dan rekap aktivitas.
- **Dokumen Ajuan**: Melihat daftar dokumen yang diajukan, status, dan detail pengajuan.
- **Form Pengajuan Dokumen**: Mengisi form untuk mengajukan dokumen baru (jenis dokumen, judul, tanggal, file draft, catatan).
- **Dokumen Sah**: Melihat dokumen yang sudah disetujui dan sah, serta mengupload file final (PDF) jika diperlukan.
- **Rekap Dokumen**: Melihat rekap seluruh dokumen yang pernah diajukan oleh pokja tersebut.
- **Logout**: Keluar dari aplikasi.

### Fungsi Utama Pokja

- Mengajukan dokumen baru untuk dinomori dan diverifikasi oleh admin dengan cara mengisi form
  pengajuan dokumen (jenis dokumen, judul, tanggal, upload file draft (harus word), catatan) selanjutnya Data tersimpan di tabel `tb_pengajuan_dokumen` dengan status "Menunggu Verifikasi" lalu pokja juga dapat mengirim pesan pengajuan dokumen ke Email admin sekretariat
- Melihat status pengajuan dokumen (menunggu, disetujui, ditolak, selesai).
- Melihat dan membaca catatan admin pada dokumen miliknya.
- Melihat rekap dan statistik dokumen miliknya.

## Alur singkat Pada Sistem

1. **Login**

   - User Pokja dan Admin login melalui halaman login.
   - Hak akses dan menu yang tampil sesuai role (Pokja/Admin).

2. **Pengajuan Dokumen**

   - User Pokja mengisi form pengajuan dokumen (jenis dokumen, judul, tanggal, file draft, catatan).
   - Data tersimpan di tabel `tb_pengajuan_dokumen` dengan status "Menunggu Verifikasi".

3. **Verifikasi Admin**

   - Admin melihat daftar pengajuan dokumen.
   - Admin mendownload dan memprint dokument yang di ajukan oleh pokja dan diserahkan ke direktur
   - Admin dapat menyetujui (jika keputusan direktur di acc), menolak, atau memberi catatan pada pengajuan.
   - Jika disetujui, status menjadi "Disetujui" dan admin dapat mengisi nomor dokumen secara otomatis (berdasarkan jenis dokumen dan kode pokja) serta admin memberikan catatan jika ada yang ingin di sampaikan.
   - Admin akan me
   - Jika ditolak, status menjadi "Ditolak" dan user pokja dapat melihat alasan penolakan (admin memberikan catatan jika ada yang ingin di sampaikan).

4. **Pengesahan & Upload Final**

   - Setelah dokumen disetujui, user pokja dapat mengupload file final (PDF).
   - Status dokumen berubah menjadi "Selesai".

5. **Dashboard & Rekap**
   - User Pokja dan Admin dapat melihat statistik pengajuan, status, dan rekap dokumen.
   - Catatan admin dan nomor dokumen dapat dilihat oleh user pokja pada dokumen miliknya.

## Alur Tabel Database

### Tabel Utama

#### tb_user

| Kolom        | Tipe    | Keterangan                  |
| ------------ | ------- | --------------------------- |
| id_user      | INT     | Primary key, auto increment |
| nama_lengkap | VARCHAR | Nama user                   |
| username     | VARCHAR | Username login              |
| password     | VARCHAR | Password hash               |
| level        | VARCHAR | Role (Admin/Pokja)          |
| kode_pokja   | VARCHAR | Kode unit/pokja             |

#### tb_jenis_dokumen

| Kolom      | Tipe    | Keterangan         |
| ---------- | ------- | ------------------ |
| id_jenis   | INT     | Primary key        |
| nama_jenis | VARCHAR | Nama jenis dokumen |

#### tb_pengajuan_dokumen

| Kolom           | Tipe    | Keterangan                                 |
| --------------- | ------- | ------------------------------------------ |
| id_pengajuan    | INT     | Primary key, auto increment                |
| id_user         | INT     | User pengaju (relasi ke tb_user)           |
| id_jenis        | INT     | Jenis dokumen (relasi ke tb_jenis_dokumen) |
| judul_dokumen   | VARCHAR | Judul dokumen                              |
| file_draft      | VARCHAR | Nama file draft dokumen                    |
| tanggal_dokumen | DATE    | Tanggal dokumen                            |
| tanggal_ajuan   | DATE    | Tanggal pengajuan                          |
| catatan         | TEXT    | Catatan dari user pokja                    |
| status          | VARCHAR | Status pengajuan                           |
| nomor_surat     | VARCHAR | Nomor dokumen (diisi admin)                |
| catatan_admin   | TEXT    | Catatan admin                              |
| file_final      | VARCHAR | File final dokumen (PDF)                   |

### Relasi Tabel

- `tb_pengajuan_dokumen.id_user` → `tb_user.id_user`
- `tb_pengajuan_dokumen.id_jenis` → `tb_jenis_dokumen.id_jenis`

## Catatan

- Setiap user pokja hanya dapat melihat dan mengelola dokumen miliknya.
- Admin dapat mengelola semua pengajuan, memberi nomor dokumen, dan catatan admin.
- Status dokumen: Menunggu Verifikasi, Disetujui, Ditolak, Selesai.
- Jika ada terdapat revisi dengan dokumen yang ada user pokja dapat melakukan pengajuan dokumen
  seperti biasa dengan nomor surat yang baru serta pada judul di wajibkan menulis (Revisi)

---

📞 Support
Untuk pertanyaan atau bantuan:

Email : riyanadityapradanaa@gmail.com
Instagram : @riyanadityapradanaa
TikTok : @daemon.yan

© 2025 IT_RS Pelita Insani - Aplikasi Sistem Penomoran Surat Akreditasi RSPI
