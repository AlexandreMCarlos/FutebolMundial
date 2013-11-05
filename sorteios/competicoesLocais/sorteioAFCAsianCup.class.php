<?php

require_once ('../../classes/executeDBOperations.class.php');
require_once ('../../classes/compGrupos.class.php');
require_once ('../../classes/procedimentosSorteio.class.php');

Class SorteioAFCAsianCup{

	private $acOrganizador;
	private $acPrefixo = 'ACQ';
    private $acFase = 'ACQ_01';
	private $acAno;
	private $acQualifGrupos = array();
	private $sorteioErro;
	
	

	/*******************************************************************************************************************/
	/*
	* Nome: __construct
	* Data: 31/01/2012
	* Autor: Alexandre M. Carlos
	* Acção: Constructor da classe. Efectua o sorteio.
	* Entrada: Não tem.
	* Saida: Não tem.
	*/
	Function __construct(){
        echo "Vou começar.\n";
        $this->sorteioErro = $this->funSetAFCAsianCupAno();

		If ($this->sorteioErro == 0){
			$this->sorteioErro = $this->funSetAFCAsianCupOrganizador();

			If ($this->sorteioErro == 0){
				$this->sorteioErro = $this->funSetGrpQualifAC();

                If ($this->sorteioErro == 0){

                    For ($i = 0; $i < sizeof($this->acQualifGrupos); $i++){
                        $this->sorteioErro = $this->acQualifGrupos[$i]->funSetGrupoJogos();
                    }

                    If ($this->sorteioErro == 0){
                        $this->sorteioErro = $this->insereQualifAC();
                    }
                }
			}
		}
	}

	/*******************************************************************************************************************/
	/*
	* Nome: insereQualifAC
	* Data: 06/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Função que irá fazer as inserções na base de dados
	* Entrada: Não tem.
	* Saida: Código de erro: 0 se OK, -1 se NOTOK.
	*/

    Function insereQualifAC(){
        $returnCode = 0;
        $procSorteio = new procedimentosSorteio();

        $returnCode = $procSorteio->funInsereOrganizador($this->acAno, $this->acOrganizador, 'tab_afc_ac');

        If ($returnCode == 0){

            $returnCode = $procSorteio->insereCompeticaoActual($this->acFase, $this->acPrefixo.$this->acAno, $this->acOrganizador);

            If ($returnCode == 0){
                For ($i = 0; $i < sizeof($this->acQualifGrupos); $i++){
                    $returnCode = $procSorteio->insereGruposCompeticao($this->acQualifGrupos[$i]);

                    If ($returnCode == 0){
                        $returnCode = $procSorteio->insereJogosCompeticao($this->acQualifGrupos[$i]);
                    }
                }
            }
        }
        Return $returnCode;
    }



	/*******************************************************************************************************************/
	/*
	* Nome: funSetAFCAsianCupAno
	* Data: 30/01/2012
	* Autor: Alexandre M. Carlos
	* Acção: Função que vai determinar qual o Ano para a CAN em questão
	* Entrada: Não tem.
	* Saida: Código de erro: 0 se OK, -1 se NOTOK.
	*/

	Function funSetAFCAsianCupAno(){
		$returnCode = 0;
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			echo "Não é possivel determinar o ano de realização do Euro devido a problemas de ligação com a BD";
			$returnCode = -1;
			Return $returnCode;
		}

		If ($returnCode == 0){
			$varBD->queryString = 'Begin :retVal := package_asia.fun_getacAno(); End;';
            $varBD->parseDBQuery($varBD->queryString);
			oci_bind_by_name($varBD->queryParse, ':retVal', $this->acAno, 50);

			oci_execute($varBD->queryParse);

			$varBD->fechaLigacao();
		}
		Return $returnCode;
	}
	
	/*******************************************************************************************************************/
	/*
	* Nome: funSorteiaOrganizador
	* Data: 31/01/2012
	* Autor: Alexandre M. Carlos
	* Acção: Função que vai fazer a escolha do organizador e actualizar o atributo 'acOrganizador'
	* Entrada: $poteOrganizador - contém todas as selecções elegíveis para organizar a Gold Cup
	* Saida: Não tem (Actualiza um dos atributos do objecto SorteioCONCACAFGCup)
	*/
	Function funSorteiaOrganizador($poteOrganizadores){
		$tamanho = sizeof($poteOrganizadores);
		$rotacoes = rand($tamanho + 1, 100);
		
		for ($i = 0; $i < $rotacoes; $i++){
			shuffle($poteOrganizadores);
		}
		
		$this->acOrganizador = $poteOrganizadores[0];
	}


	/*******************************************************************************************************************/
	/*
	* Nome: funSetAFCAsianCupOrganizador
	* Data: 30/01/2012
	* Autor: Alexandre M. Carlos
	* Acção: Função que vai fazer a escolha das equipas elegíveis para organizar a CAN
	* Entrada: Não tem
	* Saida: Não tem 
	*/
	Function funSetAFCAsianCupOrganizador(){
		$returnCode = 0;
		$poteOrganizadores = array();

		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->connection == null){
			echo "Não é possivel determinar o organizador do Euro devido a problemas de ligação com a BD";
			$returnCode = -1;
			Return $returnCode;
		}
		
		If ($returnCode == 0){
			$varBD->queryString = 'Select tab_equipas_trig
                                   From tab_equipas
                                   Where tab_equipas_conf_rank < 9
                                   And tab_equipas_conf = 1
                                   And tab_equipas_trig not in (select tab_ac_equipa
                                                                from tab_afc_ac
                                                                where tab_ac_rank = 0
                                                                and tab_ac_ano > (to_number(to_char(sysdate, \'YYYY\')) - 7))';
			$varBD->parseDBQuery($varBD->queryString);

			oci_execute($varBD->queryParse);

			While ($row = oci_fetch_array($varBD->queryParse)){
				array_push($poteOrganizadores, $row[0]);
			}

			$this->funSorteiaOrganizador($poteOrganizadores);

			$varBD->fechaLigacao();
		}		
		
		Return $returnCode;
	}


	/*******************************************************************************************************************/
	/*
	* Nome: funInicializaGrupos
	* Data: 31/01/2012
	* Autor: Alexandre M. Carlos
	* Acção: Função que vai fazer a inicialização dos grupos para o sorteio
	* Entrada: Array com o ID de cada um dos grupos
	* Saida: Não tem
	*/	
	Function funInicializaGrupos($euroGrupos){
		
		For($i = 0; $i < sizeof($euroGrupos); $i++){
			$varGrupo = new compGrupos('GRUPO_'.$euroGrupos[$i], $this->acFase, $this->acPrefixo, $this->acAno);
			array_push($this->acQualifGrupos, $varGrupo);
		}
	}	
	
	/*******************************************************************************************************************/
	/*
	* Nome: funSorteiaGrupos
	* Data: 31/01/2012
	* Autor: Alexandre M. Carlos
	* Acção: Função que vai fazer o sorteio dos grupos para a fase de qualificação da CAN
	* Entrada: Array com as equipas ordenadas por ranking da confederação
	* Saida: Não tem 
	*/
	Function funSorteiaGrupos($varEquipas){
        $returnCode = 0;
        $varPotes = array_chunk($varEquipas, 8);

		for ($i = 0; $i < sizeof($varPotes); $i++){
			for ($j= 1; $j < 8; $j++){
				shuffle($varPotes[$i]);
			}
		}


		For ($i = 0; $i < sizeof($varPotes); $i++){
		    $grupoId = 0;
			For($j = 0; $j < sizeof($varPotes[$i]); $j++){
			        $this->acQualifGrupos[$grupoId]->funSetEquipaGrupo($varPotes[$i][$j]);
                    $grupoId++;
			}
		}
        Return $returnCode;
	}

	/*******************************************************************************************************************/
	/*
	* Nome: funSetGrpQualifAC
	* Data: 31/01/2012
	* Autor: Alexandre M. Carlos
	* Acção: Função que vai fazer a recolha dass equipas para o sorteio. Vai chamar funções que vão
	*		 proceder ao sorteio própriamente dito.
	* Entrada: Não tem
	* Saida: Não tem
	*/	
	Function funSetGrpQualifAC(){
		$returnCode = 0;
		$gcGrupos = array ('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H');
		$varEquipas = array();
		
		$this->funInicializaGrupos($gcGrupos);
		
		
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		
		If ($varBD->connection == null){
			echo "Não é possivel sortear os grupos da Gold Cup devido a problemas de ligação com a BD";
			$returnCode = -1;			
		}

		If ($returnCode == 0){
			$varBD->queryString = 'Select tab_equipas_trig
								  From tab_equipas
								  Where tab_equipas_trig <> :ORG
								  And tab_equipas_conf = 1
								  Order by tab_equipas_conf_rank';

			$varBD->parseDBQuery($varBD->queryString);

            oci_bind_by_name($varBD->queryParse, ':ORG', $this->acOrganizador);

			oci_execute($varBD->queryParse);

			While ($row = oci_fetch_array($varBD->queryParse, OCI_NUM)){
                array_push($varEquipas, $row[0]);
			}
			
			$returnCode = $this->funSorteiaGrupos($varEquipas);

			$varBD->fechaLigacao();
		}
		Return $returnCode;
	}	
}

?>