<?php
require_once ('classes/executeDBOperations.class.php');
//require_once ('simulador/criterios/funcoesCriterios.include.php');

Class simuladorEquipa{
	private $eqJogoId;
	private $eqNome;
	private $eqNivelAdv;
	private $eqNivel;
	private $eqConf;
	private $eqLocal; //'C'asa ou 'F'ora
	private $valoresIniciais = array();
	private $criterioI = array();
	private $criterioII = array();
	private $criterioIII = array();
	private $golosMarcados = array();
	private $golosSofridos = array();
	
	private $returnCode = 0;
 

	public function __construct($equipa, $local, $jogoID){		
		$this->eqNome = $equipa;
		$this->eqLocal = $local;
		$this->eqJogoId = $jogoID;
		$this->funGetEquipaConf();
		If ($this->returnCode < 0)
			break;
		Else{
			$this->funGetNivel();
			If ($this->returnCode < 0)
				break;
			Else{
				$this->funGetNivelAdv();				
				If ($this->returnCode == 0){
					$this->funSetValoresIniciais();
					//echo "Valores: ".$this->valoresIniciais[0]." - ".$this->valoresIniciais[1]." - ".$this->valoresIniciais[2]."\n";
					If ($this->returnCode == 0){
						$this->funSetCriterioI();
						//echo "Criterio I: ".$this->criterioI[0]." - ".$this->criterioI[1]." - ".$this->criterioI[2]."\n";						
						If ($this->returnCode == 0){
							$this->funSetCriterioII();
							//echo "Criterio II: ".$this->criterioII[0]." - ".$this->criterioII[1]." - ".$this->criterioII[2]."\n";
							If ($this->returnCode == 0){
								$this->funSetCriterioIII();
								//echo "Criterio III: ".$this->criterioIII[0]." - ".$this->criterioIII[1]." - ".$this->criterioIII[2]."\n";
								If ($this->returnCode == 0){
									$this->funSetEquipaGolos();
									//echo "Criterio III: ".$this->criterioIII[0]." - ".$this->criterioIII[1]." - ".$this->criterioIII[2]."\n";
								}																						
							}						
						}
					}					
				}
			}
		}
	}
	
	/* Preenche a confederação da equipa */
	private function funGetEquipaConf(){		
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
			
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			$this->returnCode = -904; //Erro de ligação a BD
			break;
		}		
		try{
			$varBD->queryString = 'select tab_equipas_conf from tab_equipas where tab_equipas_trig = :EQU';
		
	        $varBD->parseDBQuery($varBD->queryString);		
			oci_bind_by_name($varBD->queryParse, ':EQU', $this->eqNome);
			
			oci_execute($varBD->queryParse);
			$row = oci_fetch_array($varBD->queryParse);
			$this->eqConf = $row[0];

			$varBD->fechaLigacao();
			unset($varBD);
		}catch(Exception $e){
			$varBD->fechaLigacao();
			unset($varBD);
			$this->returnCode = -909;
		}				
	}
	
	
	/*Determina o ranking da equipa e vai chamar outra função que preenche o nivel da equipa*/
	private function funGetNivel(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		
		$posEquipa = 0;
			
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			$this->returnCode = -904; //Erro de ligação a BD
			break;
		}
		try{
			$varBD->queryString = 'select tab_equipas_conf_rank from tab_equipas where tab_equipas_trig = :EQU';
		
	        $varBD->parseDBQuery($varBD->queryString);		
			oci_bind_by_name($varBD->queryParse, ':EQU', $this->eqNome);
			
			oci_execute($varBD->queryParse);
			$row = oci_fetch_array($varBD->queryParse);
			$posEquipa = $row[0];
						
			//Agora vai chamar uma função que irá determinar o nivel da equipa
			$this->eqNivel = $this->funsetNivelEquipa($posEquipa);			
			$varBD->fechaLigacao();
			unset($varBD);
		}catch(Exception $e){
			$varBD->fechaLigacao();
			unset($varBD);
			$this->returnCode = -913;
		}						
	}	

	/* Determina e preenche o nivel da equipa */
	private function funSetNivelEquipa($posEquipa){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();				
			
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			$this->returnCode = -904; //Erro de ligação a BD
			break;
		}
		try{
			$varBD->queryString = 'select tab_niveis_nivel ';
			$varBD->queryString .='from tab_niveis ';
			$varBD->queryString .='where tab_niveis_conf = :CONF ';
			$varBD->queryString .='and (tab_niveis_min_pos <= :POS and tab_niveis_max_pos >= :POS)';
		
	        $varBD->parseDBQuery($varBD->queryString);		
			oci_bind_by_name($varBD->queryParse, ':CONF', $this->eqConf);
			oci_bind_by_name($varBD->queryParse, ':POS', $posEquipa);
			
			oci_execute($varBD->queryParse);
			$row = oci_fetch_array($varBD->queryParse);
			$posEquipa = $row[0];

			$varBD->fechaLigacao();
			unset($varBD);
			return $posEquipa;
		}catch(Exception $e){
			$varBD->fechaLigacao();
			unset($varBD);
			$this->returnCode = -912;
		}						
	}
	

	/*Determina o ranking da equipa e vai chamar outra função que preenche o nivel da equipa*/
	private function funGetNivelAdv(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		
		$equipaAdv;
		$posEq = 0;
			
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			$this->returnCode = -904; //Erro de ligação a BD
			break;
		}
		try{
			If ($this->eqLocal == 'C')	
				$varBD->queryString = 'select tab_jogos_equipa_fora from tab_jogos where tab_jogos_id = :JOGO';
			ElseIf($this->eqLocal == 'F')
				$varBD->queryString = 'select tab_jogos_equipa_casa from tab_jogos where tab_jogos_id = :JOGO';
		
	        $varBD->parseDBQuery($varBD->queryString);		
			oci_bind_by_name($varBD->queryParse, ':JOGO', $this->eqJogoId);
			
			oci_execute($varBD->queryParse);
			$row = oci_fetch_array($varBD->queryParse);
			$equipaAdv = $row[0];
			//echo "Adv: ".$equipaAdv."\n";  OK
			$varBD->fechaLigacao();

			$varBDII = new ExecuteDBOperations();
			$varBDII->abreLigacao();

			If ($varBDII->funGetConnection() == null){
				unset($varBDII);
				$this->returnCode = -904; //Erro de ligação a BD
				return 0;				
			}
			try{
				$varBDII->queryString = 'select tab_equipas_conf_rank from tab_equipas where tab_equipas_trig = :EQU';
			
		        $varBDII->parseDBQuery($varBDII->queryString);		
				oci_bind_by_name($varBDII->queryParse, ':EQU', $equipaAdv);
				
				oci_execute($varBDII->queryParse);
				$row = oci_fetch_array($varBDII->queryParse);
				$posEq = $row[0];
				$varBDII->fechaLigacao();				
			}catch(Exception $e){
				$varBDII->fechaLigacao();
				unset($varBDII);
				$this->returnCode = -913;
			}			
			//Agora vai chamar uma função que irá determinar o nivel da equipa
			$this->eqNivelAdv = $this->funSetNivelEquipaAdv($posEq);			
			
			unset($varBD);
			unset($varBDII);			
		}catch(Exception $e){
			$varBD->fechaLigacao();
			unset($varBD);
			unset($varBDII);
			$this->returnCode = -913;
		}						
	}	

	/* Determina e preenche o nivel da equipa */
	private function funSetNivelEquipaAdv($posEquipa){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();				
			
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			$this->returnCode = -904; //Erro de ligação a BD
			break;
		}
		try{
			$varBD->queryString = 'select tab_niveis_nivel ';
			$varBD->queryString .='from tab_niveis ';
			$varBD->queryString .='where tab_niveis_conf = :CONF ';
			$varBD->queryString .='and (tab_niveis_min_pos <= :POS and tab_niveis_max_pos >= :POS)';
		
	        $varBD->parseDBQuery($varBD->queryString);		
			oci_bind_by_name($varBD->queryParse, ':CONF', $this->eqConf);
			oci_bind_by_name($varBD->queryParse, ':POS', $posEquipa);
			
			oci_execute($varBD->queryParse);
			$row = oci_fetch_array($varBD->queryParse);
			$posEquipa = $row[0];

			$varBD->fechaLigacao();
			unset($varBD);
			return $posEquipa;
		}catch(Exception $e){
			$varBD->fechaLigacao();
			unset($varBD);
			$this->returnCode = -912;
		}						
	}	
		




