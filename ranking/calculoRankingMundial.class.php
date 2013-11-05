<?php
require_once ('classes/executeDBOperations.class.php');

Class calculoRankingMundial{
	protected $equipaNome;
	protected $jogos = array();
	protected $pontosRanking; 
	
	public function __construct($equipa){
		$this->equipaNome = $equipa;
		$returnCode = $this->funPreencheJogos();
	}
	
	private function funPreencheJogos(){
		If ($returnCode = $this->funPreencheJogosEquipa())
			$returnCode = $this->funSetPontosRanking();				
		
		Return $returnCode;
	}	
		
	private function funPreencheJogosEquipa(){				
			
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			exit;
		}
		
		try{
			$varBD->queryString = 'select t_e.tab_equipas_global_rank, t_e.tab_equipas_conf, t_j.tab_jogos_res_casa, t_j.tab_jogos_res_fora, '; 
       		$varBD->queryString .= 'nvl(t_j.tab_jogos_res_casa_prol, -1), nvl(t_j.tab_jogos_res_fora_prol, -1), ';
       		$varBD->queryString .= 'nvl(t_j.tab_jogos_res_casa_pen, -1), nvl(t_j.tab_jogos_res_fora_pen, -1), '; 
       		$varBD->queryString .= 't_j.tab_jogos_data, substr(t_j.tab_jogos_grupo_id, 1, 6), t_j.tab_jogos_id ';
			$varBD->queryString .= 'from tab_jogos t_j, tab_equipas t_e ';
			$varBD->queryString .= 'where t_j.tab_jogos_equipa_casa = :PAISCASA and t_j.tab_jogos_data is not null ';
			$varBD->queryString .= 'and t_e.tab_equipas_trig = t_j.tab_jogos_equipa_fora order by t_j.tab_jogos_equipa_casa';

			$varBD->parseDBQuery($varBD->queryString);
			oci_bind_by_name($varBD->queryParse, ':PAISCASA', $this->equipaNome);								
			
			oci_execute($varBD->queryParse);
			
			while ($row = oci_fetch_array($varBD->queryParse)){
				$dadosJogo = array();
				$dadosJogo['jogoID'] = $row[10];
				$dadosJogo['confValor'] = $this->funGetConfForca($row[1]);    
				$dadosJogo['jogoPts'] = $this->funGetJogoVencPts($row[2], $row[3], $row[4], $row[5], $row[6], $row[7]);
				$dadosJogo['compValor'] = $this->funGetCompValor($row[9]);
				$dadosJogo['oppValor'] = $this->funGetOppValor($row[0]);				
				array_push($this->jogos, $dadosJogo);				
			}
			

			$varBD->queryString = 'select t_e.tab_equipas_global_rank, t_e.tab_equipas_conf, t_j.tab_jogos_res_fora, t_j.tab_jogos_res_casa, '; 
       		$varBD->queryString .= 'nvl(t_j.tab_jogos_res_fora_prol, -1), nvl(t_j.tab_jogos_res_casa_prol, -1), ';
       		$varBD->queryString .= 'nvl(t_j.tab_jogos_res_fora_pen, -1), nvl(t_j.tab_jogos_res_casa_pen, -1), '; 
       		$varBD->queryString .= 't_j.tab_jogos_data, substr(t_j.tab_jogos_grupo_id, 1, 6), t_j.tab_jogos_id ';
			$varBD->queryString .= 'from tab_jogos t_j, tab_equipas t_e ';
			$varBD->queryString .= 'where t_j.tab_jogos_equipa_fora = :PAISFORA and t_j.tab_jogos_data is not null ';
			$varBD->queryString .= 'and t_e.tab_equipas_trig = t_j.tab_jogos_equipa_casa order by t_j.tab_jogos_equipa_fora';
							
			$varBD->parseDBQuery($varBD->queryString);							
			oci_bind_by_name($varBD->queryParse, ':PAISFORA', $this->equipaNome);
			
			oci_execute($varBD->queryParse);
			
			while ($row = oci_fetch_array($varBD->queryParse)){
				$dadosJogo = array();
				$dadosJogo['jogoID'] = $row[10];
				$dadosJogo['confValor'] = $this->funGetConfForca($row[1]);    
				$dadosJogo['jogoPts'] = $this->funGetJogoVencPts($row[2], $row[3], $row[4], $row[5], $row[6], $row[7]);
				$dadosJogo['compValor'] = $this->funGetCompValor($row[9]);
				$dadosJogo['oppValor'] = $this->funGetOppValor($row[0]);				
				array_push($this->jogos, $dadosJogo);				
			}

			$varBD->fechaLigacao();				
			unset($varBD);
			return true;
		}catch(Exception $e){
			$varBD->fechaLigacao();				
			unset($varBD);
			return false;
		}					
	}

	private function funGetConfValor($confID){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			exit;
		}
		
		try{
			$varBD->queryString = 'select tab_conf_peso * 100 from tab_confederacoes where tab_conf_id = :CONF'; 
						
			$varBD->parseDBQuery($varBD->queryString);
			oci_bind_by_name($varBD->queryParse, ':CONF', $confID);								
			oci_execute($varBD->queryParse);
			
			$row = oci_fetch_array($varBD->queryParse);
			$varBD->fechaLigacao();				
			unset($varBD);
			return $row[0];	
		}catch(Exception $e){
			unset($varBD);
			return 0;
		}
		
	}
	
	private function funGetConfForca($opConf){
		$opConfValor = $this->funGetConfValor($opConf);
		$myConf = $this->funGetConfValorPais();

		return ($opConfValor + $myConf)/200;
	}
	
	private function funGetConfValorPais(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			exit;
		}
		
		try{
			$varBD->queryString = 'select tc.tab_conf_peso * 100 from tab_equipas te, tab_confederacoes tc where te.tab_equipas_trig = :PAIS and te.tab_equipas_conf = tc.tab_conf_id'; 
						
			$varBD->parseDBQuery($varBD->queryString);
			oci_bind_by_name($varBD->queryParse, ':PAIS', $this->equipaNome);								
			oci_execute($varBD->queryParse);
			
			$row = oci_fetch_array($varBD->queryParse);
			$varBD->fechaLigacao();				
			unset($varBD);
			return $row[0];								
		}catch(Exception $e){
			unset($varBD);
			return 0;
		}		
	}
	
	
	private function funGetCompValor($compID){
					
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		
		If ($varBD->funGetConnection() == null){
			unset($varBD);
			exit;
		}
		
		try{
			$varBD->queryString = 'select tab_competicao_peso * 100 from tab_competicao where tab_competicao_fase = :FASE'; 
						
			$varBD->parseDBQuery($varBD->queryString);
			oci_bind_by_name($varBD->queryParse, ':FASE', $compID);								
			oci_execute($varBD->queryParse);
			
			$row = oci_fetch_array($varBD->queryParse);
			$varBD->fechaLigacao();				
			unset($varBD);

			return $row[0]/100;								
		}catch(Exception $e){
			unset($varBD);
			return 0;
		}
		
	}
	
	private function funGetJogoVencPts($opGS, $opGM, $opGSPr, $opGMPr, $opGSPn, $opGMPn){
		If($opGSPn != -1 && $opGMPn != -1){
			If ($opGSPn > $opGMPn)
				return 2;
			If ($opGSPn < $opGMPn)
				return 1;
		}elseif($opGSPr != -1 && $opGMPr != -1){
			If ($opGSPr > $opGMPr)
				return 3;
			If ($opGSPr < $opGMPr)
				return 0;
			If ($opGSPr == $opGMPr)
				return 1;
		}else{
			If ($opGS > $opGM)
				return 3;
			If ($opGS < $opGM)
				return 0;
			If ($opGS == $opGM)
				return 1;
		}		
	}
	
	private function funGetOppValor($oppRank){
		If ($oppRank >= 150)
			return 0.5;
		If ($oppRank == 1)
			return 2;
		
		return (200 - $oppRank)/100;
	}
	
	private function funSetPontosRanking(){
		$jogosValorTotal = 0;
		for($i = 0; $i < sizeof($this->jogos); $i++){
			$jogosValorTotal += round(100*($this->jogos[$i]['jogoPts']*($this->jogos[$i]['compValor']*($this->jogos[$i]['oppValor']*$this->jogos[$i]['confValor']))));	
		}
		
		If($jogosValorTotal == 0)
			$this->pontosRanking = $jogosValorTotal;
		Else
			$this->pontosRanking = round($jogosValorTotal/$i);
				
		
		return true;
	}
	
	public function funGetEquipaNome(){
		return $this->equipaNome;
	}	
	
	public function funGetEquipaPontos(){
		return $this->pontosRanking;
	}
}
