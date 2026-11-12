<?php
						//$campos[key($campos)] = ltrim($campos[key($campos)],"_");
//header("Access-Control-Allow-Origin: *");
//new db("direccion_server","usuario","contraseña","baseDeDatos_nombre");

////METODOS
///Los metodos pueden estar compuestos en sus propiedades por arrays. Ej: tablas="tabla" o tablas=["tabla1","tabla2"]
//select(tabla/s,campo/s,condicional/es,foragneo/s) _____ Condicional y foragneo no son obligatorios.

class db{
	public $srv;
	public $usr;
	public $pass;
	public $dbname;
	public $fieldInfo;
	public $error;

	private $conection;

	public function __construct($srv, $usr, $pass, $dbname, $dbfieldsInfo = null){
		$this->srv = $srv;
		$this->usr = $usr;
		$this->pass = $pass;
		$this->dbname = $dbname;
		$this->fieldInfo = $dbfieldsInfo;
	}
	public function conectar(){
		$this->conection = new mysqli($this->srv, $this->usr, $this->pass, $this->dbname);
		$this->conection->set_charset("utf8");
		if(!$this->conection) {
			return "Error de conexion";
			die();
		} else {
			return "Conecto;\n";
		};
	}
	public function cierro(){
		$this->conection->close();
	}
	private function and($metodo,$ands){
		$and_s = null;
		foreach ($ands as $and) {
			if($metodo != 0 && (isset($and[2]) && is_string($and[2]))){
				if ($and[1] !== "IN" && $and[1] !== "NOT IN") {
					$and[2] = "'".$and[2]."'";
				};
			};
			if (!$and_s) {
				$and_s = $and[0]." ".$and[1]." ".$and[2];
			} else {
				$andor = "AND";
				if(sizeof($and)==4){
					switch ($and[3]) {
						case 'OR':
							$andor = "OR";
							break;
					};
				};
				$and_s = $and_s." ".$andor." ".((isset($and[0])) ? $and[0] : "")." ".((isset($and[1])) ? $and[1] : "")." ".((isset($and[2])) ? $and[2] : "");
			};
		};
		return $and_s;
	}
	private function comar($metodo,$campos){
		$campos_s = null;
		//echo sizeof($campos)."\n";
		if (is_array($campos)) {
			foreach($campos as $key => $campo) {
				if (!$campo) {
					$campo = "NULL";
				} else if ($metodo == 1){
					switch (true){
						case (is_array($campo)):
							$campo = "'".$campo[0]."'";
						break;
					};
				};
				if (!is_int($key)) {
					$campo = $key."=".$campo;
				};
				if (!$campos_s) {
					$campos_s = $campos_s.$campo;
				} else {
					$campos_s = $campos_s.", ".$campo;
				};
			};
		};
		return $campos_s;
	}
	private function wAndInner($metodo,$condiciones){
		$condiciones_s = null;
		if (is_array($condiciones[0]) && sizeof($condiciones)>1) {
			$condiciones_s = $this->and($metodo, $condiciones);		
		} else {
			if (is_array($condiciones[0]) && sizeof($condiciones)>0) {
				$condiciones = $condiciones[0];
			};
			if ($metodo != 0 && (isset($condiciones[2]) && is_string($condiciones[2]))) {
				if ($condiciones[1] !== "IN" && $condiciones[1] !== "NOT IN") {
					$condiciones[2] = "'".$condiciones[2]."'";
				};
			};
			$condiciones_s = $condiciones[0]." ".$condiciones[1]." ".(isset($condiciones[2]) ? $condiciones[2] : "");
		};
		switch ($metodo) {
			case 0:
			case 2:
				$condiciones_s = " ON ".$condiciones_s;
				break;
			case 1:
				$condiciones_s = "WHERE ".$condiciones_s;
				break;
		};
		return $condiciones_s;
	}
	private function innerJoin($tables, $inner){
		if(!$inner) {
			$inner = "INNER";
		};

		if(is_array($tables)) {
			switch (true){
				case (is_array($tables[0])):
					$tables = " FROM ".$tables[0][0]." ".$tables[1]." JOIN (".str_replace($tables[0][0].", ","",$this->comar(1,$tables[0])).") ";
					break;
				case (sizeof($tables)==1):
					$tables = " FROM ".$tables[0];
					break;
				default:
					$tables = " FROM ".$tables[0]." ".$inner." JOIN (".str_replace($tables[0].", ","",$this->comar(1,$tables)).") ";
				};
		} else {
			$tables = " FROM ".$tables;
		};
		return $tables;
	}
	private function query($caso, $query){
		switch ($caso) {
			case 'select':
				/* Desde PHP 8.1 mysqli lanza excepciones: un error (tabla o columna inexistente)
				   devuelve null como siempre, en lugar de cortar la ejecución. El detalle queda en $this->error */
				try {
					$resultado = $this->conection->query($query);
				} catch (mysqli_sql_exception $e){
					$this->error = $e->getMessage();
					return null;
				};
				if ($resultado && mysqli_num_rows($resultado)) {
					$rtn = [];
					if($resultado->num_rows > 0) {
						while ($row = $resultado->fetch_assoc()){
							array_push($rtn,$row);
						};
						return $rtn;
					} else {
						return null;
					};
				} else {
					return null;
				};
			break;
			case 'update':
				try {
					$this->conection->query($query);
					return true;
				} catch (mysqli_sql_exception $e){
					$this->error = $e->getMessage();
					return false;
				};
				break;
			case 'create':
				try {
					$this->conection->query($query);
					return $this->query("select","SELECT LAST_INSERT_ID() lastId")[0];
				} catch(mysqli_sql_exception $e){
					$this->error = $e->getMessage();
					return false;
				};
			break;
			case 'remove':
				try {
					$resultado = $this->conection->query($query);
				} catch (mysqli_sql_exception $e){
					$this->error = $e->getMessage();
					return false;
				};
				if ($resultado === TRUE && $this->conection->affected_rows) {
					return true;
				} else {
					return false;
				};
				break;
		};
	}
	public function select($retQ, $tablas, $campos, $condiciones = null, $foragneas = null, $order = null, $limit = null){
		if (is_array($campos)) {
			$campos = $this->comar(1,$campos);
		};
		if (is_array($tablas)) {
			switch (true) {
				case (is_array($tablas[0])):
					$tablas = " FROM ".$tablas[0][0]." ".$tablas[1]." JOIN (".str_replace($tablas[0][0].", ","",$this->comar(1,$tablas[0])).") ";
				break;
				case (sizeof($tablas)>1):
					$tablas = " FROM ".$tablas[0]." INNER JOIN (".str_replace($tablas[0].", ","",$this->comar(1,$tablas)).") ";
				break;
			};
		} else {
			$tablas = " FROM ".$tablas;
		};
		if($condiciones){
			$condiciones = $this->wAndInner(1,$condiciones);
		};
		if($foragneas){
			$foragneas = $this->wAndInner(0,$foragneas);
		};
		if($order){
			switch ($order[1]) {
				case true:
					$order[1] = "ASC";
					break;
				case false:
					$order[1] = "DESC";
					break;
			};
			$order = " ORDER BY ".$order[0]." ".$order[1];
		};
		if($limit){
			$limit = " LIMIT ".$limit;
		}
		$consulta = "SELECT ".$campos.$tablas.$foragneas." ".$condiciones.$order.$limit;
		if ($retQ) {
			return $consulta;
		};
		$rtn = $this->query("select",$consulta);
		return $rtn;
	}
	public function select_nw($retQ, $data){
		if (is_array($data["field"])) {
			$data["field"] = $this->comar(1,$data["field"]);
		};
		if(isset($data["join"]) && is_array($data["join"])){
			$data["tables"] = $this->innerJoin($data["tables"], null);
			foreach ($data["join"] as $j_k=>$j_v) {
				if(!isset($data["foreign"])) $data["foreign"] = [];
				$data["foreign"][$j_k] = ((isset($j_v[2]))?$j_v[2]:"INNER")." JOIN ".$j_v[0].$this->wAndInner(0,$j_v[1]);
			};
			$data["foreign"] =" ".implode(" ", $data["foreign"]);
		} else {
			if($data["tables"]){
				if(!isset($data["join"])) {
					$data["join"] = null;
				};
				$data["tables"] = $this->innerJoin($data["tables"], $data["join"]);
			} else {
				return;
			};
			if(isset($data["foreign"])) {
				$data["foreign"] = $this->wAndInner(0,$data["foreign"]);
			} else {
				$data["foreign"] = null;
			};
		};
		if(isset($data["conditions"])) {
			$data["conditions"] = $this->wAndInner(1,$data["conditions"]);
		} else {
			$data["conditions"] = null;	
		};
		if(isset($data["order"])) {
			switch ($data["order"][1]) {
				case true:
					$data["order"][1] = "ASC";
					break;
				case false:
					$data["order"][1] = "DESC";
					break;
			};
			$data["order"] = " ORDER BY ".$data["order"][0]." ".$data["order"][1];
		} else {
			$data["order"] = null;
		};

		if(isset($data["limit"])) {
			$data["limit"] = " LIMIT ".$data["limit"];
		} else {
			$data["limit"] = null;
		};
		if(isset($data["group"])) {
			$data["group"] = " GROUP BY ".$data["group"];
		} else {
			$data["group"] = null;
		};
		$consulta = "SELECT ".$data["field"].$data["tables"].$data["foreign"]." ".$data["conditions"].$data["group"].$data["order"].$data["limit"];
		if ($retQ) {
			return $consulta;
		};
		$rtn = $this->query("select",$consulta);
		return $rtn;
	}
	public function create($retQ,$tablaInsert,$campoVal, $condicional = null,$tablasInner = null,$foragneos = null){
		$campos = [];
		$valores = [];
		foreach ($campoVal as $key => $value){
			$campos[sizeof($campos)] = $key;
			if (is_array($value)) {
				$valores[sizeof($valores)] = "'".$value[0]."'";
			} else {
				$valores[sizeof($valores)] = $value;
			}
		};
		$campos = " (".$this->comar(1,$campos).") ";
		if (!$tablasInner) {
			$tablasInner = "DUAL";
		};
		$consulta = "INSERT INTO ".$tablaInsert.$campos.$this->select(true,$tablasInner,$valores,$condicional,$foragneos, null, 1);
		if ($retQ) {
			return $consulta; 
		};
		$rtn = $this->query("create",$consulta);
		return  $rtn;
	}
	
