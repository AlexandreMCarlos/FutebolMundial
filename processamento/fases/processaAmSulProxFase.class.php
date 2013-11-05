<?php

require_once('processaProxFaseGeral.class.php');
require_once ('classes/executeDBOperations.class.php');
require_once ('classes/compGrupos.class.php');
require_once ('classes/funcoesAuxPlf.class.php');

Class processaAmSulProxFase Extends processaProxFaseGeral{
	
	protected $amSulConfID = 4;
	protected $amSulFase = array();
	//protected $amSulPlayOff = array();
	//protected $repescagem = array();
	protected $paisIsento;
	private $faseAmSul;
	private $faseGeral = 'MAM';
	
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
		parent::__construct($faseActual, $proxFase, $compPrefix, $compAno);
		$this->funGetPrimFase();
		$this->paisIsento = $this->faseAmSul.'_'.$this->funGetCompPrefix().'_GRUPO_A';
	}
	
	/*******************************************************************************************************************
	* Nome: funProcessaProxFase
	* Data: 23/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Construir a fase seguinte
	* Entrada: N/A
	* Saida: N/A
	*/	
	public function funProcessaProxFase(){	
		Try{
			switch($this->faseActual){
				case 'MAM_01': //Vai apurar directamente 3 selecções e gerar o playoff seguinte
					$returnCode = $this->funTrataPrimFaseAmSul();
					break;
				case 'MAM_02': // Pré apura os 2ºs classificados dos grupos B e C
					$returnCode = $this->funTrataSegFaseAmSul(); //1ª fase playoff
					break;
				case 'MAM_03': // O pré apurado da fase anterior fará um playOff com o 2º class do grupo A
					$returnCode = $this->funTrataApuramentoFinalAmSul(); 
					break;
				case 'MAM_04': // Vai processar o apuramento sul americano
					$returnCode = $this->funTrataApuramentoAmSul();
					break;
				default: 
					$returnCode = false;
					break;								
			}	
			
			return $returnCode;			
		}catch(Exception $e){
			return false;
		}	
	}
	
	/**********************************************************************/
	/* Retorna a confederação do organizador da competicao		          */
	/**********************************************************************/
	private function funGetOrgConf(){
		$competicao = $this->funGetCompPrefix();
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return -1;
		}

		try{
			$varBD->queryString = 'select te.tab_equipas_conf from tab_comp_actual tcc, tab_equipas te';
			$varBD->queryString .= ' where tcc.tab_ca_fase = :FASE and tcc.tab_ca_tipo = :COMP';
			$varBD->queryString .= ' and tcc.tab_ca_org = te.tab_equipas_trig';
			
	        $varBD->parseDBQuery($varBD->queryString);		
			oci_bind_by_name($varBD->queryParse, ':FASE', $this->faseActual);
			oci_bind_by_name($varBD->queryParse, ':COMP', $competicao);
			
			oci_execute($varBD->queryParse);
			
			$row = oci_fetch_array($varBD->queryParse);
			$confOrgComp = $row[0];		
			$varBD->fechaLigacao();
			unset($varBD);
			
			return $confOrgComp;
			
		}catch(Exception $e){
			return -1;
		}	
	}
	
	/**********************************************************************/
	/* Faz o tratamento dos apurados dos vários grupos e trata de         */
	/* preencher o array $amSulFase									  	  */
	/**********************************************************************/
	private function funGetApuradosAmSul($uefaApurados){
		/*$euroPrimClass = array();
		$euroSegClass = array();*/
		$comp = $this->funGetCompFaseActualID();
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}

		try{
			$varBD->queryString = 'Select tab_grupos_equipa From tab_grupos ';
			$varBD->queryString .= 'Where substr(tab_grupos_id, 1, 14) = :GRP ';			
			$varBD->queryString .= 'and tab_grupos_class <= :PRA order by tab_grupos_class, tab_grupos_id';

	        $varBD->parseDBQuery($varBD->queryString);		
			oci_bind_by_name($varBD->queryParse, ':GRP', $comp);
			oci_bind_by_name($varBD->queryParse, ':PRA', $uefaApurados);
			
			oci_execute($varBD->queryParse);
			
			While ($row = oci_fetch_array($varBD->queryParse)){
				array_push($this->amSulFase, $row[0]);						
			}
			
			$varBD->fechaLigacao();
			unset($varBD);
			return true;
		
		}catch(Exception $e){
			return false;
		}	
	}
	
	
	/**********************************************************************/
	/* Faz o tratamento dos apurados dos vários grupos e trata de         */
	/* preencher o array $amSulFase									  	  */
	/**********************************************************************/
	protected function funTrataPrimFaseAmSul(){
		$apurados = 1;
		$orgConf = $this->funGetOrgConf();
		$returnCode = true;		
		
		If ($orgConf != -1){
			//Vou buscar os apurados da primeira fase
		   	If ($this->funGetApuradosAmSul($apurados)){		   				   		
				If ($orgConf != $this->amSulConfID){//Am do Sul não organiza o Mundial de Futebol
					$returnCode = $this->funGeraPrimeiraFasePlayOff();		
				}
				//Vai inserir os paises apurados na tab_fifa_wc
				If ($returnCode){
					$returnCode = $this->funFechaFase($this->faseActual, $this->compPrefix.$this->compAno);
					if ($returnCode){
						$anoComp = $this->funGetCompAno();	
						For($index = 0; $index < sizeof($this->amSulFase); $index++){						
							$returnCode = $this->funSetApuradosAmSul($this->amSulFase[$index], $anoComp);
						}
					}
				}
			}
		} 	
	}
			
	/******************************************************************/
	/* Regista o vencedor do playOff como apurado				      */
	/* Chama package na BD que coloca apurado na tablea tab_fifa_wc	  */
	/******************************************************************/
	protected function funSetApuradosAmSul($apurado, $compAno){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
		
		try{			
			$varBD->queryString = 'Begin :retVal := package_mundial.funInsAprMundial(:APR, :ANO); End;';		
			$varBD->parseDBQuery($varBD->queryString);
					
			oci_bind_by_name($varBD->queryParse, ':retVal', $returnCode, 50);
			oci_bind_by_name($varBD->queryParse, ':APR', $apurado);
			oci_bind_by_name($varBD->queryParse, ':ANO', $this->compAno);
			
			oci_execute($varBD->queryParse);
			
			$varBD->fechaLigacao(); 		
			unset($varBD);
			
			return $returnCode;
		}catch(Exception $e){
			return false;
		}
		
	}
	
