<?php

namespace App\Controllers;

use App\Models\PenawaranModel;
use App\Models\UserModel;
use CodeIgniter\Controller;
use CodeIgniter\Email\Email;

class UserController extends Controller
{
    public function login()
    {
        return view('login'); // Tampilkan halaman login
    }

    public function loginProcess()
    {
        $userModel = new UserModel();
        $email = trim($this->request->getPost('email'));
        $password = $this->request->getPost('password');

        $user = $userModel->where('email', $email)->first();

        if (!$user) {
            return redirect()->back()->with('error', 'Email tidak ditemukan.');
        }

        if (!$user['is_verified']) {
            return redirect()->back()->with('error', 'Akun belum diverifikasi. Silakan cek email Anda.');
        }

        if (!password_verify($password, $user['password'])) {
            return redirect()->back()->with('error', 'Password salah.');
        }

        session()->set([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'marketing_name' => $user['marketing_name'],
            'branch' => $user['branch'],
            'role' => $user['role'],
            'logged_in' => true,
        ]);

        return redirect()->to('/home');
    }


    public function register()
    {
        return view('register'); // Tampilkan halaman register
    }

    public function registerProcess()
    {
        $userModel = new UserModel();

        $validationRules = [
            'email' => 'required|valid_email|is_unique[users.email]',
            'phone' => 'required|numeric|is_unique[users.phone]',
            'password' => 'required|min_length[6]',
            'marketing_name' => 'required|string',
            'branch' => 'required|string',
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', $this->validator->listErrors());
        }

        $email = trim($this->request->getPost('email'));
        $verificationToken = bin2hex(random_bytes(16)); // Generate token unik

        // Simpan data pengguna
        $userModel->save([
            'email' => $email,
            'phone' => $this->request->getPost('phone'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'role' => 'user',
            'marketing_name' => $this->request->getPost('marketing_name'),
            'branch' => $this->request->getPost('branch'),
            'verification_token' => $verificationToken,
            'is_verified' => 0,
        ]);

        // Kirim email verifikasi
        $this->sendVerificationEmail($email, $verificationToken);

        return redirect()->to('/login')->with('success', 'Registrasi berhasil. Silakan cek email untuk verifikasi.');
    }

    private function sendVerificationEmail($email, $token)
    {
        $emailService = \Config\Services::email();
        $emailService->setFrom('penawaranauto.com', 'Registrasi Penawaran Auto');
        $emailService->setTo($email);
        $emailService->setSubject('Verifikasi Akun Anda');
        $emailService->setMessage("Klik link berikut untuk verifikasi akun Anda: " .
            base_url("verify?token=$token"));

        if (!$emailService->send()) {
            log_message('error', 'Gagal mengirim email: ' . $emailService->printDebugger());
        }
    }

    public function verify()
    {
        $token = $this->request->getGet('token');
        $userModel = new UserModel();

        // Log token untuk debugging
        log_message('debug', 'Token yang diterima: ' . $token);

        $user = $userModel->where('verification_token', $token)->first();

        if (!$user) {
            return redirect()->to('/login')->with('error', 'Token tidak valid atau akun sudah diverifikasi.');
        }

        // Jika token ditemukan dan akun belum diverifikasi
        $userModel->update($user['id'], [
            'is_verified' => 1,
            'verification_token' => null, // Hapus token verifikasi setelah digunakan
        ]);

        return redirect()->to('/login')->with('success', 'Akun berhasil diverifikasi. Silakan login.');
    }


    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login')->with('success', 'Logout berhasil.');
    }
}
