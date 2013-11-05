<?php

require_once ('classes/executeDBOperations.class.php');
require_once ('classes/compGrupos.class.php');
require_once ('ranking/rankingFutebolMundial.class.php');

Class sorteioMundialFaseFinal{
	
	protected $varBD;
	protected $gruposSorteio = array();
	protected $equipasSorteio = array();
	protected $idConf = 7;
	public $prefixoFase = array('CMF_01');
	
	protected $mundialPrefixo = 'CMF';
	protected $qualCompPrefix = 'CMQ';
	protected $mundialOrganizador;
	protected $mundialAno;
	protected $gruposElems = 104;	
		
	
	public function __construct(){
		$this->funSetVarBD();
		If ($this->funGetAno())
			$this->funGetOrganizador();
		else{
			unset($this->varBD);
			exit;
		}
	}
	
	public function funGetSorteioOK(){
		If ($this->funGetQualifEquipas() && $this->funGetQualif())
			return true;
		else 
			return false;
	}

	public function funSetNewRanking(){
		$ranking = new rankingFutebolMundial();

		If ($ranking->funSetPontosRanking()){
			unset($ranking);
			If ($this->funActualizaRankApurados()){
				unset($ranking);	
				return true;
			}else{
				unset($ranking);
				$this->funUnsetVars();
				return false;			
			}					
		}else{
			unset($ranking);
			$this->funUnsetVars();
			return false;
		}
	}	
	
	public function funEfectuaSorteio(){
		If ($this->funGetOrganizador()){
			If($this->funGetEquipasApuradas()){
				If ($this->funSorteiaNivelUm()){
					if ($this->funSorteiaNivelDois()){
						if ($this->funSorteiaNivelTres()){
							if ($this->funSorteiaNivelQuatro()){
								If ($this->funGeraJogosGrupo() == 0){	
								//$this->funImprimeGrupos();
								//echo "BUUU\n";
									If ($this->funInsereDadosBD()){
										$this->funUnsetVars();	
										return true;									
									}else{
										$this->funUnsetVars();
										return false;
									}
								}else{
									$this->funUnsetVars();
									return false;
								}	
							}else{
								$this->funUnsetVars();
								return false;
							}
						}else{
							$this->funUnsetVars();
							return false;
						}	
					}else{
						$this->funUnsetVars();
						return false;
					}
				}else{
					$this->funUnsetVars();
					return false;			
				}			
			}else{
				$this->funUnsetVars();
				return false;
			}						
		}else{
			$this->funUnsetVars();
			return false;
		}
		$this->funUnsetVars();
		return true;	
	}
	
	private function funSetVarBD(){
		$this->varBD = new ExecuteDBOperations();
	}
		
	
	private function funGetAno(){
		$this->varBD->abreLigacao();
		
		If ($this->varBD->funGetConnection() == null){			
			return false;
		}
				
		$this->varBD->queryString = 'select tab_comp_ult from tab_competicao_ano where tab_comp_id = :COMP';
		
        $this->varBD->parseDBQuery($this->varBD->queryString);
		
		oci_bind_by_name($this->varBD->queryParse, ':COMP', $this->qualCompPrefix);

		oci_execute($this->varBD->queryParse);
		$row = oci_fetch_array($this->varBD->queryParse);
		$this->mundialAno = $row[0];
		$this->varBD->fechaLigacao();
		return true;		
	}
	
	
	
	private function funGetOrganizador(){
		$this->varBD->abreLigacao();
		$varTipoComp = $this->qualCompPrefix.$this->mundialAno;
		If ($this->varBD->funGetConnection() == null){			
			return false;
		}
		
		$this->varBD->queryString = 'select tab_ca_org from tab_comp_actual where tab_ca_fase = :COMP and tab_ca_tipo = :TIPO';
		$this->varBD->parseDBQuery($this->varBD->queryString);		
		oci_bind_by_name($this->varBD->queryParse, ':COMP', $this->qualCompPrefix);
		oci_bind_by_name($this->varBD->queryParse, ':TIPO', $varTipoComp);
		
		oci_execute($this->varBD->queryParse);
		$row = oci_fetch_array($this->varBD->queryParse);
		$this->mundialOrganizador = $row[0];
		$this->varBD->fechaLigacao();
		return true;		
	}
	
	
	
	private function funGetQualif(){
		$this->varBD->abreLigacao();
		$varTipoComp = $this->qualCompPrefix.$this->mundialAno;
		If ($this->varBD->funGetConnection() == null){			
			return false;
		}
		
		$this->varBD->queryString = 'select count(tab_ca_tipo) from tab_comp_actual where tab_ca_fase = :COMP and tab_ca_tipo = :TIPO';
		$this->varBD->parseDBQuery($this->varBD->queryString);		
		oci_bind_by_name($this->varBD->queryParse, ':COMP', $this->qualCompPrefix);
		oci_bind_by_name($this->varBD->queryParse, ':TIPO', $varTipoComp);
		
		oci_execute($this->varBD->queryParse);
		$row = oci_fetch_array($this->varBD->queryParse);
		
		If ($row[0] == 1){
			$this->varBD->fechaLigacao();
			return true;	
		}else{
			$this->varBD->fechaLigacao();
			return false;				
		}
	} 
	
	private function funGetQualifEquipas(){
		$this->varBD->abreLigacao();
		$varTipoComp = $this->qualCompPrefix.$this->mundialAno;
		If ($this->varBD->funGetConnection() == null){			
			return false;
		}
		
		$this->varBD->queryString = 'select count(tab_wc_equipa) from tab_fifa_wc where tab_wc_ano = :ANO and tab_wc_class_final is null';
		$this->varBD->parseDBQuery($this->varBD->queryString);		
		oci_bind_by_name($this->varBD->queryParse, ':ANO', $this->mundialAno);
		
		
		oci_execute($this->varBD->queryParse);
		$row = oci_fetch_array($this->varBD->queryParse);
		
		If ($row[0] == 32){
			$this->varBD->fechaLigacao();
			return true;	
		}else{
			$this->varBD->fechaLigacao();
			return false;				
		}
	}
	
	private function funActualizaRankApurados(){
		$apurados = array();
		$this->varBD->abreLigacao();		
		
		If ($this->varBD->funGetConnection() == null){			
			return false;
		}
		try{
			$this->varBD->queryString = 'select te.tab_equipas_trig ';
			$this->varBD->queryString .= 'from tab_equipas te, tab_fifa_wc tf ';
			$this->varBD->queryString .= 'where te.tab_equipas_trig = tf.tab_wc_equipa ';
			$this->varBD->queryString .= 'and tf.tab_wc_ano = :ANO ';
			$this->varBD->queryString .= 'and te.tab_equipas_trig <> :EQU ';
			$this->varBD->queryString .= 'order by te.tab_equipas_global_rank';
			$this->varBD->parseDBQuery($this->varBD->queryString);		
			oci_bind_by_name($this->varBD->queryParse, ':ANO', $this->mundialAno);
			oci_bind_by_name($this->varBD->queryParse, ':EQU', $this->mundialOrganizador);
			
			
			oci_execute($this->varBD->queryParse);
			While ($row = oci_fetch_array($this->varBD->queryParse)){
				array_push($apurados, $row[0]);	
			}	
			
			$this->varBD->queryString = 'update tab_fifa_wc set tab_wc_rank = :RANK where tab_wc_equipa = :EQU and tab_wc_ano = :ANO';
			$this->varBD->parseDBQuery($this->varBD->queryString);
			for ($indice = 1; $indice <= sizeof($apurados); $indice++){
				oci_bind_by_name($this->varBD->queryParse, ':ANO', $this->mundialAno);
				oci_bind_by_name($this->varBD->queryParse, ':EQU', $apurados[$indice-1]);
				oci_bind_by_name($this->varBD->queryParse, ':RANK', $indice);
				oci_execute($this->varBD->queryParse);
			}
			
			$this->varBD->fechaLigacao();
			return true;
		}catch(Exception $e){
			unset($this->varBD);
			return false;
		}
		
	}

	private function funGetEquipasApuradas(){
		$equipa = array();
		$this->varBD->abreLigacao();		
		
		If ($this->varBD->funGetConnection() == null){			
			return false;
		}
		try{
			$this->varBD->queryString = 'select te.tab_equipas_trig, te.tab_equipas_conf ';
			$this->varBD->queryString .= 'from tab_equipas te, tab_fifa_wc tf ';
			$this->varBD->queryString .= 'where te.tab_equipas_trig = tf.tab_wc_equipa ';
			$this->varBD->queryString .= 'and tf.tab_wc_ano = :ANO ';
			$this->varBD->queryString .= 'order by tf.tab_wc_rank';

			$this->varBD->parseDBQuery($this->varBD->queryString);		
			oci_bind_by_name($this->varBD->queryParse, ':ANO', $this->mundialAno);			
			
			
			oci_execute($this->varBD->queryParse);
			While ($row = oci_fetch_array($this->varBD->queryParse)){
				$equipa['nome'] = $row[0];
				$equipa['conf'] = $row[1];	
				array_push($this->equipasSorteio, $equipa);	
			}	
			
			$this->equipasSorteio = array_chunk($this->equipasSorteio, 8);
			for ($i = 0; $i < sizeof($this->equipasSorteio); $i++){	
				/*for ($j = 0; $j < sizeof($this->equipasSorteio[$i]); $j++){
					echo "I: ".$i." Equipa: ".$this->equipasSorteio[$i][$j]['nome']." Conf: ".$this->equipasSorteio[$i][$j]['conf']."\n";
				}*/
			}
			
			$this->varBD->fechaLigacao();
			return true;
		}catch(Exception $e){
			unset($this->varBD);
			return false;
		}
	}


	private function funInicializaGrupos($sorteioGrupos){
		try{	
			$grupos = array();
			For($i = 0; $i < sizeof($sorteioGrupos); $i++){
				$varGrupo = new compGrupos('GRUPO_'.$sorteioGrupos[$i], $this->prefixoFase[0], $this->mundialPrefixo, $this->mundialAno);
				array_push($grupos, $varGrupo);
			}
			
			array_push($this->gruposSorteio, $grupos);
			return true;
		}catch(Exception $e){
			return false;
		}
	}

	private function funInsereEquipaGrupo($equipa, $grupo){
		$this->gruposSorteio[0][$grupo]->funSetEquipaGrupo($equipa);
	}
	

	private function funGetGrupoSorteado($grupos){
		$baralha = rand(1, 25);
		for ($indice = 0; $indice <= $baralha; $indice++){
			shuffle($grupos);
		}
		
		return $grupos[0];
	}
	

	private function funSorteiaNivelUm(){
		$faseGrupos = array ('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H');	
		try{	
			$organizador = array();
			$organizador = array_shift($this->equipasSorteio[0]);
			
			$baralha = rand(1, 25);
			for ($indice = 0; $indice <= $baralha; $indice++){
				shuffle($this->equipasSorteio[0]);
			}
			
			array_unshift($this->equipasSorteio[0], $organizador);
			if ($this->funInicializaGrupos($faseGrupos)){
				try{	
					for ($i = 0; $i < sizeof($this->equipasSorteio[0]); $i++){
						$this->funInsereEquipaGrupo($this->equipasSorteio[0][$i], $i);
						/*echo "Equipa a inserir: ".$this->equipasSorteio[0][$i]['nome']."\n";*/
					}
				}catch(Exception $x){
					return false;
				}
			}
			
			return true;
		}catch(Exception $e){
			return false;
		}
	}
	
	
	private function funSorteiaNivelDois(){
		try{
			$baralha = rand(1, 25);
			for ($indice = 0; $indice <= $baralha; $indice++){
				shuffle($this->equipasSorteio[1]);
			}
			
			for ($i = 0; $i < sizeof($this->equipasSorteio[1]); $i++){
				$grupos = array();
				for($j = 0; $j < sizeof($this->equipasSorteio[0]); $j++){
					If ($this->gruposSorteio[0][$j]->equipasGrupo[0]['conf'] == $this->equipasSorteio[1][$i]['conf']){
						If ($this->equipasSorteio[1][$i]['conf'] == 6){
							If (sizeof($this->gruposSorteio[0][$j]->equipasGrupo) == 1){
								/*echo "Tamanho do grupo: ".sizeof($this->gruposSorteio[0][$j]->equipasGrupo)."\n";*/								
								array_push($grupos, $j);	
							}								
						}
					}else{
						If (sizeof($this->gruposSorteio[0][$j]->equipasGrupo) == 1){
							array_push($grupos, $j);
						}
					}
				}
				if (sizeof($grupos) > 0){
					$grupoSorteado = $this->funGetGrupoSorteado($grupos);
					$this->funInsereEquipaGrupo($this->equipasSorteio[1][$i], $grupoSorteado);
					//echo "Tamanho do grupo: ".$grupoSorteado." = ".sizeof($this->gruposSorteio[0][$grupoSorteado]->equipasGrupo)."\n";
					//echo "Grupo: ".$grupoSorteado." Equipa a inserir: ".$this->equipasSorteio[1][$i]['nome']."\n";
				}else
					return false;	
			}
			return true;
		}catch(Exception $e){
			return false;
		}
	}



	private function funSorteiaNivelTres(){
		try{
			$baralha = rand(1, 25);
			for ($indice = 0; $indice <= $baralha; $indice++){
				shuffle($this->equipasSorteio[2]);
			}
			
			for ($i = 0; $i < sizeof($this->equipasSorteio[2]); $i++){
				$grupos = array();
				for($j = 0; $j < sizeof($this->equipasSorteio[0]); $j++){												
					If ($this->gruposSorteio[0][$j]->equipasGrupo[0]['conf'] != $this->equipasSorteio[2][$i]['conf']){
						If ($this->gruposSorteio[0][$j]->equipasGrupo[1]['conf'] == $this->equipasSorteio[2][$i]['conf']){													
							If ($this->gruposSorteio[0][$j]->equipasGrupo[1]['conf'] == 6){
								If (sizeof($this->gruposSorteio[0][$j]->equipasGrupo) == 2){
									array_push($grupos, $j);	
								}								
							}
						}else{
							If (sizeof($this->gruposSorteio[0][$j]->equipasGrupo) == 2){
								array_push($grupos, $j);	
							}								
						}
					}else{
						If ($this->gruposSorteio[0][$j]->equipasGrupo[1]['conf'] != $this->equipasSorteio[2][$i]['conf']){
							If ($this->gruposSorteio[0][$j]->equipasGrupo[0]['conf'] == 6){
								If (sizeof($this->gruposSorteio[0][$j]->equipasGrupo) == 2){
									array_push($grupos, $j);	
								}								
							}													
						}	
					}
				}
				if (sizeof($grupos) > 0){
					$grupoSorteado = $this->funGetGrupoSorteado($grupos);
					/*echo "Tamanho do grupo: ".$grupoSorteado." = ".sizeof($this->gruposSorteio[0][$grupoSorteado]->equipasGrupo)."\n";
					echo "Grupo: ".$grupoSorteado." Equipa a inserir: ".$this->equipasSorteio[2][$i]['nome']."\n";
					*/$this->funInsereEquipaGrupo($this->equipasSorteio[2][$i], $grupoSorteado);
				}else
					return false;	
			}
			return true;
		}catch(Exception $e){
			return false;
		}
	}


	private function funSorteiaNivelQuatro(){
		try{
			$baralha = rand(1, 25);
			for ($indice = 0; $indice <= $baralha; $indice++){
				shuffle($this->equipasSorteio[3]);
			}
			
			for ($i = 0; $i < sizeof($this->equipasSorteio[3]); $i++){
				$grupos = array();
				for($j = 0; $j < sizeof($this->equipasSorteio[0]); $j++){																
					If ($this->gruposSorteio[0][$j]->equipasGrupo[0]['conf'] != $this->equipasSorteio[3][$i]['conf']){
						If ($this->gruposSorteio[0][$j]->equipasGrupo[1]['conf'] == $this->equipasSorteio[3][$i]['conf']){
							If ($this->gruposSorteio[0][$j]->equipasGrupo[2]['conf'] != $this->equipasSorteio[3][$i]['conf']){																						
								If ($this->gruposSorteio[0][$j]->equipasGrupo[1]['conf'] == 6){
									If (sizeof($this->gruposSorteio[0][$j]->equipasGrupo) == 3){
										array_push($grupos, $j);	
									}								
								}
							}
						}else{
							If ($this->gruposSorteio[0][$j]->equipasGrupo[2]['conf'] != $this->equipasSorteio[3][$i]['conf']){
								If (sizeof($this->gruposSorteio[0][$j]->equipasGrupo) == 3){
									array_push($grupos, $j);	
								}								
							}else{																							
								If ($this->gruposSorteio[0][$j]->equipasGrupo[2]['conf'] == 6){
									If (sizeof($this->gruposSorteio[0][$j]->equipasGrupo) == 3){
										array_push($grupos, $j);	
									}								
								}
							}							
						}							
					}else{
						If ($this->gruposSorteio[0][$j]->equipasGrupo[1]['conf'] != $this->equipasSorteio[3][$i]['conf']){
							If ($this->gruposSorteio[0][$j]->equipasGrupo[2]['conf'] != $this->equipasSorteio[3][$i]['conf']){																						
								If ($this->gruposSorteio[0][$j]->equipasGrupo[0]['conf'] == 6){
									If (sizeof($this->gruposSorteio[0][$j]->equipasGrupo) == 3){
										array_push($grupos, $j);	
									}								
								}
							}												
						}	
					}
				}
				if (sizeof($grupos) > 0){
					$grupoSorteado = $this->funGetGrupoSorteado($grupos);
					/*echo "Tamanho do grupo: ".$grupoSorteado." = ".sizeof($this->gruposSorteio[0][$grupoSorteado]->equipasGrupo)."\n";
					echo "Grupo: ".$grupoSorteado." Equipa a inserir: ".$this->equipasSorteio[3][$i]['nome']."\n";					
					*/$this->funInsereEquipaGrupo($this->equipasSorteio[3][$i], $grupoSorteado);
				}else
					return false;	
			}
			return true;
		}catch(Exception $e){
			return false;
		}
	}

	private function funUnsetVars(){
		unset($this->varBD);
		unset($this->gruposSorteio);
		unset($this->equipasSorteio);
		unset($this->idConf);
		unset($this->prefixoFase);
		
		unset($this->mundialPrefixo);
		unset($this->qualCompPrefix);
		unset($this->mundialOrganizador);
		unset($this->mundialAno);			
	}

	
	
	public function funInsereDadosBD(){
		require_once ('classes/procedimentosSorteio.class.php');
		$returnCode = 0;
		/* Insersão geral */
		$procSorteio = new procedimentosSorteio();
		$returnCode = $procSorteio->fechaCompeticao($this->qualCompPrefix, $this->qualCompPrefix.$this->mundialAno);
		if (!$returnCode){
			$returnCode = $procSorteio->insereCompeticaoActual($this->mundialPrefixo, $this->mundialPrefixo.$this->mundialAno, $this->mundialOrganizador);
			if (!$returnCode){
				$returnCode = $procSorteio->insereCompeticaoActual($this->prefixoFase[0], $this->mundialPrefixo.$this->mundialAno, $this->mundialOrganizador);				
				If (!$returnCode){
					For($grupo = 0; $grupo < sizeof($this->gruposSorteio[0]); $grupo++){
						$returnCode = $procSorteio->insereGruposCompeticao($this->gruposSorteio[0][$grupo]);			
		                If (!$returnCode){
		                    $returnCode = $procSorteio->insereJogosCompeticao($this->gruposSorteio[0][$grupo]);
		                }else
							return false;				
					}			
				}else
					return false;
			}else
				return false;				
		}else
			return false;		
		unset($procSorteio);	
		return true;
	}
	
	
	private function funGeraJogosGrupo(){

		For($grupo = 0; $grupo < sizeof($this->gruposSorteio[0]); $grupo++){
			For($equipa = 0; $equipa < sizeof($this->gruposSorteio[0][$grupo]->equipasGrupo); $equipa++){
				$myEquipa = $this->gruposSorteio[0][$grupo]->equipasGrupo[$equipa]['nome'];								
				$this->gruposSorteio[0][$grupo]->equipasGrupo[$equipa] = $myEquipa;				
			}
			echo $this->gruposSorteio[0][$grupo]->fun_getCompID()."\n";
			$sorteioErro = $this->gruposSorteio[0][$grupo]->funSetGrupoJogosMundial($this->gruposSorteio[0][$grupo]->fun_getCompID());			
		}
		return $sorteioErro;	
	}
	
	private function funImprimeGrupos(){
		
		/*For($grupo = 0; $grupo < sizeof($this->gruposSorteio[0]); $grupo++){
			echo "Grupo: ".$this->gruposSorteio[0][$grupo]->fun_getCompID().'_'.$this->gruposSorteio[0][$grupo]->fun_getCompFase().'_'.$this->gruposSorteio[0][$grupo]->fun_getGrupoID()."\n";
			For($equipa = 0; $equipa < sizeof($this->gruposSorteio[0][$grupo]->equipasGrupo); $equipa++){		
				echo $this->gruposSorteio[0][$grupo]->equipasGrupo[$equipa]['nome']."\n";
			}	
		}*/
		
				
	}


	
}

?>