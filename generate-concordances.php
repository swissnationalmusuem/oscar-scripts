<?php 
 /*****
 * Author: Ethan Gruber
 * Date: January 2021
 * Function: Process the OSCAR spreadsheet into NHMZ and DT concordance NUDS documents that redirect to OSCAR
 *****/

$data = generate_json('https://docs.google.com/spreadsheets/d/e/2PACX-1vRkGPpZKs3oCtsL6nchfA8tvoRs-tk2MJO9SZfigq7_R3zCAbHQ0N2bkqDLqwo8rxDExH0Oy6PcmJfW/pub?output=csv');

$dt = array();
$nhmz = array();
//$records = array();

foreach($data as $row){
    if (strlen($row["DT ID"]) > 0){
        $pieces = explode('|', $row['DT ID']);
        
        foreach ($pieces as $id){
            if (!array_key_exists($id, $dt)){
                $oscarID = $row['ID'];
                $dt[$id][] = $oscarID;
            } else {
                if (!in_array($oscarID, $dt[$id])){
                    $dt[$id][] = $oscarID;
                }
            }
        }
    }
    
    if (strlen($row["NHMZ ID"]) > 0){
        $pieces = explode('|', $row['NHMZ ID']);
        
        foreach ($pieces as $id){
            if (!array_key_exists($id, $nhmz)){
                $oscarID = $row['ID'];
                $nhmz[$id][] = $oscarID;
            } else {
                if (!in_array($oscarID, $nhmz[$id])){
                    $nhmz[$id][] = $oscarID;
                }
            }
        }
    }
}

foreach ($dt as $k=>$v){
    generate_nuds($k, $v, 'DT ID');
}

foreach ($nhmz as $k=>$v){
    generate_nuds($k, $v, 'NHMZ ID');
}

