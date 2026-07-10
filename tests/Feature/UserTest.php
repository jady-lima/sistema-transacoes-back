<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Teste para criar um novo usuário
     */
    public function test_registro_valido_novo_usuario(): void
    {
        $response = $this->postJson('/api/register', [
            'email' => 'teste.valido@gmail.com',
            'password' => 'senha123',
            'name' => 'teste valido'
        ]);

        $response->assertStatus(201);
    }

    /**
     * Teste inválido para criar um novo usuário
     */
    public function test_registro_invalido_novo_usuario(): void
    {
        $response = $this->postJson('/api/register', [
            'email' => 'teste.invalido',
            'password' => 'senha123',
            'name' => 'teste valido'
        ]);

        $response->assertStatus(422);
    }

    /**
     * Teste que verifica se o admin está conseguindo listar todas os usuários do sistema
     */
    public function test_admin_listar_todos_usuarios(): void
    {
        User::factory()->create([
            'email' => 'admin.usuarios@gmail.com',
            'password' => Hash::make('senha123'),
            'role' => 'admin',
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'admin.usuarios@gmail.com',
            'password' => 'senha123',
        ]);

        $loginResponse->assertStatus(200)
                      ->assertJsonStructure([
                          'access_token',
                      ]);

        $token = $loginResponse->json('access_token');
        $this->assertNotEmpty($token);

        $response = $this->withToken($token)->getJson('/api/admin/usuarios');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                    'users',
                 ]);
    }

    /**
     * Teste que verifica se o cliente está conseguindo listar todas os usuários do sistema
     */
    public function test_cliente_listar_contas(): void
    {
        User::factory()->create([
            'email' => 'cliente.usuarios@gmail.com',
            'password' => Hash::make('senha123'),
            'role' => 'cliente',
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'cliente.usuarios@gmail.com',
            'password' => 'senha123',
        ]);

        $token = $loginResponse->json('access_token');

        $response = $this->withToken($token)
                         ->getJson('/api/admin/usuarios');

        $response->assertStatus(403)
                 ->assertJson([
                     'message' => 'Acesso não autorizado.',
                 ]);
    }
}