/***********************************************************************************************************************************
* Calculo dos valores iniciais
************************************************************************************************************************************/
	
	/* Vai calcular os valores iniciais da equipa */
	private function funSetValoresIniciais(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
			
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			$this->returnCode = -904; //Erro de ligação a BD
			break;
		}		
		try{
			$varBD->queryString = 'select tab_equipas_jogos, tab_equipas_vit, tab_equipas_emp, tab_equipas_drr ';
			$varBD->queryString .= 'from tab_equipas where tab_equipas_trig = :EQU';
		
	        $varBD->parseDBQuery($varBD->queryString);		
			oci_bind_by_name($varBD->queryParse, ':EQU', $this->eqNome);
			
			oci_execute($varBD->queryParse);
			$row = oci_fetch_array($varBD->queryParse);
			
			If ($row[0] == 0){ //Vai buscar os valores iniciais à tabela tab_niveis
				$this->funGetValoresStandard();
			}Else{			
				for ($i = 1; $i < 4; $i++){
					array_push($this->valoresIniciais, $this->funGetValor($row[0], $row[$i]));
				}
			}

			$varBD->fechaLigacao();
			unset($varBD);
		}catch(Exception $e){
			$varBD->fechaLigacao();
			unset($varBD);
			$this->returnCode = -910;
		}				
	}
		
	
	/* Vai determinar os valores standard da equipa */
	private function funGetValoresStandard(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
			
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			$this->returnCode = -904; //Erro de ligação a BD
			break;
		}
		try{
			$varBD->queryString = 'select tab_niveis_vit, tab_niveis_emp, tab_niveis_drr from tab_niveis where tab_niveis_conf = :CONF and tab_niveis_nivel = :NIV';			
		
	        $varBD->parseDBQuery($varBD->queryString);		
			oci_bind_by_name($varBD->queryParse, ':CONF', $this->eqConf);
			oci_bind_by_name($varBD->queryParse, ':NIV', $this->eqNivel);
			
			oci_execute($varBD->queryParse);
			$row = oci_fetch_array($varBD->queryParse);
			array_push($this->valoresIniciais, $row[0], $row[1], $row[2]);

			$varBD->fechaLigacao();
			unset($varBD);
		}catch(Exception $e){
			$varBD->fechaLigacao();
			unset($varBD);
			$this->returnCode = -914;
		}						
	}
	
	
	private function funGetValor($jogos, $results){
		return round(($results * 100)/$jogos);
	}