	//$retQ => bool, cuando es true, no ejecuta la consulta y la devuelve en formato texto, false ejecuta la consulta
	//$tablas => si usas una tabla sola, va suelto, pero si son varias, va en un array
	//$campoVal => Si solo modificas un campo, este lo pones dentro de un array, seguido por el valor (["campo"=>"valor"])
	//$condiciones = nul
	//$foragneas = null
	public function update($retQ, $tablas, $campoVal, $condiciones = null, $foragneas = null){
		$campoVal = $this->comar(1, $campoVal);
		if (is_array($tablas)) {
			switch (true) {
				case (sizeof($tablas)>1):
					$tablas =$tablas[0]." INNER JOIN (".str_replace($tablas[0].", ","",$this->comar(1,$tablas)).") ";
				break;
			};
		};
		if($condiciones){
			$condiciones = $this->wAndInner(1,$condiciones);
		};
		if($foragneas){
			$foragneas = $this->wAndInner(0,$foragneas);
		};
		$consulta = "UPDATE ".$tablas.$foragneas." SET ".$campoVal." ".$condiciones;
		if ($retQ) {
			return $consulta; 
		};
		$rtn = $this->query("update",$consulta);
		return $rtn;
	}

	public function multiUpdate($retQ, $tablas, $campoVal, $condiciones, $foragneas = null) {
		if (!empty($campoVal["multi"])) {
			$whenThen = $campoVal["attr"][0]." = CASE ".$campoVal["attr"][1]." ";
			foreach ($campoVal["values"] as $updateKey => $updateVal) {
				$whenThen =$whenThen."WHEN ".$updateKey." THEN ".$updateVal." ";
			};
			$whenThen = $whenThen." END";
			$campoVal = $whenThen;
		} else {
			$campoVal = $this->comar(1, $campoVal);
		};
		if (is_array($tablas)) {
			switch (true) {
				case (sizeof($tablas)>1):
					$tablas =$tablas[0]." INNER JOIN (".str_replace($tablas[0].", ","",$this->comar(1,$tablas)).") ";
				break;
			};
		};
		if($condiciones){
			$condiciones = $this->wAndInner(1,$condiciones);
		};
		if($foragneas){
			$foragneas = $this->wAndInner(0,$foragneas);
		};
		$consulta = "UPDATE ".$tablas.$foragneas." SET ".$campoVal." ".$condiciones;
		if ($retQ) {
			return $consulta; 
		};
		$rtn = $this->query("update",$consulta);
		return $rtn;
	}

