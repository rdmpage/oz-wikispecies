<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/lib.php');
require_once (dirname(__FILE__) . '/couchsimple.php');

//----------------------------------------------------------------------------------------
function process_references ($obj, $force = false)
{
	global $config;
	global $couch;
	
	// Call external service to parse reference
	$n = count($obj->references);
	for ($i = 0; $i < $n; $i++)
	{
		$string = $obj->references[$i]->string;

		$url = $config['parser_url'] . '/parse?string=' . urlencode($string);

		$json = get($url);
		if ($json != '')
		{
			$csl = json_decode($json);
	
			$ignore = true;
	
			if (isset($csl->unstructured))
			{
				$ignore = false;
				if ($csl->unstructured == '')
				{
					$ignore = true;
				}
			}

			if (!$ignore)
			{					
	
				$obj->references[$i]->csl = $csl;
			}
		}
	}	
		
	print_r($obj);

		
	if (1)
	{
		
		$go = true;
	
		// Check whether this record already exists (i.e., have we done this object already?)
		$exists = $couch->exists($obj->_id);

		if ($exists)
		{
			echo $obj->_id . " exists\n";
			$go = false;

			if ($force)
			{
				echo "[forcing]\n";
				$couch->add_update_or_delete_document(null, $obj->_id, 'delete');
				$go = true;		
			}
		}

		if ($go)
		{
			// Do we want to attempt to add any identifiers here, such as DOIs?
			$resp = $couch->send("PUT", "/" . $config['couchdb_options']['database'] . "/" . urlencode($obj->_id), json_encode($obj));
			var_dump($resp);					
		}	
	}
		
	
}		