/***********************************************************************************************************/
/* Vai buscar o prefixo da fase de apuramento da Am. do Sul.											   */							
/***********************************************************************************************************/
	protected function funGetPrimFase(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
		
		try{			
			$varBD->queryString = 'select tab_competicao_fase from tab_competicao'; 
			$varBD->queryString .= ' where substr(tab_competicao_fase, 1, 3) = :FASE'; 
			$varBD->queryString .= ' and tab_competicao_act = 1 and tab_competicao_fase_ant is null';		
			$varBD->parseDBQuery($varBD->queryString);	

			oci_bind_by_name($varBD->queryParse, ':FASE', $this->faseGeral);							
			
			oci_execute($varBD->queryParse);

			$row = oci_fetch_array($varBD->queryParse);
			$this->funSetFase($row[0]);
			
			$varBD->fechaLigacao(); 		
			unset($varBD);
			
			return true;
		}catch(Exception $e){
			return false;
		}
		
	}
		
	private function funSetFase($fase){
		$this->faseAmSul = $fase;
	}
	
	
/***********************************************************************************************************/
/* TRATAMENTO DO APURAMENTO EUROPEU COM PLAYOFFS														   */							
/***********************************************************************************************************/


/***********************************************************************************************************/
/* UTILIZADA PARA DEVOLVER OS APURADOS DE UM PLAYOFF													   */							
/***********************************************************************************************************/
	private function funGetPreApuradosPlayOffAmSul(){
		/*$apurado = array();*/
		require_once('processamento/elims/processaFMEliminatorias.class.php');
		$elim = new processaFMEliminatorias($this->faseActual, $this->compPrefix, $this->compAno);
		$this->amSulPlayOff = $elim->tratamentoPlayOff();		
		return true;
	}

