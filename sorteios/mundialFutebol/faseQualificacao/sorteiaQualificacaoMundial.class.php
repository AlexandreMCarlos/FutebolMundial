<?php

require_once('sorteios/mundialFutebol/sorteiaOrganizadorMundial.class.php');
require_once('classes/executeDBOperations.class.php');
require_once('classes/MySQLBDClass.class.php');

require_once ('ranking/rankingFutebolMundial.class.php');

class sorteiaQualificacaoMundial{
	
	protected $organizadorMundial = array();
	protected $prefixoQualif = 'CMQ';
	protected $prefixoFaseFinal = 'CMF';
	protected $erroSorteio;
	protected $mundialAno;
	protected $sorteioMundialConfs = array();
	
	
	/*******************************************************************************************************************/
	/*
	* Nome: __construct
	* Data: 07/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai dar inicio ao sorteio da fase de qualificação para o Mundial de Futebol 
	* Entrada: N/A
	* Saida: N/A
	* Observações: Este método constructor irá determinar qual o organizador e invocar os métodos constructores
	*			   para o sorteio de cada uma das 6 confederações envolvidas.
	*			   Irá também inserir na BD todos os dados relativos à organização do Mundial  
	*/	
	public function __construct(){
		$ranking = new rankingFutebolMundial();
		$ranking->funSetPontosRanking();
			
		$this->funSetMundialAno();
		
		//echo $this->mundialAno."\n";
		
		If ($this->okSorteioMundial() == 2){	
			$sorteioOrganizador = new sorteiaOrganizadorMundial($this->mundialAno);
			$sorteioOrganizador->sorteiaPrimeiraFase();
			$this->organizadorMundial['pais'] = $sorteioOrganizador->funGetOrganizadorMundial();
			$this->organizadorMundial['conf'] = $this->funGetOrganizadorConfederacao();
								
			
			$this->erroSorteio = $this->efectuaSorteioMundial();
		}else{
			unset($this);
			exit('O Sorteio para o Mundial de Futebol escolhido já foi feito.');
		}		
	}
	
	/*******************************************************************************************************************/
	/*
	* Nome: funSetMundialAno
	* Data: 16/03/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Fun��o que vai determinar qual o Ano para o Campeonato Mundial em quest�o
	* Entrada: N�o tem.
	* Saida: C�digo de erro: 0 se OK, -1 se NOTOK.
	*/
		
	Function funSetMundialAno(){
		$returnCode = 0;
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($this);
			unset($varBD);
			exit('Não é possivel proceder ao sorteio para o Mundial de Futebol');
		}

		$varBD->queryString = 'Begin :retVal := package_mundial.fun_getMundialAno(:QUAL); End;';
        $varBD->parseDBQuery($varBD->queryString);
		oci_bind_by_name($varBD->queryParse, ':QUAL', $this->prefixoQualif);
		oci_bind_by_name($varBD->queryParse, ':retVal', $this->mundialAno, 50);

		oci_execute($varBD->queryParse);

