<?php
/**
 * TeaModelGen class file.
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @link http://www.Tea.com/
 * @copyright Copyright &copy; 2009 Leng Sheng Hong
 * @license http://www.Tea.com/license
 */


/**
 * TeaModelGen serves as a Model class file generator for rapid development
 *
 * <p>If you have your database configurations setup, call TeaModelGen::gen_mysql() and
 * it will generate the Model files for all the tables in that database</p>
 *
 * @author Leng Sheng Hong <darkredz@gmail.com>
 * @version $Id: TeaModelGen.php 1000 2009-07-7 18:27:22
 * @package Tea.db
 * @since 1.0
 */
class TeaModelGen{

    const EXTEND_MODEL = 'TeaModel';
    const EXTEND_SMARTMODEL = 'TeaSmartModel';

    public static function exportRules($ruleArr) {
        $rule = preg_replace("/\d+\s+=>\s+/", '', var_export($ruleArr, true));
        $rule = str_replace("\n      ", ' ', $rule);
        $rule = str_replace(",\n    )", ' )', $rule);
        $rule = str_replace("array (", 'array(', $rule);
        $rule = str_replace("    array(", '                        array(', $rule);
        $rule = str_replace("=> \n  array(", '=> array(', $rule);
        $rule = str_replace("  '", "                '", $rule);
        $rule = str_replace("  ),", "                ),\n", $rule);
        $rule = str_replace(",\n\n)", "\n            );", $rule);
        return $rule;
    }
    
    /**
     * Use TeaModelGen::genMySQL() instead
     * @deprecated deprecated since version 1.3
     */
	public static function gen_mysql($comments=true, $vrules=true, $extends='TeaModel', $createBase=true, $baseSuffix='Base', $chmod=null, $path=null) {
        self::genMySQL($comments, $vrules, $extends, $createBase, $baseSuffix, $chmod, $path);
    }
    
