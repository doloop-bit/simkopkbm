anda adalah developer laravel professional, tolong bantu saya merencanakan fitur input penilaian untuk guru, kemudian generate laporan hasil belajar (cetak dan pdf). juga buatkan portal laporan hasil belajar online yang bisa diakses orang tua.

komponen utama pada laporan hasil belajar adalah :

1. Identitas Peserta Didik
2. Deskripsi Capaian Pembelajaran (Informasi yang diberikan terkait 
elemen-elemen Capaian Pembelajaran, yaitu Elemen Nilai Agama dan Budi 
Pekerti, Elemen Jati Diri, dan Elemen Dasar-Dasar Literasi, Matematika, 
Sains, Teknologi, Rekayasa, dan Seni). 
3. Catatan Guru
4. refleksi orang tua (kosong diisi oleh orang tua)
5. kehadiran
6. data BB dan TB
7. Lampiran portofolio (opsional)

pada poin 2 isinya adalah tabel sub elemen capaian pembelajaran per semester. berupa TP (tujuan pembelajaran) contohnya seperti ini:

**CP Nilai Agama dan Budi Pekerti**
1. TP 1: Peserta didik mulai terbiasa mempraktikkan ibadah (doa, salam, ibadah personal) sesuai dengan agama yang dianutnya kolom penilaiannya berupa : 
   - Belum Berkembang (BB)
   - Mulai Berkembang (MB)
   - Berkembang Sesuai Harapan (BSH)
   - Berkembang Sangat Baik (BSB)
2. TP 2: Peserta didik mulai menunjukkan sikap toleransi terhadap teman yang berbeda agama dan kepercayaan
3. dst


**CP Jati Diri**
...
... dst

CP ini adalah data statis 
TP di input per elemen CP, satu CP multi TP
buatkan CRUD untuk TP 
input TP bisa mingguan atau bulanan.

buatkan form input nilai oleh guru untuk tiap TP yang ada dengan 
1. opsi (BB, MB, BSH, BSB), 
2. ada kolom catatan(opsional)
3. foto (opsional)

data TP di load saat guru memilih buat rapor, guru tinggal menambahkan rangkuman catatan/keterangan dan memilih foto dari TP yang sudah diupload

Catatan guru adalah rangkuman naratif dari guru tentang perkembangan peserta didik selama semester tersebut.

kehadiran disamakan dengan rapor kesetaraan, bisa ambil harian atau manual

data bb dan tb mengambil dari data periodik siswa. per semester