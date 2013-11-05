<?php

require_once('processaProxFaseGeral.class.php');
require_once ('classes/executeDBOperations.class.php');
require_once ('classes/compGrupos.class.php');

Class processaAmNorProxFase Extends processaProxFaseGeral{
	
	protected $ccfConfID = 3;
	protected $conmConfID = 4;
	protected $afcConfID = 1;
	protected $plf = false;
	protected $amNorFase = array ();
	protected $ultGrupo;
	private $faseAmNor;
	private $faseGeral = 'MAN';	
	
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
		$this->funGetUltFase();
		$this->ultGrupo = $this->faseAmNor.'_'.$this->funGetCompPrefix().'_GRUPO_A';			
	}
	

/***********************************************************************************************************/
/* Vai buscar o prefixo da fase de apuramento da Am. do Sul.											   */							
/***********************************************************************************************************/
	protected function funGetUltFase(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
		
		try{			
			$varBD->queryString = 'select tab_competicao_fase from tab_competicao'; 
			$varBD->queryString .= ' where substr(tab_competicao_fase, 1, 3) = :FASE'; 
			$varBD->queryString .= ' and tab_competicao_act = 1 and tab_competicao_fase_ant = \'MAN_02\'';		
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
		$this->faseAmNor = $fase;
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
		$returnCode = false;	
		Try{
			switch($this->faseActual){
				case 'MAN_01':
					$returnCode = $this->funTrataPrimFaseAmNor();
					break;	
				case 'MAN_02':
					$returnCode = $this->funTrataSegFaseAmNor();
					break;
				case 'MAN_03':
					$returnCode = $this->funTrataApuramentoAmNor();
					break;
			}
			
			return $returnCode;			
		}catch(Exception $e){
			return false;
		}	
	}	
	
	/**********************************************************************/
	/* Faz o tratamento dos apurados dos vários grupos e trata de         */
	/* preencher o array $amNorFase									  */
	/**********************************************************************/
	private function funGetPreApuradosAmNor($preApurados){
		$amNorPrimClass = array();
		$amNorSegClass = array();
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
				$amNorEquipa = array();
				$amNorEquipa['equipa'] = $row[0];
				$amNorEquipa['grupo'] = $row[1];
				If ($row[2] == 1)
					array_push($amNorPrimClass, $amNorEquipa);
				else 
					array_push($amNorSegClass, $amNorEquipa);									
			}
			array_push($this->amNorFase, $amNorPrimClass);
			
			if ($preApurados == 2)
				array_push($this->amNorFase, $amNorSegClass);
			
			unset($amNorEquipa);
			unset($amNorPrimClass);
			unset($amNorSegClass);
			return true;
		
		}catch(Exception $e){
			return false;
		}	
	}

	/**********************************************************************/
	/* Faz o sorteio para a segunda fase do apuramento Asiático           */
	/* para o Mundial de Futebol. Trata da inserção na BD  				  */
	/**********************************************************************/
	private function funSorteiaSegFaseAmNor(){
		$faseGrupos = array ('A', 'B', 'C', 'D', 'E', 'F', 'G');
		$item = array();
		try{
			require_once ('classes/compGrupos.class.php');
			require_once ('classes/procedimentosSorteio.class.php');
			//Terei que ir à BD buscar o organizador da competicao
			$organizador = $this->funGetCompOrganizador();
			
			If ($organizador != -1){
				$sorteio = new procedimentosSorteio();
				$returnCode = $sorteio->insereCompeticaoActual($this->proxFase, $this->compPrefix.$this->compAno, $organizador);
				
				if (!$returnCode){
					$this->amNorFase[1] = array_reverse($this->amNorFase[1]);						
					array_push($this->amNorFase[1], array_shift($this->amNorFase[1]));
					$this->amNorFase[1] = array_reverse($this->amNorFase[1]);					
					for($i = 0; $i < sizeof($this->amNorFase[0]); $i++){
						$varGrupo = new compGrupos('GRUPO_'.$faseGrupos[$i], $this->proxFase, $this->compPrefix, $this->compAno);
						for ($j = 0; $j < sizeof($this->amNorFase); $j++){							
							$varGrupo->funSetEquipaGrupo($this->amNorFase[$j][$i]['equipa']);
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
	protected function funTrataPrimFaseAmNor(){
		$preApurados = 2;
		try{
			//Vou determinar 1º e 2º lugares nos grupos da primeira fase		
			if ($returnCode = $this->funGetPreApuradosAmNor($preApurados)){
				$returnCode = $this->funSorteiaSegFaseAmNor();	
			}
			return $returnCode;
		}
		catch(Exception $e){
			return false;
		}
		
	}
	
	private function funGetPreApuradosSegFaseAmNor(){
		$apurado = array();
		require_once('processamento/elims/processaFMEliminatorias.class.php');
		$elim = new processaFMEliminatorias($this->faseActual, $this->compPrefix, $this->compAno);
		$this->amNorFase = $elim->tratamentoPlayOff();		
		return true;
	}

	/**********************************************************************/
	/* Faz o tratamento dos apurados dos vários grupos e trata do         */
	/* sorteio para o playoff da segunda fase. No fim, insere tudo na BD  */
	/**********************************************************************/
	protected function funTrataSegFaseAmNor(){
		$preApurados = 1;
		try{
			//Vou determinar 1º e 2º lugares nos grupos da primeira fase		
			if ($returnCode = $this->funGetPreApuradosSegFaseAmNor()){
				$returnCode = $this->funSorteiaTerFaseAmNor();	
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
	private function funSorteiaTerFaseAmNor(){
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
					shuffle($this->amNorFase);
					$varGrupo = new compGrupos('GRUPO_'.$faseGrupos[0], $this->proxFase, $this->compPrefix, $this->compAno);				
					for($i = 0; $i < sizeof($this->amNorFase); $i++){
						$varGrupo->funSetEquipaGrupo($this->amNorFase[$i]);
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
			return $returnCode;			
		}catch (Exception $e){
			return false;
		}
	}
	

	
	/*******************************************************************************************************************
	* Nome: funTrataApuramentoAmNor
	* Data: 23/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Processa o apuramento da zona Asiática para o Mundial de Futebol
	* Entrada: N/A
	* Saida: N/A
	*/	
	private function funTrataApuramentoAmNor(){
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
			
			//echo "Conf Org: ".$confOrgComp."\n";
			//echo "This Conf: ".$this->ccfConfID."\n";
						
			If ($confOrgComp != $this->ccfConfID){//Am. Norte não organiza o Mundial de Futebol				
				$apuramentoDirecto = 3;
				If ($confOrgComp != $this->conmConfID && $confOrgComp != $this->afcConfID){
					$this->plf = true;
				}				
			}
			else{
				$apuramentoDirecto = 2;
				$this->plf = true;
			}	
			
			//echo "Apurados: ".$apuramentoDirecto."\n";							
			
			$this->funTrataApuramentoDirectoAmNor($apuramentoDirecto);			
			return true;
			
		}catch(Exception $e){
			return false;
		}	
	}

	/*******************************************************************************************************************
	* Nome: funTrataApuramentoDirectoAmNor
	* Data: 23/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Processa o apuramento directo da zona Asiática para o Mundial de Futebol
	* Entrada: N/A
	* Saida: N/A
	*/
	private function funTrataApuramentoDirectoAmNor($apuramentoDirecto){
		//Variavel que vai conter o número de equipas a serem apuradas directamente.	
		$organizador = $this->funGetCompOrganizador();
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
		try{
			$varBD->queryString = 'Begin :retVal := package_amnorte.funAprMundialDrt(:FASE, :COMP, :ANO, :APR); End;';		
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
				If ($returnCode){
					If ($this->plf){
						require_once ('classes/funcoesAuxPlf.class.php');
						$auxVar = new funcoesAuxPlf($this->ultGrupo, $apuramentoDirecto+1);
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
			
			return $returnCode;
		}catch (Exception $e){
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
}

?>