    /**
     * Generates Model class files from a MySQL database
     * @param bool $comments Generate comments along with the Model class
     * @param bool $vrules Generate validation rules along with the Model class
     * @param string $extends make Model class to extend TeaModel or TeaSmartModel
     * @param bool $createBase Generate base model class, will not rewrite/replace model classes if True.
     * @param string $baseSuffix Suffix string for the base model.
     * @param int $chmod Chmod for file manager
     * @param string $path Path to write the model class files
     */
	public static function genMySQL($comments=true, $vrules=true, $extends='TeaModel', $createBase=true, $baseSuffix='Base', $chmod=null, $path=null) {
        if($path===null){
            $path = Tea::conf()->SITE_PATH . Tea::conf()->PROTECTED_FOLDER . 'model/';
        }
        
		Tea::loadHelper('TeaFile');
        if($chmod===null){
            $fileManager = new TeaFile();
        }else{
            $fileManager = new TeaFile($chmod);
        }
        
		$dbconf = Tea::db()->getDefaultDbConfig();
		if(!isset($dbconf) || empty($dbconf)) {
			echo "<html><head><title>Tea Model Generator - DB: Error</title></head><body bgcolor=\"#2e3436\"><span style=\"font-size:190%;font-family: 'Courier New', Courier, monospace;\"><span style=\"color:#fff;\">Please setup the DB first in index.php and db.conf.php</span></span>";
			exit;
		}

		$dbname = $dbconf[1];

		echo "<html><head><title>Tea Model Generator - DB: $dbname</title></head><body bgcolor=\"#2e3436\">";

		$smt = Tea::db()->query("SHOW TABLES");
		$tables = $smt->fetchAll();
		$clsExtendedNum = 0;
		foreach( $tables as $tbl ) {
			if(stristr($_SERVER['SERVER_SOFTWARE'], 'Win32')) {
				$tblname = $tbl['Tables_in_'.strtolower($dbname)];
			}else {
				$tblname = $tbl['Tables_in_'.$dbname];
			}
			$smt2 = null;
			unset($smt2);
			$smt2 = Tea::db()->query("DESC `$tblname`");
			$fields = $smt2->fetchAll();
			//print_r($fields);
			$classname = '';
			$temptbl = $tblname;
			for($i=0;$i<strlen($temptbl);$i++) {
				if($i==0) {
					$classname .= strtoupper($temptbl[0]);
				}
				else if($temptbl[$i]=='_' || $temptbl[$i]=='-' || $temptbl[$i]=='.' ) {
					$classname .= strtoupper( $temptbl[ ($i+1) ] );
					$arr = str_split($temptbl);
					array_splice($arr, $i, 1);
					$temptbl = implode('', $arr);
				}else {
					$classname .= $temptbl[$i];
				}
			}

			if( !empty($extends) ) {
				if($createBase!=True) {
					if($extends==TeaModelGen::EXTEND_MODEL || $extends==TeaModelGen::EXTEND_SMARTMODEL)
						$filestr = "<?php\nTea::loadCore('db/$extends');\n\nclass $classname extends $extends{\n";
					else
						$filestr = "<?php\nTea::loadClass('$extends');\n\nclass $classname extends $extends{\n";
				}else {
					if($extends==TeaModelGen::EXTEND_MODEL || $extends==TeaModelGen::EXTEND_SMARTMODEL)
						$filestr = "<?php\nTea::loadCore('db/$extends');\n\nclass {$classname}{$baseSuffix} extends $extends{\n";
					else
						$filestr = "<?php\nTea::loadClass('$extends');\n\nclass {$classname}{$baseSuffix} extends $extends{\n";
				}
			}else {
				if($createBase!=True)
					$filestr = "<?php\nclass $classname{\n";
				else
					$filestr = "<?php\nclass {$classname}{$baseSuffix}{\n";
			}

			$pkey = '';
			$ftype = '';
			$fieldnames = array();

			if($vrules)
				Tea::loadHelper('TeaValidator');

			$rules = array();
			foreach($fields as $f) {
				$fstring='';
				if($comments && isset($f['Type']) && !empty($f['Type'])) {
					preg_match('/([^\(]+)[\(]?([\d]*)?[\)]?(.+)?/', $f['Type'], $ftype);
					$length = '';
					$more = '';

					if(isset($ftype[2]) && !empty($ftype[2]))
						$length = " Max length is $ftype[2].";
					if(isset($ftype[3]) && !empty($ftype[3])) {
						$more = " $ftype[3].";
						$ftype[3] = trim($ftype[3]);
					}

					$fstring = "\n    /**\n     * @var {$ftype[1]}$length$more\n     */\n";

					//-------- generate rules for the setupValidation() in Model ------
					if($vrules) {
						$rule = array();
						if($rulename = TeaValidator::dbDataTypeToRules(strtolower($ftype[1]))) {
							$rule = array(array($rulename));
						}

						if(isset($ftype[3]) && $ftype[3]=='unsigned')
							$rule[] = array('min',0);
						if(ctype_digit($ftype[2])) {
							if($ftype[1]=='varchar' || $ftype[1]=='char' )
								$rule[] = array('maxlength', intval($ftype[2]));
							else if($rulename=='integer') {
								$rule[] = array('maxlength', intval($ftype[2]));
							}
						}

						if(strtolower($f['Null'])=='no' && (strpos(strtolower($f['Extra']), 'auto_increment') === false))
							$rule[] = array('notnull');
						else
							$rule[] = array('optional');

						if(isset($rule[0]))
							$rules[$f['Field']] = $rule;
					}
				}

				$filestr .= "$fstring    public \${$f['Field']};\n";
				$fieldnames[] = $f['Field'];
				if($f['Key']=='PRI') {
					$pkey = $f['Field'];
				}
			}

			$fieldnames = implode($fieldnames, "','");
			$filestr .= "\n    public \$_table = '$tblname';\n";
			$filestr .= "    public \$_primarykey = '$pkey';\n";
			$filestr .= "    public \$_fields = array('$fieldnames');\n";

			if($vrules && !empty ($rules)) {
				$filestr .= "\n    public function getVRules() {\n        return ". self::exportRules($rules) ."\n    }\n\n";
				if(empty($extends)) {
					$filestr .="    public function validate(\$checkMode='all'){
		//You do not need this if you extend TeaModel or TeaSmartModel
		//MODE: all, all_one, skip
		Tea::loadHelper('TeaValidator');
		\$v = new TeaValidator;
		\$v->checkMode = \$checkMode;
		return \$v->validate(get_object_vars(\$this), \$this->getVRules());
	}\n\n";
				}
			}
			$filestr .= "}";

			if($createBase!=True) {
				if($fileManager->create($path. "$classname.php", $filestr, 'w+')) {
					echo "<span style=\"font-size:190%;font-family: 'Courier New', Courier, monospace;\"><span style=\"color:#fff;\">Model for table </span><strong><span style=\"color:#e7c118;\">$tblname</span></strong><span style=\"color:#fff;\"> generated. File - </span><strong><span style=\"color:#729fbe;\">$classname</span></strong><span style=\"color:#fff;\">.php</span></span><br/><br/>";
				} else {
					echo "<span style=\"font-size:190%;font-family: 'Courier New', Courier, monospace;\"><span style=\"color:#f00;\">Model for table </span><strong><span style=\"color:#e7c118;\">$tblname</span></strong><span style=\"color:#f00;\"> could not be generated. File - </span><strong><span style=\"color:#729fbe;\">$classname</span></strong><span style=\"color:#f00;\">.php</span></span><br/><br/>";
				}
			}else {

				if($fileManager->create($path . "base/{$classname}{$baseSuffix}.php", $filestr, 'w+')) {
					echo "<span style=\"font-size:190%;font-family: 'Courier New', Courier, monospace;\"><span style=\"color:#fff;\">Base model for table </span><strong><span style=\"color:#e7c118;\">$tblname</span></strong><span style=\"color:#fff;\"> generated. File - </span><strong><span style=\"color:#729fbe;\">{$classname}{$baseSuffix}</span></strong><span style=\"color:#fff;\">.php</span></span><br/><br/>";
					$clsfile = $path. "$classname.php";
					if(!file_exists($clsfile)) {
						$filestr = "<?php\nTea::loadModel('base/{$classname}{$baseSuffix}');\n\nclass $classname extends {$classname}{$baseSuffix}{\n}";
						if ($fileManager->create($clsfile, $filestr, 'w+')) {
							echo "<span style=\"font-size:190%;font-family: 'Courier New', Courier, monospace;\"><span style=\"color:#fff;\">Model for table </span><strong><span style=\"color:#e7c118;\">$tblname</span></strong><span style=\"color:#fff;\"> generated. File - </span><strong><span style=\"color:#729fbe;\">$classname</span></strong><span style=\"color:#fff;\">.php</span></span><br/><br/>";
							$clsExtendedNum++;
						} else {
							echo "<span style=\"font-size:190%;font-family: 'Courier New', Courier, monospace;\"><span style=\"color:#f00;\">Model for table </span><strong><span style=\"color:#e7c118;\">$tblname</span></strong><span style=\"color:#f00;\"> could not be generated. File - </span><strong><span style=\"color:#729fbe;\">$classname</span></strong><span style=\"color:#f00;\">.php</span></span><br/><br/>";
						}
					}
				} else {
					echo "<span style=\"font-size:190%;font-family: 'Courier New', Courier, monospace;\"><span style=\"color:#f00;\">Base model for table </span><strong><span style=\"color:#e7c118;\">$tblname</span></strong><span style=\"color:#f00;\"> could not be generated. File - </span><strong><span style=\"color:#729fbe;\">{$classname}{$baseSuffix}</span></strong><span style=\"color:#f00;\">.php</span></span><br/><br/>";
				}
			}
		}

