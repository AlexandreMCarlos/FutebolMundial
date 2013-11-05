<?php

require_once('processaProxFaseGeral.class.php');
require_once ('classes/executeDBOperations.class.php');
require_once ('classes/compGrupos.class.php');

Class processaOFCProxFase Extends processaProxFaseGeral{
	
	protected $ofcConfID = 5;
	protected $ofcFase = array();
	
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
				case 'MOC_01': //Vai pré apurar os 2 melhores de cada grupo e sortear o grupo final
					$returnCode = $this->funTrataPrimFaseOFC();
					break;
				case 'MOC_02': // Apura 1 selecção para o mundial de futebol
					$returnCode = $this->funTrataSegFaseOFC(); //1ª fase playoff
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
	/* preencher o array $ofcFase									  	  */
	/**********************************************************************/
	private function funGetApuradosOFC($ofcApurados){
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
			oci_bind_by_name($varBD->queryParse, ':PRA', $ofcApurados);
			
			oci_execute($varBD->queryParse);
			
			While ($row = oci_fetch_array($varBD->queryParse)){
				array_push($this->ofcFase, $row[0]);						
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
	/* preencher o array $ofcFase									  	  */
	/**********************************************************************/
	protected function funTrataPrimFaseOFC(){
		$apurados = 2;
		$orgConf = $this->funGetOrgConf();
		$returnCode = true;		
		
		If ($orgConf != -1){
			//Vou buscar os apurados da primeira fase
		   	If ($this->funGetApuradosOFC($apurados)){		   				   		
				//Vai inserir os paises apurados na tab_fifa_wc
				If ($returnCode){
					$returnCode = $this->funFechaFase($this->faseActual, $this->compPrefix.$this->compAno);
					if ($returnCode){
						$returnCode = $this->funSorteiaSegFaseOFC();
					}
				}
			}
		} 	
	}
	
	
	/**********************************************************************/
	/* Tratar o apuramento de uma Selecção para o Mundial de Futebol      */
	/**********************************************************************/
	protected function funTrataSegFaseOFC(){
		$apurados = 1;
		$orgConf = $this->funGetOrgConf();
		$organizador = $this->funGetCompOrganizador();
		$lastGrp = '_GRUPO_A';
		$returnCode = true;		
		
		try{
			If ($orgConf != -1){
				//Vou buscar os apurados da primeira fase
				If ($returnCode){
					$returnCode = $this->funFechaFase($this->faseActual, $this->compPrefix.$this->compAno);
					if ($returnCode){
						require_once ('classes/funcoesAuxPlf.class.php');						
						$anoComp = $this->funGetCompAno();	
						//$returnCode = $this->funSetApuradosOFC($this->ofcFase[0], $anoComp);
						$auxVar = new funcoesAuxPlf($this->funGetCompFaseActualID().$lastGrp, $apurados);
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
					}
				}
			}
		}catch(Exception $e){
			unset($elim);
			return false;
		} 	
	}
	
	
	
			
	/******************************************************************/
	/* Regista o vencedor do playOff como apurado				      */
	/* Chama package na BD que coloca apurado na tablea tab_fifa_wc	  */
	/******************************************************************/
	protected function funSetApuradosOFC($apurado, $compAno){
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

	private function funVerificaPlayOff(){
		$orgConf = $this->funGetOrgConf();
		$organizador = $this->funGetCompOrganizador();
		
		If ($orgConf != $this->amSulConfID){//Am do Sul não organiza o Mundial de Futebol - vou buscar o 5º classificado
			$auxVar = new funcoesAuxPlf($this->paisIsento, 5);
			$auxVar->funGetEquClassGrupo();
			$auxVar->funSetEquipaPlfInt(); //Insere a equipa na tabela de playoff
			$plfOk = $auxVar->funVerificaPlfOK();
			
			If ($plfOk == -1)
				return false;
			Else{
				If ($plfOk){
					if ($auxVar->funSorteiaPlfInt($this->compPrefix, $this->compAno, $organizador))							
						return true;
					else
						return false;
				}
			}
			
			/*If ($orgConf != 1 && $orgConf != 5)
				$plfTotal = 4;
			Else
				$plfTotal = 2;*/
			
			//Função para verificar se é possivel sortear o playoff ainda não foi feita.
			
		}
		
		
	}
	
	

	/***********************************************************************/
	/* Vai sortear a segunda fase do apuramento OFC 					   */
	/***********************************************************************/
	protected function funSorteiaSegFaseOFC(){
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
					shuffle($this->ofcFase);
					$varGrupo = new compGrupos('GRUPO_'.$faseGrupos[0], $this->proxFase, $this->compPrefix, $this->compAno);															
					for($i = 0; $i < sizeof($this->ofcFase); $i++){																		
						$varGrupo->funSetEquipaGrupo($this->ofcFase[$i]);
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
			if ($returnCode == 0)
				$returnCode = true;
			else
				$returnCode = false;
			
			return $returnCode;			
		}catch (Exception $e){
			return false;
		}		
	}
}
?>