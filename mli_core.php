<?php
ini_set("soap.wsdl_cache", "1");
ini_set("soap.wsdl_cache_enabled", "1");
ini_set("memory_limit", "1024M");
#ini_set("default_socket_timeout", "180");

function get_client() {
	$wsdl_url = "https://apitest.merlion.com/dl/mlservice3?wsdl";
	$params = array('login' => "TC0036357|API"
	, 'password' => "123456"
	, 'encoding' => "UTF-8"
	, 'features' => SOAP_SINGLE_ELEMENT_ARRAYS
	, 'cache_wsdl' => WSDL_CACHE_MEMORY
#	, 'connection_timeout' => 15
	);
	return new SoapClient($wsdl_url, $params);
}


function mli_writeFile($file, $data) {
	$handle = fopen($file, 'w+');
	fwrite($handle, $data);
	fclose($handle);
}

function mli_readFile($file, $type) {
	$handle = fopen($file, 'r');
	if ($type == 'serialize') {
		$res = unserialize(stream_get_contents($handle));
	} else {
		$res = stream_get_contents($handle);
	}
	fclose($handle);
	return $res;
}


function mb_ucfirst($str) {
	return mb_strtoupper(mb_substr($str, 0, 1)).mb_substr($str, 1);
}


function er_log($er) {
	$file = $_SERVER['DOCUMENT_ROOT'].'/wp-content/uploads/merlion_import/mli_log.txt';
	$handle = fopen($file, 'a+');
	fwrite($handle, date('Y-m-d H:i:s') . ': ' . $er . "\n");
	fclose($handle);
}


function getAvail($client, $cat, $method, $dates, $avail) {
	if ($client) {
		try {
			$items = $client->getItemsAvail(array('cat_id' => $cat, 'shipment_method' => $method, 'shipment_date' => $dates, 'only_avail' => $avail));
			$ar = array();
			foreach ($items->getItemsAvailResult->item as $row) {
				if($row->No != '') {
					$ar[] = array('sku' => $row->No, 'price_region' => $row->PriceClientRUB_RG, 'price_msk' => $row->PriceClientRUB_MSK, 'avail_region' => $row->AvailableClient_RG, 'avail_msk' => $row->AvailableClient_MSK);
				}
			}
			return $ar;
		} catch (Exception $e) {
			er_log('getItemsAvail: '.$e->faultstring);
		}
	}
}


function getProp($client, $cat, $page, $rows, $time) {
	if ($client) {
		try {
			$items = $client->getItemsProperties(array('cat_id' => $cat, 'page' => $page, 'rows_on_page' => $rows, 'last_time_change' => $time));
			$ar = array();
			foreach ($items->getItemsPropertiesResult->item as $row) {
				if ($row->No != '') {
					$ar[] = array('sku' => $row->No, 'attr_name' => $row->PropertyName, 'attr_val' => $row->Value, 'attr_pos' => $row->Sorting);
				}
			}
			return $ar;
		} catch (Exception $e) {
			er_log('getItemsProperties: '.$e->faultstring);
		}
	}
}


function getImg($client, $cat, $page, $rows, $time) {
	if ($client) {
		try {
			$items = $client->getItemsImages(array('cat_id' => $cat, 'page' => $page, 'rows_on_page' => $rows, 'last_time_change' => $time));
			$ar = array();
			foreach ($items->getItemsImagesResult->item as $row) {
				if ($row->SizeType == 'b') {
					$ar[] = array('sku' => $row->No, 'img' => "http://img.merlion.ru/items/".$row->FileName);
				}
			}
			return $ar;
		} catch (Exception $e) {
			er_log('getItemsImages: '.$e->faultstring);
		}
	}
}


function getShipmentDates($client) {
	if ($client) {
		try {
			$items = $client->getShipmentDates(array('code' => ''));
			$ar = $items->getShipmentDatesResult->item;
			return $ar[0]->Date;
		} catch (Exception $e) {
			er_log('getShipmentDates: '.$e->faultstring);
		}
	}
}


