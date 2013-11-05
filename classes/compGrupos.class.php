<?php

require_once ('futebolMundialJogos.class.php');

Class compGrupos{

	private $compFase; //Na forma de MFF2012
	private $compID; //Na forma de MFF_01
	private $grupoID; //Na forma de GRUPO_A
	public $equipasGrupo = array();
	private $grupoElemsTotal;
	private $grupoJogos = array();
	

	/*******************************************************************************************************************/
	/*
	* Nome: ...
	* Data: 31/01/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Fun��es que retornam as propriedades do objecto
	* Entrada: N�o tem
	* Saida: As diferentes propriedades do objecto.
	*/

	Function funGetElementosGrupo($indice){
		If (isset($this->equipasGrupo[$indice]))	
			Return $this->equipasGrupo[$indice];
		else
			Return null;
	}

	
	Function funGetTotalElementosGrupo(){
		Return $this->grupoElemsTotal;
	}

	Function funGetGrupoID(){
		Return $this->compID."_".$this->compFase."_".$this->grupoID;
	}

    Function fun_getCompFase(){
        Return $this->compFase;
    }

    Function fun_getCompID(){
        Return $this->compID;
    }

    Function fun_getGrupoID(){
        Return $this->grupoID;
    }

    Function fun_getEquipasGrupo(&$arrayGrupo){
        For ($i = 0; $i < sizeof($this->equipasGrupo); $i++){
            array_push($arrayGrupo, $this->equipasGrupo[$i]);
        }
    }

    Function fun_getEquipasJogosGrupo(&$arrayJogos){
        For ($i = 0; $i < sizeof($this->grupoJogos); $i++){
            array_push($arrayJogos, $this->grupoJogos[$i]);
        }
    }


	/*******************************************************************************************************************/
	/*
	* Nome: __construct
	* Data: 31/01/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Constructor da classe. Gera o grupo.
	* Entrada: Id do grupo, ID da competi��o, fase a que corresponde o grupo, ano da competi��o.
	* Saida: N�o tem.
	*/
	Function __construct($id, $cID, $fase, $ano){
		$this->compFase = $fase.$ano;
		$this->compID = $cID;
		$this->grupoID = $id;
	}


	/*******************************************************************************************************************/
	/*
	* Nome: funSetEquipaGrupo
	* Data: 31/01/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Insere uma equipa num grupo
	* Entrada: A equipa a inserir
	* Saida: N�o tem.
	*/
    Function funSetEquipaGrupo($team){
        array_push($this->equipasGrupo, $team);
    }



	/*******************************************************************************************************************/
	/*
	* Nome: funSetGrupoElems
	* Data: 04/02/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Insere na vari�vel respectiva o n�mero de elementos do grupo
	* Entrada: N�o tem.
	* Saida: N�o tem.
	*/
    Function funSetGrupoElems(){
        $this->grupoElemsTotal = sizeof($this->equipasGrupo);
    }

	/*******************************************************************************************************************/
	/*
	* Nome: funGetGrupoJogos
	* Data: 05/02/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Associa ao grupo os jogos respectivos
	* Entrada: N�o tem.
	* Saida: C�digo de erro.
	*/
    Function funGetGrupoJogos(){
        $returnCode = 0;

		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			$returnCode = -1;
			Return $returnCode;
		}

		If ($returnCode == 0){
			
			If (substr($this->compID, 0, 3) == 'CMF')
				$elemsGrupo = $this->grupoElemsTotal + 100;
			Else
				$elemsGrupo = $this->grupoElemsTotal; 
			

			$varBD->queryString = 'Select tab_esc_eqcasa, tab_esc_eqfora
                                   From tab_escalonamento
                                   Where tab_esc_elems = :ELEMS';
            $varBD->parseDBQuery($varBD->queryString);
            oci_bind_by_name($varBD->queryParse, ':ELEMS', $elemsGrupo);

			oci_execute($varBD->queryParse);

            While ($row = oci_fetch_array($varBD->queryParse)){
				$grupoJogo = new futebolMundialJogos($this->compID."_".$this->compFase."_".$this->grupoID, $row[0], $row[1]);
                array_push($this->grupoJogos, $grupoJogo);
			}
        }

        Return $returnCode;

    }

	/*******************************************************************************************************************/
	/*
	* Nome: funSetGrupoJogos
	* Data: 04/02/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Trata de gerar os jogos para os grupos em quest�o
	* Entrada: N�o tem.
	* Saida: C�digo de erro.
	*/
	Function funSetGrupoJogos(){
        $returnCode = 0;
        $commitValue = 1;

        $this->funSetGrupoElems();

		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			$returnCode = -1;
			Return $returnCode;
		}

		If ($returnCode == 0){

			$varBD->queryString = 'Begin :retVal := package_jogos.fun_escalonaJogosGrupo(:GRUPO, :ELEMS, :COMMIT); End;';
            $varBD->parseDBQuery($varBD->queryString);

			oci_bind_by_name($varBD->queryParse, ':retVal', $returnCode, 50);
            oci_bind_array_by_name($varBD->queryParse, ':GRUPO', $this->equipasGrupo, $this->grupoElemsTotal+1, -1, SQLT_CHR);
            oci_bind_by_name($varBD->queryParse, ':ELEMS', $this->grupoElemsTotal);
            oci_bind_by_name($varBD->queryParse, ':COMMIT', $commitValue);

			oci_execute($varBD->queryParse);

			$varBD->fechaLigacao();

            $returnCode = $this->funGetGrupoJogos();
		}
		Return $returnCode;
	}


	Function funSetGrupoJogosMundial($faseMundial){
        $returnCode = 0;
        $commitValue = 1;

		$this->funSetGrupoElems();

		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			$returnCode = -1;
			Return $returnCode;
		}

		If ($returnCode == 0){
			$varBD->queryString = 'Begin :retVal := package_jogos.funEscalonaJogosGrupoMundial(:GRUPO, :ELEMS, :FASE, :COMMIT); End;';
            $varBD->parseDBQuery($varBD->queryString);

			oci_bind_by_name($varBD->queryParse, ':retVal', $returnCode, 50);
            oci_bind_array_by_name($varBD->queryParse, ':GRUPO', $this->equipasGrupo, $this->grupoElemsTotal+1, -1, SQLT_CHR);
            oci_bind_by_name($varBD->queryParse, ':ELEMS', $this->grupoElemsTotal);
			oci_bind_by_name($varBD->queryParse, ':FASE', $this->compID);
            oci_bind_by_name($varBD->queryParse, ':COMMIT', $commitValue);

			oci_execute($varBD->queryParse);

			$varBD->fechaLigacao();

            $returnCode = $this->funGetGrupoJogos();
		}
		Return $returnCode;
	}



	

	

}

?>