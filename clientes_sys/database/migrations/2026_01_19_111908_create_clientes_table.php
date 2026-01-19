<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('clientes', function (Blueprint $table) {
        $table->id();
        $table->date('data_cadastro')->default(now());
        $table->string('nome');
        $table->string('cpf');
        $table->date('data_nascimento');
        $table->integer('idade')->nullable();
        $table->string('nome_pai')->nullable();
        $table->string('nome_mae')->nullable();
        $table->string('telefone')->nullable();
        $table->string('email')->nullable();
        $table->string('cep')->nullable();
        $table->string('endereco')->nullable();
        $table->string('bairro')->nullable();
        $table->integer('numero')->nullable();
        $table->string('complemento')->nullable();
        $table->string('cidade')->nullable();
        $table->string('banco')->nullable();
        $table->string('agencia')->nullable();
        $table->string('conta_corrente')->nullable();
        $table->boolean('simulacao')->default(false);
        $table->boolean('correntista')->default(false);
        $table->boolean('proposta_enviada')->default(false);
        $table->boolean('aprovada')->default(false);
        $table->string('documento_path')->nullable();
        $table->string('foto_path')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
