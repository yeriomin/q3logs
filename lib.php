<?
include_once($_SERVER['DOCUMENT_ROOT'].'/t_xml.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/phpQuery-onefile.php');
$month_str[1]='января';
$month_str[2]='февраля';
$month_str[3]='марта';
$month_str[4]='апреля';
$month_str[5]='мая';
$month_str[6]='июня';
$month_str[7]='июля';
$month_str[8]='августа';
$month_str[9]='сентября';
$month_str[10]='октября';
$month_str[11]='ноября';
$month_str[12]='декабря';
function date2str($input)
{
	global $month_str;
	$out=substr($input,8,2).' '.$month_str[(integer)substr($input,5,2)].' '.substr($input,0,4);
	return $out;
}
function date2str_ddmmyyyy($input)
{
	$out=substr($input,8,2).'.'.substr($input,5,2).'.'.substr($input,0,4);
	return $out;
}
function date2time($input)
{
	return substr($input,11,8);
}
function get_row($file)
{
	$row=fgets($file,4096);
	preg_match_all("/([0-9]+\.[0-9]) ([^:]+):(.*)/",$row,$matches);
	unset($row);
	$row['time']=$matches[1][0];
	$row['command']=$matches[2][0];
	$row['params']=trim($matches[3][0]);
	return $row;
}
function format_time($seconds)
{
	return str_pad((integer)($seconds/60),2,0,STR_PAD_LEFT).':'.str_pad(number_format($seconds-(integer)($seconds/60)*60,1,'.',''),4,0,STR_PAD_LEFT);
}
function get_weapon_name($input)
{
	if ($input=='MOD_ROCKET' || $input=='MOD_ROCKET_SPLASH')
	{
		return 'Ракета';
	}elseif ($input=='MOD_PLASMA' || $input=='MOD_PLASMA_SPLASH')
	{
		return 'Плазма';
	}elseif ($input=='MOD_BFG' || $input=='MOD_BFG_SPLASH')
	{
		return 'BFG';
	}elseif ($input=='MOD_SHOTGUN')
	{
		return 'Ружье';
	}elseif ($input=='MOD_GRENADE' || $input=='MOD_GRENADE_SPLASH')
	{
		return 'Грена';
	}elseif ($input=='MOD_MACHINEGUN')
	{
		return 'Пулемет';
	}elseif ($input=='MOD_GAUNTLET')
	{
		return 'Пила';
	}elseif ($input=='MOD_RAILGUN')
	{
		return 'Рельса';
	}elseif ($input=='MOD_LIGHTNING')
	{
		return 'Лайтинг';
	}elseif ($input=='MOD_TELEFRAG')
	{
		return 'Телефраг';
	}elseif ($input=='MOD_SWITCHTEAM')
	{
		return 'Смена команды';
	}else
	{
		return $input;
	}
}
function flip_team($team_idx)
{
	if ($team_idx==1)
	{
		return 2;
	}else
	{
		return 1;
	}
}
function get_games_list()
{
	global $game_types;
	$filesize=filesize(GAMESLOG);
	$content=file_exists(GAMELIST)?file_get_contents(GAMELIST):'';
	$page=phpQuery::newDocumentHTML(mb_convert_encoding($content,'html-entities','utf-8'),'utf-8');
	$xml_filesize=(integer)($page['games']->attr("filesize"));
	if ($xml_filesize!=$filesize)
	{
		$log=fopen(GAMESLOG,'r');
		while (!feof($log))
		{
			$filepos=ftell($log);
			$row=get_row($log);
			if ($row['command']=='InitGame')
			{
				#Получаем название карты
				$params=explode('\\',$row['params']);
				unset($params[0]);
				unset($init_params);
				for ($i=1;$i<=count($params)/2;$i++)
				{
					$init_params[$params[$i*2-1]]=$params[$i*2];
				}
				#Получаем время
				$row_ServerTime=get_row($log);
				$row_Game_Start=get_row($log);
				
				if ($row_Game_Start['command']=='Game_Start')
				{
					$params=explode('	',$row_ServerTime['params']);
					$date_time=preg_replace('/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/',"$1-$2-$3 $4:$5:$6",$params[0]);
					$menu[]=array(
						'offset'=>$filepos,
						'href'=>'/?game_offset='.$filepos.'&datetime='.rawurlencode(date2str_ddmmyyyy($date_time).', '.date2time($date_time)).'',
						'date'=>date2str_ddmmyyyy($date_time).', '.date2time($date_time),
						'map'=>$init_params['mapname'],
						'type'=>$game_types[$init_params['g_gametype']]
					);
				}
			}
		}
		fclose($log);
		#Сохраняем xml на будущее
		$xml = new t_xml();
		$xml->createXml('1.0','utf-8');
		$xml->startElement('root');
//		$xml->addElement('size',null,$filesize);
		$xml->startElement('games',array('filesize'=>$filesize));
		if (count($menu)>0)
		for ($i=0;$i<count($menu);$i++)
		{
			$xml->startElement('game',array('offset'=>$menu[$i]['offset'],'map'=>$menu[$i]['map'],'type'=>$menu[$i]['type']));
				$xml->addElement('href',null,$menu[$i]['href']);
				$xml->addElement('date',null,$menu[$i]['date']);
			$xml->endElement();
		}
		$xml->endElement();
		$xml->endElement();
		$xml->endXml();
		file_put_contents(GAMELIST,$xml->getXml());
	}else
	{
		foreach ($page['games game'] as $game)
		{
			$menu[]=array(
				'offset'=>pq($game)->attr("offset"),
				'href'=>str_replace("&amp;","&",pq($game)->find("href")->html()),
				'date'=>pq($game)->find("date")->html(),
				'map'=>pq($game)->attr("map"),
				'type'=>pq($game)->attr("type")
			);
		}
	}
	return $menu;
}
?>