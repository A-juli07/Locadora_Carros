# API Locadora de Carros

API com CRUD para uma locadora de carros, com autorização por meio de JWT e documentação com Swagger. As rotas principais são as de auth ( login | cadastro | me | logout ), cliente(CRUD), marca(CRUD), modelo(CRUD), carro(CRUD) e locacao(CRUD). 

## Rotas da API

Todas as rotas com autenticação pelo JWT, tem as rotas api/v1/{functions}.

### Auth 

POST
- api/login  Realiza o login dentro da aplicação e envia o token valido
- api/cadastro   Cria cadastro e já envia o token para validação
- api/v1/me  Retorna os dados do cliente logado
- api/v1/logout  Desloga o cliente conectado

### Carro

GET 
- api/v1/carro    Retorna um json com todos os dados dos carros cadastrados
- api/v1/carro/{id}    Pede o id de um carro em especifico e retorna seus dados

POST 
- api/v1/carro    Cadastra um carro

PUT
- api/v1/carro/{id}    Atualização completa dos dados do carro

PATCH
- api/v1/carro/{id}    Atualiza somente os dados passados

DELETE
- api/v1/carro/{id}    Deleta carro correspondente ao id informado
