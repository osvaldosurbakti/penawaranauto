<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Penawaran</title>
</head>

<body>
    <h2>Riwayat Penawaran Anda</h2>

    <?php if (!empty($penawaran)): ?>
        <table border="1">
            <thead>
                <tr>
                    <th>Nomor Surat</th>
                    <th>Nama Tertanggung</th>
                    <th>Jenis Mobil</th>
                    <th>Harga Mobil</th>
                    <th>Tahun Mobil</th>
                    <th>File PDF</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($penawaran as $pnw): ?>
                    <tr>
                        <td><?= $pnw['nomor_surat']; ?></td>
                        <td><?= $pnw['nama_tertanggung']; ?></td>
                        <td><?= $pnw['jenis_mobil']; ?></td>
                        <td><?= number_format($pnw['harga_mobil'], 2, ',', '.'); ?></td>
                        <td><?= $pnw['tahun_mobil']; ?></td>
                        <td>
                            <!-- Link ke file PDF -->
                            <a href="<?= base_url($pnw['file_pdf']); ?>" target="_blank">Lihat PDF</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Tidak ada penawaran yang ditemukan.</p>
    <?php endif; ?>

    <a href="/home">Kembali ke Beranda</a>
</body>

</html>