function setOrderHeaderCommand($client, $method, $dates) {
	if ($client) {
		try {
			$items = $client->setOrderHeaderCommand(array('document_no' => '', 'shipment_method' => $method, 'shipment_date' => $dates));
			$arr = $items->setOrderHeaderCommandResult;
			return $arr;
		} catch (Exception $e) {
			er_log('setOrderHeaderCommand: '.$e->faultstring);
		}
	}
}

function setOrderLineCommand($client, $no, $sku, $qty, $price) {
	if ($client) {
		try {
			$items = $client->setOrderLineCommand(array('document_no' => $no, 'item_no' => $sku, 'qty' => $qty, 'price' => $price));
			$arr = $items->setOrderLineCommandResult;
			return $arr;
		} catch (Exception $e) {
			er_log('setOrderLineCommand: '.$e->faultstring);
		}
	}
}

function getCommandResult($client, $no) {
	if ($client) {
		try {
			$items = $client->getCommandResult(array('operation_no' => $no));
			$arr = $items->getCommandResultResult->item;
			return $arr;
		} catch (Exception $e) {
			er_log('getCommandResult: '.$e->faultstring);
		}
	}
}

function setDeleteOrderCommand($client, $no) {
	if ($client) {
		try {
			$items = $client->setDeleteOrderCommand(array('operation_no' => $no));
			$arr = $items->setDeleteOrderCommandResult;
			return $arr;
		} catch (Exception $e) {
			er_log('setDeleteOrderCommand: '.$e->faultstring);
		}
	}
}

function getOrdersList($client, $no) {
	if ($client) {
		try {
			$items = $client->getOrdersList(array('document_no' => $no));
			$arr = $items->getOrdersListResult->item;
			return $arr;
		} catch (Exception $e) {
			er_log('getOrdersList: '.$e->faultstring);
		}
	}
}

function getOrderLines($client, $no) {
	if ($client) {
		try {
			$items = $client->getOrderLines(array('document_no' => $no));
			$arr = $items->getOrderLinesResult->item;
			return $arr;
		} catch (Exception $e) {
			er_log('getOrderLinesResult: '.$e->faultstring);
		}
	}
}



function getData($client, $cat, $page, $rows, $time) {
	if ($client) {
		try {
			$items = $client->getItems(array('cat_id' => $cat, 'page' => $page, 'rows_on_page' => $rows, 'last_time_change' => $time));
			$ar = array();
			foreach ($items->getItemsResult->item as $row) {
				if ($row->No != '') {
					$prop_ar = array();
					$prop_ar['warranty'] = array('name' => 'Гарантия', 'value' => $row->Warranty == 0 ? 'нет' : $row->Warranty.'мес.', 'position' => 1000, 'is_visible' => 1, 'is_variation' => 0, 'is_taxonomy' => 0);
					$prop_ar['min_packaged'] = array('name' => 'Мин. кол-во', 'value' => $row->Min_Packaged == 0 ? 'нет' : $row->Min_Packaged, 'position' => 1001, 'is_visible' => 1, 'is_variation' => 0, 'is_taxonomy' => 0);
					$prop_ar['vendor_part'] = array('name' => 'Партномер', 'value' => $row->Vendor_part, 'position' => 1002, 'is_visible' => 1, 'is_variation' => 0, 'is_taxonomy' => 0);
					if ($row->ActionDesc != '') {
						$prop_ar['action_desc'] = array('name' => 'Акция', 'value' => $row->ActionDesc, 'position' => 1003, 'is_visible' => 1, 'is_variation' => 0, 'is_taxonomy' => 0);
						$prop_ar['action_www'] = array('name' => 'Описание акции', 'value' => $row->ActionWWW, 'position' => 1004, 'is_visible' => 1, 'is_variation' => 0, 'is_taxonomy' => 0);
					}
					if ($row->EOL == 1) {
						$prop_ar['eol'] = array('name' => 'В производстве', 'value' => 'Снято с производства', 'position' => 1005, 'is_visible' => 1, 'is_variation' => 0, 'is_taxonomy' => 0);
					}

					$cat_name = mb_convert_case($row->GroupName1, MB_CASE_UPPER, "UTF-8");
					
					$ar[] = array(
					'sku' => $row->No,
					'post_title' => $row->Name.' ',
					'product_cat' => $cat_name.'>'.$row->GroupName2.'>'.$row->GroupName3.' ',
					'post_date' => $row->Last_time_modified,
					'images' => '',
					'pa_vendor' => $row->Brand,
					'product_attributes' => $prop_ar,
					'weight' => $row->Weight,
					'price_region' => '',
					'price_msk' => '',
					'avail_region' => 0,
					'avail_msk' => 0,
					'action' => $row->ActionDesc != '' ? 'Акция ' : ' ',
					'new' => ( strval(strtotime(date('Y-m-d'))) - strval(strtotime(substr($row->Last_time_modified,0,10))) )/86400 <= 30 ? 'Новинка ' : ' '
					);
				}
			}
			return $ar;
		} catch (Exception $e) {
			er_log('getItems: '.$e->faultstring);
		}
	}
}


