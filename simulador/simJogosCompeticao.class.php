<?php
    
require_once ('classes/executeDBOperations.class.php');
require_once ('simuladorGrupo.class.php'); 
    
Class simJogosCompeticao{
		
	private $gruposSimulados = array();
	public $returnCode = 0;
	
	public function __construct($tipoID, $ano){
		If (substr($tipoID, 0, 3) == 'CMF') //Não pode simular jogos de fases finais de mundiais
			$this->returnCode = -903;
		Else{
			$this->funInicSimulacao($tipoID, $ano); 		
		}
	}
	
	
	public function funGetReturnCode(){
		return $returnCode;
	}
	
	
	private function funInicSimulacao($tipoID, $ano){
		$returnCode = 0;	
		$compID = substr($tipoID, 0, 3).$ano;		
		
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		//Vai verificar se a competição se encontra aberta, ou seja, a decorrer.
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			$this->returnCode = -904; //Erro de ligação a BD
		}		
		
		try{
			$varBD->queryString = 'Begin :retVal := package_competicoes.funGetFaseActiva(:FASE, :COMP); End;';
			 
			$varBD->parseDBQuery($varBD->queryString);			
			oci_bind_by_name($varBD->queryParse, ':retVal', $returnCode, 50);
			oci_bind_by_name($varBD->queryParse, ':COMP', $compID);
			oci_bind_by_name($varBD->queryParse, ':FASE', $tipoID);
			oci_execute($varBD->queryParse);
			$varBD->fechaLigacao();
			unset($varBD);
						
			If ($returnCode < 0)
				$this->returnCode = -902;
			Else{//A fase encontra-se activa portanto vamos determinar o que se vai simular.
				If (strlen($tipoID) == 6){
					$this->funSimulaJogosFase($tipoID, $ano); //Vai simular os jogos da fase em questão
				}Elseif (strlen($tipoID) == 22){
					$this->funSimulaJogosGrupo($tipoID, $ano); //Vai simular os jogos do grupo em questão
				}Else
					$this->returnCode = -900;									
			}			
						
		}catch(Exception $e){
			$varBD->fechaLigacao();
			unset($varBD);
			$this->returnCode = -905;
		}
	}
	
	private function funSimulaJogosFase($tipoID, $ano){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		
		$grupo = $tipoID.'_'.substr($tipoID, 0, 3).$ano;		
		//echo "ENTREI\n";
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			$this->returnCode = -904; //Erro de ligação a BD
		}		
		
		try{
			$varBD->queryString = 'select distinct tab_grupos_id from tab_grupos ';
			$varBD->queryString .= 'where substr(tab_grupos_id, 1, 14) = :GRP Order By 1';			
			 
			$varBD->parseDBQuery($varBD->queryString);			
			oci_bind_by_name($varBD->queryParse, ':GRP', $grupo);
			oci_execute($varBD->queryParse);
			//echo $grupo."\n";			
			While ($row = oci_fetch_array($varBD->queryParse)){
				$this->returnCode = $this->funGetJogosNFeitos($row[0]);
				//echo "Grupo: ".$row[0]."\n";
				If ($this->returnCode > 0){
					$novoGrupo = new simuladorGrupo($row[0]);
					$this->returnCode = $novoGrupo->funGetReturnCode();
					If ($this->returnCode == 0)
						array_push($this->gruposSimulados, $novoGrupo);						
					else{
						unset($this->gruposSimulados);
						break;
					}							
				} 
			}
			
			$varBD->fechaLigacao();
			unset($varBD);
			
		}catch(Exception $e){
			$varBD->fechaLigacao();
			unset($varBD);
			$this->returnCode = -911;
		}
	}
		
		
	private function funGetJogosNFeitos($grupoID){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
			
		$returnCode = 0;	
		
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			$this->returnCode = -904; //Erro de ligação a BD
		}
				
		try{
			$varBD->queryString = 'Begin :retVal := package_grupos.funGetJogosNFeitos(:GRP); End;';
			 
			$varBD->parseDBQuery($varBD->queryString);			
			oci_bind_by_name($varBD->queryParse, ':retVal', $returnCode, 50);
			oci_bind_by_name($varBD->queryParse, ':GRP', $grupoID);
			oci_execute($varBD->queryParse);
						
			$varBD->fechaLigacao();
			unset($varBD);
			
			return $returnCode;
						
		}catch(Exception $e){
			$varBD->fechaLigacao();
			unset($varBD);
			return -912;
		}
	}
	
	public function funGetGruposSimulados($grupo){
		return $this->gruposSimulados[$grupo];
	}	
		 	
}	

?>