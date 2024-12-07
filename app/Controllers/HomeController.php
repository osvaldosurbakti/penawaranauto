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
        $bengkelAuthorizedHasil = 0;
        if ($bengkel_authorized && is_numeric($bengkel_authorized_value)) {
            $bengkelAuthorizedHasil = $harga_mobil * ($bengkel_authorized_value / 100);
            $hasil += $bengkelAuthorizedHasil;
        }


        // Jika RSCC dicentang, tambahkan 0.05% ke hasil
        if ($rscc) {
            $rscc_value = $harga_mobil * 0.0005;  // 0.05%
            $hasil += $rscc_value;
        }

        // Jika Banjir dicentang, tambahkan perhitungan berdasarkan wilayah
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

        // Jika Earthquake dicentang, tambahkan perhitungan berdasarkan wilayah
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
            $hasil += $earthquake_value;
        }

        // Generate Nomor Quotation
        $penawaranModel = new PenawaranModel();
        $nomor_quotation = $penawaranModel->generateNomorQuotation($userId);

        // Generate PDF
        $pdf = new TCPDF();
        $pdf->SetMargins(10, 20, 10); // Set margin atas, kiri, kanan
        $pdf->SetAutoPageBreak(TRUE, 15); // Atur margin bawah
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);

        // Header
        $pdf->Image(FCPATH . 'assets/logo.jpeg', 10, 15, 50);
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->Cell(0, 15, 'Quotation Summary', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'PT. Asuransi Raksa Pratikara', 0, 1, 'C');
        $pdf->Cell(0, 5, "Phone: $phone | Email: $email", 0, 1, 'C');

        // Garis Pembatas
        $pdf->Line(10, 40, 200, 40);
        $pdf->Ln(10);

        // Detail Quotation
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Detail Penawaran', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(50, 10, 'Nama Tertanggung:', 0, 0, 'L');
        $pdf->Cell(0, 10, ucfirst($nama_tertanggung), 0, 1, 'L');
        // Ulangi untuk detail lainnya
        $pdf->Cell(50, 10, 'Alamat Tertanggung:', 0, 0, 'L');
        $pdf->Cell(0, 10, ucfirst($alamat_tertanggung), 0, 1, 'L');
        $pdf->Cell(50, 10, 'Jenis Kendaraan:', 0, 0, 'L');
        $pdf->Cell(0, 10, ucfirst($kategori), 0, 1, 'L');
        $pdf->Cell(50, 10, 'Tahun:', 0, 0, 'L');
        $pdf->Cell(0, 10, $tahun_mobil, 0, 1, 'L');
        $pdf->Cell(50, 10, 'Harga Pertanggungan:', 0, 0, 'L');
        $pdf->Cell(0, 10, ucfirst($harga_mobil), 0, 1, 'L');

        // Ringkasan Premium dalam Tabel
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Ringkasan Premium', 0, 1, 'L');

        // Mulai tabel dengan header
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(45, 8, 'Nilai Pertanggungan', 1, 0, 'C', 1);
        $pdf->Cell(30, 8, 'X', 1, 0, 'C', 1);
        $pdf->Cell(35, 8, 'Rate', 1, 0, 'C', 1);
        $pdf->Cell(50, 8, 'Premi', 1, 1, 'C', 1);

        // Reset font dan fill untuk isi tabel
        $pdf->SetFont('helvetica', '', 12);
        $pdf->SetFillColor(255, 255, 255);

        // Tambahkan data Comprehensive
        $comprehensiveResult = $harga_mobil * (1.7900 / 100);
        $pdf->Cell(45, 8, number_format($harga_mobil, 0, ',', '.'), 1, 0, 'C');
        $pdf->Cell(30, 8, 'x', 1, 0, 'C');
        $pdf->Cell(35, 8, '1.7900%', 1, 0, 'C');
        $pdf->Cell(50, 8, 'Rp ' . number_format($comprehensiveResult, 0, ',', '.'), 1, 1, 'C');

        // Tambahkan data Bengkel Authorized
        if ($bengkel_authorized) {
            $bengkelAuthorizedResult = $harga_mobil * ($bengkel_authorized_value / 100);
            $pdf->Cell(45, 8, number_format($harga_mobil, 0, ',', '.'), 1, 0, 'C');
            $pdf->Cell(30, 8, 'x', 1, 0, 'C');
            $pdf->Cell(35, 8, number_format($bengkel_authorized_value, 4, ',', '.') . '%', 1, 0, 'C');
            $pdf->Cell(50, 8, 'Rp ' . number_format($bengkelAuthorizedResult, 0, ',', '.'), 1, 1, 'C');
        }

        // Tambahkan data RSCC
        if ($rscc) {
            $rsccResult = $harga_mobil * (0.05 / 100);
            $pdf->Cell(45, 8, number_format($harga_mobil, 0, ',', '.'), 1, 0, 'C');
            $pdf->Cell(30, 8, 'x', 1, 0, 'C');
            $pdf->Cell(35, 8, '0.050%', 1, 0, 'C');
            $pdf->Cell(50, 8, 'Rp ' . number_format($rsccResult, 0, ',', '.'), 1, 1, 'C');
        }

        // Tambahkan data Risiko Banjir
        if ($banjir) {
            $banjirResult = $harga_mobil * ($banjir_value / 100);
            $pdf->Cell(45, 8, number_format($harga_mobil, 0, ',', '.'), 1, 0, 'C');
            $pdf->Cell(30, 8, 'x', 1, 0, 'C');
            $pdf->Cell(35, 8, number_format($banjir_value, 4, ',', '.') . '%', 1, 0, 'C');
            $pdf->Cell(50, 8, 'Rp ' . number_format($banjirResult, 0, ',', '.'), 1, 1, 'C');
        }

        // Tambahkan data Risiko Gempa
        if ($earthquake) {
            $earthquakeResult = $harga_mobil * ($earthquake_value / 100);
            $pdf->Cell(45, 8, number_format($harga_mobil, 0, ',', '.'), 1, 0, 'C');
            $pdf->Cell(30, 8, 'x', 1, 0, 'C');
            $pdf->Cell(35, 8, number_format($earthquake_value, 4, ',', '.') . '%', 1, 0, 'C');
            $pdf->Cell(50, 8, 'Rp ' . number_format($earthquakeResult, 0, ',', '.'), 1, 1, 'C');
        }

        // Tambahkan data PA Driver dan Passenger jika ada
        if ($pa_driver_value) {
            $paDriverResult = $pa_driver_value * (0.5 / 100);
            $pdf->Cell(45, 8, number_format($pa_driver_value, 0, ',', '.'), 1, 0, 'C');
            $pdf->Cell(30, 8, 'x', 1, 0, 'C');
            $pdf->Cell(35, 8, '0.500%', 1, 0, 'C');
            $pdf->Cell(50, 8, 'Rp ' . number_format($paDriverResult, 0, ',', '.'), 1, 1, 'C');
        }

        // Tambahkan data PA Passenger jika ada
        if ($pa_passenger_value) {
            $paPassengerResult = $pa_passenger_value * (0.1 / 100);
            $pdf->Cell(45, 8, number_format($pa_passenger_value, 0, ',', '.'), 1, 0, 'C');
            $pdf->Cell(30, 8, 'x', 1, 0, 'C');
            $pdf->Cell(35, 8, '0.100%', 1, 0, 'C');
            $pdf->Cell(50, 8, 'Rp ' . number_format($paPassengerResult, 0, ',', '.'), 1, 1, 'C');
        }

        // Tambahkan data Tanggung Jawab Pihak Ketiga jika ada
        if ($tpl_value) {
            $tanggungJawabResult = $tpl_value * (1.0 / 100);
            $pdf->Cell(45, 8, number_format($tpl_value, 0, ',', '.'), 1, 0, 'C');
            $pdf->Cell(30, 8, 'x', 1, 0, 'C');
            $pdf->Cell(35, 8, '1.000%', 1, 0, 'C');
            $pdf->Cell(50, 8, 'Rp ' . number_format($tanggungJawabResult, 0, ',', '.'), 1, 1, 'C');
        }

        // Tambahkan total premium di akhir tabel
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(45, 8, 'Total Premium', 1, 0, 'L', 1);
        $pdf->Cell(30, 8, '', 1, 0, 'C');
        $pdf->Cell(35, 8, '', 1, 0, 'C');
        $pdf->Cell(50, 8, 'Rp ' . number_format($hasil, 2, ',', '.'), 1, 1, 'C');

        // Catatan
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->MultiCell(0, 10, 'Catatan Penting: OR (Own Risk) Rp. 300.000. Risiko tambahan...', 0, 'L');
        $pdf->MultiCell(0, 10, 'Kendaraan tidak disewakan atau digunakan sebagai taxi online atau penggunaan lain yang mendapatkan imbalan jasa', 0, 'L');

        // Tambahkan QR Code
        $qrStyle = array('border' => 1, 'padding' => 3);
        $pdf->write2DBarcode('https://www.raksaonline.com/', 'QRCODE,H', 150, 10, 30, 30, $qrStyle, 'N');

        // Footer
        $pdf->SetY(-15);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(0, 10, 'Page ' . $pdf->getAliasNumPage() . ' of ' . $pdf->getAliasNbPages(), 0, 0, 'C');

        // Output
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
