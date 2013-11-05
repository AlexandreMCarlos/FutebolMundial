<?php

require_once('processaProxFaseGeral.class.php');
require_once ('classes/executeDBOperations.class.php');
require_once ('classes/compGrupos.class.php');

Class processaEuroProxFase Extends processaProxFaseGeral{
	
	protected $uefaConfID = 6;
	protected $euroFase = array();
	protected $euroPlayOff = array();
	protected $repescagem = array();
	protected $paisIsento;
	
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
		
		/*If ($this->faseActual == 'MEU_03')
			$this->paisIsento = 'MEU_01_'.$this->funGetCompPrefix().'_GRUPO_A';
		elseif ($this->faseActual == 'MEU_01')
			$this->paisIsento = $this->funGetCompFaseActualID().'_GRUPO_A'; 
		else
			$this->paisIsento = null;*/
			
		$this->paisIsento = null;		
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
				case 'MEU_01': //Vai apurar directamente 13 selecções e, se for caso disso, gerar o playoff seguinte
					$returnCode = $this->funTrataPrimFaseEuropa();
					break;
				case 'MEU_02': // 12 segundos class dos grupos de 4 elems fazem playoff
					$returnCode = $this->funTrataSegFaseEuropa(); //1ª fase playoff
					break;
				case 'MEU_03': //6 vencedores do playoff anterior fazem playoff (2ª fase)
					/*$returnCode = $this->funTrataTerFaseEuropa(); //Vai determinar os 3 vencedores e gerar a 4ª fase com o 2º do Grupo A
					break;*/
					$returnCode = $this->funTrataApuramentoFinalEuropa();
					break;					
				case 'MEU_04': //Os 3 vencedores da eliminatória anterior + o 2º class do grupo A.
					$returnCode = $this->funTrataQuartaFaseEuropa(); //Meia Final
					break;
				case 'MEU_05': //Os 2 vencedores anteriores fazem playoff. O vencedor vai ao Mundial de Futebol
					$returnCode = $this->funTrataApuramentoFinalEuropa();
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
	/* preencher o array $euroFase									  	  */
	/**********************************************************************/
	private function funGetApuradosEuropa($uefaApurados){
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
				array_push($this->euroFase, $row[0]);
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
	/* preencher o array $euroFase									  	  */
	/**********************************************************************/
	protected function funTrataPrimFaseEuropa(){
		$apurados = 1;
		$orgConf = $this->funGetOrgConf();
		$returnCode = true;		
		
		If ($orgConf != -1){
			//Vou buscar os apurados da primeira fase
		   	If ($this->funGetApuradosEuropa($apurados)){
				$returnCode = $this->funGeraPrimeiraFasePlayOff(5);			   				   		
				//Vai inserir os paises apurados na tab_fifa_wc
				If ($returnCode){
					$returnCode = $this->funFechaFase($this->faseActual, $this->compPrefix.$this->compAno);
					if ($returnCode){						
						$anoComp = $this->funGetCompAno();	
						For($index = 0; $index < sizeof($this->euroFase); $index++){						
							$returnCode = $this->funSetApuradosEuropa($this->euroFase[$index], $anoComp);
						}
					}
				}
			}
		} 	
	}
		
	/**********************************************************************/
	/* Vai determinar o ultimo apurado da Europa para o Mundial           */
	/* Preenche o array $repescagem									  	  */
	/**********************************************************************/
	private function funGetUltimoApuradoEuropa(){
		$comp = $this->funGetCompFaseActualID().'_GRUPO_A';
		$ultimoApurado = array();
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}

		try{
			$varBD->queryString = 'select tab_grupos_equipa, tab_grupos_id ';
			$varBD->queryString .= 'from tab_grupos where tab_grupos_class = 2 ';			
			$varBD->queryString .= 'and tab_grupos_id = :GRP';

	        $varBD->parseDBQuery($varBD->queryString);		
			oci_bind_by_name($varBD->queryParse, ':GRP', $comp);
			oci_execute($varBD->queryParse);
			
			$row = oci_fetch_array($varBD->queryParse);
			
			array_push($this->euroFase, $row[0]);
			
			$varBD->fechaLigacao();			
			unset($varBD);
			
			return true;
		
		}catch (Exception $e){
			unset($varBD);
			return false;			
		}
	}
	
	/******************************************************************/
	/* Regista o vencedor do playOff como apurado				      */
	/* Chama package na BD que coloca apurado na tablea tab_fifa_wc	  */
	/******************************************************************/
	protected function funSetApuradosEuropa($apurado, $compAno){
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
/* TRATAMENTO DO APURAMENTO EUROPEU COM PLAYOFFS														   */							
/***********************************************************************************************************/


/***********************************************************************************************************/
/* UTILIZADA PARA DEVOLVER OS APURADOS DE UM PLAYOFF													   */							
/***********************************************************************************************************/
	private function funGetPreApuradosPlayOffEuropa(){
		/*$apurado = array();*/
		require_once('processamento/elims/processaFMEliminatorias.class.php');
		$elim = new processaFMEliminatorias($this->faseActual, $this->compPrefix, $this->compAno);
		$this->euroPlayOff = $elim->tratamentoPlayOff();		
		return true;
	}

/***********************************************************************************************************/
/* TRATA DE GERAR A PRIMEIRA FASE DO PLAYOFF															   */
/***********************************************************************************************************/
	protected function funGeraPrimeiraFasePlayOff($fase){
		$gruposEuro = $this->funGetCompFaseActualID();
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
		
		try{			
			$varBD->queryString = 'select tab_grupos_equipa ';
			$varBD->queryString .= 'from tab_grupos where tab_grupos_class = 2 ';			
			//$varBD->queryString .= 'and tab_grupos_id <> :GRP and substr(tab_grupos_id, 1, 14) = :FASE';
			$varBD->queryString .= 'and substr(tab_grupos_id, 1, 14) = :FASE';			
			$varBD->parseDBQuery($varBD->queryString);
			//oci_bind_by_name($varBD->queryParse, ':GRP', $this->paisIsento);
			oci_bind_by_name($varBD->queryParse, ':FASE', $gruposEuro);
			oci_execute($varBD->queryParse);
			While ($row = oci_fetch_array($varBD->queryParse)){
				array_push($this->euroPlayOff, $row[0]);			
			}
			
			//$returnCode = $this->funSorteiaPlayOffEuro(6);
			$returnCode = $this->funSorteiaPlayOffEuro($fase);
			
			return $returnCode;
			
		}catch(Exception $e){
			return false;
		}
		
	}
	
	/***********************************************************************/
	/* Vai sortear a primeira fase do PlayOff Europeu					   */
	/***********************************************************************/
	protected function funSorteiaPlayOffEuro($comprimento){
		if ($comprimento == 6)
			$faseGrupos = array ('A', 'B', 'C', 'D', 'E', 'F');
		elseif ($comprimento == 5){
			$comprimento == 2;
			$faseGrupos = array ('A', 'B');
		} 
		elseif ($comprimento == 3) 
			$faseGrupos = array ('A', 'B', 'C');
		elseif ($comprimento == 2)
			$faseGrupos = array ('A', 'B');
		elseif ($comprimento == 1)
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
					shuffle($this->euroPlayOff);
					
					if ($comprimento == 1){
						$varGrupo = new compGrupos('GRUPO_'.$faseGrupos[0], $this->proxFase, $this->compPrefix, $this->compAno);
						for ($i = 0; $i < sizeof($this->euroPlayOff); $i++){
							echo $this->euroPlayOff[$i]."\n";							
							$varGrupo->funSetEquipaGrupo($this->euroPlayOff[$i]);
						}
						$returnCode = $varGrupo->funSetGrupoJogos();						
						if ($returnCode == 0){
							//Vou fazer a inserção do grupo e dos jogos
							//echo "Entrei aqui também.\n";
							If (($returnCode = $sorteio->insereGruposCompeticao($varGrupo)) == 0){
								$returnCode = $sorteio->insereJogosCompeticao($varGrupo);								
							}
						}
					}else{
						$this->euroPlayOff = array_chunk($this->euroPlayOff, $comprimento);
						echo sizeof($this->euroPlayOff)."\n";
						echo sizeof($this->euroPlayOff[0])."\n";
						//echo sizeof($this->euroPlayOff[1])."\n";										
						for($i = 0; $i < sizeof($this->euroPlayOff); $i++){
							echo 'GRUPO_'.$faseGrupos[$i]."\n";
							$varGrupo = new compGrupos('GRUPO_'.$faseGrupos[$i], $this->proxFase, $this->compPrefix, $this->compAno);
							for ($j = 0; $j < sizeof($this->euroPlayOff[$i]); $j++){
								echo $this->euroPlayOff[$i][$j]."\n";							
								$varGrupo->funSetEquipaGrupo($this->euroPlayOff[$i][$j]);
							}
							$returnCode = $varGrupo->funSetGrupoJogos();						
							if ($returnCode == 0){
								//Vou fazer a inserção do grupo e dos jogos
								//echo "Entrei aqui também.\n";
								If (($returnCode = $sorteio->insereGruposCompeticao($varGrupo)) == 0){
									$returnCode = $sorteio->insereJogosCompeticao($varGrupo);								
								}
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
	/*protected function funTrataSegFaseEuropa(){
		try{
			//Vou determinar 1º e 2º lugares nos grupos da primeira fase		
			if ($returnCode = $this->funGetPreApuradosPlayOffEuropa()){
				$returnCode = $this->funSorteiaPlayOffEuro(3);
				if ($returnCode)
					$returnCode = $this->funFechaFase($this->faseActual, $this->compPrefix.$this->compAno);	
			}
			return $returnCode;
		}
		catch(Exception $e){
			return false;
		}
		
	}*/

	protected function funTrataSegFaseEuropa(){
		$orgConf = $this->funGetOrgConf();	
		try{
			//Vou determinar 1º e 2º lugares nos grupos da primeira fase		
			If ($orgConf != $this->uefaConfID){//Europa não organiza o Mundial de Futebol			
				$apurados = 2;
				$returnCode = $this->funGetApuradosEuropa($apurados);												
			}else{
				$apurados = 1;
				if ($returnCode = $this->funGetApuradosEuropa($apurados)){					
						$returnCode = $this->funGeraPrimeiraFasePlayOff(1);
				}		
			}			
			
			If ($returnCode){
				$returnCode = $this->funFechaFase($this->faseActual, $this->compPrefix.$this->compAno);
				if ($returnCode){
					$anoComp = $this->funGetCompAno();	
					For($index = 0; $index < sizeof($this->euroFase); $index++){						
						$returnCode = $this->funSetApuradosEuropa($this->euroFase[$index], $anoComp);
					}
				}
			}

			return $returnCode;
		}
		catch(Exception $e){
			return false;
		}
		
	}

/***********************************************************************************************************/
/* TRATA OS RESULTADOS DA SEGUNDA FASE DO PLAYOFF														   */
/* TRATA DE GERAR A TERCEIRA FASE DO PLAYOFF															   */
/***********************************************************************************************************/
	/*protected function funTrataTerFaseEuropa(){
		try{
			//Vou determinar 1º e 2º lugares nos grupos da primeira fase		
			if ($returnCode = $this->funGetPreApuradosPlayOffEuropa()){
				$returnCode = $this->funGetPaisIsentoEuropa(2);
				if ($returnCode)
					$returnCode = $this->funFechaFase($this->faseActual, $this->compPrefix.$this->compAno);					
			}
			return $returnCode;
		}
		catch(Exception $e){
			return false;
		}
		
	}*/
		
	
	protected function funGetPaisIsentoEuropa(){
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
			array_push($this->euroPlayOff, $row[0]);			
			
			
			$returnCode = $this->funSorteiaPlayOffEuro(2);
			
			return $returnCode;
			
		}catch(Exception $e){
			return false;
		}
	}
	
/***********************************************************************************************************/
/* TRATA OS RESULTADOS DA TERCEIRA FASE DO PLAYOFF														   */
/* TRATA DE GERAR A QUARTA FASE DO PLAYOFF															   */
/***********************************************************************************************************/
	protected function funTrataQuartaFaseEuropa(){
		try{
			//Vou determinar 1º e 2º lugares nos grupos da primeira fase		
			if ($returnCode = $this->funGetPreApuradosPlayOffEuropa()){
				$returnCode = $this->funSorteiaPlayOffEuro(1);								
				if ($returnCode)
					$returnCode = $this->funFechaFase($this->faseActual, $this->compPrefix.$this->compAno);					
			}
			return $returnCode;
		}
		catch(Exception $e){
			return false;
		}
		
	}
	
/***********************************************************************************************************/
/* TRATA OS RESULTADOS DA QUARTA FASE DO PLAYOFF														   */
/* TRATA DE GERAR A QUINTA FASE DO PLAYOFF															   	   */
/***********************************************************************************************************/
	protected function funTrataApuramentoFinalEuropa(){
		try{
			$anoComp = $this->funGetCompAno();					
			if ($returnCode = $this->funGetPreApuradosPlayOffEuropa()){
				$returnCode = $this->funSetApuradosEuropa($this->euroPlayOff[0], $anoComp);								
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
}
?>