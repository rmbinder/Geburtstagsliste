<?php
/**
 ***********************************************************************************************
 * Gemeinsame Funktionen fuer das Admidio-Plugin Geburtstagsliste
 *
 * @copyright 2004-2020 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');

if(!defined('PLUGIN_FOLDER'))
{
	define('PLUGIN_FOLDER', '/'.substr(__DIR__,strrpos(__DIR__,DIRECTORY_SEPARATOR)+1));
}

if(!defined('ORG_ID'))
{
	define('ORG_ID', (int) $gCurrentOrganization->getValue('org_id'));
}

/**
 * Funktion prueft, ob der Nutzer berechtigt ist das Plugin aufzurufen.
 * Zur Prüfung werden die Einstellungen von 'Modulrechte' und 'Sichtbar für' 
 * verwendet, die im Modul Menü für dieses Plugin gesetzt wurden.
 * @param   string  $scriptName   Der Scriptname des Plugins
 * @return  bool    true, wenn der User berechtigt ist
 */
function isUserAuthorized($scriptName)
{
	global $gDb, $gCurrentUser, $gMessage, $gL10n, $gLogger;
	
	$userIsAuthorized = false;
	$menId = 0;
	
	$sql = 'SELECT men_id
              FROM '.TBL_MENU.'
             WHERE men_url = ? -- $scriptName ';
	
	$menuStatement = $gDb->queryPrepared($sql, array($scriptName));
	
	if ( $menuStatement->rowCount() === 0 || $menuStatement->rowCount() > 1)
	{
		$gLogger->notice('BirthdayList: Error with menu entry: Found rows: '. $menuStatement->rowCount() );
		$gLogger->notice('BirthdayList: Error with menu entry: ScriptName: '. $scriptName);
		$gMessage->show($gL10n->get('PLG_GEBURTSTAGSLISTE_MENU_URL_ERROR', array($scriptName)), $gL10n->get('SYS_ERROR'));
	}
	else
	{
		while ($row = $menuStatement->fetch())
		{
			$menId = (int) $row['men_id'];
		}
	}
	
	$sql = 'SELECT men_id, men_com_id, com_name_intern
              FROM '.TBL_MENU.'
         LEFT JOIN '.TBL_COMPONENTS.'
                ON com_id = men_com_id
             WHERE men_id = ? -- $menId
          ORDER BY men_men_id_parent DESC, men_order';
	
	$menuStatement = $gDb->queryPrepared($sql, array($menId));
	while ($row = $menuStatement->fetch())
	{
		if ((int) $row['men_com_id'] === 0 || Component::isVisible($row['com_name_intern']))
		{
			// Read current roles rights of the menu
			$displayMenu = new RolesRights($gDb, 'menu_view', $row['men_id']);
			$rolesDisplayRight = $displayMenu->getRolesIds();
			
			// check for right to show the menu
			if (count($rolesDisplayRight) === 0 || $displayMenu->hasRight($gCurrentUser->getRoleMemberships()))
			{
				$userIsAuthorized = true;
			}
		}
	}
	return $userIsAuthorized;
}

/**
 * Funktion überprueft den uebergebenen Namen, ob er gemaess den Namenskonventionen für
 * Profilfelder und Kategorien zum Uebersetzen durch eine Sprachdatei geeignet ist
 * Bsp: SYS_COMMON --> Rueckgabe true
 * Bsp: Mitgliedsbeitrag --> Rueckgabe false
 *
 * @param   string  $field_name
 * @return  bool
 */
function check_languagePGL($field_name)
{
    $ret = false;
 
    //pruefen, ob die ersten 3 Zeichen von $field_name Grußbuchstaben sind
    //pruefen, ob das vierte Zeichen von $field_name ein _ ist

    //Pruefung entfaellt: pruefen, ob die restlichen Zeichen von $field_name Grussbuchstaben sind
    //if ((ctype_upper(substr($field_name,0,3))) && ((substr($field_name,3,1))=='_')  && (ctype_upper(substr($field_name,4)))   )

    if ((ctype_upper(substr($field_name,0,3))) && ((substr($field_name,3,1)) == '_'))
    {
      $ret = true;
    }
    return $ret;
}

/**
 * Vergleichsfunktion für g_arr_dimsort (aus dem Web)
 * @param   mixed  $a
 * @param   mixed  $b
 * @return  bool
 */
function arr_dimsort_cmp($a,$b)
{
	global $G_ARR_STYPE, $G_ARR_SDIM;

  	/* -- Sort numbers? */

  	if ($G_ARR_STYPE == 'NUMBER') 
  	{
      	if ((float)$a[$G_ARR_SDIM] == (float)$b[$G_ARR_SDIM]) return 0;

      	return (floatval($a[$G_ARR_SDIM]) > floatval($b[$G_ARR_SDIM])) ? 1 : -1;
  	}
  	/* -- Sort strings? */

  	if ($G_ARR_STYPE == 'STRING') return strcmp($a[$G_ARR_SDIM],$b[$G_ARR_SDIM]);

  	/* -- First time: get the right data type */

  	$G_ARR_STYPE = is_string($a[$G_ARR_SDIM]) ? 'STRING' : 'NUMBER';

  	return arr_dimsort_cmp($a,$b);
}

