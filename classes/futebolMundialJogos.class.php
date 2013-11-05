<?php


Class futebolMundialJogos{

    protected $grupoID;
    protected $equipaCasa;
    protected $equipaFora;


	/*******************************************************************************************************************/
	/*
	* Nome: __construct
	* Data: 05/02/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Constructor da classe. Instancia os jogos
	* Entrada: O ID do grupo e ambas as equipas: a que joga em 'casa' e a que joga 'fora'
	* Saida: N�o tem.
	*/
    Function __construct($gID, $eqCasa, $eqFora){
        $this->grupoID = $gID;
        $this->equipaCasa = $eqCasa;
        $this->equipaFora = $eqFora;
    }

    /*******************************************************************************************************************/
	/*
	* Nome: ...
	* Data: 07/02/2012
	* Autor: Alexandre M. Carlos
	* Ac��o: Fun��es que retornam as propriedades do objecto
	* Entrada: N�o tem
	* Saida: As diferentes propriedades do objecto.
	*/
    Public Function fun_getGrupoID(){
        Return $this->grupoID;
    }

    Public Function fun_getEquipaCasa(){
        Return $this->equipaCasa;
    }

    Public Function fun_getEquipaFora(){
        Return $this->equipaFora;
    }


}


?>