		$total = sizeof($tables)+$clsExtendedNum;
		echo "<span style=\"font-size:190%;font-family: 'Courier New', Courier, monospace;color:#fff;\">Total $total file(s) generated.</span></body></html>";
	}
	
    
    
    /**
     * Use TeaModelGen::genSqlite() instead
     * @deprecated deprecated since version 1.3
     */
    public static function gen_sqlite($extends='', $createBase=false, $addmaps=false, $filenameModelPrefix = '', $baseSuffix='Base', $chmod=null, $path=null){
        self::genSqlite($extends, $createBase, $addmaps, $filenameModelPrefix, $baseSuffix, $chmod, $path);
    }
    
    /**
     * Generates Model class files from a SQLite database
     * @param string $extends make Model class to extend TeaModel or TeaSmartModel
     * @param bool $createBase Generate base model class, will not rewrite/replace model classes if True.
     * @param bool $addmaps Writes table relation map in Model class analyze with foreign keys available (You do not need to define in the maps in db.conf.php) 
     * @param string $filenameModelPrefix Add a prefix for the model class name
     * @param string $baseSuffix Suffix string for the base model.
     * @param int $chmod Chmod for file manager
     * @param string $path Path to write the model class files
     */
    public static function genSqlite($extends='', $createBase=false, $addmaps=false, $filenameModelPrefix = '', $baseSuffix='Base', $chmod=null, $path=null){
        if($path===null){
            $path = Tea::conf()->SITE_PATH . Tea::conf()->PROTECTED_FOLDER . 'model/';
        }
        
		Tea::loadHelper('TeaFile');
        if($chmod===null){
            $fileManager = new TeaFile();
        }else{
            $fileManager = new TeaFile($chmod);
        }

		// get database info
        $dbconf = Tea::db()->getDefaultDbConfig();
        $dbname = $dbconf[0];

        // print debug information about inspected db file
        echo "<html><head><title>Tea Model Generator - Sqlite filename: $dbname</title></head><body bgcolor=\"#2e3436\">";

        // get table names
        $tables = Tea::db()->query("SELECT name FROM sqlite_master where (type='table' or type='view') and name<>'sqlite_sequence'")->fetchAll(PDO::FETCH_COLUMN);
        
        $clsExtendedNum = 0;
        
        // cycle tables to compute one model file for each one       
        foreach( $tables as $tblname ){
           
            // get table fields and try to search for a primary key
            $res = Tea::db()->query( "PRAGMA table_info('" . $tblname . "')" )->fetchAll();

            // init primary key and fields
            $tblkey    = null;
            $tblfields = array();

            foreach( $res as $column ){
                $tblfields[] = $column[ 'name' ];
         
                // try to set pk
                if( intval( $column[ 'pk' ] ) && is_null( $tblkey ) )
                    $tblkey = $column[ 'name' ];
            }

            // get foreign keys
            $res = Tea::db()->query( "PRAGMA foreign_key_list('" . $tblname . "')" )->fetchAll();

            // init db map
            $dbmaps = array();
            
            $classname = '';
            $temptbl = $tblname;
            for($i=0;$i<strlen($temptbl);$i++){
                if($i==0){
                   $classname .= strtoupper($temptbl[0]);
                }
                else if($temptbl[$i]=='_' || $temptbl[$i]=='-' || $temptbl[$i]=='.' ){
                    $classname .= strtoupper( $temptbl[ ($i+1) ] );
                    $arr = str_split($temptbl);
                    array_splice($arr, $i, 1);
                    $temptbl = implode('', $arr);
                }else{
                    $classname .= $temptbl[$i];
                }
            }           
			
			$classname = $filenameModelPrefix . $classname;
           
            // start model filename content
            $filestr  = "<?php\n\n";
           
            // add db maps
            if( $addmaps && !empty($res) ){
                foreach( $res as $column ){
                    $dbtableid              = $column[ 'from' ];
                    $reference_dbtablemodel = $column[ 'table' ];
                    $reference_dbtableid    = $column[ 'to' ];

                    $dbmaps[] = '$dbmap["' . $classname              . '"]["has_many"]["' . $reference_dbtablemodel . '"]   = array("foreign_key"=>"' . $reference_dbtableid . '");';
                    $dbmaps[] = '$dbmap["' . $reference_dbtablemodel . '"]["belongs_to"]["' . $classname              . '"] = array("foreign_key"=>"' . $dbtableid . '");';
                }
                $filestr .= '$dbmap = array();' . "\n" . implode( "\n", $dbmaps ) . "\n" . 'Tea::db()->appendMap( $dbmap );' . "\n\n";
            }
            
            if(!empty($extends)){
               if($createBase!=True){
                   if($extends==TeaModelGen::EXTEND_MODEL || $extends==TeaModelGen::EXTEND_SMARTMODEL)
                       $filestr .= "Tea::loadCore('db/$extends');\n\nclass $classname extends $extends{\n";
                   else
                       $filestr .= "Tea::loadClass('$extends');\n\nclass $classname extends $extends{\n";
               }else{
                   if($extends==TeaModelGen::EXTEND_MODEL || $extends==TeaModelGen::EXTEND_SMARTMODEL)
                       $filestr .= "Tea::loadCore('db/$extends');\n\nclass {$classname}Base extends $extends{\n";
                   else
                       $filestr .= "Tea::loadClass('$extends');\n\nclass {$classname}Base extends $extends{\n";
               }
            }else{
               if($createBase!=True)
                   $filestr .= "class $classname{\n";
               else
                   $filestr .= "class {$classname}{$baseSuffix}{\n";
            }

            // export class variables
            foreach( $tblfields as $f )
                $filestr .= "    public \${$f};\n";

            // export class fields list
            $filestr .= "    public \$_table = '$tblname';\n";
            $filestr .= "    public \$_primarykey = '$tblkey';\n";
            $filestr .= "    public \$_fields = array('" . implode( $tblfields, "','" ) . "');\n";
            $filestr .= "}\n?>";

            // write content
            if($createBase!=True){
				if ($fileManager->create($path. "$classname.php", $filestr, 'w+')) {
					echo "<span style=\"font-size:100%;font-family: 'Courier New', Courier, monospace;\"><span style=\"color:#fff;\">Model for table </span><strong><span style=\"color:#e7c118;\">$tblname</span></strong><span style=\"color:#fff;\"> generated. File - </span><strong><span style=\"color:#729fbe;\">$classname</span></strong><span style=\"color:#fff;\">.php</span></span><br/><br/>";
				} else {
					echo "<span style=\"font-size:100%;font-family: 'Courier New', Courier, monospace;\"><span style=\"color:#f00;\">Model for table </span><strong><span style=\"color:#e7c118;\">$tblname</span></strong><span style=\"color:#f00;\"> could not be generated. File - </span><strong><span style=\"color:#729fbe;\">$classname</span></strong><span style=\"color:#f00;\">.php</span></span><br/><br/>";
				}
                
            } else {
				if ($fileManager->create($path. "base/{$classname}{$baseSuffix}.php", $filestr, 'w+')) {
					echo "<span style=\"font-size:100%;font-family: 'Courier New', Courier, monospace;\"><span style=\"color:#fff;\">Base model for table </span><strong><span style=\"color:#e7c118;\">$tblname</span></strong><span style=\"color:#fff;\"> generated. File - </span><strong><span style=\"color:#729fbe;\">{$classname}{$baseSuffix}</span></strong><span style=\"color:#fff;\">.php</span></span><br/><br/>";
					$clsfile = $path. "$classname.php";
					if(!file_exists($clsfile)){
						$filestr = "<?php\nTea::loadModel('base/{$classname}{$baseSuffix}');\n\nclass $classname extends {$classname}{$baseSuffix}{\n}\n?>";
						if ($fileManager->create($clsfile, $filestr, 'w+')){
							$clsExtendedNum++;
							echo "<span style=\"font-size:100%;font-family: 'Courier New', Courier, monospace;\"><span style=\"color:#fff;\">Model for table </span><strong><span style=\"color:#e7c118;\">$tblname</span></strong><span style=\"color:#fff;\"> generated. File - </span><strong><span style=\"color:#729fbe;\">$classname</span></strong><span style=\"color:#fff;\">.php</span></span><br/><br/>";
						} else {
							echo "<span style=\"font-size:100%;font-family: 'Courier New', Courier, monospace;\"><span style=\"color:#f00;\">Model for table </span><strong><span style=\"color:#e7c118;\">$tblname</span></strong><span style=\"color:#f00;\"> could not be generated. File - </span><strong><span style=\"color:#729fbe;\">$classname</span></strong><span style=\"color:#f00;\">.php</span></span><br/><br/>";
						}
					}
				} else {
					echo "<span style=\"font-size:100%;font-family: 'Courier New', Courier, monospace;\"><span style=\"color:#f00;\">Base model for table </span><strong><span style=\"color:#e7c118;\">$tblname</span></strong><span style=\"color:#f00;\"> could not be generated. File - </span><strong><span style=\"color:#729fbe;\">{$classname}{$baseSuffix}</span></strong><span style=\"color:#f00;\">.php</span></span><br/><br/>";
				}
            }
        }

        $total = sizeof($tables)+$clsExtendedNum;
        echo "<span style=\"font-size:100%;font-family: 'Courier New', Courier, monospace;color:#fff;\">Total " . $total . " file(s) generated.</span></body></html>";
    }    
    
    /**
     * Use TeaModelGen::genPgSQL() instead
     * @deprecated deprecated since version 1.3
     */
    public static function gen_pgsql(){
        self::genPgSQL(); 
    }
    
    public static function genPgSQL($path=null){
        if($path===null){
            $path = Tea::conf()->SITE_PATH . Tea::conf()->PROTECTED_FOLDER . 'model/';
        }
        
		Tea::loadHelper('TeaFile');
		$fileManager = new TeaFile(0777);

		$dbconf = Tea::db()->getDefaultDbConfig();
        $dbSchema = $dbconf[6];
        $dbname = $dbconf[1];

        echo "<html><head><title>Tea Model Generator - DB: $dbname</title></head><body bgcolor=\"#2e3436\">";

        $smt = Tea::db()->query("SELECT table_name as name FROM INFORMATION_SCHEMA.tables WHERE table_schema = '$dbSchema'");
        $tables = $smt->fetchAll();
        foreach( $tables as $tbl ){
            $tblname = $tbl["name"]; //tablename
            //Get table description
            $smt2 = Tea::db()->query(
                "SELECT DISTINCT column_name AS name, data_type AS type, is_nullable AS null,
                column_default AS default, ordinal_position AS position, character_maximum_length AS char_length,
                character_octet_length AS oct_length FROM information_schema.columns
                WHERE table_name = '$tblname' AND table_schema = '$dbSchema'   ORDER BY position"
            );
            $fields = $smt2->fetchAll();
            //Get primary key
            $smt3 = Tea::db()->query(
                "SELECT relname, indkey
                  FROM pg_class, pg_index
                 WHERE pg_class.oid = pg_index.indexrelid
                   AND pg_class.oid IN (
                    SELECT indexrelid
                      FROM pg_index, pg_class
                     WHERE pg_class.relname='$tblname'
                       AND pg_class.oid=pg_index.indrelid
                       AND indisprimary = 't')"); //indkey
            $fields3 = $smt3->fetchAll();
            $smt4 = Tea::db()->query(
                "SELECT t.relname, a.attname, a.attnum
                     FROM pg_index c
                LEFT JOIN pg_class t
                       ON c.indrelid  = t.oid
                LEFT JOIN pg_attribute a
                       ON a.attrelid = t.oid
                      AND a.attnum = ANY(indkey)
                    WHERE t.relname = '$tblname'
                      AND a.attnum = {$fields3[0]['indkey']}"

            );
            $fields4 = $smt4->fetchAll();
            $pkey = $fields4[0]['attname'];
            //Prepare model class
           $classname = '';
           $temptbl = $tblname;
           for($i=0;$i<strlen($temptbl);$i++){
               if($i==0){
                   $classname .= strtoupper($temptbl[0]);
               }
               else if($temptbl[$i]=='_' || $temptbl[$i]=='-' || $temptbl[$i]=='.' ){
                    $classname .= strtoupper( $temptbl[ ($i+1) ] );
                    $arr = str_split($temptbl);
                    array_splice($arr, $i, 1);
                    $temptbl = implode('', $arr);
               }else{
                    $classname .= $temptbl[$i];
               }
           }

           $filestr = "<?php\nclass $classname{\n";
           
           $fieldnames = array();
           foreach($fields as $f){
                $filestr .= "    public \${$f['name']};\n";
                $fieldnames[] = $f['name'];
           }

           $fieldnames = implode($fieldnames, "','");
           $filestr .= "    public \$_table = '$tblname';\n";
           $filestr .= "    public \$_primarykey = '$pkey';\n";
           $filestr .= "    public \$_fields = array('$fieldnames');\n";
           $filestr .= "}\n?>";
		   if ($fileManager->create($path. "$classname.php", $filestr, 'w+')) {
				echo "<span style=\"font-size:190%;font-family: 'Courier New', Courier, monospace;\"><span style=\"color:#fff;\">Model for table </span><strong><span style=\"color:#e7c118;\">$tblname</span></strong><span style=\"color:#fff;\"> generated. File - </span><strong><span style=\"color:#729fbe;\">$classname</span></strong><span style=\"color:#fff;\">.php</span></span><br/><br/>";
		   } else {
			   echo "<span style=\"font-size:190%;font-family: 'Courier New', Courier, monospace;\"><span style=\"color:#f00;\">Model for table </span><strong><span style=\"color:#e7c118;\">$tblname</span></strong><span style=\"color:#f00;\"> could not be generated. File - </span><strong><span style=\"color:#729fbe;\">$classname</span></strong><span style=\"color:#f00;\">.php</span></span><br/><br/>";
		   }
        }

        $total = sizeof($tables);
        echo "<span style=\"font-size:190%;font-family: 'Courier New', Courier, monospace;color:#fff;\">Total $total file(s) generated.</span></body></html>";
    }
}
