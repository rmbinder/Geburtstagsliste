<?php
/**
 ***********************************************************************************************
 * Class manages the data for the birthday list
 *
 * @copyright 2004-2022 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/******************************************************************************
 * Klasse verwaltet die Daten fuer die Anzeige der Geburtstagsliste
 *
 * Folgende Methoden stehen zur Verfuegung:
 *
 * generate_listData()		- erzeugt die Arrays listData und headerData fuer den Report
 * generate_dateMinMax		- erzeugt die Min- und Max-Datumsangaben zur Filterung
 *
 *****************************************************************************/

class GenList
{
    public	$headerData = array();               ///< Array mit allen Spaltenueberschriften
    public	$listData  	= array();               ///< Array mit den Daten fuer die Liste

    public	$conf;
    public	$previewDays = 0;
    public	$month;
    public 	$date_min = 0;
    public 	$date_max = 0;

    /**
     * GenList constructor
     */
    public function __construct($config, $previewDays, $month)
    {		
		$this->conf = trim($config, 'X');
		$this->previewDays = trim($previewDays, 'X');
		$this->month = $month;

		$this->generate_dateMinMax();
    }

    /**
     * Erzeugt die Arrays listData und headerData fuer die Anzeige
     * @return void
     */
	public function generate_listData()
	{
		global $gProfileFields, $pPreferences;
		
		// die Werte fuer die runden Geburtstage, Jubilaeen usw einlesen
		if (stristr($pPreferences->config['Konfigurationen']['col_values'][$this->conf],'-'))         // wenn das Zeichen '-' vorhanden ist, dann ist es ein Wertebereich (x-y;z)
		{
		    $jubi_rund = array();
		    $jubi_rund_work = preg_split('/[-;]/', $pPreferences->config['Konfigurationen']['col_values'][$this->conf] );
		    
		    for ($x = $jubi_rund_work[0]; $x <= $jubi_rund_work[1]; $x += $jubi_rund_work[2] )
		    {
		        $jubi_rund[] = $x;
		    } 
		    
		    //für den Fall, dass im Wertebereich (Von-Bis;Schrittweite) der "Von"-Wert größer als der "Bis-"Wert ist
		    if (empty($jubi_rund))
		    {
		        $jubi_rund[] = '';
		    }
		}
		else                                                                                          // else: leer ('') oder Einzelwerte (x1,x2,x3...)
		{
   	        $jubi_rund = explode(',', $pPreferences->config['Konfigurationen']['col_values'][$this->conf]);
		}		
   					
		$colfields = explode(',', $pPreferences->config['Konfigurationen']['col_fields'][$this->conf]);
		for ($i = 1; $i < count($colfields)+1; $i++)
		{
			if (substr($colfields[$i-1], 0, 1) == 'r')          //relationship
			{
				$this->headerData[$i]['data'] = $gProfileFields->getPropertyById((int) substr($colfields[$i-1], 1), 'usf_name').'*';
			}
			else 
			{
				$this->headerData[$i]['data'] = $gProfileFields->getPropertyById((int) $colfields[$i-1], 'usf_name');
			}
			$this->headerData[$i]['id'] =  $colfields[$i-1] ;
			
		}
		$this->headerData[$i]['id'] = 0 ;
		$this->headerData[$i]['data'] = $pPreferences->config['Konfigurationen']['col_desc'][$this->conf];
	
		$user = new User($GLOBALS['gDb'], $gProfileFields);
		
		// Filter: nur Mitglieder der aktuellen Organisation
		$orgCondition = ' mem_usr_id IN (
        			    SELECT DISTINCT mem_usr_id
        					       FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES.  '
                                  WHERE mem_rol_id = rol_id
        					        AND mem_begin <= ? -- DATE_NOW
        						    AND mem_end    > ? -- DATE_NOW
        						    AND rol_valid  = 1
        						    AND rol_cat_id = cat_id
        						    AND (  cat_org_id = ? -- $GLOBALS[\'gCurrentOrgId\']
            					     OR cat_org_id IS NULL ) )';
		
		// alle Mitglieder inkl. evtl. vorhandener Beziehungen einlesen
		$sql = ' SELECT DISTINCT mem_usr_id, ure_usr_id2
							FROM '.TBL_MEMBERS.'
					   LEFT JOIN '.TBL_USER_RELATIONS.'
							  ON ure_usr_id1 = mem_usr_id
						     AND ure_urt_id = ? -- $pPreferences->config[\'Konfigurationen\'][\'relation\'][$this->conf]
						   WHERE '. $orgCondition. '  ';

        $queryParams = array(
            $pPreferences->config['Konfigurationen']['relation'][$this->conf],
			DATE_NOW,
			DATE_NOW,
			$GLOBALS['gCurrentOrgId']
		);    
		$statement = $GLOBALS['gDb']->queryPrepared($sql, $queryParams);

		while ($row = $statement->fetch())
		{
			$workarray[$row['mem_usr_id']] = $row['ure_usr_id2'];
		}
		// wenn als Beziehung Ehepartner gewaehlt wurde, dann ist $workarray jetzt folgendermassen aufgebaut:
		// $workarray[userid-member] = userid-Ehepartner
		
		$membercounter = 0;
		foreach ($workarray as $usr_id => $dummy)
		{
			$user->readDataById($usr_id);
			$rolesArr = $user->getRoleMemberships();
			
			// bestehen Rollen- und/oder Kategorieeinschraenkungen?
        	$rolecatmarker = true;
        	if ($pPreferences->config['Konfigurationen']['selection_role'][$this->conf] <> ' '
        	 || $pPreferences->config['Konfigurationen']['selection_cat'][$this->conf] <> ' ')
        	{
        		$rolecatmarker = false;	
        		foreach (explode(',', $pPreferences->config['Konfigurationen']['selection_role'][$this->conf]) as $rol)
        		{
        			if ($user->isMemberOfRole((int) $rol))
        			{
        				$rolecatmarker = true;
        			}
        		}	
				foreach (explode(',',$pPreferences->config['Konfigurationen']['selection_cat'][$this->conf]) as $cat)
        		{
        			if (isMemberOfCategorie($cat, $usr_id))
        			{
        				$rolecatmarker = true;
        			}
        		}
        	} 
        	
        	// pruefen, ob der aktuelle user ($gCurrentUser) mindestens eine Rolle einsehen darf, in der das Geburtstagskind Mitglied ist
        	$hasRightToView = false;
        	
        	foreach ($rolesArr as $role_id)
        	{
        		if ($GLOBALS['gCurrentUser']->hasRightViewRole($role_id))
        		{
        			$hasRightToView = true;
        			break;
        		}
        	}

			if ($rolecatmarker && $hasRightToView)
        	{
        		$workDate = '';
        	
				// ein Profilfeld wurde als Fokusfeld gewaehlt
				if (substr($pPreferences->config['Konfigurationen']['col_sel'][$this->conf], 0, 1) == 'p')
        		{
        			$workDate = $user->getValue($gProfileFields->getPropertyById((int) substr($pPreferences->config['Konfigurationen']['col_sel'][$this->conf], 1), 'usf_name_intern'));
        		}
        		// eine Rolle wurde als Fokusfeld gewaehlt (-> $workDate ist der Beginn der Rollenzugehoerigkeit)
        		elseif (substr($pPreferences->config['Konfigurationen']['col_sel'][$this->conf],0,1) == 'r')
        		{
        			$membership = new TableAccess($GLOBALS['gDb'], TBL_MEMBERS, 'rol');
        			$membership->readDataByColumns(array('mem_rol_id' => substr($pPreferences->config['Konfigurationen']['col_sel'][$this->conf], 1), 'mem_usr_id' => $usr_id));
        			$workDate = $membership->getValue('mem_begin');
        		}
        	
				//nur weiter, wenn ein Datumswert von diesem Mitglied eingelesen werden konnte
        		if ($workDate <> '')
        		{
        			$mon = date("m", strtotime($workDate));
					$tag = date("d", strtotime($workDate));
        			$jahr_min = jahre(date("Y-m-d", strtotime($workDate)), date('Y-m-d', $this->date_min));
					$jahr_max = jahre(date("Y-m-d", strtotime($workDate)), date('Y-m-d', $this->date_max));
					$jubi_data = array();
					
					for ($i = ($jahr_min+1); $i < ($jahr_max+1); $i++)
					{   			
   						if (($jubi_rund[0] == '') && ($this->month == 0 || $mon == $this->month))
   						{
   							$jubi_data[] = $i;
   						}
   						else 
   						{
   							if (in_array($i, $jubi_rund) && ($this->month == 0 || $mon == $this->month))
   							{
   								$jubi_data[] = $i;
   							}	
   						}
					}                
					// in $jubi_data sind jetzt alle moeglichen Geburtstage/Jubilaeeen
					
					foreach ($jubi_data as $jubi)
					{
						$colcount = 0;
        				$this->listData[$membercounter] = array();
        				$this->listData[$membercounter][$colcount] = array('usr_id' => $usr_id, 'usr_uuid' => $user->getValue('usr_uuid')); 

        				$colcount = 1;
						foreach (explode(',', $pPreferences->config['Konfigurationen']['col_fields'][$this->conf]) as $usfid )
						{
							if (substr($usfid,0,1) == 'r' && !empty($workarray[$usr_id]))          //relationship
							{
								$usfid = substr($usfid,1);
								$user->readDataById($workarray[$usr_id]);
							}
							
							if (($gProfileFields->getPropertyById((int) $usfid, 'usf_type') == 'DROPDOWN'
                       			|| $gProfileFields->getPropertyById((int) $usfid, 'usf_type') == 'RADIO_BUTTON') )
    						{
    							$this->listData[$membercounter][$colcount] = $user->getValue($gProfileFields->getPropertyById((int) $usfid, 'usf_name_intern'), 'database');
    						}
    						else 
    						{
    							$this->listData[$membercounter][$colcount] = $user->getValue($gProfileFields->getPropertyById((int) $usfid, 'usf_name_intern'));
    						}
    						
    						if (!empty($workarray[$usr_id]))          						//relationship
    						{
    							$user->readDataById($usr_id);
    						}
							$colcount++;
						}

						$this->listData[$membercounter][$colcount] = '';              //$colcount ist jetzt die letzte Spalte
   						$this->listData[$membercounter]['jubi_datum'] = '';
   						
						$jahr = date("Y", strtotime($workDate))+$jubi;
            			$jubi_datum = $jahr."-".$mon."-".$tag;	
        	
        				$this->listData[$membercounter]['jubi_datum'] = $jubi_datum;
   						
        				// falls konfiguriert: Tag, Monat und Jahr ersetzen
   						$suffix = str_replace('#Day#', $tag, $pPreferences->config['Konfigurationen']['col_suffix'][$this->conf]);
   						$suffix = str_replace('#Month#', $mon, $suffix);
   						$suffix = str_replace('#Year#', $jahr, $suffix);
   			
						$this->listData[$membercounter][$colcount]=($pPreferences->config['Konfigurationen']['suppress_age'][$this->conf] ? '' : $jubi).$suffix;	
   						
						$membercounter++;
					}
        		}                            //end $workdate <> ''
        	}                         // end $rolemarker && $catmarker
		}                      // end foreach $usr_id

    	// jetzt nach Datum sortieren
    	g_arr_dimsort($this->listData,'jubi_datum');
	
		// die Spalte jubi_datum wurde nur zum Sortieren verwendet und muss wieder geloescht werden
		// sie wuerde ansonsten unter Einstellungen-Spalten in der Spaltenauswahl erscheinen
		foreach ($this->listData as $counter => $dummy)
		{
   			unset ($this->listData[$counter]['jubi_datum']);
		}		
	}	
	
    /**
     * Generiert die Datumsgrenzen Min und Max fuer die Filterung
     * @return void
     */
	private function generate_dateMinMax()
	{
		global $pPreferences;
		
		// aufgrund eines Wunsches von "red" im Forum wurde der Parameter Jahresversatz eingefuehrt
		// dadurch ist es moeglich, Geburtstage in naechsten oder vergangenen Jahren anzuzeigen
		$yearsOffset = $pPreferences->config['Konfigurationen']['years_offset'][$this->conf];
		
		// standardmaessig wird die Geburtstagsliste immer ab dem aktuellen Datum angezeigt
		// aufgrund eines Wunsches im Forum kann der Beginn der Anzeige auf den 1. Januar gesetzt werden (Kalenderjahr)
		if ($pPreferences->config['Konfigurationen']['calendar_year'][$this->conf])
		{
			if ($this->previewDays >= 0)
			{
				$this->date_min = date("U", strtotime($yearsOffset." year", strtotime('-1 day',strtotime(date("Y")."-01-01"))));
				$this->date_max = date("U", strtotime($yearsOffset." year", strtotime(($this->previewDays)." day", strtotime(date("Y")."-01-01"))));
			}
			else
			{
				$this->date_min = date("U", strtotime($yearsOffset." year", strtotime(($this->previewDays-1)." day", strtotime(date("Y")."-01-01"))));
				$this->date_max = date("U", strtotime($yearsOffset." year", strtotime(date("Y")."-01-01")));
			}
		}
		else
		{
			if ($this->previewDays >= 0)
			{
				$this->date_min = date("U", strtotime($yearsOffset." year", strtotime('-1 day')));
				$this->date_max = date("U", strtotime($yearsOffset." year", strtotime(($this->previewDays)." day")));				
			}
			else
			{
				$this->date_min = date("U", strtotime($yearsOffset." year", strtotime(($this->previewDays-1)." day")));
				$this->date_max = date("U", strtotime($yearsOffset." year"));						
			}
		}
	}
}
