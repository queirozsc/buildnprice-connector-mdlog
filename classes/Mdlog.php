<?php
class Mdlog {
  private $_db,
    $_data,
    $_ncc,
    $_empresa,
    $_pedidos,
    $_produtos,
    $_areas,
    $_cdId;

  public function __construct($empresa='p'){
    $this->_db = DB::getInstance('mdlog'.$empresa);
    $this->_empresa = $empresa;
  }

  public function preencheCombo($tabela,$parent=null) {
    switch ($tabela) {
      case 'categoria':
        $query = "SELECT X.T486_CONTA CODIGO, X.T486_DESCRICAO DESCRICAO FROM T486_CATEGORIA_PRODUTO X WHERE LENGTH(X.T486_CONTA) = 2 ORDER BY 2";
        break;
      case 'subcategoria':
        $query = "SELECT X.T486_CONTA CODIGO, X.T486_DESCRICAO DESCRICAO FROM T486_CATEGORIA_PRODUTO X WHERE LENGTH(X.T486_CONTA) = 4";
        if ($parent) {
          $query.= "AND X.T486_CONTA LIKE '$parent%'";
        }
        $query.= " ORDER BY 1";
        break;
      case 'cat.analitica':
        $query = "SELECT X.T486_CONTA CODIGO, X.T486_DESCRICAO DESCRICAO FROM T486_CATEGORIA_PRODUTO X WHERE LENGTH(X.T486_CONTA) > 4";
        if ($parent) {
          $query.= "AND X.T486_CONTA LIKE '$parent%'";
        }
        $query.= " ORDER BY 1";
        break;
      case 'segmento':
        $query = "SELECT Y.T301_SEGMENTO_IU CODIGO, Y.T301_DESCRICAO DESCRICAO FROM T301_SEGMENTO Y ORDER BY 2";
        break;
      case 'marca':
        $query = "SELECT W.T303_MARCA_IU CODIGO, W.T303_DESCRICAO DESCRICAO FROM T303_MARCA W ORDER BY 2";
        break;
      case 'industria':
        $query = "SELECT A.T019_FORNECEDOR_IU CODIGO, A. T019_RAZAO_SOCIAL DESCRICAO FROM T019_FORNECEDOR A ORDER BY 2";
        break;
      case 'formapagto':
        $query = "SELECT FP.T224_COD_INTEGRA_SOFTVAR_U CODIGO, UPPER(FP.T224_DESCRICAO) DESCRICAO FROM T224_FORMA_PAGAMENTO FP ORDER BY 2";
        break;
      default:
        # code...
        break;
    }
    //die($query);
    $this->_data = $this->_db->query($query)->results();
    if (count($this->_data)) {
      return $this->_data;
    }
    return null;
  }