	public function nw_update($retQ, $data){
		$campoVal = $data["field_val"];
		if (!empty($campoVal["multi"])) {
			$whenThen = $campoVal["attr"][0]." = CASE ".$campoVal["attr"][1]." ";
			foreach ($campoVal["values"] as $updateKey => $updateVal) {
				$whenThen =$whenThen."WHEN ".$updateKey." THEN ".$updateVal." ";
			};
			$whenThen = $whenThen." END";
			$campoVal = $whenThen;
		} else {
			$campoVal = $this->comar(1, $campoVal);
		};

		$tablas = $data["tables"];
		if (is_array($tablas)) {
			switch (true) {
				case (sizeof($tablas)>1):
					$tablas =$tablas[0]." INNER JOIN (".str_replace($tablas[0].", ","",$this->comar(1,$tablas)).") ";
				break;
				case (sizeof($tablas) == 1):
					$tablas = $tablas[0];
				break;
			};
		};

		$condiciones = $data["conditions"];
		if($condiciones){
			$condiciones = $this->wAndInner(1,$condiciones);
		};

		$consulta = "UPDATE ".$tablas." SET ".$campoVal." ".$condiciones;
		if($retQ){
			return $consulta;
		};
		$rtn = $this->query("update",$consulta);
		return $rtn;
	}

