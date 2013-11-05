<?php

require_once ('../../classes/executeDBOperations.class.php');
require_once ('../../classes/compGrupos.class.php');
require_once ('../../classes/procedimentosSorteio.class.php');

Class SorteioCANAfrica{

	private $canOrganizador;
	private $canPrefixo = 'CNQ';
    private $canFase = 'CNQ_01';
	private $canAno;
	private $canQualifGrupos = array();
	private $sorteioErro;
	
	

	/*******************************************************************************************************************/
	/*
	* Nome: __construct
	* Data: 31/01/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Constructor da classe. Efectua o sorteio.
	* Entrada: N�o tem.
	* Saida: N�o tem.
	*/
	Function __construct(){
        echo "Vou come�ar.\n";
        $this->sorteioErro = $this->funSetCanAno();
		
		If ($this->sorteioErro == 0){
			$this->sorteioErro = $this->funSetCanOrganizador();

			If ($this->sorteioErro == 0){
				$this->sorteioErro = $this->funSetGrpQualifCAN();

                If ($this->sorteioErro == 0){
                    For ($i = 0; $i < sizeof($this->canQualifGrupos); $i++){
                        $this->sorteioErro = $this->canQualifGrupos[$i]->funSetGrupoJogos();
                    }

                    If ($this->sorteioErro == 0){
                        $this->sorteioErro = $this->insereQualifCan();
                    }
                }
			}
		}
	}

	/*******************************************************************************************************************/
	/*
	* Nome: insereQualifCan
	* Data: 06/02/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Fun��o que ir� fazer as inser��es na base de dados
	* Entrada: N�o tem.
	* Saida: C�digo de erro: 0 se OK, -1 se NOTOK.
	*/

    Function insereQualifCan(){
        $returnCode = 0;
        $procSorteio = new procedimentosSorteio();

        $returnCode = $procSorteio->funInsereOrganizador($this->canAno, $this->canOrganizador, 'tab_caf_can');

        If ($returnCode == 0){

            $returnCode = $procSorteio->insereCompeticaoActual($this->canFase, $this->canPrefixo.$this->canAno, $this->canOrganizador);

            If ($returnCode == 0){
                For ($i = 0; $i < sizeof($this->canQualifGrupos); $i++){
                    $returnCode = $procSorteio->insereGruposCompeticao($this->canQualifGrupos[$i]);

                    If ($returnCode == 0){
                        $returnCode = $procSorteio->insereJogosCompeticao($this->canQualifGrupos[$i]);
                    }
                }
            }
        }
        Return $returnCode;
    }



	/*******************************************************************************************************************/
	/*
	* Nome: funSetCanAno
	* Data: 30/01/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Fun��o que vai determinar qual o Ano para a CAN em quest�o
	* Entrada: N�o tem.
	* Saida: C�digo de erro: 0 se OK, -1 se NOTOK.
	*/
		
	Function funSetCanAno(){
		$returnCode = 0;
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			echo "N�o � possivel determinar o ano de realiza��o do Euro devido a problemas de liga��o com a BD";
			$returnCode = -1;
			Return $returnCode;
		}

		If ($returnCode == 0){
			$varBD->queryString = 'Begin :retVal := package_africa.fun_getCANAno(); End;';
            $varBD->parseDBQuery($varBD->queryString);
			oci_bind_by_name($varBD->queryParse, ':retVal', $this->canAno, 50);

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
	* Ac��o: Fun��o que vai fazer a escolha do organizador e actualizar o atributo 'canOrganizador'
	* Entrada: $poteOrganizador - cont�m todas as selec��es eleg�veis para organizar a CAN
	* Saida: N�o tem (Actualiza um dos atributos do objecto SorteioCANAfrica)	
	*/
	Function funSorteiaOrganizador($poteOrganizadores){
		$tamanho = sizeof($poteOrganizadores);
		$rotacoes = rand($tamanho + 1, 100);
		
		for ($i = 0; $i < $rotacoes; $i++){
			shuffle($poteOrganizadores);
		}
		
		$this->canOrganizador = $poteOrganizadores[0];
	}


	/*******************************************************************************************************************/
	/*
	* Nome: funSetCanOrganizador
	* Data: 30/01/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Fun��o que vai fazer a escolha das equipas eleg�veis para organizar a CAN
	* Entrada: N�o tem
	* Saida: N�o tem 
	*/
	Function funSetCanOrganizador(){
		$returnCode = 0;
		$poteOrganizadores = array();
		
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		
		If ($varBD->connection == null){
			echo "N�o � possivel determinar o organizador do Euro devido a problemas de liga��o com a BD";
			$returnCode = -1;
			Return $returnCode;
		}
		
		If ($returnCode == 0){
			$varBD->queryString = 'Select tab_equipas_trig
                                   From tab_equipas
                                   Where tab_equipas_conf_rank < 9
                                   And tab_equipas_conf = 2
                                   And tab_equipas_trig not in (select tab_can_equipa
                                                                from tab_caf_can
                                                                where tab_can_rank = 0
                                                                and tab_can_ano > (to_number(to_char(sysdate, \'YYYY\')) - 7))';
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
	* Ac��o: Fun��o que vai fazer a inicializa��o dos grupos para o sorteio
	* Entrada: Array com o ID de cada um dos grupos
	* Saida: N�o tem
	*/	
	Function funInicializaGrupos($euroGrupos){
		
		For($i = 0; $i < sizeof($euroGrupos); $i++){
			$varGrupo = new compGrupos('GRUPO_'.$euroGrupos[$i], $this->canFase, $this->canPrefixo, $this->canAno);
			array_push($this->canQualifGrupos, $varGrupo);
		}
	}	
	
	/*******************************************************************************************************************/
	/*
	* Nome: funSorteiaGrupos
	* Data: 31/01/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Fun��o que vai fazer o sorteio dos grupos para a fase de qualifica��o da CAN
	* Entrada: Array com as equipas ordenadas por ranking da confedera��o
	* Saida: N�o tem 
	*/
	Function funSorteiaGrupos($varEquipas){

        $varPotes = array_chunk($varEquipas, 7);

		for ($i = 0; $i < sizeof($varPotes); $i++){
			for ($j= 1; $j < 7; $j++){
				shuffle($varPotes[$i]);
			}
		}

		
		For ($i = 0; $i < sizeof($varPotes); $i++){
		    $grupoId = 0;
			For($j = 0; $j < sizeof($varPotes[$i]); $j++){
			        $this->canQualifGrupos[$grupoId]->funSetEquipaGrupo($varPotes[$i][$j]);
                    $grupoId++;
			}
		}
	}

	/*******************************************************************************************************************/
	/*
	* Nome: funSetGrpQualifCAN
	* Data: 31/01/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Fun��o que vai fazer a recolha dass equipas para o sorteio. Vai chamar fun��es que v�o
	*		 proceder ao sorteio pr�priamente dito.
	* Entrada: N�o tem
	* Saida: N�o tem
	*/	
	Function funSetGrpQualifCAN(){
		$returnCode = 0;
		$canGrupos = array ('A', 'B', 'C', 'D', 'E', 'F', 'G');
		$varEquipas = array();
		
		$this->funInicializaGrupos($canGrupos);
		
		
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();
		
		If ($varBD->connection == null){
			echo "N�o � possivel sortear os grupos do Euro devido a problemas de liga��o com a BD";
			$returnCode = -1;			
		}

		If ($returnCode == 0){
			$varBD->queryString = 'Select tab_equipas_trig
								  From tab_equipas
								  Where tab_equipas_trig <> :ORG
								  And tab_equipas_conf = 2
								  Order by tab_equipas_conf_rank';

			$varBD->parseDBQuery($varBD->queryString);

            oci_bind_by_name($varBD->queryParse, ':ORG', $this->canOrganizador);

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