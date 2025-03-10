<?php
define('LASTUPDATED', dirname(__FILE__).'../../cache/lastupdated.txt');
define('CACHE', dirname(__FILE__).'../../cache/cache.html');

function displayNames($names=array(), $prefix=null, $suffix=null, $output=null){
	if(!count($names)) return null;
	sort($names);
	foreach($names as $name){
		$output .= $prefix.htmlentities(revertInvertedName($name)).$suffix;
	}
	return $output;
}

function revertInvertedName($name, $output=null){
	if(!$name) return null;
	$parts = explode(',', $name);
	foreach(array_reverse($parts) as $p){
		$output .= trim($p).' ';
	}
	return $output;
}

function hasTypeCounts($itemTypes=array(), $output=0){
	if(!$itemTypes) return;
	foreach($itemTypes as $type){
		$output = $output + (int)$type['total'];
	}
	return (int)$output;
}

function item_data_dashboard($includeNames=false, $output=array()){	
	$items = get_records('Item',array(),false); // all items
	$itemTypes = array();
	$hasTranscript = 0;
	$people = array();
	$peopleAskers = array();
	$peopleAnswerers = array();
	$peopleOther = array();
	$cameBackForMore = array(); // return-interviewees
	$becameAPro = array(); // return-interviewers
	foreach($items as $item){
		// process transcript status
		if($tr = metadata($item,array('Item Type Metadata','Transcription'))){
			if(strlen($tr)) $hasTranscript++;
		}
		// process item type
		if($type = $item->getItemType()){
			$name = $type->name;
			$total = $type->totalItems();
			if(!array_key_exists($name, $itemTypes)){
				$itemTypes[$name]['total'] = $total;
			}
		}
		// process contributors
		if($co = metadata($item,array('Dublin Core','Contributor'), 'all')){
			foreach($co as $c2){ // each element string
				foreach(explode(';', $c2) as $contributor){ // each semicolon-separated string
					$contributor = trim($contributor);
					$simplifiedName = trim(preg_replace("/\([^)]+\)/","", $contributor)); // remove role parenthetical
					if(!in_array($contributor, $people)){
						if(strlen($contributor)){
							if(stripos($contributor, 'interviewee') !== false){
								if(!in_array($simplifiedName, $peopleAskers)
								&& !in_array($simplifiedName, $peopleOther)){
									$peopleAnswerers[] = $simplifiedName;
								}
							}elseif(stripos($contributor, 'interviewer') !== false){
								if(!in_array($simplifiedName, $peopleAnswerers)
								&& !in_array($simplifiedName, $peopleOther)){
									$peopleAskers[] = $simplifiedName;
								}
							}else{
								if(!in_array($simplifiedName, $peopleAnswerers)
								&& !in_array($simplifiedName, $peopleAskers)){
									$peopleOther[] = $simplifiedName;
								}
							}
							
							$people[] = $contributor;
						}
					}else{
						if(strlen($contributor)){
							if(stripos($contributor, 'interviewee') !== false){
								if(!in_array($simplifiedName, $cameBackForMore)){
									$cameBackForMore[] = $simplifiedName;
								}
							}
							if(stripos($contributor, 'interviewer') !== false){
								if(!in_array($simplifiedName, $becameAPro)){
									$becameAPro[] = $simplifiedName;
								}
							}
						}
					}
				}
			}
		}
	}
	$output['items'] = count($items);
	$output['types'] = $itemTypes;
	$output['notype'] = count($items) - hasTypeCounts($itemTypes);
	$output['people'] = count($people);
	if($includeNames){
		$output['names'] = array_merge($peopleAskers, $peopleAnswerers, $peopleOther);
	}
	$output['interviewers'] = count($peopleAskers);
	$output['interviewees'] = count($peopleAnswerers);
	$output['other'] = count($peopleOther);
	$output['multirole'] = count($people) - count($peopleAskers) - count($peopleAnswerers) - count($peopleOther);
	$output['transcribed'] = $hasTranscript;
	$output['returninterviewees'] = count($cameBackForMore);
	$output['returninterviewers'] = count($becameAPro);
		
	return $output;
}
function cacheAge($now){
	if(!$now){
		date_default_timezone_set("America/New_York");
		$now = time();
	}
	$lastupdated = file_get_contents(LASTUPDATED);
	$ageseconds =  $now - $lastupdated; // compare unix timestamps
	return $ageseconds;
}

