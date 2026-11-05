@include('errors.minimal-sipz', ['code' => 403, 'title' => 'Akses ditolak', 'message' => $exception->getMessage() ?: 'Halaman ini tidak tersedia untuk peran Anda.'])