/***********************************************************************************************************/
/* TRATA DE GERAR A PRIMEIRA FASE DO PLAYOFF															   */
/***********************************************************************************************************/
	protected function funGeraPrimeiraFasePlayOff(){
		$gruposAmSul = $this->funGetCompFaseActualID();
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
		
		try{
			$varBD->queryString = 'select tab_grupos_equipa ';
			$varBD->queryString .= 'from tab_grupos where tab_grupos_class = 2 ';			
			$varBD->queryString .= 'and tab_grupos_id <> :GRP and substr(tab_grupos_id, 1, 14) = :FASE';			
			$varBD->parseDBQuery($varBD->queryString);
			oci_bind_by_name($varBD->queryParse, ':GRP', $this->paisIsento);
			oci_bind_by_name($varBD->queryParse, ':FASE', $gruposAmSul);
			oci_execute($varBD->queryParse);
			While ($row = oci_fetch_array($varBD->queryParse)){		
				array_push($this->amSulPlayOff, $row[0]);			
			}
			
			$returnCode = $this->funSorteiaPlayOffAmSul();
			
			return $returnCode;
			
		}catch(Exception $e){
			return false;
		}
		
	}
	
	/***********************************************************************/
	/* Vai sortear a primeira fase do PlayOff Europeu					   */
	/***********************************************************************/
	protected function funSorteiaPlayOffAmSul(){
		$faseGrupos = array ('A');
			
		try{
			require_once ('classes/compGrupos.class.php');
			require_once ('classes/procedimentosSorteio.class.php');
			//Terei que ir à BD buscar o organizador da competicao
			$organizador = $this->funGetCompOrganizador();
			
			If ($organizador != -1){
				$sorteio = new procedimentosSorteio();
				$returnCode = $sorteio->insereCompeticaoActual($this->proxFase, $this->compPrefix.$this->compAno, $organizador);
				
				if (!$returnCode){
					shuffle($this->amSulPlayOff);
					$this->amSulPlayOff = array_chunk($this->amSulPlayOff, 1);										
					for($i = 0; $i < sizeof($this->amSulPlayOff[0]); $i++){
						$varGrupo = new compGrupos('GRUPO_'.$faseGrupos[$i], $this->proxFase, $this->compPrefix, $this->compAno);
						for ($j = 0; $j < sizeof($this->amSulPlayOff); $j++){							
							$varGrupo->funSetEquipaGrupo($this->amSulPlayOff[$j][$i]);
						}
						$returnCode = $varGrupo->funSetGrupoJogos();						
						if ($returnCode == 0){
							//Vou fazer a inserção do grupo e dos jogos
							If (($returnCode = $sorteio->insereGruposCompeticao($varGrupo)) == 0){
								$returnCode = $sorteio->insereJogosCompeticao($varGrupo);								
							}
						}	
					}
				}
			}
			if ($returnCode == 0)
				$returnCode = true;
			else
				$returnCode = false;
			
			return $returnCode;			
		}catch (Exception $e){
			return false;
		}		
	}

