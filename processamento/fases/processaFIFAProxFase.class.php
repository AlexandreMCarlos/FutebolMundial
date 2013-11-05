<?php

require_once ('processaProxFaseGeral.class.php');
require_once ('classes/executeDBOperations.class.php');
require_once ('classes/compGrupos.class.php');

Class processaFIFAProxFase Extends processaProxFaseGeral{
	
	protected $fifaConfID = 7;
	protected $fifaFase = array();
	protected $fifaJogos = array();
	
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
				case 'CMF_01':
					$returnCode = $this->funTrataPrimFaseMundial();//Gera os 1/8's de final
					break;	
				case 'CMF_02':
					$returnCode = $this->funTrataFaseMundial();//Gera os 1/4's de final
					break;
				case 'CMF_03':
					$returnCode = $this->funTrataFaseMundial();//Gera as 1/2's finais
					break;
				case 'CMF_04':
					$returnCode = $this->funTrataFaseMundial();//Gera a Final
					break;
				case 'CMF_05':
					$returnCode = $this->funTrataFaseMundial();//Trata vencedor/vencido
					break;
				case 'PLF_01':
					$returnCode = $this->funTrataPlfInternacional(); //Trata o playoff internacional
					break;													
			}
			
			return $returnCode;			
		}catch(Exception $e){
			return false;
		}	
	}
	
	/**********************************************************************/
	/* Faz o tratamento dos apurados dos vários grupos e trata de         */
	/* preencher o array $fifaFase									  */
	/**********************************************************************/
	private function funGetApuradosMundial($preApurados){
		$fifaPrimClass = array();
		$fifaSegClass = array();
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
				$fifaEquipa = array();
				$fifaEquipa['equipa'] = $row[0];
				$fifaEquipa['grupo'] = $row[1];
				$fifaEquipa['sorteada'] = false;
				If ($row[2] == 1)
					array_push($fifaPrimClass, $fifaEquipa);
				else 
					array_push($fifaSegClass, $fifaEquipa);									
			}
			array_push($this->fifaFase, $fifaPrimClass);
			
			if ($preApurados == 2)
				array_push($this->fifaFase, $fifaSegClass);
			
			unset($fifaEquipa);
			unset($fifaPrimClass);
			unset($fifaSegClass);
			$varBD->fechaLigacao();
			return true;
		
		}catch(Exception $e){
			return false;
		}	
	}


	private function funTrataEliminadosMundial($eliminados){
		$comp = $this->funGetFaseActual();
		$compGrupos = $this->funGetCompFaseActualID();
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}

		try{
			$varBD->queryString = 'Select tab_grupos_equipa From tab_grupos ';
			$varBD->queryString .= 'Where substr(tab_grupos_id, 1, 14) = :GRP ';			
			$varBD->queryString .= 'and tab_grupos_class > :PRA order by tab_grupos_class, tab_grupos_id';

	        $varBD->parseDBQuery($varBD->queryString);		
			oci_bind_by_name($varBD->queryParse, ':GRP', $compGrupos);
			oci_bind_by_name($varBD->queryParse, ':PRA', $eliminados);
			
			oci_execute($varBD->queryParse);
			
			$fifaEquipa = array();
			
			While ($row = oci_fetch_array($varBD->queryParse)){
				array_push($fifaEquipa, $row[0]);									
			}
									
			switch($comp){
				case 'CMF_01':
					$eliminatoria = 'Eliminado na fase de grupos';
					break;
				case 'CMF_02':
					$eliminatoria = 'Eliminado nos Oitavos de final';
					break;
				case 'CMF_03':
					$eliminatoria = 'Eliminado nos Quartos de final';
					break;
				case 'CMF_04':
					$eliminatoria = 'Eliminado nas Meias Finais';
					break;
				case 'CMF_05':
					$eliminatoria = 'Vice-campeao Mundial';
					break;					
			}
			$eliminatoria = utf8_encode($eliminatoria);

			$varBD->queryString = 'Update tab_fifa_wc set tab_wc_class_final = :CLASS where tab_wc_ano = :ANO and tab_wc_equipa = :EQU';

	        $varBD->parseDBQuery($varBD->queryString);		

			oci_bind_by_name($varBD->queryParse, ':CLASS', $eliminatoria);
			oci_bind_by_name($varBD->queryParse, ':ANO', $this->compAno);

			for ($i = 0; $i < sizeof($fifaEquipa); $i++){			
				oci_bind_by_name($varBD->queryParse, ':EQU', $fifaEquipa[$i]);	
				oci_execute($varBD->queryParse);				
			}						
			
			$varBD->fechaLigacao();
			unset($varBD);
			
			return true;
		
		}catch(Exception $e){
			$varBD->fechaLigacao();
			unset($varBD);		
			return false;
		}	
	}

	private function funSorteiaElemento($elementos){
		try{	
			$indice = rand (0, sizeof($elementos)-1);
			
			return $elementos[$indice];
		}catch(Exception $e){
			return -1;
		}
	
	}

	private function reiniciaEquipasSorteio(){
		for($i = 0; $i < sizeof($this->fifaFase[0]); $i++){
			$this->fifaFase[0][$i]['sorteada'] = false;
		}

		for($i = 0; $i < sizeof($this->fifaFase[1]); $i++){
			$this->fifaFase[1][$i]['sorteada'] = false;
		}
	}

	
	private function sorteiaEquipas(){
		$jogo = array();	
		shuffle($this->fifaFase[0]);
		shuffle($this->fifaFase[1]);
		
		$this->reiniciaEquipasSorteio();
		for($i = 0; $i < sizeof($this->fifaFase[0]); $i++){
			$possiveis = array();
			for($j = 0; $j < sizeof($this->fifaFase[1]); $j++){
				If ($this->fifaFase[1][$j]['grupo'] != $this->fifaFase[0][$i]['grupo']){
					If (!$this->fifaFase[1][$j]['sorteada'])					
						array_push($possiveis, $this->fifaFase[1][$j]['equipa']);
				}					
			}
			If (sizeof($possiveis) > 0){
				$sorteado = $this->funSorteiaElemento($possiveis);
				if ($sorteado != -1){
					array_push($jogo, $this->fifaFase[0][$i]['equipa'], $sorteado);
					array_push($this->fifaJogos, $jogo);
					$jogo = array();
					$this->fifaFase[0][$i]['sorteada'] = true;
					for ($y = 0; $y < sizeof($this->fifaFase[1]); $y++){
						if ($this->fifaFase[1][$y]['equipa'] == $sorteado)
							$this->fifaFase[1][$y]['sorteada'] = true;
					}
				}else{
					$this->fifaJogos = array();
					return false;
				}
			}else{
				$this->fifaJogos = array();
				return false;				
			}				 
		}

		if (sizeof($this->fifaJogos) == 8)
			return true;
		else
			return false;
	}
	
	
	private function funEfectuaSorteioOitavos(){
		$sorteioOK = false;	
		
					
		While (!$sorteioOK){
			$sorteioOK = $this->sorteiaEquipas();
		}
		
	}
	
	
	
	/**********************************************************************/
	/* Faz o sorteio para a segunda fase do apuramento Asiático           */
	/* para o Mundial de Futebol. Trata da inserção na BD  				  */
	/**********************************************************************/
	private function funSorteiaSegFaseMundial(){
		$faseGrupos = array ('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H');
		try{
			require_once ('classes/compGrupos.class.php');
			require_once ('classes/procedimentosSorteio.class.php');
			//Terei que ir à BD buscar o organizador da competicao
			$organizador = $this->funGetCompOrganizador();
			
			If ($organizador != -1){
				$sorteio = new procedimentosSorteio();
				$returnCode = $sorteio->insereCompeticaoActual($this->proxFase, $this->compPrefix.$this->compAno, $organizador);
				
				if (!$returnCode){
					$this->funEfectuaSorteioOitavos();
					for($i = 0; $i < sizeof($this->fifaJogos); $i++){
						$varGrupo = new compGrupos('GRUPO_'.$faseGrupos[$i], $this->proxFase, $this->compPrefix, $this->compAno);
						for ($j = 0; $j < sizeof($this->fifaJogos[$i]); $j++){							
							$varGrupo->funSetEquipaGrupo($this->fifaJogos[$i][$j]);
						}
						$returnCode = $varGrupo->funSetGrupoJogosMundial($this->proxFase);						
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
	protected function funTrataPrimFaseMundial(){
		$apurados = 2;
		$eliminados = 2;
		try{
			//Vou determinar 1º e 2º lugares nos grupos da primeira fase		
			if ($returnCode = $this->funGetApuradosMundial($apurados)){
				If ($returnCode = $this->funTrataEliminadosMundial($eliminados)){					
					$returnCode = $this->funSorteiaSegFaseMundial();
				}	
			}
			return $returnCode;
		}
		catch(Exception $e){
			return false;
		}
		
	}
	
	private function funGetVencElimMundial(){
		$apurado = array();
		require_once('processamento/elims/processaFMEliminatorias.class.php');
		$elim = new processaFMEliminatorias($this->faseActual, $this->compPrefix, $this->compAno);
		$this->fifaFase = $elim->tratamentoEliminatoria();		
		return true;
	}


	private function funInsereDadosCampeao($eliminados){
		$comp = $this->funGetFaseActual();
		$compGrupos = $this->funGetCompFaseActualID();
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		//echo "Grupo: ".$compGrupos."\n";
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}

		try{
			$varBD->queryString = 'Select tab_grupos_equipa From tab_grupos ';
			$varBD->queryString .= 'Where substr(tab_grupos_id, 1, 14) = :GRP ';			
			$varBD->queryString .= 'and tab_grupos_class = :PRA order by tab_grupos_class, tab_grupos_id';

	        $varBD->parseDBQuery($varBD->queryString);		
			oci_bind_by_name($varBD->queryParse, ':GRP', $compGrupos);
			oci_bind_by_name($varBD->queryParse, ':PRA', $eliminados);
			
			oci_execute($varBD->queryParse);
			
			$fifaEquipa = array();
			
			While ($row = oci_fetch_array($varBD->queryParse)){
				array_push($fifaEquipa, $row[0]);									
			}
			$eliminatoria = "Campeao Mundial";
			$eliminatoria = utf8_encode($eliminatoria);

			$varBD->queryString = 'Update tab_fifa_wc set tab_wc_class_final = :CLASS where tab_wc_ano = :ANO and tab_wc_equipa = :EQU';

	        $varBD->parseDBQuery($varBD->queryString);		

			oci_bind_by_name($varBD->queryParse, ':CLASS', $eliminatoria);
			oci_bind_by_name($varBD->queryParse, ':ANO', $this->compAno);

			for ($i = 0; $i < sizeof($fifaEquipa); $i++){			
				oci_bind_by_name($varBD->queryParse, ':EQU', $fifaEquipa[$i]);	
				oci_execute($varBD->queryParse);				
			}						
			
			$varBD->fechaLigacao();
			unset($varBD);
			
			return true;
		
		}catch(Exception $e){
			$varBD->fechaLigacao();
			unset($varBD);		
			return false;
		}	
	}

	private function funFechaMundial(){
		if ($this->funFechaFase('CMF', $this->funGetCompPrefix()))
			return true;
		else
			return false;
	}


	/**********************************************************************/
	/* Faz o tratamento dos apurados dos vários grupos e trata do         */
	/* sorteio para o playoff da segunda fase. No fim, insere tudo na BD  */
	/**********************************************************************/
	protected function funTrataFaseMundial(){
		$comp = $this->funGetFaseActual();	
		$eliminados = 1;
		try{
			//Vou determinar 1º e 2º lugares nos grupos da primeira fase		
			if ($returnCode = $this->funGetVencElimMundial()){
				If ($returnCode = $this->funTrataEliminadosMundial($eliminados)){
					If ($comp == 'CMF_05'){
						//Insere os dados do campeão
						if ($this->funInsereDadosCampeao($eliminados)){
							If ($this->funFechaMundial())
								return true;
							else 
								return false;
						}else
							return false;
						//Fecha Mundial						
					}else					
						$returnCode = $this->funSorteiaFaseMundial();
				}	
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
	private function funSorteiaFaseMundial(){
		$comp = $this->funGetFaseActual();
		switch($comp){
			case 'CMF_02':
				$faseGrupos = array ('A', 'B', 'C', 'D');
				break;
			case 'CMF_03':
				$faseGrupos = array ('A', 'B');
				break;
			case 'CMF_04':
				$faseGrupos = array ('A');
				break;
		}
		
		try{			
			require_once ('classes/compGrupos.class.php');
			require_once ('classes/procedimentosSorteio.class.php');
			//Terei que ir à BD buscar o organizador da competicao
			$organizador = $this->funGetCompOrganizador();
			
			If ($organizador != -1){
				$sorteio = new procedimentosSorteio();
				$returnCode = $sorteio->insereCompeticaoActual($this->proxFase, $this->compPrefix.$this->compAno, $organizador);
				
				if (!$returnCode){
					shuffle($this->fifaFase);
					$this->fifaFase = array_chunk($this->fifaFase, 2);					
					for($i = 0; $i < sizeof($this->fifaFase); $i++){
						$varGrupo = new compGrupos('GRUPO_'.$faseGrupos[$i], $this->proxFase, $this->compPrefix, $this->compAno);
						for ($j = 0; $j < sizeof($this->fifaFase[$i]); $j++){							
							$varGrupo->funSetEquipaGrupo($this->fifaFase[$i][$j]);
						}
						$returnCode = $varGrupo->funSetGrupoJogosMundial($this->proxFase);						
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

	/******************************************************************/
	/* Retira o vencedor do playOff como apurado				      */
	/* Chama package na BD que coloca apurado na tablea tab_fifa_wc	  */
	/******************************************************************/
	protected function funUnSetApuradoPlfInter($apurado, $compAno){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
		
		try{
			$varBD->queryString = 'Begin :retVal := package_mundial.funDelAprMundial(:APR, :ANO); End;';		
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



	/******************************************************************/
	/* Regista o vencedor do playOff como apurado				      */
	/* Chama package na BD que coloca apurado na tablea tab_fifa_wc	  */
	/******************************************************************/
	protected function funSetApuradoPlfInter($apurado, $compAno){
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
	* Nome: funTrataPlfinternacional
	* Data: 09/12/2012
	* Autor: Alexandre M. Carlos
	* Acção: Determina os apurados vindos do playoff internacional
	* Entrada: N/A
	* Saida: N/A
	*/	
	private function funTrataPlfinternacional(){
		$returnCode = true;
		try{
			$apurado = array();
			require_once('processamento/elims/processaFMEliminatorias.class.php');
			require_once ('classes/funcoesAuxPlf.class.php');
			$elim = new processaFMEliminatorias($this->faseActual, $this->compPrefix, $this->compAno);
			$apurado = $elim->tratamentoPlayOff();
			
			for($indice = 0; $indice < count($apurado); $indice++){
				If ($returnCode){
					$returnCode = $this->funSetApuradoPlfInter($apurado[$indice], $this->compAno);					
				}else{
					$returnCode = $this->funUnSetApuradoPlfInter($apurado[$indice], $this->compAno);
					return false;	
				}	
				
			}
			
			return $returnCode;
			
		}catch(Exception $e){
			unset($elim);
			return false;
		}
	}
		
}

?>