  public function consultaProdutos($produto, $unidade=null, $categoria=null, $limite=5000){
    if (is_null($unidade) || $unidade == '100' || $unidade == '200' || empty($unidade)) { // vai ficar fixo o custo de entrada no CD
      $unidade = ($this->_empresa=='p') ? '108' : '203';
    }
    if ($produto) {
      $query = "";
      $query .= "SELECT ";
      $query .= "TO_CHAR(A.T076_PRODUTO_IU) AS PRODUTO ";
      $query .= ", TO_CHAR(A.T076_CODIGO_BARRA) AS EAN ";
      $query .= ", A.T076_DESCRICAO AS DESCRICAO ";
      $query .= ", A.T076_UNIDADE_VENDA AS UN ";
      $query .= ", B.T502_COD_BARRAS_IU AS EAN2 ";
      $query .= ", C.T077_UNIDADE_IE UNIDADE ";
      $query .= ", A.T076_NCM_E AS NCM ";
      $query .= ", C.T077_CUSTO_COMPRA AS CUSTO ";
      $query .= ", FDAT_SALDO_ESTOQUE_RV(A.T076_PRODUTO_IU, C.T077_UNIDADE_IE, 2) AS DISPONIVEL ";
      $query .= ", C.T077_PRECO_VENDA AS PVENDA ";
      $query .= ", E.T486_CONTA COD_CATEGORIA ";
      $query .= ", E.T486_DESCRICAO CATEGORIA ";
      $query .= ", H.T301_SEGMENTO_IU COD_SEGMENTO ";
      $query .= ", H.T301_DESCRICAO SEGMENTO ";
      $query .= ", A.T076_INDUSTRIA_E FID ";
      $query .= ", F.T019_NOME FORNECEDOR ";
      $query .= ", CASE";
      $query .= " WHEN TO_CHAR(B.T502_DATA_CADASTRO,'YYYY-MM-DD') = TO_CHAR(SYSDATE,'YYYY-MM-DD') THEN '1' ELSE '0'";
      $query .= " END AS OPERACAO ";
      $query .= ", NVL(A.T076_BLOQUEADO_VENDA,'N') BLOQUEADO ";
      $query .= ", NVL(C.T077_PRODUTO_ATIVO,'I') STATUS ";
      $query .= ", C.T077_PRECO_PROM AS PPROMO";
      $query .= ", CASE WHEN TRUNC(SYSDATE) >= TRUNC(C.T077_DT_INICIO_PROM) AND TRUNC(SYSDATE) <= TRUNC(C.T077_DT_FIM_PROM) THEN 'S' ELSE 'N' END AS PROMOCAO ";
      $query .= "FROM T076_PRODUTO A ";
      $query .= " LEFT JOIN T502_PROD_EMB_COD_CODBARRAS B";
      $query .= " ON B.T502_PRODUTO_IE = A.T076_PRODUTO_IU ";
      $query .= " LEFT JOIN T077_PRODUTO_UNIDADE C ";
      $query .= " ON A.T076_PRODUTO_IU = C.T077_PRODUTO_IE ";
      $query.= "  LEFT JOIN DBAMDATA.T486_CATEGORIA_PRODUTO E ";
      $query.= "  ON E.T486_CATEGORIA_IU = A.T076_CATEGORIA_E ";
      $query.= "  LEFT JOIN DBAMDATA.T301_SEGMENTO H ";
      $query.= "  ON H.T301_SEGMENTO_IU = A.T076_SEGMENTO_E ";
      $query.= "  LEFT JOIN DBAMDATA.T019_FORNECEDOR F";
      $query.= "  ON F.T019_FORNECEDOR_IU = A.T076_INDUSTRIA_E";
      /*$query.= "  LEFT JOIN (SELECT PRODUTO, CADASTRO, ATUAL, CASE";
      $query.= "    WHEN CADASTRO = ATUAL THEN '1' ELSE '0'";
      $query.= "    END OPERACAO";
      $query.= "  FROM (";
      $query.= "  SELECT DISTINCT A.T076_PRODUTO_IU AS PRODUTO, TO_CHAR(B.T502_DATA_CADASTRO,'YYYY-MM-DD') AS CADASTRO, TO_CHAR(SYSDATE,'YYYY-MM-DD') AS ATUAL";
      $query.= "  FROM T076_PRODUTO A";
      $query.= "    LEFT JOIN T502_PROD_EMB_COD_CODBARRAS B";
      $query.= "    ON B.T502_PRODUTO_IE = A.T076_PRODUTO_IU ";
      $query.= "  )) G ";
      $query.= "  ON G.PRODUTO = A.T076_PRODUTO_IU";*/
      //$query.= " WHERE NVL(A.T076_BLOQUEADO_VENDA,'N') = 'N' ";
      //$query.= " AND NVL(C.T077_PRODUTO_ATIVO,'I') = 'A' ";
      $query .= " WHERE C.T077_UNIDADE_IE = ".$unidade;
      if (is_numeric($produto)) {
        $query .= " AND ((A.T076_PRODUTO_IU = ".$produto.") OR (A.T076_CODIGO_BARRA = ".$produto.") OR (B.T502_COD_BARRAS_IU = ".$produto.")) ";
      } else {
        $produto = strtoupper($produto);
        $query .= " AND (A.T076_DESCRICAO LIKE '%". str_replace(' ', '%', $produto) ."%') ";
      }
      if ($categoria) {
        $query .= " AND (E.T486_CONTA LIKE '$categoria%') ";
      }
      if ($limite) {
        $query .= "AND (ROWNUM <= $limite) ";
      }
      $query .= "ORDER BY 1";
      // die($query);
      $this->_produtos = $this->_db->query($query)->results();
      if (count($this->_produtos)) {
        return true;
      }
    }
    return false;
  }

