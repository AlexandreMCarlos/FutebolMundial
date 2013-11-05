<?php

require_once ('classes/executeDBOperations.class.php');

Class funcoesAuxPlf{
	protected $grupo; //Id do grupo
	protected $classGrupo; //Classificação de uma equipa dentro do grupo
	protected $equipaGrupo = null; //A equipa
	protected $apurados = 0;
	protected $intPlf = array();
	
	
	
	public function __construct($inGrupo, $inGrupoClass){			
		$this->grupo = $inGrupo;
		$this->classGrupo = $inGrupoClass;
		echo "Grupo: ".$this->grupo."\n";
		echo "Apurado: ".$this->classGrupo."\n";		
	}
	
	public function funGetGrupo(){
		return $this->grupo;
	}
	
	public function funGetClassGrupo(){
		return $this->classGrupo;	
	}
	
	public function funGetEquipaGrupo(){
		return $this->equipaGrupo;
	}
	
	private function funGetGrupoAno(){
		return substr($this->grupo, 10, 4);
	}
	
	public function funSetEquClassGrupo($equipa){
		$this->equipaGrupo = $equipa;	
	}
	
	public function funGetEquClassGrupo(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
		
		$varBD->queryString = 'select tab_grupos_equipa from tab_grupos where tab_grupos_id = :GID and tab_grupos_class = :GCL';
        $varBD->parseDBQuery($varBD->queryString);		
		oci_bind_by_name($varBD->queryParse, ':GID', $this->grupo);
		oci_bind_by_name($varBD->queryParse, ':GCL', $this->classGrupo);
		
		oci_execute($varBD->queryParse);
		
		$row = oci_fetch_array($varBD->queryParse);
		$this->equipaGrupo = $row[0];
		echo "Equipa: ".$this->equipaGrupo."\n";
		$varBD->fechaLigacao();
		unset($varBD);
	
		return true;				
	}
	
	
	public function funSetEquipaPlfInt(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
		
		If (!isset($this->equipaGrupo)){
			return false;			
		}else{
			try{	
				$ano = $this->funGetGrupoAno();
				
				$varBD->queryString = 'Begin :retval := package_competicoes.funInsereEquipaPLF(:EQU, :ANO); End;';
	        	$varBD->parseDBQuery($varBD->queryString);
				
				oci_bind_by_name($varBD->queryParse, ':EQU', $this->equipaGrupo);
				oci_bind_by_name($varBD->queryParse, ':ANO', $ano);				
				oci_bind_by_name($varBD->queryParse, ':retVal', $returnCode, 50);
				
				oci_execute($varBD->queryParse);
				
				$varBD->fechaLigacao(); 		
				unset($varBD);							

				return $returnCode;
			}catch(Exception $e){
				return false;
			}			
		}
	}
	
	private function funGetPlfApr($compAno){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
		
		try{
			
			$varBD->queryString = 'select count(1) from tab_plf_internacional where tab_plf_ano = :ANO';

        	$varBD->parseDBQuery($varBD->queryString);
			oci_bind_by_name($varBD->queryParse, ':ANO', $compAno);							
			
			oci_execute($varBD->queryParse);
			
			$row = oci_fetch_array($varBD->queryParse);
			$plfApr = $row[0];
			$varBD->fechaLigacao();
			unset($varBD);
		
			return $plfApr;
		}catch(Exception $e){
			return -1;
		}			
	} 
	
	public function funVerificaPlfOK(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
		
		try{	
			$ano = $this->funGetGrupoAno();
			echo "Ano: ".$ano."\n";
			
			$varBD->queryString = 'select tab_equipas_conf';
			$varBD->queryString .= ' from tab_equipas t_e, tab_fifa_wc t_f_wc';
			$varBD->queryString .= ' Where t_e.tab_equipas_trig = t_f_wc.tab_wc_equipa';
			$varBD->queryString .= ' and t_f_wc.tab_wc_ano = :ANO';
			$varBD->queryString .= ' and t_f_wc.tab_wc_rank = 0';
        	$varBD->parseDBQuery($varBD->queryString);
						
			oci_bind_by_name($varBD->queryParse, ':ANO', $ano);				
			
			
			oci_execute($varBD->queryParse);
			
			$row = oci_fetch_array($varBD->queryParse);
			$mundialOrgConf = $row[0];
			$varBD->fechaLigacao();
			unset($varBD);
			
			$this->apurados = $this->funGetPlfApr($ano);
			echo "Org conf.: ".$mundialOrgConf."\n";
			echo "Pré apurados: ".$this->apurados."\n";
			
			If ($this->apurados < 0)
				return false;
			else{
				If ($mundialOrgConf == 4 || $mundialOrgConf == 5 || $mundialOrgConf == 1){
					If ($this->apurados == 2)
						return true;
					else 
						return false;							
				}else{
					If ($this->apurados == 4)
						return true;
					else
						return false;
				} 
			}
					
		}catch(Exception $e){
			return -1;
		}		
	}


	public function funGetPlfEquipas($compAno){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
		
		try{	
			$varBD->queryString = 'select tab_plf_equipa from tab_plf_internacional where tab_plf_ano = :ANO';

        	$varBD->parseDBQuery($varBD->queryString);
			oci_bind_by_name($varBD->queryParse, ':ANO', $compAno);							
			
			oci_execute($varBD->queryParse);
			
			While ($row = oci_fetch_array($varBD->queryParse)){
				array_push($this->intPlf, $row[0]);						
			}

			$varBD->fechaLigacao();
			unset($varBD);
			
			return true;
							
		}catch(Exception $e){
			$varBD->fechaLigacao();
			unset($varBD);			
			$this->intPlf = array();
			return false;
		}		
	}



 	public function funSorteiaPlfInt($compPrefix, $compAno, $organizador){
		$proxFase = 'PLF_01';
		$compPrefix = 'PLF';
		If ($this->apurados == 2){	
			$faseGrupos = array ('A');
			$divide = 1;
		}else{
			$faseGrupos = array ('A', 'B');
			$divide = 2;			
		}
			
		try{
			require_once ('classes/compGrupos.class.php');
			require_once ('classes/procedimentosSorteio.class.php');
			//Terei que ir à BD buscar o organizador da competicao
			
			If ($organizador != -1){
				$sorteio = new procedimentosSorteio();
				$returnCode = $sorteio->insereCompeticaoActual($proxFase, $compPrefix.$compAno, $organizador);				
				if (!$returnCode){
					$returnCode = $this->funGetPlfEquipas($compAno);
					if ($returnCode){
						shuffle($this->intPlf);
						for ($a = 0; $a < sizeof($this->intPlf); $a++)
							echo "Equipa: ".$this->intPlf[$a]."\n";
						$this->intPlf = array_chunk($this->intPlf, $divide);										
						for($i = 0; $i < sizeof($this->intPlf[0]); $i++){
							$varGrupo = new compGrupos('GRUPO_'.$faseGrupos[$i], $proxFase, $compPrefix, $compAno);
							for ($j = 0; $j < sizeof($this->intPlf); $j++){							
								$varGrupo->funSetEquipaGrupo($this->intPlf[$j][$i]);
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