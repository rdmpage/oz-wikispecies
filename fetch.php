<?php

error_reporting(E_ALL);

// Fetch pages direct from Wiki and extract references

require_once (dirname(__FILE__) . '/lib.php');
require_once (dirname(__FILE__) . '/store.php');


$page_names = array();

// Find pages that link to a DOI and build page list
if (0)
{
	$dois=array('10.4039/Ent112875-9');

	$dois = array('10.11646/zootaxa.3680.1.12');
	
	// from file
	if (1)
	{
		$dois = array();
		
		$filename = 'zootaxa-doi.txt';
		$filename = 'zookeys-doi.txt';
		$filename = 'doi.txt';
	
		$file_handle = fopen($filename, "r");
		while (!feof($file_handle)) 
		{
			$doi = trim(fgets($file_handle));
			$dois[] = $doi;
		}
	}	

	foreach ($dois as $doi)
	{
		$parameters = array
		(
			'action'  => 'query',
			'format'  => 'json',
			'list'	  => 'exturlusage',
			'euquery' => 'dx.doi.org/' . urlencode($doi)
		);
	
		$url = 'https://species.wikimedia.org/w/api.php?' . http_build_query($parameters);
	
		$json = get($url);
	
		if ($json != '')
		{
			$obj = json_decode($json);
		
			print_r($obj);
		
			foreach ($obj->query->exturlusage as $hit)
			{
				if ($hit)
				{
					$page_names[] = str_replace(' ', '_', $hit->title);
				}
			}
		}

	}

	print_r($page_names);
	
	file_put_contents('pages.txt', join("\n",$page_names));
}

/*
$page_names = array('Kylie_S._Stumkat');
$page_names = array('Valerie_Todd_Davies');
$page_names = array('Renaud_Maurice_Adrien_Paulian');
$page_names = array('Pekka_T._Lehtinen');
*/

/*
$page_names = array(
'Günther_Theischinger',
'Rudy_Jocqué',
'Martin_Baehr',
'Barbara_C._Baehr',
'Barbara_York_Main',
'Steven_J.B._Cooper'
);

$page_names = array(
'Gary_C.B._Poore'
);
*/

$page_names = array(
'Randall_Tobias_Schuh'
);

// Read list of page names
if (0)
{
	$page_names = array();
	
	$filename = 'pages.txt';

	$file_handle = fopen($filename, "r");
	while (!feof($file_handle)) 
	{
		$page_names[] = trim(fgets($file_handle));
	}

}

$include_transclusions = true;
//$include_transclusions = false;

$force = true;
$force = false;


while (count($page_names) > 0)
{
	$page_name = array_pop($page_names);
	
	$url = 'https://species.wikimedia.org/w/index.php?title=Special:Export&pages=' . $page_name;

	$xml = get($url);
	
	//echo $xml;

	$dom= new DOMDocument;
	$dom->loadXML($xml);
	$xpath = new DOMXPath($dom);

	$xpath->registerNamespace("wiki", "http://www.mediawiki.org/xml/export-0.10/");
	
	$obj = new stdclass;
	
	$obj->_id = $page_name;
	
	$nodeCollection = $xpath->query ("//wiki:title");
	foreach($nodeCollection as $node)
	{
		$obj->title = $node->firstChild->nodeValue;
	}	
	
	$nodeCollection = $xpath->query ("//wiki:timestamp");
	foreach($nodeCollection as $node)
	{
		$obj->timestamp = $node->firstChild->nodeValue;
	}	
		
	$nodeCollection = $xpath->query ("//wiki:text");
	foreach($nodeCollection as $node)
	{
		$obj->references = array();
		
		// get text
		$obj->text = $node->firstChild->nodeValue;		
		$lines = explode("\n", $obj->text);
		
		foreach ($lines as $line)
		{
			if (preg_match('/^\*\s+\{\{a/', $line))
			{
				// possible reference
				
				$r = trim($line);
				$r = str_replace('</text>', '', $r);
				
				$citation = new stdclass;
				$citation->string = $r;
				
				$obj->references[] = $citation;
			}	
			
			if ($include_transclusions)
			{
			
				// transcluded references
				$matched = false;
				if (!$matched)
				{
					if (preg_match('/^(\*\s+)?\{\{(?<refname>[A-Z][\p{L}]+([,\s&;[a-zA-Z]+)[0-9]{4}[a-z]?)\}\}$/u', trim($line), $m))
					{
						$refname = $m['refname'];
						$refname = str_replace(' ', '_', $refname);
						$refname = str_replace('&', '%26', $refname);	
					
						$page_names[] = 'Template:' . $refname;	
						
						$matched = true;	
					}			
				}

				if (!$matched)
				{
					if (preg_match('/^\{\{(?<refname>[A-Z][\p{L}]+(.*)\s+[0-9]{4}[a-z]?)\}\}$/u', trim($line), $m))
					{
						$refname = $m['refname'];
						$refname = str_replace(' ', '_', $refname);
						$refname = str_replace('&', '%26', $refname);	
					
						$page_names[] = 'Template:' . $refname;	
						
						$matched = true;	
					}			
				}


			}			
			
		}	
			
		// taxonomy
		if (preg_match('/== Taxonavigation ==\s+\{\{(?<parent>.*)\}\}/Uu', $obj->text, $m))
		{
			$obj->taxonavigation = $m['parent'];
		}
		
		// categories		
		if (preg_match_all('/\[\[Category:\s*(?<category>.*)\]\]/Uu', $obj->text, $m))
		{
			$obj->categories = $m['category'];
		}

		
	}
	
	//print_r($page_names);
	//exit();
	
	//print_r($obj);
	
	
	process_references($obj, $force);
									
	//$include_transclusions = false; // only do this the first time

}

?>
