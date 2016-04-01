<?php
/******************************************************************************
 * 
 * common_function.php
 *   
 * Gemeinsame Funktionen fuer das Admidio-Plugin Geburtstagsliste
 * 
 * Copyright    : (c) 2004 - 2015 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 * 
 ****************************************************************************/

// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);

require_once($plugin_path. '/../adm_program/system/common.php');

// Funktion liest die Role-ID einer Rolle aus
// Parameter: $role_name - Name der zu pruefenden Rolle
function getRole_IDPGL($role_name)
{
    global $gDb, $gCurrentOrganization;
	
    $sql    = 'SELECT rol_id
                 FROM '. TBL_ROLES. ', '. TBL_CATEGORIES. '
                 WHERE rol_name   = \''.$role_name.'\'
                 AND rol_valid  = 1 
                 AND rol_cat_id = cat_id
                 AND (  cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
                 OR cat_org_id IS NULL ) ';
                      
    $result = $gDb->query($sql);
    $row = $gDb->fetch_object($result);

   // für den seltenen Fall, dass während des Betriebes die Sprache umgeschaltet wird:  $row->rol_id prüfen
    return (isset($row->rol_id) ?  $row->rol_id : 0);
}

// Funktion prueft, ob der Nutzer, aufgrund seiner Rollenzugehörigkeit, berechtigt ist das Plugin aufzurufen
// Parameter: Array mit Rollen-IDs: entweder $pPreferences->config['Pluginfreigabe']['freigabe']
//      oder $pPreferences->config['Pluginfreigabe']['freigabe_config']
function check_showpluginPGL($array)
{
	global $gCurrentUser;
	
    $showPlugin = false;

    foreach ($array AS $i)
    {
        if($gCurrentUser ->isMemberOfRole($i))
        {
            $showPlugin = true;
        } 
    } 
    return $showPlugin;
}

// Funktion überprüft den übergebenen Namen, ob er gemaess den Namenskonventionen für
// Profilfelder und Kategorien zum Uebersetzen durch eine Sprachdatei geeignet ist
// Bsp: SYS_COMMON --> Rueckgabe true
// Bsp: Mitgliedsbeitrag --> Rueckgabe false
function check_languagePGL($field_name)
{
    $ret = false;
 
    //prüfen, ob die ersten 3 Zeichen von $field_name Grußbuchstaben sind
    //prüfen, ob das vierte Zeichen von $field_name ein _ ist

    //Prüfung entfällt: prüfen, ob die restlichen Zeichen von $field_name Grußbuchstaben sind
    //if ((ctype_upper(substr($field_name,0,3))) && ((substr($field_name,3,1))=='_')  && (ctype_upper(substr($field_name,4)))   )

    if ((ctype_upper(substr($field_name,0,3))) && ((substr($field_name,3,1))=='_')   )
    {
      $ret = true;
    }
    return $ret;
}

// Compare function used when sorting arrays
// aus dem Web
function arr_dimsort_cmp($a,$b)
{
	global $G_ARR_STYPE, $G_ARR_SDIM;

  	/* -- Sort numbers? */

  	if ($G_ARR_STYPE == 'NUMBER') 
  	{
      	if ((float)$a[$G_ARR_SDIM] == (float)$b[$G_ARR_SDIM]) return 0;

      	return (floatval($a[$G_ARR_SDIM]) > floatval($b[$G_ARR_SDIM])) ? 1: -1;
  	}
  	/* -- Sort strings? */

  	if ($G_ARR_STYPE == 'STRING') return strcmp($a[$G_ARR_SDIM],$b[$G_ARR_SDIM]);

  	/* -- First time: get the right data type */

  	$G_ARR_STYPE = is_string($a[$G_ARR_SDIM])? 'STRING' : 'NUMBER';

  	return arr_dimsort_cmp($a,$b);
}

// Sort an array by a given dimension
// aus dem Web
function g_arr_dimsort(&$arr,$dim,$type = '',$keepkey = false)
{
  	global $G_ARR_SDIM, $G_ARR_STYPE;

  	$G_ARR_SDIM = $dim; $G_ARR_STYPE = $type;

  	if ($keepkey) uasort($arr,'arr_dimsort_cmp');
  	else
      	usort($arr,'arr_dimsort_cmp');
}

 // Funktion prueft, ob ein User die uebergebene Rolle besitzt
