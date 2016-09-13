<?php
/*
Plugin Name: Merlion Import
Description: Merlion Import for Woocommerce.
Version: 0.26
Author: vadimsva
Author URI: http://trendwebsites.ru
*/

defined( 'ABSPATH' ) or die( 'Plugin file cannot be accessed directly.' );
require("mli_core.php");
add_action('admin_init', 'mli_admin_init' );
function mli_admin_init() {
	wp_register_style('mli_styles', plugins_url('/src/mli_styles.css', __FILE__));
	wp_register_script('mli_script', plugins_url('/src/mli_script.js', __FILE__));
	wp_register_script('jquery-ui', '//code.jquery.com/ui/1.11.4/jquery-ui.js');
	wp_register_style('jquery-ui', '//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css');
}

add_action('admin_menu', 'mli_menu', 12);
function mli_menu() {
	$page = add_menu_page('Merlion Import', 'Merlion Import', 'manage_options', 'merlion_import', 'mli_page', plugins_url('/src/mli_ico.png',  __FILE__));
	add_action('admin_print_scripts-'.$page, 'mli_admin_scripts');
}
function mli_admin_scripts() {
	wp_enqueue_script('jquery');
	wp_enqueue_style('mli_styles');
	wp_enqueue_script('mli_script');
	wp_enqueue_script('jquery-ui');
	wp_enqueue_style('jquery-ui');
}

function delete_directory($dirname) {
	if (is_dir($dirname)) {
		$dir_handle = opendir($dirname);
	}
	if (!$dir_handle) {
		return false;
	}
	while($file = readdir($dir_handle)) {
		if ($file != "." && $file != "..") {
			if (!is_dir($dirname."/".$file)) {
				unlink($dirname."/".$file);
			} else {
				delete_directory($dirname.'/'.$file);
			}
		}
	}
	closedir($dir_handle);
	rmdir($dirname);
	return true;
}

register_uninstall_hook(__FILE__, 'mli_uninstall');
function mli_uninstall() {
	delete_directory($_SERVER['DOCUMENT_ROOT'].'/wp-content/uploads/merlion_import');
}


function my_sort_cat($a, $b) {
	return ($a->ID < $b->ID) ? -1 : 1;
	return 0;
}


function getCatList($client) {
	if ($client) {
		try {
			$items = $client->getCatalog(array('cat_id' => 'All'));
			$arr = $items->getCatalogResult->item;
			usort($arr, "my_sort_cat");
			$upload_dir = wp_upload_dir();
			$dir = $upload_dir['basedir'].'/merlion_import';
			if (!wp_mkdir_p($dir)) {
				echo '<p>Невозможно создать папку для импорта. Необходимо дать полный доступ к папке uploads.</p>';
			}
			$box1 = '<div class="box1">';
			$box2 = '<div class="box2">';
			$box3 = '<div class="box3">';
			foreach ($arr as $row) {
				if ($row->ID_PARENT == 'Order') {
					$tolower = mb_convert_case(substr($row->Description, 2, strlen($row->Description)), MB_CASE_LOWER, "UTF-8");
					$firstupper = mb_convert_case(substr($row->Description, 0, 2), MB_CASE_UPPER, "UTF-8");
					$cat_name = $firstupper.$tolower;
				} else {
					$cat_name = $row->Description;
				}
				$str = '<label data-subcat="'.$row->ID_PARENT.'" data-cat="'.$row->ID.'">'.$cat_name.'</label>';
				if ($row->ID_PARENT == 'Order') {
					$box1 .= $str;
				} else if (strlen(utf8_decode($row->ID_PARENT)) == 2) {
					$box2 .= $str;
				} else if (strlen(utf8_decode($row->ID_PARENT)) == 4) {
					$box3 .= $str;
				}
			}
			$box1 .= '</div>';
			$box2 .= '</div>';
			$box3 .= '</div>';
			return '<div id="catList" style="display:none">'.$box1.$box2.$box3.'</div>';
		} catch (Exception $e) {
			return $e->faultstring;
		}
	}
}

function getShipmentMethods($client) {
	if ($client) {
		try {
			$items = $client->getShipmentMethods(array('code' => ''));
			$arr = $items->getShipmentMethodsResult->item;
			$str = '';
			$only_avail = '<div class="only_avail"><label><input type="checkbox" name="only_avail" value="1"> Только в наличии</label></div>';
			foreach ($arr as $row) {
				if ($row->IsDefault == 1) {
					$checked = 'checked';
				} else {
					$checked = '';
				}
				$str .= '<div><label><input type="radio" name="shipment" value="'.$row->Code.'" '.$checked.'> '.$row->Description.' ('.$row->Code.')</label></div>';
			}
			return $str.$only_avail;
		} catch (Exception $e) {
			echo getShipmentMethods($client);
		}
	}
}


