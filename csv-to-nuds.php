<?php 
 /*****
 * Author: Ethan Gruber
 * Date: January 2021
 * Function: Process the OSCAR spreadsheet into NUDS document
 *****/

$data = generate_json('https://docs.google.com/spreadsheets/d/e/2PACX-1vRkGPpZKs3oCtsL6nchfA8tvoRs-tk2MJO9SZfigq7_R3zCAbHQ0N2bkqDLqwo8rxDExH0Oy6PcmJfW/pub?output=csv');

$nomismaUris = array();
//$records = array();

$count = 1;
foreach($data as $row){
	
	//process OSCAR first	
	generate_nuds($row, $count);
	
	$count++;
}

//functions
function generate_nuds($row, $count){
	
	$uri_space = 'http://oscar.nationalmuseum.ch/id/';
	
	$recordId = trim($row['ID']);
	
	
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
				
				//concordances with other published corpora
				if (strlen($row['DT ID']) > 0){
				    $pieces = explode('|', $row['DT ID']);
			        foreach ($pieces as $id){
			            $doc->startElement('otherRecordId');
				            $doc->writeAttribute('semantic', 'dcterms:replaces');
				            $doc->text($id);
			            $doc->endElement();
			            $doc->startElement('otherRecordId');
				            $doc->writeAttribute('semantic', 'skos:exactMatch');
				            $doc->text($uri_space . $id);
			            $doc->endElement();
				    }
				}
				
				if (strlen($row['NHMZ ID']) > 0){
				    $pieces = explode('|', $row['NHMZ ID']);
				    foreach ($pieces as $id){
				        $doc->startElement('otherRecordId');
    				        $doc->writeAttribute('semantic', 'dcterms:replaces');
    				        $doc->text($id);
				        $doc->endElement();
				        $doc->startElement('otherRecordId');
    				        $doc->writeAttribute('semantic', 'skos:exactMatch');
    				        $doc->text($uri_space . $id);
				        $doc->endElement();
				    }
				}
				
				//insert a sortID
				$doc->startElement('otherRecordId');
    				$doc->writeAttribute('localType', 'sortId');
    				$doc->text(number_pad(intval($count), 5));
				$doc->endElement();
				
				$doc->writeElement('publicationStatus', 'approved');
				$doc->writeElement('maintenanceStatus', 'derived');

				
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
		
			//title: English and German
			$doc->startElement('title');
    			$doc->writeAttribute('xml:lang', 'en');
    			$doc->text('OSCAR ' . str_replace('oscar.', '', $recordId));
			$doc->endElement();
			
			$doc->startElement('title');
				$doc->writeAttribute('xml:lang', 'de');
				$doc->text('OSCAR ' . str_replace('oscar.', '', $recordId));
			$doc->endElement();
			
			/***** NOTES *****/
			if (strlen(trim($row['Variationen der Darstellung'])) > 0 || strlen(trim($row['notes (Bemerkungen)'])) > 0){
				$doc->startElement('noteSet');
				if (strlen(trim($row['Variationen der Darstellung'])) > 0){
					$doc->startElement('note');
						$doc->writeAttribute('xml:lang', 'de');
						$doc->text(trim($row['Variationen der Darstellung']));
					$doc->endElement();
				}
				if (strlen(trim($row['notes (Bemerkungen)'])) > 0){
					$doc->startElement('note');
						$doc->writeAttribute('xml:lang', 'de');
						$doc->text(trim($row['notes (Bemerkungen)']));
					$doc->endElement();
				}
				$doc->endElement();
			}
			
			/***** TYPEDESC *****/
			$doc->startElement('typeDesc');
			
				//objectType
				$doc->startElement('objectType');
					$doc->writeAttribute('xlink:type', 'simple');
					$doc->writeAttribute('xlink:href', 'http://nomisma.org/id/coin');
					$doc->text('Coin');
				$doc->endElement();
				
				//sort dates
				if (strlen($row['Start Date']) > 0 || strlen($row['End Date']) > 0){
					if (($row['Start Date'] == $row['End Date']) || (strlen($row['Start Date']) > 0 && strlen($row['End Date']) == 0)){
					    if (is_numeric(trim($row['Start Date']))){
					        
					        $fromDate = intval(trim($row['Start Date']));					        
					        $doc->startElement('date');
    					        $doc->writeAttribute('standardDate', number_pad($fromDate, 4));
    					        $doc->text(get_date_textual($fromDate));
					        $doc->endElement();
					    }
					} else {
						$fromDate = intval(trim($row['Start Date']));
						$toDate= intval(trim($row['End Date']));
						
						//only write date if both are integers
						if (is_int($fromDate) && is_int($toDate)){
						    $doc->startElement('dateRange');
    						    $doc->startElement('fromDate');
    						      $doc->writeAttribute('standardDate', number_pad($fromDate, 4));
    						      $doc->text(get_date_textual($fromDate));
    						    $doc->endElement();
    						    $doc->startElement('toDate');
    						      $doc->writeAttribute('standardDate', number_pad($toDate, 4));
    						      $doc->text(get_date_textual($toDate));
    						    $doc->endElement();
						    $doc->endElement();
						}
					}
				}
				
				/*if (strlen($row['Denomination URI']) > 0){
					$vals = explode('|', $row['Denomination URI']);
					foreach ($vals as $val){
						if (substr($val, -1) == '?'){
							$uri = substr($val, 0, -1);
							$uncertainty = true;
							$content = processUri($uri);
						} else {
							$uri =  $val;
							$uncertainty = false;
							$content = processUri($uri);
						}
						
						$doc->startElement($content['element']);
						$doc->writeAttribute('xlink:type', 'simple');
						$doc->writeAttribute('xlink:href', $uri);
						if($uncertainty == true){
							$doc->writeAttribute('certainty', 'uncertain');
						}
						$doc->text($content['label']);
						$doc->endElement();
					}
				}*/
				
				if (strlen($row['denomination']) > 0){
					$doc->startElement('denomination');
						$doc->text(trim($row['denomination']));
					$doc->endElement();
				}
				if (strlen($row['denomination 2']) > 0){
					$doc->startElement('denomination');
					   $doc->text(trim($row['denomination 2']));
					$doc->endElement();
				}
				
				//manufacture: if the SC no. includes P, it is plated, otherwise struck
				/*if (strpos($row['SC no.'], 'P') !== FALSE){
					$doc->startElement('manufacture');
						$doc->writeAttribute('xlink:type', 'simple');
						$doc->writeAttribute('xlink:href', 'http://nomisma.org/id/plated');
						$doc->text('Plated');
					$doc->endElement();
				} else {
					$doc->startElement('manufacture');
						$doc->writeAttribute('xlink:type', 'simple');
						$doc->writeAttribute('xlink:href', 'http://nomisma.org/id/struck');
						$doc->text('Struck');
					$doc->endElement();
				}*/
				
				
				if (strlen($row['Material URI']) > 0){
					$vals = explode('|', $row['Material URI']);
					foreach ($vals as $val){
						if (substr($val, -1) == '?'){
							$uri = substr($val, 0, -1);
							$uncertainty = true;
							$content = processUri($uri);
						} else {
							$uri =  $val;
							$uncertainty = false;
							$content = processUri($uri);
						}
						
						$doc->startElement($content['element']);
							$doc->writeAttribute('xlink:type', 'simple');
							$doc->writeAttribute('xlink:href', $uri);
							if($uncertainty == true){
								$doc->writeAttribute('certainty', 'uncertain');
							}
							$doc->text($content['label']);
						$doc->endElement();
					}
				}
				
				//authority
				if (strlen($row['mintmaster']) > 0 || strlen($row['mintmaster 2']) > 0 || strlen($row['ruler']) > 0 || strlen($row['state']) > 0){
					$doc->startElement('authority');
						/*if (strlen($row['Authority URI']) > 0){
							$vals = explode('|', $row['Authority URI']);
							foreach ($vals as $val){
								if (substr($val, -1) == '?'){
									$uri = substr($val, 0, -1);
									$uncertainty = true;
									$content = processUri($uri);
								} else {
									$uri =  $val;
									$uncertainty = false;
									$content = processUri($uri);
								}
								$role = 'authority';
								
								$doc->startElement($content['element']);
									$doc->writeAttribute('xlink:type', 'simple');
									$doc->writeAttribute('xlink:role', $role);
									$doc->writeAttribute('xlink:href', $uri);
									if($uncertainty == true){
										$doc->writeAttribute('certainty', 'uncertain');
									}
									$doc->text($content['label']);
								$doc->endElement();
							}
						}*/
					
    					if (strlen($row['ruler']) > 0){
    					    $doc->startElement('persname');
        					    $doc->writeAttribute('xlink:role', 'ruler');
        					    $doc->text(trim($row['ruler']));
    					    $doc->endElement();
    					}
						if (strlen($row['state']) > 0){
							$doc->startElement('corpname');
								$doc->writeAttribute('xlink:role', 'state');
								$doc->text(trim($row['state']));
							$doc->endElement();
						}
						if (strlen($row['mintmaster']) > 0){
						    $doc->startElement('persname');
    						    $doc->writeAttribute('xlink:role', 'issuer');
    						    $doc->text(trim($row['mintmaster']));
						    $doc->endElement();
						}
						if (strlen($row['mintmaster 2']) > 0){
						    $doc->startElement('persname');
    						    $doc->writeAttribute('xlink:role', 'issuer');
    						    $doc->text(trim($row['mintmaster 2']));
						    $doc->endElement();
						}
						
					$doc->endElement();
				}
				
				//geography:mint
				if (strlen($row['mint 1 URI']) > 0){
				    $doc->startElement('geographic');
				    if (strlen($row['mint 1 URI']) > 0){
				        $content = processUri($row['mint 1 URI']);
				        $doc->startElement('geogname');
    				        $doc->writeAttribute('xlink:type', 'simple');
    				        $doc->writeAttribute('xlink:role', 'mint');
    				        $doc->writeAttribute('xlink:href', $row['mint 1 URI']);
    				        if ($row['mint uncertain'] == "TRUE"){
    				            $doc->writeAttribute('certainty', 'http://nomisma.org/id/uncertain_value');
    				        }
    				        $doc->text($content['label']);
				        $doc->endElement();
				    }
				    if (strlen($row['mint 2 URI']) > 0){
				        $content = processUri($row['mint 2 URI']);
				        $doc->startElement('geogname');
    				        $doc->writeAttribute('xlink:type', 'simple');
    				        $doc->writeAttribute('xlink:role', 'mint');
    				        $doc->writeAttribute('xlink:href', $row['mint 2 URI']);
    				        if ($row['mint uncertain'] == "TRUE"){
    				            $doc->writeAttribute('certainty', 'http://nomisma.org/id/uncertain_value');
    				        }
    				        $doc->text($content['label']);
				        $doc->endElement();				        
				    }
				    $doc->endElement();
				}
				
				
				
				//obverse
				if (strlen($row['obverse type']) > 0 || strlen($row['obverse legend']) > 0){
					//$key = trim($row['O']);
					//$type = '';
					
					$doc->startElement('obverse');		
					
    					//legend
    					if (strlen(trim($row['obverse legend'])) > 0){
    					    $legend = trim($row['obverse legend']);    					    
    					    
    					    $doc->startElement('legend');
        					    $doc->text($legend);
    					    $doc->endElement();
    					}
					
						//multilingual type descriptions
    					if (strlen($row['obverse type']) > 0){
	    					$doc->startElement('type');
								$doc->startElement('description');
									$doc->writeAttribute('xml:lang', 'de');
									$doc->text($row['obverse type']);
								$doc->endElement();
							$doc->endElement();	
    					}
    					
    					if (strlen($row['portrait']) > 0){
    					    $doc->startElement('persname');
        					    $doc->writeAttribute('xlink:role', 'portrait');
        					    $doc->text(trim($row['portrait']));
    					    $doc->endElement();
    					}
					//end obverse
					$doc->endElement();
				}
				
				//reverse
				if (strlen($row['reverse type']) > 0 || strlen($row['reverse legend']) > 0){
					//$key = trim($row['O']);
					//$type = '';
					
					$doc->startElement('reverse');
					
					//legend
					if (strlen(trim($row['reverse legend'])) > 0){
						$legend = trim($row['reverse legend']);
						
						$doc->startElement('legend');
							$doc->text($legend);
						$doc->endElement();
					}
					
					//multilingual type descriptions
					if (strlen($row['reverse type']) > 0){
						$doc->startElement('type');
							$doc->startElement('description');
								$doc->writeAttribute('xml:lang', 'de');
								$doc->text($row['reverse type']);
							$doc->endElement();
						$doc->endElement();
					}
					//end obverse
					$doc->endElement();
				}
				
				if (strlen($row['rim (edge)']) > 0){
				    $doc->startElement('edge');
				        $doc->startElement('description');
				            $doc->writeAttribute('xml:lang', 'de');
				            $doc->text($row['rim (edge)']);				            
				        $doc->endElement();
				    $doc->endElement();
				}
				
				//Type Series should be explicit
				$doc->startElement('typeSeries');
					$doc->writeAttribute('xlink:type', 'simple');
					$doc->writeAttribute('xlink:href', 'http://nomisma.org/id/oscar');
					$doc->text('OSCAR');
				$doc->endElement();
				
				//end typeDesc
				$doc->endElement();
				
				/***** REFDESC *****/
				if (strlen($row['DT ID']) > 0 || strlen($row['NHMZ ID']) > 0 || strlen($row['Ref3']) > 0 || strlen($row['Ref4']) > 0){
					$doc->startElement('refDesc');
    					if (strlen($row['DT ID']) > 0){
    					    $pieces = explode('|', $row['DT ID']);
    					    foreach ($pieces as $id){
    					        $num = explode('.', $id);
    					        
    					        $doc->startElement('reference');
        					        $doc->writeAttribute('xlink:type', 'simple');
        					        $doc->writeAttribute('xlink:href', $uri_space . $id);
        					        $doc->startElement('tei:title');
            					        $doc->writeAttribute('key', 'http://nomisma.org/id/divo-tobler');
            					        $doc->text($row['Divo - Tobler (=DT)']);
        					        $doc->endElement();
        					        $doc->startElement('tei:idno');
        					           $doc->text($num[2]);
        					        $doc->endElement();
    					        $doc->endElement();
    					    }
    					}
    					
    					if (strlen($row['NHMZ ID']) > 0){
    					    $pieces = explode('|', $row['NHMZ ID']);
    					    foreach ($pieces as $id){
    					        $num = str_replace('nhmz.', '', $id);
    					        
    					        $doc->startElement('reference');
        					        $doc->writeAttribute('xlink:type', 'simple');
        					        $doc->writeAttribute('xlink:href', $uri_space . $id);
        					        $doc->startElement('tei:title');
            					        $doc->writeAttribute('key', 'http://nomisma.org/id/nhmz');
            					        $doc->text('NHMZ');
            					    $doc->endElement();
            					    $doc->startElement('tei:idno');
            					       $doc->text($num);
        					        $doc->endElement();
    					        $doc->endElement();
    					    }
    					}
    					
    					if (strlen($row['Ref3']) > 0){
    					    construct_ref($doc, $row['Ref3'], $row['Ref3 Seite(n)'], $row['Ref3 Nr']);
    					}
    					if (strlen($row['Ref4']) > 0){
    					    construct_ref($doc, $row['Ref4'], $row['Ref4 Seite(n)'], $row['Ref4 Nr']);
    					}
					$doc->endElement();
				}
				
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
function construct_ref($doc, $ref, $page, $num){
    $doc->startElement('reference');
        $doc->startElement('tei:title');
            $doc->text(trim($ref));
        $doc->endElement();
        if (strlen($page) > 0){
            $doc->text(' ' . $page . ', Nr ');
        }
        if (strlen($num) > 0){
            $doc->startElement('tei:idno');
                $doc->text(trim($num));
            $doc->endElement();
        }

    $doc->endElement();
}

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