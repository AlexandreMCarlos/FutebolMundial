<?php

require_once ('classes/executeDBOperations.class.php');
require_once ('simuladorJogos.class.php');


Class simuladorGrupo{
		
	private $grupoID; //ID do grupo
	private $elementosGrupo; //Elementos do grupo
	private $jornadasGrupo; //Numero de jogos a simular por jornada		
	private $returnCode = 0;
	private $jogosSimulados = array(); //Jogos a simular (novo objecto jogo)
	
	public function __construct($gruposASimular){
		$this->grupoID = $gruposASimular;
		$this->funGetJornadasGrupo();
		If ($this->returnCode < 0){
			break;	
		}else{
			$this->funGetJogosJornada();
		}
	}
	
	
	private function funGetJornadasGrupo(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
			
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			$this->returnCode = -904; //Erro de ligação a BD
		}		
		
		try{
			$varBD->queryString = 'select count(tab_grupos_equipa) from tab_grupos ';
			$varBD->queryString .= 'where tab_grupos_id = :GRP';			
			
	        $varBD->parseDBQuery($varBD->queryString);		
			oci_bind_by_name($varBD->queryParse, ':GRP', $this->grupoID);

			oci_execute($varBD->queryParse);
			
			$row = oci_fetch_array($varBD->queryParse);
			$this->elementosGrupo = $row[0];
			$varBD->fechaLigacao();
			unset($varBD);
			$this->jornadasGrupo = $this->calculaJornadas($this->elementosGrupo); //Calcula quantos jogos de cada jornada serão simulados
			//echo "JORNADAS A SIMULAR NESTE GRUPO: ".$this->jornadasGrupo."\n";
						
		}catch(Exception $e){
			$varBD->fechaLigacao();
			unset($varBD);
			$this->returnCode = -907; //Erro na determinação das jornadas a simular
		}
	}
	
	
	private function calculaJornadas($elemsGrupo){		
		return round((($elemsGrupo-1)/2));
	}
	
	private function funGetJogosJornada(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
					
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			$this->returnCode = -904; //Erro de ligação a BD
		}
		
		try{
			$varBD->queryString = 'select tab_jogos_id, tab_jogos_equipa_casa, tab_jogos_equipa_fora from ( ';
  			$varBD->queryString .= 'select tab_jogos_id, tab_jogos_equipa_casa, tab_jogos_equipa_fora from '; 
      		$varBD->queryString .= '(select * from tab_jogos where tab_jogos_grupo_id = :GRP '; 
       		$varBD->queryString .= 'and tab_jogos_data is null order by tab_jogos_id) jogos ';
  			$varBD->queryString .= 'where rownum <= :JOR ';
  			$varBD->queryString .= 'order by rownum) ';
			$varBD->queryString .= 'where tab_jogos_equipa_casa not in (select tab_equ_utilizador from tab_equipa_utilizador ';
			$varBD->queryString .= 'where tab_equipa_ano = to_number(substr(:GRP, 11, 4))) '; 
			$varBD->queryString .= 'and tab_jogos_equipa_fora not in (select tab_equ_utilizador from tab_equipa_utilizador ';
            $varBD->queryString .= 'where tab_equipa_ano = to_number(substr(:GRP, 11, 4))) ';
			$varBD->queryString .= 'and tab_jogos_id < ALL (select min(tab_jogos_id) + :JOR from tab_jogos where tab_jogos_grupo_id = :GRP and tab_jogos_data is null)';
		
	        $varBD->parseDBQuery($varBD->queryString);		
			oci_bind_by_name($varBD->queryParse, ':GRP', $this->grupoID);
			oci_bind_by_name($varBD->queryParse, ':JOR', $this->jornadasGrupo);
			
			oci_execute($varBD->queryParse);
			
			While ($row = oci_fetch_array($varBD->queryParse)){
				$jogos = new jogosSimulados($row[0], $row[1], $row[2]);
				$this->returnCode = $jogos->funGetReturnCode();
				
				if ($this->returnCode == 0){									
					array_push($this->jogosSimulados, $jogos);
					unset($jogos);
				}else
					break;
			}
			$varBD->fechaLigacao();
			unset($varBD);
		}catch(Exception $e){
			$varBD->fechaLigacao();
			unset($varBD);
			$this->returnCode = -908; //Erro na determinação das jornadas a simular
		}				
	}
	
	
	public function funGetReturnCode(){
		return $this->returnCode;
	}
	
	public function funGetGrupoID(){
		return $this->grupoID;
	} 
}    
?>