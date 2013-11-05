<?php

require_once ('classes/futebolMundialJogos.class.php');
require_once ('classes/executeDBOperations.class.php');

Class processaFMJogos extends futebolMundialJogos{

	private $jogoID;
	
	private $resCasa;
	private $resCasaProl;
	private $resCasaPen;
	
	private $resFora;
	private $resForaProl;
	private $resForaPen;
	
	private $vencedorJogo;
	
	protected $tipoJogo; //E - Eliminatória; P - Playoff; G - Grupo; A - Amigável
	protected $pontosJogo = array(); 

	/*******************************************************************************************************************/
	/*
	* Nome: __construct
	* Data: 25/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Constructor da classe. Instancia os jogos
	* Entrada: O ID do jogo e ambas as equipas: a que joga em 'casa' e a que joga 'fora'
	* Saida: Não tem.
	*/
	
	public function __construct($jogoID, $equipaCasa, $equipaFora){
		$this->jogoID = $jogoID;
		$grupoID = $this->funSetGrupoID();			
		parent::__construct($grupoID, $equipaCasa, $equipaFora);		
		//Aqui vai preencher qual o tipo de jogo: 
		//E - Eliminatória; P - Playoff; G - Grupo; A - Amigável
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		
		If ($varBD->funGetConnection() == null){
			echo "Não é possivel abrir a ligação com a BD.";
			unset($this);
			exit();
		}
		
		$varBD->queryString = 'Select tab_jogos_tipo From tab_jogos Where tab_jogos_id = :JOGOID';
		
		$varBD->parseDBQuery($varBD->queryString);
				
		oci_bind_by_name($varBD->queryParse, ':JOGOID', $jogoID);
		
		oci_execute($varBD->queryParse);
		
		$row = oci_fetch_array($varBD->queryParse);
		
		$this->tipoJogo = $row[0];
		
		$varBD->fechaLigacao();
		unset($varBD);
	}

	/*******************************************************************************************************************/
	/*
	* Nome: funGetTipoJogo
	* Data: 25/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Retorna o tipo de jogo: E - Eliminatória; P - Playoff; G - Grupo; A - Amigável 
	* Entrada: N/A
	* Saida: Tipo de jogo: E - Eliminatória; P - Playoff; G - Grupo; A - Amigável;
	*/
	public function funGetTipoJogo(){
		return $this->tipoJogo;
	}

	/*******************************************************************************************************************/
	/*
	* Nome: funSetResultado
	* Data: 25/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai inicializar as propriedades relativas ao prolongamento
	* Entrada: $resCasa - Resultado da equipa de casa ao fim do jogo
	*		 : $resFora - Resultado da equipa Fora ao fim do jogo 
	* Saida: Não tem;
	*/	
	public function funSetResultado($casa, $fora){
		$this->resCasa = $casa;
		$this->resFora = $fora;
	}
	
	/*******************************************************************************************************************/
	/*
	* Nome: funGetResultadoCasa
	* Data: 27/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Retorna o resultado da equipa que joga em casa
	* Entrada: N/A 
	* Saida: Número de golos marcados pela equipa que joga em casa;
	*/	
	public function funGetResultadoCasa(){
		return $this->resCasa;
	}	
	

	/*******************************************************************************************************************/
	/*
	* Nome: funGetResultadoFora
	* Data: 27/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Retorna o resultado da equipa que joga fora
	* Entrada: N/A 
	* Saida: Número de golos marcados pela equipa que joga fora;
	*/	
	public function funGetResultadoFora(){
		return $this->resFora;
	}


	/*******************************************************************************************************************/
	/*
	* Nome: funSetProlongamento
	* Data: 25/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai inicializar as propriedades relativas ao prolongamento
	* Entrada: $jogoId - Id do jogo em questão
	*		 : $resCasaProl - Resultado da equipa de casa ao fim do prolongamento
	*		 : $resForaProl - Resultado da equipa Fora ao fim do prolongamento 
	* Saida: Não tem;
	*/	
	public function funSetProlongamento($casaProl, $foraProl){
		$this->resCasaProl = $casaProl;
		$this->resForaProl = $foraProl;
	}
	
	
	/*******************************************************************************************************************/
	/*
	* Nome: funGetProlongamentoCasa
	* Data: 27/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Retorna o número de golos marcados no prolongamento pela equipa que joga em casa
	* Entrada: N/A;
	* Saida: Número de golos marcados no prolongamento pela equipa que joga em casa;
	*/	
	public function funGetProlongamentoCasa(){
		return $this->resCasaProl;
	}
	
	
	/*******************************************************************************************************************/
	/*
	* Nome: funGetProlongamentoFora
	* Data: 27/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Retorna o número de golos marcados no prolongamento pela equipa que joga fora
	* Entrada: N/A;
	* Saida: Número de golos marcados no prolongamento pela equipa que joga fora;
	*/	
	public function funGetProlongamentoFora(){
		return $this->resForaProl;
	}	
	
	
	
	/*******************************************************************************************************************/
	/*
	* Nome: funSetPenalties
	* Data: 25/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai inicializar as propriedades relativas ao prolongamento
	* Entrada: $resCasa - Resultado da equipa de casa ao fim dos penalties
	*		 : $resFora - Resultado da equipa Fora ao fim dos penalties 
	* Saida: Não tem;
	*/	
	public function funSetPenalties($casaPen, $foraPen){
		$this->resCasaPen = $casaPen;
		$this->resForaPen = $foraPen;
	}
	
	
	
	/*******************************************************************************************************************/
	/*
	* Nome: funGetPenaltiesCasa
	* Data: 27/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Retorna o número de golos marcados nos penalties pela equipa que joga em casa
	* Entrada: N/A;
	* Saida: Número de golos marcados nos penalties pela equipa que joga em casa;
	*/	
	public function funGetPenaltiesCasa(){
		return $this->resCasaPen;
	}
	
	
	/*******************************************************************************************************************/
	/*
	* Nome: funGetPenaltiesFora
	* Data: 27/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Retorna o número de golos marcados nos penalties pela equipa que joga fora
	* Entrada: N/A;
	* Saida: Número de golos marcados nos penalties pela equipa que joga fora;
	*/	
	public function funGetPenaltiesFora(){
		return $this->resForaPen;
	}		
	
	
	/*******************************************************************************************************************/
	/*
	* Nome: funGetCompeticao
	* Data: 25/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai indicar/determinar qual a competição q que o jogo diz respeito
	* Entrada: Não tem. Vai ser instanciado sobre o objecto processaFMJogos
	* Saida: Não tem.
	*/
	
	public function funGetCompeticao(){
		return substr($this->funGetGrupoJogo(), 0, 6);
	}
	

	/*******************************************************************************************************************/
	/*
	* Nome: funSetVencedorJogo
	* Data: 25/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai determinar qual o vencedor do jogo em questão e colocá-lo na propriedade $vencedorJogo
	* Entrada: Não tem. Vai ser instanciado sobre o objecto processaFMJogos
	* Saida: Código de Erro 0 se OK, -1 se NOK;
	* Observações: $vencedorJogo terá a equipa vencedora ou -1 em caso de empate;
	*/	
	
	public function funSetVencedorjogo(){		
		if (isset($this->resCasaPen) && (isset($this->resForaPen))){			
			if ($this->resCasaPen > $this->resForaPen){
				$this->vencedorJogo = $this->equipaCasa;
				$this->funSetPontos(2,1);
			}
			elseif ($this->resCasaPen < $this->resForaPen){
				$this->vencedorJogo = $this->equipaFora;
				$this->funSetPontos(1,2);
			}
			else
				return -1;
				
		}elseif (isset($this->resCasaProl) && (isset($this->resForaProl))){		
			if ($this->resCasaProl > $this->resForaProl){
				$this->vencedorJogo = $this->equipaCasa;
				$this->funSetPontos(3,0);
			}elseif ($this->resCasaProl < $this->resForaProl){
				$this->vencedorJogo = $this->equipaFora;
				$this->funSetPontos(0,3);			
			}else 
				return -1;			
		}else {			
			if ($this->resCasa > $this->resFora){				
				$this->vencedorJogo = $this->equipaCasa;
				$this->funSetPontos(3,0);
			}elseif ($this->resCasa < $this->resFora){
				$this->vencedorJogo = $this->equipaFora;				
				$this->funSetPontos(0,3);
			}else{ 
				$this->vencedorJogo = -1;
				$this->funSetPontos(1,1);
			}						
		}
		
		Return 0;  		
	}
	

	/*******************************************************************************************************************/
	/*
	* Nome: funGetVencedorJogo
	* Data: 25/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai retornar qual o vencedor do jogo em causa
	* Entrada: Não tem. Vai ser instanciado sobre o objecto processaFMJogos
	* Saida: $this->vencedorJogo se isset($this->vencedorjogo)/ -1 CC;
	* Observações: $vencedorJogo terá a equipa vencedora ou -1 em caso de empate;
	*/
	
	public function funGetVencedorjogo(){
		
		if (isset($this->vencedorJogo)){
			return $this->vencedorJogo;
		}else
			return -1;
	}
		

	/****************************************************************************************************/
	/* Secção de Funções auxiliares ao processamento dos jogos */
	/****************************************************************************************************/

	
	/*******************************************************************************************************************/
	/*
	* Nome: funGetGrupoJogo
	* Data: 25/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai retornar o grupo correespondente ao jogo actual
	* Entrada: Não tem. Vai ser instanciado sobre o objecto processaFMJogos
	* Saida: $grupoID do jogo em questão.
	*/
	
	public function funGetGrupoJogo(){
		Return $this->grupoID;
	}
	
	
	/*******************************************************************************************************************/
	/*
	* Nome: funSetPontos
	* Data: 25/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai colocar na propriedade pontosJogo os pontos de ambas as equipas
	* Entrada: $golosCasa - Golos marcados pela equipa que joga em casa,
	* 		 : $golosFora - Golos marcados pela equipa que joga fora
	* Saida: Não tem
	* Observações: N/A
	*/
	
	public function funSetPontos($pontosCasa, $pontosFora){		
		$this->pontosJogo['pontoscasa'] = $pontosCasa;
		$this->pontosJogo['pontosfora'] = $pontosFora;
	}
	
	
	/*******************************************************************************************************************/
	/*
	* Nome: funGetPontosCasa
	* Data: 25/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai retornar os pontos da equipa que joga em casa 
	* Entrada: N/A	
	* Saida: $pontosJogo['pontoscasa']
	* Observações: N/A
	*/
	
	public function funGetPontosCasa(){
		return $this->pontosJogo['pontoscasa'];
	}


	/*******************************************************************************************************************/
	/*
	* Nome: funGetPontosFora
	* Data: 25/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai retornar os pontos da equipa que joga fora 
	* Entrada: N/A	
	* Saida: $pontosJogo['pontosfora']
	* Observações: N/A
	*/
	
	public function funGetPontosFora(){
		return $this->pontosJogo['pontosfora'];
	}
	

	/*******************************************************************************************************************/
	/*
	* Nome: funJogoRealizado
	* Data: 29/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai determinar se o jogo já foi realizado ou não 
	* Entrada: N/A	
	* Saida: Código de erro: 0 se OK, -1 se NOT
	* Observações: N/A
	*/
	
	public function funJogoRealizado(){
		$returnCode;
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			echo "Não é possivel abrir a ligação com a BD.";
			exit();
		}
		
		$varBD->queryString = 'select count(1) from tab_jogos ';
		$varBD->queryString .= 'where tab_jogos_id = :JID and tab_jogos_data is not null';

		$varBD->parseDBQuery($varBD->queryString);
		
		oci_bind_by_name($varBD->queryParse, ':JID', $this->jogoID);
		
		oci_execute($varBD->queryParse);
		
		$row = oci_fetch_array($varBD->queryParse);
		
		$returnCode = $row[0];

		$varBD->fechaLigacao();
		
		unset($varBD);
		
		If ($returnCode == 0)
			Return false;
		Else return true;		
	}	

	/*******************************************************************************************************************/
	/* ROTINAS DE MANIPULAÇÃO DE DADOS PARA INSERÇÃO DE VALORES NA BASE DE DADOS									   */
	/*******************************************************************************************************************/
	

	/*******************************************************************************************************************/
	/*
	* Nome: funActualizaDados
	* Data: 25/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai fazer a inserção (update) dos dados na BD 
	* Entrada: N/A	
	* Saida: Código de erro: 0 se OK, -1 se NOT
	* Observações: N/A
	*/
	
	public function funActualizaDados(){

		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();		

		If ($varBD->funGetConnection() == null){
			echo "Não é possivel abrir a ligação com a BD.";
			exit();
		}	
		
		$varBD->queryString = 'Update tab_jogos set ';
		If (isset($this->resCasaPen) && isset($this->resForaPen)){
			$varBD->queryString .= 'tab_jogos_res_casa_pen = :CASAPEN, tab_jogos_res_fora_pen = :FORAPEN, tab_jogos_pen = 1'; 
		}
		
		If (isset($this->resCasaProl) && isset($this->resForaProl)){
			if (strlen($varBD->queryString) > 22)
				$varBD->queryString .= ', '; 
			$varBD->queryString .= 'tab_jogos_res_casa_prol = :CASAPROL, tab_jogos_res_fora_prol = :FORAPROL, tab_jogos_prol = 1'; 
		}
		
		if (strlen($varBD->queryString) > 22)
			$varBD->queryString .= ', '; 
		$varBD->queryString .= ' tab_jogos_res_casa = :RESCASA, tab_jogos_res_fora = :RESFORA';
		$varBD->queryString .= ', tab_jogos_data = trunc(sysdate)';
		$varBD->queryString .= ' Where tab_jogos_id = :JOGOID And tab_jogos_equipa_casa = :EQUC ';
		$varBD->queryString .= 'And tab_jogos_equipa_fora = :EQUF';		
		$varBD->parseDBQuery($varBD->queryString);
		
		oci_bind_by_name($varBD->queryParse, ':RESCASA', $this->resCasa);
		oci_bind_by_name($varBD->queryParse, ':RESFORA', $this->resFora);
		
		If (isset($this->resCasaProl) && isset($this->resForaProl)){
			oci_bind_by_name($varBD->queryParse, ':CASAPROL', $this->resCasaProl);
			oci_bind_by_name($varBD->queryParse, ':FORAPROL', $this->resForaProl);
		}
		 
		If (isset($this->resCasaPen) && isset($this->resForaPen)){
			oci_bind_by_name($varBD->queryParse, ':CASAPEN', $this->resCasaPen);
			oci_bind_by_name($varBD->queryParse, ':FORAPEN', $this->resForaPen);				
		}

		oci_bind_by_name($varBD->queryParse, ':JOGOID', $this->jogoID);
		oci_bind_by_name($varBD->queryParse, ':EQUC', $this->equipaCasa);
		oci_bind_by_name($varBD->queryParse, ':EQUF', $this->equipaFora);
		oci_execute($varBD->queryParse);

		$varBD->fechaLigacao();	
		
		unset($varBD);				
	}

	/*******************************************************************************************************************/
	/*
	* Nome: funSetGrupoID
	* Data: 20/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai determinar qual o grupo a que o jogo pertence baseado no ID do jogo
	* Entrada: N/A
	* Saida: N/A
	*/
	
	protected function funSetGrupoID(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();		

		If ($varBD->funGetConnection() == null){
			echo "Não é possivel abrir a ligação com a BD.";
			unset($this);
			exit();
		}
		
		$varBD->queryString = 'Select tab_jogos_grupo_id From tab_jogos Where tab_jogos_id = :JOGO';
		$varBD->parseDBQuery($varBD->queryString);
		
		oci_bind_by_name($varBD->queryParse, ':JOGO', $this->jogoID);
		oci_execute($varBD->queryParse);
		
		$row = oci_fetch_array($varBD->queryParse);
		$returnCode = $row[0];
		$varBD->fechaLigacao();
		
		unset($varBD);
		
		Return $returnCode;						
	}
	
	/*******************************************************************************************************************/
	/*
	* Nome: funGetGrupoID
	* Data: 20/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai devolver o grupo a que o jogo pertence baseado no ID do jogo
	* Entrada: N/A
	* Saida: N/A
	*/
	public function funGetGrupoID(){
		Return $this->grupoID;
	}	
}

?>