<?php
    
class MySQLBDClass{
		
	public $ligacao;
	protected $anfitriao = 'localhost';
	protected $utilizador = 'prduser';
	protected $password = 'prduser.001';
	protected $BDactiva;
	protected $bd = 'FMAppPrd';
	public $resultado;
	
	public function abreLigacao(){
		$this->ligacao = mysql_connect($this->anfitriao, $this->utilizador, $this->password);
		If(!$this->ligacao){
			die("Não é possível ligar à BD. Erro MySQL: ".mysql_error());
		}
	}
	
	public function activaBD(){
		$this->BDactiva = mysql_select_db($this->bd, $this->ligacao);
		If(!$this->BDactiva){
			die ("Não é possível ligar à BD pretendida. Erro MySQL: ".mysql_error());
		}
	}
	
	public function funExecutaQuery($queryBD){
		$this->resultado = mysql_query($queryBD, $this->ligacao);
		If (!$this->resultado){
			die ("Não é possível efectuar a query: ".$queryBD. "Erro: ".mysql_error());
		} 
	}
}
?>	