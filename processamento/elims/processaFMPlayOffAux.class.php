<?php
require_once ('classes/executeDBOperations.class.php');

Class processaFMPlayOffAux{
	protected $equipaA = array();
	protected $equipaB = array();
	protected $plfJogos = array();
	protected $plfGrupo;
	public $erroPlf = true;
	
	public function __construct($grupo){
		$this->plfGrupo = $grupo;
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			$this->erroPlf = false;
			exit;
		}
		
		try{			
			$varBD->queryString = 'Select min(tab_jogos_id), max(tab_jogos_id) from tab_jogos ';
			$varBD->queryString .= 'Where tab_jogos_grupo_id = :GRP';
			
			$varBD->parseDBQuery($varBD->queryString);					
			oci_bind_by_name($varBD->queryParse, ':GRP', $this->plfGrupo);			
			oci_execute($varBD->queryParse);
			
			$row = oci_fetch_array($varBD->queryParse);
			array_push($this->plfJogos, $row[0], $row[1]);

			$varBD->fechaLigacao();				
			unset($varBD);
			
			if ($this->funGetPrimeiraMao()){
				$this->funGetSegundaMao();
			}
			else{
				$this->erroPlf = false;
			}									
		}catch (Exception $e){
			unset($varBD);
			$this->erroPlf = false;
		}
		
			
	}
	
	protected function funGetPrimeiraMao(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
			
		try{			 
			$varBD->queryString = 'select tab_jogos_equipa_casa, tab_jogos_res_casa, tab_jogos_equipa_fora, tab_jogos_res_fora ';
			$varBD->queryString .= 'from tab_jogos where tab_jogos_id = :JOGO';
			$varBD->parseDBQuery($varBD->queryString);					
			oci_bind_by_name($varBD->queryParse, ':JOGO', $this->plfJogos[0]);			
			oci_execute($varBD->queryParse);
			
			$row = oci_fetch_array($varBD->queryParse);
			$this->equipaA['NOME'] = $row[0];
			$this->equipaA['FORA'] = 0;
			$this->equipaA['TOTAL']	= $row[1];
			$this->equipaB['NOME'] = $row[2];
			$this->equipaB['TOTAL'] = $row[3];
			$this->equipaB['FORA'] = $row[3]; 
			
			$varBD->fechaLigacao();				
			unset($varBD);

			return true;		
		}catch (Exception $e){
			return false;	
		}
	}
	
	protected function funGetSegundaMao(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			return false;
		}
		
		try{
			//echo "Jogo: ".$this->plfJogos[1]."\n";			
			$varBD->queryString = 'select tab_jogos_res_casa, tab_jogos_res_fora, '; 
			$varBD->queryString .= 'nvl(tab_jogos_res_casa_prol, 0), nvl(tab_jogos_res_fora_prol, 0), ';
			$varBD->queryString .= 'nvl(tab_jogos_res_casa_pen, 0), nvl(tab_jogos_res_fora_pen, 0) ';
			$varBD->queryString .= 'from tab_jogos where tab_jogos_id = :JOGO';
			$varBD->parseDBQuery($varBD->queryString);					
			oci_bind_by_name($varBD->queryParse, ':JOGO', $this->plfJogos[1]);			
			oci_execute($varBD->queryParse);
			
			$row = oci_fetch_array($varBD->queryParse);
			$this->equipaA['TOTAL']	= $this->equipaA['TOTAL'] + $row[1] + $row[3];
			$this->equipaA['FORA'] = $row[1] + $row[3];
			$this->equipaA['PEN'] = $row[5];
						
			$this->equipaB['TOTAL'] = $this->equipaB['TOTAL'] + $row[0] + $row[2];
			$this->equipaB['PEN'] = $row[4];

			$varBD->fechaLigacao();				
			unset($varBD);
			
			return true;		
		}catch (Exception $e){
			return false;	
		}
	}
	
	public function funTrataPlayOff(){
		$vencedor = array();
		echo "Equipa: ".$this->equipaA['NOME']." - Total: ".$this->equipaA['TOTAL']." - Fora: ".$this->equipaA['FORA']."\n";
		echo "Equipa: ".$this->equipaB['NOME']." - Total: ".$this->equipaB['TOTAL']." - Fora: ".$this->equipaB['FORA']."\n";
		If ($this->equipaA['TOTAL'] > $this->equipaB['TOTAL']){
			$vencedor = $this->equipaA['NOME'];
		}
		elseif ($this->equipaB['TOTAL'] > $this->equipaA['TOTAL']){
			$vencedor = $this->equipaB['NOME'];		
		}
		else{
			If ($this->equipaA['FORA'] > $this->equipaB['FORA']){ 
				$vencedor = $this->equipaA['NOME'];
			}
			elseif ($this->equipaB['FORA'] > $this->equipaA['FORA']){
				$vencedor = $this->equipaB['NOME'];
			}
			else{
				If ($this->equipaA['PEN'] > $this->equipaB['PEN']){ 
					$vencedor = $this->equipaA['NOME'];
				}
				elseif ($this->equipaB['PEN'] > $this->equipaA['PEN']){
					$vencedor = $this->equipaB['NOME'];
				}else
					$vencedor = false;
			}
		}
		return $vencedor;			
	}
	
}
?>