/***********************************************************************************************************************************
* Calculo do numero de jogos da equipa
************************************************************************************************************************************/
	private function funGetnumJogosEquipa(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			$this->returnCode = -904; //Erro de ligação a BD
			break;
		}try{
			$varBD->queryString = 'select tab_equipas_jogos from tab_equipas where tab_equipas_trig = :EQU';
			$varBD->parseDBQuery($varBD->queryString);		
			oci_bind_by_name($varBD->queryParse, ':EQU', $this->eqNome);						
			oci_execute($varBD->queryParse);
			$row = oci_fetch_array($varBD->queryParse);
			return $row[0];
		}catch(Exception $e){
			$varBD->fechaLigacao();
			unset($varBD);
			$this->returnCode = -914;
		}						
	}



/***********************************************************************************************************************************
* Calculo dos valores do CriterioI
************************************************************************************************************************************/

	private function funSetCriterioI(){
		//Variáveis
		$equJogos;
		$primJogos = array();
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
			
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			$this->returnCode = -904; //Erro de ligação a BD
			break;
		}		
		try{			
			$equJogos = $this->funGetNumJogosEquipa();
			If ($this->returnCode < 0){
				$varBD->fechaLigacao();	
				unset($varBD);
				break;	
			}elseif ($equJogos >= 5){			
				$varBD->queryString = 'select tab_jogos_equipa_casa, tab_jogos_res_casa+nvl(tab_jogos_res_casa_prol, 0), '; 
	       		$varBD->queryString .= 'tab_jogos_equipa_fora, tab_jogos_res_fora+nvl(tab_jogos_res_fora_prol, 0) '; 
				$varBD->queryString .= 'from ( ';
	  			$varBD->queryString .= 'select * from tab_jogos '; 
	  			$varBD->queryString .= 'where (tab_jogos_equipa_casa = :EQU or tab_jogos_equipa_fora = :EQU) ';
	  			$varBD->queryString .= 'and tab_jogos_data is not null ';
	  			$varBD->queryString .= 'order by tab_jogos_data desc, tab_jogos_id asc) ';
				$varBD->queryString .= 'where rownum <= 5 ';
				$varBD->queryString .= 'order by rownum desc';			
			
		        $varBD->parseDBQuery($varBD->queryString);		
				oci_bind_by_name($varBD->queryParse, ':EQU', $this->eqNome);
				
				oci_execute($varBD->queryParse);
			
				While ($row = oci_fetch_array($varBD->queryParse)){
					$jogos = array();
					array_push($jogos, $row[0], $row[1], $row[2], $row[3]);
					array_push($primJogos, $jogos);
				}
				unset($jogos);
				//$primJogos já tem o resultado dos ultimos 5 jogos (por ordem). Vamos determinar o desfecho
				$primJogos = $this->funJogosRes($primJogos, $this->eqNome);								
				$this->criterioI = $this->calculaValoresCriterio($primJogos); 
				unset($primJogos);		
			}elseif ($equJogos < 5){
				//Vai igualar os valores iniciais ao critério I
				$this->criterioI = $this->valoresIniciais;
			}
			//Após os calculos estarem feitos, o array vai ser invertido, caso se trate da equipa que joga FORA
			//Se se tratar da equipa que joga em casa, não se mexe no array!!!!
			if ($this->eqLocal == 'F')
				$this->criterioI = array_reverse($this->criterioI);									
		}catch(Exception $e){
			$varBD->fechaLigacao();
			unset($varBD);
			$this->returnCode = -914;
		}
	}


