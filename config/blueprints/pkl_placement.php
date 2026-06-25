<?php

return [
    'name' => 'Penempatan PKL Siswa',
    'tables' => [
        [
            'name' => 'jurusan',
            'label' => 'Jurusan',
            'columns' => [
                ['name' => 'kode', 'label' => 'Kode', 'type' => 'VARCHAR', 'length' => 50],
                ['name' => 'nama', 'label' => 'Nama Jurusan', 'type' => 'VARCHAR', 'length' => 160],
            ],
        ],
        [
            'name' => 'kelas',
            'label' => 'Kelas',
            'columns' => [
                ['name' => 'nama', 'label' => 'Nama Kelas', 'type' => 'VARCHAR', 'length' => 120],
                ['name' => 'jurusan_id', 'label' => 'Jurusan', 'type' => 'INT', 'length' => 11, 'foreign_key' => ['table' => 'jurusan', 'column' => 'id']],
            ],
        ],
        [
            'name' => 'siswa_pkl',
            'label' => 'Siswa PKL',
            'columns' => [
                ['name' => 'nisn', 'label' => 'NISN', 'type' => 'VARCHAR', 'length' => 40],
                ['name' => 'nama', 'label' => 'Nama Siswa', 'type' => 'VARCHAR', 'length' => 180],
                ['name' => 'kelas_id', 'label' => 'Kelas', 'type' => 'INT', 'length' => 11, 'foreign_key' => ['table' => 'kelas', 'column' => 'id']],
                ['name' => 'jurusan_id', 'label' => 'Jurusan', 'type' => 'INT', 'length' => 11, 'foreign_key' => ['table' => 'jurusan', 'column' => 'id']],
                ['name' => 'domisili', 'label' => 'Domisili', 'type' => 'VARCHAR', 'length' => 220, 'nullable' => true],
                ['name' => 'kompetensi', 'label' => 'Kompetensi Keahlian', 'type' => 'VARCHAR', 'length' => 220, 'nullable' => true],
                ['name' => 'status', 'label' => 'Status', 'type' => 'VARCHAR', 'length' => 40, 'default' => 'aktif'],
            ],
        ],
        [
            'name' => 'perusahaan_dudi',
            'label' => 'Perusahaan / DU-DI',
            'columns' => [
                ['name' => 'nama', 'label' => 'Nama Perusahaan', 'type' => 'VARCHAR', 'length' => 200],
                ['name' => 'alamat', 'label' => 'Alamat', 'type' => 'TEXT', 'nullable' => true],
                ['name' => 'kota', 'label' => 'Kota', 'type' => 'VARCHAR', 'length' => 120, 'nullable' => true],
                ['name' => 'bidang_industri', 'label' => 'Bidang Industri', 'type' => 'VARCHAR', 'length' => 180, 'nullable' => true],
                ['name' => 'kebutuhan_kompetensi', 'label' => 'Kebutuhan Kompetensi', 'type' => 'VARCHAR', 'length' => 220, 'nullable' => true],
                ['name' => 'kuota', 'label' => 'Kuota', 'type' => 'INT', 'length' => 11, 'default' => 0],
                ['name' => 'status', 'label' => 'Status', 'type' => 'VARCHAR', 'length' => 40, 'default' => 'aktif'],
            ],
        ],
        [
            'name' => 'pilihan_tempat_pkl',
            'label' => 'Pilihan Tempat PKL',
            'columns' => [
                ['name' => 'siswa_id', 'label' => 'Siswa', 'type' => 'INT', 'length' => 11, 'foreign_key' => ['table' => 'siswa_pkl', 'column' => 'id']],
                ['name' => 'perusahaan_id', 'label' => 'Perusahaan Pilihan', 'type' => 'INT', 'length' => 11, 'foreign_key' => ['table' => 'perusahaan_dudi', 'column' => 'id']],
                ['name' => 'urutan_pilihan', 'label' => 'Urutan Pilihan', 'type' => 'INT', 'length' => 11, 'default' => 1],
                ['name' => 'alasan', 'label' => 'Alasan', 'type' => 'TEXT', 'nullable' => true],
                ['name' => 'status', 'label' => 'Status', 'type' => 'VARCHAR', 'length' => 40, 'default' => 'diajukan'],
            ],
        ],
        [
            'name' => 'penempatan_pkl',
            'label' => 'Penempatan PKL',
            'columns' => [
                ['name' => 'siswa_id', 'label' => 'Siswa', 'type' => 'INT', 'length' => 11, 'foreign_key' => ['table' => 'siswa_pkl', 'column' => 'id']],
                ['name' => 'perusahaan_id', 'label' => 'Perusahaan', 'type' => 'INT', 'length' => 11, 'foreign_key' => ['table' => 'perusahaan_dudi', 'column' => 'id']],
                ['name' => 'jurusan_id', 'label' => 'Jurusan', 'type' => 'INT', 'length' => 11, 'foreign_key' => ['table' => 'jurusan', 'column' => 'id']],
                ['name' => 'kelas_id', 'label' => 'Kelas', 'type' => 'INT', 'length' => 11, 'foreign_key' => ['table' => 'kelas', 'column' => 'id']],
                ['name' => 'status_penempatan', 'label' => 'Status Penempatan', 'type' => 'VARCHAR', 'length' => 60, 'default' => 'draft'],
                ['name' => 'catatan', 'label' => 'Catatan Ketua Jurusan', 'type' => 'TEXT', 'nullable' => true],
                ['name' => 'tanggal_persetujuan', 'label' => 'Tanggal Persetujuan', 'type' => 'DATE', 'nullable' => true],
            ],
        ],
    ],
    'forms' => [
        [
            'name' => 'Form Siswa PKL',
            'slug' => 'form-siswa-pkl',
            'table' => 'siswa_pkl',
        ],
        [
            'name' => 'Form Perusahaan DU-DI',
            'slug' => 'form-perusahaan-dudi',
            'table' => 'perusahaan_dudi',
        ],
        [
            'name' => 'Form Pilihan Tempat PKL',
            'slug' => 'form-pilihan-tempat-pkl',
            'table' => 'pilihan_tempat_pkl',
        ],
        [
            'name' => 'Form Mapping Penempatan PKL',
            'slug' => 'form-mapping-penempatan-pkl',
            'table' => 'penempatan_pkl',
        ],
    ],
    'datatables' => [
        [
            'name' => 'Daftar Penempatan PKL',
            'table' => 'penempatan_pkl',
            'columns' => [
                ['field' => 'siswa_id', 'label' => 'Siswa', 'visible' => true, 'fk_display_mode' => 'related_column', 'related_display_column' => 'nama'],
                ['field' => 'jurusan_id', 'label' => 'Jurusan', 'visible' => true, 'fk_display_mode' => 'related_column', 'related_display_column' => 'nama'],
                ['field' => 'kelas_id', 'label' => 'Kelas', 'visible' => true, 'fk_display_mode' => 'related_column', 'related_display_column' => 'nama'],
                ['field' => 'perusahaan_id', 'label' => 'Perusahaan', 'visible' => true, 'fk_display_mode' => 'related_column', 'related_display_column' => 'nama'],
                ['field' => 'status_penempatan', 'label' => 'Status', 'visible' => true],
            ],
            'filters' => [
                ['field' => 'jurusan_id', 'label' => 'Jurusan'],
                ['field' => 'kelas_id', 'label' => 'Kelas'],
                ['field' => 'perusahaan_id', 'label' => 'Perusahaan'],
            ],
            'stats' => [
                ['field' => 'jurusan_id', 'label' => 'Siswa per Jurusan'],
                ['field' => 'perusahaan_id', 'label' => 'Siswa per Perusahaan'],
            ],
            'workflow' => [
                'approval_enabled' => true,
                'status_field' => 'status_penempatan',
                'pending_value' => 'draft',
                'approved_value' => 'disetujui',
                'button_label' => 'Setujui',
            ],
            'actions' => ['view' => true, 'edit' => true, 'delete' => true],
            'search' => true,
            'pagination' => true,
        ],
    ],
    'menus' => [
        ['name' => 'Penempatan PKL', 'type' => 'route', 'route' => '/master-datatable', 'icon' => 'briefcase', 'sort_order' => 40],
    ],
];
