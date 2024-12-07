<?php

namespace App\Models;

use CodeIgniter\Model;

class PenawaranModel extends Model
{
    protected $table = 'penawaran';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'nomor_surat',
        'nama_tertanggung',
        'alamat_tertanggung',
        'jenis_penawaran',
        'tanggal',
        'status',
        'remarks',
        'created_at',
        'updated_at',
        'file_pdf',
        'file_created_at',
        'harga_mobil',
        'jenis_mobil',
        'tahun_mobil',
        'user_id',
        'bengkel_authorized_value',
        'rscc',
        'banjir',
        'earthquake',
        'pa_driver_value',
        'pa_passenger_value',
        'pa_passenger_count',
        'tpl_value',
        'wilayah',
        'kategori'
    ];
    protected $useTimestamps = true;

    public function generateNomorQuotation($userId)
    {
        // Ambil data pengguna berdasarkan userId
        $userModel = new UserModel();
        $user = $userModel->find($userId);
        $marketingBranch = $user['branch'];  // Mendapatkan cabang dari tabel users

        // Kode cabang untuk masing-masing cabang
        $branchCodes = [
            'Surabaya' => '08',
            'Jakarta'  => '01',
            'Bali'     => '03',
            'Medan'    => '02',
            'Bandung'  => '04',
            'Yogyakarta' => '05',
            'Semarang' => '06'
        ];

        // Tentukan kode cabang berdasarkan nama cabang
        $branchCode = isset($branchCodes[$marketingBranch]) ? $branchCodes[$marketingBranch] : '00';

        // Dapatkan bulan dan tahun saat ini
        $currentMonth = date('m'); // Format bulan (2 digit)
        $currentYear = date('Y');  // Format tahun (4 digit)

        // Hitung jumlah penawaran yang sudah ada untuk cabang ini pada bulan dan tahun yang sama
        $penawaranCount = $this->join('users', 'users.id = penawaran.user_id')  // Gabungkan dengan tabel users
            ->where('users.branch', $marketingBranch)  // Filter berdasarkan cabang
            ->where('MONTH(penawaran.created_at)', $currentMonth) // Filter berdasarkan bulan
            ->where('YEAR(penawaran.created_at)', $currentYear)  // Filter berdasarkan tahun
            ->countAllResults();

        // Nomor penawaran berikutnya, tambah 1 dari jumlah penawaran yang ada
        $nextNomor = str_pad($penawaranCount + 1, 5, '0', STR_PAD_LEFT);

        // Gabungkan semua bagian untuk membuat nomor penawaran
        return "{$branchCode}-QUOT-{$nextNomor}-{$currentMonth}-{$currentYear}";
    }
}
