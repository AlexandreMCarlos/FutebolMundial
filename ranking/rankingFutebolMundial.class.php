<?php
require_once ('classes/executeDBOperations.class.php');
require_once ('calculoRankingMundial.class.php');

Class rankingFutebolMundial{
	protected $equipas = array();	
	
	
	public function __construct(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			exit;
		}
		
		try{
			$varBD->queryString = 'Select tab_equipas_trig From tab_equipas';			
			$varBD->parseDBQuery($varBD->queryString);								
			oci_execute($varBD->queryParse);
			
			while ($row = oci_fetch_array($varBD->queryParse)){
				$equipa = new calculoRankingMundial($row[0]);
				array_push($this->equipas, $equipa);
			}
								
			$varBD->fechaLigacao();
			unset($varBD);	
		}catch (Exception $e){
			$varBD->fechaLigacao();
			unset($varBD);	

			return false;
		}		
	}
	
	public function funPrintRankingResults(){
		for ($i = 0; $i < sizeof($this->equipas); $i++){
			$equipa = $this->equipas[$i]->funGetEquipaNome();
			$pontos = $this->equipas[$i]->funGetEquipaPontos();
		}
	}
	
	public function funSetPontosRanking(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			exit;
		}			
			
		for ($i = 0; $i < sizeof($this->equipas); $i++){
			$equipa = $this->equipas[$i]->funGetEquipaNome();
			$pontos = $this->equipas[$i]->funGetEquipaPontos();
			try{
				$varBD->queryString = 'Update tab_equipas Set tab_equipas_pts_rank = :PTS Where tab_equipas_trig = :PAIS';			
				$varBD->parseDBQuery($varBD->queryString);
				oci_bind_by_name($varBD->queryParse, ':PTS', $pontos);
				oci_bind_by_name($varBD->queryParse, ':PAIS', $equipa);								
				oci_execute($varBD->queryParse);
				
				
				}catch(Exception $e){
					$varBD->fechaLigacao();
					unset($varBD);
			}						
		}
		
		$varBD->fechaLigacao();
		unset($varBD);
		
		$this->funOrdenaEquipas();				
		return true;					
	}
	
	
	public function funOrdenaEquipas(){
		$equipasOrdenadas = array();
		$returnCode = 0;
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			exit;
		}
		
		try{
			$varBD->queryString = 'Select tab_equipas_trig From tab_equipas Order By tab_equipas_pts_rank Desc';			
			$varBD->parseDBQuery($varBD->queryString);
			oci_execute($varBD->queryParse);			

			while ($row = oci_fetch_array($varBD->queryParse)){			
				array_push($equipasOrdenadas, $row[0]);
			}
			
			for ($index = 0; $index < sizeOf($equipasOrdenadas); $index++){
				$rankingPos = $index + 1;
				$varBD->queryString = 'Update tab_equipas Set tab_equipas_global_rank = :RANK Where tab_equipas_trig = :EQUIPA';			
				$varBD->parseDBQuery($varBD->queryString);
				oci_bind_by_name($varBD->queryParse, ':RANK', $rankingPos);
				oci_bind_by_name($varBD->queryParse, ':EQUIPA', $equipasOrdenadas[$index]);					
				oci_execute($varBD->queryParse);				
			}
			
				
			}catch(Exception $e){
					$varBD->fechaLigacao();
					unset($varBD);
		}
			
		try{
			$varBD->queryString = 'Begin Package_equipas.proOrdenaRankConf; End;';
			$varBD->parseDBQuery($varBD->queryString);			
			
			oci_execute($varBD->queryParse);
		
		}catch(Exception $e){
			$varBD->fechaLigacao();
			unset($varBD);
		}		
		
		
		$varBD->fechaLigacao();
		unset($varBD);
	}						
}


?> 