if(isset($_POST['categories'])) {
	$ar = array('categories' => $_POST['categories'], 'shipment_type' => $_POST['shipment_type'], 'only_avail' => $_POST['only_avail'], 'selected_date' => $_POST['selected_date'], 'round_price' => $_POST['round_price'], 'exrate' => $_POST['exrate'], 'price_empty' => $_POST['price_empty']);
	mli_writeFile($_SERVER['DOCUMENT_ROOT'].'/wp-content/uploads/merlion_import/mli_settings.txt', serialize($ar));
	mli_writeFile($_SERVER['DOCUMENT_ROOT'].'/wp-content/uploads/merlion_import/mli_schedule.txt', $_POST['schedule_date']);
}

function getCron() {
	$schedule_full = $_SERVER['DOCUMENT_ROOT'].'/wp-content/uploads/merlion_import/full_run.txt';
	$schedule_avail = $_SERVER['DOCUMENT_ROOT'].'/wp-content/uploads/merlion_import/avail_run.txt';
	$cron_run = '';
	if (file_exists($schedule_full)) {
		$cron_run = 'full';
	} else if (file_exists($schedule_avail)) {
		$cron_run = 'avail';	
	}
	return $cron_run;
}
$cron_run = getCron();

if (isset($_POST['import']) || isset($_POST['import_price']) || isset($_GET['import']) || isset($_GET['import_price']) ||  $cron_run != '') {
	ignore_user_abort(true);
	set_time_limit(0);
	ob_start();
	$size = ob_get_length();
	header("Content-Length: $size");
	header('Connection: close');
	ob_end_flush();
	ob_flush();
	flush();
	if (session_id()) session_write_close();

	$mli_set = $_SERVER['DOCUMENT_ROOT'].'/wp-content/uploads/merlion_import/mli_settings.txt';
	if (file_exists($mli_set)) {
		$option = mli_readFile($mli_set, 'serialize');
		$categories = $option['categories'];
		$shipment_type = $option['shipment_type'];
		$only_avail = $option['only_avail'];
		$selected_date = $option['selected_date'];
		$round_price = $option['round_price'];
		$exrate = $option['exrate'];
		$price_empty = $option['price_empty'];
		$cron_run = getCron();
		if (isset($_GET['import']) || $cron_run == 'full') {
			$name = $_SERVER['DOCUMENT_ROOT'].'/wp-content/uploads/merlion_import/price_import.csv';
			if (file_exists($name) && $selected_date == '') {
				$schedule_date = explode(",", mli_readFile($_SERVER['DOCUMENT_ROOT'].'/wp-content/uploads/merlion_import/mli_schedule.txt', ''));
				$selected_date = date("Y-m-d", strtotime("-".$schedule_date[1]." week")).'T00:00:00';
			}
		}
		
		function translitIt($str) {
			$str = strtolower($str);
			$tr = array(
			"А"=>"A","Б"=>"B","В"=>"V","Г"=>"G",
			"Д"=>"D","Е"=>"E","Ж"=>"J","З"=>"Z","И"=>"I",
			"Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
			"О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
			"У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"TS","Ч"=>"CH",
			"Ш"=>"SH","Щ"=>"SCH","Ъ"=>"","Ы"=>"YI","Ь"=>"",
			"Э"=>"E","Ю"=>"YU","Я"=>"YA","а"=>"a","б"=>"b",
			"в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"j",
			"з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
			"м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
			"с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
			"ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
			"ы"=>"yi","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya",
			" "=>"_", "-"=>"_", "+"=>"_", "="=>"_"
			);
			return strtr($str,$tr);
		}

		function init_import($categories, $shipment_type, $only_avail, $selected_date, $round_price, $exrate, $price_empty, $cron_run) {
			mli_writeFile($_SERVER['DOCUMENT_ROOT'].'/wp-content/uploads/merlion_import/mli_run.txt', strval(getmypid()));
			$client = get_client();
			$all_data = array();
			$exrate = 1 + $exrate / 100;
			$dates = getShipmentDates($client);
			$query = 5000;
			$max_page = 2000;
			for ($c = 0; $c < count($categories); $c++) {
				$data_ar = array();
				$img_ar = array();
				$prop_ar = array();
				$avail_ar = array();
				
				if (isset($_POST['import']) || isset($_GET['import']) || $cron_run == 'full') {
					for ($page = 1; $page <= $max_page; $page++) {
						sleep(1);
						$data_product = getData($client, $categories[$c], $page, $query, $selected_date);
						if (count($data_product) == 0) {
							break;
						} else {
							$data_ar = array_merge($data_ar, $data_product);
						}
					}
					for ($page = 1; $page <= $max_page; $page++) {
						sleep(1);
						$img_product = getImg($client, $categories[$c], $page, $query, $selected_date);
						if (count($img_product) == 0) {
							break;
						} else {
							$img_ar = array_merge($img_ar, $img_product);
						}
					}
					for ($page = 1; $page <= $max_page; $page++) {
						sleep(1);
						$prop_product = getProp($client, $categories[$c], $page, $query, $selected_date);
						if (count($prop_product) == 0) {
							break;
						} else {
							$prop_ar = array_merge($prop_ar, $prop_product);
						}
					}
				}
				sleep(1);
				$avail_product = getAvail($client, $categories[$c], $shipment_type, $dates, $only_avail);
				$avail_ar = array_merge($avail_ar, $avail_product);

				if (isset($_POST['import']) || isset($_GET['import']) ||  $cron_run == 'full') {
					for ($i = 0; $i < count($data_ar); $i++) {
						for ($j = 0; $j < count($img_ar); $j++) {
							if ($data_ar[$i]['sku'] == $img_ar[$j]['sku']) {
								if ($data_ar[$i]['images'] == '') {
									$sep = '';
								} else {
									$sep = ',';
								}
								$data_ar[$i]['images'] .= $sep.$img_ar[$j]['img'];
							}
						}
					}
					unset($img_ar);
					
					$prop_ar_new = array();
					for ($i = 0; $i < count($data_ar); $i++) {
						$prop_ar_new[] = array('sku' => $data_ar[$i]['sku'], 'properties' => array());
					}
					for ($i = 0; $i < count($prop_ar_new); $i++) {
						for ($j = 0; $j < count($prop_ar); $j++) {
							if ($prop_ar_new[$i]['sku'] == $prop_ar[$j]['sku']) {
								$prop_ar_new[$i]['properties'][strtolower(translitIt($prop_ar[$j]['attr_name']))] = array('name' => $prop_ar[$j]['attr_name'], 'value' => $prop_ar[$j]['attr_val'], 'position' => $prop_ar[$j]['attr_pos'], 'is_visible' => 1, 'is_variation' => 0, 'is_taxonomy' => 0);
							}
						}
					}
					for ($i = 0; $i < count($data_ar); $i++) {
						for ($j = 0; $j < count($prop_ar_new); $j++) {
							if ($data_ar[$i]['sku'] == $prop_ar_new[$j]['sku']) {
								$attrs = array_merge($data_ar[$i]['product_attributes'], $prop_ar_new[$j]['properties']);
								$data_ar[$i]['product_attributes'] = serialize($attrs);
							}
						}
					}
					unset($prop_ar,$prop_ar_new);
					
					for ($i = 0; $i < count($data_ar); $i++) {
						for ($j = 0; $j < count($avail_ar); $j++) {
							if ($data_ar[$i]['sku'] == $avail_ar[$j]['sku']) {
								if ($avail_ar[$j]['price_region'] != 0.0) {
									$val = strval($avail_ar[$j]['price_region']) * $exrate;
									$val = number_format((float)$val, 2, '.', '');
									if ($round_price == 1) {
										if ($val < 10) {
											$val = round($val, 0);
										} else {
											$val = round($val, -1);
										}
									}
									$data_ar[$i]['price_region'] = $val;
								}
								if ($avail_ar[$j]['price_msk'] != 0.0) {
									$val = strval($avail_ar[$j]['price_msk']) * $exrate;
									$val = number_format((float)$val, 2, '.', '');
									if ($round_price == 1) {
										if ($val < 10) {
											$val = round($val, 0);
										} else {
											$val = round($val, -1);
										}
									}
									$data_ar[$i]['price_msk'] = $val;
								}
								$data_ar[$i]['avail_region'] = $avail_ar[$j]['avail_region'];
								$data_ar[$i]['avail_msk'] = $avail_ar[$j]['avail_msk'];
							}
						}
					}
					unset($avail_ar);
				} else if (isset($_POST['import_price']) || isset($_GET['import_price']) ||  $cron_run == 'avail') {
					
					$avail_ar = array_unique($avail_ar, SORT_REGULAR);
					for ($j = 0; $j < count($avail_ar); $j++) {
						if ($avail_ar[$j]['price_region'] != 0.0) {
							$pr = strval($avail_ar[$j]['price_region']) * $exrate;
							$pr = number_format((float)$pr, 2, '.', '');
							if ($round_price == 1) {
								if ($pr < 10) {
									$pr = round($pr, 0);
								} else {
									$pr = round($pr, -1);
								}
							}
						} else {
							$pr = '';
						}
						if ($avail_ar[$j]['price_msk'] != 0.0) {
							$pm = strval($avail_ar[$j]['price_msk']);
							$pm = number_format((float)$pm, 2, '.', '');
							if ($round_price == 1) {
								if ($pm < 10) {
									$pm = round($pm, 0);
								} else {
									$pm = round($pm, -1);
								}
							}
						} else {
							$pm = '';
						}
						$data_ar[] = array(
						'sku' => $avail_ar[$j]['sku'],
						'price_region' => $pr,
						'price_msk' => $pm,
						'avail_region' => $avail_ar[$j]['avail_region'],
						'avail_msk' => $avail_ar[$j]['avail_msk']
						);
					}
					unset($avail_ar);
				}
				if ($only_avail == 1 || $only_avail == '1') {
					$data_ar_new = array();
					for ($i = 0; $i < count($data_ar); $i++) {
						if ($data_ar[$i]['avail_region'] > 0 || $data_ar[$i]['avail_msk'] > 0) {
							$data_ar_new[] = $data_ar[$i];
						}
					}
					unset($data_ar);
					$data_ar = $data_ar_new;
					unset($data_ar_new);
				}
				
				if ($price_empty == 1 || $price_empty == '1') {
					$data_ar_new = array();
					for ($i = 0; $i < count($data_ar); $i++) {
						if ($data_ar[$i]['price_region'] != '') {
							$data_ar_new[] = $data_ar[$i];
						}
					}
					unset($data_ar);
					$data_ar = $data_ar_new;
					unset($data_ar_new);
				}
				
				if (count($data_ar) != 0) {
					$all_data = array_merge($all_data, $data_ar);
					unset($data_ar);
				}
				
			}

			if (isset($_POST['import']) || isset($_GET['import']) || $cron_run == 'full') {
				$headers_ar = array(
					'sku',
					'post_title',
					'product_cat',
					'post_date',
					'images',
					'pa_vendor',
					'product_attributes',
					'weight',
					'regular_price_region',
					'regular_price_msk',
					'stock_region',
					'stock_msk',
					'action_tag',
					'new_tag'
				);
				array_unshift($all_data, $headers_ar);
			}

			if (isset($_POST['import']) || isset($_GET['import']) || $cron_run == 'full') {
				$name = $_SERVER['DOCUMENT_ROOT'].'/wp-content/uploads/merlion_import/price_import.csv';
				$price = fopen($name, "w");
				foreach ($all_data as $row) {
					fputcsv($price, $row);
				}
				fclose($price);
				copy($name, $_SERVER['DOCUMENT_ROOT'].'/wp-content/uploads/wpallimport/files/price_import.csv');
			} else if (isset($_POST['import_price']) || isset($_GET['import_price']) ||  $cron_run == 'avail') {
				/*foreach ($all_data as $row) {
					$product_id = wc_get_product_id_by_sku($row['sku']);
					$regular_price = $row['price_region'];
					$qty = $row['avail_region'];
					update_post_meta( $product_id, '_regular_price', $regular_price );
					update_post_meta( $product_id, '_stock', $qty );
					wc_delete_product_transients( $product_id );
				}*/
			}
			
			unlink($_SERVER['DOCUMENT_ROOT'].'/wp-content/uploads/merlion_import/mli_run.txt');
		}

		init_import($categories, $shipment_type, $only_avail, $selected_date, $round_price, $exrate, $price_empty, $cron_run);
		
	}
}

