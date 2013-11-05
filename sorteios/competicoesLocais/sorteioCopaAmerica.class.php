<?php

require_once ('../../classes/executeDBOperations.class.php');
require_once ('../../classes/compGrupos.class.php');
require_once ('../../classes/procedimentosSorteio.class.php');

Class SorteioCopaAmerica{

	private $caOrganizador;
	private $caPrefixo = 'CAQ';
    private $caFase = 'CAQ_01';
	private $caAno;
	private $caQualifGrupos = array();
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
        $this->sorteioErro = $this->funSetCopaAmericaAno();

		If ($this->sorteioErro == 0){
			$this->sorteioErro = $this->funSetCopaAmericaOrganizador();

			If ($this->sorteioErro == 0){
				$this->sorteioErro = $this->funSetGrpQualifCA();

                If ($this->sorteioErro == 0){

                    For ($i = 0; $i < sizeof($this->caQualifGrupos); $i++){
                        $this->sorteioErro = $this->caQualifGrupos[$i]->funSetGrupoJogos();
                    }

                    If ($this->sorteioErro == 0){
                        $this->sorteioErro = $this->insereQualifCA();
                    }
                }
			}
		}
	}

	/*******************************************************************************************************************/
	/*
	* Nome: insereQualifCA
	* Data: 06/02/2012
	* Autor: Alexandre M. Carlos
	* Acção: Função que irá fazer as inserções na base de dados
	* Entrada: Não tem.
	* Saida: Código de erro: 0 se OK, -1 se NOTOK.
	*/

    Function insereQualifCA(){
        $returnCode = 0;
        $procSorteio = new procedimentosSorteio();

        $returnCode = $procSorteio->funInsereOrganizador($this->caAno, $this->caOrganizador, 'tab_conmebol_ca');

        If ($returnCode == 0){

            $returnCode = $procSorteio->insereCompeticaoActual($this->caFase, $this->caPrefixo.$this->caAno, $this->caOrganizador);

            If ($returnCode == 0){
                For ($i = 0; $i < sizeof($this->caQualifGrupos); $i++){
                    $returnCode = $procSorteio->insereGruposCompeticao($this->caQualifGrupos[$i]);

                    If ($returnCode == 0){
                        $returnCode = $procSorteio->insereJogosCompeticao($this->caQualifGrupos[$i]);
                    }
                }
            }
        }
        Return $returnCode;
    }



	/*******************************************************************************************************************/
	/*
	* Nome: funSetCopaAmericaAno
	* Data: 30/01/2012
	* Autor: Alexandre M. Carlos
	* Acção: Função que vai determinar qual o Ano para a CAN em questão
	* Entrada: Não tem.
	* Saida: Código de erro: 0 se OK, -1 se NOTOK.
	*/

	Function funSetCopaAmericaAno(){
		$returnCode = 0;
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			echo "Não é possivel determinar o ano de realização da Copa América devido a problemas de ligação com a BD";
			$returnCode = -1;
			Return $returnCode;
		}

		If ($returnCode == 0){
			$varBD->queryString = 'Begin :retVal := package_americaSul.fun_getcaAno(); End;';
            $varBD->parseDBQuery($varBD->queryString);
			oci_bind_by_name($varBD->queryParse, ':retVal', $this->caAno, 50);

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
	* Acção: Função que vai fazer a escolha do organizador e actualizar o atributo 'caOrganizador'
	* Entrada: $poteOrganizador - contém todas as selecções elegíveis para organizar a Gold Cup
	* Saida: Não tem (Actualiza um dos atributos do objecto SorteioCONCACAFGCup)
	*/
	Function funSorteiaOrganizador($poteOrganizadores){
		$tamanho = sizeof($poteOrganizadores);
		$rotacoes = rand($tamanho + 1, 100);
		
		for ($i = 0; $i < $rotacoes; $i++){
			shuffle($poteOrganizadores);
		}
		
		$this->caOrganizador = $poteOrganizadores[0];
	}


	/*******************************************************************************************************************/
	/*
	* Nome: funSetCopaAmericaOrganizador
	* Data: 30/01/2012
	* Autor: Alexandre M. Carlos
	* Acção: Função que vai fazer a escolha das equipas elegíveis para organizar a CAN
	* Entrada: Não tem
	* Saida: Não tem 
	*/
	Function funSetCopaAmericaOrganizador(){
		$returnCode = 0;
		$poteOrganizadores = array();

		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->connection == null){
			echo "Não é possivel determinar o organizador da Copa América devido a problemas de ligação com a BD";
			$returnCode = -1;
			Return $returnCode;
		}
		
		If ($returnCode == 0){
			$varBD->queryString = 'Select tab_equipas_trig
                                   From tab_equipas
                                   Where tab_equipas_conf_rank < 9
                                   And tab_equipas_conf = 4
                                   And tab_equipas_trig not in (select tab_ca_equipa
                                                                from tab_conmebol_ca
                                                                where tab_ca_rank = 0
                                                                and tab_ca_ano > (to_number(to_char(sysdate, \'YYYY\')) - 7))';
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
			$varGrupo = new compGrupos('GRUPO_'.$caGrupos[$i], $this->caFase, $this->caPrefixo, $this->caAno);
			array_push($this->caQualifGrupos, $varGrupo);
		}
	}	
	
	/*******************************************************************************************************************/
	/*
	* Nome: funSorteiaGrupos
	* Data: 31/01/2012
	* Autor: Alexandre M. Carlos
	* Acção: Função que vai fazer o sorteio dos grupos para a fase de qualificação da Copa América
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
			        $this->caQualifGrupos[$grupoId]->funSetEquipaGrupo($varPotes[$i][$j]);
                    $grupoId++;
			}
		}
        Return $returnCode;
	}

	/*******************************************************************************************************************/
	/*
	* Nome: funSetGrpQualifCA
	* Data: 31/01/2012
	* Autor: Alexandre M. Carlos
	* Acção: Função que vai fazer a recolha dass equipas para o sorteio. Vai chamar funções que vão
	*		 proceder ao sorteio própriamente dito.
	* Entrada: Não tem
	* Saida: Não tem
	*/	
	Function funSetGrpQualifCA(){
		$returnCode = 0;
		$caGrupos = array ('A', 'B', 'C');
		$varEquipas = array();

		$this->funInicializaGrupos($caGrupos);
		
		
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		
		If ($varBD->connection == null){
			echo "Não é possivel sortear os grupos da Copa América devido a problemas de ligação com a BD";
			$returnCode = -1;			
		}

		If ($returnCode == 0){
			$varBD->queryString = 'Select tab_equipas_trig
								  From tab_equipas
								  Where tab_equipas_trig <> :ORG
								  And tab_equipas_conf = 4
								  Order by tab_equipas_conf_rank';

			$varBD->parseDBQuery($varBD->queryString);

            oci_bind_by_name($varBD->queryParse, ':ORG', $this->caOrganizador);

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