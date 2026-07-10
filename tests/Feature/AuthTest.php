<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Teste que verifica o login de um usuário do tipo cliente
     */
    public function test_login_valido_cliente(): void
    {
        User::factory()->create([
            'email' => 'teste.valido@gmail.com',
            'password' => bcrypt('senha123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'teste.valido@gmail.com',
            'password' => 'senha123',
        ]);

        $response->assertStatus(200);
    }

    /**
     * Teste que verifica o login de um usuário do tipo admin
     */
    public function test_login_valido_admin(): void
    {
        User::factory()->create([
            'email' => 'teste.valido@gmail.com',
            'password' => bcrypt('senha123'),
            'role' => 'admin'
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'teste.valido@gmail.com',
            'password' => 'senha123',
        ]);

        $response->assertStatus(200);
    }

    /**
     * Teste que verifica o login de um usuário do tipo cliente com senha errada
     */
    public function test_login_invalido(): void
    {
        User::factory()->create([
            'email' => 'teste.invalido@gmail.com',
            'password' => bcrypt('senha123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'teste.invalido@gmail.com',
            'password' => 'senhaerrada',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Teste que verifica dados de um usuário sem token 
     */
    public function test_acesso_info_sem_token(): void
    {
        $response = $this->getJson('/api/info');

        $response->assertStatus(401);
    }

    /**
     * Teste que verifica dados de um usuário com token do tipo cliente
     */
    public function test_acesso_info_com_token_cliente(): void
    {
        $user = User::factory()->create([
            'email' => 'teste.valido@gmail.com',
            'password' => bcrypt('senha123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'teste.valido@gmail.com',
            'password' => 'senha123',
        ]);

        $response->assertStatus(200);
        $token = $response->json('access_token');

        $response = $this->withHeader('Authorization', "Bearer $token")->getJson('/api/info');

        $response->assertStatus(200);
    }

     /**
     * Teste que verifica dados de um usuário com token do tipo admin
     */
    public function test_acesso_info_com_token_admin(): void
    {
        $user = User::factory()->create([
            'email' => 'teste.valido@gmail.com',
            'password' => bcrypt('senha123'),
            'role' => 'admin'
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'teste.valido@gmail.com',
            'password' => 'senha123',
        ]);

        $response->assertStatus(200);
        $token = $response->json('access_token');

        $response = $this->withHeader('Authorization', "Bearer $token")->getJson('/api/info');

        $response->assertStatus(200);
    }
}