	public function remove($retQ, $tabla, $condiciones = null){
		if($condiciones){
			$condiciones = $this->wAndInner(1,$condiciones);
		};
		$consulta = "DELETE ".$tabla." FROM ".$tabla." ".$condiciones;
		if ($retQ) {
			return $consulta;
		};
		$rtn = $this->query("remove",$consulta);
		return $rtn;
	}

	public function remove_nw($retQ, $data){
		if (is_array($data["tables"])) {
			switch (true) {
				case (is_array($data["tables"][0])):
					$data["tables"] = implode(", ", $data["tables"])." FROM ".$data["tables"][0][0].$data["tables"][1]." JOIN (".str_replace($data["tables"][0][0].", ","",$this->comar(1,$data["tables"][0])).") ";
				break;
				case (sizeof($data["tables"])>1):
					$data["tables"] = implode(", ", $data["tables"])." FROM ".$data["tables"][0]." JOIN (".str_replace($data["tables"][0].", ","",$this->comar(1,$data["tables"])).") ";
				break;
			};
		} else {
			$data["tables"] = " FROM ".$data["tables"];
		};
		if(isset($data["conditions"])) {
			$data["conditions"] = $this->wAndInner(1,$data["conditions"]);
		} else {
			$data["conditions"] = null;	
		};
		if(isset($data["foreign"])) {
			$data["foreign"] = $this->wAndInner(0,$data["foreign"]);
		} else {
			$data["foreign"] = null;
		};
		$consulta = "DELETE ".$data["tables"].$data["foreign"]." ".$data["conditions"];
		if ($retQ) {
			return $consulta;
		};
		$rtn = $this->query("remove",$consulta);
		return $rtn;
	}