  public function consultaPrecoEstoque($produto, $fornecedor=null, $limite=100) {
    $query = "";
    $query .= "SELECT ";
    $query .= "A.T076_PRODUTO_IU PRODUTO ";
    $query .= ", NVL(C.T502_COD_BARRAS_IU, A.T076_CODIGO_BARRA) EAN ";
    $query .= ", A.T076_DESCRICAO DESCRICAO ";
    if ($this->_empresa=='p') {
      $query .= ", (SELECT P.T077_PRECO_VENDA FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 101 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS PRECO1 ";
      $query .= ", (SELECT P.T077_SALDO_ESTOQUE FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 101 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS ESTOQUE1 ";
      $query .= ", (SELECT P.T077_PRECO_VENDA FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 102 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS PRECO2 ";
      $query .= ", (SELECT P.T077_SALDO_ESTOQUE FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 102 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS ESTOQUE2 ";
      $query .= ", (SELECT P.T077_PRECO_VENDA FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 106 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS PRECO3 ";
      $query .= ", (SELECT P.T077_SALDO_ESTOQUE FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 106 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS ESTOQUE3 ";
      $query .= ", (SELECT P.T077_PRECO_VENDA FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 107 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS PRECO4 ";
      $query .= ", (SELECT P.T077_SALDO_ESTOQUE FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 107 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS ESTOQUE4 ";
      $query .= ", (SELECT P.T077_PRECO_VENDA FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 108 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS PRECO5 ";
      $query .= ", (SELECT P.T077_SALDO_ESTOQUE FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 108 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS ESTOQUE5 ";
    } else {
      $query .= ", (SELECT P.T077_PRECO_VENDA FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 201 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS PRECO1 ";
      $query .= ", (SELECT P.T077_SALDO_ESTOQUE FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 201 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS ESTOQUE1 ";
      $query .= ", (SELECT P.T077_PRECO_VENDA FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 202 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS PRECO2 ";
      $query .= ", (SELECT P.T077_SALDO_ESTOQUE FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 202 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS ESTOQUE2 ";
      $query .= ", (SELECT P.T077_PRECO_VENDA FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 203 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS PRECO3 ";
      $query .= ", (SELECT P.T077_SALDO_ESTOQUE FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 203 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS ESTOQUE3 ";
      $query .= ", (SELECT P.T077_PRECO_VENDA FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 204 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS PRECO4 ";
      $query .= ", (SELECT P.T077_SALDO_ESTOQUE FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 204 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS ESTOQUE4 ";
      $query .= ", (SELECT P.T077_PRECO_VENDA FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 205 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS PRECO5 ";
      $query .= ", (SELECT P.T077_SALDO_ESTOQUE FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 205 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS ESTOQUE5 ";
      $query .= ", (SELECT P.T077_PRECO_VENDA FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 206 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS PRECO6 ";
      $query .= ", (SELECT P.T077_SALDO_ESTOQUE FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 206 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS ESTOQUE6 ";
      $query .= ", (SELECT P.T077_PRECO_VENDA FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 200 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS PRECO7 ";
      $query .= ", (SELECT P.T077_SALDO_ESTOQUE FROM T077_PRODUTO_UNIDADE P WHERE P.T077_UNIDADE_IE = 200 AND P.T077_PRODUTO_IE = A.T076_PRODUTO_IU) AS ESTOQUE7 ";
    }
    $query .= ", A.T076_INDUSTRIA_E FORNECEDOR ";
    $query .= ", B.T019_RAZAO_SOCIAL RAZAO_SOCIAL ";
    $query .= "FROM DBAMDATA.T076_PRODUTO A ";
    $query .= " LEFT JOIN DBAMDATA.T019_FORNECEDOR B ";
    $query .= " ON B.T019_FORNECEDOR_IU = A.T076_INDUSTRIA_E ";
    $query .= " LEFT JOIN DBAMDATA.T502_PROD_EMB_COD_CODBARRAS C ";
    $query .= " ON C.T502_PRODUTO_IE = A.T076_PRODUTO_IU ";
    $query .= "WHERE NVL(A.T076_BLOQUEADO_VENDA,'N') = 'N' ";
    $query .= "AND EXISTS (";
    $query .= " SELECT *";
    $query .= " FROM T077_PRODUTO_UNIDADE PU";
    $query .= " WHERE NVL(PU.T077_PRODUTO_ATIVO,'I') = 'A'";
    $query .= " AND PU.T077_PRODUTO_IE = A.T076_PRODUTO_IU ";
    $query .= ") ";
    if ($produto) {
      if (is_numeric($produto)) {
        $query .= "AND (A.T076_PRODUTO_IU = $produto OR A.T076_CODIGO_BARRA = '$produto' OR C.T502_COD_BARRAS_IU = '$produto') ";
      } else {
        $query .= "AND A.T076_DESCRICAO LIKE '%".strtoupper(str_replace(' ', '%', $produto))."%'  ";
      }
      $where = true;
    }
    if ($fornecedor) {
      if (is_numeric($fornecedor)) {
        $query .= "AND (A.T076_INDUSTRIA_E = $fornecedor OR B.T019_CGC = '$fornecedor') ";
      } else {
        $query .= "AND B.T019_NOME LIKE '%".strtoupper(str_replace(' ', '%', $fornecedor))."%' ";
      }
    }
    $query .= "AND (ROWNUM <= ".$limite.") "; // limita resultado
    $query .= "ORDER BY A.T076_INDUSTRIA_E, A.T076_PRODUTO_IU";
    $this->_data = $this->_db->query($query)->results();
    if (count($this->_data)) {
      return true;
    }
    return false;
  }

