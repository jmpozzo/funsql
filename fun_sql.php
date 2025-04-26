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

	private $conection;

	public function __construct($srv, $usr, $pass, $dbname){
		$this->srv = $srv;
		$this->usr = $usr;
		$this->pass = $pass;
		$this->dbname = $dbname;
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
			if($metodo != 0 && is_string($and[2])){
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
				$and_s = $and_s." ".$andor." ".$and[0]." ".$and[1]." ".$and[2];
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
		/*} else {
			if (is_array($campos)) {
				if (!$campos[key($campos)]) {
					$campos[key($campos)] = "NULL";
				} else if ($metodo == 0 && is_string($campos[key($campos)])) {
					$campos[key($campos)] = "'".$campos[key($campos)]."'";
				};
				if (!is_int(key($campos))) {
					$campos[0] = key($campos)."=".$campos[key($campos)];
				};
				$campos = $campos[0];
			};
			$campos_s = $campos;*/
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
			if ($metodo != 0 && is_string($condiciones[2])) {
				if ($condiciones[1] !== "IN" && $condiciones[1] !== "NOT IN") {
					$condiciones[2] = "'".$condiciones[2]."'";
				};
			};
			$condiciones_s = $condiciones[0]." ".$condiciones[1]." ".$condiciones[2];
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
				$resultado = $this->conection->query($query);
				if ($resultado && mysqli_num_rows($resultado)) {
					$rtn = [];
					while ($row = $resultado->fetch_assoc()){
						array_push($rtn,$row);
					};
					if (sizeof($rtn)<=1) {
						$rtn = $rtn[0]; 
					};
					if(!isset($rtn[0])) $rtn = [$rtn];
					return $rtn;
				} else {
					return null;
				};
			break;
			case 'update':
				$resultado = $this->conection->query($query);
				if ($resultado === TRUE) {
					return true;
				} else {
					return false;
				};
			case 'create':
				$resultado = $this->conection->query($query);
				if ($resultado === TRUE) {
					return $this->query("select","SELECT LAST_INSERT_ID() lastId")[0];
				} else {
					return false;
				};
			break;
			case 'remove':
				$resultado = $this->conection->query($query);
				if ($resultado === TRUE) {
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
		if($data["tables"]){
			if(!array_key_exists("join", $data)) {
				$data["join"] = null;
			};
			$data["tables"] = $this->innerJoin($data["tables"], $data["join"]);
		} else {
			return;
		};
		/*if (is_array($data["tables"])) {
			switch (true) {
				case (is_array($data["tables"][0])):
					$data["tables"] = " FROM ".$data["tables"][0][0]." ".$data["tables"][1]." JOIN (".str_replace($data["tables"][0][0].", ","",$this->comar(1,$data["tables"][0])).") ";
				break;
				case (sizeof($data["tables"])>1):
					$data["tables"] = " FROM ".$data["tables"][0]." INNER JOIN (".str_replace($data["tables"][0].", ","",$this->comar(1,$data["tables"])).") ";
				break;
			};
		} else {
			$data["tables"] = " FROM ".$data["tables"];
		};*/
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
}
?>