//functions
function generate_nuds($recordId, $array, $key){
	
	$uri_space = 'http://oscar.nationalmuseum.ch/id/';
	
	
	if (strlen($recordId) > 0){
		echo "Processing {$recordId}\n";
		$doc = new XMLWriter();
		
		//$doc->openUri('php://output');
		$doc->openUri('nuds/' . $recordId . '.xml');
		$doc->setIndent(true);
		//now we need to define our Indent string,which is basically how many blank spaces we want to have for the indent
		$doc->setIndentString("    ");
		
		$doc->startDocument('1.0','UTF-8');
		
		$doc->startElement('nuds');
			$doc->writeAttribute('xmlns', 'http://nomisma.org/nuds');
				$doc->writeAttribute('xmlns:xs', 'http://www.w3.org/2001/XMLSchema');
				$doc->writeAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
				$doc->writeAttribute('xmlns:tei', 'http://www.tei-c.org/ns/1.0');	
				$doc->writeAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
				$doc->writeAttribute('xsi:schemaLocation', 'http://nomisma.org/nuds http://nomisma.org/nuds.xsd');
				$doc->writeAttribute('recordType', 'conceptual');
			
			//control
			$doc->startElement('control');
				$doc->writeElement('recordId', $recordId);
				
				foreach ($array as $id){
				    $doc->startElement('otherRecordId');
    				    $doc->writeAttribute('semantic', 'dcterms:isReplacedBy');
    				    $doc->text($id);
				    $doc->endElement();
				    $doc->startElement('otherRecordId');
    				    $doc->writeAttribute('semantic', 'skos:exactMatch');
    				    $doc->text($uri_space . $id);
				    $doc->endElement();
				}
				
				$doc->writeElement('publicationStatus', 'deprecatedType');
				if (count($array) == 1){
				    $doc->writeElement('maintenanceStatus', 'cancelledReplaced');
				} elseif (count($array) > 1){
				    $doc->writeElement('maintenanceStatus', 'cancelledSplit');
				}

				
				$doc->startElement('maintenanceAgency');
				    $doc->writeElement('agencyName', 'Swiss National Museum');
				$doc->endElement();
				
				//maintenanceHistory
				$doc->startElement('maintenanceHistory');
					$doc->startElement('maintenanceEvent');
						$doc->writeElement('eventType', 'derived');
						$doc->startElement('eventDateTime');
							$doc->writeAttribute('standardDateTime', date(DATE_W3C));
							$doc->text(date(DATE_RFC2822));
						$doc->endElement();
						$doc->writeElement('agentType', 'machine');
						$doc->writeElement('agent', 'PHP');
						$doc->writeElement('eventDescription', 'Generated from CSV from the OSCAR folder on Google Drive.');
					$doc->endElement();
				$doc->endElement();
				
				//rightsStmt
				$doc->startElement('rightsStmt');
					$doc->writeElement('copyrightHolder', 'Swiss National Museum');
					$doc->startElement('license');
						$doc->writeAttribute('xlink:type', 'simple');
						$doc->writeAttribute('xlink:href', 'http://opendatacommons.org/licenses/odbl/');
						$doc->writeAttribute('for', 'data');
					$doc->endElement();
				$doc->endElement();
				
				//semanticDeclaration
				$doc->startElement('semanticDeclaration');
					$doc->writeElement('prefix', 'dcterms');
					$doc->writeElement('namespace', 'http://purl.org/dc/terms/');
				$doc->endElement();
				
				$doc->startElement('semanticDeclaration');
					$doc->writeElement('prefix', 'nmo');
					$doc->writeElement('namespace', 'http://nomisma.org/ontology#');
				$doc->endElement();
				
				$doc->startElement('semanticDeclaration');
					$doc->writeElement('prefix', 'skos');
					$doc->writeElement('namespace', 'http://www.w3.org/2004/02/skos/core#');
				$doc->endElement();
			//end control
			$doc->endElement();
		
			//start descMeta
			$doc->startElement('descMeta');
		
			//get title
			if ($key == 'DT ID'){
			    $pieces = explode('.', $recordId);
			    
			    switch ($pieces[1]){
			        case '17':
			            $vol = 'Divo - Tobler, 17. Jh.';
			            break;
			        case '18':
			            $vol = 'Divo - Tobler, 18. Jh.';
			            break;
			        case '19-20':
			            $vol = 'Divo - Tobler, 19. Jh. / 20 Jh.';
			            break;
			    }
			    
			    $title = $vol . ' ' . $pieces[2];
			    
			} elseif ($key == 'NHMZ ID'){
			    $pieces = explode('-', $recordId);
			    
			    switch ($pieces[0]){
			        case 'nhmz.1':
			            $vol = 'NHMZ 2011, Bd 1';
			            break;
			        case 'nhmz.2':
			            $vol = 'NHMZ 2011, Bd 2';
			            break;
			    }
			    
			    $title = $vol . ' ' . $pieces[1];
			}
			
			//title: English and German
			$doc->startElement('title');
    			$doc->writeAttribute('xml:lang', 'en');
    			$doc->text($title);
			$doc->endElement();
			
			$doc->startElement('title');
    			$doc->writeAttribute('xml:lang', 'de');
    			$doc->text($title);
			$doc->endElement();
			
			
			/***** TYPEDESC *****/
			$doc->startElement('typeDesc');
			
				//objectType
				$doc->startElement('objectType');
					$doc->writeAttribute('xlink:type', 'simple');
					$doc->writeAttribute('xlink:href', 'http://nomisma.org/id/coin');
					$doc->text('Coin');
				$doc->endElement();
				
				//Type Series should be explicit
				$doc->startElement('typeSeries');
					$doc->writeAttribute('xlink:type', 'simple');
					if ($key == 'ID'){
						$doc->writeAttribute('xlink:href', 'http://nomisma.org/id/oscar');
						$doc->text('OSCAR');
					}
					if ($key == 'DT ID'){
						$doc->writeAttribute('xlink:href', 'http://nomisma.org/id/divo-tobler');
						$doc->text('Divo - Tobler (Die Münzen der Schweiz)');
					}
					if ($key == 'NHMZ ID'){
						$doc->writeAttribute('xlink:href', 'http://nomisma.org/id/nhmz');
						$doc->text('NHMZ (Der neue HMZ-Katalog)');
					}
				$doc->endElement();
				
				//end typeDesc
				$doc->endElement();
				
			//end descMeta
			$doc->endElement();		
		//close NUDS
		$doc->endElement();
		
		//close file
		$doc->endDocument();
		$doc->flush();
	}
}


 /***** FUNCTIONS *****/
