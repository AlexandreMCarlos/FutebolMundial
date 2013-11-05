<?php

require_once ('classes/executeDBOperations.class.php');
require_once ('classes/compGrupos.class.php');
    
Class sorteioMundialEuropa{
		
	public $prefixoConf = 'MEU';
	protected $idConf = 6;	

	public $prefixoFase = array('MEU_01', 'MEU_02');
	
	public $gruposSorteio = array();
	private $equipasSorteio = array();
	
	public $sorteioErro;
	private $mundialOrganizador = array();
	private $mundialAno;
	

	/*******************************************************************************************************************/
	/*
	* Nome: __constructor
	* Data: 09/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai dar inicio ao sorteio da fase de qualificação para o Mundial de Futebol (Zona Europeia) 
	* Entrada: $confOrganizador - Confederação do organizador do Mundial
	*	       $anoMundial - Ano do Mundial 
	* Saida: Código de Erro: 0 se OK, -1 se NOTOK
	* Observações: N/A  
	*/
	
	public function __construct($confOrganizador, $mundialAno){
		$this->mundialOrganizador['pais'] = $confOrganizador['pais'];
		$this->mundialOrganizador['conf'] = $confOrganizador['conf'];
		$this->mundialAno = $mundialAno;
		
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		
		If ($varBD->connection == null){
			$this->sorteioErro = -1;				
			exit;
		} 
		
		$varBD->queryString = 'Select tab_equipas_trig From tab_equipas Where ';
		$varBD->queryString .= 'tab_equipas_conf = :CONF and tab_equipas_trig <> :EQU';		
		$varBD->queryString .= ' order by tab_equipas_global_rank';
		
		$varBD->parseDBQuery($varBD->queryString);
		
		oci_bind_by_name($varBD->queryParse, ':EQU', $this->mundialOrganizador['pais']);
		oci_bind_by_name($varBD->queryParse, ':CONF', $this->idConf);
		
		oci_execute($varBD->queryParse);

		While ($row = oci_fetch_array($varBD->queryParse, OCI_NUM)){
            array_push($this->equipasSorteio, $row[0]);
		}
		
		$varBD->fechaLigacao();				
		
		$this->sorteioErro = $this->sorteiaPrimeiraFase();
		If (!$this->sorteioErro){
			$this->sorteioErro = $this->insereSorteioBD();			
			
		}
	}
	
	/*******************************************************************************************************************/
	/*
	* Nome: funInicializaGrupos
	* Data: 09/03/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Fun��o que vai fazer a inicializa��o dos grupos para o sorteio (primeira fase)
	* Entrada: Array com o ID de cada um dos grupos
	* Saida: N�o tem
	*/	
	Function funInicializaGrupos($sorteioGrupos, $fase){
		$grupos = array();
		For($i = 0; $i < sizeof($sorteioGrupos); $i++){
			$varGrupo = new compGrupos('GRUPO_'.$sorteioGrupos[$i], $this->prefixoFase[$fase-1], $this->prefixoConf, $this->mundialAno);
			array_push($grupos, $varGrupo);
		}
		
		array_push($this->gruposSorteio, $grupos);
	}
		
		
		
	/*******************************************************************************************************************/
	/*
	* Nome: funSorteiaGrupos
	* Data: 09/03/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Fun��o que vai fazer o sorteio dos grupos para a fase de qualifica��o do Mundial (UEFA)	
	* Entrada: $varEquipas - Array com as equipas ordenadas por ranking da confedera��o
	*		   $fase - A fase a que diz respeito esta ordenação 
	* Saida: N�o tem 
	*/
	Function funSorteiaGrupos($varEquipas, $faseSorteio){

        $varPotes = array_chunk($varEquipas, 10);

		for ($i = 0; $i < sizeof($varPotes); $i++){
			for ($j= 1; $j < 13; $j++){
				shuffle($varPotes[$i]);
			}
		}

		
		For ($i = 0; $i < sizeof($varPotes); $i++){
		    $grupoId = 0;
			For($j = 0; $j < sizeof($varPotes[$i]); $j++){
		        $this->gruposSorteio[$faseSorteio-1][$grupoId]->funSetEquipaGrupo($varPotes[$i][$j]);
                $grupoId++;
			}
		}
	}
			
	
	/*******************************************************************************************************************/
	/*
	* Nome: sorteiaPrimeiraFase
	* Data: 09/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai sortear a primeira fase do apuramento 
	* Entrada: N/A
	* Saida: Código de Erro: 0 se OK, -1 se NOTOK
	* Observações: N/A  
	*/	
	protected function sorteiaPrimeiraFase(){
		//$faseGrupos = array ('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M');
		$faseGrupos = array ('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
		$varEquipas = array();
		
		$this->funInicializaGrupos($faseGrupos, 1);
		$this->funSorteiaGrupos($this->equipasSorteio, 1);
		
        For ($i = 0; $i < sizeof($this->gruposSorteio[0]); $i++){
            $this->sorteioErro = $this->gruposSorteio[0][$i]->funSetGrupoJogos();
        }		
		
		return $this->sorteioErro;
	}
	
	/*******************************************************************************************************************/
	/*
	* Nome: insereSorteioBD
	* Data: 13/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai inserir o resultado do sorteio na BD 
	* Entrada: N/A
	* Saida: Código de Erro: 0 se OK, -1 se NOTOK
	* Observações: N/A  
	*/	
	
	private function insereSorteioBD(){
		
		for ($i = 0; $i < sizeof($this->gruposSorteio[0]); $i++){
			echo "\nGrupo: ".$this->gruposSorteio[0][$i]->funGetGrupoID()."\n";
			$tamanho = $this->gruposSorteio[0][$i]->funGetTotalElementosGrupo();			
			for ($j = 0; $j < $tamanho; $j++){
				echo $this->gruposSorteio[0][$i]->funGetElementosGrupo($j)."\n";
			}
		}
	}
	
}   

?>