<?php
$pname = array();
$payment_method = array();
$csod_meta = array();

$management_status = apply_filters( 'usces_filter_management_status', get_option('usces_management_status') );
$payment_method = usces_get_system_option( 'usces_payment_method', 'sort' );
foreach( $payment_method as $pmet){
	$pname[] = $pmet['name'];
}
if( !in_array( __('Transfer (prepayment)', 'usces'), $pname) ){
	$newp['name'] = __('Transfer (prepayment)', 'usces');
	$newp['explanation'] = '';
	$newp['settlement'] = 'transferAdvance';
	$newp['module'] = '';
	$payment_method[] = $newp;
}
if( !in_array( __('Transfer (postpay)', 'usces'), $pname) ){
	$newp['name'] = __('Transfer (postpay)', 'usces');
	$newp['explanation'] = '';
	$newp['settlement'] = 'transferDeferred';
	$newp['module'] = '';
	$payment_method[] = $newp;
}
if( !in_array( __('COD', 'usces'), $pname) ){
	$newp['name'] = __('COD', 'usces');
	$newp['explanation'] = '';
	$newp['settlement'] = 'COD';
	$newp['module'] = '';
	$payment_method[] = $newp;
}

if($order_action == 'new'){

	$oa = 'newpost';
	$taio = 'new';
	$admin = 'adminorder';
	$receipt = '';
	$ordercheck = array();
	$order_delivery_method = -1;
	$order_id = NULL;

	$csod_meta = usces_has_custom_field_meta('order');
	if(is_array($csod_meta)) {
		$keys = array_keys($csod_meta);
		foreach($keys as $key) {
			$csod_key = 'csod_'.$key;
			$csod_meta[$key]['data'] = NULL;
		}
	}
	$cscs_meta = usces_has_custom_field_meta('customer');
	if(is_array($cscs_meta)) {
		$keys = array_keys($cscs_meta);
		foreach($keys as $key) {
			$cscs_key = 'cscs_'.$key;
			$cscs_meta[$key]['data'] = NULL;
		}
	}
	$csde_meta = usces_has_custom_field_meta('delivery');
	if(is_array($csde_meta)) {
		$keys = array_keys($csde_meta);
		foreach($keys as $key) {
			$csde_key = 'csde_'.$key;
			$csde_meta[$key]['data'] = NULL;
		}
	}

	$condition = $this->get_condition();
	$data = array(
		'mem_name1' => '', 
		'mem_name2' => '', 
		'mem_name3' => '', 
		'mem_name4' => '', 
		'mem_zip' => '',
		'mem_address1' => '',
		'mem_address2' => '',
		'mem_address3' => '',
		'mem_tel' => '',
		'mem_fax' => '',
		'mem_country' => '',
		'mem_pref' => '',
		'order_name1' => '', 
		'order_name2' => '', 
		'order_name3' => '', 
		'order_name4' => '', 
		'order_zip' => '',
		'order_address1' => '',
		'order_address2' => '',
		'order_address3' => '',
		'order_tel' => '',
		'order_fax' => '',
		'order_country' => '',
		'order_pref' => '',
		'ID' => '',
		'order_condition' => $condition,
		'order_date' => current_time('mysql'),
		'order_delivery_date' => '',
	 );
	$deli = array(
		'name1' => '', 
		'name2' => '', 
		'name3' => '', 
		'name4' => '', 
		'zipcode' => '',
		'address1' => '',
		'address2' => '',
		'address3' => '',
		'tel' => '',
		'fax' => '',
		'country' => '',
		'pref' => ''
	 );
	$cart = array();
	$navibutton = '';
	$include_tax = '';

}else{

	$oa = 'editpost';

	$order_id = $_REQUEST['order_id'];
	$data = $this->get_order_data( $order_id, 'direct' );

	$deli = stripslashes_deep(unserialize($data['order_delivery']));
	$seriarized_cart = stripslashes_deep(unserialize($data['order_cart']));
	$cart = usces_get_ordercartdata( $order_id );
	$condition = stripslashes_deep(unserialize($data['order_condition']));
	$ordercheck = stripslashes_deep(unserialize($data['order_check']));
	if( !is_array($ordercheck) ) $ordercheck = array();
	$order_delivery_method = $data['order_delivery_method'];

	if( !empty($data) ){
		$data = stripslashes_deep($data);
	}
	$exclusion_status = apply_filters( 'usces_filter_taio_exclusion_status', array('noreceipt','receipted','pending', 'estimate', 'adminorder') );
	foreach ($management_status as $status_key => $status_name){
		if( in_array($status_key, $exclusion_status) )
			continue;
			
		if($this->is_status($status_key, $data['order_status'])){
			$taio = $status_key;
			break;
		}else{
			$taio = 'new';
		}
	}

	if($this->is_status('estimate', $data['order_status']))
		$admin = 'estimate';
	else if($this->is_status('adminorder', $data['order_status']))
		$admin = 'adminorder';
	else
		$admin = '';

	if($this->is_status('noreceipt', $data['order_status']))
		$receipt = 'noreceipt';
	else if($this->is_status('receipted', $data['order_status']))
		$receipt = 'receipted';
	else if($this->is_status('pending', $data['order_status']))
		$receipt = 'pending';
	else
		$receipt = '';

	$csod_meta = usces_has_custom_field_meta('order');
	if(is_array($csod_meta)) {
		$keys = array_keys($csod_meta);
		foreach($keys as $key) {
			$csod_key = 'csod_'.$key;
			$csod_meta[$key]['data'] = maybe_unserialize($this->get_order_meta_value($csod_key, $order_id));
		}
	}
	$cscs_meta = usces_has_custom_field_meta('customer');
	if(is_array($cscs_meta)) {
		$keys = array_keys($cscs_meta);
		foreach($keys as $key) {
			$cscs_key = 'cscs_'.$key;
			$cscs_meta[$key]['data'] = maybe_unserialize($this->get_order_meta_value($cscs_key, $order_id));
		}
	}
	$csde_meta = usces_has_custom_field_meta('delivery');
	if(is_array($csde_meta)) {
		$keys = array_keys($csde_meta);
		foreach($keys as $key) {
			$csde_key = 'csde_'.$key;
			$csde_meta[$key]['data'] = maybe_unserialize($this->get_order_meta_value($csde_key, $order_id));
		}
	}

	$exopt = get_option('usces_ex');
	if( isset($exopt['system']['datalistup']['orderlist_flag']) && $exopt['system']['datalistup']['orderlist_flag'] ){
		$prev_uri = $this->get_prev_page_uri( 'order', $order_id );
		$next_uri = $this->get_next_page_uri( 'order', $order_id );
		$navibutton = '';
		if( !empty($prev_uri) ){
			$navibutton .= '<a href="' . $prev_uri . '" class="prev-page"><span class="dashicons dashicons-arrow-left-alt2"></span>' . __('to prev page', 'usces') . '</a>';
		}
		$navibutton .= '<a href="' . admin_url('admin.php?page=usces_orderlist&returnList=1') . '" class="back-list"><span class="dashicons dashicons-list-view"></span>' . __('to order list', 'usces') . '</a>';
		if( !empty($next_uri) ){
			$navibutton .= '<a href="' . $next_uri . '" class="next-page">' . __('to next page', 'usces') . '<span class="dashicons dashicons-arrow-right-alt2"></span></a>';
		}
	}else{
		$navibutton = '<a href="' . admin_url('admin.php?page=usces_orderlist&returnList=1') . '" class="back-list"><span class="dashicons dashicons-list-view"></span>' . __('to order list', 'usces') . '</a>';
	}
	if( 'include' == $this->options['tax_mode'] ) {
		$materials = array(
			'total_items_price' => $data['order_item_total_price'],
			'discount' => $data['order_discount'],
			'shipping_charge' => $data['order_shipping_charge'],
			'cod_fee' => $data['order_cod_fee'],
			'use_point' => $data['order_usedpoint'],
		);
		$include_tax = '(' . usces_crform( usces_internal_tax( $materials, 'return' ), false, false, 'return', true ) . ')';
	} else {
		$include_tax = '';
	}
}
$delivery_after_days = apply_filters( 'usces_filter_delivery_after_days', ( !empty($this->options['delivery_after_days']) ? (int)$this->options['delivery_after_days'] : 100 ) );
$delivery_date = ( isset( $data['order_delivery_date'] ) && usces_is_date( $data['order_delivery_date'] ) ) ? $data['order_delivery_date'] : '';
$delidue_date = ( isset( $data['order_delidue_date'] ) && usces_is_date( $data['order_delidue_date'] ) ) ? $data['order_delidue_date'] : '';
$order_date_time = explode( " ", $data['order_date'] );
$order_date = explode( "-", $order_date_time[0] );
$order_yy = (int)$order_date[0];
$order_mm = (int)$order_date[1] - 1;
$order_dd = (int)$order_date[2];

$noreceipt_status = apply_filters( 'usces_filter_noreceipt_status', get_option( 'usces_noreceipt_status' ) );
$filter_args = compact( 'order_action', 'order_id', 'data', 'cart' ); 
$action_args = compact( 'order_action', 'order_id', 'cart' ); 

$payments_str = '';
$payments = usces_get_system_option( 'usces_payment_method', 'sort' );
foreach ( (array)$payments as $id => $array ) {
	$payments_str .= "'" . esc_js($array['name']) . "': '" . esc_js($array['settlement']) . "', ";
}
$payments_str .= "'" . __('Transfer (prepayment)', 'usces') . "': 'transferAdvance', ";
$payments_str .= "'" . __('Transfer (postpay)', 'usces') . "': 'transferDeferred', ";
$payments_str .= "'" . __('COD', 'usces') . "': 'COD', ";
$payments_str = rtrim($payments_str, ', ');