/***********************************************************************************************************************************
* Calculo dos valores do CriterioII
************************************************************************************************************************************/

	private function funSetCriterioII(){
		//Variáveis
		$mundialFutebol = 'CMF%';
		$equJogos;
		$primJogos = array();
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
			
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			$this->returnCode = -904; //Erro de ligação a BD
			break;
		}		
		try{			
			$equJogos = $this->funGetNumJogosEquipa();
			If ($this->returnCode < 0){
				$varBD->fechaLigacao();	
				unset($varBD);
				break;	
			}elseif ($equJogos >= 5){			
				$varBD->queryString = 'select tab_jogos_equipa_casa, tab_jogos_res_casa+nvl(tab_jogos_res_casa_prol, 0), '; 
	       		$varBD->queryString .= 'tab_jogos_equipa_fora, tab_jogos_res_fora+nvl(tab_jogos_res_fora_prol, 0) '; 
				$varBD->queryString .= 'from ( ';
	  			$varBD->queryString .= 'select * from tab_jogos ';
				if ($this->eqLocal == 'C') 
	  				$varBD->queryString .= 'where tab_jogos_equipa_casa = :EQU ';
				elseif ($this->eqLocal =='F')
					$varBD->queryString .= 'where tab_jogos_equipa_fora = :EQU ';
	  			$varBD->queryString .= 'and tab_jogos_data is not null ';				
				$varBD->queryString .= 'and tab_jogos_grupo_id not like :CMF ';
	  			$varBD->queryString .= 'order by tab_jogos_data desc, tab_jogos_id asc) ';
				$varBD->queryString .= 'where rownum <= 5 ';
				$varBD->queryString .= 'order by rownum desc';			
			
		        $varBD->parseDBQuery($varBD->queryString);		
				oci_bind_by_name($varBD->queryParse, ':EQU', $this->eqNome);
				oci_bind_by_name($varBD->queryParse, ':CMF', $mundialFutebol);
				
				oci_execute($varBD->queryParse);
			
				While ($row = oci_fetch_array($varBD->queryParse)){
					$jogos = array();
					array_push($jogos, $row[0], $row[1], $row[2], $row[3]);
					array_push($primJogos, $jogos);
				}
				unset($jogos);
				//$primJogos já tem o resultado dos ultimos 5 jogos (por ordem). Vamos determinar o desfecho				
				$primJogos = $this->funJogosRes($primJogos, $this->eqNome);								
				$this->criterioII = $this->calculaValoresCriterio($primJogos); 
				unset($primJogos);		
			}elseif ($equJogos < 5){
				//Vai igualar os valores iniciais ao critério I
				$this->criterioII = $this->valoresIniciais;
			}
			//Após os calculos estarem feitos, o array vai ser invertido, caso se trate da equipa que joga FORA
			//Se se tratar da equipa que joga em casa, não se mexe no array!!!!
			if ($this->eqLocal == 'F')
				$this->criterioII = array_reverse($this->criterioII);									
		}catch(Exception $e){
			$varBD->fechaLigacao();
			unset($varBD);
			$this->returnCode = -914;
		}
	}



