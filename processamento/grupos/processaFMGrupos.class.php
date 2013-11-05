<?php

require_once ('classes/executeDBOperations.class.php');

Class processaFMGrupos{
	
	protected $grupoID;
	protected $equipasGrupo = array();
	protected $faseID;
	protected $compID;
	
		
	/*******************************************************************************************************************/
	/*
	* Nome: __construct
	* Data: 28/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Constructor da classe. Instancia o grupo com as equipas em questão
	* 		 No final da execução deste método, o array $equipasGrupo irá conter 2 arrays associativos com
	*		 os dados de ambas as equipas. 
	* Entrada: $grupoID - ID do grupo em questão
	* 		   $equipaA e $equipaB - ambas as equipas relativas a um determinado jogo, que deve ser registado num grupo
	* Saida: Não tem.
	*/	
	public function __construct($grupoID, $equipaA, $equipaB){
		$equipa = array();	
		$this->grupoID = $grupoID;
		
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		
		If ($varBD->funGetConnection() == null){
			exit();
		}
		
		$varBD->queryString = 'select tab_grupos_equipa, tab_grupos_jogos, ';
		$varBD->queryString .= 'tab_grupos_vit, tab_grupos_emp, tab_grupos_drr, ';
		$varBD->queryString .= 'tab_grupos_gm, tab_grupos_gs, tab_grupos_pts ';
		$varBD->queryString .= 'from tab_grupos where tab_grupos_id = :GRP ';
		$varBD->queryString .= 'and tab_grupos_equipa in (:EQUA,:EQUB)';
		
		$varBD->parseDBQuery($varBD->queryString);
		
		oci_bind_by_name($varBD->queryParse, ':GRP', $grupoID);
		oci_bind_by_name($varBD->queryParse, ':EQUA', $equipaA);			
		oci_bind_by_name($varBD->queryParse, ':EQUB', $equipaB);
		
		oci_execute($varBD->queryParse);
		
		While ($row = oci_fetch_assoc($varBD->queryParse)){
			array_push($equipa, $row['TAB_GRUPOS_EQUIPA']);
			array_push($equipa, $row['TAB_GRUPOS_JOGOS']);
			array_push($equipa, $row['TAB_GRUPOS_VIT']);
			array_push($equipa, $row['TAB_GRUPOS_EMP']);
			array_push($equipa, $row['TAB_GRUPOS_DRR']);
			array_push($equipa, $row['TAB_GRUPOS_GM']);
			array_push($equipa, $row['TAB_GRUPOS_GS']);
			array_push($equipa, $row['TAB_GRUPOS_PTS']);
			
			array_push($this->equipasGrupo, $row);
			$equipas = array(); 
		}
		
		$this->faseID = substr($this->grupoID, 0, 6);
		$this->compID = substr($this->grupoID, 7, 7);
		$varBD->fechaLigacao();
		unset($varBD);				
	}


	/*******************************************************************************************************************/
	/*
	* Nome: funSetEquipasJogos
	* Data: 29/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: incrementa o número de jogos da equipa em 1
	* Entrada: $indice - Indice, no array de equipas, da equipa à qual deve ser adicionado 1 jogo
	* Saida: Não tem.
	*/
	private function funSetEquipasJogos($indice){
		$this->equipasGrupo[$indice]['TAB_GRUPOS_JOGOS'] += 1;
	}
	

	/*******************************************************************************************************************/
	/*
	* Nome: funSetEquipasDerrota
	* Data: 29/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Incrementa o número de derrotas da equipa em 1.
	* Entrada: $indice - Indice, no array de equipas, da equipa à qual deve ser adicionado 1 derrota
	* Saida: Não tem.
	*/
	private function funSetEquipasDerrota($indice){
		$this->equipasGrupo[$indice]['TAB_GRUPOS_DRR'] += 1;
	}
	

	/*******************************************************************************************************************/
	/*
	* Nome: funSetEquipasDerrota
	* Data: 29/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Incrementa o número de derrotas da equipa em 1.
	* Entrada: $indice - Indice, no array de equipas, da equipa à qual deve ser adicionado 1 derrota
	* Saida: Não tem.
	*/
	private function funSetEquipasDerrotaPen($indice){
		$this->equipasGrupo[$indice]['TAB_GRUPOS_EMP'] += 1;
		$this->equipasGrupo[$indice]['TAB_GRUPOS_PTS'] += 1;
	}	
	

	/*******************************************************************************************************************/
	/*
	* Nome: funSetEquipasEmpate
	* Data: 29/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Incrementa o número de empates da equipa em 1 e adiciona o número de pontos aos já conquistados pela equipa
	* Entrada: $indice - Indice, no array de equipas, da equipa à qual deve ser adicionado 1 empate
	* Saida: Não tem.
	*/
	private function funSetEquipasEmpate($indice){
		$this->equipasGrupo[$indice]['TAB_GRUPOS_EMP'] += 1;
		$this->equipasGrupo[$indice]['TAB_GRUPOS_PTS'] += 1;
	}
	
			
	/*******************************************************************************************************************/
	/*
	* Nome: funSetEquipasVitoria
	* Data: 29/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Incrementa o número de vitorias da equipa em 1 e adiciona o número de pontos aos já conquistados pela equipa
	* Entrada: $indice - Indice, no array de equipas, da equipa à qual deve ser adicionado 1 vitoria
	* Saida: Não tem.
	*/
	private function funSetEquipasVitoria($indice){
		$this->equipasGrupo[$indice]['TAB_GRUPOS_VIT'] += 1;
		$this->equipasGrupo[$indice]['TAB_GRUPOS_PTS'] += 3;
	}
	
	/*******************************************************************************************************************/
	/*
	* Nome: funSetEquipasVitoria
	* Data: 29/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Incrementa o número de vitorias da equipa em 1 e adiciona o número de pontos aos já conquistados pela equipa
	* Entrada: $indice - Indice, no array de equipas, da equipa à qual deve ser adicionado 1 vitoria
	* Saida: Não tem.
	*/
	private function funSetEquipasVitoriaPen($indice){
		$this->equipasGrupo[$indice]['TAB_GRUPOS_EMP'] += 1;
		$this->equipasGrupo[$indice]['TAB_GRUPOS_PTS'] += 2;
	}				


	/*******************************************************************************************************************/
	/*
	* Nome: funSetEquipasGolos
	* Data: 29/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Adiciona ao número de golos marcados e sofridos da equipa
	* Entrada: $indice  - Indice do array de equipas
	*		   $gm - Golos marcados
	*          $gs - Golos sofridos   
	* Saida: Não tem.
	*/
	
	private function funSetEquipasGolos($indice, $gm, $gs){
		$this->equipasGrupo[$indice]['TAB_GRUPOS_GM'] += $gm;
		$this->equipasGrupo[$indice]['TAB_GRUPOS_GS'] += $gs;
	}
	

	/*******************************************************************************************************************/
	/*
	* Nome: funSetGrupoValores
	* Data: 29/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Processamento dos dados das equipas dentro dos grupos
	* Entrada: $equipa - Qual a equipa em questão
	* 		   $pontos - Pontos ganhos pela equipa em questão
	*		   $gm - Golos Marcados
	*		   $gs - Golos sofridos  
	*		   $penalties - Flag que indica se o jogo foi a penalties (1) ou não (0)
	* Saida: Não tem.
	*/
	
	public function funSetGrupoValores($equipa, $pontos, $gm, $gs, $penalties){
		
		If ($this->equipasGrupo[0]['TAB_GRUPOS_EQUIPA'] == $equipa)
			$indice = 0;
		Else $indice = 1;
		
		//Aqui começa o processamento dos dados da equipa
		$this->funSetEquipasJogos($indice);
		
		Switch($pontos){
			Case 0:	
				$this->funSetEquipasDerrota($indice);
				Break;
			Case 1:
				If ($penalties == 0)
					$this->funSetEquipasEmpate($indice);
				Else 
					$this->funSetEquipasDerrotaPen($indice);
				Break;
			Case 2:
				$this->funSetEquipasVitoriaPen($indice);
				break;
			Case 3:
				$this->funSetEquipasVitoria($indice);
				break;	
		}		
		$this->funSetEquipasGolos($indice, $gm, $gs);
	}
	
	/*************************************************************************/
	/* funSetGrupoOrdenação													 */
	/* Vai ordenar o grupo do objecto $this. ($this->grupoID)				 */
	/*************************************************************************/
	protected function funSetGrupoOrdenação(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
						
		If ($varBD->funGetConnection() == null){			
			exit();
		}
		
		try{
			$varBD->queryString = 'Begin :retVal := package_grupos.funOrdenaGrupos(:GRP); End;';
			$varBD->parseDBQuery($varBD->queryString);			
			oci_bind_by_name($varBD->queryParse, ':retVal', $returnCode, 50);
			oci_bind_by_name($varBD->queryParse, ':GRP', $this->grupoID);
			oci_execute($varBD->queryParse);
			
			If ($returnCode)
				return true;
			else
				return false;
			$varBD->fechaLigacao(); 
		
			unset($varBD);			
				 
		}catch(Exception $e){
			unset($varBD);
			return false;
		}
		
	}
	

	/*******************************************************************************************************************/
	/*
	* Nome: funInsereGrupoValores
	* Data: 29/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai inserir na BD as actualizações feitas aos dados de ambas as euipas participantes num grupo
	* Entrada: N/A 
	* Saida: N/A
	*/
	
	public function funInsereGrupoValores(){	
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
						
		If ($varBD->funGetConnection() == null){			
			exit();
		}
		
		$varBD->queryString = 'Update tab_grupos Set tab_grupos_jogos = :JG, ';
		$varBD->queryString .= 'tab_grupos_vit = :VIT, tab_grupos_emp = :EMP, ';
		$varBD->queryString .= 'tab_grupos_drr = :DRR, tab_grupos_gm = :GM, ';
		$varBD->queryString .= 'tab_grupos_gs = :GS, tab_grupos_pts = :PTS ';
		$varBD->queryString .= 'Where tab_grupos_equipa = :EQUIPA And tab_grupos_id = :GRP';
		
		$varBD->parseDBQuery($varBD->queryString);
		
		for ($indice = 0; $indice < sizeof($this->equipasGrupo); $indice++){
			oci_bind_by_name($varBD->queryParse, ':JG', $this->equipasGrupo[$indice]['TAB_GRUPOS_JOGOS']);
			oci_bind_by_name($varBD->queryParse, ':VIT', $this->equipasGrupo[$indice]['TAB_GRUPOS_VIT']);
			oci_bind_by_name($varBD->queryParse, ':EMP', $this->equipasGrupo[$indice]['TAB_GRUPOS_EMP']);
			oci_bind_by_name($varBD->queryParse, ':DRR', $this->equipasGrupo[$indice]['TAB_GRUPOS_DRR']);
			oci_bind_by_name($varBD->queryParse, ':GM', $this->equipasGrupo[$indice]['TAB_GRUPOS_GM']);
			oci_bind_by_name($varBD->queryParse, ':GS', $this->equipasGrupo[$indice]['TAB_GRUPOS_GS']);
			oci_bind_by_name($varBD->queryParse, ':PTS', $this->equipasGrupo[$indice]['TAB_GRUPOS_PTS']);
			oci_bind_by_name($varBD->queryParse, ':EQUIPA', $this->equipasGrupo[$indice]['TAB_GRUPOS_EQUIPA']);
			oci_bind_by_name($varBD->queryParse, ':GRP', $this->grupoID);
			
			oci_execute($varBD->queryParse);
		}
		$varBD->fechaLigacao(); 
		
		unset($varBD);
		
		return $this->funSetGrupoOrdenação();
	}

	/*******************************************************************************************************************/
	/*
	* Nome: funGrupoTerminado
	* Data: 01/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai determinar se o grupo já terminou 
	* Entrada: N/A	
	* Saida: true - Grupo terminado
	*		 false - Grupo ainda não terminado 
	* Observações: N/A
	*/							
	public function funGrupoTerminado(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
						
		If ($varBD->funGetConnection() == null){
			unset($varBD);			
			exit();
		}
		
		//Vai determinar o total de jogos do grupo
		$varBD->queryString = 'select count(1) from tab_jogos where tab_jogos_grupo_id = :GRP';
		$varBD->parseDBQuery($varBD->queryString);
		oci_bind_by_name($varBD->queryParse, ':GRP', $this->grupoID);
		
		oci_execute($varBD->queryParse);
		
		$row = oci_fetch_array($varBD->queryParse);
		$jogosTotal = $row[0];		
		
		//Vai determinar o total de jogos do grupo já realizados		
		$varBD->queryString .= ' and tab_jogos_data is not null';
		$varBD->parseDBQuery($varBD->queryString);

		oci_bind_by_name($varBD->queryParse, ':GRP', $this->grupoID);
		
		oci_execute($varBD->queryParse);
		
		$row = oci_fetch_array($varBD->queryParse);
		$jogosFeitos = $row[0];
		
		$varBD->fechaLigacao();
		unset($varBD);
		
		If ($jogosFeitos == $jogosTotal)
			return true;
		Else
			return false;				
	}

	/*******************************************************************************************************************/
	/*
	* Nome: funSetFaseTerminada
	* Data: 22/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai determinar se a fase já terminou e receber o resultado da actualização de valores
	* Entrada: N/A	
	* Saida: 1 se a fase está completa e o registo foi actualizado
	*	     0 se apenas o registo foi actualizado
	*       -1 Caso Contrário
	* Observações: N/A
	*/
	public function funSetFaseTerminada(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
						
		If ($varBD->funGetConnection() == null){
			unset($varBD);			
			exit();
		}
		
		//Vai determinar o total de jogos do grupo
		$varBD->queryString = 'Begin :retVal := package_competicoes.funSetFaseCompleta(:FASE, :COMP, :GRP); End;';
		$varBD->parseDBQuery($varBD->queryString);
		oci_bind_by_name($varBD->queryParse, ':retVal', $returnCode, 50);
		oci_bind_by_name($varBD->queryParse, ':FASE', $this->faseID);		
		oci_bind_by_name($varBD->queryParse, ':COMP', $this->compID);
		oci_bind_by_name($varBD->queryParse, ':GRP', $this->grupoID);
		
		oci_execute($varBD->queryParse);

		$varBD->fechaLigacao();
		unset($varBD);

		Return $returnCode;
	}

	/*******************************************************************************************************************/
	/*
	* Nome: funGetFase
	* Data: 23/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai devolver a fase que está associada ao grupo
	* Entrada: N/A	
	* Saida: Fase que está associada ao grupo
	* Observações: N/A
	*/	
	public function funGetFase(){
		return $this->faseID;
	}
	
	/*******************************************************************************************************************/
	/*
	* Nome: funGetCompeticao
	* Data: 23/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai devolver a competição que está associada ao grupo
	* Entrada: N/A	
	* Saida: Competição que está associada ao grupo
	* Observações: N/A
	*/	
	public function funGetCompeticao(){
		return $this->compID;
	}
	
	/*******************************************************************************************************************/
	/*
	* Nome: funGetGrupoID
	* Data: 23/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai devolver o ID do grupo
	* Entrada: N/A	
	* Saida: ID do grupo
	* Observações: N/A
	*/	
	public function funGetGrupoID(){
		return $this->grupoID;
	}	
		
}

?>