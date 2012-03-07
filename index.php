<?
define('GAMESLOG', '/tmp/games.log');
define('GAMELIST', '/tmp/q3gamelist.xml');
	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	include_once('lib.php');

	$game_types[0]='DM';
	$game_types[3]='TDM';
	$game_types[4]='CTF';
	$teams_name['red']='Красная';
	$teams_name['blue']='Синяя';
	$game_offset=$_GET['game_offset'];

	$menu=get_games_list();
	if (count($menu)>0)
	{
		if ($game_offset==0)
		{
			header("Location: ".$menu[count($menu)-1]['href']);
			return;
		}
	}
?>
<html>
<head>
	<link href='/main.css' rel=stylesheet type=text/css>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!--[if IE]><script language="javascript" type="text/javascript" src="/js/flot/excanvas.pack.js"></script><![endif]-->
    <script language="javascript" type="text/javascript" src="/js/flot/jquery.js"></script>
    <script language="javascript" type="text/javascript" src="/js/flot/jquery.flot.js"></script>
</head>
<style>
body {
	font-size: 14px;
	font-family: Verdana;
}
h1 {
	margin: 0 0 0.3em 0;
	font-weight: normal;
	font-size: 140%;
}
h2 {
	margin: 0.3em 0 0.3em 0;
	font-weight: normal;
	font-size: 120%;
}
table {
	background-color: #C0C0C0;
}
table td {
	background-color: white;
}
table tr.summary td {
	background-color: #F0F0F0;
}
div.time_block {
	height: 60px;
	width: 80px;
	float: left;
	background-image: url("/images/time_grid.gif");
	background-position: right top;
	background-repeat: no-repeat;
	text-align: right;
	padding-right: 12px;
	font-size: 14px;
	font-weight: bold;
}
.time_team {
	vertical-align: top;
}
.time_team div {
	position: relative;
}
.time_team img {
	position: absolute;
	left: 0px;
	width: 64px;
	height: 2px;
}
.red {
	color: red;
}
.blue {
	color: blue;
}
.gray {
	color: #B0B0B0;
}
.gray .blue {
	color: #A0A0FF;
}
.gray .red {
	color: #FFA0A0;
}
table.all_right td {
	text-align: right;
}
.f90 td {
	font-size: 90%;
}
h1 span {
	font-size: 75%;
	color: red;
}
#id_menu_table tr.selected td {
	background-color: #AFA;
}
</style>
<body id="body">
<script language="JavaScript">
$(document).ready(function(){
	$(".time_team img").hover(function(){
		$(this).attr("_src",$(this).attr("src"));
		$(this).attr("src","/images/dot_red.gif");
	},
	function(){
		$(this).attr("src",$(this).attr("_src"));
	});
});
</script>
<h1 style="font-size: 180%;">Статистика Quake 3 <span>v1.1</span></h1>
<table border=0 cellspacing=0 cellpadding=0 width="100%" style="font-size: 100%" id="id_menu_table">
<tr><td valign="top" width="230px">
<?
	echo '<h1>Выберите игру</h1>';
	echo '<table border=0 cellspacing=1 cellpadding=2 class="simple f90">';
	echo '<thead><th>Дата</th><th>Карта</th><th>Тип</th></thead>';
	$menu=get_games_list();
	if (count($menu)>0)
	{
		$menu=array_reverse($menu);
		for ($i=0;$i<count($menu);$i++)
		{
			echo '<tr '.($_GET['game_offset']==$menu[$i]['offset']?'class="selected"':'').'><td><a href="'.$menu[$i]['href'].'">'.mb_substr($menu[$i]['date'],0,5).'.'.mb_substr($menu[$i]['date'],8,2).', '.mb_substr($menu[$i]['date'],12,5).'</a></td><td>'.$menu[$i]['map'].'</td><td>'.$menu[$i]['type'].'</td></tr>';
		}
	}
	echo '</table>';