  public function listaProdutosReduzido($produto, $fornecedor, $segmento, $filial = null){
    $query = "";
    $filial = $filial ?: $this->_cdId;
    /*código, descrição, un, ean, fornecedor, segmento, preço, estoque, filial*/
    $query.= "SELECT";
    $query.= " S.T301_DESCRICAO department,";
    $query.= "  F.T019_NOME supplier,";
    $query.= "  P.T076_PRODUTO_IU code,";
    $query.= "  P.T076_DESCRICAO description,";
    $query.= "  P.T076_UNIDADE_VENDA measure,";
    $query.= "  P.T076_CODIGO_BARRA barcode,";
    $query.= "  U.T077_PRECO_VENDA price,";
    $query.= "  FDAT_SALDO_ESTOQUE_RV(";
    $query.= "    P.T076_PRODUTO_IU,";
    $query.= "    U.T077_UNIDADE_IE,";
    $query.= "    2";
    $query.= "  ) balance,";
    $query.= "  U.T077_UNIDADE_IE warehouse ";
    $query.= "FROM";
    $query.= "  DBAMDATA.T076_PRODUTO P ";
    $query.= "INNER JOIN DBAMDATA.T077_PRODUTO_UNIDADE U ON";
    $query.= "  U.T077_PRODUTO_IE = P.T076_PRODUTO_IU";
    $query.= "  AND U.T077_UNIDADE_IE = $filial ";
    $query.= "INNER JOIN DBAMDATA.T301_SEGMENTO S ON";
    $query.= "  S.T301_SEGMENTO_IU = P.T076_SEGMENTO_E ";
    $query.= "INNER JOIN DBAMDATA.T019_FORNECEDOR F ON";
    $query.= "  F.T019_FORNECEDOR_IU = P.T076_INDUSTRIA_E ";
    $query.= "WHERE";
    $query.= "  NVL( P.T076_BLOQUEADO_VENDA, 'N' )= 'N'";
    $query.= "  AND NVL( U.T077_PRODUTO_ATIVO, 'A' )= 'A' ";
    if ($produto) {
      if(is_numeric($produto)){
        $query.= "AND (P.T076_PRODUTO_IU = $produto OR P.C.T076_CODIGO_BARRA = $produto) ";
      } else {
        $query.= "AND P.T076_DESCRICAO LIKE '%".strtoupper(str_replace(' ','%',$produto))."%' ";
      }
    }
    if ($fornecedor) {
      if(is_numeric($fornecedor)){
        $query.= "AND F.T019_FORNECEDOR_IU = $fornecedor ";
      } else {
        $query.= "AND F.T019_NOME LIKE '%".strtoupper(str_replace(' ','%',$fornecedor))."%' ";
      }
    }
    if ($segmento) {
      if(is_numeric($segmento)){
        $query.= "AND S.T301_SEGMENTO_IU = $segmento ";
      } else {
        $query.= "AND S.T301_DESCRICAO LIKE '%".strtoupper(str_replace(' ','%',$segmento))."%' ";
      }
    }
    $query.= "ORDER BY 1, 2, 4 ASC";
    // die($query);
    $this->_data = $this->_db->query($query)->results();
    if(count($this->_data)){
      return true;
    }
    return false;
  }