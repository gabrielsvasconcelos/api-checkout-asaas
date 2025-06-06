```markdown
<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
</p>

<p align="center">
  <a href="https://laravel.com" target="_blank">Projeto Laravel com Integração Asaas</a>
</p>

---

## 🚀 Visão Geral

Este projeto é uma API desenvolvida com Laravel, com foco em cadastros de produtos, clientes e geração de cobranças via [Asaas](https://asaas.com). A autenticação é baseada em tokens (Laravel Sanctum), e todas as ações protegidas exigem autenticação via Bearer Token.

---

## ⚙️ Instalação

1. Clone o repositório:
   ```bash
   git clone https://github.com/seu-usuario/seu-repo.git
   cd seu-repo
   ```

2. Instale as dependências:
   ```bash
   composer install
   ```

3. Copie o arquivo `.env` e configure:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. No `.env`, além das configurações padrão de Laravel, **defina obrigatoriamente**:

   ```env
   ASAAS_BASE_URL=https://api-sandbox.asaas.com
   ASAAS_API_KEY=[sua-chave-de-api-asaas]
   ```

5. Execute as migrations:
   ```bash
   php artisan migrate
   ```

6. Inicie o servidor:
   ```bash
   php artisan serve
   ```

---

## 🔐 Autenticação

A API utiliza Laravel Sanctum. Para acessar os endpoints protegidos, primeiro registre-se e faça login para obter um token:

### 📌 Registro

```bash
curl --location 'http://localhost:8000/api/auth/register' \
--header 'Accept: application/json' \
--header 'Content-Type: application/json' \
--data-raw '{
  "name": "Test User",
  "email": "test@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}'
```

### 📌 Login

```bash
curl --location 'http://localhost:8000/api/auth/login' \
--header 'Accept: application/json' \
--header 'Content-Type: application/json' \
--data-raw '{
  "email": "test@example.com",
  "password": "password123"
}'
```

> O token retornado deve ser utilizado nas requisições seguintes no header:  
> `Authorization: Bearer SEU_TOKEN`

---

## 📦 Produtos

### Criar Produto

```bash
curl --location 'http://localhost:8000/api/products' \
--header 'Authorization: Bearer SEU_TOKEN' \
--header 'Content-Type: application/json' \
```

### Listar Seus Produtos

```bash
curl --location 'http://localhost:8000/api/my-products' \
--header 'Authorization: Bearer SEU_TOKEN'
```

---

## 💰 Pedidos e Pagamentos

### Criar Pedido

```bash
curl --location 'http://localhost:8000/api/orders' \
--header 'Authorization: Bearer SEU_TOKEN' \
--header 'Content-Type: application/json' \
--data-raw '{
  "customer": {
    "name": "Nome teste",
    "email": "nometeste@gmail.com",
    "document": "36660666079",
    "phone": "21934564321",
    "address": {
      "zip_code": "01001000",
      "street": "Rua Teste",
      "number": "123",
      "state": "CE",
      "city": "Fortaleza",
      "neighborhood": "Meireles"
    }
  },
  "items": [
    {
      "product_id": 2,
      "quantity": 1
    }
  ],
  "payment_method": "boleto"
}'
```

### Listar Seus Pedidos

```bash
curl --location 'http://localhost:8000/api/my-orders' \
--header 'Authorization: Bearer SEU_TOKEN'
```

---

## 📄 Licença

Este projeto está licenciado sob a licença MIT. Consulte o arquivo [LICENSE](LICENSE) para mais detalhes.

---

## 🙋‍♂️ Suporte

Para dúvidas ou sugestões, abra uma issue ou envie uma pull request.
```
