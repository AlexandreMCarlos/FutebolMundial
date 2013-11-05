<?php
    
require_once('classes/executeDBOperations.class.php');
        
Class sorteiaOrganizadorMundial{
	
	protected $sorteioPrimeiraFase = array();
	protected $sorteioSegundaFase = array();
	protected $sorteioTerceiraFase = array();
	protected $mundialOrganizador;
	protected $mundialAno;    		
    	

	public function __construct($anoMundial){
		$this->mundialAno = $anoMundial;
	}

	
	/*******************************************************************************************************************/
	/*
	* Nome: funSetOrganizadorMundial
	* Data: 06/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai colocar o país organizador na propriedade correspondente 
	* Entrada: O país organizador	
	* Saida: N/A
	* Observações: N/A
	*/
	public function funSetOrganizadorMundial($paisOrganizador){
		$this->mundialOrganizador = $paisOrganizador;
	} 	
	

	/*******************************************************************************************************************/
	/*
	* Nome: funGetOrganizadorMundial
	* Data: 06/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai retornar o país organizador do Mundial de Futebol 
	* Entrada: N/A
	* Saida: O país organizador
	* Observações: N/A
	*/
	public function funGetOrganizadorMundial(){
		return $this->mundialOrganizador;
	}	
	
	
	public function funGetExistsOrganizador(){		
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		
		If ($varBD->funGetConnection() == null){
			echo "Não é possivel abrir a ligação com a BD.";
			unset($this);
			exit();
		}
		
		$varBD->queryString = 'select count(tab_wc_equipa) from tab_fifa_wc where tab_wc_rank = 0 and tab_wc_ano = :ANO';
		$varBD->parseDBQuery($varBD->queryString);		
		oci_bind_by_name($varBD->queryParse, ':ANO', $this->mundialAno);
		oci_execute($varBD->queryParse);
		
		While ($row = oci_fetch_array($varBD->queryParse)){
			$organizador = $row[0];							
		}		
				
		if ($organizador == 0)
			return false;
		else {			
			$varBD->queryString = 'select tab_wc_equipa from tab_fifa_wc where tab_wc_rank = 0 and tab_wc_ano = :ANO';
			$varBD->parseDBQuery($varBD->queryString);		
			oci_bind_by_name($varBD->queryParse, ':ANO', $this->mundialAno);
			oci_execute($varBD->queryParse);
			
			While ($row = oci_fetch_array($varBD->queryParse)){
				$organizador = $row[0];							
			}			
			$this->funSetOrganizadorMundial($organizador);
			return true;
		}
		$varBD->fechaLigacao();
		unset($varBD);		
	}
	
	/*******************************************************************************************************************/
	/*
	* Nome: sorteiaPrimeiraFase
	* Data: 03/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai fazer a primeira fase do sorteio do organizador do Mundial de Futebol 
	* Entrada: N/A	
	* Saida: N/A
	* Observações: N/A
	*/
	public function sorteiaPrimeiraFase(){		
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		
		if (!$this->funGetExistsOrganizador()){
		
			If ($varBD->funGetConnection() == null){
				echo "Não é possivel abrir a ligação com a BD.";
				unset($this);
				exit();
			}
			
			$varBD->queryString = 'select te.tab_equipas_trig, (((199 - te.tab_equipas_global_rank)/100)*tc.tab_conf_peso)';
			$varBD->queryString .= ' from tab_equipas te, tab_confederacoes tc';
			$varBD->queryString .= ' where te.tab_equipas_org_mundial is not null'; 
			$varBD->queryString .= ' and te.tab_equipas_conf = tc.tab_conf_id';
			$varBD->queryString .= ' and te.tab_equipas_trig not in (select tab_wc_equipa from tab_fifa_wc where tab_wc_ano > = :ANO and tab_wc_rank = 0 )';
			$varBD->queryString .= ' order by te.tab_equipas_conf_rank';
			
			$varBD->parseDBQuery($varBD->queryString);
			$ano = $this->mundialAno - 6;
			oci_bind_by_name($varBD->queryParse, ':ANO', $ano);
			oci_execute($varBD->queryParse);
			
			While ($row = oci_fetch_array($varBD->queryParse)){
				$organizador = array();
				$organizador['pais'] = $row[0];
				$organizador['factor'] = $row[1];
				$organizador['votos'] = 0;
				
				array_push($this->sorteioPrimeiraFase, $organizador);							
			}
			unset($organizador);
			
			for($i = 0; $i < 200; $i++){				
				$indice = rand(0, sizeof($this->sorteioPrimeiraFase)-1);
				$this->sorteioPrimeiraFase[$indice]['votos'] += 1; 		
			}
			
			for ($i = 0; $i < sizeof($this->sorteioPrimeiraFase); $i++)
				$this->sorteioPrimeiraFase[$indice]['votos'] = round($this->sorteioPrimeiraFase[$i]['factor'] * $this->sorteioPrimeiraFase[$i]['votos']);
	
	
			$this->ordenaPrimeiraFase();
	
			/*for ($i = 0; $i < sizeof($this->sorteioPrimeiraFase); $i++)
				echo $this->sorteioPrimeiraFase[$i]['pais']." - ".$this->sorteioPrimeiraFase[$i]['votos']."\n";
				*/	
			
			$this->sorteiaSegundaFase();		
			
			/*If (isset($this->sorteioTerceiraFase)){
				echo "\n";
				for ($i = 0; $i < sizeof($this->sorteioTerceiraFase); $i++)
					echo $this->sorteioTerceiraFase[$i]['pais']." - ".$this->sorteioTerceiraFase[$i]['votos']."\n";
			}*/
		}
	}

	/*******************************************************************************************************************/
	/*
	* Nome: sorteiaSegundaFase
	* Data: 04/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai fazer o sorteio final para determinar o organizador do Mundial de Futebol 
	* Entrada: N/A	
	* Saida: N/A
	* Observações: N/A
	*/
	public function sorteiaSegundaFase(){	
		for($i = 0; $this->sorteioPrimeiraFase[$i]['votos'] >= 9 && isset($this->sorteioPrimeiraFase[$i]); $i++){
			array_push($this->sorteioSegundaFase, $this->sorteioPrimeiraFase[$i]);
			$this->sorteioSegundaFase[$i]['votos'] = 0;
		}
				
		for($i = 0; $i < 200; $i++){				
			$indice = rand(0, sizeof($this->sorteioSegundaFase)-1);
			$this->sorteioSegundaFase[$indice]['votos'] += 1; 		
		}		
		
		$this->ordenaSegundaFase();
		
		/*echo "\n";
		for ($i = 0; $i < sizeof($this->sorteioSegundaFase); $i++)
			echo $this->sorteioSegundaFase[$i]['pais']." - ".$this->sorteioSegundaFase[$i]['votos']."\n";		
		
		echo sizeof($this->sorteioSegundaFase)."\n";*/
		
		If (sizeof($this->sorteioSegundaFase) <= 5 && sizeof($this->sorteioSegundaFase) >= 0){
			$this->funSetOrganizadorMundial($this->sorteioSegundaFase[0]['pais']);
		}Else{
			$this->sorteiaTerceiraFase();
		}	
	}


	/*******************************************************************************************************************/
	/*
	* Nome: sorteiaTerceiraFase
	* Data: 11/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai fazer o sorteio final para determinar o organizador do Mundial de Futebol 
	* Entrada: N/A	
	* Saida: N/A
	* Observações: N/A
	*/
	public function sorteiaTerceiraFase(){
		for($i = 0; isset($this->sorteioSegundaFase[$i])&& $this->sorteioSegundaFase[$i]['votos'] >= 15; $i++){			
			array_push($this->sorteioTerceiraFase, $this->sorteioSegundaFase[$i]);			
			$this->sorteioTerceiraFase[$i]['votos'] = 0;
		}
		
		for($i = 0; $i < 200; $i++){				
			$indice = rand(0, sizeof($this->sorteioTerceiraFase)-1);
			$this->sorteioTerceiraFase[$indice]['votos'] += 1; 		
		}		
		
		$this->ordenaTerceiraFase();
						
		$this->funSetOrganizadorMundial($this->sorteioTerceiraFase[0]['pais']);
	}


	/*******************************************************************************************************************/
	/*
	* Nome: ordenaPrimeiraFase
	* Data: 03/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai fazer a ordenação do array que contém todos os votos da primeira fase do sorteio do 
	* 		 organizador do Mundial de Futebol 
	* Entrada: N/A	
	* Saida: N/A
	* Observações: N/A
	*/
	public function ordenaPrimeiraFase(){
		$valores = array();
		foreach ($this->sorteioPrimeiraFase as $key => $value) {
			$valores[$key] = $value['votos'];							
		}
		
		array_multisort($valores, SORT_DESC, $this->sorteioPrimeiraFase);				
	}
	
	/*******************************************************************************************************************/
	/*
	* Nome: ordenaSegundaFase
	* Data: 04/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai fazer a ordenação do array que contém todos os votos da segunda fase do sorteio do 
	* 		 organizador do Mundial de Futebol 
	* Entrada: N/A	
	* Saida: N/A
	* Observações: N/A
	*/
	public function ordenaSegundaFase(){
		$valores = array();
		foreach ($this->sorteioSegundaFase as $key => $value) {
			$valores[$key] = $value['votos'];							
		}
		
		array_multisort($valores, SORT_DESC, $this->sorteioSegundaFase);				
	}	
	
	/*******************************************************************************************************************/
	/*
	* Nome: ordenaTerceiraFase
	* Data: 11/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai fazer a ordenação do array que contém todos os votos da terceira fase do sorteio do 
	* 		 organizador do Mundial de Futebol 
	* Entrada: N/A	
	* Saida: N/A
	* Observações: N/A
	*/
	public function ordenaTerceiraFase(){
		$valores = array();
		foreach ($this->sorteioTerceiraFase as $key => $value) {
			$valores[$key] = $value['votos'];							
		}
		
		array_multisort($valores, SORT_DESC, $this->sorteioTerceiraFase);				
	}	
	
}


?>