/***********************************************************************************************************/
/* TRATA OS RESULTADOS DA PRIMEIRA FASE DO PLAYOFF														   */
/* TRATA DE GERAR A SEGUNDA FASE DO PLAYOFF															   	   */
/***********************************************************************************************************/
	protected function funTrataSegFaseAmSul(){
		try{
			//Vou determinar 1º e 2º lugares nos grupos da primeira fase		
			if ($returnCode = $this->funGetPreApuradosPlayOffAmSul()){
				$returnCode = $this->funGetPaisIsentoAmSul();
				if ($returnCode)
					$returnCode = $this->funFechaFase($this->faseActual, $this->compPrefix.$this->compAno);	
			}
			return $returnCode;
		}
		catch(Exception $e){
			return false;
		}
		
	}

	protected function funGetPaisIsentoAmSul(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
		
		try{
			$varBD->queryString = 'select tab_grupos_equipa ';
			$varBD->queryString .= 'from tab_grupos where tab_grupos_class = 2 ';			
			$varBD->queryString .= 'and tab_grupos_id = :GRP';
			
			$varBD->parseDBQuery($varBD->queryString);
			oci_bind_by_name($varBD->queryParse, ':GRP', $this->paisIsento);
			oci_execute($varBD->queryParse);			
			
			$row = oci_fetch_array($varBD->queryParse);		
			array_push($this->amSulPlayOff, $row[0]);			
			
			
			$returnCode = $this->funSorteiaPlayOffAmSul();
			
			return $returnCode;
			
		}catch(Exception $e){
			return false;
		}
	}
	
	
/***********************************************************************************************************/
/* TRATA OS RESULTADOS DA QUARTA FASE DO PLAYOFF														   */
/* TRATA DE GERAR A QUINTA FASE DO PLAYOFF															   	   */
/***********************************************************************************************************/
	protected function funTrataApuramentoFinalAmSul(){
		try{
			$anoComp = $this->funGetCompAno();
			if ($returnCode = $this->funGetPreApuradosPlayOffAmSul()){
				$returnCode = $this->funSetApuradosAmSul($this->amSulPlayOff[0], $anoComp);								
				if ($returnCode){					
					$returnCode = $this->funFechaFase($this->faseActual, $this->compPrefix.$this->compAno);
				}					
			}
			return $returnCode;
		}
		catch(Exception $e){
			return false;
		}
		
	}
		
	/**********************************************************************/
	/* Faz o tratamento dos apurados da América do Sul			          */
	/* 																	  */
	/**********************************************************************/
	protected function funTrataApuramentoAmSul(){
		$apurados = 4;		
		$orgConf = $this->funGetOrgConf();
		$returnCode = true;				
		
		If ($orgConf != -1){			
			//Vou buscar os apurados da primeira fase
		   	If ($this->funGetApuradosAmSul($apurados)){		   				   		
				//Vai inserir os paises apurados na tab_fifa_wc
				If ($returnCode){
					$returnCode = $this->funFechaFase($this->faseActual, $this->compPrefix.$this->compAno);
					if ($returnCode){
						$anoComp = $this->funGetCompAno();	
						For($index = 0; $index < sizeof($this->amSulFase); $index++){						
							$returnCode = $this->funSetApuradosAmSul($this->amSulFase[$index], $anoComp);
						}
						
						If ($returnCode)
							$returnCode = $this->funVerificaPlayOff();
					}
				}				
			}			
		}
		
		return $returnCode; 	
	}
	
	private function funVerificaPlayOff(){
		$orgConf = $this->funGetOrgConf();
		$organizador = $this->funGetCompOrganizador();
		
		If ($orgConf != $this->amSulConfID){//Am do Sul não organiza o Mundial de Futebol - vou buscar o 5º classificado
			$auxVar = new funcoesAuxPlf($this->paisIsento, 5);
			$auxVar->funGetEquClassGrupo();
			$auxVar->funSetEquipaPlfInt(); //Insere a equipa na tabela de playoff
			$plfOk = $auxVar->funVerificaPlfOK();
			
			//If ($plfOk == -1)
			If (!$plfOk)
				return false;
			Else{
				If ($plfOk){
					if ($auxVar->funSorteiaPlfInt($this->compPrefix, $this->compAno, $organizador))							
						return true;
				}
			}
			
			/*If ($orgConf != 1 && $orgConf != 5)
				$plfTotal = 4;
			Else
				$plfTotal = 2;*/
			
			//Função para verificar se é possivel sortear o playoff ainda não foi feita.
			
		}
		
		
	}
			
}
?>