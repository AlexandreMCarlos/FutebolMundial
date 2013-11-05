<?php

Class ExecuteDBOperations{

	public $connection;
	public $queryString = null;
	/*public $queryOutParams = array();
	public $queryInParams = array();
	public $resultArray = array();*/
	public $queryErro;
	public $queryParse;

	function abreLigacao(){
		/*$this->connection = oci_connect('fmprd','fmprd.001','localhost/XE');*/	
		$this->connection = oci_connect('fmdev','fmdev.001','localhost/XE');
		If (!$this->connection){
			//A liga��o n�o foi bem sucedida
			$this->connection = null;
			exit("Não foi possível estabelecer a ligação á BD.");
		}
	}
	
	function fechaLigacao(){
		$var_aux_connect = oci_close($this->connection);
		If (!$var_aux_connect){
			echo "N�o foi poss�vel fechar a liga��o � BD.\n";
			exit();			
		}
	}
	
	function parseDBQuery($dbQuery){
		$this->queryParse = oci_parse($this->connection, $dbQuery);
	}

    function funGetConnection(){
        return $this->connection;
    }
	
}

?>