// $role_id   - ID der zu pruefenden Rolle
// $user_id   - ID des Users, fuer den die Mitgliedschaft geprueft werden soll
function hasRole_IDPGL($role_id, $user_id = 0)
{
    global $gCurrentUser, $gDb, $gCurrentOrganization;

    if($user_id == 0)
    {
        $user_id = $gCurrentUser->getValue('usr_id');
    }
    elseif(is_numeric($user_id) == false)
    {
        return -1;
    }

    $sql    = 'SELECT mem_id
                FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES. '
                WHERE mem_usr_id = '.$user_id.'
                AND mem_begin <= \''.DATE_NOW.'\'
                AND mem_end    > \''.DATE_NOW.'\'
                AND mem_rol_id = rol_id
                AND rol_id   = \''.$role_id.'\'
                AND rol_valid  = 1 
                AND rol_cat_id = cat_id
                AND (  cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
                OR cat_org_id IS NULL ) ';
                
    $result = $gDb->query($sql);

    $user_found = $gDb->num_rows($result);

    if($user_found == 1)
    {
        return 1;
    }
    else
    {
        return 0;
    }
}
// Funktion prueft, ob ein User Angehoeriger einer Kategorie ist
// $cat_id    - ID der zu pruefenden Kategorie
// $user_id   - ID des Users, fuer den die Mitgliedschaft geprueft werden soll
function hasCategorie_IDPGL($cat_id, $user_id = 0)
{
    global $gCurrentUser, $gDb, $gCurrentOrganization;

    if($user_id == 0)
    {
        $user_id = $gCurrentUser->getValue('usr_id');
    }
    elseif(is_numeric($user_id) == false)
    {
        return -1;
    }

    $sql    = 'SELECT mem_id
                FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES. '
                WHERE mem_usr_id = '.$user_id.'
                AND mem_begin <= \''.DATE_NOW.'\'
                AND mem_end    > \''.DATE_NOW.'\'
                AND mem_rol_id = rol_id
                AND cat_id   = \''.$cat_id.'\'
                AND rol_valid  = 1 
                AND rol_cat_id = cat_id
                AND (  cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
                OR cat_org_id IS NULL ) ';
                
    $result = $gDb->query($sql);

    $user_found = $gDb->num_rows($result);

    if($user_found == 1)
    {
        return 1;
    }
    else
    {
        return 0;
    }   
}

// erzeugt die Auswahlliste für die Spaltenauswahl
function generate_configSelection()
{
	global $gDb,  $gL10n, $gProfileFields, $gCurrentOrganization, $gCurrentUser;
	    
    $categories = array(); 
    $configSelection = array();  
        
    $i 	= 0;
    foreach($gProfileFields->mProfileFields as $field)
    {             
        if(($field->getValue('usf_hidden') == 0 || $gCurrentUser->editUsers()) && $field->getValue('usf_type')== 'DATE')
        {   
        	$configSelection[$i][0]   = 'p'.$field->getValue('usf_id');
            $configSelection[$i][1]   = addslashes($field->getValue('usf_name'));               
            $configSelection[$i][2]   = $field->getValue('cat_name');
			$i++;
        }
    }
        
	// alle (Rollen-)Kategorien der aktuellen Organisation einlesen
	$sql = ' SELECT DISTINCT cat.cat_name, cat.cat_id
             FROM '.TBL_CATEGORIES.' as cat, '.TBL_ROLES.' as rol
             WHERE cat.cat_type = \'ROL\' 
             AND cat.cat_id = rol.rol_cat_id
             AND (  cat.cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
             OR cat.cat_org_id IS NULL )';
	
	$result = $gDb->query($sql);

	$k = 0;
	while ($row = $gDb->fetch_array($result))
	{
		// ueberprüfen, ob der Kategoriename mittels der Sprachdatei übersetzt werden kann
        if(check_languagePGL($row['cat_name']))
        {
        	$row['cat_name'] = $gL10n->get($row['cat_name']);
        }
		$categories[$k]['cat_id']   = $row['cat_id'];
		$categories[$k]['cat_name'] = $row['cat_name'];
		$k++;
	}
 
	// alle eingelesenen Kategorien durchlaufen und die Rollen dazu einlesen
  	foreach ($categories as $data)
	{
       	$sql = 'SELECT DISTINCT rol.rol_name, rol.rol_id
                FROM '.TBL_CATEGORIES.' as cat, '.TBL_ROLES.' as rol
                WHERE cat.cat_id = \''.$data['cat_id'].'\'
                AND cat.cat_id = rol.rol_cat_id';
    	$result = $gDb->query($sql);
    		
        while($row = $gDb->fetch_array($result))
        {
        	$configSelection[$i][0]  = 'r'.$row['rol_id'];
			$configSelection[$i][1]	= $gL10n->get('SYS_ROLE').': '.$row['rol_name'];
			$configSelection[$i][2]	= $data['cat_name'];
			$i++;
        }	
    }
    return $configSelection;		
}

// ermittelt die Differenz zwichen $beginn und $ende (ab PHP 5.3)
function jahre( $beginn, $ende )
{
  $date1 = new DateTime($beginn);
  $date2 = new DateTime($ende);
  $differenz = $date1->diff($date2);
 
  return $differenz->format('%y');
}
?>