<?php

namespace App\Controllers;

use App\Models\PenawaranModel;
use CodeIgniter\Controller;
use App\Models\UserModel;
use TCPDF;

class HomeController extends Controller
{
    public function index()
    {
        // Halaman home untuk user yang sudah login
        return view('home');
    }



    public function generatePdf()
    {
        // Ambil data dari form
        $wilayah = $this->request->getPost('wilayah');
        $kategori = $this->request->getPost('kategori');
        $nama_tertanggung = $this->request->getPost('nama_tertanggung');
        $alamat_tertanggung = $this->request->getPost('alamat_tertanggung');
        $harga_mobil = $this->request->getPost('harga_mobil');
        $jenis_mobil = $this->request->getPost('jenis_mobil');
        $tahun_mobil = $this->request->getPost('tahun_mobil');
        $rate = $this->request->getPost('rate');
        $bengkel_authorized = $this->request->getPost('bengkel_authorized');
        $bengkel_authorized_value = $this->request->getPost('bengkel_authorized_value');
        $rscc = $this->request->getPost('rscc');  // Ambil input RSCC
        $banjir = $this->request->getPost('banjir'); // Ambil input Banjir
        $earthquake = $this->request->getPost('earthquake'); // Ambil input Earthquake
        $pa_driver_value = $this->request->getPost('pa_driver_value');
        $pa_passenger_value = $this->request->getPost('pa_passenger_value');
        $pa_passenger_count = $this->request->getPost('pa_passenger_count');
        $tpl_value = $this->request->getPost('tpl_value');

        if ($harga_mobil < 5000000 || $harga_mobil > 10000000000) {
            return redirect()->back()->with('error', 'Harga mobil tidak valid.');
        }

        // Inisialisasi validasi form
        $validation = \Config\Services::validation();

        // Set rules validasi
        $validation->setRules([
            'wilayah' => 'required|in_list[1,2,3]', // Pastikan wilayah terpilih dari 1, 2, 3
            'kategori' => 'required|in_list[mobil,truk,bus,motor]', // Validasi kategori
            'nama_tertanggung' => 'required|min_length[3]|max_length[255]', // Nama tertanggung harus ada dan memiliki panjang yang sesuai
            'alamat_tertanggung' => 'required|min_length[3]|max_length[255]', // Alamat minimal 10 karakter
            'harga_mobil' => 'required|decimal', // Harga mobil harus dalam format angka desimal
            'jenis_mobil' => 'required|min_length[3]|max_length[255]', // Jenis mobil harus ada dan valid
            'tahun_mobil' => 'required|numeric|min_length[4]|max_length[4]', // Tahun mobil harus 4 digit
            'rate' => 'required|in_list[atas,bawah]', // Pilihan rate harus antara 'atas' atau 'bawah'
            'bengkel_authorized_value' => 'permit_empty|decimal', // Bengkel authorized bersifat opsional, jika ada harus berupa angka
        ]);
        // Jika validasi gagal, kirim kembali pesan error
        if (!$validation->withRequest($this->request)->run()) {
            // Ambil error validasi
            return redirect()->back()->with('errors', $validation->getErrors());
        }

        // Dapatkan user_id dari sesi
        $userId = session()->get('user_id');
        if (!$userId) {
            return redirect()->to('/login')->with('error', 'User tidak ditemukan');
        }

        // Ambil informasi user dari UserModel
        $userModel = new UserModel();
        $userData = $userModel->getUserById($userId);

        if (!$userData) {
            return redirect()->to('/login')->with('error', 'Data user tidak ditemukan');
        }

        $phone = $userData['phone'];
        $email = $userData['email'];


        // Hitung usia kendaraan
        $currentYear = date('Y');
        $age_of_vehicle = $currentYear - $tahun_mobil;

        // Hitung jumlah tahun lebih dari 5
        $years_over_5 = max(0, $age_of_vehicle - 5);

        // Tentukan persentase berdasarkan wilayah, kategori, dan rentang harga
        $persentase = 0;
        if ($kategori === 'mobil') {
            if ($harga_mobil <= 125000000) {
                $persentase = $wilayah == '1' ? ($rate == 'atas' ? 4.20 : 3.82)
                    : ($wilayah == '2' ? ($rate == 'atas' ? 3.59 : 3.26)
                        : ($rate == 'atas' ? 2.78 : 2.53));
            } elseif ($harga_mobil <= 200000000) {
                $persentase = $wilayah == '1' ? ($rate == 'atas' ? 2.94 : 2.67)
                    : ($wilayah == '2' ? ($rate == 'atas' ? 2.72 : 2.47)
                        : ($rate == 'atas' ? 2.95 : 2.69));
            } elseif ($harga_mobil <= 400000000) {
                $persentase = $wilayah == '1' ? ($rate == 'atas' ? 2.40 : 2.18)
                    : ($wilayah == '2' ? ($rate == 'atas' ? 2.29 : 2.08)
                        : ($rate == 'atas' ? 1.97 : 1.79));
            } elseif ($harga_mobil <= 800000000) {
                $persentase = $rate == 'atas' ? 1.32 : 1.20;
            } else {
                $persentase = $rate == 'atas' ? 1.16 : 1.05;
            }
        } elseif ($kategori === 'truk') {
            $persentase = $wilayah == '1' ? ($rate == 'atas' ? 2.67 : 2.42)
                : ($wilayah == '2' ? ($rate == 'atas' ? 2.63 : 2.39)
                    : ($rate == 'atas' ? 2.46 : 2.23));
        } elseif ($kategori === 'bus') {
            $persentase = $wilayah == '1' ? ($rate == 'atas' ? 1.14 : 1.04)
                : ($wilayah == '2' ? ($rate == 'atas' ? 1.14 : 1.04)
                    : ($rate == 'atas' ? 0.97 : 0.88));
        } elseif ($kategori === 'motor') {
            $persentase = $rate == 'atas' ? 3.50 : 3.18;
        }

        $loadingrate = $persentase * 5 / 100;

        // Jika tahun mobil > 5 tahun, tambahkan 5% per tahun ke rate
        if ($years_over_5 > 0) {
            $additional_rate = $years_over_5 * $loadingrate; // 5% per tahun lebih dari 5 tahun
            $persentase += $additional_rate;
        }

        // Hitung hasil premium
        $hasil = $harga_mobil * ($persentase / 100);

        // Jika Bengkel Authorized dicentang, tambahkan perhitungan


        // Generate Nomor Quotation
        $penawaranModel = new PenawaranModel();
        $nomor_quotation = $penawaranModel->generateNomorQuotation($userId);

        // Generate PDF
        $pdf = new TCPDF();
        $pdf->SetMargins(15, 25, 15); // Margin lebih rapi
        $pdf->SetAutoPageBreak(TRUE, 20);
        $pdf->AddPage();

        // Header
        $pdf->Image(FCPATH . 'assets/logo.jpeg', 15, 15, 40); // Sesuaikan ukuran dan posisi logo
        $pdf->SetFont('helvetica', 'B', 18);
        $pdf->Cell(0, 10, 'Quotation Summary', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'PT. Asuransi Raksa Pratikara', 0, 1, 'C');
        $pdf->Cell(0, 5, "Phone: $phone | Email: $email", 0, 1, 'C');
        $pdf->Line(15, 45, 195, 45); // Garis pemisah
        $pdf->Ln(10);

        // Detail Quotation
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Detail Penawaran', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 12);

        //Detaril Tertanggung
        $dataTertanggung = [
            'Nama Tertanggung' => ucfirst($nama_tertanggung),
            'Alamat Tertanggung' => ucfirst($alamat_tertanggung),
            'Jenis Kendaraan' => ucfirst($kategori),
            'Tahun' => $tahun_mobil,
            'Harga Pertanggungan' => 'Rp ' . number_format($harga_mobil, 0, ',', '.'),
        ];

        foreach ($dataTertanggung as $key => $value) {
            $pdf->Cell(50, 8, "$key:", 0, 0, 'L');
            $pdf->Cell(0, 8, $value, 0, 1, 'L');
        }

        $pdf->Ln(10);


        // Tabel Ringkasan Premium
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Ringkasan Premium', 0, 1, 'L');
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetFont('helvetica', 'B', 12);

        // Header Tabel
        $header = ['Perluasan', 'Nilai Pertanggungan', 'X', 'Rate', 'Premium'];
        $widths = [60, 50, 10, 30, 40]; // Sesuaikan lebar kolom dengan isi tabel

        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetFillColor(200, 200, 200); // Warna latar header
        foreach ($header as $key => $col) {
            $pdf->Cell($widths[$key], 8, $col, 1, 0, 'C', 1);
        }
        $pdf->Ln();

        // Isi Tabel
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetFillColor(255, 255, 255);

        // Tambahkan data Bengkel Authorized
        $bengkelAuthorizedHasil = 0;
        if ($bengkel_authorized) {
            $bengkelAuthorizedHasil = $harga_mobil * ($bengkel_authorized_value / 100);
            $hasil += $bengkelAuthorizedHasil;
        }

        // Jika ada Banjir
        if ($banjir) {
            $banjirPersentase = 0;
            if ($wilayah == '1') {
                $banjirPersentase = $rate == 'atas' ? 0.1 : 0.075;
            } elseif ($wilayah == '2') {
                $banjirPersentase = $rate == 'atas' ? 0.125 : 0.1;
            } else {
                $banjirPersentase = $rate == 'atas' ? 0.1 : 0.075;
            }
            $banjir_value = $harga_mobil * ($banjirPersentase / 100);
            $hasil += $banjir_value;
        }

        // Jika ada RSCC
        if ($rscc) {
            $rscc_value = $harga_mobil * 0.0005;  // 0.05%
            $hasil += $rscc_value;
        }

        // Risiko Gempa
        if ($earthquake) {
            $earthquakePersentase = 0;
            if ($wilayah == '1') {
                $earthquakePersentase = $rate == 'atas' ? 0.135 : 0.120;
            } elseif ($wilayah == '2') {
                $earthquakePersentase = $rate == 'atas' ? 0.125 : 0.1;
            } else {
                $earthquakePersentase = $rate == 'atas' ? 0.135 : 0.075;
            }
            $earthquake_value = $harga_mobil * ($earthquakePersentase / 100);
            $earthquake_value = is_numeric($earthquake_value) ? (float)$earthquake_value : 0;
            $hasil += $earthquake_value;
        }

        // PA Driver and Passenger
        if ($pa_driver_value) {
            $pa_driver_value = is_numeric($pa_driver_value) ? (float)$pa_driver_value : 0;
            $paDriverResult = $pa_driver_value * (0.5 / 100);
            $hasil += $paDriverResult;
        }

        if ($pa_passenger_value && is_numeric($pa_passenger_value) && $pa_passenger_count) {
            $pa_passenger_value = (float)$pa_passenger_value;

            $multiplier = 0;
            switch ($pa_passenger_count) {
                case 1:
                    $multiplier = 0.1;
                    break;
                case 2:
                    $multiplier = 0.2;
                    break;
                case 3:
                    $multiplier = 0.3;
                    break;
                case 4:
                    $multiplier = 0.4;
                    break;
            }

            $paPassengerResult = $pa_passenger_value * $multiplier / 100;
            $hasil += $paPassengerResult;
        }

        // Tanggung Jawab Pihak Ketiga
        if ($tpl_value) {
            $tpl_value = is_numeric($tpl_value) ? (float)$tpl_value : 0;
            $tanggungJawabResult = $tpl_value * (1.0 / 100);
            $hasil += $tanggungJawabResult;
        }

        $comprehensiveResult = $harga_mobil * ($persentase / 100);

        $this->tableRow(
            $pdf,
            'Comprehensive',
            $harga_mobil,
            'x',
            number_format($persentase, 4, ',', '.') . '%',
            $comprehensiveResult
        );

        // Tambahkan data Bengkel Authorized
        if ($bengkel_authorized) {
            $this->tableRow(
                $pdf,
                'Bengkel Authorized',
                $harga_mobil,
                'x',
                number_format($bengkel_authorized_value, 2, ',', '.') . '%',
                $bengkelAuthorizedHasil
            );
        }

        // Jika ada Banjir
        if ($banjir) {
            $this->tableRow(
                $pdf,
                'Banjir, Badai, Angin Topan dan Longsor',
                $harga_mobil,
                'x',
                number_format($banjirPersentase, 3, ',', '.') . '%',
                $banjir_value
            );
        }

        // Jika ada RSCC
        if ($rscc) {
            $this->tableRow(
                $pdf,
                'RSCC (Huru Hara)',
                $harga_mobil,
                'x',
                '0.050%',
                $rscc_value
            );
        }

        // Risiko Gempa
        if ($earthquake) {
            $this->tableRow(
                $pdf,
                'Gempa, Letusan Gunung Api dan Tsunami',
                $harga_mobil,
                'x',
                number_format($earthquakePersentase, 3, ',', '.') . '%',
                $earthquake_value
            );
        }

        // PA Driver
        if ($pa_driver_value) {
            $this->tableRow(
                $pdf,
                'PA Driver (Asuransi Pengemudi) - Limit ' . $pa_driver_value,
                $pa_driver_value,
                'x',
                '0.500%',
                $paDriverResult
            );
        }

        // PA Passenger
        if ($pa_passenger_value && is_numeric($pa_passenger_value) && $pa_passenger_count) {
            $this->tableRow(
                $pdf,
                'PA Passenger (Asuransi Penumpang) Max 4 Orang - Limit ' . $pa_passenger_value,
                $pa_passenger_value,
                'x',
                '0.100%',
                $paPassengerResult
            );
        }

        // Tanggung Jawab Pihak Ketiga
        if ($tpl_value) {
            $this->tableRow(
                $pdf,
                'Tanggung Jawab Pihak Ketiga Limit',
                $tpl_value,
                'x',
                '1.000%',
                $tanggungJawabResult
            );
        }

        // Total Premium
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(150, 8, 'Total Premium', 1, 0, 'R', 1);
        $pdf->Cell(40, 8, 'Rp ' . number_format($hasil, 0, ',', '.'), 1, 1, 'C');

        // Administrasi + Materai
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(150, 8, 'Administrasi + Materai', 1, 0, 'R', 1);
        $pdf->Cell(40, 8, 'Rp 60.000', 1, 1, 'C');

        // Total Premi yang Harus Dibayar
        $hasilakhir = $hasil + 60000;
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetFillColor(200, 200, 200); // Tambahkan latar abu-abu untuk penekanan
        $pdf->Cell(150, 10, 'Total Premi yang Harus Dibayar', 1, 0, 'R', 1);
        $pdf->Cell(40, 10, 'Rp ' . number_format($hasilakhir, 0, ',', '.'), 1, 1, 'C');

        // Catatan Penting
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->MultiCell(0, 8, 'Catatan Penting: OR (Own Risk) Rp. 300.000...', 0, 'L');

        // Tambahkan QR Code
        $pdf->write2DBarcode('https://www.raksaonline.com/', 'QRCODE,H', 160, 15, 30, 30, array(), 'N');

        // Footer
        $pdf->SetY(-15);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(0, 10, 'Page ' . $pdf->getAliasNumPage() . ' of ' . $pdf->getAliasNbPages(), 0, 0, 'C');

        // Output PDF
        $file_name = strtolower(str_replace(' ', '_', $nama_tertanggung)) . '_' . $nomor_quotation . '.pdf';
        $file_path = FCPATH . 'uploads/' . $file_name;
        $pdf->Output($file_path, 'F');


        // Simpan data ke database
        $penawaranModel->save([
            'nomor_surat' => $nomor_quotation,
            'nama_tertanggung' => $nama_tertanggung,
            'harga_mobil' => $harga_mobil,
            'jenis_mobil' => $jenis_mobil,
            'tahun_mobil' => $tahun_mobil,
            'user_id' => $userId,
            'file_pdf' => 'uploads/' . $file_name
        ]);


        // Return the generated PDF as a download
        return $this->response->download($file_path, null);
    }

