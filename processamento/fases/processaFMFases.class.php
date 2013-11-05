<?php

require_once ('classes/executeDBOperations.class.php');

Class processaFMFases{
	
	protected $faseActual;
	protected $proxFase;
	protected $confComp;
	protected $compID;
	protected $compAno;	
	public $erroProc = 0;
	
	
	/*******************************************************************************************************************/
	/*
	* Nome: __construct
	* Data: 23/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Constructor da classe. Instancia a fase com os dados necessários
	* Entrada: $faseID - ID da fase actual
	* 		   $compID - Competição à qual a fase diz respeito
	* Saida: Não tem.
	*/
	
	public function __construct($faseID, $compID){
		$this->faseActual = $faseID;
		$this->compID = substr($compID, 0, 3);
		$this->compAno = substr($compID, 3, 4);
		
		$this->erroProc = $this->funGetConfProxFase();
		If ($this->erroProc){
			$this->erroProc = $this->processaFaseSeguinte();
		}		
	}
	

	/*******************************************************************************************************************/
	/*
	* Nome: funGetConfProxFase
	* Data: 23/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai determinar a confederação e a próxima fase
	* Entrada: N/A
	* Saida: N/A
	*/		
	protected function funGetConfProxFase(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}

		$varBD->queryString = 'select tab_competicao_conf, nvl(tab_competicao_prox_fase, 0) from tab_competicao';
		$varBD->queryString .= ' where tab_competicao_fase = :FASE and tab_competicao_pref = :PREF';
		$varBD->queryString .= ' and tab_competicao_act = 1';
        $varBD->parseDBQuery($varBD->queryString);		
		oci_bind_by_name($varBD->queryParse, ':FASE', $this->faseActual);
		oci_bind_by_name($varBD->queryParse, ':PREF', $this->compID);
		
		oci_execute($varBD->queryParse);
		
		$row = oci_fetch_array($varBD->queryParse);
		$this->confComp = $row[0];
		$this->proxFase = $row[1];
		$varBD->fechaLigacao();
		unset($varBD);
	
		return true;				
	}
	
	/*******************************************************************************************************************/
	/*
	* Nome: processaFaseSeguinte
	* Data: 23/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai processar a construcção da fase seguinte 
	* Entrada: N/A
	* Saida: N/A
	*/
	protected function processaFaseSeguinte(){		
		switch($this->confComp){
			case 1:
				require_once ('processaAsiaProxFase.class.php');
				$faseAsia = new processaAsiaProxFase($this->faseActual, $this->proxFase, $this->compID, $this->compAno);
				$returnCode = $faseAsia->funProcessaProxFase();
				break;
			case 2:
				require_once ('processaAfricaProxFase.class.php');
				$faseAfrica = new processaAfricaProxFase($this->faseActual, $this->proxFase, $this->compID, $this->compAno);
				$returnCode = $faseAfrica->funProcessaProxFase();
				break;
			case 3:
				require_once ('processaAmNorProxFase.class.php');
				$faseAmNor = new processaAmNorProxFase($this->faseActual, $this->proxFase, $this->compID, $this->compAno);
				$returnCode = $faseAmNor->funProcessaProxFase();
				break;				
			case 4:
				require_once ('processaAmSulProxFase.class.php');
				$faseAmSul = new processaAmSulProxFase($this->faseActual, $this->proxFase, $this->compID, $this->compAno);
				$returnCode = $faseAmSul->funProcessaProxFase();
				break;				
			case 6:
				require_once ('processaEuroProxFase.class.php');
				$faseEuro = new processaEuroProxFase($this->faseActual, $this->proxFase, $this->compID, $this->compAno);
				$returnCode = $faseEuro->funProcessaProxFase();
				break;				
			case 5:
				require_once ('processaOFCProxFase.class.php');
				$faseOFC = new processaOFCProxFase($this->faseActual, $this->proxFase, $this->compID, $this->compAno);
				$returnCode = $faseOFC->funProcessaProxFase();
				break;				
			case 7:
				require_once ('processaFIFAProxFase.class.php');
				$faseFIFA = new processaFIFAProxFase($this->faseActual, $this->proxFase, $this->compID, $this->compAno);
				$returnCode = $faseFIFA->funProcessaProxFase();
				break;				
			default:
				$returnCode = false;
				break;								
		}

		return $returnCode;
	} 	
	
}

?>