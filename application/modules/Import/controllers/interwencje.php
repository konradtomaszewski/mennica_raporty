<?php
header('Access-Control-Allow-Origin:*');
defined('BASEPATH') OR exit('No direct script access allowed');

class Interwencje extends MX_Controller {
	public $path_dir = './imported_files/interwencje/';
	public $excel_file = null;

	public function __construct()
	{
		parent::__construct();
		ini_set('max_execution_time', 0); 
		ini_set('memory_limit','2048M');
	}

	public function index()
	{
		$data['title'] = "Zarządzenie danymi";
		$this->load->view("template/header", $data);
		$this->load->view("interwencje", $data);
		$this->load->view("template/footer");
	}

	public function run_import()
	{
		$this->db->cache_off();
		//usun tabulator z pierwszej linii
		if ($handle = opendir($this->path_dir)) {
			while (false !== ($this->excel_file = readdir($handle))) {
				if ($this->excel_file != "." && $this->excel_file != "..") {
					$plik = $this->path_dir.$this->excel_file;
					$zawartosc=file($plik);
			
					//pobranie do zmiennej pierwszej linii bez tabulatora
					$no_tabs = trim($zawartosc[0]);
					//usunięcie pierwszej linii
					unset($zawartosc[0]);
		
					$tresc = implode("", (array)$zawartosc);
					 
					$plik = fopen($plik,"w+");
					//zapis pierwszej linii bez tabulatora na początku pliku
					fwrite($plik,$no_tabs);
	
					//dalsza część pliku
					fwrite($plik,$tresc);

					fclose($plik);
				}
			}
			closedir($handle);
		}
					
		if ($handle = opendir($this->path_dir)) {
			while (false !== ($this->excel_file = readdir($handle))) {
				if ($this->excel_file != "." && $this->excel_file != "..") {
					$m = explode("_", $this->excel_file);
					$mob = explode(".", $this->excel_file);
					$miasto = $m[1];
					$mobilne = substr($mob[0],-3);
					if($mobilne == 'mob')
					{
						$miasto = $m[1]."mob";
					}
					else $miasto = $m[1];
					
					Interwencje::parse_xml($this->path_dir,$this->excel_file, $miasto);
				}
				//Insert into imported file
				if ($this->excel_file != "." && $this->excel_file != "..") {
					$result = $this->db->query("SELECT * FROM imported_files WHERE filename='$this->excel_file'");
					$check_exist = $result->num_rows();
					if($check_exist == 0)
					{
						$this->db->query("INSERT INTO imported_files (id, filename, miasto, import_date, filetype) VALUES (NULL, '$this->excel_file', '$miasto', NOW(), 'interwencje')");
					}
				}
			}
			closedir($handle);
		}	
	}

	public function parse_xml($path_dir,$excel_file,$miasto)
	{		
		if ( $excel_file ){
			$dom = new DOMDocument();
			$dom->load( $path_dir.$excel_file );
			$rows = $dom->getElementsByTagName( 'Row' );
			$first_row = true;
			foreach ($rows as $row){
				if ( !$first_row ){
					$nra = "";
					$data = "";
					$serwisant = "";
					$usterka = "";
	
					$index = 1;
					$cells = $row->getElementsByTagName( 'Cell' );
					foreach( $cells as $cell ){
						$ind = $cell->getAttribute( 'ss:Index' );
						if ( $ind != null ) $index = $ind;
	
						if ( $index == 2 ) $nra = trim($cell->nodeValue);
						if ( $index == 4 ) $data = $cell->nodeValue;
						if ( $index == 5 ) $serwisant = trim($cell->nodeValue);
						if ( $index == 12) $usterka = trim($cell->nodeValue);
						if ( $index == 13) $rodzaj_usterki = trim($cell->nodeValue);
	
						$index += 1;
					}
					Interwencje::add_interwencje($nra, $data, $serwisant, $rodzaj_usterki, $usterka, $miasto, $excel_file);
				}
				$first_row = false;
			}


		}
	}

