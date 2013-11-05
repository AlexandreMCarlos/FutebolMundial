<?php

Class processaProxFaseGeral{
	protected $faseActual;
	protected $proxFase;
	protected $compPrefix;
	protected $compAno;
	
	/*******************************************************************************************************************
	* Nome: __construct
	* Data: 23/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Constructor da classe. Instancia a fase com os dados necessários
	* Entrada: $faseActual - ID da fase actual
	* 		   $proxFase - ID da próxima fase
	* 		   $compPrefix - Competição à qual a fase diz respeito
	* 		   $compAno - Ano da competição
	* Saida: Não tem.
	*/	
	public function __construct($faseActual, $proxFase, $compPrefix, $compAno){
		$this->faseActual = $faseActual;
		$this->proxFase = $proxFase;
		$this->compPrefix = $compPrefix;
		$this->compAno = $compAno;
	}
	
	public function funGetFaseActual(){
		return $this->faseActual;
	}
	
	public function funGetProxFase(){
		return $this->proxFase;
	}
	
	public function funGetCompAno(){
		return $this->compAno;
	}
	
	public function funGetCompID(){
		return $this->compPrefix;
	}
	
	public function funGetCompPrefix(){
		return $this->compPrefix.$this->compAno;
	}
	
	public function funGetCompFaseActualID(){
		return $this->faseActual.'_'.$this->funGetCompPrefix();
	}
	
	public function funGetCompProxFaseID(){
		return $this->proxFase.'_'.$this->funGetCompPrefix();
	}
	
	/**********************************************************************/
	/* Faz o sorteio para a segunda fase do apuramento Asiático           */
	/* para o Mundial de Futebol. Trata da inserção na BD  				  */
	/**********************************************************************/
	public function funGetCompOrganizador(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return -1;
		}
		
		try{
			$competicao = $this->compPrefix.$this->compAno;
			$varBD->queryString = 'Select tab_ca_org From tab_comp_actual Where tab_ca_fase = :FASE And tab_ca_tipo = :COMP';		
			$varBD->parseDBQuery($varBD->queryString);		
			oci_bind_by_name($varBD->queryParse, ':FASE', $this->faseActual);
			oci_bind_by_name($varBD->queryParse, ':COMP', $competicao);
			
			oci_execute($varBD->queryParse);
			$row = oci_fetch_array($varBD->queryParse);
			$varBD->fechaLigacao(); 		
			unset($varBD);
			
			Return $row[0];
		}catch(Exception $e){
			unset($varBD);
			return -1;
		}
	}	
	
	public function funFechaFase($faseActual, $compActual){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
		
		try{
			$varBD->queryString = 'Update tab_comp_actual Set tab_ca_fim = -1 Where tab_ca_fase = :FASE And tab_ca_tipo = :COMP';		
			$varBD->parseDBQuery($varBD->queryString);
					
			oci_bind_by_name($varBD->queryParse, ':FASE', $faseActual);
			oci_bind_by_name($varBD->queryParse, ':COMP', $compActual);
			
			oci_execute($varBD->queryParse);
			$varBD->fechaLigacao(); 		
			unset($varBD);
			
			Return true;
		}catch (Exception $e){
			return false;
		}		
	}
}

?>