/***********************************************************************************************************************************
* Calculo dos valores do CriterioIII
************************************************************************************************************************************/

	private function funSetCriterioIII(){
		//Variáveis
		$mundialFutebol = 'CMF%';
		$equJogos;
		$primJogos = array();
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
			
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			$this->returnCode = -904; //Erro de ligação a BD
			break;
		}		
		try{			
			$equJogos = $this->funGetNumJogosEquipa();
			If ($this->returnCode < 0){
				$varBD->fechaLigacao();	
				unset($varBD);
				break;	
			}elseif ($equJogos >= 5){			
				$varBD->queryString = 'select tab_jogos_equipa_casa, tab_jogos_res_casa+nvl(tab_jogos_res_casa_prol, 0), '; 
				$varBD->queryString .= 'tab_jogos_equipa_fora, tab_jogos_res_fora+nvl(tab_jogos_res_fora_prol, 0) from ( ';
				$varBD->queryString .= 'select * from ( ';
				$varBD->queryString .= 'select * from tab_jogos '; 
				$varBD->queryString .= 'where tab_jogos_equipa_casa = :EQU and tab_jogos_equipa_fora in ( ';
				$varBD->queryString .= 'select tab_equipas_trig from tab_equipas where tab_equipas_conf = :CONF ';
				$varBD->queryString .= 'and tab_equipas_conf_rank <= (select tab_niveis_max_pos from tab_niveis ';
				$varBD->queryString .= 'where tab_niveis_conf = :CONF and tab_niveis_nivel = :NVADV) ';
				$varBD->queryString .= 'and tab_equipas_conf_rank >= (select tab_niveis_min_pos ';
				$varBD->queryString .= 'from tab_niveis where tab_niveis_conf = :CONF and tab_niveis_nivel = :NVADV)) ';
				$varBD->queryString .= 'and tab_jogos_data is not null ';                                                                 
				$varBD->queryString .= 'union all ';
				$varBD->queryString .= 'select * from tab_jogos '; 
				$varBD->queryString .= 'where tab_jogos_equipa_fora = :EQU and tab_jogos_equipa_casa in ( ';
				$varBD->queryString .= 'select tab_equipas_trig from tab_equipas where tab_equipas_conf = :CONF ';
				$varBD->queryString .= 'and tab_equipas_conf_rank <= (select tab_niveis_max_pos from tab_niveis ';
				$varBD->queryString .= 'where tab_niveis_conf = :CONF and tab_niveis_nivel = :NVADV) ';
				$varBD->queryString .= 'and tab_equipas_conf_rank >= (select tab_niveis_min_pos '; 
				$varBD->queryString .= 'from tab_niveis where tab_niveis_conf = :CONF and tab_niveis_nivel = :NVADV)) ';
				$varBD->queryString .= 'and tab_jogos_data is not null) ';
				$varBD->queryString .= 'order by tab_jogos_data) ';
				$varBD->queryString .= 'where rownum <= 5 ';
				$varBD->queryString .= 'order by rownum';
			
		        $varBD->parseDBQuery($varBD->queryString);		
				oci_bind_by_name($varBD->queryParse, ':EQU', $this->eqNome);
				oci_bind_by_name($varBD->queryParse, ':CONF', $this->eqConf);
				oci_bind_by_name($varBD->queryParse, ':NVADV', $this->eqNivelAdv);
				
				oci_execute($varBD->queryParse);
			
				While ($row = oci_fetch_array($varBD->queryParse)){
					$jogos = array();
					array_push($jogos, $row[0], $row[1], $row[2], $row[3]);										
					array_push($primJogos, $jogos);					
				}
				unset($jogos);
				//$primJogos já tem o resultado dos ultimos 5 jogos (por ordem). Vamos determinar o desfecho
				$primJogos = $this->funJogosRes($primJogos, $this->eqNome);
				$this->criterioIII = $this->calculaValoresCriterio($primJogos);
				unset($primJogos);		
			}elseif ($equJogos < 5){
				//Vai igualar os valores iniciais ao critério I
				$this->criterioIII = $this->valoresIniciais;
			}
			//Após os calculos estarem feitos, o array vai ser invertido, caso se trate da equipa que joga FORA
			//Se se tratar da equipa que joga em casa, não se mexe no array!!!!
			if ($this->eqLocal == 'F')
				$this->criterioIII = array_reverse($this->criterioIII);									
		}catch(Exception $e){
			$varBD->fechaLigacao();
			unset($varBD);
			$this->returnCode = -914;
		}
	}