		$varBD->fechaLigacao();
		unset($varBD);
	}
		
	
	/*******************************************************************************************************************/
	/*
	* Nome: okSorteioMundial
	* Data: 11/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Determina se é possível ou não efectuar o sorteio do mundial em questão 
	* Entrada: N/A
	* Saida: N/A
	* Observações: N/A  
	*/		
	private function okSorteioMundial(){
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		
		If ($varBD->funGetConnection() == null){
			unset($this);
	 		unset($varBD);
			exit('Não é possivel abrir a ligação com a BD.');
		}
		
		$varBD->queryString = 'Select count(1) From tab_comp_actual Where tab_ca_tipo In (:CMQ, :CMF) and tab_ca_fase in (:QUALIF, :FF) and tab_ca_fim = -1';
        $varBD->parseDBQuery($varBD->queryString);
		
		$qualifMundial = $this->prefixoQualif.($this->mundialAno-1);
		$faseFinalMundial = $this->prefixoFaseFinal.($this->mundialAno-1);		
		
		oci_bind_by_name($varBD->queryParse, ':CMQ', $qualifMundial);	
		oci_bind_by_name($varBD->queryParse, ':CMF', $faseFinalMundial);
		oci_bind_by_name($varBD->queryParse, ':QUALIF', $this->prefixoQualif);	
		oci_bind_by_name($varBD->queryParse, ':FF', $this->prefixoFaseFinal);		
		
		oci_execute($varBD->queryParse);
		
		$row = oci_fetch_array($varBD->queryParse);
	 	$varBD->fechaLigacao();
	 	unset($varBD);
		
		Return $row[0];	
		
	}


	/*******************************************************************************************************************/
	/*
	* Nome: funGetOrganizadorConfederacao
	* Data: 07/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai retornar a confederação de um país, neste caso o organizador 
	* Entrada: N/A
	* Saida: N/A
	* Observações: N/A
	*/
	public function funGetOrganizadorConfederacao(){
		$returnCode;	
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		
		If ($varBD->funGetConnection() == null){
			unset($this);
			exit('Não é possivel abrir a ligação com a BD.');
		}		
		
		$varBD->queryString = 'Begin :RETVAL := package_equipas.funGetConfederacao(:EQU); End;';
        $varBD->parseDBQuery($varBD->queryString);
		oci_bind_by_name($varBD->queryParse, ':RETVAL', $returnCode, 50);	
		oci_bind_by_name($varBD->queryParse, ':EQU', $this->organizadorMundial['pais']);

		oci_execute($varBD->queryParse);

		$varBD->fechaLigacao();
		unset($varBD);
		
		return $returnCode;				
	}
	
	/*******************************************************************************************************************/
	/*
	* Nome: efectuaSorteioMundial
	* Data: 08/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai dar inicio ao sorteio da fase de qualificação para o Mundial de Futebol 
	* Entrada: N/A
	* Saida: Código de Erro: 1 se OK, 0 se NOTOK
	* Observações: N/A  
	*/	
	protected function efectuaSorteioMundial(){
		$returnCode = 0;
		$mysqlBD = new MySQLBDClass();
		$mysqlBD->abreLigacao();
		$mysqlBD->activaBD();
		//$mysqlQuery = 'Select CLASS_DIR, FICHEIRO From includesSorteioMundial Where idSorteioMundial = 1 Order By idSorteioMundial';
		$mysqlQuery = 'Select CLASS_DIR, FICHEIRO From includesSorteioMundial Where idSorteioMundial < 7 Order By idSorteioMundial';
		$mysqlBD->funExecutaQuery($mysqlQuery);
		
		While ($resultado = mysql_fetch_assoc($mysqlBD->resultado)){			
			require_once($resultado['CLASS_DIR']."/".$resultado['FICHEIRO']);
		}
		
		unset($mysqlBD);
		/* Aqui vai iniciar a chamada às várias rotinas do sorteio */
		/* Ásia */
		$sorteioAsia = new sorteioMundialAsia($this->organizadorMundial, $this->mundialAno);
		If (!$sorteioAsia->sorteioErro){
			array_push($this->sorteioMundialConfs, $sorteioAsia);
			/* Europa */
			$sorteioEuropa = new sorteioMundialEuropa($this->organizadorMundial, $this->mundialAno);
			If (!$sorteioEuropa->sorteioErro){
				array_push($this->sorteioMundialConfs, $sorteioEuropa);
				/* África */
				$sorteioAfrica = new sorteioMundialAfrica($this->organizadorMundial, $this->mundialAno);				
				If (!$sorteioAfrica->sorteioErro){
					array_push($this->sorteioMundialConfs, $sorteioAfrica);
					/* América do Norte */
					$sorteioAmNorte = new sorteioMundialAmNorte($this->organizadorMundial, $this->mundialAno);
					If (!$sorteioAmNorte->sorteioErro){
						array_push($this->sorteioMundialConfs, $sorteioAmNorte);
						/* América do Sul */
						$sorteioAmSul = new sorteioMundialAmSul($this->organizadorMundial, $this->mundialAno);
						If (!$sorteioAmSul->sorteioErro){
							array_push($this->sorteioMundialConfs, $sorteioAmSul);
						 	If ($this->organizadorMundial['conf'] <> 5){
						 		/* Oceania */
								$sorteioOFC = new sorteioMundialOFC($this->organizadorMundial, $this->mundialAno);
								If (!$sorteioOFC->sorteioErro){
									array_push($this->sorteioMundialConfs, $sorteioOFC);
									/* Todos os sorteios OK. Vou prosseguir para a rotina de inserção na BD*/
									$returnCode = $this->insereQualifMundialBD();
								}
							}
							else{
								$returnCode = $this->insereQualifMundialBD();	
							}																
						}						
					}
				}
			}		
		}
		unset($sorteioAsia);
		unset($sorteioAfrica);
		unset($sorteioEuropa);
		unset($sorteioAmNorte);
		unset($sorteioAmSul);
	}

	/*******************************************************************************************************************/
	/*
	* Nome: insereQualifMundialBD
	* Data: 15/03/2012
	* Autor: Alexandre M. Carlos
	* Acção: Vai inserir os dados da qualificação na BD 
	* Entrada: N/A
	* Saida: Código de Erro: 1 se OK, 0 se NOTOK
	* Observações: N/A  
	*/	
	private function insereQualifMundialBD(){
		require_once ('classes/procedimentosSorteio.class.php');	
		$returnCode = 0;
		/* Insersão geral */
		$procSorteio = new procedimentosSorteio();
		$returnCode = $procSorteio->funInsereOrganizador($this->mundialAno, $this->organizadorMundial['pais'], 'tab_fifa_wc');
		/*echo "Retorno: ".$returnCode."\n";
		echo "Org: ".$this->organizadorMundial['pais']."\n";*/
		If (!$returnCode){
			/*echo "Entrei aqui!!!\n";*/
			$returnCode = $procSorteio->insereCompeticaoActual($this->prefixoQualif, $this->prefixoQualif.$this->mundialAno, $this->organizadorMundial['pais']);
			If (!$returnCode){
				/* Inserção para cada uma das confederações */
				For ($i = 0; $i < sizeof($this->sorteioMundialConfs); $i++){
					$returnCode = 0;
					If (!$returnCode){
						$returnCode = $procSorteio->insereCompeticaoActual($this->sorteioMundialConfs[$i]->prefixoFase[0], 
																		   $this->sorteioMundialConfs[$i]->prefixoConf.$this->mundialAno, 
																		   $this->organizadorMundial['pais']);
/*						echo "Bu ".$this->sorteioMundialConfs[$i]->prefixoFase[0]."\n";
						echo "Retorno :".$returnCode."\n";*/
						If (!$returnCode){
			                For ($j = 0; $j < sizeof($this->sorteioMundialConfs[$i]->gruposSorteio[0]); $j++){
			                    $returnCode = $procSorteio->insereGruposCompeticao($this->sorteioMundialConfs[$i]->gruposSorteio[0][$j]);
								
			                    If (!$returnCode){
			                        $returnCode = $procSorteio->insereJogosCompeticao($this->sorteioMundialConfs[$i]->gruposSorteio[0][$j]);
			                    }
			                }
						}	 						
					}
				}
			}
		}
	unset($procSorteio);
	return $returnCode;		
	}
}

?>