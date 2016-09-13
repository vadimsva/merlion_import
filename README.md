# merlion_import
Wordpress плагин для работы с Merlion API


<h3>Как использовать:</h3>

<b>1.</b> Скопировать файлы в папку <b>plugins/merlion_import</b>, затем активировать плагин в Wordpress

<b>2.</b> В папке с темой, в файл <b>"footer.php"</b> добавить
<pre>
&lt;script&gt;
var $ = jQuery;
$(function(){

	if (window.location.pathname == '/checkout/order-received/'+$('.order_details .order > strong').text()) {
		var order_ar = [];
		$('.order_info').each(function(){
			order_ar.push({
				sku:$(this).find('> ._sku').text(),
				qty:$(this).find('> ._qty').text(),
				price:$(this).find('> ._price').text()
			});
		});
		$.post(location.origin+'/wp-content/plugins/merlion_import/mli_core.php', {order_products: order_ar}, function(response,status){	});
	}

});
&lt;/script&gt;
</pre>

<b>3.</b> В папке с темой создать папку woocommerce/order и скопировать файл <b>"order-details-item.php"</b> из папки <b>plugins/woocommerce/templates/order</b>, добавить следующие строки внутрь тэга <code>&lt;td class="product-name"&gt;</code>
<pre>
&lt;div class="order_info" style="display:none"&gt;
	&lt;span class="_sku"&gt;&lt;?php $q = get_post_meta($product-&gt;post-&gt;ID); echo $q['_sku'][0]; ?&gt;&lt;/span&gt;
	&lt;span class="_qty"&gt;&lt;?php echo $item['qty']; ?&gt;&lt;/span&gt;
	&lt;span class="_price"&gt;&lt;?php echo $item['line_subtotal']/$item['qty']; ?&gt;&lt;/span&gt;
&lt;/div&gt;
</pre>

<b>4.</b> В панели управления wordpress перейти на страницу Merlion Import и настроить все необходимые параметры


