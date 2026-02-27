<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAutoDraftOblsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auto_draft_obls', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to auto_drafts
            $table->foreignId('auto_draft_id')->constrained('auto_drafts')->onDelete('cascade');
            
            // OBL Index (1, 2, 3, etc.)
            $table->unsignedInteger('obl_index')->default(1);
            
            // Layanan (services) - stored as JSON array
            $table->json('layanan')->nullable();
            
            // Payment & Duration Configuration
            $table->enum('terms_of_payment', [
                'Bulanan',
                'OTC',
                'Termin',
                'Bulanan dan OTC',
                'Bulanan dan Termin'
            ])->default('Bulanan');
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->string('durasi')->nullable();
            
            // ================================
            // MITRA 1 (Primary Partner)
            // ================================
            $table->string('mitra_1_nama')->nullable();
            $table->text('mitra_1_alamat')->nullable();
            $table->string('mitra_1_nomor_sph')->nullable();
            $table->date('mitra_1_tanggal_sph')->nullable();
            $table->decimal('mitra_1_harga_bulanan', 20, 2)->default(0);
            $table->decimal('mitra_1_harga_otc', 20, 2)->default(0);
            $table->decimal('mitra_1_harga_total', 20, 2)->default(0);
            
            // ================================
            // MITRA 2 (Secondary Partner - for Tender mode)
            // ================================
            $table->string('mitra_2_nama')->nullable();
            $table->text('mitra_2_alamat')->nullable();
            $table->string('mitra_2_nomor_sph')->nullable();
            $table->date('mitra_2_tanggal_sph')->nullable();
            $table->decimal('mitra_2_harga_bulanan', 20, 2)->default(0);
            $table->decimal('mitra_2_harga_otc', 20, 2)->default(0);
            $table->decimal('mitra_2_harga_total', 20, 2)->default(0);
            
            // Tender mode flag
            $table->boolean('is_tender')->default(false);
            
            // ================================
            // P2 Document
            // ================================
            $table->date('date_p2')->nullable();
            
            // ================================
            // P3 Document
            // ================================
            $table->string('nomor_p3_mitra_1')->nullable();
            $table->date('date_p3_mitra_1')->nullable();
            $table->string('nomor_p3_mitra_2')->nullable();
            $table->date('date_p3_mitra_2')->nullable();
            
            // ================================
            // P4 Document
            // ================================
            $table->date('date_p4')->nullable();
            $table->date('target_delivery')->nullable();
            $table->enum('skema_bisnis', ['Sewa Murni', 'Beli Putus'])->default('Sewa Murni');
            $table->decimal('slg', 5, 2)->nullable()->comment('Service Level Guarantee percentage');
            
            // ================================
            // P5 Document
            // ================================
            $table->date('date_p5')->nullable();
            
            // ================================
            // P6 Document (Negotiation Result)
            // ================================
            $table->date('date_p6')->nullable();
            $table->enum('mitra_final', ['mitra_1', 'mitra_2'])->nullable()->comment('Winner for tender mode');
            $table->decimal('harga_bulanan_final', 20, 2)->default(0);
            $table->decimal('harga_otc_final', 20, 2)->default(0);
            $table->decimal('harga_total_final', 20, 2)->default(0);
            
            // ================================
            // P7 Document
            // ================================
            $table->string('nomor_p7')->nullable();
            $table->date('date_p7')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('auto_draft_id');
            $table->index('obl_index');
            $table->unique(['auto_draft_id', 'obl_index']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('auto_draft_obls');
    }
}
