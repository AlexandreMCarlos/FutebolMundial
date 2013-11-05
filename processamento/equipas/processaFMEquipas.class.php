<?php

require_once ('classes/executeDBOperations.class.php');

Class processaFMEquipas{

	protected $equipas = array();	
	
	/*******************************************************************************************************************/
	/*
	* Nome: __construct
	* Data: 28/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Constructor da classe. Instancia as equipas
	* 		 No final da execução deste método, o array $equipas irá conter 2 arrays associativos com
	*		 os dados de ambas as equipas. 
	* Entrada: ambas as equipas relativas a um determinado jogo
	* Saida: Não tem.
	*/
	
	public function __construct($equipaA, $equipaB){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		$equipa = array();
				
		If ($varBD->funGetConnection() == null){
			echo "Não é possivel abrir a ligação com a BD.";
			exit();
		}
		
		
		$varBD->queryString = 'Select tab_equipas_trig, tab_equipas_jogos, tab_equipas_vit, tab_equipas_emp, tab_equipas_drr';
		$varBD->queryString .= ', tab_equipas_gm, tab_equipas_gs From tab_equipas Where tab_equipas_trig In (:EQUA, :EQUB)';
		
		$varBD->parseDBQuery($varBD->queryString);
		
		oci_bind_by_name($varBD->queryParse, ':EQUA', $equipaA);			
		oci_bind_by_name($varBD->queryParse, ':EQUB', $equipaB);
		
		oci_execute($varBD->queryParse);
		
		While ($row = oci_fetch_assoc($varBD->queryParse)){
			array_push($equipa, $row['TAB_EQUIPAS_TRIG']);
			array_push($equipa, $row['TAB_EQUIPAS_JOGOS']);
			array_push($equipa, $row['TAB_EQUIPAS_VIT']);
			array_push($equipa, $row['TAB_EQUIPAS_EMP']);
			array_push($equipa, $row['TAB_EQUIPAS_DRR']);
			array_push($equipa, $row['TAB_EQUIPAS_GM']);
			array_push($equipa, $row['TAB_EQUIPAS_GS']);
			
			array_push($this->equipas, $row);
			$equipas = array(); 
		}		
		$varBD->fechaLigacao();
	}


	/*******************************************************************************************************************/
	/*
	* Nome: funSetEquipaJogos
	* Data: 28/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Adiciona ao número de jogos da equipa
	* Entrada: $indice  - Indice do array de equipas 
	* Saida: Não tem.
	*/
	
	private function funSetEquipaJogos($indice){
		$this->equipas[$indice]['TAB_EQUIPAS_JOGOS'] += 1;
	}
	
	
	/*******************************************************************************************************************/
	/*
	* Nome: funSetEquipaDerrotas
	* Data: 28/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Adiciona ao número de derrotas da equipa
	* Entrada: $indice  - Indice do array de equipas 
	* Saida: Não tem.
	*/
	
	private function funSetEquipaDerrotas($indice){
		$this->equipas[$indice]['TAB_EQUIPAS_DRR'] += 1;
	}
	
	/*******************************************************************************************************************/
	/*
	* Nome: funSetEquipaEmpate
	* Data: 28/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Adiciona ao número de empates da equipa
	* Entrada: $indice  - Indice do array de equipas 
	* Saida: Não tem.
	*/
	
	private function funSetEquipaEmpate($indice){
		$this->equipas[$indice]['TAB_EQUIPAS_EMP'] += 1;
	}



	/*******************************************************************************************************************/
	/*
	* Nome: funSetEquipaVitorias
	* Data: 28/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Adiciona ao número de vitórias da equipa
	* Entrada: $indice  - Indice do array de equipas 
	* Saida: Não tem.
	*/
	
	private function funSetEquipaVitorias($indice){
		$this->equipas[$indice]['TAB_EQUIPAS_VIT'] += 1;
	}
	

	/*******************************************************************************************************************/
	/*
	* Nome: funSetEquipaGolos
	* Data: 28/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Adiciona ao número de golos marcados e sofridos da equipa
	* Entrada: $indice  - Indice do array de equipas
	*		   $gm - Golos marcados
	*          $gs - Golos sofridos   
	* Saida: Não tem.
	*/
	
	private function funSetEquipaGolos($indice, $gm, $gs){
		$this->equipas[$indice]['TAB_EQUIPAS_GM'] += $gm;
		$this->equipas[$indice]['TAB_EQUIPAS_GS'] += $gs;
	}	



	/*******************************************************************************************************************/
	/*
	* Nome: funSetEquipaValores
	* Data: 28/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Actualizar os dados relativos a uma equipa
	* Entrada: $equipa - Qual a equipa em questão
	* 		   $pontos - Pontos ganhos pela equipa em questão
	*		   $gm - Golos Marcados
	*		   $gs - Golos sofridos  
	*		   $penalties - Flag que indica se o jogo foi a penalties (1) ou não (0) 
	* Saida: Não tem.
	*/
	
	public function funSetEquipaValores($equipa, $pontos, $gm, $gs, $penalties){		
				
		If ($this->equipas[0]['TAB_EQUIPAS_TRIG'] == $equipa)
			$indice = 0;
		else	$indice = 1;
		
		/* Aqui começa o processamento dos dados do jogo */
		$this->funSetEquipaJogos($indice); //Adiciona ao número de jogos já existentes
		
		Switch($pontos){
			Case 0:	
				$this->funSetEquipaDerrotas($indice);
				Break;
			Case 1:
				If ($penalties == 0)
					$this->funSetEquipaEmpate($indice);
				Else 
					$this->funSetEquipaDerrotas($indice);
				Break;
			Case 2:
				$this->funSetEquipaEmpate($indice);
				break;
			Case 3:
				$this->funSetEquipaVitorias($indice);
				break;
		}		
		$this->funSetEquipaGolos($indice, $gm, $gs); 		
	}	
	

	/*******************************************************************************************************************/
	/*
	* Nome: funInsereEquipasValores
	* Data: 28/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai inserir na BD as actualizações feitas aos dados de ambas as euipas participantes num jogo
	* Entrada: N/A 
	* Saida: Código de erro: 0 OK, -1 NOK
	*/
	
	public function funInsereEquipasValores(){
		$returnCode = 0;	
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
						
		If ($varBD->funGetConnection() == null){
			echo "Não é possivel abrir a ligação com a BD.";
			$returnCode = -1;
			exit();
		}
		
		If ($returnCode == 0){
			$varBD->queryString = 'Update tab_equipas Set tab_equipas_jogos = :JG, ';
			$varBD->queryString .= 'tab_equipas_vit = :VIT, tab_equipas_emp = :EMP, ';
			$varBD->queryString .= 'tab_equipas_drr = :DRR, tab_equipas_gm = :GM, ';
			$varBD->queryString .= 'tab_equipas_gs = :GS ';
			$varBD->queryString .= 'Where tab_equipas_trig = :EQUIPA';
			
			$varBD->parseDBQuery($varBD->queryString);
			
			for ($indice = 0; $indice < sizeof($this->equipas); $indice++){
				oci_bind_by_name($varBD->queryParse, ':JG', $this->equipas[$indice]['TAB_EQUIPAS_JOGOS']);
				oci_bind_by_name($varBD->queryParse, ':VIT', $this->equipas[$indice]['TAB_EQUIPAS_VIT']);
				oci_bind_by_name($varBD->queryParse, ':EMP', $this->equipas[$indice]['TAB_EQUIPAS_EMP']);
				oci_bind_by_name($varBD->queryParse, ':DRR', $this->equipas[$indice]['TAB_EQUIPAS_DRR']);
				oci_bind_by_name($varBD->queryParse, ':GM', $this->equipas[$indice]['TAB_EQUIPAS_GM']);
				oci_bind_by_name($varBD->queryParse, ':GS', $this->equipas[$indice]['TAB_EQUIPAS_GS']);
				oci_bind_by_name($varBD->queryParse, ':EQUIPA', $this->equipas[$indice]['TAB_EQUIPAS_TRIG']);
				
				oci_execute($varBD->queryParse);
			}
			$varBD->fechaLigacao(); 
		}
		return $returnCode;					
	}	
}

?>