/**
 * Funktion sortiert ein Array nach einer gegebenen Dimension (aus dem Web)
 * @param   array   $arr     das zu sortierende Array
 * @param   string  $dim     die Dimension, nach der sortiert werden soll
 * @param   string  $type    NUMBER oder STRING
 * @param   bool    $keepkey Schluessel beibehalten
 * @return  void
 */
function g_arr_dimsort(&$arr, $dim, $type = '',$keepkey = false)
{
  	global $G_ARR_SDIM, $G_ARR_STYPE;

  	$G_ARR_SDIM = $dim; $G_ARR_STYPE = $type;

  	if ($keepkey) uasort($arr,'arr_dimsort_cmp');
  	else
      	usort($arr,'arr_dimsort_cmp');
}


/**
 * Funktion prueft, ob ein User Angehoeriger einer bestimmten Kategorie ist
 *
 * @param   int  $cat_id    ID der zu pruefenden Kategorie
 * @param   int  $user_id   ID des Users, fuer den die Mitgliedschaft geprueft werden soll
 * @return  bool
 */
function isMemberOfCategorie($cat_id, $user_id = 0)
{
    global $gCurrentUser, $gDb;

    if ($user_id == 0)
    {
        $user_id = $gCurrentUser->getValue('usr_id');
    }
    elseif (is_numeric($user_id) == false)
    {
        return -1;
    }

    $sql = 'SELECT mem_id
              FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES. '
             WHERE mem_usr_id = '.$user_id.'
               AND mem_begin <= \''.DATE_NOW.'\'
               AND mem_end    > \''.DATE_NOW.'\'
               AND mem_rol_id = rol_id
               AND cat_id   = \''.$cat_id.'\'
               AND rol_valid  = 1 
               AND rol_cat_id = cat_id
               AND ( cat_org_id = '.ORG_ID.'
                OR cat_org_id IS NULL ) ';
                
    $statement = $gDb->query($sql);

    $user_found = $statement->rowCount();

    if ($user_found == 1)
    {
        return 1;
    }
    else
    {
        return 0;
    }   
}

/**
 * Erzeugt die Auswahlliste für die Spaltenauswahl
 * @return  array   $configSelection
 */
function generate_configSelection()
{
	global $gDb,  $gL10n, $gProfileFields, $gCurrentUser;
	    
    $categories = array(); 
    $configSelection = array();  
        
    $i 	= 0;
    foreach ($gProfileFields->getProfileFields() as $field)
    {             
        if (($field->getValue('usf_hidden') == 0 || $gCurrentUser->editUsers()) && $field->getValue('usf_type') == 'DATE')
        {   
        	$configSelection[$i][0] = 'p'.$field->getValue('usf_id');
            $configSelection[$i][1] = addslashes($field->getValue('usf_name'));               
            $configSelection[$i][2] = $field->getValue('cat_name');
			$i++;
        }
    }
        
	// alle (Rollen-)Kategorien der aktuellen Organisation einlesen
	$sql = ' SELECT DISTINCT cat.cat_name, cat.cat_id
                        FROM '.TBL_CATEGORIES.' as cat, '.TBL_ROLES.' as rol
                       WHERE cat.cat_type = \'ROL\' 
                         AND cat.cat_id = rol.rol_cat_id
                         AND ( cat.cat_org_id = '.ORG_ID.'
                          OR cat.cat_org_id IS NULL )';
	
	$statement = $gDb->query($sql);

	$k = 0;
	while ($row = $statement->fetch())
	{
		// ueberpruefen, ob der Kategoriename mittels der Sprachdatei uebersetzt werden kann
        if (check_languagePGL($row['cat_name']))
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
    	$statement = $gDb->query($sql);
    		
        while ($row = $statement->fetch())
        {
        	$configSelection[$i][0] = 'r'.$row['rol_id'];
			$configSelection[$i][1]	= $gL10n->get('SYS_ROLE').': '.$row['rol_name'];
			$configSelection[$i][2]	= $data['cat_name'];
			$i++;
        }	
    }
    return $configSelection;		
}

/**
 * Ermittelt die Differenz zwischen $beginn und $ende (ab PHP 5.3)
 *
 * @param   string  $beginn
 * @param   string  $ende
 * @return  string  Differenz als zweistellige Jahrszahl
 */
function jahre( $beginn, $ende )
{
  $date1 = new DateTime($beginn);
  $date2 = new DateTime($ende);
  $differenz = $date1->diff($date2);
 
  return $differenz->format('%y');
}
