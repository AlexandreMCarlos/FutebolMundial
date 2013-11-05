<?php
require_once ('classes/executeDBOperations.class.php');
require_once ('simuladorEquipa.class.php');


Class jogosSimulados{
	
	private $jogoID;
	private $eqCasa; //Objecto que irá conter uma equipa
	private $eqFora; //Objecto que irá conter uma equipa
	private $criterioI = array();
	private $criterioII = array();
	private $criterioIII = array();
	private $valoresFinais = array();
	private $desfechoJogo; //'C'asa, 'E'mpate ou 'F'ora
	private $resultadoFinal = array(); //Terá o resultado final do jogo
	
	private $returnCode = 0;
	
	public function __construct($jogoID, $eqCasa, $eqFora){
		//echo "JOGO: ".$jogoID."\n";
		//echo "CASA: ".$eqCasa."\n";
		//echo "FORA: ".$eqFora."\n";
		$this->jogoID = $jogoID;
		$this->eqCasa = new simuladorEquipa($eqCasa, 'C', $jogoID);
		if ($this->eqCasa->funGetReturnCode() == 0){
			$this->eqFora = new simuladorEquipa($eqFora, 'F', $jogoID);
			if ($this->eqFora->funGetReturnCode() == 0){
				$this->funCalculaCriterioI();
				//echo "Criterio I: ".$this->criterioI[0]." - ".$this->criterioI[1]." - ".$this->criterioI[2]."\n";
				
				if ($this->returnCode == 0){
					$this->funCalculaCriterioII();
					//echo "Criterio II: ".$this->criterioII[0]." - ".$this->criterioII[1]." - ".$this->criterioII[2]."\n";
					if ($this->returnCode == 0){
						$this->funCalculaCriterioIII();
						//echo "Criterio III: ".$this->criterioIII[0]." - ".$this->criterioIII[1]." - ".$this->criterioIII[2]."\n";										
						if ($this->returnCode == 0){
							$this->funCalculaValoresFinais();
							//echo "Valores finais: ".$this->valoresFinais[0]." - ".$this->valoresFinais[1]." - ".$this->valoresFinais[2]."\n";
							if ($this->returnCode == 0){
								$this->funCalculaDesfechoJogo();
								//echo "Desfecho do Jogo: ".$this->desfechoJogo."\n";
								echo "Jogo: ".$this->resultadoFinal[0]."  ".$this->resultadoFinal[1]." ".$this->resultadoFinal[2]." - ".$this->resultadoFinal[3]." ".$this->resultadoFinal[4]."\n";
								echo $this->resultadoFinal[0]." N ".$this->resultadoFinal[2]." ".$this->resultadoFinal[4]."\n";
							}																		
						}				
					}					
				}				
			}
		}
	}
		
		
	public function funGetReturnCode(){
		return $this->returnCode;
	}
	
	
	private function funCalculaCriterioI(){
		$critICasa = array();
		$critIFora = array();
		$valVit;
		$valEmp;
		$valDrr;

		try{
			$critICasa = $this->eqCasa->funDevolveCriterioI();
			$critIFora = $this->eqFora->funDevolveCriterioI();
			
			$valVit = round(($critICasa[0] + $critIFora[0])/2);
			$valEmp = round(($critICasa[1] + $critIFora[1])/2);
			$valDrr = round(($critICasa[2] + $critIFora[2])/2);
			
			array_push($this->criterioI, $valVit, $valEmp, $valDrr);
		}catch(Exception $e){
			$this->returnCode = -915;
		}
	} 
	

	private function funCalculaCriterioII(){
		$critIICasa = array();
		$critIIFora = array();
		$valVit;
		$valEmp;
		$valDrr;

		try{
			$critIICasa = $this->eqCasa->funDevolveCriterioII();
			$critIIFora = $this->eqFora->funDevolveCriterioII();
			
			$valVit = round(($critIICasa[0] + $critIIFora[0])/2);
			$valEmp = round(($critIICasa[1] + $critIIFora[1])/2);
			$valDrr = round(($critIICasa[2] + $critIIFora[2])/2);
			
			array_push($this->criterioII, $valVit, $valEmp, $valDrr);
		}catch(Exception $e){
			$this->returnCode = -916;
		}
	} 
	

	private function funCalculaCriterioIII(){
		$critIIICasa = array();
		$critIIIFora = array();
		$valVit;
		$valEmp;
		$valDrr;

		try{
			$critIIICasa = $this->eqCasa->funDevolveCriterioIII();
			$critIIIFora = $this->eqFora->funDevolveCriterioIII();
            
            $valVit = round(($critIIICasa[0] + $critIIIFora[0])/2);
            $valEmp = round(($critIIICasa[1] + $critIIIFora[1])/2);
            $valDrr = round(($critIIICasa[2] + $critIIIFora[2])/2);
            
            array_push($this->criterioIII, $valVit, $valEmp, $valDrr);
        }catch(Exception $e){
            $this->returnCode = -916;
        }
    } 

	
	private function funCalculaValoresFinais(){
		$vFinaisCasa = array();
		$vFinaisFora = array();
		
		$valCasa;
		$valEmp;
		$valFora;
		
		$valCasa = round((($this->criterioI[0] + $this->criterioII[0] + $this->criterioIII[0])/3)/10);
		$valEmp = round((($this->criterioI[1] + $this->criterioII[1] + $this->criterioIII[1])/3)/10);
		$valFora = round((($this->criterioI[2] + $this->criterioII[2] + $this->criterioIII[2])/3)/10);
		
		array_push($this->valoresFinais, $valCasa, $valEmp, $valFora);
	}
	
	
	private function funCalculaDesfechoJogo(){
		$equipaCasa = $this->eqCasa->funGetEquipanome();
		$equipaFora = $this->eqFora->funGetEquipanome();
		$valoresSorteio = array();
		
		for ($idx = 0; $idx < sizeof($this->valoresFinais); $idx++){
			$indice = $this->valoresFinais[$idx];
			for ($idxII = 1; $idxII <= $indice; $idxII++){
				if ($idx == 0){
					array_push($valoresSorteio, $equipaCasa);
				}elseif ($idx == 2){
					array_push($valoresSorteio, $equipaFora);
				}else
					array_push($valoresSorteio, "EMP");	
			}
		}
		
		/*for ($idx = 0; $idx < sizeof($valoresSorteio); $idx++)
			echo $valoresSorteio[$idx]." ";*/
		$valoresSorteio = $this->funAcertaHipotesesDesfecho($valoresSorteio);
		/*echo "\n";
		for ($idx = 0; $idx < sizeof($valoresSorteio); $idx++)
			echo $valoresSorteio[$idx]." ";*/
		
		$this->desfechoJogo = $this->funEfectuaSorteioFinal($valoresSorteio);
		
		$this->funDeterminaResultado();
	}
	
	private function funDeterminaResultado(){
		$equipaCasa = $this->eqCasa->funGetEquipanome();
		$equipaFora = $this->eqFora->funGetEquipanome();
		$intersect = array();
		$intersectII = array();
		$valoresCalculo = array();
		$golosCasa = 0;
		$golosFora = 0;		

		If ($this->desfechoJogo == $equipaCasa){
			$intersect = array_intersect($this->eqCasa->funDevolveGolosMar(), $this->eqFora->funDevolveGolosSof());
			//Elimina os valores 0 do array e determina os golos marcados pela equipa que joga em casa (vencedora)			
			for ($idx = 0; $idx < sizeof($intersect); $idx++){
				If (!isset($intersect[$idx]))
					$intersect[$idx] = 0;				
				If ($intersect[$idx] != 0)
					array_push($valoresCalculo, $intersect[$idx]);	
			}
			
			if (sizeof($valoresCalculo) > 0){
				shuffle($valoresCalculo);
				$golosCasa = $valoresCalculo[0];				
			}else{
				array_push($valoresCalculo, 1, 2, 2, 2, 3, 3);
				shuffle($valoresCalculo);
				$golosCasa = $valoresCalculo[0];
			}			
			$valoresCalculo = array();
			//Agora vai calcular os golos da equipa que joga fora (derrotado)
			$intersect = array_intersect($this->eqCasa->funDevolveGolosSof(), $this->eqFora->funDevolveGolosMar());
			for ($idx = 0; $idx < sizeof($intersect); $idx++){
				If (!isset($intersect[$idx]))
					$intersect[$idx] = 0;	
				If ($intersect[$idx] <  $golosCasa)
					array_push($valoresCalculo, $intersect[$idx]);	
			}
			
			if (sizeof($valoresCalculo) > 0){
				shuffle($valoresCalculo);
				$golosFora = $valoresCalculo[0];				
			}		
		}ElseIf ($this->desfechoJogo == $equipaFora){
			$intersect = array_intersect($this->eqFora->funDevolveGolosMar(), $this->eqCasa->funDevolveGolosSof());
			//Elimina os valores 0 do array e determina os golos marcados pela equipa que joga fora (vencedora)
			for ($idx = 0; $idx < sizeof($intersect); $idx++){
				If (!isset($intersect[$idx]))
					$intersect[$idx] = 0;
				If ($intersect[$idx] != 0){
					array_push($valoresCalculo, $intersect[$idx]);
				}
			}
			
			if (sizeof($valoresCalculo) > 0){
				shuffle($valoresCalculo);
				$golosFora = $valoresCalculo[0];				
			}else{
				array_push($valoresCalculo, 1, 2, 2, 2, 3, 3);
				shuffle($valoresCalculo);
				$golosFora = $valoresCalculo[0];
			}			
			$valoresCalculo = array();
			//Agora vai calcular os golos da equipa que joga em casa (derrotado)
			$intersect = array_intersect($this->eqFora->funDevolveGolosSof(), $this->eqCasa->funDevolveGolosMar());
			for ($idx = 0; $idx < sizeof($intersect); $idx++){
				If (!isset($intersect[$idx]))
					$intersect[$idx] = 0;	
				If ($intersect[$idx] <  $golosFora)
					array_push($valoresCalculo, $intersect[$idx]);	
			}
			
			if (sizeof($valoresCalculo) > 0){
				shuffle($valoresCalculo);
				$golosCasa = $valoresCalculo[0];
			}
		}Else{
			$intersect = array_intersect($this->eqCasa->funDevolveGolosMar(), $this->eqFora->funDevolveGolosSof());
			$intersectII = array_intersect($this->eqFora->funDevolveGolosMar(), $this->eqCasa->funDevolveGolosSof());
			
			$valoresCalculo = array_intersect($intersect, $intersectII);
		
			if (sizeof($valoresCalculo) > 0){
				shuffle($valoresCalculo);
				$golosCasa = $valoresCalculo[0];
				$golosFora = $golosCasa;
			}else{
				array_push($valoresCalculo, 0, 0, 1, 2, 2, 2, 3);
				shuffle($valoresCalculo);
				$golosCasa = $valoresCalculo[0];
				$golosFora = $golosCasa;
			}			
		}
		//Parte final em que vai ser colocado o resultado final do jogo
		array_push($this->resultadoFinal, $this->jogoID);
		array_push($this->resultadoFinal, $this->eqCasa->funGetEquipaNome());
		array_push($this->resultadoFinal, $golosCasa);
		array_push($this->resultadoFinal, $this->eqFora->funGetEquipaNome());
		array_push($this->resultadoFinal, $golosFora);
	}
	
	
	private function funEfectuaSorteioFinal($valores){
		$randI = rand(); //Nº de vezes que o array vai ser baralhado
		$randMinII = 0; //Posição 0 do array
		$randMaxII = sizeof($valores)-1; //Posição N do array
		
		for ($idx = 0; $idx <= $randI; $idx++)
			shuffle($valores);
		
		$randIII = rand($randMinII, $randMaxII);
		
		return $valores[$randIII];
	}


	
	private function funAcertaHipotesesDesfecho($valores){
		$valoresSorteio = array();
		$valoresSorteio = $valores;
		$nivCasa = $this->eqCasa->funDevolveEquipaNivel();
		$nivFora = $this->eqFora->funDevolveEquipaNivel();
		$equipaCasa = $this->eqCasa->funGetEquipanome();
		$equipaFora = $this->eqFora->funGetEquipanome();
		
		$jogoConf = $this->eqCasa->funDevolveEquipaConf();				
		
		if ($nivCasa < $nivFora){			
			$difNiv = $nivFora - $nivCasa;
			if ($difNiv <= 1){
				array_push($valoresSorteio, $equipaCasa);	
			}elseif ($difNiv <= 2){
				array_push($valoresSorteio, $equipaCasa, $equipaCasa);
			}elseif ($difNiv <= 3){
				array_push($valoresSorteio, $equipaCasa, $equipaCasa, $equipaCasa);
			}elseif($difNiv <= 4){
				array_push($valoresSorteio, $equipaCasa, $equipaCasa, $equipaCasa, $equipaCasa);
			}elseif($difNiv <= 5){
				array_push($valoresSorteio, $equipaCasa, $equipaCasa, $equipaCasa, $equipaCasa, $equipaCasa);
			}elseif($difNiv <= 6){
				array_push($valoresSorteio, $equipaCasa, $equipaCasa, $equipaCasa, $equipaCasa, $equipaCasa, $equipaCasa);
			}elseif($difNiv <= 7){
				array_push($valoresSorteio, $equipaCasa, $equipaCasa, $equipaCasa, $equipaCasa, $equipaCasa, $equipaCasa, $equipaCasa);
			}elseif($difNiv <= 8){
				array_push($valoresSorteio, $equipaCasa, $equipaCasa, $equipaCasa, $equipaCasa, $equipaCasa, $equipaCasa, $equipaCasa, $equipaCasa);				
			}elseif($difNiv <= 9){
				array_push($valoresSorteio, $equipaCasa, $equipaCasa, $equipaCasa, $equipaCasa, $equipaCasa, $equipaCasa, $equipaCasa, $equipaCasa, $equipaCasa); 			
			} 			
		}elseif ($nivCasa > $nivFora){
			$difNiv = $nivCasa - $nivFora;
			if ($difNiv <= 1){
				array_push($valoresSorteio, $equipaFora);	
			}elseif ($difNiv <= 2){
				array_push($valoresSorteio, $equipaFora, $equipaFora);
			}elseif ($difNiv <= 3){
				array_push($valoresSorteio, $equipaFora, $equipaFora, $equipaFora);
			}elseif($difNiv <= 4){
				array_push($valoresSorteio, $equipaFora, $equipaFora, $equipaFora, $equipaFora);
			}elseif($difNiv <= 5){
				array_push($valoresSorteio, $equipaFora, $equipaFora, $equipaFora, $equipaFora, $equipaFora);
			}elseif($difNiv <= 6){
				array_push($valoresSorteio, $equipaFora, $equipaFora, $equipaFora, $equipaFora, $equipaFora, $equipaFora);
			}elseif($difNiv <= 7){
				array_push($valoresSorteio, $equipaFora, $equipaFora, $equipaFora, $equipaFora, $equipaFora, $equipaFora, $equipaFora);
			}elseif($difNiv <= 8){
				array_push($valoresSorteio, $equipaFora, $equipaFora, $equipaFora, $equipaFora, $equipaFora, $equipaFora, $equipaFora, $equipaFora);				
			}elseif($difNiv <= 9){
				array_push($valoresSorteio, $equipaFora, $equipaFora, $equipaFora, $equipaFora, $equipaFora, $equipaFora, $equipaFora, $equipaFora, $equipaFora); 			
			} 			
		}
		return $valoresSorteio;
	}	
}

?>
	