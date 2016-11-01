<?php
/**
 ***********************************************************************************************
 * Class manages the data for the birthday list
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/******************************************************************************
 * Klasse verwaltet die Daten für die Anzeige der Geburtstagsliste
 *
 * Folgende Methoden stehen zur Verfügung:
 *
 * generate_listData()		- erzeugt die Arrays listData und headerData für den Report
 * generate_dateMinMax		- erzeugt die Min- und Max-Datumsangaben zur Filterung
 *
 *****************************************************************************/

class GenList
{
    public	$headerData = array();               ///< Array mit allen Spaltenüberschriften
    public	$listData  	= array();               ///< Array mit den Daten für die Liste

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
		$this->conf=trim($config,'X');
		$this->previewDays=trim($previewDays,'X');
		$this->month=$month;

		$this->generate_dateMinMax();
    }

    /**
     * Erzeugt die Arrays listData und headerData für die Anzeige
     * @return void
     */
	public function generate_listData()
	{
		global $gDb, $gProfileFields, $gCurrentOrganization, $pPreferences;
		
		// die Werte für die runden Geburtstage, Jubilaeen usw einlesen
   		$jubi_rund = explode(',',$pPreferences->config['Konfigurationen']['col_values'][$this->conf]);
   					
		$colfields=explode(',',$pPreferences->config['Konfigurationen']['col_fields'][$this->conf]);
		for($i=1; $i < count($colfields)+1; $i++)
		{
			$this->headerData[$i]['id'] = (int) $colfields[$i-1] ;
			$this->headerData[$i]['data'] = $gProfileFields->getPropertyById((int) $colfields[$i-1], 'usf_name');
		}
		$this->headerData[$i]['id'] = 0 ;
		$this->headerData[$i]['data'] = $pPreferences->config['Konfigurationen']['col_desc'][$this->conf];
	
		$user = new User($gDb, $gProfileFields);
		
		// alle Mitglieder der aktuellen Organisation einlesen
		$sql = ' SELECT mem.mem_usr_id, mem.mem_begin
             	FROM '.TBL_MEMBERS.' as mem, '.TBL_ROLES.' as rol, '. TBL_CATEGORIES. ' as cat
             	WHERE mem.mem_rol_id = rol.rol_id
             	AND rol.rol_valid  = 1   
             	AND rol.rol_cat_id = cat.cat_id
             	AND (  cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
               	OR cat.cat_org_id IS NULL )
             	AND mem.mem_end = \'9999-12-31\' ';
		$statement = $gDb->query($sql);

		while ($row = $statement->fetch())
		{
			$workarray[$row['mem_usr_id']] = $row['mem_usr_id'];
		}
		
		$membercounter = 0;
		foreach($workarray as $usr_id)
		{
			// bestehen Rollen- und/oder Kategorieeinschränkungen?
        	$rolecatmarker = true;
        	if ($pPreferences->config['Konfigurationen']['selection_role'][$this->conf]<>' '
        	 || $pPreferences->config['Konfigurationen']['selection_cat'][$this->conf]<>' ')
        	{
        		$rolecatmarker = false;	
        		foreach (explode(',',$pPreferences->config['Konfigurationen']['selection_role'][$this->conf]) as $rol)
        		{
        			if (hasRole_IDPGL($rol, $usr_id))
        			{
        				$rolecatmarker = true;
        			}
        		}	
				foreach (explode(',',$pPreferences->config['Konfigurationen']['selection_cat'][$this->conf]) as $cat)
        		{
        			if (hasCategorie_IDPGL($cat, $usr_id))
        			{
        				$rolecatmarker = true;
        			}
        		}
        	} 			
			if ($rolecatmarker )
        	{
        		$workDate = '';
				$user->readDataById($usr_id);
        	
				// ein Profilfeld wurde als Fokusfeld gewählt
				if(substr($pPreferences->config['Konfigurationen']['col_sel'][$this->conf],0,1)=='p')
        		{
        			$workDate = $user->getValue($gProfileFields->getPropertyById((int) substr($pPreferences->config['Konfigurationen']['col_sel'][$this->conf],1), 'usf_name_intern'));
        		}
        		// eine Rolle wurde als Fokusfeld gewählt (-> $workDate ist das Beginn der Rollenzugehörigkeit)
        		elseif(substr($pPreferences->config['Konfigurationen']['col_sel'][$this->conf],0,1)=='r')
        		{
        			$membership = new TableAccess($gDb, TBL_MEMBERS, 'rol');
        			$membership->readDataByColumns(array('mem_rol_id' => substr($pPreferences->config['Konfigurationen']['col_sel'][$this->conf],1), 'mem_usr_id' => $usr_id));
        			$workDate = $membership->getValue('mem_begin');
        		}
        	
				//nur weiter, wenn ein Datumswert von diesem Mitglied eingelesen werden konnte
        		if ($workDate <> '')
        		{
        			$mon = date("m",strtotime($workDate));
					$tag = date("d",strtotime($workDate));
        			$jahr_min = jahre(date("Y-m-d",strtotime($workDate)),date('Y-m-d',$this->date_min));
					$jahr_max = jahre(date("Y-m-d",strtotime($workDate)),date('Y-m-d',$this->date_max));
					$jubi_data = array();
					
					for($i=($jahr_min+1); $i < ($jahr_max+1); $i++)
					{   			
   						if(($jubi_rund[0]=='') && ($this->month==0 || $mon ==$this->month ) )
   						{
   							$jubi_data[]=$i;
   						}
   						else 
   						{
   							if(in_array($i, $jubi_rund) && ($this->month==0 || $mon ==$this->month ) )
   							{
   								$jubi_data[]=$i;
   							}	
   						}
					}                
					// in $jubi_data sind jetzt alle möglichen Geburtstage/Jubiläen
					
					foreach($jubi_data as $jubi)
					{
						$colcount=0;
        				$this->listData[$membercounter] = array();
						$this->listData[$membercounter][$colcount] = $usr_id; 

        				$colcount=1;
						foreach(explode(',',$pPreferences->config['Konfigurationen']['col_fields'][$this->conf]) as $usfid )
						{
							if(  ($gProfileFields->getPropertyById((int) $usfid, 'usf_type') == 'DROPDOWN'
                       			|| $gProfileFields->getPropertyById((int) $usfid, 'usf_type') == 'RADIO_BUTTON') )
    						{
    							$this->listData[$membercounter][$colcount] = $user->getValue($gProfileFields->getPropertyById((int) $usfid, 'usf_name_intern'),'database');
    						}
    						else 
    						{
    							$this->listData[$membercounter][$colcount] = $user->getValue($gProfileFields->getPropertyById((int) $usfid, 'usf_name_intern'));
    						}
							$colcount++;
						}

						$this->listData[$membercounter][$colcount] = '';              //$colcount ist jetzt die letzte Spalte
   						$this->listData[$membercounter]['jubi_datum'] = '';
   						
						$jahr = date("Y",strtotime($workDate))+$jubi;
            			$jubi_datum = $jahr."-".$mon."-".$tag;	
        	
        				$this->listData[$membercounter]['jubi_datum'] = $jubi_datum;
   						
        				// falls konfiguriert: Tag, Monat und Jahr ersetzen
   						$suffix=str_replace('#Day#', $tag, $pPreferences->config['Konfigurationen']['col_suffix'][$this->conf]);
   						$suffix=str_replace('#Month#', $mon, $suffix);
   						$suffix=str_replace('#Year#', $jahr, $suffix);
   			
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
     * Generiert die Datumsgrenzen Min und Max für die Filterung
     * @return void
     */
	private function generate_dateMinMax()
	{
		global  $pPreferences;
		
		// aufgrund eines Wunsches von "red" im Forum wurde der Parameter Jahresversatz eingeführt
		// dadurch ist es möglich, Geburtstage in nächsten oder vergangenen Jahren anzuzeigen
		$yearsOffset = $pPreferences->config['Konfigurationen']['years_offset'][$this->conf];
		
		// standardmäßig wird die Geburtstagsliste immer ab dem aktuellen Datum angezeigt
		// aufgrund eines Wunsches im Forum kann der Beginn der Anzeige auf den 1. Januar gesetzt werden (Kalenderjahr)
		if($pPreferences->config['Konfigurationen']['calendar_year'][$this->conf])
		{
			if ($this->previewDays >= 0)
			{
				$this->date_min = date("U",strtotime($yearsOffset." year",strtotime('-1 day',strtotime(date("Y")."-01-01"))));
				$this->date_max = date("U",strtotime($yearsOffset." year",strtotime(($this->previewDays)." day",strtotime(date("Y")."-01-01"))));
			}
			else
			{
				$this->date_min = date("U",strtotime($yearsOffset." year",strtotime(($this->previewDays-1)." day",strtotime(date("Y")."-01-01"))));
				$this->date_max = date("U",strtotime($yearsOffset." year",strtotime(date("Y")."-01-01")));
			}
		}
		else
		{
			if ($this->previewDays >= 0)
			{
				$this->date_min = date("U",strtotime($yearsOffset." year",strtotime('-1 day')));
				$this->date_max = date("U",strtotime($yearsOffset." year",strtotime(($this->previewDays)." day")));				
			}
			else
			{
				$this->date_min = date("U",strtotime($yearsOffset." year",strtotime(($this->previewDays-1)." day")));
				$this->date_max = date("U",strtotime($yearsOffset." year"));						
			}
		}
	}
}