if (isset($_POST['order_products'])) {
	
	ignore_user_abort(true);
	set_time_limit(0);
	ob_start();
	$size = ob_get_length();
	header("Content-Length: $size");
	header('Connection: close');
	ob_end_flush();
	ob_flush();
	flush();
	if (session_id()) session_write_close();
	
	$mli_set = $_SERVER['DOCUMENT_ROOT'].'/wp-content/uploads/merlion_import/mli_settings.txt';
	if(file_exists($mli_set)) {
		$option = mli_readFile($mli_set, 'serialize');
		$shipment_type = $option['shipment_type'];
	} else {
		$shipment_type = 'С/В';
	}
	$client = get_client();
	$mli_doc = $_SERVER['DOCUMENT_ROOT'].'/wp-content/uploads/merlion_import/mli_order_doc.txt';
	$dates = getShipmentDates($client);
	$order_query = '';
	
	function createFileDoc($mli_doc, $str) {
		mli_writeFile($mli_doc, $str);
	}
	function readFileDoc($mli_doc) {
		return mli_readFile($mli_doc, '');
	}
	
	function getOrderNo($client, $oper_no) {
		for ($i = 1; $i < 1000; $i++) {
			$res = getCommandResult($client, $oper_no);
			if ($res[0]->ProcessingResult == 'Сделано') {
				$order_query = $res[0]->DocumentNo;
				break;
			} else {
				$order_query = '';
				sleep($i);
			}
		}
		return $order_query;
	}

	if (file_exists($mli_doc)) {
		if ((time()-filemtime($mli_doc)) > 86400) {
			unlink($mli_doc);
			$oper_no = setOrderHeaderCommand($client, $shipment_type, $dates);
			$order_query = getOrderNo($client, $oper_no);
			createFileDoc($mli_doc, $order_query);
		} else {
			$order_query = readFileDoc($mli_doc);
		}
	} else {
		$oper_no = setOrderHeaderCommand($client, $shipment_type, $dates);
		$order_query = getOrderNo($client, $oper_no);
		createFileDoc($mli_doc, $order_query);
	}

	function create_order($client, $order_query) {
		for ($i = 0; $i < count($_POST['order_products']); $i++) {
			$sku = $_POST['order_products'][$i]['sku'];
			$qty = intval($_POST['order_products'][$i]['qty']);
			$price = intval($_POST['order_products'][$i]['price']);
			if ($price == '') {
				$price = 0;
			}
			$oper_no = setOrderLineCommand($client, $order_query, $sku, $qty, $price);
			$product_query = getOrderNo($client, $oper_no);
		}
	}
	
	create_order($client, $order_query);
	
}
?>