function processUri($uri){
	GLOBAL $nomismaUris;
	$content = array();
	$uri = trim($uri);
	$type = '';
	$label = '';
	$node = '';
	
	//if the key exists, then formulate the XML response
	if (array_key_exists($uri, $nomismaUris)){
		$type = $nomismaUris[$uri]['type'];
		$label = $nomismaUris[$uri]['label'];
		if (isset($nomismaUris[$uri]['parent'])){
			$parent = $nomismaUris[$uri]['parent'];
		}
	} else {
		//if the key does not exist, look the URI up in Nomisma
		$pieces = explode('/', $uri);
		$id = $pieces[4];
		if (strlen($id) > 0){
			$uri = 'http://nomisma.org/id/' . $id;
			$file_headers = @get_headers($uri);
			
			//only get RDF if the ID exists
			if ($file_headers[0] == 'HTTP/1.1 200 OK'){
				$xmlDoc = new DOMDocument();
				$xmlDoc->load('http://nomisma.org/id/' . $id . '.rdf');
				$xpath = new DOMXpath($xmlDoc);
				$xpath->registerNamespace('skos', 'http://www.w3.org/2004/02/skos/core#');
				$xpath->registerNamespace('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
				$type = $xpath->query("/rdf:RDF/*")->item(0)->nodeName;
				$label = $xpath->query("descendant::skos:prefLabel[@xml:lang='en']")->item(0)->nodeValue;
				
				if (!isset($label)){
					echo "Error with {$id}\n";
				}
				
				//get the parent, if applicable
				$parents = $xpath->query("descendant::org:organization");
				if ($parents->length > 0){
					$nomismaUris[$uri] = array('label'=>$label,'type'=>$type, 'parent'=>$parents->item(0)->getAttribute('rdf:resource'));
					$parent = $parents->item(0)->getAttribute('rdf:resource');
				} else {
					$nomismaUris[$uri] = array('label'=>$label,'type'=>$type);
				}
			} else {
				//otherwise output the error
				echo "Error: {$uri} not found.\n";
				$nomismaUris[$uri] = array('label'=>$uri,'type'=>'nmo:Mint');
			}
		}
	}
	switch($type){
		case 'nmo:Mint':
		case 'nmo:Region':
			$content['element'] = 'geogname';
			$content['label'] = $label;
			if (isset($parent)){
				$content['parent'] = $parent;
			}
			break;
		case 'nmo:Material':
			$content['element'] = 'material';
			$content['label'] = $label;
			break;
		case 'nmo:Denomination':
			$content['element'] = 'denomination';
			$content['label'] = $label;
			break;
		case 'nmo:Manufacture':
			$content['element'] = 'manufacture';
			$content['label'] = $label;
			break;
		case 'nmo:ObjectType':
			$content['element'] = 'objectType';
			$content['label'] = $label;
			break;
		case 'rdac:Family':
			$content['element'] = 'famname';
			$content['label'] = $label;
			break;
		case 'foaf:Organization':
		case 'foaf:Group':
		case 'nmo:Ethnic':
			$content['element'] = 'corpname';
			$content['label'] = $label;
			break;
		case 'foaf:Person':
			$content['element'] = 'persname';
			$content['label'] = $label;
			if (isset($parent)){
				$content['parent'] = $parent;
			}
			break;
		case 'crm:E4_Period':
			$content['element'] = 'periodname';
			$content['label'] = $label;
			break;
		default:
			$content['element'] = 'ERR';
			$content['label'] = $label;
	}
	return $content;
}

function get_date_textual($year){
    $textual_date = '';
    //display start date
    if($year < 0){
        $textual_date .= abs($year) . ' BC';
    } elseif ($year > 0) {
        if ($year <= 600){
            $textual_date .= 'AD ';
        }
        $textual_date .= $year;
    }
    return $textual_date;
}

//pad integer value from Filemaker to create a year that meets the xs:gYear specification
function number_pad($number,$n) {
	if ($number > 0){
		$gYear = str_pad((int) $number,$n,"0",STR_PAD_LEFT);
	} elseif ($number < 0) {
		$gYear = '-' . str_pad((int) abs($number),$n,"0",STR_PAD_LEFT);
	}
	return $gYear;
}

function generate_json($doc){
	$keys = array();
	$geoData = array();
	
	$data = csvToArray($doc, ',');
	
	// Set number of elements (minus 1 because we shift off the first row)
	$count = count($data) - 1;
	
	//Use first row for names
	$labels = array_shift($data);
	
	foreach ($labels as $label) {
		$keys[] = $label;
	}
	
	// Bring it all together
	for ($j = 0; $j < $count; $j++) {
		$d = array_combine($keys, $data[$j]);
		$geoData[$j] = $d;
	}
	return $geoData;
}

// Function to convert CSV into associative array
function csvToArray($file, $delimiter) {
	if (($handle = fopen($file, 'r')) !== FALSE) {
		$i = 0;
		while (($lineArray = fgetcsv($handle, 4000, $delimiter, '"')) !== FALSE) {
			for ($j = 0; $j < count($lineArray); $j++) {
				$arr[$i][$j] = $lineArray[$j];
			}
			$i++;
		}
		fclose($handle);
	}
	return $arr;
}

?>