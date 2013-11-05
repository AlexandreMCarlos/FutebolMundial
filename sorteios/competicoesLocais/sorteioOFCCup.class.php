<?php

require_once ('../../classes/executeDBOperations.class.php');
require_once ('../../classes/compGrupos.class.php');
require_once ('../../classes/procedimentosSorteio.class.php');

Class SorteioOFCCup{

	private $ofcOrganizador;
	private $ofcPrefixo = 'OCQ';
    private $ofcFase = 'OCQ_01';
	private $ofcAno;
	private $ofcQualifGrupos = array();
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
        $this->sorteioErro = $this->funSetOFCAno();

		If ($this->sorteioErro == 0){
			$this->sorteioErro = $this->funSetOFCOrganizador();
			If ($this->sorteioErro == 0){
				$this->sorteioErro = $this->funSetGrpQualifOFC();
                If ($this->sorteioErro == 0){
                    For ($i = 0; $i < sizeof($this->ofcQualifGrupos); $i++){
                        $this->sorteioErro = $this->ofcQualifGrupos[$i]->funSetGrupoJogos();
                    }

                    If ($this->sorteioErro == 0){
                        $this->sorteioErro = $this->insereQualifOFC();
                    }
                }
			}
		}
	}

	/*******************************************************************************************************************/
	/*
	* Nome: insereQualifOFC
	* Data: 06/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Função que irá fazer as inserções na base de dados
	* Entrada: Não tem.
	* Saida: Código de erro: 0 se OK, -1 se NOTOK.
	*/

    Function insereQualifOFC(){
        $returnCode = 0;
        $procSorteio = new procedimentosSorteio();


        $returnCode = $procSorteio->funInsereOrganizador($this->ofcAno, $this->ofcOrganizador, 'tab_ofc_cup');

        If ($returnCode == 0){
            $returnCode = $procSorteio->insereCompeticaoActual($this->ofcFase, $this->ofcPrefixo.$this->ofcAno, $this->ofcOrganizador);

            If ($returnCode == 0){
                For ($i = 0; $i < sizeof($this->ofcQualifGrupos); $i++){
                    $returnCode = $procSorteio->insereGruposCompeticao($this->ofcQualifGrupos[$i]);

                    If ($returnCode == 0){
                        $returnCode = $procSorteio->insereJogosCompeticao($this->ofcQualifGrupos[$i]);
                    }
                }
            }
        }
        Return $returnCode;
    }



	/*******************************************************************************************************************/
	/*
	* Nome: funSetOFCAno
	* Data: 30/01/2012
	* Autor: Alexandre M. Carlos
	* Acção: Função que vai determinar qual o Ano para a OFC Cup em questão
	* Entrada: Não tem.
	* Saida: Código de erro: 0 se OK, -1 se NOTOK.
	*/

	Function funSetOFCAno(){
		$returnCode = 0;
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			echo "Não é possivel determinar o ano de realização da OFC Cup devido a problemas de ligação com a BD";
			$returnCode = -1;
			Return $returnCode;
		}

		If ($returnCode == 0){
			$varBD->queryString = 'Begin :retVal := package_oceania.fun_getOFCAno(); End;';
            $varBD->parseDBQuery($varBD->queryString);
			oci_bind_by_name($varBD->queryParse, ':retVal', $this->ofcAno, 50);

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
	* Acção: Função que vai fazer a escolha do organizador e actualizar o atributo 'ofcOrganizador'
	* Entrada: $poteOrganizador - contém todas as selecções elegíveis para organizar a OFC Cup
	* Saida: Não tem (Actualiza um dos atributos do objecto SorteioOFCCup)
	*/
	Function funSorteiaOrganizador($poteOrganizadores){
		$tamanho = sizeof($poteOrganizadores);
		$rotacoes = rand($tamanho + 1, 100);
		
		for ($i = 0; $i < $rotacoes; $i++){
			shuffle($poteOrganizadores);
		}
		
		$this->ofcOrganizador = $poteOrganizadores[0];
	}


	/*******************************************************************************************************************/
	/*
	* Nome: funSetOFCOrganizador
	* Data: 30/01/2012
	* Autor: Alexandre M. Carlos
	* Acção: Função que vai fazer a escolha das equipas elegíveis para organizar a OFC Cup
	* Entrada: Não tem
	* Saida: Não tem 
	*/
	Function funSetOFCOrganizador(){
		$returnCode = 0;
		$poteOrganizadores = array();

		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->connection == null){
			echo "Não é possivel determinar o organizador da OFC Cup devido a problemas de ligação com a BD";
			$returnCode = -1;
			Return $returnCode;
		}
		
		If ($returnCode == 0){
			$varBD->queryString = 'Select tab_equipas_trig
                                   From tab_equipas
                                   Where tab_equipas_conf_rank < 5
                                   And tab_equipas_conf = 5
                                   And tab_equipas_trig not in (select tab_ofc_equipa
                                                                from tab_ofc_cup
                                                                where tab_ofc_rank = 0
                                                                and tab_ofc_ano > (to_number(to_char(sysdate, \'YYYY\')) - 7))';
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
	Function funInicializaGrupos($caGrupos){
		
		For($i = 0; $i < sizeof($caGrupos); $i++){
			$varGrupo = new compGrupos('GRUPO_'.$caGrupos[$i], $this->ofcFase, $this->ofcPrefixo, $this->ofcAno);
			array_push($this->ofcQualifGrupos, $varGrupo);
		}
	}	
	
	/*******************************************************************************************************************/
	/*
	* Nome: funSorteiaGrupos
	* Data: 31/01/2012
	* Autor: Alexandre M. Carlos
	* Acção: Função que vai fazer o sorteio dos grupos para a fase de qualificação da OFC Cup
	* Entrada: Array com as equipas ordenadas por ranking da confederação
	* Saida: Não tem 
	*/
	Function funSorteiaGrupos($varEquipas){
        $returnCode = 0;
        $varPotes = array_chunk($varEquipas, 3);

		for ($i = 0; $i < sizeof($varPotes); $i++){
			for ($j= 1; $j < 3; $j++){
				shuffle($varPotes[$i]);
			}
		}


		For ($i = 0; $i < sizeof($varPotes); $i++){
		    $grupoId = 0;
			For($j = 0; $j < sizeof($varPotes[$i]); $j++){
			        $this->ofcQualifGrupos[$grupoId]->funSetEquipaGrupo($varPotes[$i][$j]);
                    $grupoId++;
			}
		}
        Return $returnCode;
	}

	/*******************************************************************************************************************/
	/*
	* Nome: funSetGrpQualifOFC
	* Data: 31/01/2012
	* Autor: Alexandre M. Carlos
	* Acção: Função que vai fazer a recolha dass equipas para o sorteio. Vai chamar funções que vão
	*		 proceder ao sorteio própriamente dito.
	* Entrada: Não tem
	* Saida: Não tem
	*/	
	Function funSetGrpQualifOFC(){
		$returnCode = 0;
		$caGrupos = array ('A', 'B', 'C');
		$varEquipas = array();

		$this->funInicializaGrupos($caGrupos);
		
		
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		
		If ($varBD->connection == null){
			echo "Não é possivel sortear os grupos da OFC Cup devido a problemas de ligação com a BD";
			$returnCode = -1;			
		}

		If ($returnCode == 0){
			$varBD->queryString = 'Select tab_equipas_trig
								  From tab_equipas
								  Where tab_equipas_trig <> :ORG
								  And tab_equipas_conf = 5
								  Order by tab_equipas_conf_rank';

			$varBD->parseDBQuery($varBD->queryString);

            oci_bind_by_name($varBD->queryParse, ':ORG', $this->ofcOrganizador);

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