function project_stats_html($data, $html = null){
	if(!$data) return;
	$cachenotify = 'Last cached: '.date("Y-m-d h:i:s A", $now);
	$percentTranscribed = round($data['transcribed'] / $data['items'] * 100, 2).'%';
	$html .= '<section class="panel five columns alpha">';
		$html .= '<h2>'.__('Project Statistics').'</h2><br>';
		$html .= '<h3>'.__('Records').'</h3>';
		$html .= '<ul>';
			$html .= '<li>'.__('%s Total Items', number_format($data['items'])).' <sup>*</sup><ul>';
			foreach($data['types'] as $key=>$t){
				$paren = null;
				if($key == 'Oral History' && $data['transcribed'] > 0){
				}
				$html .= '<li>'.number_format($t['total']).' '.$key.'</li>';
			}
			if($data['notype'] > 0){
				$html .= '<li>'.__('%s No Type Assigned', number_format($data['notype'])).' <sup>†</sup></li>';
			}
			$html .= '</ul></li>';
		$html .= '</ul>';
		$html .= '<div class="recent-row"></div>'; // bottom-border cheat
		$html .= '<h3>'.__('People').'</h3>';
		$html .= '<ul>';
			$html .= '<li>'.__('%s Total Participants', number_format($data['people'] - $data['multirole'])).' <sup>‡</sup><ul>';
				$html .= '<li>'.__('%s Interviewees', number_format($data['interviewees'])).'</li>';
				$html .= '<li>'.__('%s Interviewers', number_format($data['interviewers'])).'</li>';
				$html .= '<li>'.__('%s Other Roles', number_format($data['other'])).'</li>';
			$html .= '</ul></li>';
		$html .= '</ul>';
		$html .= '<div class="recent-row"></div>';
		$html .= '<p>';
		$html .= '* <em>'.__('%1s items (%2s of the total) have been transcribed, not necessarily accounting for item type (i.e. podcast versus oral history).', number_format($data['transcribed']), $percentTranscribed).'</em><br>';
		$html .= '† <em>'.__('Interview items are only assigned the Oral History item type after they have been transcribed.').'</em><br>';
		$html .= '‡ <em>'.__('Note that misspellings and other metadata errors will effect the accuracy of these numbers. Totals have been rectified to account for the %1s individuals documented as having participated in more than one role over time – these individuals are counted once in the total as expected and once in a single role-subtotal (e.g. if a particpant was both an interviewee and an interviewer, they will only be counted as an interviewee). A total of %2s interviewees returned to be interviewed again. A total of %3s interviewers have conducted more than one interview.', number_format($data['multirole']), number_format($data['returninterviewees']), number_format($data['returninterviewers']) ).'</em><br>';
		$html .= '</p>';
		$html .= '<p><small>&#9432; <em>'.$cachenotify.'</em></small></p>';
	$html .= '</section>';
	return $html;
}

function dashboard_init($cacheexpire_seconds=3600, $html=null){
	date_default_timezone_set("America/New_York");
	$now = time();
	$age = cacheAge($now);
	if($age < $cacheexpire_seconds){ // 3600 = 60 minutes unix epoch
		// fetch and return cached html
		return file_get_contents(CACHE);
	}else{
		// (re)create html
		$data = item_data_dashboard();
		$html = project_stats_html($data);
		// update timestamp
		file_put_contents(LASTUPDATED, $now);
		// update cache
		file_put_contents(CACHE, $html);
		// return html for js (see admin_footer)
		return $html;
	}


}
