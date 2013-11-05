<?php

require_once ('classes/executeDBOperations.class.php');
require_once('processaFMPlayOffAux.class.php');

Class processaFMEliminatorias{
	protected $eliminatoria;
	protected $gruposElim = array();
	protected $apuradosElim = array();
	
	public function __construct($faseActual, $compPrefix, $compAno){
		$this->eliminatoria = $faseActual.'_'.$compPrefix.$compAno;
	}
	
	
	/***************************************************************/
	/* Vai buscar todos os grupos de uma Eliminatória			   */
	/* 															   */
	/***************************************************************/
	protected function funSetGruposElim(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
		
		try{
			$varBD->queryString = 'Select distinct(tab_grupos_id) From tab_grupos';
			$varBD->queryString .= ' Where substr(tab_grupos_id, 1, 14) = :COMP';		
			$varBD->parseDBQuery($varBD->queryString);
					
			oci_bind_by_name($varBD->queryParse, ':COMP', $this->eliminatoria);
			
			oci_execute($varBD->queryParse);
			
			While ($row = oci_fetch_array($varBD->queryParse)){
				array_push($this->gruposElim, $row[0]);
			}
								
			$varBD->fechaLigacao(); 		
			unset($varBD);
			
		}catch(Exception $e){
			unset($varBD);
			return false;
		}		
	}
	
	
	/***************************************************************/
	/* Determina os vencedores de um PlayOff   					   */
	/* Preenche o array $apuradosElim							   */
	/***************************************************************/
	protected function funGetPlayOffVencedores(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
				
		try{
			For($i=0; $i<sizeof($this->gruposElim); $i++){
				$varBD->queryString = 'Select a.tab_grupos_equipa From tab_grupos a, tab_grupos b';
				$varBD->queryString .= ' Where a.tab_grupos_class = 1';
				$varBD->queryString .= ' And a.tab_grupos_id = :GRP';
				$varBD->queryString .= ' And a.tab_grupos_id = b.tab_grupos_id';
				$varBD->queryString .= ' And (a.tab_grupos_pts >= 4 and b.tab_grupos_pts <= 1)';		
				$varBD->parseDBQuery($varBD->queryString);
						
				oci_bind_by_name($varBD->queryParse, ':GRP', $this->gruposElim[$i]);
				
				oci_execute($varBD->queryParse);
				
				$row = oci_fetch_array($varBD->queryParse);
				If (sizeof($row) > 1){
					array_push($this->apuradosElim, $row[0]);
				}
				else{
					$playOff = new processaFMPlayOffAux($this->gruposElim[$i]);
					if ($playOff->erroPlf){
						$vencedor = $playOff->funTrataPlayOff();
						array_push($this->apuradosElim, $vencedor);
						unset ($playOff);											
					}else{														
						array_push($this->apuradosElim, 0);
						unset($playOff);			
					}
				}					
			}
			$varBD->fechaLigacao(); 		
			unset($varBD);				
			
		}catch (Exception $e){
			array_push($this->apuradosElim, 0);			
			$varBD->fechaLigacao(); 		
			unset($varBD);	
		}
	}
	
	
	/***************************************************************/
	/* Faz a análise de um PlayOff								   */
	/* Devolve o trigrama da selecção apurada via PlayOff		   */
	/***************************************************************/
	public function tratamentoPlayOff(){
		//Vou ver quantos grupos de PlayOff existem	
		try{	
			$this->funSetGruposElim();
			$this->funGetPlayOffVencedores();
			//Retorna o Apurado
			return $this->apuradosElim;
		}catch (Exception $e){
			array_push($this->apuradosElim, 0);
			return $this->apuradosElim;
		}
		
	}
	
	/***************************************************************/
	/* Determina os vencedores de uma Eliminatória				   */
	/* Preenche o array $apuradosElim							   */
	/***************************************************************/
	protected function funGetElimVencedores(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
				
		try{
			For($i=0; $i<sizeof($this->gruposElim); $i++){
				$varBD->queryString = 'Select tab_grupos_equipa From tab_grupos';
				$varBD->queryString .= ' Where tab_grupos_class = 1';
				$varBD->queryString .= ' And tab_grupos_id = :GRP';		
				$varBD->parseDBQuery($varBD->queryString);
						
				oci_bind_by_name($varBD->queryParse, ':GRP', $this->gruposElim[$i]);
				
				oci_execute($varBD->queryParse);
				
				$row = oci_fetch_array($varBD->queryParse);
				array_push($this->apuradosElim, $row[0]);			
			}
			$varBD->fechaLigacao(); 		
			unset($varBD);				
			
		}catch (Exception $e){
			unset($varBD);			
			unset($this->apuradosElim);
		}
	}


	/***************************************************************/
	/* Faz a análise de uma Eliminatória						   */
	/* Devolve o trigrama da selecção apurada via Eliminatória	   */
	/* Devolve false caso exista um erro						   */
	/***************************************************************/
	public function tratamentoEliminatoria(){
		try{	
			$this->funSetGruposElim();
			$this->funGetElimVencedores();
			If (!isset($this->apuradosElim)){
				return false;
			}else{
				return $this->apuradosElim;
			}
		}catch (Exception $e){
			return false;
		}
		
	}	
}

?>