    private function tableRow($pdf, $label, $coverage, $symbol, $rate, $premium)
    {
        // Set font
        $pdf->SetFont('Helvetica', '', 10);

        // Lebar kolom (disesuaikan)
        $labelWidth = 60;
        $coverageWidth = 50;
        $symbolWidth = 10;
        $rateWidth = 30;
        $premiumWidth = 40;

        // Tinggi baris
        $rowHeight = 8;

        // Ambil posisi awal X dan Y
        $xStart = $pdf->GetX();
        $yStart = $pdf->GetY();

        // Kolom pertama: Label (MultiCell untuk teks panjang)
        $pdf->MultiCell($labelWidth, $rowHeight, $label, 1, 'L');

        // Hitung tinggi teks label untuk penyesuaian tinggi baris
        $labelHeight = $pdf->GetY() - $yStart;

        // Ambil tinggi terbesar antara label dan tinggi baris default
        $finalRowHeight = max($rowHeight, $labelHeight);

        // Atur posisi kembali untuk kolom lainnya
        $pdf->SetXY($xStart + $labelWidth, $yStart);

        // Kolom lainnya (gunakan Cell)
        $pdf->Cell($coverageWidth, $finalRowHeight, number_format($coverage, 0, ',', '.'), 1, 0, 'C');
        $pdf->Cell($symbolWidth, $finalRowHeight, $symbol, 1, 0, 'C');
        $pdf->Cell($rateWidth, $finalRowHeight, $rate, 1, 0, 'C');
        $pdf->Cell($premiumWidth, $finalRowHeight, 'Rp ' . number_format($premium, 0, ',', '.'), 1, 1, 'C');
    }

    public function history()
    {
        // Ambil id user yang sedang login
        $userId = session()->get('user_id');
        if (!$userId) {
            return redirect()->to('/login')->with('error', 'User tidak ditemukan');
        }

        $penawaranModel = new PenawaranModel();
        // Ambil semua penawaran yang dibuat oleh marketing
        $penawaran = $penawaranModel->where('user_id', $userId)->findAll();

        return view('history', ['penawaran' => $penawaran]);
    }
}
