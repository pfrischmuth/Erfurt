<?
/**
 * RDFSmodel
 *
 * @package RDFSAPI
 * @author Atanas Alexandrov <sirakov@gmail.com>
 *
 **/
class RDFSModel extends DefaultRDFSModel {
	
	function RDFSModel($store,$modelname,$type=NULL) {
		$modelVars =& $store->dbConn->execute("SELECT * FROM MDSYS.RDF_MODEL$ WHERE MODEL_NAME='" .$modelname ."'");
		$this->modelOwner 	= $modelVars->fields[0];
		$this->modelID 	= $modelVars->fields[1];
		$this->modelName 	= $modelVars->fields[2];
		$this->tableName 	= $modelVars->fields[3];
		parent::DefaultRDFSModel($store,$modelname,$type);
	}

	/**
	 * Add triple to model
	 * 
	 * @param	string	$subj
	 * @param	string	$pred
	 * @param	string	$obj
	 * @return	boolean	$success
	 */
	public function add($subj, $pred, $obj){
		$success = false;
		
		//get max triple ID, generate next ID for the tripple
		$_newTable = $this->modelOwner.".".$this->tableName; // just concat -> Example ONTOWIKI.FAMILY_RDF_DATA
		$count = "SELECT MAX(ID) FROM ".$_newTable; // get last triple ID
		$lastTripleId = $this->dbConn->GetOne($count); // Get the number of the last tripleId
		$newId = $lastTripleId + 1; // the Id for the new triple will be 1 bigger		
		
		//Check, if triple already exists
		$tripleexist = "SELECT SDO_RDF.IS_TRIPLE('".$this->modelName."', '".$subj."', '".$pred."', '".$obj."') AS is_triple FROM DUAL";

		if (($this->dbConn->GetOne($tripleexist)) == "TRUE (EXACT)"){
			echo "Triple already exist!";
			$success=true;
		}
		else {
			//Insert triple	
			$insert_query = "INSERT INTO ".$this->tableName." ";
			$insert_query .= "VALUES (".$newId.", SDO_RDF_TRIPLE_S('".$this->modelName."','".$subj."','".$pred."','".$obj."'))";	
			$this->dbConn->Execute($insert_query);
		}
		
		// Check, if the triple was added successfully
		if ($newId == $this->dbConn->GetOne("SELECT MAX(ID) FROM ".$_newTable)){
			print("Triple added successfully!");
			$success=true;	
		}
		
		return $success;
	}
	
	/**
	 * Remove triple from model
	 * 
	 * @param	string	$subj
	 * @param	string	$pred
	 * @param	string	$obj
	 * @return	boolean	$success
	 */
	public function remove($subj, $pred, $obj){
		$success = false;

		$_newTable = $this->modelOwner.".".$this->tableName; // just concat -> Example ONTOWIKI.FAMILY_RDF_DATA
		$getLastTripleId = $this->dbConn->GetOne("SELECT MAX(ID) FROM ".$_newTable);		
		$tripleexist = "SELECT SDO_RDF.IS_TRIPLE('".$this->modelName."', '".$subj."', '".$pred."', '".$obj."') AS is_triple FROM DUAL"; // query to check, if triple already exists

		// delete triple query
		$deleteTriple = "DELETE FROM ".$_newTable." a where a.triple.get_subject()='".$subj."' and a.triple.get_property() = '".$pred."' and to_char(a.triple.get_object()) = '".$obj."'";
		
		if (($this->dbConn->GetOne($tripleexist)) == "TRUE (EXACT)"){
			$this->dbConn->Execute($deleteTriple);
		} else {
			print ("Error: Triple does not exist!");
		}
		
		// Check, if the triple was added successfully
		if ($getLastTripleId > $this->dbConn->GetOne("SELECT MAX(ID) FROM ".$_newTable)){
			print ("Triple deleted successfully!");
			$success=true;
		} else {
			print ("Error: triple was not removed succesfully"); 
		}
		
		return $success;	
	}
	
	/**
	 * Insert new namespace into MSYS.RDF_NAMESPACE Table$
	 * @param	string	$namespace
	 */
	public function insertNamespace($namespace){
		$success=false;
		$nsExist = "SELECT count(*) FROM MDSYS.RDF_NAMESPACE$ ns WHERE ns.NAMESPACE_NAME.getUrl()='".$namespace."'";	
		$counter = $this->dbConn->GetOne($nsExist);
		if ($counter > 0) {
				echo "Namespace already exist";
				$success;
			}	else { 
				$this->dbConn->Execute("BEGIN SDO_RDF.ADD_NAMESPACES('".$namespace."'); END;");
				print "Namespace was added successfull!";
				$success=true;
			}
		return $success;
	}

	 /**
	 * Returns an array of all XML namespaces (unique)
	 * used in the model (not the namespaces from the namespace table; 
	 * just namespaces that are used in statements).
	 *
	 * @return string[] Array of XML namespaces.
	 */
	
	public function listNamespaces(){
		$query = "SELECT DISTINCT a.VALUE_NAME.getUrl() FROM MDSYS.RDF_VALUE$ a, MDSYS.RDFM_".$this->modelName." b WHERE a.VALUE_ID = b.START_NODE_ID OR a.VALUE_ID = b.END_NODE_ID OR a.VALUE_ID = b.P_VALUE_ID";
		
		// temp array
		$tmpArray = array();
		$rs = $this->dbConn->Execute($query);
		if ($rs){
			$i = 0; //counter for the new array keys
			 while ($arr = $rs->FetchRow()) {
				 if (strpos($arr[0],"#") == FALSE) {
					  // substring from position first character to last "/"
					$newValue = substr($arr[0],0,strrpos($arr[0],'/'));
					$tmpArray[$i] = $newValue;
				 }
				 else{
					 // substring from fist character to "#" 
					$newValue = substr($arr[0],0,strrpos($arr[0],'#'));
					$tmpArray[$i] = $newValue;
				 }
			 $i++; 
			 }
		}
		
		// put unique namespaces into new array
		$arrNamespaces = array_unique($tmpArray); 
		sort($arrNamespaces);
		return $arrNamespaces;
	}
?>
