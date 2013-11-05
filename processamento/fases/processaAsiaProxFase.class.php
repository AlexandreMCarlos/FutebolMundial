<?php

require_once('processaProxFaseGeral.class.php');
require_once ('classes/executeDBOperations.class.php');
require_once ('classes/compGrupos.class.php');

Class processaAsiaProxFase Extends processaProxFaseGeral{
	
	protected $afcConfID = 1;
	protected $ofcConfID = 5;
	protected $asiaFase = array ();
	
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
				case 'MAC_01':
					$returnCode = $this->funTrataPrimFaseAsia();
					break;	
				case 'MAC_02':
					$returnCode = $this->funTrataSegFaseAsia();
					break;
				case 'MAC_03':
					$returnCode = $this->funTrataApuramentoAsia();
					break;
				case 'MAC_04':
					$returnCode = $this->funTrataApuramentoPlayoffAsia();
					break;				
			}
			
			return $returnCode;			
		}catch(Exception $e){
			return false;
		}	
	}
	
	/**********************************************************************/
	/* Faz o tratamento dos apurados dos vários grupos e trata de         */
	/* preencher o array $asiaFase									  */
	/**********************************************************************/
	private function funGetPreApuradosAsia($preApurados){
		$asiaPrimClass = array();
		$asiaSegClass = array();
		$comp = $this->funGetCompFaseActualID();
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}

		try{
			$varBD->queryString = 'Select tab_grupos_equipa, tab_grupos_id, tab_grupos_class From tab_grupos ';
			$varBD->queryString .= 'Where substr(tab_grupos_id, 1, 14) = :GRP ';			
			$varBD->queryString .= 'and tab_grupos_class <= :PRA order by tab_grupos_class, tab_grupos_id';

	        $varBD->parseDBQuery($varBD->queryString);		
			oci_bind_by_name($varBD->queryParse, ':GRP', $comp);
			oci_bind_by_name($varBD->queryParse, ':PRA', $preApurados);
			
			oci_execute($varBD->queryParse);
			
			While ($row = oci_fetch_array($varBD->queryParse)){
				$asiaEquipa = array();
				$asiaEquipa['equipa'] = $row[0];
				$asiaEquipa['grupo'] = $row[1];
				If ($row[2] == 1)
					array_push($asiaPrimClass, $asiaEquipa);
				else 
					array_push($asiaSegClass, $asiaEquipa);									
			}
			array_push($this->asiaFase, $asiaPrimClass);
			
			if ($preApurados == 2)
				array_push($this->asiaFase, $asiaSegClass);
			
			unset($asiaEquipa);
			unset($asiaPrimClass);
			unset($asiaSegClass);
			return true;
		
		}catch(Exception $e){
			return false;
		}	
	}

	/**********************************************************************/
	/* Faz o sorteio para a segunda fase do apuramento Asiático           */
	/* para o Mundial de Futebol. Trata da inserção na BD  				  */
	/**********************************************************************/
	private function funSorteiaSegFaseAsia(){
		$faseGrupos = array ('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
		try{
			require_once ('classes/compGrupos.class.php');
			require_once ('classes/procedimentosSorteio.class.php');
			//Terei que ir à BD buscar o organizador da competicao
			$organizador = $this->funGetCompOrganizador();
			
			If ($organizador != -1){
				$sorteio = new procedimentosSorteio();
				$returnCode = $sorteio->insereCompeticaoActual($this->proxFase, $this->compPrefix.$this->compAno, $organizador);
				
				if (!$returnCode){
					$this->asiaFase[1] = array_reverse($this->asiaFase[1]);
					for($i = 0; $i < sizeof($this->asiaFase[0]); $i++){
						$varGrupo = new compGrupos('GRUPO_'.$faseGrupos[$i], $this->proxFase, $this->compPrefix, $this->compAno);
						for ($j = 0; $j < sizeof($this->asiaFase); $j++){							
							$varGrupo->funSetEquipaGrupo($this->asiaFase[$j][$i]['equipa']);
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
			return $returnCode;			
		}catch (Exception $e){
			return false;
		}
	}

	/**********************************************************************/
	/* Faz o tratamento dos apurados dos vários grupos e trata do         */
	/* sorteio para o playoff da segunda fase. No fim, insere tudo na BD  */
	/**********************************************************************/
	protected function funTrataPrimFaseAsia(){
		$preApurados = 2;
		try{
			//Vou determinar 1º e 2º lugares nos grupos da primeira fase		
			if ($returnCode = $this->funGetPreApuradosAsia($preApurados)){
				$returnCode = $this->funSorteiaSegFaseAsia();	
			}
			return $returnCode;
		}
		catch(Exception $e){
			return false;
		}
		
	}
	
	private function funGetPreApuradosSegFaseAsia(){
		$apurado = array();
		require_once('processamento/elims/processaFMEliminatorias.class.php');
		$elim = new processaFMEliminatorias($this->faseActual, $this->compPrefix, $this->compAno);
		$this->asiaFase = $elim->tratamentoPlayOff();		
		return true;
	}

	/**********************************************************************/
	/* Faz o tratamento dos apurados dos vários grupos e trata do         */
	/* sorteio para o playoff da segunda fase. No fim, insere tudo na BD  */
	/**********************************************************************/
	protected function funTrataSegFaseAsia(){
		$preApurados = 1;
		try{
			//Vou determinar 1º e 2º lugares nos grupos da primeira fase		
			if ($returnCode = $this->funGetPreApuradosSegFaseAsia()){
				$returnCode = $this->funSorteiaTerFaseAsia();	
			}
			return $returnCode;
		}
		catch(Exception $e){
			return false;
		}
		
	}
	
	/**********************************************************************/
	/* Faz o sorteio para a terceira fase do apuramento Asiático          */
	/* para o Mundial de Futebol. Trata da inserção na BD  				  */
	/**********************************************************************/
	private function funSorteiaTerFaseAsia(){
		$faseGrupos = array ('A', 'B');
		try{			
			require_once ('classes/compGrupos.class.php');
			require_once ('classes/procedimentosSorteio.class.php');
			//Terei que ir à BD buscar o organizador da competicao
			$organizador = $this->funGetCompOrganizador();
			
			If ($organizador != -1){
				$sorteio = new procedimentosSorteio();
				$returnCode = $sorteio->insereCompeticaoActual($this->proxFase, $this->compPrefix.$this->compAno, $organizador);
				
				if (!$returnCode){
					shuffle($this->asiaFase);
					$this->asiaFase = array_chunk($this->asiaFase, 5);					
					for($i = 0; $i < sizeof($this->asiaFase); $i++){
						$varGrupo = new compGrupos('GRUPO_'.$faseGrupos[$i], $this->proxFase, $this->compPrefix, $this->compAno);
						for ($j = 0; $j < sizeof($this->asiaFase[$i]); $j++){							
							$varGrupo->funSetEquipaGrupo($this->asiaFase[$i][$j]);
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
			return $returnCode;			
		}catch (Exception $e){
			return false;
		}
	}
	

	
	/*******************************************************************************************************************
	* Nome: funTrataApuramentoAsia
	* Data: 23/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Processa o apuramento da zona Asiática para o Mundial de Futebol
	* Entrada: N/A
	* Saida: N/A
	*/	
	private function funTrataApuramentoAsia(){
		$competicao = $this->funGetCompPrefix();
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
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
			
			$this->funTrataApuramentoDirectoAsia();
			If ($confOrgComp != $this->afcConfID  && $confOrgComp != $this->ofcConfID){//Asia não organiza o Mundial de Futebol
			//Vai gerar playoff
				$this->proxFase = 'MAC_04';
				$this->funGeraAsiaPlayoff();
				
			}			
			return true;
			
		}catch(Exception $e){
			return false;
		}	
	}

	/*******************************************************************************************************************
	* Nome: funTrataApuramentoDirectoAsia
	* Data: 23/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Processa o apuramento directo da zona Asiática para o Mundial de Futebol
	* Entrada: N/A
	* Saida: N/A
	*/
	private function funTrataApuramentoDirectoAsia(){
		//Variavel que vai conter o número de equipas a serem apuradas directamente.
		$apuramentoDirecto = 2;	
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
		try{
			$varBD->queryString = 'Begin :retVal := package_asia.funAprMundialDrt(:FASE, :COMP, :ANO, :APR); End;';		
			$varBD->parseDBQuery($varBD->queryString);
					
			oci_bind_by_name($varBD->queryParse, ':retVal', $returnCode, 50);
			oci_bind_by_name($varBD->queryParse, ':FASE', $this->faseActual);
			oci_bind_by_name($varBD->queryParse, ':COMP', $this->compPrefix);
			oci_bind_by_name($varBD->queryParse, ':ANO', $this->compAno);
			oci_bind_by_name($varBD->queryParse, ':APR', $apuramentoDirecto);
			
			oci_execute($varBD->queryParse);
			$varBD->fechaLigacao(); 		
			unset($varBD);
			
			If ($returnCode)
				$returnCode = $this->funFechaFase($this->faseActual, $this->compPrefix.$this->compAno);
			
			return $returnCode;
		}catch (Exception $e){
			return false;
		}
	}
	

	/*******************************************************************************************************************
	* Nome: funGeraAsiaPlayoff
	* Data: 23/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Gera o Playoff Asiático para o Mundial de Futebol
	* Entrada: N/A
	* Saida: N/A
	*/	
	private function funGeraAsiaPlayoff(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
		
		try{
			$varBD->queryString = 'Begin :retVal := package_asia.geraAsiaPlayoff(:FASE, :COMP, :ANO); End;';		
			$varBD->parseDBQuery($varBD->queryString);
					
			oci_bind_by_name($varBD->queryParse, ':retVal', $returnCode, 50);
			oci_bind_by_name($varBD->queryParse, ':FASE', $this->proxFase);
			oci_bind_by_name($varBD->queryParse, ':COMP', $this->compPrefix);
			oci_bind_by_name($varBD->queryParse, ':ANO', $this->compAno);
			
			oci_execute($varBD->queryParse);
			$varBD->fechaLigacao(); 		
			unset($varBD);
			
			return $returnCode;
		}catch(Exception $e){
			return false;
		}
	}
	
	/******************************************************************/
	/* Regista o vencedor do playOff como apurado				      */
	/* Chama package na BD que coloca apurado na tablea tab_fifa_wc	  */
	/******************************************************************/
	protected function funSetApuradoPlfAsia($apurado, $compAno){
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
	
	/*******************************************************************************************************************
	* Nome: funTrataApuramentoPlayoffAsia
	* Data: 23/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Determina o apurado asiático vindo do playoff
	* Entrada: N/A
	* Saida: N/A
	*/	
	private function funTrataApuramentoPlayoffAsia(){
		$orgConf = $this->funGetOrgConf();
		$organizador = $this->funGetCompOrganizador();
		try{
			$apurado = array();
			require_once('processamento/elims/processaFMEliminatorias.class.php');
			require_once ('classes/funcoesAuxPlf.class.php');
			$elim = new processaFMEliminatorias($this->faseActual, $this->compPrefix, $this->compAno);
			$apurado = $elim->tratamentoPlayOff();
			
			If ($orgConf != $this->afcConfID && $orgConf != $this->ofcConfID){
				$auxVar = new funcoesAuxPlf($this->funGetCompFaseActualID(), 0);
				$auxVar->funSetEquClassGrupo($apurado[0]);
				
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
				/*if ($this->funSetApuradoPlfAsia($apurado[0], $this->compAno))
					return true;
				else
					return false;*/				
			}

		}catch(Exception $e){
			unset($elim);
			return false;
		}
	}
	
	/**********************************************************************/
	/* Retorna o organizador da competição						          */
	/*                               									  */
	/**********************************************************************/
	/*private function funGetCompOrganizador(){
		
	}*/	
	
}

?>