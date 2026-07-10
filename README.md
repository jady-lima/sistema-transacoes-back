# Backend Laravel

Backend da aplicação desenvolvido com **Laravel 13.19**, **PHP** e **PostgreSQL**.

Este documento apresenta os passos necessários para executar o projeto pela primeira vez utilizando Docker.

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

O projeto utiliza um arquivo `.env` para armazenar as configurações de ambiente. Copie o `.env.example` e renomeie para `.env` substituindo o link da api backend para correspondente.

```
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=sistema-transacoes
DB_USERNAME={USERNAME}
DB_PASSWORD={PASS}
```

## Executando o projeto pela primeira vez

Com o Docker Desktop aberto, execute na raiz do projeto:

```bash
docker compose up --build
```

Após a aplicação ser iniciada, acesse:

```text
http://localhost:8000
```

## Executando em segundo plano

Para iniciar a aplicação sem manter o terminal ocupado, execute:

```bash
docker compose up --build -d
```

# Gerar a chave da aplicação
```
docker compose exec app php artisan key:generate
```

# Executar as migrations
```
docker compose exec app php artisan migrate
```

Executar os seeder:

```
docker compose exec app php artisan migrate --seed
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