	public function add_interwencje($nra, $data, $serwisant, $rodzaj_usterki, $usterka, $miasto, $excel_file)
	{
		$dane= array(
			'nra' => $nra,
			'data' => $data,
			'serwisant' => $serwisant,
			'usterka' => $usterka,
			'rodzaj_usterki' => $rodzaj_usterki,
			'miasto' => $miasto
		);
	
		if($nra>0){
			$result = $this->db->query("SELECT * FROM interwencje WHERE nra='$nra' AND data='$data' AND serwisant='$serwisant' AND usterka='$usterka'  AND rodzaj_usterki='$rodzaj_usterki' AND miasto='$miasto'");

			$check_exist = $result->num_rows();
			if($check_exist == 0)
			{
				$this->db->insert('interwencje', $dane);
				//$this->db->query("INSERT INTO interwencje (id, nra, data, serwisant, usterka, rodzaj_usterki,miasto) VALUES (NULL, '$nra_val', '$data', '$serwisant', '$usterka', '$rodzaj_usterki', '$miasto');");
			}
		}
	}

	public function import_result_details()
	{
		$this->db->cache_off();
		$query = $this->db->query("SELECT filename,miasto FROM imported_files WHERE number_of_records='0' AND filetype='interwencje'");
		$check_exist = $query->num_rows();
		if($check_exist > 0)
		{
			foreach($query->result() as $row)
			{
				$filename = $row->filename;
				$d = explode("_",$filename);
							
				$miasto = $row->miasto;
				$mob = substr($miasto,-3);
				if($mob == 'mob')
				{
					$data = substr($d[2],0,-7);
				}else{
					$data = substr($d[2],0,-4);
				}

				$count_query = $this->db->query("SELECT count(id) as 'ilosc' FROM interwencje WHERE miasto = '$miasto' AND date(data)='$data'");
				foreach($count_query->result() as $count)
				{
					$this->db->query("UPDATE imported_files SET number_of_records='$count->ilosc' WHERE miasto = '$miasto' AND filename='$filename'");
				}
			}
		}

		if($_POST['data_od'] && $_POST['data_do']){
			$data_od = $_POST['data_od'];
			$data_do = $_POST['data_do'];
		}else{
			$data_od = date("Y-m-d");
			$data_do = date("Y-m-d");
		}
		
		$details = $this->db->query("SELECT filename,number_of_records,import_date FROM imported_files WHERE date(import_date) BETWEEN '$data_od' AND '$data_do' AND filetype='interwencje'");
		$check_exist = $details->num_rows();
		if($check_exist > 0)
		{
			$result = array();
			foreach($details->result() as $row)
			{
				$temp = array();
				$temp['import_date'] = $row->import_date;
				$temp['filename'] = $row->filename;
				$temp['number_of_records'] = $row->number_of_records;
				$result[] = $temp;
			}
			echo '<table class="mdl-data-table mdl-js-data-table  mdl-shadow--2dp projects-table">
			<thead>
			<tr>
				<th class="mdl-data-table__cell--non-numeric">Data wykonania importu</th>
				<th class="mdl-data-table__cell--non-numeric">Zaimportowane pliki</th>
				<th class="mdl-data-table__cell--non-numeric">Ilość rekordów w pliku</th>
			</tr>
			</thead>
			<tbody>';			
			
			foreach($details->result() as $row)
			{
				echo "<tr>";	
				echo "<td class='mdl-data-table__cell--non-numeric'>".$row->import_date."</td>";
				echo "<td class='mdl-data-table__cell--non-numeric'>".$row->filename."</td>";
				echo "<td class='mdl-data-table__cell--non-numeric'>".$row->number_of_records."</td>";
				echo "</tr>";
			}
			echo '</tbody></table>';
		}
		else
		{
			 echo '<table class="mdl-data-table mdl-js-data-table  mdl-shadow--2dp projects-table">
				<thead>
				<tr>
					<th class="mdl-data-table__cell--non-numeric">Data importu</th>
					<th class="mdl-data-table__cell--non-numeric">Zaimportowany pliki</th>
					<th class="mdl-data-table__cell--non-numeric">Ilość rekordów</th>
				</tr>
				</thead>
				<tbody><tr><td class="mdl-data-table__cell--non-numeric" colspan="3"><div style="text-align:center; font-weight:bold">Niczego nie zaimportowano</div></td></tr></tbody></table>';	
		}
	}
}