/***********************************************************************************************************************************
* Devolve o código de erro
************************************************************************************************************************************/
	
	public function funGetReturnCode(){
		return $this->returnCode;
	} 

/***********************************************************************************************************************************
* Calculo do desfecho dos jogos com base nos resultados
************************************************************************************************************************************/
	private function funJogosRes($jogos, $equipa){
		$desfechoJogos = array();			
		for ($idx = 0; $idx < sizeof($jogos); $idx++){
			if ($jogos[$idx][0] == $equipa){
				if ($jogos[$idx][1] > $jogos[$idx][3])
					array_push($desfechoJogos, 'V');
				elseif ($jogos[$idx][1] < $jogos[$idx][3])
					array_push($desfechoJogos, 'D');
				else
					array_push($desfechoJogos, 'E');
											
			}elseif($jogos[$idx][2] == $equipa){
				if ($jogos[$idx][1] > $jogos[$idx][3])
					array_push($desfechoJogos, 'D');
				elseif ($jogos[$idx][1] < $jogos[$idx][3])
					array_push($desfechoJogos, 'V');
				else
					array_push($desfechoJogos, 'E');				
			}			
		}
 
		return $desfechoJogos;
	}





/***********************************************************************************************************************************
* Calculo dos valores dos criterios com base no resultado dos jogos
************************************************************************************************************************************/
	private function calculaValoresCriterio($jogos){
		$vit = $this->valoresIniciais[0];
		$emp = $this->valoresIniciais[1];
		$drr = $this->valoresIniciais[2];
		$valorFinal = array();
				
		for ($idx = 0; $idx < sizeof($jogos); $idx++){
			if ($jogos[$idx] == 'V'){
				If ($drr > 0){
					$vit = round($vit + ((($idx + 1)/10) * $drr)/2);
					$drr = round($drr - ((($idx + 1)/10) * $drr)/2);
				}else{
					$vit = round($vit + ((($idx + 1)/10) * 5)/2);
					If ($emp >= ((($idx + 1)/10) * 5)/2){
						$emp = round($emp - ((($idx + 1)/10) * 5)/2);
					}else{
						$emp = 0;
					}
					$drr = 0;
				}
			}elseif ($jogos[$idx] == 'D'){
				If ($vit > 0){
					$drr = round($drr + ((($idx + 1)/10) * $vit)/2);					
					$vit = round($vit - ((($idx + 1)/10) * $vit)/2);
				}else{
					$drr = round($drr + ((($idx + 1)/10) * 5)/2);
					If ($emp >= ((($idx + 1)/10) * 5)/2){
						$emp = round($emp - ((($idx + 1)/10) * 5)/2);
					}else{
						$emp = 0;
					}
					$vit = 0;
				}
			}elseif ($jogos[$idx] == 'E'){
				$bolo = $vit + $drr;
				if ($bolo > 0){
					if ($vit > (((($idx + 1)/10) * $bolo)/2)/2){
						$vit = round($vit - (((($idx + 1)/10) * $bolo)/2)/2);
					}else
						$vit = 0;					
					if ($drr > (((($idx + 1)/10) * $bolo)/2)/2){
						$drr = round($drr - (((($idx + 1)/10) * $bolo)/2)/2);
					}else
						$drr = 0;
					$emp = round($emp + ((($idx + 1)/10) * $bolo)/2);
				}else{
					if ($vit > (((($idx + 1)/10) * 5)/2)/2){
						$vit = round($vit - (((($idx + 1)/10) * 5)/2)/2);
					}else
						$vit = 0;
					if ($drr > (((($idx + 1)/10) * 5)/2)/2)
						$drr = round($drr - (((($idx + 1)/10) * 5)/2)/2);
					else
						$drr = 0;
					$emp = round($emp + ((($idx + 1)/10) * 5)/2);					
				}
			}
		}
		array_push($valorFinal, $vit, $emp, $drr);
		return $valorFinal;	
	}

	private function funSetEquipaGolos(){
		//Variáveis
		$mundialFutebol = 'CMF%';
		$equJogos;
		$primJogos = array();
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
			
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			$this->returnCode = -904; //Erro de ligação a BD
			break;
		}		
		try{			
			If ($this->returnCode < 0){
				$varBD->fechaLigacao();	
				unset($varBD);
				break;	
			}else{			
				$varBD->queryString = 'select tab_jogos_equipa_casa, tab_jogos_res_casa, '; 
				$varBD->queryString .= 'tab_jogos_equipa_fora, tab_jogos_res_fora '; 
				$varBD->queryString .= 'from ( ';
				$varBD->queryString .= 'select * from tab_jogos '; 
				$varBD->queryString .= 'where (tab_jogos_equipa_casa = :EQU or tab_jogos_equipa_fora = :EQU) ';
				$varBD->queryString .= 'and tab_jogos_data is not null ';
				$varBD->queryString .= 'and substr(tab_jogos_grupo_id, 1, 3) <> :CMF ';
				$varBD->queryString .= 'order by tab_jogos_data desc, tab_jogos_id asc) ';
				$varBD->queryString .= 'order by rownum desc';
		        $varBD->parseDBQuery($varBD->queryString);		
				oci_bind_by_name($varBD->queryParse, ':EQU', $this->eqNome);
				oci_bind_by_name($varBD->queryParse, ':CMF', $mundialFutebol);
				
				oci_execute($varBD->queryParse);
			
				While ($row = oci_fetch_array($varBD->queryParse)){
					If ($this->eqNome == $row[0]){
						array_push($this->golosMarcados, $row[1]);
						array_push($this->golosSofridos, $row[3]);
					}elseIf ($this->eqNome == $row[2]){
						array_push($this->golosMarcados, $row[3]);
						array_push($this->golosSofridos, $row[1]);
					}
				}
				
				If (sizeof($this->golosMarcados) == 0)
					array_push($this->golosMarcados, 0,0,0,1,1,1,2,2,2);
				If (sizeof($this->golosSofridos) == 0)
					array_push($this->golosSofridos, 0,0,0,1,1,1,2,2,2);				
			}
		}catch(Exception $e){
			$varBD->fechaLigacao();
			unset($varBD);
			$this->returnCode = -914;
		}
		
	}

