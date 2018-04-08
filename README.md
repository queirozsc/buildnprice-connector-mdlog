# Conector MDLOG
## MDLog ERP
O MD-Log  é um ERP construído pela Informata especialmente criado para automatizar as regras de negócio ou do core business  da Distribuição, Atacado e Retaguarda da Rede de Varejo, com foco na Gestão dos Fornecedores e Produtos para Revenda, garantindo o melhor estoque, giro e preço nas lojas ou armazéns.

Saiba mais em: http://www.informata.com.br/home/ver-produto.php?nome=md-log-erp

## Obtendo a lista de produtos do fornecedor
Para fazer a carga inicial faça a chamada à API:
```
curl -X POST \
  http://api.fornecedor.com.br:64462/ \
  -H 'cache-control: no-cache' \
  -H 'content-type: application/json' \
  -H 'postman-token: 29c8e4c0-7b18-7999-8131-26ba5e09a787' \
  -d '{
    "app_id": "{APPLICATION ID}",
    "token": "{USER TOKEN}",
    "mode": "bnp_mdlog_get_products",
    "segmento": "",
    "produto": "",
    "fornecedor": ""
}'
```
> Os parâmetros **segmento**, **produto** e **fornecedor** servem para filtrar, permitindo uma carga reduzida.

O resultado do comando será o arquivo JSON usado para dar a carga no ElasticSearch:
```
{
    "flag": false,
    "data": "api.fornecedor.com.br:64462/media/bnp_mdlog_get_products.json"
}
```
## Enviando a lista de produtos
Faça o download do arquivo gerado pelo passo anterior:
```
wget http://api.fornecedor.com.br:64462/api/media/bnp_mdlog_get_products.json catalog-products.json
```
Envie os dados para o ElasticSearch:
```
curl -XPOST https://search-buildnprice-es-supplier.us-east-1.es.amazonaws.com/_bulk --data-binary @catalog-products.json 
```

## Consultando os produtos no fornecedor
Envie a lista dos códigos que deseja pesquisar:
```
curl -X POST \
  http://api.apotiguar.com.br:64462/ \
  -H 'authorization: AWS4-HMAC-SHA256 Credential=/20170807/us-east-1/execute-api/aws4_request, SignedHeaders=content-type;host;x-amz-date, Signature=0552d73111e76ef923d9379c98dfdbdca45e5231c1ea9b74843fe916cc3d3ff7' \
  -H 'cache-control: no-cache' \
  -H 'content-type: application/json' \
  -H 'host: api.fornecedor.com.br:64462' \
  -H 'postman-token: a4658d57-b886-3f23-fc3b-86ee3dd9cf46' \
  -H 'x-amz-date: 20170807T191827Z' \
  -d '{
  "app_id": "{APPLICATION ID}",
  "token": "{USER TOKEN}",
  "mode": "bnp_mdlog_get_list",
  "produtos": [
    "708291", "632376", "62898"
  ]
}'
``` 
O resultado da consulta é um JSON:
```
{
    "flag": true,
    "data": [
        {
            "ean": 708291,
            "data": [
                {
                    "PRODUTO": 708291,
                    "EAN": 7898119557941,
                    "DESCRICAO": "TORNEIRA PIA 1160 JED",
                    "PRECO1": 44.77,
                    "ESTOQUE1": 11,
                    "PRECO2": 44.77,
                    "ESTOQUE2": 94,
                    "PRECO3": 44.77,
                    "ESTOQUE3": 26,
                    "PRECO4": 44.77,
                    "ESTOQUE4": 14,
                    "PRECO5": 44.77,
                    "ESTOQUE5": 580,
                    "FORNECEDOR": 2526,
                    "RAZAO_SOCIAL": "GUIRADO SCHAFFER IND E COM DE METAI"
                },
                {
                    "PRODUTO": 708291,
                    "EAN": 7898119531408,
                    "DESCRICAO": "TORNEIRA PIA 1160 JED",
                    "PRECO1": 44.77,
                    "ESTOQUE1": 11,
                    "PRECO2": 44.77,
                    "ESTOQUE2": 94,
                    "PRECO3": 44.77,
                    "ESTOQUE3": 26,
                    "PRECO4": 44.77,
                    "ESTOQUE4": 14,
                    "PRECO5": 44.77,
                    "ESTOQUE5": 580,
                    "FORNECEDOR": 2526,
                    "RAZAO_SOCIAL": "GUIRADO SCHAFFER IND E COM DE METAI"
                }
            ]
        },
        {
            "ean": 632376,
            "data": [
                {
                    "PRODUTO": 632376,
                    "EAN": 7896451825254,
                    "DESCRICAO": "CHUVEIRO MAXI DUCHA 3200W",
                    "PRECO1": 52,
                    "ESTOQUE1": 18,
                    "PRECO2": 52,
                    "ESTOQUE2": 15,
                    "PRECO3": 52,
                    "ESTOQUE3": 1,
                    "PRECO4": 52,
                    "ESTOQUE4": 15,
                    "PRECO5": 52,
                    "ESTOQUE5": 4,
                    "FORNECEDOR": 132,
                    "RAZAO_SOCIAL": "LORENZETTI SA IND BRAS ELETOMETAL"
                },
                {
                    "PRODUTO": 632376,
                    "EAN": 7896451824820,
                    "DESCRICAO": "CHUVEIRO MAXI DUCHA 3200W",
                    "PRECO1": 52,
                    "ESTOQUE1": 18,
                    "PRECO2": 52,
                    "ESTOQUE2": 15,
                    "PRECO3": 52,
                    "ESTOQUE3": 1,
                    "PRECO4": 52,
                    "ESTOQUE4": 15,
                    "PRECO5": 52,
                    "ESTOQUE5": 4,
                    "FORNECEDOR": 132,
                    "RAZAO_SOCIAL": "LORENZETTI SA IND BRAS ELETOMETAL"
                }
            ]
        },
        {
            "ean": 62898,
            "data": [
                {
                    "PRODUTO": 62898,
                    "EAN": 7891333088345,
                    "DESCRICAO": "EROS BACIA P CAIXA ACOP BRANCO INCEPA",
                    "PRECO1": 89.9,
                    "ESTOQUE1": 0,
                    "PRECO2": 89.9,
                    "ESTOQUE2": 0,
                    "PRECO3": 89.9,
                    "ESTOQUE3": 0,
                    "PRECO4": 89.9,
                    "ESTOQUE4": 1,
                    "PRECO5": 89.9,
                    "ESTOQUE5": 1,
                    "FORNECEDOR": 5975,
                    "RAZAO_SOCIAL": "ROCA BRASIL LTDA"
                },
                {
                    "PRODUTO": 62898,
                    "EAN": 7891333038081,
                    "DESCRICAO": "EROS BACIA P CAIXA ACOP BRANCO INCEPA",
                    "PRECO1": 89.9,
                    "ESTOQUE1": 0,
                    "PRECO2": 89.9,
                    "ESTOQUE2": 0,
                    "PRECO3": 89.9,
                    "ESTOQUE3": 0,
                    "PRECO4": 89.9,
                    "ESTOQUE4": 1,
                    "PRECO5": 89.9,
                    "ESTOQUE5": 1,
                    "FORNECEDOR": 5975,
                    "RAZAO_SOCIAL": "ROCA BRASIL LTDA"
                }
            ]
        }
    ]
}
```