<?php
/*
Ficheiro: SorteioMundialFaseFinal
DataCria��o: 15/02/2011 Vers�oCria��o: 000.000.001 Observa��es: Fun_set_organizador - Vai determinar qual o pa�s organizador do mundial
DataCria��o: 23/02/2011 Vers�oCria��o: 000.000.002 Observa��es: Sorteios dos niveis 1 e 2
DataCria��o: 27/02/2011 Vers�oCria��o: 000.000.003 Observa��es: Sorteios dos niveis 3 e 4; Inser��o na BD
Autor: Alexandre M. Carlos (Copyright 2011)
*/

//Includes e Include_once
include_once "connect.php";
//include_once "C:/Apache/htdocs/php/includes/connect.php";

//Fun��es
//Nome: fun_set_organizador
//Parametros: Entrada Vari�vel contendo a liga��o � BD e o ano a que se refere o sorteio
//Descri��o: Determina qual a selec��o que ir� organizar o mundial. Das 32 selec��es ir� sortear de
//          entre as 10 mais bem classificadas do ranking � altura do sorteio e que n�o tenham
//          organizado nenhum dos �ltimos 7 mundiais.
//Retorno: O c�digo de equipa da selec��o escolhida como organizadora.
function fun_set_organizador($conn, $ano){
    $retCode = 0;
    $orgs = array();

    print $ano."\n";

    //Vai ordenar o ranking para que possa ser feita a escolha do organizador e o sorteio
    $stid = oci_parse($conn, 'Begin :RCODE := Pack_t_mundial.fun_set_cm_ranking(:p_ano); End;');
    oci_bind_by_name($stid, ':RCODE', $retCode, 3);
    oci_bind_by_name($stid, ':p_ano', $ano);

    oci_execute($stid);

    //print $retCode."\n";
    If ($retCode == 0){
        //Vai determinar qual o organizador
        $stid =  oci_parse ($conn, 'Select t_mundial_equipa from t_mundial
             where t_mundial_ano = :p_ano
             and t_mundial_rank <= 15
             and t_mundial_equipa not in (select t_comp_organizador
                                          from t_tipo_competicao
                                          where t_comp_id = \'MFF_00\'
                                          and to_number(substr(t_comp_tipo, 4, length(t_comp_tipo))) >=
                                            (select max(to_number(substr(t_comp_tipo, 4, length(t_comp_tipo))))-6
                                             from t_tipo_competicao where t_comp_id = \'MFF_00\'))');

        oci_bind_by_name($stid, ':p_ano', $ano);

        oci_execute($stid);

        while ($row = oci_fetch_array($stid, OCI_NUM)) {
            foreach ($row as $item){
                array_push($orgs, $item);
            }
        }

        $maxShuffle = rand(0, 50);
        for ($randTimes = 0; $randTimes <= $maxShuffle; $randTimes++){
            shuffle($orgs);
        }
        //print $maxShuffle."\n";
        //print $orgs[0]."\n";

        //Vai colocar o pais sorteado � frente no ranking para que seja sorteado com ocabe�a de s�rie
        $stid = oci_parse($conn, 'Begin :RCODE := Pack_t_mundial.fun_set_organizador(:org, :p_ano); End;');
        oci_bind_by_name($stid, ':RCODE', $retCode, 3);
        oci_bind_by_name($stid, ':org', $orgs[0]);
        oci_bind_by_name($stid, ':p_ano', $ano);

        oci_execute($stid);
    }
    return $orgs[0];
}

//Nome: fun_exists_mundial
//Parametros: Entrada Vari�vel contendo a liga��o � BD; Ano a que se refere o sorteio
//Descri��o: Ir� verificar se � possivel fazer o sorteio, ou seja, se a fase de qualifica��o
//           j� terminou e se o mesmo mundial n�o se encontra j� sorteado
//Retorno: 0 se � possivel fazer o sorteio; -1 caso contr�rio
Function fun_exists_mundial($conn, $ano){

    $retCode = 0;
    //Vai verificar se j� existe um sorteio feito para o ano em quest�o
    $stid = oci_parse($conn, 'select count(*) from t_mundial_ano
                              where t_ano_mundial = :p_ano
                              and ((t_mundial_inic = 1 and t_mundial_fim = 1) or (t_mundial_inic = 1 and t_mundial_fim = 0))');

    oci_bind_by_name($stid, ':p_ano', $ano);
    oci_execute($stid);

    $existe = oci_fetch_array($stid, OCI_NUM);

    //Vai verificar se j� est�o 32 equipas apuradas
    $stid = oci_parse($conn, 'select count(*) from t_mundial
                              where t_mundial_ano = :p_ano');

    oci_bind_by_name($stid, ':p_ano', $ano);
    oci_execute($stid);

    $equipas = oci_fetch_array($stid, OCI_NUM);

    if ($existe != 0 || $equipas < 32){
      $retcode = -1; //NOTOK
    }

    return $retCode;
}

//Nome: fun_get_ano
//Parametros: Entrada Vari�vel contendo a liga��o � BD;
//Descri��o: Ir� determinar a que ano se refere o sorteio. Para isso vai verificar qual o �ltimo
//           mundial feito e somar 1.
//Retorno: O ano a que se refere o sorteio
Function fun_get_ano($conn){

    //Vai verificar para que ano est� a ser feito o sorteio
    $stid = oci_parse($conn, 'Select Max(t_ano_mundial) From t_mundial_ano
                            Where t_mundial_inic = 1 And t_mundial_fim = 1');
    oci_execute($stid);

    $ano_mundial = oci_fetch_array($stid, OCI_NUM);
    $ano_mundial[0]++;

    return $ano_mundial[0];
}

Function fun_set_insere_prim_fase($grupos, $conn, $organizador, $ano){
    $retCode = 0;

    $v_titulo_comp = 'Campeonato Mundial de Futebol '.$ano.' - Fase Final';
    $v_abrev_comp = 'MFF'.$ano;
    $v_comp_id = 'MFF_00';

    $stid = oci_parse($conn, 'Insert Into T_TIPO_COMPETICAO Values (SEQ_T_TIPO_COMPETICAO.NEXTVAL, :COMP_ID, :V_TIT_COMP, :ORG, :COMP_TIPO, 0)');
    oci_bind_by_name($stid, ':COMP_ID', $v_comp_id);
    oci_bind_by_name($stid, ':V_TIT_COMP', $v_titulo_comp);
    oci_bind_by_name($stid, ':COMP_TIPO', $v_abrev_comp);
    oci_bind_by_name($stid, ':ORG', $organizador);

    oci_execute($stid);

    $v_titulo_comp = 'Campeonato Mundial de Futebol '.$ano.' - Fase de Grupos';
    $v_abrev_comp = 'MFF'.$ano;
    $v_comp_id = 'MFF_01';

    // DADOS DOS GRUPOS
    for ($i = 0; $i <= count($grupos)-1; $i++){
        $groups = null;
        for ($j = 1; $j <= count($grupos[$i])-1; $j++){
            if ($j < count($grupos[$i])-1){
                $groups = $groups.$grupos[$i][$j].'-';
            }else{
                $groups = $groups.$grupos[$i][$j];
            }
        }
        $v_group_id =  $grupos[$i][0];
        echo $v_group_id." - ";
        echo $groups."\n";

        $stid = oci_parse($conn, 'Begin PACK_T_GRUPO.CRIA_GRUPO(:COMP_ID, :COMP_TIPO, :NOME_GRUPO, :EQUIPAS_GRUPO); End;');
        oci_bind_by_name($stid, ':COMP_ID', $v_comp_id);
        oci_bind_by_name($stid, ':COMP_TIPO', $v_abrev_comp);
        oci_bind_by_name($stid, ':NOME_GRUPO', $v_group_id, strlen($v_group_id));
        oci_bind_by_name($stid, ':EQUIPAS_GRUPO', $groups, strlen($groups));

        oci_execute($stid);
    }

    $stid = oci_parse($conn, 'Insert Into T_TIPO_COMPETICAO Values (SEQ_T_TIPO_COMPETICAO.NEXTVAL, :COMP_ID, :V_TIT_COMP, :ORG, :COMP_TIPO, 0)');
    oci_bind_by_name($stid, ':COMP_ID', $v_comp_id);
    oci_bind_by_name($stid, ':V_TIT_COMP', $v_titulo_comp);
    oci_bind_by_name($stid, ':COMP_TIPO', $v_abrev_comp);
    oci_bind_by_name($stid, ':ORG', $organizador);

    oci_execute($stid);

    return $retCode;
}

Function fun_set_insere_oitavos($conn, $ano, $organizador){
    $retCode = 0;
    $v_abrev_comp = 'MFF'.$ano;
    $v_comp_id = 'MFF_01';
    $oitavos_venc = array();
    $oitavos_seg = array();
    $oitavos = array();
    $v_commit = 1;

    $v_group_prefix =  $v_comp_id."_".$v_abrev_comp;

    $stid = oci_parse($conn, 'select distinct(t_grupo_id) from t_grupo where substr (t_grupo_id, 0, 14) = :GROUP_PREFIX  order by 1');
    oci_bind_by_name($stid, ':GROUP_PREFIX', $v_group_prefix);
    oci_execute($stid);

    while ($row = oci_fetch_array($stid, OCI_NUM)) {
        foreach ($row as $item){
            array_push($oitavos_venc, $item);
        }
    }
    // Variavel que vai conter o n�mero de equipas que existem antes de se proceder ao desdobramento entre 1�s e 2�s classificados
    $static_count = count($oitavos_venc);

    for ($i = 0; $i <= $static_count-1; $i++){
        $j = $oitavos_venc[$i]."_CLASS_2";
        $oitavos_venc[$i] = $oitavos_venc[$i]."_CLASS_1";
        array_push($oitavos_seg, $j);
        echo $oitavos_venc[$i]."\n";
        echo $oitavos_seg[$i]."\n";
    }

    $oitavos_seg = array_reverse($oitavos_seg);

    for ($i = 0; $i <= $static_count-1; $i++){
        array_push($oitavos, $oitavos_venc[$i], $oitavos_seg[$i]);
    }

    $v_titulo_comp = 'Campeonato Mundial de Futebol '.$ano.' - Oitavos de Final';
    $v_abrev_comp = 'MFF'.$ano;
    $v_comp_id = 'MFF_02';

    $stid = oci_parse($conn, 'Insert Into T_TIPO_COMPETICAO Values (SEQ_T_TIPO_COMPETICAO.NEXTVAL, :COMP_ID, :V_TIT_COMP, :ORG, :COMP_TIPO, 0)');
    oci_bind_by_name($stid, ':COMP_ID', $v_comp_id);
    oci_bind_by_name($stid, ':V_TIT_COMP', $v_titulo_comp);
    oci_bind_by_name($stid, ':COMP_TIPO', $v_abrev_comp);
    oci_bind_by_name($stid, ':ORG', $organizador);

    oci_execute($stid);

    $jogo_num = 1;
    $jogo_id = 1;
    for ($i = 0; $i <= ($static_count * 2)-1; $i+=2){
        $v_jogo = $oitavos[$i]."-".$oitavos[$i+1];
        //echo $v_jogo."\n";
        $v_eliminatoria = "JOGO_".$jogo_id;

        $stid = oci_parse($conn, 'Begin PACK_T_ELIMINATORIA.Cria_Eliminatoria(:COMP_ID, :COMP_TIPO, :NOME_GRUPO, :EQUIPAS_GRUPO, :COMM); End;');
        oci_bind_by_name($stid, ':COMP_ID', $v_comp_id);
        oci_bind_by_name($stid, ':COMP_TIPO', $v_abrev_comp);
        oci_bind_by_name($stid, ':NOME_GRUPO', $v_eliminatoria, strlen($v_eliminatoria));
        oci_bind_by_name($stid, ':EQUIPAS_GRUPO', $v_jogo, strlen($v_jogo));
        oci_bind_by_name($stid, ':COMM', $v_commit);

        oci_execute($stid);

        $jogo_num -= 1;
        $jogo_id += 1;
    }
    return $retCode;
}

Function fun_set_insere_quartos($conn, $ano, $organizador){
    $quartos = array();
    $retCode = 0;
    $v_abrev_comp = 'MFF'.$ano;
    $v_comp_id = 'MFF_02';
    $v_commit = 1;

    $v_group_prefix =  $v_comp_id."_".$v_abrev_comp;

    $stid = oci_parse($conn, 'select distinct(t_eliminatoria_id) from t_eliminatoria where substr (t_eliminatoria_id, 0, 14) = :GROUP_PREFIX  order by 1');
    oci_bind_by_name($stid, ':GROUP_PREFIX', $v_group_prefix);
    oci_execute($stid);

    while ($row = oci_fetch_array($stid, OCI_NUM)) {
        foreach ($row as $item){
            array_push($quartos, $item);
        }
    }
    shuffle($quartos);

    $v_titulo_comp = 'Campeonato Mundial de Futebol '.$ano.' - Quartos de Final';
    $v_abrev_comp = 'MFF'.$ano;
    $v_comp_id = 'MFF_03';

    $stid = oci_parse($conn, 'Insert Into T_TIPO_COMPETICAO Values (SEQ_T_TIPO_COMPETICAO.NEXTVAL, :COMP_ID, :V_TIT_COMP, :ORG, :COMP_TIPO, 0)');
    oci_bind_by_name($stid, ':COMP_ID', $v_comp_id);
    oci_bind_by_name($stid, ':V_TIT_COMP', $v_titulo_comp);
    oci_bind_by_name($stid, ':COMP_TIPO', $v_abrev_comp);
    oci_bind_by_name($stid, ':ORG', $organizador);

    oci_execute($stid);

    $static_count = count($quartos);

    for ($i = 0; $i <= $static_count -1; $i++){
        $quartos[$i] = $quartos[$i]."_VENC";
    }

    $jogo_num = 1;
    $jogo_id = 1;
    for ($i = 0; $i <= $static_count -1; $i+=2){

        $v_jogo = $quartos[$i]."-".$quartos[$i+1];
        $v_eliminatoria = "JOGO_".$jogo_id;

        $stid = oci_parse($conn, 'Begin PACK_T_ELIMINATORIA.Cria_Eliminatoria(:COMP_ID, :COMP_TIPO, :NOME_GRUPO, :EQUIPAS_GRUPO, :COMM); End;');
        oci_bind_by_name($stid, ':COMP_ID', $v_comp_id);
        oci_bind_by_name($stid, ':COMP_TIPO', $v_abrev_comp);
        oci_bind_by_name($stid, ':NOME_GRUPO', $v_eliminatoria, strlen($v_eliminatoria));
        oci_bind_by_name($stid, ':EQUIPAS_GRUPO', $v_jogo, strlen($v_jogo));
        oci_bind_by_name($stid, ':COMM', $v_commit);

        oci_execute($stid);
        $jogo_num -= 1;
        $jogo_id += 1;
    }
    return $retCode;
}

Function fun_set_insere_meias($conn, $ano, $organizador){
    $meias = array();
    $retCode = 0;
    $v_abrev_comp = 'MFF'.$ano;
    $v_comp_id = 'MFF_03';
    $v_commit = 1;

    $v_group_prefix =  $v_comp_id."_".$v_abrev_comp;

    $stid = oci_parse($conn, 'select distinct(t_eliminatoria_id) from t_eliminatoria where substr (t_eliminatoria_id, 0, 14) = :GROUP_PREFIX  order by 1');
    oci_bind_by_name($stid, ':GROUP_PREFIX', $v_group_prefix);
    oci_execute($stid);

    while ($row = oci_fetch_array($stid, OCI_NUM)) {
        foreach ($row as $item){
            array_push($meias, $item);
        }
    }

    shuffle($meias);

    $v_titulo_comp = 'Campeonato Mundial de Futebol '.$ano.' - Meias finais';
    $v_abrev_comp = 'MFF'.$ano;
    $v_comp_id = 'MFF_04';

    $stid = oci_parse($conn, 'Insert Into T_TIPO_COMPETICAO Values (SEQ_T_TIPO_COMPETICAO.NEXTVAL, :COMP_ID, :V_TIT_COMP, :ORG, :COMP_TIPO, 0)');
    oci_bind_by_name($stid, ':COMP_ID', $v_comp_id);
    oci_bind_by_name($stid, ':V_TIT_COMP', $v_titulo_comp);
    oci_bind_by_name($stid, ':COMP_TIPO', $v_abrev_comp);
    oci_bind_by_name($stid, ':ORG', $organizador);

    oci_execute($stid);

    $static_count = count($meias);

    for ($i = 0; $i <= $static_count -1; $i++){
        $meias[$i] = $meias[$i]."_VENC";
    }

    $jogo_num = 1;
    $jogo_id = 1;
    for ($i = 0; $i <= $static_count -1; $i+=2){

        $v_jogo = $meias[$i]."-".$meias[$i+1];
        $v_eliminatoria = "JOGO_".$jogo_id;

        $stid = oci_parse($conn, 'Begin PACK_T_ELIMINATORIA.Cria_Eliminatoria(:COMP_ID, :COMP_TIPO, :NOME_GRUPO, :EQUIPAS_GRUPO, :COMM); End;');
        oci_bind_by_name($stid, ':COMP_ID', $v_comp_id);
        oci_bind_by_name($stid, ':COMP_TIPO', $v_abrev_comp);
        oci_bind_by_name($stid, ':NOME_GRUPO', $v_eliminatoria, strlen($v_eliminatoria));
        oci_bind_by_name($stid, ':EQUIPAS_GRUPO', $v_jogo, strlen($v_jogo));
        oci_bind_by_name($stid, ':COMM', $v_commit);

        oci_execute($stid);
        $jogo_num -= 1;
        $jogo_id += 1;
    }
    return $retCode;
}

Function fun_set_insere_final($conn, $ano, $organizador){
    $final = array();
    $retCode = 0;
    $v_abrev_comp = 'MFF'.$ano;
    $v_comp_id = 'MFF_04';
    $v_commit = 1;

    $v_group_prefix =  $v_comp_id."_".$v_abrev_comp;

    $stid = oci_parse($conn, 'select distinct(t_eliminatoria_id) from t_eliminatoria where substr (t_eliminatoria_id, 0, 14) = :GROUP_PREFIX  order by 1');
    oci_bind_by_name($stid, ':GROUP_PREFIX', $v_group_prefix);
    oci_execute($stid);

    while ($row = oci_fetch_array($stid, OCI_NUM)) {
        foreach ($row as $item){
            array_push($final, $item);
        }
    }

    $v_titulo_comp = 'Campeonato Mundial de Futebol '.$ano.' - Final';
    $v_abrev_comp = 'MFF'.$ano;
    $v_comp_id = 'MFF_06';

    $stid = oci_parse($conn, 'Insert Into T_TIPO_COMPETICAO Values (SEQ_T_TIPO_COMPETICAO.NEXTVAL, :COMP_ID, :V_TIT_COMP, :ORG, :COMP_TIPO, 0)');
    oci_bind_by_name($stid, ':COMP_ID', $v_comp_id);
    oci_bind_by_name($stid, ':V_TIT_COMP', $v_titulo_comp);
    oci_bind_by_name($stid, ':COMP_TIPO', $v_abrev_comp);
    oci_bind_by_name($stid, ':ORG', $organizador);

    oci_execute($stid);

    $static_count = count($final);

    for ($i = 0; $i <= $static_count -1; $i++){
        $final[$i] = $final[$i]."_VENC";
    }

    $v_jogo = $final[0]."-".$final[1];
    $v_eliminatoria = "JOGO_1";

    $stid = oci_parse($conn, 'Begin PACK_T_ELIMINATORIA.Cria_Eliminatoria(:COMP_ID, :COMP_TIPO, :NOME_GRUPO, :EQUIPAS_GRUPO, :COMM); End;');
    oci_bind_by_name($stid, ':COMP_ID', $v_comp_id);
    oci_bind_by_name($stid, ':COMP_TIPO', $v_abrev_comp);
    oci_bind_by_name($stid, ':NOME_GRUPO', $v_eliminatoria, strlen($v_eliminatoria));
    oci_bind_by_name($stid, ':EQUIPAS_GRUPO', $v_jogo, strlen($v_jogo));
    oci_bind_by_name($stid, ':COMM', $v_commit);

    oci_execute($stid);

    return $retCode;
}

Function fun_set_insere_tql($conn, $ano, $organizador){
    $tql = array();
    $retCode = 0;
    $v_abrev_comp = 'MFF'.$ano;
    $v_comp_id = 'MFF_04';
    $v_commit = 1;

    $v_group_prefix =  $v_comp_id."_".$v_abrev_comp;

    $stid = oci_parse($conn, 'select distinct(t_eliminatoria_id) from t_eliminatoria where substr (t_eliminatoria_id, 0, 14) = :GROUP_PREFIX  order by 1');
    oci_bind_by_name($stid, ':GROUP_PREFIX', $v_group_prefix);
    oci_execute($stid);

    while ($row = oci_fetch_array($stid, OCI_NUM)) {
        foreach ($row as $item){
            array_push($tql, $item);
        }
    }

    $v_titulo_comp = 'Campeonato Mundial de Futebol '.$ano.' - Terceiro e Quarto lugares';
    $v_abrev_comp = 'MFF'.$ano;
    $v_comp_id = 'MFF_05';

    $stid = oci_parse($conn, 'Insert Into T_TIPO_COMPETICAO Values (SEQ_T_TIPO_COMPETICAO.NEXTVAL, :COMP_ID, :V_TIT_COMP, :ORG, :COMP_TIPO, 0)');
    oci_bind_by_name($stid, ':COMP_ID', $v_comp_id);
    oci_bind_by_name($stid, ':V_TIT_COMP', $v_titulo_comp);
    oci_bind_by_name($stid, ':COMP_TIPO', $v_abrev_comp);
    oci_bind_by_name($stid, ':ORG', $organizador);

    oci_execute($stid);

    $static_count = count($tql);

    for ($i = 0; $i <= $static_count -1; $i++){
        $tql[$i] = $tql[$i]."_DERR";
    }

    $v_jogo = $tql[0]."-".$tql[1];
    $v_eliminatoria = "JOGO_1";

    $stid = oci_parse($conn, 'Begin PACK_T_ELIMINATORIA.Cria_Eliminatoria(:COMP_ID, :COMP_TIPO, :NOME_GRUPO, :EQUIPAS_GRUPO, :COMM); End;');
    oci_bind_by_name($stid, ':COMP_ID', $v_comp_id);
    oci_bind_by_name($stid, ':COMP_TIPO', $v_abrev_comp);
    oci_bind_by_name($stid, ':NOME_GRUPO', $v_eliminatoria, strlen($v_eliminatoria));
    oci_bind_by_name($stid, ':EQUIPAS_GRUPO', $v_jogo, strlen($v_jogo));
    oci_bind_by_name($stid, ':COMM', $v_commit);

    oci_execute($stid);

    return $retCode;
}

//Nome: fun_set_sorteio_nivel1
//Parametros: Array com os 8 grupos (saida);  Array com os 8 cabe�as de s�rie; Organizador
//Descri��o: Ir� efectuar o sorteio relativo aos cabe�as de s�rie.
//Retorno: 0 se sorteio OK; -1 se sorteio NOTOK
Function fun_set_sorteio_nivel1(&$grupos, $paises, $organizador){
    //Paises
    $cabecasSerie = array();
    //Retorno
    $retCode = 0;

    //Processo de sorteio dos cabe�as de s�rie
    shuffle($paises);
    $size = count($paises)-1;
    if ($paises[0] != $organizador){
        array_push ($cabecasSerie, $organizador);
        for ($i = 0; $i <= $size; $i++ ){
            if ($paises[$i] != $organizador){
              array_push($cabecasSerie, $paises[$i]);
            }
        }
    }else{
        for ($i = 0; $i <= $size; $i++ ){
              array_push($cabecasSerie, $paises[$i]);
        }
    }
//    print $size."\n";
    for ($i = 0; $i <= $size; $i++ ){
        array_push($grupos[$i], $cabecasSerie[$i]);
        //print $i." ".$grupos[$i][1]."\n";
    }
    return $retCode;
}

Function fun_get_grps_nivel2($grupos, $pais, &$grps_disp){

    //Vai conter o continente a que pertence o pais em quest�o
    $pais_cont = substr($pais, 0, 3);
    //print "A sortear: ".$pais_cont." - ".$pais."\n";
    //Vai percorrer todos os grupos para determinar quais os grupos para onde o
    //pais em quest�o pode ir
    for($i = 0; $i < 8; $i++){
        $sorteados = count($grupos[$i])-1; //Estamos a ignorar a primeira posi��o que tem informa��o do grupo
        if ($sorteados < 2){
            //print $i."   ".$grupos[$i][1]."\n";
            $pais_cont = substr($pais, 0, 3);
            $grp_cont = substr($grupos[$i][1], 0, 3);
            //print $pais_cont." B ".$grp_cont."\n";
            if (($pais_cont == 'UEF' && $grp_cont == 'UEF') ||
                ($pais_cont == 'UEF' && $grp_cont != 'UEF') ||
                ($pais_cont != 'UEF' && $grp_cont == 'UEF')){
                //print "Vou colocar: ".$i."\n";
                array_push($grps_disp, $i);
            }
            if ($pais_cont != 'UEF' && $grp_cont != 'UEF'){
                if ($pais_cont != $grp_cont){
                    array_push($grps_disp, $i);
                //print "Vou colocar2: ".$i."\n";
                }
            }
        }
    }
    If (count($grps_disp) == 0){
      return -1;
    }else{
      return 0;
    }
}

Function fun_set_sorteio_nivel2(&$grupos, $paises){
    //Retorno
    $retCode = 0;
    //Arrays de controlo
    $grps_aux = array();

    $maxShuffle = rand(0, 50);
    for ($randTimes = 0; $randTimes <= $maxShuffle; $randTimes++){
        shuffle($paises);
    }

    //Limpeza do array de grupos para retirar qualquer elemento de nivel 2 que j� tenha sido sorteado
    //Para o caso de n�o ser o primeiro sorteio e de o sorteio anrterior ter corrido mal.
    for ($i = 0; $i < 8; $i++){
        if (count($grupos[$i]) == 3){
            $exit = array_pop($grupos[$i]);
        }
    }

    for ($j = 0; $j < count($paises); $j++){
        $paises_aux = array();
        //print "\n".$paises[$j]."\n\n";
        $retCode = fun_get_grps_nivel2($grupos, $paises[$j], $paises_aux);
        if ($retCode == -1){
          return -1;
        }
        /*for ($i = 0; $i < count($paises_aux); $i++){
            print $paises_aux[$i]."\n";
        }*/
        shuffle($paises_aux);
        array_push($grupos[$paises_aux[0]], $paises[$j]);
    }
}

Function fun_get_grps_nivel3($grupos, $pais, &$grps_disp){

    //Vai conter o continente a que pertence o pais em quest�o
    $pais_cont = substr($pais, 0, 3);
    //print "A sortear: ".$pais_cont." - ".$pais."\n";
    //Vai percorrer todos os grupos para determinar quais os grupos para onde o
    //pais em quest�o pode ir
    for($i = 0; $i < 8; $i++){
        $sorteados = count($grupos[$i])-1; //Estamos a ignorar a primeira posi��o que tem informa��o do grupo
        if ($sorteados < 3){
            //print $i."   ".$grupos[$i][1]."   ".$grupos[$i][2]."\n";
                $pais_cont = substr($pais, 0, 3);
            $grp_cont1 = substr($grupos[$i][1], 0, 3);
            $grp_cont2 = substr($grupos[$i][2], 0, 3);
            //print $pais_cont." B ".$grp_cont1."  ".$grp_cont2."\n";
            if (($pais_cont == 'UEF' && $grp_cont1 == 'UEF' && $grp_cont2 != 'UEF') ||
                ($pais_cont == 'UEF' && $grp_cont1 != 'UEF' && $grp_cont2 != 'UEF') ||
                ($pais_cont == 'UEF' && $grp_cont1 != 'UEF' && $grp_cont2 == 'UEF') ||
                ($pais_cont != 'UEF' && $grp_cont1 == 'UEF' && $grp_cont2 == 'UEF') ||
                ($pais_cont != 'UEF' && $grp_cont1 != 'UEF' && $grp_cont2 == 'UEF' && $pais_cont != $grp_cont1) ||
                ($pais_cont != 'UEF' && $grp_cont1 == 'UEF' && $grp_cont2 != 'UEF' && $pais_cont != $grp_cont2) ||
                ($pais_cont != 'UEF' && $grp_cont1 != 'UEF' && $grp_cont2 != 'UEF' && $pais_cont != $grp_cont2 && $pais_cont != $grp_cont1)){
                //print "Vou colocar: ".$i."\n";
                array_push($grps_disp, $i);
            }
        }
    }
    If (count($grps_disp) == 0){
      return -1;
    }else{
      return 0;
    }
}

Function fun_set_sorteio_nivel3(&$grupos, $paises){
    //Retorno
    $retCode = 0;
    //Arrays de controlo
    $grps_aux = array();

    $maxShuffle = rand(0, 50);
    for ($randTimes = 0; $randTimes <= $maxShuffle; $randTimes++){
        shuffle($paises);
    }

    //Limpeza do array de grupos para retirar qualquer elemento de nivel 2 que j� tenha sido sorteado
    //Para o caso de n�o ser o primeiro sorteio e de o sorteio anrterior ter corrido mal.
    for ($i = 0; $i < 8; $i++){
        if (count($grupos[$i]) == 4){
            $exit = array_pop($grupos[$i]);
        }
    }

    for ($j = 0; $j < count($paises); $j++){
        $paises_aux = array();
        //print "\n".$paises[$j]."\n\n";
        $retCode = fun_get_grps_nivel3($grupos, $paises[$j], $paises_aux);
        if ($retCode == -1){
          return -1;
        }
        /*for ($i = 0; $i < count($paises_aux); $i++){
            print $paises_aux[$i]."\n";
        }*/
        shuffle($paises_aux);
        array_push($grupos[$paises_aux[0]], $paises[$j]);
    }
}

Function fun_get_grps_nivel4($grupos, $pais, &$grps_disp){

    //Vai conter o continente a que pertence o pais em quest�o
    $pais_cont = substr($pais, 0, 3);
    //Vai percorrer todos os grupos para determinar quais os grupos para onde o
    //pais em quest�o pode ir
    for($i = 0; $i < 8; $i++){
        $sorteados = count($grupos[$i])-1; //Estamos a ignorar a primeira posi��o que tem informa��o do grupo
        if ($sorteados < 4){
            //print $pais."  ".$grupos[$i][1]."  ".$grupos[$i][2]."  ".$grupos[$i][3]."\n";
            $pais_cont = substr($pais, 0, 3);
            $grp_cont1 = substr($grupos[$i][1], 0, 3);
            $grp_cont2 = substr($grupos[$i][2], 0, 3);
            $grp_cont3 = substr($grupos[$i][3], 0, 3);
            if ($pais_cont != 'UEF' &&
                $grp_cont1 == 'UEF' &&
                $grp_cont2 == 'UEF' &&
                $grp_cont3 != 'UEF' &&
                $pais_cont != $grp_cont3){
                    array_push($grps_disp, $i);
            }
            if ($pais_cont != 'UEF' &&
                $grp_cont1 == 'UEF' &&
                $grp_cont2 != 'UEF' &&
                $grp_cont3 == 'UEF' &&
                $pais_cont != $grp_cont2){
                    array_push($grps_disp, $i);
            }
            if ($pais_cont != 'UEF' &&
                $grp_cont1 != 'UEF' &&
                $grp_cont2 == 'UEF' &&
                $grp_cont3 == 'UEF' &&
                $pais_cont != $grp_cont1){
                    array_push($grps_disp, $i);
            }
            if ($grp_cont1 == 'UEF' &&
                $grp_cont2 != 'UEF' &&
                $grp_cont3 != 'UEF' &&
                $pais_cont != $grp_cont2 &&
                $pais_cont != $grp_cont3){
                    array_push($grps_disp, $i);
            }
            if ($grp_cont1 != 'UEF' &&
                $grp_cont2 == 'UEF' &&
                $grp_cont3 != 'UEF' &&
                $pais_cont != $grp_cont1 &&
                $pais_cont != $grp_cont3){
                    array_push($grps_disp, $i);
            }
            if ($grp_cont1 != 'UEF' &&
                $grp_cont2 != 'UEF' &&
                $grp_cont3 == 'UEF' &&
                $pais_cont != $grp_cont1 &&
                $pais_cont != $grp_cont2){
                    array_push($grps_disp, $i);
            }
            if ($grp_cont1 != 'UEF' &&
                $grp_cont2 != 'UEF' &&
                $grp_cont3 != 'UEF' &&
                $pais_cont != $grp_cont1 &&
                $pais_cont != $grp_cont2 &&
                $pais_cont != $grp_cont3){
                    array_push($grps_disp, $i);
            }
        }
    }
    If (count($grps_disp) == 0){
      return -1;
    }else{
      return 0;
    }
}

Function fun_set_sorteio_nivel4(&$grupos, $paises){
    //Retorno
    $retCode = 0;
    //Arrays de controlo
    $grps_aux = array();

    $maxShuffle = rand(0, 50);
    for ($randTimes = 0; $randTimes <= $maxShuffle; $randTimes++){
        shuffle($paises);
    }

    //Limpeza do array de grupos para retirar qualquer elemento de nivel 2 que j� tenha sido sorteado
    //Para o caso de n�o ser o primeiro sorteio e de o sorteio anrterior ter corrido mal.
    for ($i = 0; $i < 8; $i++){
        if (count($grupos[$i]) == 5){
            $exit = array_pop($grupos[$i]);
        }
    }

    for ($j = 0; $j < count($paises); $j++){
        $paises_aux = array();
        //print "\n".$paises[$j]."\n\n";
        $retCode = fun_get_grps_nivel4($grupos, $paises[$j], $paises_aux);
        if ($retCode == -1){
          print $retCode."\n";
          return -1;
        }
        /*for ($i = 0; $i < count($paises_aux); $i++){
            print $paises_aux[$i]."\n";
        }*/
        shuffle($paises_aux);
        array_push($grupos[$paises_aux[0]], $paises[$j]);
    }
}

//Nome: fun_set_sorteio
//Parametros: Entrada Vari�vel contendo a liga��o � BD; Ano do mundial; Organizador
//Descri��o: Ir� efectuar o sorterio propriamente dito
//Retorno: 0 se sorteio OK; -1 se sorteio NOTOK
Function fun_set_sorteio($conn, $ano, $organizador){
    //Vari�veis
    //Retorno
    $retcode = 0;
    //Grupos
    // Array dos grupos da 1� fase
    $grupoA = array(); array_push($grupoA, 'GRUPO_A');
    $grupoB = array(); array_push($grupoB, 'GRUPO_B');
    $grupoC = array(); array_push($grupoC, 'GRUPO_C');
    $grupoD = array(); array_push($grupoD, 'GRUPO_D');
    $grupoE = array(); array_push($grupoE, 'GRUPO_E');
    $grupoF = array(); array_push($grupoF, 'GRUPO_F');
    $grupoG = array(); array_push($grupoG, 'GRUPO_G');
    $grupoH = array(); array_push($grupoH, 'GRUPO_H');

    $grupos = array($grupoA,$grupoB,$grupoC,$grupoD,$grupoE,$grupoF,$grupoG,$grupoH);

    $paises = array();

    //Vai actualizar o ranking mundial
    $stid = oci_parse($conn, 'Begin pack_t_ranking.pro_set_pontos_ranking; End;');
    oci_execute($stid);

    //Vai buscar as selec��es � tabela do mundial para o ano em causa.
    $stid = oci_parse($conn, 'select t_mundial_equipa
                              from t_mundial
                              where t_mundial_ano = :p_ano
                              order by t_mundial_rank');

    oci_bind_by_name($stid, ':p_ano', $ano);
    oci_execute($stid);
    //Recolhe o resultado da querie
    while ($row = oci_fetch_array($stid, OCI_NUM)) {
        foreach ($row as $item){
            array_push($paises, $item);
        }
    }
    // Divis�o do array de paises em 4 blocos equivalentes a 4 niveis
    $paises = array_chunk($paises, 8);

    $retCode = fun_set_sorteio_nivel1($grupos, $paises[0], $organizador);
    print $grupos[7][1]." ".$grupos[4][1]."\n";
    print $grupos[0][1]." ".$grupos[3][1]."\n";
    print $grupos[2][1]." ".$grupos[1][1]."\n";
    print $grupos[5][1]." ".$grupos[6][1]."\n";
    if ($retCode == 0){
        $retCode = -1;
        While ($retCode == -1){
            $retCode = fun_set_sorteio_nivel2($grupos, $paises[1]);
        }
        if ($retCode == 0){
            $retCode = -1;
            While ($retCode == -1){
                $retCode = fun_set_sorteio_nivel3($grupos, $paises[2]);
            }
            if ($retCode == 0){
                $retCode = -1;
                While ($retCode == -1){
                    $retCode = fun_set_sorteio_nivel4($grupos, $paises[3]);
                }
            }
        }
    }
    /*$h = 0;
    for ($i = 0; $i < count($grupos); $i++){
      print $grupos[$h][0]."\n";
      sleep(35);
      for ($j = 1; $j < count($grupos[$i]); $j++){
        $stid = oci_parse($conn, 'select nome from t_equipas where t_equipas_id = :p_equipa');

        oci_bind_by_name($stid, ':p_equipa', $grupos[$i][$j]);
        oci_execute($stid);

        $equipa = oci_fetch_array($stid, OCI_NUM);
        foreach ($equipa as $item){
            echo utf8_encode($item)."\n";
            sleep(35);
        }
      }
      $h += 1;
    }*/

    /*Inser��o dos grupos na BD - Primeira Fase - Chamada ao procedimento*/
    If ($retCode == 0){
        $retCode = fun_set_insere_prim_fase($grupos, $conn, $organizador, $ano);
        If ($retCode == 0){
            $retCode = fun_set_insere_oitavos($conn, $ano, $organizador);
            If ($retCode == 0){
                $retCode = fun_set_insere_quartos($conn, $ano, $organizador);
                If ($retCode == 0){
                    $retCode = fun_set_insere_meias($conn, $ano, $organizador);
                    If ($retCode == 0){
                        $retCode = fun_set_insere_final($conn, $ano, $organizador);
                        If ($retCode == 0){
                            $retCode = fun_set_insere_tql($conn, $ano, $organizador);
                        }
                    }
                }
            }
        }
    }
    return $retCode;
}

Function fun_set_qualif($conn, $ano){

    //Vai verificar para que ano est� a ser feito o sorteio
    $stid = oci_parse($conn, 'Update t_mundial_qlf set t_qlf_fim = 1
                            Where t_qlf_ano = :ANO And t_qlf_inic = 1 And t_qlf_fim = 0');

    oci_bind_by_name($stid, ':ANO', $ano);
    oci_execute($stid);

}


//CORPO PRINCIPAL
//Inicializa a liga��o � BD
fun_set_conn($conn);

//Vai verificar a que ano corresponde o sorteio
$ano = fun_get_ano($conn);

//Determina se � possivel fazer o sorteio
$sorteio = fun_exists_mundial($conn, $ano);
if ($sorteio == 0){
    //Determina o organizador do Mundial
    $organizador = fun_set_organizador($conn,$ano);

    print $organizador."\n";

    $retCode = fun_set_sorteio($conn, $ano, $organizador);

    if ($retCode == 0){
        fun_set_qualif($conn, $ano);
    }

}else{
    print "N�o � possivel fazer o sorteio pretendido. O Mundial em quest�o j� existe.";
}
oci_close($conn);
?>