function mli_page() {
$client = get_client();

?>

<div id="mli_wrapper" class="wrap">
	<h1>Merlion Import <button class="import_btn page-title-action">Импорт</button>
	<?php
	$filename = $_SERVER['DOCUMENT_ROOT'].'/wp-content/uploads/merlion_import/mli_run.txt';
	if (file_exists($filename)) {
		$stat = stat($filename);
    echo '<span>Импорт был запущен в ' . date("d.m.Y H:i", $stat['mtime']) . '</span>';
	}
	$filename = $_SERVER['DOCUMENT_ROOT'].'/wp-content/uploads/merlion_import/price_import.csv';
	if (file_exists($filename)) {
		$stat = stat($filename);
    echo '<span>Импорт был завершен в ' . date("d.m.Y H:i", $stat['mtime']) . '</span>';
	}
	?>
	</h1>
	<div class="col metabox-holder">
		<div class="col-70">
		
			<div class="catList_box postbox">
				<h3 class="hndle">Выберите категории для импорта:</h3>
				<div class="inside main">
					<?php echo getCatList($client); ?>
				</div>
			</div>
			
			<div class="hint"><b>Импорт товаров может занять длительное время, в зависимости от количества товаров и выбранных категорий.</b><br>После изменения настроек, необходимо нажать кнопку <b>Сохранить</b>. Все операции проходят в фоновом режиме, поэтому вы можете закрыть страницу. Последующий запланированный импорт будет занимать значительно меньше времени за счет загрузки только обновленных товаров, если указана опция <b>За все время</b>. Файл импорта будет доступен в папке <a href="<?php echo home_url().'/wp-content/uploads/merlion_import/price_import.csv'; ?>" target="_blank">uploads/merlion_import</a>.</div>
			
			<button class="save_btn action_btn button button-primary">Сохранить</button>
			<button class="import_btn action_btn button button-primary">Импорт</button>
			<button class="import_price_btn action_btn button button-default">Импорт цен</button>
			
		</div>
		<div class="col-30">
			
			<div class="shipmentType_box postbox">
				<h3 class="hndle">Импорт товаров с методом отгрузки:</h3>
				<div class="inside main">
					<?php echo getShipmentMethods($client); ?>
				</div>
			</div>
			<div class="selectedDate_box postbox">
				<h3 class="hndle">Импорт товаров с указанной даты:</h3>
				<div class="inside main">
					<div>
						<label><input type="radio" name="date" value="all" checked> За все время</label>
					</div>
					<div>
						<label><input type="radio" name="date" value="selected"> Начиная с</label>
						<input type="text" name="selected_date" value="<?php echo date('Y-m-d'); ?>" disabled>
					</div>
				</div>
			</div>
			<div class="priceSettings_box postbox">
				<h3 class="hndle">Настройка цен:</h3>
				<div class="inside main">
					<div>
						<label><input type="checkbox" name="round_price" value="1" checked> Округление цен до целых</label>
					</div>
					<div>
						<label><input type="checkbox" name="price_empty" value="1" checked> Исключать товары без цен</label>
					</div>
					<div>
						<label>Маржа <input type="text" name="exrate" value="15"> %</label>
					</div>
				</div>
			</div>
			<div class="importSchedule_box postbox">
				<h3 class="hndle">Запланировать импорт:</h3>
				<div class="inside main">
					<div>
						<label><input type="radio" name="schedule_date" value="no" checked> Не планировать</label>
					</div>
					<div>
						<label><input type="radio" name="schedule_date" value="weekly"> Запланировать</label>
					</div>
					<div class="schedule_plan_box disabled">
						<div>
							Импорт каталога раз в
							<select name="schedule_full_run" disabled>
								<option value="1" selected>неделю</option>
								<option value="2">2 недели</option>
								<option value="4">месяц</option>
							</select>, в воскресенье
						</div>
						<div>
							Импорт цен и кол-ва товаров 
							<select name="schedule_price_run" disabled>
								<option value="1" selected>каждый день</option>
								<option value="2">каждые 2 дня</option>
								<option value="3">каждые 3 дня</option>
								<option value="7">каждую неделю</option>
							</select>
						</div>
						<div>
							Время запуска: 00:00
						</div>
						<div class="cron_exec">
							Запуск через cron: <input type="text" value='curl "http://<?php echo $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; ?>&import"'>
						</div>
					</div>
				</div>
			</div>
			
		</div>
	</div>
	
	<div class="notify_popup"></div>
</div>
<script>
var mli_path = "<?php echo plugin_dir_url(__FILE__); ?>";

var $ = jQuery;
$(function(){
<?php
$name = $_SERVER['DOCUMENT_ROOT'].'/wp-content/uploads/merlion_import/mli_settings.txt';
if(file_exists($name)) {
	$handle = fopen($name, 'r');
	$option = unserialize(stream_get_contents($handle));
	fclose($handle);
	$name = $_SERVER['DOCUMENT_ROOT'].'/wp-content/uploads/merlion_import/mli_schedule.txt';
	$handle = fopen($name, 'r');
	$schedule_date = stream_get_contents($handle);
	$schedule_date = explode(",", $schedule_date);
	fclose($handle);
?>

var cats = <?php echo json_encode($option['categories']); ?>;

function checkCat1(cat) {
	$('#catList label[data-subcat="'+cat+'"]').addClass('show');
	if ($('.cat[data-subcat="'+cat+'"]').length == 0) {
		$('#catList label[data-subcat="'+cat+'"]:eq(0)').before('<div class="cat" data-subcat="'+cat+'">'+$('#catList label[data-cat="'+cat+'"]').text()+'</div>');
	}
}
function checkCat2(cat) {
	$('#catList label[data-cat="'+cat.substr(0,2)+'"]').addClass('active');
	$('#catList label[data-subcat="'+cat.substr(0,2)+'"]').addClass('show');
	if ($('.cat[data-subcat="'+cat.substr(0,2)+'"]').length == 0) {
		$('#catList label[data-subcat="'+cat.substr(0,2)+'"]:eq(0)').before('<div class="cat" data-subcat="'+cat.substr(0,2)+'">'+$('#catList label[data-cat="'+cat.substr(0,2)+'"]').text()+'</div>');
	}
}
function checkCat3(cat) {
	$('#catList label[data-cat="'+cat.substr(0,4)+'"]').addClass('active');
	$('#catList label[data-subcat="'+cat.substr(0,4)+'"]').addClass('show');
	if ($('.cat[data-subcat="'+cat.substr(0,4)+'"]').length == 0) {
		$('#catList label[data-subcat="'+cat.substr(0,4)+'"]:eq(0)').before('<div class="cat" data-subcat="'+cat.substr(0,4)+'">'+$('#catList label[data-cat="'+cat.substr(0,4)+'"]').text()+'</div>');
	}
}

for (i = 0; i < cats.length; i++) {
	if (cats[i].length == 6) {
		checkCat2(cats[i]);
		checkCat3(cats[i]);
	} else if (cats[i].length == 4) {
		checkCat2(cats[i]);
		checkCat1(cats[i]);
	} else if (cats[i].length == 2) {
		checkCat1(cats[i]);
	}
	$('#catList label[data-cat="'+cats[i]+'"]').addClass('active');
}
	
$('.shipmentType_box input[name="shipment"][value="<?php echo $option['shipment_type']; ?>"]').click();
var only_avail = "<?php echo $option['only_avail']; ?>";
var selected_date = "<?php echo $option['selected_date']; ?>";
var round_price = "<?php echo $option['round_price']; ?>";
var exrate = "<?php echo $option['exrate']; ?>";
var price_empty = "<?php echo $option['price_empty']; ?>";
var schedule_date = "<?php echo $schedule_date[0]; ?>";
var schedule_full_run = "<?php echo $schedule_date[1]; ?>";
var schedule_price_run = "<?php echo $schedule_date[2]; ?>";
if (only_avail == '1') {
	$('.shipmentType_box input[name="only_avail"]').click();
}
if (selected_date != '') {
	$('.selectedDate_box input[name="date"][value="selected"]').click();
	$('.selectedDate_box input[name="selected_date"]').val("<?php echo str_replace('T00:00:00', '', $option['selected_date']); ?>");
}
if (round_price != '1') {
	$('.priceSettings_box input[name="round_price"]').click();
}
$('.priceSettings_box input[name="exrate"]').val(exrate);
if (price_empty != '1') {
	$('.priceSettings_box input[name="price_empty"]').click();
}
if (schedule_date == 'no' || schedule_date == '') {
	$('.importSchedule_box input[name="schedule_date"][value="no"]').click();
} else if (schedule_date == 'weekly') {
	$('.importSchedule_box input[name="schedule_date"][value="weekly"]').click();
}
$('.importSchedule_box select[name="schedule_full_run"] option[value="'+schedule_full_run+'"]').attr('selected','selected');
$('.importSchedule_box select[name="schedule_price_run"] option[value="'+schedule_price_run+'"]').attr('selected','selected');

	
<?php	} else { ?>

$('.import_btn, .import_price_btn').attr('disabled','true');

<?php } ?>
});
</script>

<?php } ?>