$no_preference = apply_filters( 'usces_filter_label_delivery_date_no_preference', __('Non-request', 'usces_dual') );
$tax_readonly = ( 'include' == $this->options['tax_mode'] ) ? ' readonly' : '';
?>
<script type='text/javascript'>
/* <![CDATA[ */
	uscesPayments = {<?php echo $payments_str; ?>};
/* ]]> */
</script>
<script type="text/javascript">
jQuery(function($){
	var selected_delivery_time = '<?php echo (isset($data['order_delivery_time']) ? $data['order_delivery_time'] : ''); ?>';
	var delivery_time = [];
<?php
	foreach((array)$this->options['delivery_method'] as $dmid => $dm){
		$dm['time'] = usces_change_line_break( $dm['time'] );
		$lines = explode("\n", $dm['time']);
?>
		delivery_time[<?php echo $dm['id']; ?>] = [];
<?php
		foreach((array)$lines as $line){
			if(trim($line) != ''){
?>
				delivery_time[<?php echo $dm['id']; ?>].push("<?php echo trim($line); ?>");
<?php		}
		}
	}
?>

	$("#order_payment_name").change(function () {
		var pay_name = $("select[name='offer\[payment_name\]'] option:selected").val();
		var noreceipt_status = [ '<?php echo implode( "','", $noreceipt_status ); ?>' ];

		if( $.inArray( uscesPayments[pay_name], noreceipt_status ) >= 0 ) {

			var label = '<?php _e('transfer statement', 'usces'); ?>';
			var html = "<select name='offer[receipt]'>\n";
			html += "<option value='noreceipt'><?php echo $management_status['noreceipt']; ?></option>\n";
			html += "<option value='receipted'><?php echo $management_status['receipted']; ?></option>\n";
			html += "</select>\n";
			$("#receiptlabel").html(label);
			$("#receiptbox").html(html);
		<?php do_action( 'usces_change_payment_terms_js', $management_status, $data ); ?>
		}else{
			$("#receiptlabel").html('');
			$("#receiptbox").html('');
		}
	});
	
	$("#addItemDialog").dialog({
		bgiframe: true,
		autoOpen: false,
		height: 500,
		width: 700,
		resizable: true,
		modal: true,
		appendTo: "#dialog_parent",
		buttons: {
			'<?php _e('close', 'usces'); ?>': function() {
				$(this).dialog('close');
			}
		},
		close: function() {
			$("#newitemcategory").val( "-1" );
			$("#newitemform").html( "" );
			$('#newitemcode').val('');
		}
	});
	$('#addCartButton').click(function() {
		if($("input[name='order_id']").val() == ''){
			alert("<?php _e("Push 'change decision' button, to be settled with an order number.", 'usces'); ?>");
			return;
		}
		$('#addItemDialog').dialog('open');
	});
	$('#delivery_method_select').change(function() {
		orderfunc.make_delivery_time($('#delivery_method_select option:selected').val());
	});
	
	$("#mailSendDialog").dialog({
		bgiframe: true,
		autoOpen: false,
		height: 650,
		width: 700,
		resizable: true,
		modal: true,
		buttons: {
			'<?php _e('close', 'usces'); ?>': function() {
				$(this).dialog('close');
			}
		},
		appendTo:"#dialog_parent",
		close: function() {
			$("#sendmailmessage").html( "" );
			$('#sendmailaddress').val('');
		}
	});
	$('#completionMail').click(function() {
		orderItem.getmailmessage('completionMail');
		$("#sendmailaddress").val($("input[name='customer\[mailaddress\]']").val());
		$("#sendmailname").val($("input[name='customer\[name1\]']").val()+$("input[name='customer\[name2\]']").val());
		$("#sendmailsubject").val('<?php echo esc_js(apply_filters('usces_filter_order_confirm_mail_subject', $this->options['mail_data']['title']['completionmail'], $data, 'completionMail')); ?>');
		$('#mailChecked').val('completionmail');
		$('#mailSendDialog').dialog('option', 'title', '<?php _e('Mail for Shipping', 'usces'); ?>');
		$('#mailSendDialog').dialog('open');
	});
	$('#orderConfirmMail').click(function() {
		orderItem.getmailmessage('orderConfirmMail');
		$("#sendmailaddress").val($("input[name='customer\[mailaddress\]']").val());
		$("#sendmailname").val($("input[name='customer\[name1\]']").val()+$("input[name='customer\[name2\]']").val());
		$("#sendmailsubject").val('<?php echo esc_js(apply_filters('usces_filter_order_confirm_mail_subject', $this->options['mail_data']['title']['ordermail'], $data, 'orderConfirmMail')); ?>');
		$('#mailChecked').val('ordermail');
		$('#mailSendDialog').dialog('option', 'title', '<?php _e('Mail for confirmation of order', 'usces'); ?>');
		$('#mailSendDialog').dialog('open');
	});
	$('#changeConfirmMail').click(function() {
		orderItem.getmailmessage('changeConfirmMail');
		$("#sendmailaddress").val($("input[name='customer\[mailaddress\]']").val());
		$("#sendmailname").val($("input[name='customer\[name1\]']").val()+$("input[name='customer\[name2\]']").val());
		$("#sendmailsubject").val('<?php echo esc_js(apply_filters('usces_filter_order_confirm_mail_subject', $this->options['mail_data']['title']['changemail'], $data, 'changeConfirmMail')); ?>');
		$('#mailChecked').val('changemail');
		$('#mailSendDialog').dialog('option', 'title', '<?php _e('Mail for confiemation of change', 'usces'); ?>');
		$('#mailSendDialog').dialog('open');
	});
	$('#receiptConfirmMail').click(function() {
		orderItem.getmailmessage('receiptConfirmMail');
		$("#sendmailaddress").val($("input[name='customer\[mailaddress\]']").val());
		$("#sendmailname").val($("input[name='customer\[name1\]']").val()+$("input[name='customer\[name2\]']").val());
		$("#sendmailsubject").val('<?php echo esc_js(apply_filters('usces_filter_order_confirm_mail_subject', $this->options['mail_data']['title']['receiptmail'], $data, 'receiptConfirmMail')); ?>');
		$('#mailChecked').val('receiptmail');
		$('#mailSendDialog').dialog('option', 'title', '<?php _e('Mail for confirmation of transter', 'usces'); ?>');
		$('#mailSendDialog').dialog('open');
	});
	$('#mitumoriConfirmMail').click(function() {
		orderItem.getmailmessage('mitumoriConfirmMail');
		$("#sendmailaddress").val($("input[name='customer\[mailaddress\]']").val());
		$("#sendmailname").val($("input[name='customer\[name1\]']").val()+$("input[name='customer\[name2\]']").val());
		$("#sendmailsubject").val('<?php echo esc_js(apply_filters('usces_filter_order_confirm_mail_subject', $this->options['mail_data']['title']['mitumorimail'], $data, 'mitumoriConfirmMail')); ?>');
		$('#mailChecked').val('mitumorimail');
		$('#mailSendDialog').dialog('option', 'title', '<?php _e('estimate mail', 'usces'); ?>');
		$('#mailSendDialog').dialog('open');
	});
	$('#cancelConfirmMail').click(function() {
		orderItem.getmailmessage('cancelConfirmMail');
		$("#sendmailaddress").val($("input[name='customer\[mailaddress\]']").val());
		$("#sendmailname").val($("input[name='customer\[name1\]']").val()+$("input[name='customer\[name2\]']").val());
		$("#sendmailsubject").val('<?php echo esc_js(apply_filters('usces_filter_order_confirm_mail_subject', $this->options['mail_data']['title']['cancelmail'], $data, 'cancelConfirmMail')); ?>');
		$('#mailChecked').val('cancelmail');
		$('#mailSendDialog').dialog('option', 'title', '<?php _e('Cancelling mail', 'usces'); ?>');
		$('#mailSendDialog').dialog('open');
	});
	$('#otherConfirmMail').click(function() {
		orderItem.getmailmessage('otherConfirmMail');
		$("#sendmailaddress").val($("input[name='customer\[mailaddress\]']").val());
		$("#sendmailname").val($("input[name='customer\[name1\]']").val()+$("input[name='customer\[name2\]']").val());
		$("#sendmailsubject").val('<?php echo esc_js(apply_filters('usces_filter_order_confirm_mail_subject', $this->options['mail_data']['title']['othermail'], $data, 'otherConfirmMail')); ?>');
		$('#mailChecked').val('othermail');
		$('#mailSendDialog').dialog('option', 'title', '<?php _e('Other mail', 'usces'); ?>');
		$('#mailSendDialog').dialog('open');
	});
	
	$("#mailSendAlert").dialog({
		bgiframe: true,
		autoOpen: false,
		height: 200,
		width: 200,
		resizable: false,
		appendTo: "#mailSendDialog",
		modal: false
	});
	$("#sendmail").click(function() {
		uscesMail.sendmail();
	});
	
	$("#PDFDialog").dialog({
		bgiframe: true,
		autoOpen: false,
		height: 800,
		width: 700,
		resizable: true,
		modal: true,
		buttons: {
			'<?php _e('close', 'usces'); ?>': function() {
				$(this).dialog('close');
			}
		},
		close: function() {
			$("#new_pdf").html( "" );
		}
	});
	$('#mitumoriprint').click(function() {
		uscesMail.ordercheckpost('mitumoriprint');
		window.open ( "<?php echo USCES_ADMIN_URL.'?page=usces_orderlist&order_action=pdfout&noheader=true&order_id='.$order_id; ?>&type=mitumori", '_blank');
	});
	$('#nohinprint').click(function() {
		uscesMail.ordercheckpost('nohinprint');
		window.open ( "<?php echo USCES_ADMIN_URL.'?page=usces_orderlist&order_action=pdfout&noheader=true&order_id='.$order_id; ?>&type=nohin", '_blank');
	});
	$('#receiptprint').click(function() {
		uscesMail.ordercheckpost('receiptprint');
		window.open ( "<?php echo USCES_ADMIN_URL.'?page=usces_orderlist&order_action=pdfout&noheader=true&order_id='.$order_id; ?>&type=receipt", '_blank');
	});
	$('#billprint').click(function() {
		uscesMail.ordercheckpost('billprint');
		window.open ( "<?php echo USCES_ADMIN_URL.'?page=usces_orderlist&order_action=pdfout&noheader=true&order_id='.$order_id; ?>&type=bill", '_blank');
	});

	orderfunc = {
		sumPrice : function(obj) {
			if(obj != null) {
				if(!checkNumMinus(obj.val())) {
					alert('<?php _e("Please enter a numeric value.", "usces"); ?>');
					obj.focus();
					return false;
				}
			}

		<?php if( $this->is_reduced_tax_rate() ): ?>
			var p = $( "input[name*='skuPrice']" );
			var q = $( "input[name*='quant']" );
			var t = $( "p[id*='sub_total']" );
			var db = $( "input[name*='delButton']" );
			var applicable_tax_rate = $( "input[name*='applicable_tax_rate']" );
			var subtotal_standard = 0;
			var subtotal_reduced = 0;
			var total_full = 0;
			var n = <?php esc_html_e( $this->get_currency_decimal() ); ?>;

			for( var i = 0; i < p.length; i++ ) {
				v = parseFloat( $( p[i] ).val() ) * $( q[i] ).val();
				if( 0 < n ) {
					v = Math.round( v * Math.pow( 10, n ) ) / Math.pow( 10, n );
				}
				$( t[i] ).html( addComma( v+'' ) );

				if( 'reduced' == $( applicable_tax_rate[i] ).val() ) {
					subtotal_reduced += v;
					subtotal_reduced = Math.round( subtotal_reduced * Math.pow( 10, n ) ) / Math.pow( 10, n );
				} else {
					subtotal_standard += v;
					subtotal_standard = Math.round( subtotal_standard * Math.pow( 10, n ) ) / Math.pow( 10, n );
				}
			}
			var sub_total = subtotal_standard + subtotal_reduced;

			$( "#item_total" ).html( addComma( sub_total+'' ) );
			$( "#subtotal_standard" ).html( addComma( subtotal_standard+'' ) );
			$( "#subtotal_reduced" ).html( addComma( subtotal_reduced+'' ) );
			var order_usedpoint = $( "#order_usedpoint" ).val()*1;
			var order_discount = parseFloat( $( "#order_discount" ).val() );
			var order_shipping_charge = parseFloat( $( "#order_shipping_charge" ).val() );
			var order_cod_fee = parseFloat( $( "#order_cod_fee" ).val() );
			var order_tax = parseFloat( $( "#order_tax" ).val() );
			total_full = sub_total - order_usedpoint + order_discount + order_shipping_charge + order_cod_fee + order_tax;
			if( 0 < n ) {
				total_full = Math.round( total_full * Math.pow( 10, n ) ) / Math.pow( 10, n );
			}
			$( "#total_full" ).html( addComma( total_full+'' ) );
			$( "#total_full_top" ).html( addComma( total_full+'' ) );
		<?php else: ?>
			var p = $("input[name*='skuPrice']");
			var q = $("input[name*='quant']");
			var t = $("p[id*='sub_total']");
			var db = $("input[name*='delButton']");
			var sub_total = <?php echo apply_filters('order_edit_form_sub_total', 0, $data); ?>;
			var total_full = 0;
			var n = <?php esc_html_e( $this->get_currency_decimal() ); ?>;

			for( var i = 0; i < p.length; i++ ) {
				v = parseFloat($(p[i]).val()) * $(q[i]).val();
				if( 0 < n ) {
					v = Math.round( v * Math.pow( 10, n ) ) / Math.pow( 10, n );
				}
				$(t[i]).html(addComma(v+''));
				sub_total += v;
				sub_total = Math.round( sub_total * Math.pow( 10, n ) ) / Math.pow( 10, n );
			}

			$("#item_total").html(addComma(sub_total+''));
			var order_usedpoint = $("#order_usedpoint").val()*1;
			var order_discount = parseFloat($("#order_discount").val());
			var order_shipping_charge = parseFloat($("#order_shipping_charge").val());
			var order_cod_fee = parseFloat($("#order_cod_fee").val());
			var order_tax = parseFloat($("#order_tax").val());
			total_full = sub_total - order_usedpoint + order_discount + order_shipping_charge + order_cod_fee + order_tax;
			if( 0 < n ) {
				total_full = Math.round( total_full * Math.pow( 10, n ) ) / Math.pow( 10, n );
			}
			$("#total_full").html(addComma(total_full+''));
			$("#total_full_top").html(addComma(total_full+''));
		<?php endif; ?>
			<?php do_action( 'order_edit_form_sumPrice', $data ); ?>
		},
		make_delivery_time : function(selected) {
			var option = '';
			if(selected == -1 || delivery_time[selected] == undefined || 0 == delivery_time[selected].length){
				option += '<option value="<?php _e('Non-request', 'usces_dual'); ?>"><?php _e('Non-request', 'usces_dual'); ?></option>' + "\n";
			}else{
				for(var i=0; i<delivery_time[selected].length; i++){
					if( delivery_time[selected][i] == selected_delivery_time ) {
						option += '<option value="' + delivery_time[selected][i] + '" selected="selected">' + delivery_time[selected][i] + '</option>' + "\n";
					}else{
						option += '<option value="' + delivery_time[selected][i] + '">' + delivery_time[selected][i] + '</option>' + "\n";
					}
				}
			}
			$("#delivery_time_select").html(option);
		},
		getMember : function( email ) {
			var s = orderfunc.settings;
			s.url = uscesL10n.requestFile;
			s.data = "action=order_item_ajax&mode=getmember&email=" + encodeURIComponent(email);
			s.success = function(data, dataType){
				var values = data.split('#usces#');
				if( 'ok' == $.trim(values[0])){
					for(var i=1; i<values.length; i++){
						var val = values[i].split('=');
						if( 'member_id' == val[0] ){
							$("#member_id_label").html(val[1]);
							$("#member_id").val(val[1]);
						}else{
							$(":input[name='" + val[0] + "']").val(val[1]);
						}
					}
				}else if( 'none' == values[0]){
					alert( '<?php _e("Membership information in question does not exist.", "usces"); ?>' );
				}else{
					alert( 'ERROR' );
				}
			};
			s.error = function(data, dataType){
				alert( 'ERROR' );
			};
			$.ajax( s );
			return false;
		},
		recalculation : function() {
		<?php if( $this->is_reduced_tax_rate() ): ?>
			<?php $script = "
			var p = $( \"input[name*='skuPrice']\" );
			var q = $( \"input[name*='quant']\" );
			var pi = $( \"input[name*='postId']\" );
			var post_ids = [];
			var prices = [];
			var quants = [];
			var cart_ids = [];
			for( var i = 0; i < p.length; i++ ) {
				var piname = $( pi[i] ).attr( 'name' );
				namestrs = piname.split( '[' );
				post_ids.push( $( pi[i] ).val() );
				prices.push( parseFloat( $(p[i]).val() ) );
				quants.push( $( q[i] ).val() );
				cart_ids.push( namestrs[1].replace( /[\]]+$/g, '' ) );
			}
			var order_usedpoint = $( '#order_usedpoint' ).val()*1;
			var order_shipping_charge = parseFloat( $( '#order_shipping_charge' ).val() );
			var order_cod_fee = parseFloat( $( '#order_cod_fee' ).val() );
			var order_discount = parseFloat( $( '#order_discount' ).val() );
			var s = orderfunc.settings;
			s.url = uscesL10n.requestFile;
			s.data = {
				action: 'order_item_ajax',
				mode: 'recalculation_reduced',
				order_id: $( '#order_id' ).val(),
				mem_id: $( '#member_id_label' ).html(),
				post_ids: post_ids,
				prices: prices,
				quants: quants,
				cart_ids: cart_ids,
				upoint: order_usedpoint,
				shipping_charge: order_shipping_charge,
				cod_fee: order_cod_fee,
				discount: order_discount
			}
			s.dataType = 'json';
			s.success = function( data, dataType ) {
				if( 'ok' == data.status ) {
					$( '#order_discount' ).val( data.discount );
					$( '#order_tax' ).val( data.tax );
					$( '#order_getpoint' ).val( data.point );
					$( '#total_full' ).html( addComma( data.total_full_price+'' ) );
					$( '#total_full_top' ).html( addComma( data.total_full_price+'' ) );
					$( '#subtotal_standard' ).html( addComma( data.subtotal_standard+'' ) );
					$( '#subtotal_reduced' ).html( addComma( data.subtotal_reduced+'' ) );
					if( 0 < data.tax ) {
						$( '#order_tax_standard' ).val( data.tax_standard );
						$( '#order_tax_reduced' ).val( data.tax_reduced );
					} else {
						$( '#include_tax_standard' ).html( '(' + data.tax_standard + ')' );
						$( '#include_tax_reduced' ).html( '(' + data.tax_reduced + ')' );
					}
				} else {
					console.log( data );
					alert( 'ERROR1' );
				}
			};
			s.error = function( data, dataType ) {
				console.log( data );
				//alert( 'ERROR2' );
			};
			$.ajax( s );
			return false;";
			echo apply_filters( 'order_edit_form_recalculation_reduced', $script, $data );
			?>
		<?php else: ?>
			<?php $script = "
			var p = $( \"input[name*='skuPrice']\" );
			var q = $( \"input[name*='quant']\" );
			var pi = $( \"input[name*='postId']\" );
			var post_ids = [];
			var skus = [];
			var prices = [];
			var quants = [];
			for( var i = 0; i < p.length; i++ ) {
				var piname = $( pi[i] ).attr( 'name' );
				namestrs = piname.split( '[' );
				post_ids.push( $( pi[i] ).val() );
				prices.push( parseFloat( $(p[i]).val() ) );
				quants.push( $( q[i] ).val() );
			}
			var order_usedpoint = $( '#order_usedpoint' ).val()*1;
			var order_shipping_charge = parseFloat( $( '#order_shipping_charge' ).val() );
			var order_cod_fee = parseFloat( $( '#order_cod_fee' ).val() );
			var order_discount = parseFloat( $( '#order_discount' ).val() );
			var s = orderfunc.settings;
			s.url = uscesL10n.requestFile;
			s.data = {
				action: 'order_item_ajax',
				mode: 'recalculation',
				order_id: $( '#order_id' ).val(),
				mem_id: $( '#member_id_label' ).html(),
				post_ids: post_ids,
				skus: skus,
				prices: prices,
				quants: quants,
				upoint: order_usedpoint,
				shipping_charge: order_shipping_charge,
				cod_fee: order_cod_fee,
				discount: order_discount
			}
			s.success = function( data, dataType ) {
				if( 'ok' == data.status ) {
					$( '#order_discount' ).val( data.discount );
					$( '#order_tax' ).val( data.tax );
					$( '#include_tax' ).html( data.include_tax );
					$( '#order_getpoint' ).val( data.point );
					$( '#total_full' ).html( addComma( data.total_full_price+'' ) );
					$( '#total_full_top' ).html( addComma( data.total_full_price+'' ) );
				} else {
					console.log( data );
					alert( 'ERROR1' );
				}
			};
			s.error = function( data, dataType ) {
				console.log( data );
				//alert( 'ERROR2' );
			};
			$.ajax( s );
			return false;";
			echo apply_filters( 'order_edit_form_recalculation',$script, $data );
			?>
		<?php endif; ?>
		},
		<?php if( $this->is_reduced_tax_rate() ): ?>
		sumTax : function() {
			if( "" == $( "#order_tax_standard" ).val() || !checkNum( $( "#order_tax_standard" ).val() ) ) {
				$( "#order_tax_standard" ).val( "0" );
			}
			if( "" == $( "#order_tax_reduced" ).val() || !checkNum( $( "#order_tax_reduced" ).val() ) ) {
				$( "#order_tax_reduced" ).val( "0" );
			}
			var n = <?php esc_html_e( $this->get_currency_decimal() ); ?>;
			var order_tax = parseFloat( $( "#order_tax_standard" ).val() ) + parseFloat( $( "#order_tax_reduced" ).val() );
			order_tax = Math.round( order_tax * Math.pow( 10, n ) ) / Math.pow( 10, n );
			$( '#order_tax' ).val( addComma( order_tax+'' ) );
		},
		<?php endif; ?>
		settings: {
			url: uscesL10n.requestFile,
			type: 'POST',
			cache: false,
			success: function(data, dataType){
			}, 
			error: function(msg){
			}
		}
	};
	
	uscesMail = {
		sendmail : function() {
			if($("#sendmailaddress").val() == "") return;
		
			var address = encodeURIComponent($("#sendmailaddress").val());
			var message = encodeURIComponent($("#sendmailmessage").val());
			var name = encodeURIComponent($("#sendmailname").val());
			var subject = encodeURIComponent($("#sendmailsubject").val());
			var order_id = $("#order_id").val();
			var checked = $("#mailChecked").val();
			
			var s = uscesMail.settings;

			s.data = "action=order_item_ajax&mode=sendmail&mailaddress=" + address + "&message=" + message + "&name=" + name + "&subject=" + subject + "&order_id=" + order_id + "&checked=" + checked;

			<?php echo apply_filters('usces_filter_sendmail_js', ''); ?>

			s.success = function(data, dataType){
				if( $.trim(data) == 'success') {
					if(checked == 'completionmail'){
						$("input[name='check\[completionmail\]']").attr({checked: true});
					}else if(checked == 'ordermail'){
						$("input[name='check\[ordermail\]']").attr({checked: true});
					}else if(checked == 'changemail'){
						$("input[name='check\[changemail\]']").attr({checked: true});
					}else if(checked == 'receiptmail'){
						$("input[name='check\[receiptmail\]']").attr({checked: true});
					}else if(checked == 'mitumorimail'){
						$("input[name='check\[mitumorimail\]']").attr({checked: true});
					}else if(checked == 'cancelmail'){
						$("input[name='check\[cancelmail\]']").attr({checked: true});
					}else if(checked == 'othermail'){
						$("input[name='check\[othermail\]']").attr({checked: true});
					}
				<?php echo apply_filters('usces_filter_order_check_mail_js', '' ); ?>
					
					$('#mailSendAlert').dialog('option', 'buttons', {
															'OK': function() {
																	$(this).dialog('close');
																	$('#mailSendDialog').dialog('close');
																}
															});
					$('#mailSendAlert').dialog('option', 'title', 'SUCCESS');
					$('#mailSendAlert fieldset').html('<p><?php _e('E-mail has been sent.', 'usces'); ?></p>');
					$('#mailSendAlert').dialog('open');
					
				}else if(data == 'error'){
					$('#mailSendAlert').dialog('option', 'buttons', {
															'OK': function() {
																	$(this).dialog('close');
																}
															});
					$('#mailSendAlert').dialog('option', 'title', 'ERROR');
					$('#mailSendAlert fieldset').html('<p><?php _e('Failure in sending e-mails.', 'usces'); ?></p>');
					$('#mailSendAlert').dialog('open');
				}
			};
			s.error = function(data, dataType){
				$('#mailSendAlert').dialog('option', 'buttons', {
														'OK': function() {
																$(this).dialog('close');
															}
														});
				$('#mailSendAlert').dialog('option', 'title', 'ERROR');
				$('#mailSendAlert fieldset').html('<p><?php _e('Failure in sending e-mails.', 'usces'); ?></p>');
				$('#mailSendAlert').dialog('open');
			};
			$.ajax( s );
			return false;
		},
		ordercheckpost : function( checked ) {
			var p = uscesMail.settings;
			var order_id = $("#order_id").val();
			p.url = uscesL10n.requestFile;
			p.data = "action=order_item_ajax&mode=ordercheckpost&order_id=" + order_id + "&checked=" + checked;
			p.success = function(data, dataType){
				if($.trim(data) == 'mitumoriprint'){
					$("input[name='check\[mitumoriprint\]']").attr({checked: true});
				}else if($.trim(data) == 'nohinprint'){
					$("input[name='check\[nohinprint\]']").attr({checked: true});
				}else if($.trim(data) == 'billprint'){
					$("input[name='check\[billprint\]']").attr({checked: true});
				}else if($.trim(data) == 'receiptprint'){
					$("input[name='check\[receiptprint\]']").attr({checked: true});
				}
				<?php echo apply_filters('usces_filter_order_check_print_js', '' ); ?>
			};
			p.error = function(data, dataType){
			};
			$.ajax( p );
			return false;
		},
		settings: {
			url: uscesL10n.requestFile,
			type: 'POST',
			cache: false,
			success: function(data, dataType){
			}, 
			error: function(msg){
			}
		}
	};

	$('form').submit(function() {
		var error = 0;

		if( !checkNum( $("#order_usedpoint").val() ) ) {
			error++;
			$("#order_usedpoint").css({'background-color': '#FFA'}).click(function() {
				$(this).css({'background-color': '#FFF'});
			});
		}
		if( !checkNum( $("#order_getdpoint").val() ) ) {
			error++;
			$("#order_getdpoint").css({'background-color': '#FFA'}).click(function() {
				$(this).css({'background-color': '#FFF'});
			});
		}
		if( !checkPrice( $("#order_discount").val() ) ) {
			error++;
			$("#order_discount").css({'background-color': '#FFA'}).click(function() {
				$(this).css({'background-color': '#FFF'});
			});
		}
		if( !checkPrice( $("#order_shipping_charge").val() ) ) {
			error++;
			$("#order_shipping_charge").css({'background-color': '#FFA'}).click(function() {
				$(this).css({'background-color': '#FFF'});
			});
		}
		if( !checkPrice( $("#order_cod_fee").val() ) ) {
			error++;
			$("#order_cod_fee").css({'background-color': '#FFA'}).click(function() {
				$(this).css({'background-color': '#FFF'});
			});
		}
		if( !checkPrice( $("#order_tax").val() ) ) {
			error++;
			$("#order_tax").css({'background-color': '#FFA'}).click(function() {
				$(this).css({'background-color': '#FFF'});
			});
		}

		if( 0 < error ) {
			$("#aniboxStatus").removeClass("none");
			$("#aniboxStatus").addClass("error");
			$("#info_image").attr("src", "<?php echo USCES_PLUGIN_URL; ?>/images/list_message_error.gif");
			$("#info_massage").html("<?php _e('Data have deficiency.', 'usces'); ?>");
			$("#anibox").animate({ backgroundColor: "#FFE6E6" }, 2000);
			return false;
		} else {
			return true;
		}
	});
	<?php do_action('usces_action_order_edit_page_js', $order_id, $data ); ?>
<?php echo apply_filters('usces_filter_order_edit_page_js', '', $order_id ); ?>
<?php
	$delivery_date_select_script = "
	$( function() {
		$.datepicker.setDefaults( $.datepicker.regional['ja'] );
		$( '#delivery_date_select' ).datepicker({
			dateFormat: 'yy-mm-dd',
			minDate: new Date( ".$order_yy.", ".$order_mm.", ".$order_dd." )
		});
		$( '#delidue_date_select' ).datepicker({
			dateFormat: 'yy-mm-dd',
			minDate: new Date( ".$order_yy.", ".$order_mm.", ".$order_dd." )
		});
	});\n";
	echo apply_filters( 'usces_filter_order_edit_delivery_days_select_script', $delivery_date_select_script );
?>
});

function addComma(str)
{
	var strs = str.split('.');
	cnt = 0;
	n   = "";
	m = "";
	if( strs[0].substr(0, 1) == "-" ) {
		m = "-";
		strs[0] = strs[0].substr(1);
	}
	for (i=strs[0].length-1; i>=0; i--)
	{
		n = strs[0].charAt(i) + n;
		cnt++;
		if (((cnt % 3) == 0) && (i != 0)) n = ","+n;
	}
	n = m + n;
	if(undefined != strs[1]){
		res = n + '.' + strs[1];
	}else{
		res = n;
	}
	return res;
};

function pdfWindow( type ) {
	var wx = 800;
	var wy = 900;
	var x = (screen.width- wx) / 2;
	var y = (screen.height - wy) / 2;
	x = 0;
	y = 0;
	printWin = window.open("<?php echo USCES_ADMIN_URL.'?page=usces_orderlist&order_action=pdfout&order_id='.$order_id; ?>"+"&type="+type,"sub","left="+x+",top="+y+",width="+wx+",height="+wy+",scrollbars=yes");
}

function checkNumMinus( argValue ) {
	if( argValue.match(/[^0-9|^\-|^\.]/g) ) {
		return false;
	}
	return true;
}

function delConfirm(){
	if(confirm('<?php _e('Are you sure of deleting items?', 'usces'); ?>')){
		return true;
	}else{
		return false;
	}
}

jQuery(document).ready(function($){

	$(document).on( "click", "#mailVisiLink", function() {
		if( $("#mailBox").css("display") == "block" ) {
			$("#mailBox").css("display", "none");
			$("#mailVisiLink").html('<?php _e('Show the mail/print field', 'usces'); ?><span class="dashicons dashicons-arrow-up"></span>');
		} else {
			$("#mailBox").css("display", "block");
			$("#mailVisiLink").html('<?php _e('Hide the mail/print field', 'usces'); ?><span class="dashicons dashicons-arrow-down"></span>');
		}
	});

	orderfunc.sumPrice(null);

	$( document ).on( "change", "input[name*='skuPrice']", function() {
		orderfunc.sumPrice( $(this) );
	});
	$( document ).on( "change", "input[name*='quant']", function() {
		orderfunc.sumPrice( $(this) );
	});
	$( document ).on( "click", "input[name*='delButton']", function() {
		orderfunc.sumPrice( null );
	});
	$( document ).on( "change", "#order_usedpoint", function() {
		orderfunc.sumPrice( $("#order_usedpoint") );
	});
	$( document ).on( "change", "#order_discount", function() {
		orderfunc.sumPrice( $("#order_discount") );
	});
	$( document ).on( "change", "#order_shipping_charge", function() {
		orderfunc.sumPrice( $("#order_shipping_charge") );
	});
	$( document ).on( "change", "#order_cod_fee", function() {
		orderfunc.sumPrice( $("#order_cod_fee") );
	});
	<?php if( $this->is_reduced_tax_rate() ): ?>
	$( document ).on( "change", "#order_tax_standard", function() {
		orderfunc.sumTax();
	});
	$( document ).on( "change", "#order_tax_reduced", function() {
		orderfunc.sumTax();
	});
	<?php endif; ?>
	$( document ).on( "change", "#order_tax", function() {
		orderfunc.sumPrice( $("#order_tax") );
	});
	$( document ).on( "click", "input[name*='update_order_edit']", function() {
		if( ('completion' == $("#order_taio option:selected").val() || 'continuation' == $("#order_taio option:selected").val()) && '<?php echo substr(get_date_from_gmt(gmdate('Y-m-d H:i:s', time())), 0, 10); ?>' != $('#modified').val() ){
			if( confirm("<?php _e("Are you sure you want to change to today's date Date of renovation?", "usces"); ?>\n<?php _e("Please press the cancel If you want to update without changing the modified date.", "usces"); ?>") ){
				$('#up_modified').val('update');
			}else{
				$('#up_modified').val('');
			}
		}
		return true;
	});
	orderfunc.make_delivery_time(<?php echo $order_delivery_method; ?>);

	$( document ).on( "click", "#get_member", function() {
		if( '' == $("input[name='customer[mailaddress]']").val() ){
			alert("<?php _e('Enter the e-mail.','usces'); ?>");
			return;
		}
		if( '' != $("input[name='customer[name1]']").val() || '' != $("input[name='delivery[name1]']").val() ){
			if( !confirm("<?php _e("I will overwrite the ship-to address the customer's address. Would you like?", "usces"); ?>") ){
				return;
			}
		}
		orderfunc.getMember($("input[name='customer[mailaddress]']").val());
	});
<?php if($order_action == 'new') : ?>
	$( document ).on( "click", "#customercopy", function() {
		if( '' != $("input[name='delivery[name1]']").val() || 
			'' != $("input[name='delivery[name2]']").val() || 
			'' != $("input[name='delivery[name3]']").val() || 
			'' != $("input[name='delivery[name4]']").val() || 
			'' != $("input[name='delivery[zipcode]']").val() || 
			'' != $("input[name='delivery[address1]']").val() || 
			'' != $("input[name='delivery[address2]']").val() || 
			'' != $("input[name='delivery[address3]']").val() || 
			'' != $("input[name='delivery[tel]']").val() || 
			'' != $("input[name='delivery[fax]']").val() ) {
			if( !confirm("<?php _e('I will overwrite the ship-to address the customer\'s address. Would you like?', 'usces'); ?>") ) 
				return;
		}
		$("input[name='delivery[name1]']").val($("input[name='customer[name1]']").val());
		$("input[name='delivery[name2]']").val($("input[name='customer[name2]']").val());
		$("input[name='delivery[name3]']").val($("input[name='customer[name3]']").val());
		$("input[name='delivery[name4]']").val($("input[name='customer[name4]']").val());
		$("input[name='delivery[zipcode]']").val($("input[name='customer[zipcode]']").val());
		$("#delivery_country").val($("#customer_country option:selected").val());
		$("#delivery_pref").val($("#customer_pref option:selected").val());
		$("input[name='delivery[address1]']").val($("input[name='customer[address1]']").val());
		$("input[name='delivery[address2]']").val($("input[name='customer[address2]']").val());
		$("input[name='delivery[address3]']").val($("input[name='customer[address3]']").val());
		$("input[name='delivery[tel]']").val($("input[name='customer[tel]']").val());
		$("input[name='delivery[fax]']").val($("input[name='customer[fax]']").val());
	});
<?php endif; ?>

	$("#recalc").click(function(){ orderfunc.recalculation(); });
});
</script>
<div class="wrap">
<div class="usces_admin">
<form action="<?php echo USCES_ADMIN_URL.'?page=usces_orderlist&order_action='.$oa; ?>" method="post" name="editpost">
<h1>Welcart Management <?php _e('Edit order data','usces'); ?></h1>
<p class="version_info">Version <?php echo USCES_VERSION; ?></p>
<?php usces_admin_action_status(); ?>
<?php if( $navibutton ) echo '<div class="edit_pagenav">' . $navibutton . '</div>'; ?>
<div class="usces_tablenav usces_tablenav_top">
<div class="ordernavi"><input name="update_order_edit" class="button button-primary" type="submit" value="<?php _e('change decision', 'usces'); ?>" /><?php _e("When you change amount, please click 'Edit' before you finish your process.", 'usces'); ?></div>
<div id="mailVisiLink" class="screen-field"><?php _e('Show the mail/print field', 'usces'); ?><span class="dashicons dashicons-arrow-up"></span></div>
</div>
<div id="mailBox">
<table>
<tr>
<td><input name="check[ordermail]" type="checkbox" value="ordermail"<?php if(isset($ordercheck['ordermail'])) echo ' checked="checked"' ; ?> /><a href="#" id="orderConfirmMail"><?php _e('Mail for confirmation of order', 'usces'); ?></a></td>
<td><input name="check[changemail]" type="checkbox" value="changemail"<?php if(isset($ordercheck['changemail'])) echo ' checked="checked"' ; ?> /><a href="#" id="changeConfirmMail"><?php _e('Mail for confiemation of change', 'usces'); ?></a></td>
<td><input name="check[receiptmail]" type="checkbox" value="receiptmail"<?php if(isset($ordercheck['receiptmail'])) echo ' checked="checked"' ; ?> /><a href="#" id="receiptConfirmMail"><?php _e('Mail for confirmation of transter', 'usces'); ?></a></td>
<td><input name="check[mitumorimail]" type="checkbox" value="mitumorimail"<?php if(isset($ordercheck['mitumorimail'])) echo ' checked="checked"' ; ?> /><a href="#" id="mitumoriConfirmMail"><?php _e('estimate mail', 'usces'); ?></a></td>
<td><input name="check[cancelmail]" type="checkbox" value="cancelmail"<?php if(isset($ordercheck['cancelmail'])) echo ' checked="checked"' ; ?> /><a href="#" id="cancelConfirmMail"><?php _e('Cancelling mail', 'usces'); ?></a></td>
<td><input name="check[othermail]" type="checkbox" value="othermail"<?php if(isset($ordercheck['othermail'])) echo ' checked="checked"' ; ?> /><a href="#" id="otherConfirmMail"><?php _e('Other mail', 'usces'); ?></a></td>
</tr>
<tr>
<td><input name="check[completionmail]" type="checkbox" value="completionmail"<?php if(isset($ordercheck['completionmail'])) echo ' checked="checked"' ; ?> /><a href="#" id="completionMail"><?php _e('Mail for Shipping', 'usces'); ?></a></td>
<td><input name="check[mitumoriprint]" type="checkbox" value="mitumoriprint"<?php if(isset($ordercheck['mitumoriprint'])) echo ' checked="checked"' ; ?> /><a href="#" id="mitumoriprint"><?php _e('print out the estimate', 'usces'); ?></a></td>
<td><input name="check[nohinprint]" type="checkbox" value="nohinprint"<?php if(isset($ordercheck['nohinprint'])) echo ' checked="checked"' ; ?> /><a href="#" id="nohinprint"><?php _e('print out Delivery Statement', 'usces'); ?></a></td>
<td><input name="check[billprint]" type="checkbox" value="billprint"<?php if(isset($ordercheck['billprint'])) echo ' checked="checked"' ; ?> /><a href="#" id="billprint"><?php _e('Print Invoice', 'usces'); ?></a></td>
<td><input name="check[receiptprint]" type="checkbox" value="receiptprint"<?php if(isset($ordercheck['receiptprint'])) echo ' checked="checked"' ; ?> /><a href="#" id="receiptprint"><?php _e('Print Receipt', 'usces'); ?></a></td>
<?php echo apply_filters('usces_filter_admin_ordernavi', '<td colspan="2">&nbsp;</td>', $ordercheck ); ?>
</tr>
<tr>
<td colspan="12"><span style="color:#CC3300"><?php _e("When there is any change, please press the 'change decision' before you send or print.", 'usces'); ?></span></td>
</tr>
</table>
</div>

<div class="info_head">
<table>

	<tr>
		<td colspan="6" class="midasi0"><?php _e('order details', 'usces'); ?></td>
	</tr>
	
	<?php do_action( 'usces_action_order_edit_form_detail_top', $data, $csod_meta, $filter_args ); ?>
	
	<tr>
		<td class="label border"><?php _e('Order number', 'usces'); ?><br />(<?php echo (isset($data['ID']) ? $data['ID'] : ''); ?>)</td>
		<td class="col1 border"><div class="rod"><?php echo (isset($data['ID']) ? usces_get_deco_order_id( $data['ID'] ) : ''); ?></div></td>
		<td class="col3 label border"><?php _e('order date', 'usces'); ?></td>
		<td class="col2 border"><div class="rod long"><?php echo esc_html(isset($data['order_date']) ? $data['order_date'] : ''); ?></div></td>
		<td class="label border"><?php echo apply_filters('usces_filter_admin_modified_label', __('shpping date', 'usces') ); ?></td>
		<td class="border"><div id="order_modified" class="rod long"><?php echo esc_html(isset($data['order_modified']) ? $data['order_modified'] : ''); ?></div></td>
	</tr>

	<tr>
		<td class="label"><?php _e('membership number', 'usces'); ?></td>
		<td class="col1"><div id="member_id_label" class="rod large short"><?php echo (isset($data['mem_id']) ? $data['mem_id'] : ''); ?></div><?php if($order_action == 'new'){ ?><input name="member_id" id="member_id" type="hidden" /><?php }else{ ?><input name="member_id" id="member_id" type="hidden" value="<?php echo (isset($data['mem_id']) ? $data['mem_id'] : ''); ?>" /><?php } ?></td>

		<td colspan="2" class="wrap_td">
			<table border="0" cellspacing="0" class="cus_info">
				<tr>
					<td class="label"><?php _e('e-mail adress', 'usces'); ?></td>
					<td class="col2"><input name="customer[mailaddress]" type="text" class="text long" value="<?php echo esc_attr(isset($data['order_email']) ? $data['order_email'] : ''); ?>" /><input name="get_member" type="button" class="button" id="get_member" value="<?php _e('Membership information acquisition', 'usces'); ?>" /></td>
				</tr>
			</table>
		</td>

		<?php if($order_action == 'new') : ?>
		<td colspan="2" class="midasi1"><?php _e('shipping address', 'usces'); ?><input type="button" class="button" id="customercopy" value="<?php _e("Same shipping address", "usces"); ?>"></td>
		<?php else : ?>
		<td colspan="2" class="midasi1"><?php _e('shipping address', 'usces'); ?></td>
		<?php endif; ?>
	</tr>

	<tr>
		<td colspan="2" class="wrap_td right_area">
			<!-- Delivery brock -->
			<table border="0" cellspacing="0" class="cus_info">
				<tr>
					<td class="label"><?php _e('payment method', 'usces'); ?></td>
					<td class="col1">
						<select name="offer[payment_name]" id="order_payment_name">
						    <option value="#none#"><?php _e('-- Select --', 'usces'); ?></option>
						<?php 
						if( $payment_method ) {
							foreach ((array)$payment_method as $payments) {
								if( $payments['name'] != '' ) {
									$selected = (isset($data['order_payment_name']) && $payments['name'] == $data['order_payment_name']) ? ' selected="selected"' : '';
						?>
							<option value="<?php echo esc_attr($payments['name']); ?>"<?php echo $selected; ?>><?php echo esc_attr($payments['name']); ?></option>
						<?php
								}
							}
						}
						?>
						</select>
					</td>
				</tr>
				<tr>
					<td class="label"><?php _e('shipping option','usces'); ?></td>
					<td class="col1">
						<select name="offer[delivery_method]" id="delivery_method_select">
							<option value="-1"><?php _e('Non-request', 'usces_dual'); ?></option>
						<?php
						foreach ((array)$this->options['delivery_method'] as $dkey => $delivery) {
							$selected = $order_delivery_method == $delivery['id'] ? ' selected="selected"' : '';
							echo "\t<option value='" . esc_attr($delivery['id']) . "'{$selected}>" . esc_attr($delivery['name']) . "</option>\n";
						}
						?>
						</select>
					</td>
					</tr>
				<tr>
					<td class="label"><?php _e('Delivery date', 'usces'); ?></td>
					<td class="col1">
						<?php
						$delivery_days_select = '<input type="text" name="offer[delivery_date]" id="delivery_date_select" style="width:100%;" value="'.$delivery_date.'" />';
						echo apply_filters( 'usces_filter_order_edit_delivery_days_select', $delivery_days_select, $data, $delivery_after_days, $no_preference );
						?>
					</td>
				</tr>
				<tr>
					<td class="label"><?php _e('delivery time', 'usces'); ?></td>
					<td class="col1">
						<select name="offer[delivery_time]" id="delivery_time_select">
							<option value="<?php _e('Non-request', 'usces_dual'); ?>"><?php _e('Non-request', 'usces_dual'); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="label"><?php _e('Shipping date', 'usces'); ?></td>
					<td class="col1">
						<input type="text" name="offer[delidue_date]" id="delidue_date_select" style="width:100%;" value="<?php echo $delidue_date; ?>" />
					</td>
				</tr>
				<?php do_action( 'usces_action_order_edit_form_delivery_block', $data, $cscs_meta, $action_args ); ?>
			</table>
		</td>

		<td colspan="2" rowspan="2" class="wrap_td">
			<table border="0" cellspacing="0" class="cus_info">
				<?php echo uesces_get_admin_addressform( 'customer', $data, $cscs_meta ); ?>
			</table>
		</td>
		
		<td colspan="2" rowspan="2" class="wrap_td">
			<table border="0" cellspacing="0" class="deli_info">
			<?php echo uesces_get_admin_addressform( 'delivery', $deli, $csde_meta ); ?>
			</table>
		</td>
	</tr>

	<tr>
		<td colspan="2" rowspan="2" class="wrap_td">
			<!-- Status brock -->
			<table border="0" cellspacing="0" class="cus_info">
				<tr>
					<td colspan="2" class="midasi3"><?php _e('Status', 'usces'); ?></td>
				</tr>
				<tr>
					<td class="label status"><?php _e('The correspondence situation', 'usces'); ?></td>
					<td class="col1 status">
						<select name="offer[taio]" id="order_taio">
							<option value='#none#'><?php _e('new order', 'usces'); ?></option>
						<?php 
							$exclusion_status = apply_filters( 'usces_filter_taio_exclusion_status', array('noreceipt','receipted','pending', 'estimate', 'adminorder') );
							foreach ($management_status as $status_key => $status_name):
								if( in_array($status_key, $exclusion_status) )
									continue;
						?>
							<option value="<?php echo $status_key; ?>"<?php if($taio == $status_key){ echo ' selected="selected"';} ?>><?php echo $status_name; ?></option>
						<?php 
							endforeach;
						?>
						</select>
					</td>
				</tr>
			<?php
				$receiptlabel = '&nbsp;';
				$receiptbox = '&nbsp;';
				if( $receipt != '' ){
						$receiptlabel = __('transfer statement', 'usces');
						$selected = array( 'noreceipt'=>'', 'receipted'=>'', 'pending'=>'' );
						if( array_key_exists( $receipt, $selected ) ) $selected[$receipt] = ' selected="selected"';
						$receiptbox = '
						<select name="offer[receipt]">
							<option value="noreceipt"'.$selected['noreceipt'].'>'.$management_status['noreceipt'].'</option>
							<option value="receipted"'.$selected['receipted'].'>'.$management_status['receipted'].'</option>
							<option value="pending"'.$selected['pending'].'>'.$management_status['pending'].'</option>
						</select>';
				}
			?>
				<tr>
					<td class="label status" id="receiptlabel"><?php echo( $receiptlabel ); ?></td>
					<td class="col1 status" id="receiptbox"><?php echo( $receiptbox ); ?></td>
				</tr>
				<?php if( $admin != '' ): ?>
				<tr>
					<td class="label status"><?php echo __('estimate order', 'usces'); ?></td>
					<td class="col1 status">
					<select name="offer[admin]">
						<option value='adminorder'<?php if($admin == 'adminorder'){ echo 'selected="selected"';} ?>><?php echo $management_status['adminorder']; ?></option>
						<option value='estimate'<?php if($admin == 'estimate'){ echo 'selected="selected"';} ?>><?php echo $management_status['estimate']; ?></option>
					</select>
					</td>
				</tr>
				<?php endif; ?>
				<?php do_action( 'usces_action_order_edit_form_status_block_middle', $data, $cscs_meta, $action_args ); ?>
				<tr>
					<td colspan="2" class="status">
					<div class="midasi2"><?php if($condition['display_mode'] == 'Usualsale'){echo __('normal sale', 'usces');}elseif($condition['display_mode'] == 'Promotionsale'){echo __('Sale Campaign', 'usces');} ?></div>
					<div class="condition">
					<?php if ( $condition['display_mode'] == 'Promotionsale' ) : ?>
					<span><?php _e('Special Benefits', 'usces'); ?> : </span><?php echo $condition["campaign_privilege"]; ?> (<?php if($condition["campaign_privilege"] == 'discount'){echo esc_html($condition["privilege_discount"]).__('% Discount', 'usces');}elseif($condition["campaign_privilege"] == 'point'){echo esc_html($condition["privilege_point"]).__(" times (limited to members)", 'usces');} ?>) <br />
					<span><?php _e('applied material', 'usces'); ?> : </span><?php if( !isset($condition["campaign_category"]) || $condition["campaign_category"] == 0){echo __('all the items', 'usces');} else {echo esc_html(get_cat_name($condition["campaign_category"]));} ?><br />
					<?php endif; ?>
					</div></td>
				</tr>
				<?php do_action( 'usces_action_order_edit_form_status_block', $data, $cscs_meta, $action_args ); ?>
			</table>
		</td>
	</tr>

	<tr>
		<td class="label cus_note_label"><?php _e('Notes', 'usces'); ?></td>
		<td colspan="3" class="cus_note_value"><textarea name="offer[note]"><?php echo esc_attr(isset($data['order_note']) ? $data['order_note'] : ''); ?></textarea></td>
	</tr>
	<?php do_action( 'usces_action_order_edit_form_detail_bottom', $data, $cscs_meta, $action_args ); ?>
</table>
</div>

<div class="info_head">
<table>
<tr>
<td class="midasi0"><?php _e('Custom order field', 'usces'); ?></td>
<td class="midasi0"><?php _e('Payment information', 'usces'); ?></td>
</tr>
<tr>
<td class="wrap_td">
<table class="order_custom_wrap">
<tr>
<?php do_action( 'usces_action_order_edit_form_custom', $data, $csod_meta ); ?>
<?php
usces_admin_custom_field_input($csod_meta, 'order', '');
?>
</tr>

</table>
</td>
<td class="wrap_td">
<table class="settle_info_wrap">
<?php usces_settle_info_field( $order_id, 'tr' ); ?>
</table>
<?php do_action( 'usces_action_order_edit_form_settle_info', $data, $action_args ); ?>
</td>
</tr>
</table>
</div>

<div id="cart">
<?php
ob_start();
?>
<table cellspacing="0" id="cart_table">
	<thead>
		<tr>
			<th colspan="5" class="aright"><?php _e('Total Amount','usces'); ?></th>
			<th id="total_full_top" class="aright">&nbsp;</th>
			<th colspan="2">&nbsp;<?php _e('Currency','usces'); ?>(<?php usces_crcode(); ?>)</th>
		</tr>
	<tr>
		<th scope="row" class="num"><?php echo __('No.','usces'); ?></th>
		<th class="thumbnail"> <?php //echo __('thumbnail','usces'); ?></th>
		<th><?php _e('Items','usces'); ?></th>
		<th class="price"><?php _e('Unit price','usces'); ?></th>
		<th class="quantity"><?php _e('Quantity','usces'); ?></th>
		<th class="subtotal"><?php _e('Amount','usces'); ?>(<?php usces_crcode(); ?>)</th>
		<th class="stock"><?php _e('Current stock', 'usces'); ?></th>
		<th class="action"><input name="addButton" id="addCartButton" class="button" type="button" value="<?php _e('Add item', 'usces'); ?>" /></th>
	</tr>
	</thead>
	<tbody id="orderitemlist">
<?php echo usces_get_ordercart_row( $order_id, $cart ); ?>
	</tbody>
		<tfoot>
		<tr>
			<th colspan="5" class="aright"><?php _e('total items','usces'); ?></th>
			<th id="item_total" class="aright">&nbsp;</th>
			<?php echo apply_filters( 'usces_filter_ordereditform_item_total_td', '<th colspan="2">&nbsp;</th>', $order_id, $cart ); ?>
		</tr>
<?php if( $this->is_reduced_tax_rate() ): ?>
		<tr>
			<th colspan="5" class="aright"><?php printf( __( "Applies to %s%%", 'usces' ), $this->options['tax_rate'] ); ?></th>
			<th id="subtotal_standard" class="aright">&nbsp;</th>
			<th colspan="2">&nbsp;</th>
		</tr>
		<tr>
			<th colspan="5" class="aright"></span><?php printf( __( "Applies to %s%%", 'usces' ), $this->options['tax_rate_reduced'] ); ?></th>
			<th id="subtotal_reduced" class="aright">&nbsp;</th>
			<th colspan="2">&nbsp;</th>
		</tr>
<?php endif; ?>
<?php if( !$this->is_reduced_tax_rate() ): ?>
		<tr>
			<td colspan="5" class="aright"><?php echo apply_filters('usces_confirm_discount_label', __('Campaign discount', 'usces'), $order_id); ?></td>
			<td class="aright" style="color:#FF0000"><input name="offer[discount]" id="order_discount" class="text price" type="text" value="<?php if( isset($data['order_discount']) && !empty($data['order_discount']) ) { usces_crform( $data['order_discount'], false, false, '', false ); } else { echo '0'; } ?>" /></td>
			<td colspan="2"><?php _e('Discounted amount should be shown by -(Minus)', 'usces'); ?>&nbsp;</td>
		</tr>
<?php endif; ?>
<?php if( $this->is_reduced_tax_rate() ):
		if( 'exclude' == $this->options['tax_mode'] ) {
			$order_tax_standard = $this->get_order_meta_value( 'tax_standard', $order_id );
			$order_tax_reduced = $this->get_order_meta_value( 'tax_reduced', $order_id );
			if( empty( $order_tax_standard ) && empty( $order_tax_reduced ) ) {
				$order_tax_standard = ( isset( $data['order_tax'] ) && !empty( $data['order_tax'] ) ) ? $data['order_tax'] : 0;
			}
		}
?>
		<tr>
			<td colspan="5" class="aright"><?php printf( __( "%s%% consumption tax", 'usces' ), $this->options['tax_rate'] ); ?><span id="include_tax_standard"></span></td>
			<td class="aright"><input name="order_tax_standard" id="order_tax_standard" type="text" class="text price" value="<?php if( !empty( $order_tax_standard ) ) { usces_crform( $order_tax_standard, false, false, '', false ); } else { echo '0'; } ?>" /></td>
			<td colspan="2"><?php _e( 'It will be not caluculated automatically.', 'usces' ); ?>&nbsp;</td>
		</tr>
		<tr>
			<td colspan="5" class="aright"><?php printf( __( "%s%% consumption tax", 'usces' ), $this->options['tax_rate_reduced'] ); ?><span id="include_tax_reduced"></span></td>
			<td class="aright"><input name="order_tax_reduced" id="order_tax_reduced" type="text" class="text price" value="<?php if( !empty( $order_tax_reduced ) ) { usces_crform( $order_tax_reduced, false, false, '', false ); } else { echo '0'; } ?>" /></td>
			<td colspan="2"><?php _e( 'It will be not caluculated automatically.', 'usces' ); ?>&nbsp;</td>
		</tr>
		<tr>
			<td colspan="5" class="aright"><?php usces_tax_label(); ?><span id="include_tax"></span></td>
			<td class="aright"><input name="offer[tax]" id="order_tax" type="text" class="text price" value="<?php if( isset($data['order_tax']) && !empty($data['order_tax']) ) { usces_crform( $data['order_tax'], false, false, '', false ); } else { echo '0'; } ?>" readonly /></td>
			<td colspan="2"></td>
		</tr>
<?php else: ?>
	<?php if( 'products' == usces_get_tax_target() ) : ?>
		<tr>
			<td colspan="5" class="aright"><?php usces_tax_label(); ?><span id="include_tax"><?php echo $include_tax; ?></span></td>
			<td class="aright"><input name="offer[tax]" id="order_tax" type="text" class="text price" value="<?php if( isset($data['order_tax']) && !empty($data['order_tax']) ) { usces_crform( $data['order_tax'], false, false, '', false ); } else { echo '0'; } ?>"<?php echo $tax_readonly; ?> /></td>
			<td colspan="2"><?php _e('It will be not caluculated automatically.', 'usces'); ?>&nbsp;</td>
		</tr>
	<?php endif; ?>
<?php endif; ?>
<?php if( $this->is_reduced_tax_rate() ): ?>
		<tr>
			<td colspan="5" class="aright"><?php echo apply_filters('usces_confirm_discount_label', __('Campaign discount', 'usces'), $order_id); ?></td>
			<td class="aright" style="color:#FF0000"><input name="offer[discount]" id="order_discount" class="text price" type="text" value="<?php if( isset($data['order_discount']) && !empty($data['order_discount']) ) { usces_crform( $data['order_discount'], false, false, '', false ); } else { echo '0'; } ?>" /></td>
			<td colspan="2"><?php _e('Discounted amount should be shown by -(Minus)', 'usces'); ?>&nbsp;</td>
		</tr>
<?php endif; ?>
		<tr>
			<td colspan="5" class="aright"><?php _e('Shipping', 'usces'); ?></td>
			<td class="aright"><input name="offer[shipping_charge]" id="order_shipping_charge" class="text price" type="text" value="<?php if( isset($data['order_shipping_charge']) && !empty($data['order_shipping_charge']) ) { usces_crform( $data['order_shipping_charge'], false, false, '', false ); } else { echo '0'; } ?>" /></td>
			<td colspan="2"><?php _e('It will be not caluculated automatically.', 'usces'); ?>&nbsp;</td>
		</tr>
		<tr>
			<td colspan="5" class="aright"><?php echo apply_filters('usces_filter_cod_label', __('COD fee', 'usces'), $order_id); ?></td>
			<td class="aright"><input name="offer[cod_fee]" id="order_cod_fee" class="text price" type="text" value="<?php if( isset($data['order_cod_fee']) && !empty($data['order_cod_fee']) ) { usces_crform( $data['order_cod_fee'], false, false, '', false ); } else { echo '0'; } ?>" /></td>
			<td colspan="2"><?php _e('It will be not caluculated automatically.', 'usces'); ?>&nbsp;</td>
		</tr>
	<?php if( 'all' == usces_get_tax_target() && !$this->is_reduced_tax_rate() ) : ?>
		<tr>
			<td colspan="5" class="aright"><?php usces_tax_label(); ?><span id="include_tax"><?php echo $include_tax; ?></span></td>
			<td class="aright"><input name="offer[tax]" id="order_tax" type="text" class="text price" value="<?php if( isset($data['order_tax']) && !empty($data['order_tax']) ) { usces_crform( $data['order_tax'], false, false, '', false ); } else { echo '0'; } ?>"<?php echo $tax_readonly; ?> /></td>
			<td colspan="2"><?php _e('It will be not caluculated automatically.', 'usces'); ?>&nbsp;</td>
		</tr>
	<?php endif; ?>
		<tr>
			<td colspan="5" class="aright"><?php _e('Used points','usces'); ?></td>
			<td class="aright" style="color:#FF0000"><input name="offer[usedpoint]" id="order_usedpoint" class="text price red" type="text" value="<?php if( isset($data['order_usedpoint']) && !empty($data['order_usedpoint']) ) {echo esc_attr($data['order_usedpoint']); } else { echo '0'; } ?>" /></td>
			<td><?php _e('granted points', 'usces'); ?></td>
			<td class="aright" style="color:#FF0000"><input name="offer[getpoint]" id="order_getpoint" class="text price" type="text" value="<?php if( isset($data['order_getpoint']) && !empty($data['order_getpoint']) ) {echo esc_attr($data['order_getpoint']); } else { echo '0'; } ?>" /></td>
		</tr>
		<tr>
			<th colspan="5" class="aright"><?php _e('Total Amount','usces'); ?></th>
			<th id="total_full" class="aright">&nbsp;</th>
			<th colspan="2"><input name="recalc" id="recalc" class="button" type="button" value="<?php _e('Recalculation', 'usces'); ?>" /></th>
		</tr>
		</tfoot>
</table>
<?php
$cart_table = ob_get_contents();
ob_end_clean();
echo apply_filters( 'usces_filter_ordereditform_carttable', $cart_table, $filter_args );
?>
</div>
<?php if( $this->is_reduced_tax_rate() ): $usces_tax = Welcart_Tax::get_instance(); ?>
<div class="order_edit_notice_reduced_tax_rate"><span class="reduced_tax_rate_mark"><?php echo $usces_tax->reduced_tax_rate_mark; ?></span><?php _e( ' is reduced tax rate', 'usces' ); ?></div>
<?php endif; ?>
<div class="usces_tablenav usces_tablenav_bottom">
<div class="ordernavi"><input name="update_order_edit2" class="button button-primary" type="submit" value="<?php _e('change decision', 'usces'); ?>" /><?php _e("When you change amount, please click 'Edit' before you finish your process.", 'usces'); ?></div>
</div>

<input name="order_action" type="hidden" value="<?php echo $oa; ?>" />
<input name="order_id" id="order_id" type="hidden" value="<?php echo (isset($data['ID']) ? $data['ID'] : ''); ?>" />
<input name="old_getpoint" type="hidden" value="<?php echo (isset($data['order_getpoint']) ? $data['order_getpoint'] : ''); ?>" />
<input name="old_usedpoint" type="hidden" value="<?php echo (isset($data['order_usedpoint']) ? $data['order_usedpoint'] : ''); ?>" />
<input name="up_modified" id="up_modified" type="hidden" value="" />
<input name="modified" id="modified" type="hidden" value="<?php echo esc_attr(isset($data['order_modified']) ? $data['order_modified'] : ''); ?>" />



<div id="dialog_parent" style="position:fixed"></div>
<div id="addItemDialog" title="<?php _e('Add item', 'usces'); ?>">
	<div id="order-response"></div>
	<fieldset>
	<div class="clearfix">
		<div class="dialogsearch">
		<label><?php _e('Item Category', 'usces'); ?></label>
	<?php
		$idObj = get_category_by_slug('item');
		$dropdown_options = apply_filters( 'usces_filter_ordereditform_dropdown_options', array( 'show_option_none' => __('Select a category', 'usces'), 'name' => 'newitemcategory', 'id' => 'newitemcategory', 'hide_empty' => 1, 'hierarchical' => 1, 'orderby' => 'name', 'child_of' => $idObj->term_id ) );
		wp_dropdown_categories($dropdown_options);
	?>
		<br />
		<label><?php _e('Item to be added', 'usces'); ?></label><select name="newitemcode" id="newitemcode"></select><br />
		<div id="loading"></div>
		<label for="name"><?php _e('item code', 'usces'); ?></label>
		<input type="text" name="newitemcodein" id="newitemcodein" class="text" />
		<input name="getitem" id="getitembutton" type="button" class="button" value="<?php _e('Obtain', 'usces'); ?>" onclick="if( jQuery('#newitemcodein').val() == '' ) return; orderItem.getitem(encodeURIComponent(jQuery('#newitemcodein').val()));" />
		</div>
		<div id="newitemform"></div>
	</div>
	</fieldset>
</div>



<div id="mailSendDialog" title="">
	<div id="order-response"></div>
	<fieldset>
		<p><?php _e("Check the mail and click 'send'", 'usces'); ?></p>
		<label><?php _e('e-mail adress', 'usces'); ?></label><input type="text" name="sendmailaddress" id="sendmailaddress" class="text" /><br />
		<label><?php _e('Client name', 'usces'); ?></label><input type="text" name="sendmailname" id="sendmailname" class="text" /><br />
		<label><?php _e('subject', 'usces'); ?></label><input type="text" name="sendmailsubject" id="sendmailsubject" class="text" /><input name="sendmail" id="sendmail" type="button" class="button" value="<?php _e('send', 'usces'); ?>" /><br />
		<textarea name="sendmailmessage" id="sendmailmessage"></textarea>
		<input name="mailChecked" id="mailChecked" type="hidden" />
	</fieldset>
</div>

<div id="mailSendAlert" title="">
	<div id="order-response"></div>
	<fieldset>
	</fieldset>
</div>
<input name="usces_referer" type="hidden" id="usces_referer" value="<?php if(isset($_REQUEST['usces_referer'])) echo esc_url($_REQUEST['usces_referer']); ?>" />
<?php wp_nonce_field( 'order_edit', 'wc_nonce' ); ?>
</form>

<div id="PDFDialog" title="">
	<div id="pdf_response"></div>
	<fieldset>
		<div id="new_pdf"></div>
	</fieldset>
</div>
<?php do_action( 'usces_action_endof_order_edit_form', $data, $action_args ); ?>
</div><!--usces_admin-->
</div><!--wrap-->