?>
</td><td valign=top style="padding-left: 16px;">
<?
	#В начале выводим список игр
	if ($game_offset==0)
	{
		/*echo '<h1>Выберите игру</h1>';
		echo '<table border=0 cellspacing=1 cellpadding=2 class="simple f90">';
		echo '<thead><td>Дата</td><td>Карта</td><td>Тип</td></thead>';
		$menu=get_games_list();
		if (count($menu)>0)
		{
			$menu=array_reverse($menu);
			for ($i=0;$i<count($menu);$i++)
			{
				echo '<tr><td><a target="content" href="'.$menu[$i]['href'].'">'.$menu[$i]['date'].'</a></td><td>'.$menu[$i]['map'].'</td><td>'.$menu[$i]['type'].'</td></tr>';
			}
		}
		echo '</table>';*/
	}else
	{
		#Обрабатываем конкретную игру
		$disconnects=array();
		$log=fopen(GAMESLOG,'r');
		fseek($log,$game_offset);
		#В начале читаем все строки
		unset($rows);
		while (!feof($log) and $row['command']!='ShutdownGame')
		{
			$row=get_row($log);
			$rows[]=$row;
		}
		#Теперь обрабатываем данные
		for ($i=0;$i<count($rows);$i++)
		{
			$row=$rows[$i];
			if (isset($session[1]) && !isset($session[1]['carrier']) && (($row['time']-$game['time_start'])-$session[1]['drop_time']>=30))
				{
					$time=$session[1]['drop_time']+30;
					$session[1]['end_time']=$time;
					$session[1]['duration']=$time-$session[1]['start_time'];
					$session[1]['history'][]=format_time($time).' - возвращен по тайм-ауту';
					#Добавляем событие противоположному флагу, если он активен
					if (isset($session[2]))
						$session[2]['history'][]='<font class=gray>'.format_time($time).' - возвращен по тайм-ауту</font>';
					$session[1]['history'][]='Период: '.format_time($session[1]['duration']);
					$session[1]['mode']='returned';
					$global_sessions[1][]=$session[1];
					$session[1]['history'][]='Возвращен по тайм-ауту';
					unset($session[1]);
				}
			if (isset($session[2]) && !isset($session[2]['carrier']) && (($row['time']-$game['time_start'])-$session[2]['drop_time']>=30))
				{
					$time=$session[2]['drop_time']+30;
					$session[2]['end_time']=$time;
					$session[2]['duration']=$time-$session[2]['start_time'];
					$session[2]['history'][]=format_time($time).' - возвращен по тайм-ауту';
					#Добавляем событие противоположному флагу, если он активен
					if (isset($session[1]))
						$session[1]['history'][]='<font class=gray>'.format_time($time).' - возвращен по тайм-ауту</font>';
					$session[2]['history'][]='Период: '.format_time($session[2]['duration']);
					$session[2]['mode']='returned';
					$global_sessions[2][]=$session[2];
					$session[2]['history'][]='Возвращен по тайм-ауту';
					unset($session[2]);
				}
			unset($time);
			#В начале полюбому читаем время
			if ($row['command']=='InitGame') #Инициализируем игру
			{
				unset($game);
				#Получаем параметры игры
				$params=explode('\\',$row['params']);
				unset($params[0]);
				for ($z=1;$z<=count($params)/2;$z++)
				{
					$game['params'][$params[$z*2-1]]=$params[$z*2];
				}

				if ($row_Game_Start['command']=='Game_Start')
				{
					$params=explode('	',$row_ServerTime['params']);
					$date_time=preg_replace('/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/',"$1-$2-$3 $4:$5:$6",$params[0]);
					echo '<tr><td><a target="content" href="/?game_offset='.$filepos.'">'.date2str($date_time).', '.date2time($date_time).'</a></td><td>'.$init_params['mapname'].'</td><td>'.$game_types[$init_params['g_gametype']].'</td></tr>';
				}
			}elseif ($row['command']=='Game_Start') #Начало игры
			{
				$game['time_start']=$row['time'];
			}elseif($row['command']=='Game_End') #Конец игры
			{
				$allow_disconnects=true;
				$time=$row['time']-$game['time_start'];
				$game['time_end']=$row['time'];
				$game['time_duration']=round($game['time_end']-$game['time_start']);
				$game_terminated=true;
				#Если в воздухе висят флаги, то ставим их на место
				#И записываем несущему игроку время владения
				if (isset($session['1']))
				{
					if (isset($session['1']['carrier']))
						$game['players'][$session['1']['carrier']]['time_pickup']+=$time-$game['players'][$session['1']['carrier']]['tmp_time_pickup'];
					$session['1']['end_time']=$time;
					$session['1']['duration']=$time-$session['1']['start_time'];
					$session['1']['history'][]=format_time($time).' - Игра завершена';
					$session['1']['history'][]='Период: '.format_time($session['1']['duration']);
					$session['1']['mode']='ingame';
					$global_sessions['1'][]=$session['1'];
					unset($session['1']);
				}
				if (isset($session['2']))
				{
					if (isset($session['2']['carrier']))
						$game['players'][$session['2']['carrier']]['time_pickup']+=$time-$game['players'][$session['2']['carrier']]['tmp_time_pickup'];
					$session['2']['end_time']=$time;
					$session['2']['duration']=$time-$session['2']['start_time'];
					$session['2']['history'][]=format_time($time).' - Игра завершена';
					$session['2']['history'][]='Период: '.format_time($session['2']['duration']);
					$session['2']['mode']='ingame';
					$global_sessions['2'][]=$session['2'];
					unset($session['2']);
				}
			}elseif($row['command']=='TeamName') #Определяем команду
			{
				$value=explode(' ',$row['params']);
				$game['teams_idx'][$value[0]]['title']=$value[1];
				$game['teams_href'][$value[1]]['idx']=$value[0];
			}elseif($row['command']=='ClientUserinfoChanged') #Добавляем нового игрока
			{
				unset($player);
				$value=explode(' ',$row['params'],2);
				if (!isset($game['players'][$value[0]]))
				{
				$value[1]=trim($value[1]);
				#Получаем параметры игрока
				$params=explode('\\',$value[1]);
				for ($z=1;$z<=count($params)/2;$z++)
				{
					$player[$params[$z*2-2]]=$params[$z*2-1];
				}
				#Пропускаем спектаторов
				if ($player['t']<3)
				{
					$player['n']=preg_replace('/(\^.)/','',$player['n']);
					$game['players'][$value[0]]['name']=$player['n'];
					if ($game['params']['g_gametype']!=0)
					{
						$game['players'][$value[0]]['team_idx']=$player['t'];
						$game['teams_idx'][$player['t']]['players'][$value[0]]=true;
					}
				}
				}
			}elseif($row['command']=='Kill') #Убийство
			{
				$value=explode(' ',$row['params'],3);

				#Если человек несет флаг, обрабатываем падение флага
				#Если вражеский флаг подобран и несем его мы - то сорасываем
				$time=$row['time']-$game['time_start'];
				#Если еще не начато новой сессии подхваченного флага - то начинаем
				$player_idx=$game['players'][$value[1]]['team_idx'];
				$flag_idx=(($player_idx-2)*-1)+1;

				if (!isset($game['players'][$value[0]]))
				{
					if (isset($session[$flag_idx]))
						if (isset($session[$flag_idx]['carrier'])&&($session[$flag_idx]['carrier']==$value[1]))
						{
							#Останавливаем игроку счетчик времени владения флагом
							$game['players'][$value[1]]['time_pickup']+=$time-$game['players'][$value[1]]['tmp_time_pickup'];
							#Если разбился о землю - то флаг падает, иначе - возвращается
							if (strpos($value[2],'MOD_FALLING')>0)
							{
								$session[$flag_idx]['history'][]=format_time($time).' - <font color="'.strtolower($game['teams_idx'][$player_idx]['title']).'">'.$game['players'][$value[1]]['name'].'</font>  разбился. Флаг брошен.';
								if (isset($session[flip_team($flag_idx)]))
									$session[flip_team($flag_idx)]['history'][]='<font class="gray">'.format_time($time).' - <font class="'.strtolower($game['teams_idx'][$player_idx]['title']).'">'.$game['players'][$value[1]]['name'].'</font>  разбился. Флаг брошен.</font>';
								$session[$flag_idx]['drop_time']=$row['time']-$game['time_start'];
								unset($session[$flag_idx]['carrier']);
							}else
							{
								#Возврат флага
								$session[$flag_idx]['end_time']=$time;
								$session[$flag_idx]['duration']=$time-$session[$flag_idx]['start_time'];
								$session[$flag_idx]['history'][]=format_time($time).' - <font color="'.strtolower($game['teams_idx'][$player_idx]['title']).'">'.$game['players'][$value[1]]['name'].'</font>  упал в лаву. Флаг возвращен.';
								if (isset($session[flip_team($flag_idx)]))
									$session[flip_team($flag_idx)]['history'][]='<font class="gray">'.format_time($time).' - <font class="'.strtolower($game['teams_idx'][$player_idx]['title']).'">'.$game['players'][$value[1]]['name'].'</font>  упал в лаву. Флаг возвращен.</font>';
								$session[$flag_idx]['history'][]='Период: '.format_time($session[$flag_idx]['duration']);
								$session[$flag_idx]['mode']='returned';
								$global_sessions[$flag_idx][]=$session[$flag_idx];
								unset($session[$flag_idx]);
							}
						}
					if ($value[0]=='1022')
					{
						$game['players'][$value[1]]['sui']++;
					}else
					{
						echo 'Неизвестная причина смерти. Код: '.$value[0].' Время: '.$row['time'].'<br />';
					}
				}else
				{
					#Пишем смерть игроку, если мы не в режиме TDM и CTD и одновременно не в нашей команде
					if ($value[0]==$value[1])
					{
						$game['players'][$value[0]]['sui']++;
						#Добавляем килл
						preg_match('/(MOD_.*) /',$value[2],$weapon);
						$game['players'][(integer)$value[1]]['who_to_who'][(integer)$value[1]][]=format_time($time).' - <b>('.get_weapon_name($weapon[1]).')</b>';
//						echo $value[0].'-'.$value[1].'='.format_time($time).' - <font color="'.strtolower($game['teams_idx'][$player_idx]['title']).'">'.$game['players'][$value[1]]['name'].'</font> убил себя <strong>('.get_weapon_name($weapon[1]).')</strong>';
						if (isset($session[$flag_idx]))
							if (isset($session[$flag_idx]['carrier'])&&($session[$flag_idx]['carrier']==$value[1]))
							{
								#Останавливаем игроку счетчик времени владения флагом
								$game['players'][$value[1]]['time_pickup']+=$time-$game['players'][$value[1]]['tmp_time_pickup'];
								$session[$flag_idx]['history'][]=format_time($time).' - <font color="'.strtolower($game['teams_idx'][$player_idx]['title']).'">'.$game['players'][$value[1]]['name'].'</font> убил себя <strong>('.get_weapon_name($weapon[1]).')</strong>. Флаг брошен.';
								if (isset($session[flip_team($flag_idx)]))
									$session[flip_team($flag_idx)]['history'][]='<font class="gray">'.format_time($time).' - <font class="'.strtolower($game['teams_idx'][$player_idx]['title']).'">'.$game['players'][$value[1]]['name'].'</font> убил себя <strong>('.get_weapon_name($weapon[1]).')</strong>. Флаг брошен.</font>';
								$session[$flag_idx]['drop_time']=$row['time']-$game['time_start'];
								unset($session[$flag_idx]['carrier']);
							}
					}elseif (($game['params']['g_gametype']==0)||(($game['params']['g_gametype']!=0)and($game['players'][$value[0]]['team_idx']!=$game['players'][$value[1]]['team_idx'])))
					{
						$diagrams['kill_history'][$game['players'][$value[0]]['team_idx']][floor($time/60)]++;
						$diagrams['kill_history_2'][$game['players'][$value[0]]['team_idx']][floor($time/60)][]=format_time($time).' - '.$game['players'][$value[0]]['name'].' - '.$game['players'][$value[1]]['name'].' <b>('.get_weapon_name($weapon[1]).')</b>';
						$game['players'][$value[0]]['kill']++;
						$game['players'][$value[1]]['death']++;
						#Добавляем килл
						preg_match('/(MOD_.*) /',$value[2],$weapon);
						$game['players'][$value[0]]['who_to_who'][$value[1]][]=format_time($time).' - <b>('.get_weapon_name($weapon[1]).')</b>';
//						$diagramm
						#Статистика по оружию
						if ($weapon[1]=='MOD_ROCKET' || $weapon[1]=='MOD_ROCKET_SPLASH')
						{
							$game['players'][$value[0]]['weapon']['MOD_ROCKET']['kills']++;
						}elseif ($weapon[1]=='MOD_PLASMA' || $weapon[1]=='MOD_PLASMA_SPLASH')
						{
							$game['players'][$value[0]]['weapon']['MOD_PLASMA']['kills']++;
						}elseif ($weapon[1]=='MOD_BFG' || $weapon[1]=='MOD_BFG_SPLASH')
						{
							$game['players'][$value[0]]['weapon']['MOD_BFG']['kills']++;
						}elseif ($weapon[1]=='MOD_SHOTGUN')
						{
							$game['players'][$value[0]]['weapon']['MOD_SHOTGUN']['kills']++;
						}elseif ($weapon[1]=='MOD_GRENADE' || $weapon[1]=='MOD_GRENADE_SPLASH')
						{
							$game['players'][$value[0]]['weapon']['MOD_GRENADE']['kills']++;
						}elseif ($weapon[1]=='MOD_MACHINEGUN')
						{
							$game['players'][$value[0]]['weapon']['MOD_MACHINEGUN']['kills']++;
						}elseif ($weapon[1]=='MOD_GAUNTLET')
						{
							$game['players'][$value[0]]['weapon']['MOD_GAUNTLET']['kills']++;
						}elseif ($weapon[1]=='MOD_RAILGUN')
						{
							$game['players'][$value[0]]['weapon']['MOD_RAILGUN']['kills']++;
						}elseif ($weapon[1]=='MOD_LIGHTNING')
						{
							$game['players'][$value[0]]['weapon']['MOD_LIGHTNING']['kills']++;
						}
						#Конец статистики по оружию
						if (isset($session[$flag_idx]))
							if (isset($session[$flag_idx]['carrier'])&&($session[$flag_idx]['carrier']==$value[1]))
							{
								#Останавливаем игроку счетчик времени владения флагом
								$game['players'][$value[1]]['time_pickup']+=$time-$game['players'][$value[1]]['tmp_time_pickup'];
								$session[$flag_idx]['history'][]=format_time($time).' - <font color="'.strtolower($game['teams_idx'][$game['players'][$value[0]]['team_idx']]['title']).'">'.$game['players'][$value[0]]['name'].'</font> убил <font color="'.strtolower($game['teams_idx'][$player_idx]['title']).'">'.$game['players'][$value[1]]['name'].'</font> <strong>('.get_weapon_name($weapon[1]).')</strong>. Флаг брошен.';
								if (isset($session[flip_team($flag_idx)]))
									$session[flip_team($flag_idx)]['history'][]='<font class="gray">'.format_time($time).' - <font class="'.strtolower($game['teams_idx'][$game['players'][$value[0]]['team_idx']]['title']).'">'.$game['players'][$value[0]]['name'].'</font> убил <font class="'.strtolower($game['teams_idx'][$player_idx]['title']).'">'.$game['players'][$value[1]]['name'].'</font> <strong>('.get_weapon_name($weapon[1]).')</strong>. Флаг брошен.</font>';
								$session[$flag_idx]['drop_time']=$row['time']-$game['time_start'];
								unset($session[$flag_idx]['carrier']);
							}
					}else
					{
						$game['players'][$value[0]]['tk']++;
						#Добавляем килл
						preg_match('/(MOD_.*) /',$value[2],$weapon);
						$game['players'][$value[0]]['who_to_who'][$value[1]][]=format_time($time).' - <b>('.get_weapon_name($weapon[1]).')</b>';
						if (isset($session[$flag_idx]))
							if (isset($session[$flag_idx]['carrier'])&&($session[$flag_idx]['carrier']==$value[1]))
							{
								#Останавливаем игроку счетчик времени владения флагом
								$game['players'][$value[1]]['time_pickup']+=$time-$game['players'][$value[1]]['tmp_time_pickup'];
								$session[$flag_idx]['history'][]=format_time($time).' - <font color="'.strtolower($game['teams_idx'][$game['players'][$value[0]]['team_idx']]['title']).'">'.$game['players'][$value[0]]['name'].'</font> убил <font color="'.strtolower($game['teams_idx'][$player_idx]['title']).'">'.$game['players'][$value[1]]['name'].'</font> <strong>('.get_weapon_name($weapon[1]).')</strong>. Флаг брошен.';
								if (isset($session[flip_team($flag_idx)]))
									$session[flip_team($flag_idx)]['history'][]='<font class="gray">'.format_time($time).' - <font class="'.strtolower($game['teams_idx'][$game['players'][$value[0]]['team_idx']]['title']).'">'.$game['players'][$value[0]]['name'].'</font> убил <font class="'.strtolower($game['teams_idx'][$player_idx]['title']).'">'.$game['players'][$value[1]]['name'].'</font> <strong>('.get_weapon_name($weapon[1]).')</strong>. Флаг брошен.</font>';
								$session[$flag_idx]['drop_time']=$row['time']-$game['time_start'];
								unset($session[$flag_idx]['carrier']);
							}
					}
				}
			}elseif($row['command']=='Weapon_Stats') #Статистика по оружию
			{
				$params=explode(' ',$row['params']);
				$player_idx=$params[0];
				unset($params[0]);
				foreach ($params as $key=>$value)
				{
					$data=explode(':',$value);
					if ($data[0]=='Given')
					{
						$game['players'][$player_idx]['Given']=$data[1];
					}elseif($data[0]=='Recvd')
					{
						$game['players'][$player_idx]['Recvd']=$data[1];
					}elseif($data[0]=='Armor')
					{
						$game['players'][$player_idx]['Armor']=$data[1];
					}elseif($data[0]=='Health')
					{
						$game['players'][$player_idx]['Health']=$data[1];
					}elseif($data[0]=='TeamDmg')
					{
						$game['players'][$player_idx]['TeamDmg']=$data[1];
					}elseif($data[0]=='Gauntlet')
					{
						$game['players'][$player_idx]['weapon']['MOD_GAUNTLET']['shots']=$data[1];
						$game['players'][$player_idx]['weapon']['MOD_GAUNTLET']['hits']=$data[2];
					}elseif($data[0]=='MachineGun')
					{
						$game['players'][$player_idx]['weapon']['MOD_MACHINEGUN']['shots']=$data[1];
						$game['players'][$player_idx]['weapon']['MOD_MACHINEGUN']['hits']=$data[2];
					}elseif($data[0]=='Shotgun')
					{
						$game['players'][$player_idx]['weapon']['MOD_SHOTGUN']['shots']=$data[1];
						$game['players'][$player_idx]['weapon']['MOD_SHOTGUN']['hits']=$data[2];
					}elseif($data[0]=='G.Launcher')
					{
						$game['players'][$player_idx]['weapon']['MOD_GRENADE']['shots']=$data[1];
						$game['players'][$player_idx]['weapon']['MOD_GRENADE']['hits']=$data[2];
					}elseif($data[0]=='R.Launcher')
					{
						$game['players'][$player_idx]['weapon']['MOD_ROCKET']['shots']=$data[1];
						$game['players'][$player_idx]['weapon']['MOD_ROCKET']['hits']=$data[2];
					}elseif($data[0]=='Railgun')
					{
						$game['players'][$player_idx]['weapon']['MOD_RAILGUN']['shots']=$data[1];
						$game['players'][$player_idx]['weapon']['MOD_RAILGUN']['hits']=$data[2];
					}elseif($data[0]=='Plasmagun')
					{
						$game['players'][$player_idx]['weapon']['MOD_PLASMA']['shots']=$data[1];
						$game['players'][$player_idx]['weapon']['MOD_PLASMA']['hits']=$data[2];
					}elseif($data[0]=='LightningGun')
					{
						$game['players'][$player_idx]['weapon']['MOD_LIGHTNING']['shots']=$data[1];
						$game['players'][$player_idx]['weapon']['MOD_LIGHTNING']['hits']=$data[2];
					}
				}
				unset($data);
			}elseif($row['command']=='Item')
			{
				$data=explode(' ',$row['params']);
				if ($data[1]=='item_health_mega')
				{
					$game['players'][$data[0]]['item_health_mega']++;
				}elseif ($data[1]=='item_armor_body') #Красный армор
				{
					$game['players'][$data[0]]['item_armor_body']++;
				}elseif ($data[1]=='item_armor_combat') #Желтый армор
				{
					$game['players'][$data[0]]['item_armor_combat']++;
				}elseif ($data[1]=='item_regen') #Реген
				{
					$game['players'][$data[0]]['item_regen']++;
				}elseif ($data[1]=='item_invis') #Невидимость
				{
					$game['players'][$data[0]]['item_invis']++;
				}elseif ($data[1]=='item_quad') #Квад
				{
					$game['players'][$data[0]]['item_quad']++;
				}elseif ($data[1]=='holdable_teleporter') #Телепорт
				{
					$game['players'][$data[0]]['holdable_teleporter']++;
				}
				else
				{
//					echo $data[1].'<br />';
				}
			}elseif($row['command']=='score')
			{
				$data=explode(' ',str_replace('  ',' ',$row['params']));
				$game['players'][$data[4]]['score']=$data[0];
//				var_export($data);
			}elseif(
				($row['command']=='Defend_Flag')||
				($row['command']=='Hurt_Carrier_Defend')||
				($row['command']=='Defend_Carrier')||
				($row['command']=='Defend_Base')||
				($row['command']=='Defend_Hurt_Carrier'))   #Защита флагоносца
			{
				$game['players'][$row['params']]['td']++;
			}elseif($row['command']=='Flag_Pickup') #Флаг подобрали
			{
				$data=explode(' ',$row['params']);
				$time=$row['time']-$game['time_start'];
				#Если еще не начато новой сессии подхваченного флага - то начинаем
				$player_idx=$game['players'][$data[0]]['team_idx'];
				$flag_idx=(($player_idx-2)*-1)+1;
				#Запускаем человеку "счетчик времени владения"
				$game['players'][$data[0]]['tmp_time_pickup']=$time;
//				echo $row['time'].'-'.format_time($time).' - взял <font color="'.strtolower($game['teams_idx'][$player_idx]['title']).'">'.$game['players'][$data[0]]['name'].'</font><br />';
				if (!isset($session[$flag_idx]))
				{
					$session[$flag_idx]['start_time']=$time;
					$session[$flag_idx]['history'][]=format_time($time).' - взял <font color="'.strtolower($game['teams_idx'][$player_idx]['title']).'">'.$game['players'][$data[0]]['name'].'</font>';
					if (isset($session[flip_team($flag_idx)]))
						$session[flip_team($flag_idx)]['history'][]='<font class="gray">'.format_time($time).' - взял <font class="'.strtolower($game['teams_idx'][$player_idx]['title']).'">'.$game['players'][$data[0]]['name'].'</font></font>';
					$session[$flag_idx]['carrier']=$data[0];
					$game['players'][$data[0]]['pickup_from_base']++;
				}else
				{
					$session[$flag_idx]['history'][]=format_time($time).' - подобрал <font color="'.strtolower($game['teams_idx'][$player_idx]['title']).'">'.$game['players'][$data[0]]['name'].'</font>';
					if (isset($session[flip_team($flag_idx)]))
						$session[flip_team($flag_idx)]['history'][]='<font class="gray">'.format_time($time).' - подобрал <font class="'.strtolower($game['teams_idx'][$player_idx]['title']).'">'.$game['players'][$data[0]]['name'].'</font></font>';
					$session[$flag_idx]['carrier']=$data[0];
					$game['players'][$data[0]]['pickup_from_map']++;
				}
			}elseif($row['command']=='Flag_Return') #Флаг вернули
			{
				$time=$row['time']-$game['time_start'];
				$player_idx=$game['players'][$data[0]]['team_idx'];
				#Записываем игроку возврат флага
				$game['players'][$data[0]]['returned']++;
//				echo format_time($time).' - вернул <font color="'.strtolower($game['teams_idx'][$player_idx]['title']).'">'.$game['players'][$data[0]]['name'].'</font>'.'<br />';
				#Возврат флага
				$session[$player_idx]['end_time']=$time;
				$session[$player_idx]['duration']=$time-$session[$player_idx]['start_time'];
				$session[$player_idx]['history'][]=format_time($time).' - вернул <font class="'.strtolower($game['teams_idx'][$player_idx]['title']).'">'.$game['players'][$data[0]]['name'].'</font>';
				#Добавляем событие противоположному флагу, если он активен
				if (isset($session[flip_team($player_idx)]))
					$session[flip_team($player_idx)]['history'][]='<font class=gray>'.format_time($time).' - вернул <font class="'.strtolower($game['teams_idx'][$player_idx]['title']).'">'.$game['players'][$data[0]]['name'].'</font></font>';
				$session[$player_idx]['history'][]='Период: '.format_time($session[$player_idx]['duration']);
				$session[$player_idx]['mode']='returned';
				$global_sessions[$player_idx][]=$session[$player_idx];
				unset($session[$player_idx]);
			}elseif($row['command']=='Flag_Capture') #Флаг принесли к себе
			{
				$time=$row['time']-$game['time_start'];
				$player_idx=$game['players'][$data[0]]['team_idx'];
				$flag_idx=(($player_idx-2)*-1)+1;
				$game['players'][$data[0]]['captured']++;
				#Останавливаем игроку счетчик времени владения флагом
				$game['players'][$data[0]]['time_pickup']+=$time-$game['players'][$data[0]]['tmp_time_pickup'];
				#Установка флага
				$session[$flag_idx]['end_time']=$time;
				$session[$flag_idx]['duration']=$time-$session[$flag_idx]['start_time'];
				$session[$flag_idx]['history'][]=format_time($time).' - донес <font color="'.strtolower($game['teams_idx'][$player_idx]['title']).'">'.$game['players'][$data[0]]['name'].'</font>';
				$session[$flag_idx]['history'][]='Период: '.format_time($session[$flag_idx]['duration']);
				$session[$flag_idx]['mode']='captured';
				$global_sessions[$flag_idx][]=$session[$flag_idx];
				unset($session[$flag_idx]);
			}elseif($row['command']=='ClientDisconnect')
			{
				if (!$allow_disconnects)
				{
					$time=$row['time']-$game['time_start'];
					$disconnects[]=array('time'=>format_time($time),'who'=>$game['players'][$row['params']]['name'],'team_idx'=>$game['players'][$row['params']]['team_idx']);
				}
			}
		}
		#Вывод данных
		echo '<h1>'.rawurldecode($_GET['datetime']).', '.$game['params']['mapname'].', '.$game_types[$game['params']['g_gametype']].'</h1>';
		#echo '<h2>Настройки</h2>';
		echo 'Продолжительность: <b>'.str_pad((integer)($game['time_duration']/60),2,0,STR_PAD_LEFT).':'.str_pad($game['time_duration']-(integer)($game['time_duration']/60)*60,2,0,STR_PAD_LEFT).'</b>'.(!$game_terminated?' <font color=red><b>Игра прервана досрочно</b></font>':'').'<br />';
		echo '<br />';
		echo '
		<script language="JavaScript">
		function switch_block(obj)
		{
			$("#block_summary").hide();
			$("#block_kills").hide();
			$("#block_flags").hide();
			$("#"+$(obj).attr("className")).show();

			$("#button_summary").attr("disabled","");
			$("#button_kills").attr("disabled","");
			$("#button_flags").attr("disabled","");
			$(obj).attr("disabled","disabled");
		}
		</script>
		';
		echo '<button id="button_summary" disabled="disabled" onclick="switch_block(this)" class="block_summary">Сводка</button>&nbsp;<button id="button_kills" onclick="switch_block(this)" class="block_kills">Кто кого</button>&nbsp;<button id="button_flags" onclick="switch_block(this)" class="block_flags" '.($game['params']['g_gametype']!=4?'style="display:none"':'').'>Флаги</button>';
		$players_unsorted=$game['players'];
		#Блок совокупной статистики
		echo '<div id="block_summary">';
		@include($_SERVER['DOCUMENT_ROOT'].'/inc_summary.phtml');
		echo '</div>';
		#Блок статистики "Кто кого убил"
		echo '<div id="block_kills" style="display:none">';
		@include($_SERVER['DOCUMENT_ROOT'].'/inc_kills.phtml');
		echo '</div>';
		echo '<div id="block_flags" style="display:none">';
		@include($_SERVER['DOCUMENT_ROOT'].'/inc_ctf.phtml');
		echo '</div>';
		fclose($log);
	}
	function summary_sort($value0,$value1) #Первичная сортировка - по score, вторичная - по kill, третичная - по death
	{
		if ($value0['score']==$value1['score'])
		{
			if ($value0['kill']>$value1['kill'])
			{
				return -1;
			}elseif($value0['kill']<$value1['kill'])
			{
				return 1;
			}else
			{
				if ($value0['death']<$value1['death'])
				{
					return -1;
				}elseif($value0['death']>$value1['death'])
				{
					return 1;
				}else
				{
					return 0;
				}
			}
		}elseif($value0['score']>$value1['score'])
		{
			return -1;
		}else
		{
			return 1;
		}
	}
?>
</td></tr></table>
<script language="JavaScript" type="text/javascript" src="/js/tooltips.js"></script>
</body>
</html>