/****************************************************************************************************************************************
 * Funções de retorno de valores
 ****************************************************************************************************************************************/
/****************************************************************************************************************************************
 * Retorno o nome da equipa
 ****************************************************************************************************************************************/		
public function funGetEquipaNome(){
	Return $this->eqNome;
} 
 
		
/****************************************************************************************************************************************
 * Retorno do critério 1
 ****************************************************************************************************************************************/		
 public function funDevolveCriterioI(){
 	Return $this->criterioI;
 }
 
/****************************************************************************************************************************************
 * Retorno do critério 2
 ****************************************************************************************************************************************/		
 public function funDevolveCriterioII(){
 	Return $this->criterioII;
 }

/****************************************************************************************************************************************
 * Retorno do critério 3
 ****************************************************************************************************************************************/		
 public function funDevolveCriterioIII(){
 	Return $this->criterioIII;
 }
 
/****************************************************************************************************************************************
 * Retorno o nivel da equipa
 ****************************************************************************************************************************************/		
 public function funDevolveEquipaNivel(){
 	Return $this->eqNivel;
 }

/****************************************************************************************************************************************
 * Retorno a confederação da equipa
 ****************************************************************************************************************************************/		
 public function funDevolveEquipaConf(){
 	Return $this->eqConf;
 }
 
 /****************************************************************************************************************************************
 * Retorno dos golos marcados e sofridos
 ****************************************************************************************************************************************/		
 public function funDevolveGolosMar(){
 	Return $this->golosMarcados;
 }
 
 public function funDevolveGolosSof(){
 	Return $this->golosSofridos;
 }
}
    
?>