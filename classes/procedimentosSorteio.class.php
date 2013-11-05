<?php


Class procedimentosSorteio{

	/*******************************************************************************************************************/
	/*
	* Nome: insereCompeticaoActual
	* Data: 06/02/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Vai inserir/ dar inicio � competi��o actual
	* Entrada: $fase: 'MFF_01', $tipo: 'MFF2012', $org: Organizador
	* Saida: C�digo de erro. 0 OK, -1 NOK
	*/
    Function insereCompeticaoActual($faseComp, $tipoComp, $orgComp){
		$returnCode = 0;
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			exit();
		}

		//echo "Entrei\n";
		//echo  $faseComp."\n";
		//echo  $tipoComp."\n";
		$varBD->queryString = 'Begin :retVal := package_competicoes.fun_iniciaCompActual(:FASE, :TIPO, :ORG); End;';
        $varBD->parseDBQuery($varBD->queryString);
		oci_bind_by_name($varBD->queryParse, ':retVal', $returnCode, 50);
		oci_bind_by_name($varBD->queryParse, ':FASE', $faseComp);
		oci_bind_by_name($varBD->queryParse, ':TIPO', $tipoComp);
		oci_bind_by_name($varBD->queryParse, ':ORG', $orgComp);

		oci_execute($varBD->queryParse);

		$varBD->fechaLigacao();
		unset($varBD);
		//echo $returnCode."\n";
		Return $returnCode;
    }
	
    public function fechaCompeticao($faseComp, $tipoComp){
		$returnCode = 0;
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			unset($varBD);
			exit();
		}


		$varBD->queryString = 'Begin :retVal := package_competicoes.funFechaCompeticao(:FASE, :TIPO); End;';
        $varBD->parseDBQuery($varBD->queryString);
		oci_bind_by_name($varBD->queryParse, ':retVal', $returnCode, 50);
		oci_bind_by_name($varBD->queryParse, ':FASE', $faseComp);
		oci_bind_by_name($varBD->queryParse, ':TIPO', $tipoComp);

		oci_execute($varBD->queryParse);

		$varBD->fechaLigacao();
		unset($varBD);

		Return $returnCode;
    }	

	/*******************************************************************************************************************/
	/*
	* Nome: insereGruposCompeticao
	* Data: 06/02/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Vai inserir os grupos da competi��o
	* Entrada: $dadosGrupo: Grupo a inserir
	* Saida: C�digo de erro. 0 OK, -1 NOK
	*/
    Function insereGruposCompeticao($dadosGrupo){
		$returnCode = 0;
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			$returnCode = -1;
			Return $returnCode;
		}

		If ($returnCode == 0){
            $varID = $dadosGrupo->fun_getCompID();
            $varFase = $dadosGrupo->fun_getCompFase();
            $varGrupoID = $dadosGrupo->fun_getGrupoID();
            $varEquipas = array();
            $dadosGrupo->fun_getEquipasGrupo($varEquipas);

		    $varGrupo = $varID."_".$varFase."_".$varGrupoID;

            $varBD->queryString = 'Begin :retVal := package_grupos.fun_insereGrupos(:GRUPO, :GID, :ELEMS); End;';
            $varBD->parseDBQuery($varBD->queryString);


			oci_bind_by_name($varBD->queryParse, ':retVal', $returnCode, 50);
            oci_bind_array_by_name($varBD->queryParse, ':GRUPO', $varEquipas, sizeof($varEquipas), -1, SQLT_CHR);
            oci_bind_by_name($varBD->queryParse, ':GID', $varGrupo);
            oci_bind_by_name($varBD->queryParse, ':ELEMS', sizeof($varEquipas));

            oci_execute($varBD->queryParse);

			$varBD->fechaLigacao();
			unset($verBD);
		}
		Return $returnCode;
    }


	/*******************************************************************************************************************/
	/*
	* Nome: insereJogosCompeticao
	* Data: 07/02/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Vai inserir os Jogos da competi��o
	* Entrada: $dadosGrupo: Grupo com os jogos a inserir
	* Saida: C�digo de erro. 0 OK, -1 NOK
	*/
    Function insereJogosCompeticao($dadosGrupo){
		$returnCode = 0;
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			$returnCode = -1;
			Return $returnCode;
		}

		If ($returnCode == 0){

            $varJogos = array();
            $dadosGrupo->fun_getEquipasJogosGrupo($varJogos);

            For ($arrayJogos = 0; $arrayJogos < sizeof($varJogos); $arrayJogos++){
                $varGrupoID = $varJogos[$arrayJogos]->fun_getGrupoID();
                $eqCasa = $varJogos[$arrayJogos]->fun_getEquipaCasa();
                $eqFora = $varJogos[$arrayJogos]->fun_getEquipaFora();

                $varBD->queryString = 'Begin :retVal := package_jogos.funInsereJogosGrupo(:GRUPO, :CASA, :FORA); End;';
                $varBD->parseDBQuery($varBD->queryString);

    			oci_bind_by_name($varBD->queryParse, ':retVal', $returnCode, 50);
                oci_bind_by_name($varBD->queryParse, ':GRUPO', $varGrupoID);
                oci_bind_by_name($varBD->queryParse, ':CASA', $eqCasa);
                oci_bind_by_name($varBD->queryParse, ':FORA', $eqFora);

                oci_execute($varBD->queryParse);
            }
			$varBD->fechaLigacao();
			unset($varBD);
		}
		Return $returnCode;
    }

	/*******************************************************************************************************************/
	/*
	* Nome: funInsereOrganizador
	* Data: 23/02/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Vai inserir o organizador na competi��o
	* Entrada: $anoComp - Ano da competi��o
    *          $compOrg - Organizador
    *          $tabela - Tabela onde vai ser feita a inser��o
	* Saida: C�digo de erro. 0 OK, -1 NOK
	*/
    Function funInsereOrganizador($compAno, $compOrg, $tabela){
		$returnCode = 1;
		$varBD = new ExecuteDBOperations();
		$varBD->abreLigacao();

		If ($varBD->funGetConnection() == null){
			$returnCode = 0;
			Return $returnCode;
		}

		If ($returnCode){			
			
			if ($tabela == 'tab_fifa_wc'){
				$varBD->queryString = 'Begin :retVal := package_competicoes.funInsOrgMundial(:ORG, :ANO); End;';
			}
			
            $varBD->parseDBQuery($varBD->queryString);

            oci_bind_by_name($varBD->queryParse, ':retVal', $returnCode, 50);
            oci_bind_by_name($varBD->queryParse, ':ORG', $compOrg);
            oci_bind_by_name($varBD->queryParse, ':ANO', $compAno);

            oci_execute($varBD->queryParse);

			$varBD->fechaLigacao();
			unset($varBD);
		}
		Return $returnCode;
    }
}


?>