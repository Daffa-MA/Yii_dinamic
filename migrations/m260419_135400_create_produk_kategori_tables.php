<?php

use yii\db\Migration;

/**
 * Migration: Create produk and kategori tables for FK testing
 */
class m260419_135400_create_produk_kategori_tables extends Migration
{
    public function safeUp()
    {
        // Create kategori table first (referenced table)
        $this->createTable('kategori', [
            'id' => $this->primaryKey(),
            'nama' => $this->string(255)->notNull(),
            'deskripsi' => $this->text()->null(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        $this->createIndex('idx_kategori_nama', 'kategori', 'nama');

        // Create produk table with FK to kategori
        $this->createTable('produk', [
            'id' => $this->primaryKey(),
            'nama' => $this->string(255)->notNull(),
            'kategori_id' => $this->integer()->notNull(),
            'harga' => $this->decimal(10, 2)->null(),
            'stok' => $this->integer()->defaultValue(0),
            'deskripsi' => $this->text()->null(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Add FK constraint
        $this->addForeignKey(
            'fk_produk_kategori_id',
            'produk',
            'kategori_id',
            'kategori',
            'id',
            'RESTRICT',
            'RESTRICT'
        );

        $this->createIndex('idx_produk_nama', 'produk', 'nama');
        $this->createIndex('idx_produk_kategori_id', 'produk', 'kategori_id');

        // Insert sample data
        $this->insert('kategori', ['nama' => 'Elektronik', 'deskripsi' => 'Produk elektronik']);
        $this->insert('kategori', ['nama' => 'Pakaian', 'deskripsi' => 'Produk pakaian']);
        $this->insert('kategori', ['nama' => 'Makanan', 'deskripsi' => 'Produk makanan']);

        echo "✓ Tables created: kategori, produk\n";
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_produk_kategori_id', 'produk');
        $this->dropTable('produk');
        $this->dropTable('kategori');

        echo "✓ Tables dropped: produk, kategori\n";
    }
}
