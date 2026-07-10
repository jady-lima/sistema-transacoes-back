# Backend Laravel

Backend da aplicação desenvolvido com **Laravel 13.19**, **PHP** e **PostgreSQL**.

Este documento apresenta os passos necessários para executar o projeto pela primeira vez utilizando Docker.

# Execução

## Pré-requisitos

Antes de iniciar, instale:

* Git
* Docker Desktop

## Clonando o projeto

Clone o repositório:

```bash
git clone https://github.com/jady-lima/sistema-transacoes-back.git
```

Entre na pasta do projeto:

```bash
cd sistema-transacoes-back
```

## Configuração do ambiente

O projeto utiliza um arquivo `.env` para armazenar as configurações de ambiente. Copie o `.env.example` e renomeie para `.env`.

## Executando o projeto pela primeira vez

Com o Docker Desktop aberto, execute na raiz do projeto:

```bash
docker compose up --build
```

Após a aplicação ser iniciada, acesse:

```text
(http://host.docker.internal:8000)
```

## Executando em segundo plano

Para iniciar a aplicação sem manter o terminal ocupado, execute:

```bash
docker compose up --build -d
```

# Conferir funcionamento da aplicação
```
http://host.docker.internal:8000
```

## Parando a aplicação

Para parar e remover os containers:

```bash
docker compose down
```

## Iniciando novamente

Depois que a imagem já tiver sido criada, a aplicação pode ser iniciada com:

```bash
docker compose up
```

Ou em segundo plano:

```bash
docker compose up -d
```
## Executando Testes
```
docker compose exec app php artisan test
```

## Acesso como admin para testes
email: `admin@email.com`

senha: `12345678`

## Acesso como usuário sem conta ativa
email: `cliente@email.com`

senha: `12345678`

# Rotas
Listagem das rotas disponíveis e permissionamento.

- **Públicas (sem autenticação):**
	- **POST** `/register`(Parametros: email, password, name): registrar novo usuário
	- **POST** `/login`(Parametros: email, password): autenticação para obtenção de token de acesso

- **Autenticado:**
	- **GET** `/info`: informações do usuário autenticado
	- **POST** `/logout`: encerrar sessão e apaga token

- **Autenticado como Admin:**
	- **GET** `/admin/usuarios`: listar todos os usuários registrados no sistema
	- **GET** `/admin/contas`: listar todas as contas registradas no sistema

- **Autenticado como Cliente:**
	- **POST** `/cliente/contas`(Parametros: phone, cpf): criar conta de cliente para usuário
	- **POST** `/cliente/contas/transacao`(Parametros: number, agency, type, amount): criar nova transação
	- **GET** `/cliente/contas`: listar dados da conta e transações do cliente

- **Web:**
	- **GET** `/`: rota web root para teste de conectividade.

### Observações: 
- Nem todo usuário registrado no sistema pela `/register` é um cliente e possui uma conta, para isso ele precisa fornecer infomações de contato para que seja um cliente com conta ativa.

- Quando um usuário pode realizar uma transação de débito para sua própria conta, ela seria como o saque.
