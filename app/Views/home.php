<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <script>
        function toggleAuthorizedInput(checkbox) {
            const authorizedInput = document.getElementById('bengkel_authorized_value');
            authorizedInput.disabled = !checkbox.checked;
            if (!checkbox.checked) {
                authorizedInput.value = '';
            }
        }

        function togglePADriverInput(checkbox) {
            const paDriverInput = document.getElementById('pa_driver_value');
            paDriverInput.disabled = !checkbox.checked;
            if (!checkbox.checked) {
                paDriverInput.value = '';
            }
        }

        function togglePAPassengerInput(checkbox) {
            const paPassengerInput = document.getElementById('pa_passenger_value');
            paPassengerInput.disabled = !checkbox.checked;
            if (!checkbox.checked) {
                paPassengerInput.value = '';
            }
        }

        function toggleTPLInput(checkbox) {
            const tplInput = document.getElementById('tpl_value');
            tplInput.disabled = !checkbox.checked;
            if (!checkbox.checked) {
                tplInput.value = '';
            }
        }
    </script>
    <!-- Tombol untuk melihat riwayat penawaran -->
    <a href="/history">
        <button>Riwayat Penawaran</button>
    </a>

</head>

<body>
    <h2>Selamat datang, <?= session('marketing_name'); ?>!</h2>
    <p>Nama Marketing: <?= session('marketing_name'); ?></p>
    <p>Cabang: <?= session('branch'); ?></p>

    <h3>Penawaran Auto</h3>
    <form method="POST" action="/home/generatePdf">

        <!-- Input nama tertanggung -->
        <label for="nama_tertanggung">Nama Tertanggung</label>
        <input type="text" name="nama_tertanggung" id="nama_tertanggung" required>

        <!-- Input alamat tertanggung -->
        <label for="alamat_tertanggung">Alamat Tertanggung</label>
        <input type="text" name="alamat_tertanggung" id="alamat_tertanggung" required>

        <!-- Pilihan wilayah -->
        <label for="wilayah">Wilayah</label>
        <select name="wilayah" id="wilayah" required>
            <option value="1">Wilayah 1</option>
            <option value="2">Wilayah 2</option>
            <option value="3">Wilayah 3</option>
        </select>

        <!-- Pilihan kategori kendaraan -->
        <label for="kategori">Kategori Kendaraan</label>
        <select name="kategori" id="kategori" required>
            <option value="mobil">Mobil</option>
            <option value="truk">Truk / Pick Up</option>
            <option value="bus">Bus</option>
            <option value="motor">Motor</option>
        </select>

        <!-- Input harga mobil -->
        <label for="harga_mobil">Harga Mobil</label>
        <input type="text" name="harga_mobil" id="harga_mobil" required>

        <!-- Input jenis mobil -->
        <label for="jenis_mobil">Jenis Mobil</label>
        <input type="text" name="jenis_mobil" id="jenis_mobil" required>

        <!-- Input tahun mobil -->
        <label for="tahun_mobil">Tahun Mobil</label>
        <input type="text" name="tahun_mobil" id="tahun_mobil" required>

        <!-- Pilihan rate (batas atas atau batas bawah) -->
        <label for="rate">Pilih Rate</label><br>
        <input type="radio" id="batas_bawah" name="rate" value="bawah" checked>
        <label for="batas_bawah">Batas Bawah</label><br>
        <input type="radio" id="batas_atas" name="rate" value="atas">
        <label for="batas_atas">Batas Atas</label><br>

        <!-- Opsi Bengkel Authorized -->
        <label for="bengkel_authorized">
            <input type="checkbox" id="bengkel_authorized" name="bengkel_authorized" value="1" onclick="toggleAuthorizedInput(this)">
            Bengkel Authorized
        </label>
        <input type="text" id="bengkel_authorized_value" name="bengkel_authorized_value" placeholder="Masukkan persentase (misal: 0.1)" disabled>

        <!-- Opsi RSCC -->
        <label for="rscc">
            <input type="checkbox" id="rscc" name="rscc" value="1">
            RSCC (0.05%)
        </label>

        <!-- Opsi Banjir -->
        <label for="banjir">
            <input type="checkbox" id="banjir" name="banjir" value="1">
            Banjir
        </label>

        <!-- Opsi Earthquake -->
        <label for="earthquake">
            <input type="checkbox" id="earthquake" name="earthquake" value="1">
            Earthquake
        </label>

        <!-- Opsi PA Driver -->
        <label for="pa_driver">
            <input type="checkbox" id="pa_driver" name="pa_driver" value="1" onclick="togglePADriverInput(this)">
            PA Driver (Asuransi Pengemudi)
        </label>
        <input type="number" id="pa_driver_value" name="pa_driver_value" placeholder="Masukkan limit (misal: 20)" disabled>

        <!-- Opsi PA Passenger -->
        <label for="pa_passenger">
            <input type="checkbox" id="pa_passenger" name="pa_passenger" value="1" onclick="togglePAPassengerInput(this)">
            PA Passenger (Asuransi Penumpang)
        </label>
        <input type="number" id="pa_passenger_value" name="pa_passenger_value" placeholder="Masukkan limit (misal: 10)" disabled>

        <!-- Opsi Jumlah Penumpang untuk PA Passenger -->
        <label for="pa_passenger_count">Jumlah Penumpang (Max 4)</label>
        <select name="pa_passenger_count" id="pa_passenger_count">
            <option value="1">1 Penumpang</option>
            <option value="2">2 Penumpang</option>
            <option value="3">3 Penumpang</option>
            <option value="4">4 Penumpang</option>
        </select>

        <!-- Opsi Tanggung Jawab Pihak Ketiga -->
        <label for="tpl">
            <input type="checkbox" id="tpl" name="tpl" value="1" onclick="toggleTPLInput(this)">
            Tanggung Jawab Pihak Ketiga
        </label>
        <input type="number" id="tpl_value" name="tpl_value" placeholder="Masukkan limit (misal: 10)" disabled>

        <button type="submit">Generate PDF</button>
    </form>

    <a href="/logout">Logout</a>
</body>

<?php if (session()->has('errors')): ?>
    <div class="errors">
        <ul>
            <?php foreach (session()->get('errors') as $error): ?>
                <li><?= esc($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger">
        <?= session()->getFlashdata('error') ?>
    </div>
<?php endif; ?>

</html>