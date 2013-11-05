<?php

require_once('processaProxFaseGeral.class.php');
require_once ('classes/executeDBOperations.class.php');
require_once ('classes/compGrupos.class.php');

Class processaAfricaProxFase Extends processaProxFaseGeral{
	
	protected $cafConfID = 2;
	protected $africaFase = array ();
	
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
				case 'MAF_01':
					$returnCode = $this->funTrataPrimFaseAfrica();
					break;	
				case 'MAF_02':
					$returnCode = $this->funTrataSegFaseAfrica();
					break;
				case 'MAF_03':
					$returnCode = $this->funTrataApuramentoAfrica();
					break;
				case 'MAF_04':
					$returnCode = $this->funTrataApuramentoPlayoffAfrica();
					break;				
			}
			
			return $returnCode;			
		}catch(Exception $e){
			return false;
		}	
	}
	
	/**********************************************************************/
	/* Faz o tratamento dos apurados dos vários grupos e trata de         */
	/* preencher o array $africaFase									  */
	/**********************************************************************/
	private function funGetPreApuradosAfrica($preApurados){
		$africaPrimClass = array();
		$africaSegClass = array();
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
				$africaEquipa = array();
				$africaEquipa['equipa'] = $row[0];
				$africaEquipa['grupo'] = $row[1];
				If ($row[2] == 1)
					array_push($africaPrimClass, $africaEquipa);
				else 
					array_push($africaSegClass, $africaEquipa);									
			}
			array_push($this->africaFase, $africaPrimClass);
			
			if ($preApurados == 2)
				array_push($this->africaFase, $africaSegClass);
			
			unset($africaEquipa);
			unset($africaPrimClass);
			unset($africaSegClass);
			return true;
		
		}catch(Exception $e){
			return false;
		}	
	}

	/**********************************************************************/
	/* Faz o sorteio para a segunda fase do apuramento Asiático           */
	/* para o Mundial de Futebol. Trata da inserção na BD  				  */
	/**********************************************************************/
	private function funSorteiaSegFaseAfrica(){
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
					$this->africaFase[1] = array_reverse($this->africaFase[1]);
					for($i = 0; $i < sizeof($this->africaFase[0]); $i++){
						$varGrupo = new compGrupos('GRUPO_'.$faseGrupos[$i], $this->proxFase, $this->compPrefix, $this->compAno);
						for ($j = 0; $j < sizeof($this->africaFase); $j++){							
							$varGrupo->funSetEquipaGrupo($this->africaFase[$j][$i]['equipa']);
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
	protected function funTrataPrimFaseAfrica(){
		$preApurados = 2;
		try{
			//Vou determinar 1º e 2º lugares nos grupos da primeira fase		
			if ($returnCode = $this->funGetPreApuradosAfrica($preApurados)){
				$returnCode = $this->funSorteiaSegFaseAfrica();	
			}
			return $returnCode;
		}
		catch(Exception $e){
			return false;
		}
		
	}
	
	private function funGetPreApuradosSegFaseAfrica(){
		$apurado = array();
		require_once('processamento/elims/processaFMEliminatorias.class.php');
		$elim = new processaFMEliminatorias($this->faseActual, $this->compPrefix, $this->compAno);
		$this->africaFase = $elim->tratamentoPlayOff();
		return true;
	}

	/**********************************************************************/
	/* Faz o tratamento dos apurados dos vários grupos e trata do         */
	/* sorteio para o playoff da segunda fase. No fim, insere tudo na BD  */
	/**********************************************************************/
	protected function funTrataSegFaseAfrica(){
		$preApurados = 1;
		try{
			//Vou determinar 1º e 2º lugares nos grupos da primeira fase		
			if ($returnCode = $this->funGetPreApuradosSegFaseAfrica()){
				$returnCode = $this->funSorteiaTerFaseAfrica();	
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
	private function funSorteiaTerFaseAfrica(){
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
					shuffle($this->africaFase);
					$this->africaFase = array_chunk($this->africaFase, 5);					
					for($i = 0; $i < sizeof($this->africaFase); $i++){
						$varGrupo = new compGrupos('GRUPO_'.$faseGrupos[$i], $this->proxFase, $this->compPrefix, $this->compAno);
						for ($j = 0; $j < sizeof($this->africaFase[$i]); $j++){							
							$varGrupo->funSetEquipaGrupo($this->africaFase[$i][$j]);
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
	* Nome: funTrataApuramentoAfrica
	* Data: 23/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Processa o apuramento da zona Asiática para o Mundial de Futebol
	* Entrada: N/A
	* Saida: N/A
	*/	
	private function funTrataApuramentoAfrica(){
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
			
			$this->funTrataApuramentoDirectoAfrica();
			If ($confOrgComp != $this->cafConfID){//Africa não organiza o Mundial de Futebol
			//Vai gerar playoff
				$this->proxFase = 'MAF_04';
				$this->funGeraAfricaPlayoff();
				
			}			
			return true;
			
		}catch(Exception $e){
			return false;
		}	
	}

	/*******************************************************************************************************************
	* Nome: funTrataApuramentoDirectoAfrica
	* Data: 23/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Processa o apuramento directo da zona Asiática para o Mundial de Futebol
	* Entrada: N/A
	* Saida: N/A
	*/
	private function funTrataApuramentoDirectoAfrica(){
		//Variavel que vai conter o número de equipas a serem apuradas directamente.
		$apuramentoDirecto = 2;	
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
		try{
			$varBD->queryString = 'Begin :retVal := package_africa.funAprMundialDrt(:FASE, :COMP, :ANO, :APR); End;';		
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
	* Nome: funGeraAfricaPlayoff
	* Data: 23/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Gera o Playoff Asiático para o Mundial de Futebol
	* Entrada: N/A
	* Saida: N/A
	*/	
	private function funGeraAfricaPlayoff(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
		
		try{
			$varBD->queryString = 'Begin :retVal := package_africa.geraAfricaPlayoff(:FASE, :COMP, :ANO); End;';		
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
	protected function funSetApuradoPlfAfrica($apurado, $compAno){
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
	
	/*******************************************************************************************************************
	* Nome: funTrataApuramentoPlayoffAfrica
	* Data: 23/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Determina o apurado asiático vindo do playoff
	* Entrada: N/A
	* Saida: N/A
	*/	
	private function funTrataApuramentoPlayoffAfrica(){
		try{
			$apurado = array();
			require_once('processamento/elims/processaFMEliminatorias.class.php');
			$elim = new processaFMEliminatorias($this->faseActual, $this->compPrefix, $this->compAno);
			$apurado = $elim->tratamentoPlayOff();
			if ($this->funSetApuradoPlfAfrica($apurado[0], $this->compAno))
				return true;
			else
				return false;
		}catch(Exception $e){
			unset($elim);
			return false;
		}
	}
		
	
}

?>