	public function getDataBaseFields(){

		$dataBaseFields = $this->select_nw(false, [
			"tables"=>["INFORMATION_SCHEMA.COLUMNS c", "INFORMATION_SCHEMA.KEY_COLUMN_USAGE k"],
			"field"=>[
				"c.TABLE_NAME table_name",
				"c.COLUMN_NAME field_name",
				"c.DATA_TYPE field_type",
				"c.IS_NULLABLE field_unset",
				"c.COLUMN_KEY key_type",
				"k.REFERENCED_TABLE_NAME foreign_table",
				"k.REFERENCED_COLUMN_NAME foreign_field "
			],
			"join"=>"LEFT",
			"foreign"=>[
				["c.TABLE_SCHEMA", "=", "k.TABLE_SCHEMA"],
				["c.TABLE_NAME", "=", "k.TABLE_NAME"],
				["c.COLUMN_NAME", "=", "k.COLUMN_NAME"]
			],
			"conditions"=>["c.TABLE_SCHEMA", "=", $this->dbname],
			"order"=>["c.TABLE_NAME", true]
		]);

		$rtn = [];

		foreach ($dataBaseFields as $field) {
			if(!isset($rtn[$field["table_name"]])) $rtn[$field["table_name"]] = [];
			$rtn[$field["table_name"]][$field["field_name"]] = [
				"field_type"=>$field["field_type"],
				"field_null"=>$field["field_unset"],
				"field_key"=>$field["key_type"],
				"foreign_table"=>$field["foreign_table"],
				"foreign_field"=>$field["foreign_field"]
			];
		};

		if(sizeof($rtn) == 0) return false;
		return $rtn;
	}

	/* ------------------------------------------------------------
	 * SCRIPTS SQL (estructura, migraciones)
	 * ------------------------------------------------------------ */

	/* Separa un script en sentencias. Respeta comillas ('...', "...", `...`)
	   y quita comentarios (-- ..., # ..., /* ... *\/). */
	public function split_script($sql){
		$statements = [];
		$current = "";
		$length = strlen($sql);
		$quote = null;
		for ($i = 0; $i < $length; $i++) {
			$char = $sql[$i];
			$next = ($i + 1 < $length) ? $sql[$i + 1] : "";
			if ($quote !== null) {
				$current .= $char;
				if ($char === "\\" && $quote !== "`" && $next !== "") { $current .= $next; $i++; continue; };
				if ($char === $quote) $quote = null;
				continue;
			};
			if ($char === "'" || $char === '"' || $char === "`") { $quote = $char; $current .= $char; continue; };
			if (($char === "-" && $next === "-") || $char === "#") {
				while ($i < $length && $sql[$i] !== "\n") $i++;
				$current .= "\n";
				continue;
			};
			if ($char === "/" && $next === "*") {
				$end = strpos($sql, "*/", $i + 2);
				$i = ($end === false) ? $length : $end + 1;
				$current .= " ";
				continue;
			};
			if ($char === ";") {
				if (trim($current) !== "") $statements[] = trim($current);
				$current = "";
				continue;
			};
			$current .= $char;
		};
		if (trim($current) !== "") $statements[] = trim($current);
		return $statements;
	}

	/* Ejecuta un script SQL sentencia por sentencia.
	   $ignore: códigos de error de MySQL que se toman como "ya aplicado"
	   (ej. 1060 columna duplicada, 1050 tabla existente, 1061 índice duplicado,
	   1091 no existe lo que se quiere borrar).
	   Devuelve ["status"=>bool, "statements"=>[["sql","status","skipped","errno","error"], ...]].
	   Se detiene en el primer error no ignorado. Con $retQ = true solo devuelve las sentencias. */
	public function run_script($retQ, $sql, $ignore = []){
		$statements = $this->split_script($sql);
		if ($retQ) return $statements;

		$rtn = ["status"=>true, "statements"=>[]];
		foreach ($statements as $statement) {
			$item = ["sql"=>$statement, "status"=>true, "skipped"=>false, "errno"=>0, "error"=>null];
			try {
				$result = $this->conection->query($statement);
				if ($result === false) throw new mysqli_sql_exception($this->conection->error, $this->conection->errno);
				if ($result instanceof mysqli_result) $result->free();
			} catch (mysqli_sql_exception $e) {
				$item["errno"] = (int)$e->getCode();
				$item["error"] = $e->getMessage();
				if (in_array($item["errno"], $ignore, true)) {
					$item["skipped"] = true;
				} else {
					$item["status"] = false;
					$rtn["status"] = false;
					$this->error = $item["error"];
				};
			};
			$rtn["statements"][] = $item;
			if (!$item["status"]) break;
		};
		return $rtn;
	}

	/* Escapa un valor para usarlo dentro de comillas en una consulta */
	public function escape($value){
		if ($value === null) return null;
		return $this->conection->real_escape_string((string)$value);
	}
}
?>