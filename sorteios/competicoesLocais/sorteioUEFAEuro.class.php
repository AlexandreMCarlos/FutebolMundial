<?php

require_once ('../../classes/executeDBOperations.class.php');
require_once ('../../classes/compGrupos.class.php');
require_once ('../../classes/procedimentosSorteio.class.php');

Class SorteioUEFAEuro{

	private $euroOrganizador;
	private $euroPrefixo = 'CEQ';
    private $euroFase = 'CEQ_01';
	private $euroAno;
	private $euroQualifGrupos = array();
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
        $this->sorteioErro = $this->funSetEuroAno();
		
		If ($this->sorteioErro == 0){
			$this->sorteioErro = $this->funSetEuroOrganizador();

            If ($this->sorteioErro == 0){
				$this->sorteioErro = $this->funSetGrpQualifEuro();

                If ($this->sorteioErro == 0){
                    For ($i = 0; $i < sizeof($this->euroQualifGrupos); $i++){
                        $this->sorteioErro = $this->euroQualifGrupos[$i]->funSetGrupoJogos();
                    }

                    If ($this->sorteioErro == 0){
                        $this->sorteioErro = $this->insereQualifEuro();
                    }
                }
			}
        }
	}

	/*******************************************************************************************************************/
	/*
	* Nome: insereQualifEuro
	* Data: 06/02/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Fun��o que ir� fazer as inser��es na base de dados
	* Entrada: N�o tem.
	* Saida: C�digo de erro: 0 se OK, -1 se NOTOK.
	*/

    Function insereQualifEuro(){
        $returnCode = 0;
        $procSorteio = new procedimentosSorteio();

        $returnCode = $procSorteio->funInsereOrganizador($this->euroAno, $this->euroOrganizador, 'tab_uefa_euro');

        If ($returnCode == 0){
            $returnCode = $procSorteio->insereCompeticaoActual($this->euroFase, $this->euroPrefixo.$this->euroAno, $this->euroOrganizador);

            If ($returnCode == 0){
                For ($i = 0; $i < sizeof($this->euroQualifGrupos); $i++){
                    $returnCode = $procSorteio->insereGruposCompeticao($this->euroQualifGrupos[$i]);

                    If ($returnCode == 0){
                        $returnCode = $procSorteio->insereJogosCompeticao($this->euroQualifGrupos[$i]);
                    }
                }
            }
        }
        Return $returnCode;
    }



	/*******************************************************************************************************************/
	/*
	* Nome: funSetEuroAno
	* Data: 30/01/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Fun��o que vai determinar qual o Ano para o Campeonato Europeu em quest�o
	* Entrada: N�o tem.
	* Saida: C�digo de erro: 0 se OK, -1 se NOTOK.
	*/
		
	Function funSetEuroAno(){
		$returnCode = 0;
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			echo "N�o � possivel determinar o ano de realiza��o do Euro devido a problemas de liga��o com a BD";
			$returnCode = -1;
			Return $returnCode;
		}

		If ($returnCode == 0){
			$varBD->queryString = 'Begin :retVal := package_euro.fun_getEuroAno(); End;';
            $varBD->parseDBQuery($varBD->queryString);
			oci_bind_by_name($varBD->queryParse, ':retVal', $this->euroAno, 50);

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
	* Ac��o: Fun��o que vai fazer a escolha do organizador e actualizar o atributo 'euroOrganizador'
	* Entrada: $poteOrganizador - cont�m todas as selec��es eleg�veis para organizar o UEFA Euro
	* Saida: N�o tem (Actualiza um dos atributos do objecto SorteioUEFAEuro)	
	*/
	Function funSorteiaOrganizador($poteOrganizadores){
		$tamanho = sizeof($poteOrganizadores);
		$rotacoes = rand($tamanho + 1, 100);
		
		for ($i = 0; $i < $rotacoes; $i++){
			shuffle($poteOrganizadores);
		}
		
		$this->euroOrganizador = $poteOrganizadores[0];
	}


	/*******************************************************************************************************************/
	/*
	* Nome: funSetEuroOrganizador
	* Data: 30/01/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Fun��o que vai fazer a escolha das equipas eleg�veis para organizar o UEFA Euro
	* Entrada: N�o tem
	* Saida: N�o tem 
	*/
	Function funSetEuroOrganizador(){
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
                                   And tab_equipas_conf = 6
                                   And tab_equipas_trig not in (select tab_euro_equipa
                                                                from tab_uefa_euro
                                                                where tab_euro_rank = 0
                                                                and tab_euro_ano > (to_number(to_char(sysdate, \'YYYY\')) - 7))';
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
			$varGrupo = new compGrupos('GRUPO_'.$euroGrupos[$i], $this->euroFase, $this->euroPrefixo, $this->euroAno);
			array_push($this->euroQualifGrupos, $varGrupo);
		}
	}	
	
	/*******************************************************************************************************************/
	/*
	* Nome: funSorteiaGrupos
	* Data: 31/01/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Fun��o que vai fazer o sorteio dos grupos para a fase de qualifica��o do UEFA Euro	
	* Entrada: Array com as equipas ordenadas por ranking da confedera��o
	* Saida: N�o tem 
	*/
	Function funSorteiaGrupos($varEquipas){

        $varPotes = array_chunk($varEquipas, 10);

		for ($i = 0; $i < sizeof($varPotes); $i++){
			for ($j= 1; $j < 10; $j++){
				shuffle($varPotes[$i]);
			}
		}

		
		For ($i = 0; $i < sizeof($varPotes); $i++){
		    $grupoId = 0;
			For($j = 0; $j < sizeof($varPotes[$i]); $j++){
			        $this->euroQualifGrupos[$grupoId]->funSetEquipaGrupo($varPotes[$i][$j]);
                    $grupoId++;
			}
		}
	}

	/*******************************************************************************************************************/
	/*
	* Nome: funSetGrpQualifEuro
	* Data: 31/01/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Fun��o que vai fazer a recolha dass equipas para o sorteio. Vai chamar fun��es que v�o
	*		 proceder ao sorteio pr�priamente dito.
	* Entrada: N�o tem
	* Saida: N�o tem
	*/	
	Function funSetGrpQualifEuro(){
		$returnCode = 0;
		$euroGrupos = array ('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
		$varEquipas = array();
		
		$this->funInicializaGrupos($euroGrupos);
		
		
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
								  And tab_equipas_conf = 6
								  Order by tab_equipas_conf_rank';

			$varBD->parseDBQuery($varBD->queryString);

            oci_bind_by_name($varBD->queryParse, ':ORG', $this->euroOrganizador);

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