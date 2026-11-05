@include('errors.minimal-sipz', ['code' => 404, 'title' => 'Halaman tidak ditemukan', 'message' => $exception->getMessage() ?: 'Alamat yang dibuka tidak ada atau data sudah tidak tersedia.'])
