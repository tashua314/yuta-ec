<?php
/**
 * DSK 電算システム
 *
 * @class    DSK_SETTLEMENT
 * @author   Collne Inc.
 * @version  1.0.1
 * @since    1.4.14
 */
class DSK_SETTLEMENT extends SBPS_MAIN
{
	/**
	 * Instance of this class.
	 */
	protected static $instance = null;

	public function __construct() {

		$this->acting_name = 'DSK';
		$this->acting_formal_name = 'DSK 電算システム';

		$this->acting_card = 'dsk_card';
		$this->acting_conv = 'dsk_conv';
		$this->acting_payeasy = 'dsk_payeasy';
		$this->acting_wallet = 'dsk_wallet';
		$this->acting_mobile = 'dsk_mobile';

		$this->acting_flg_card = 'acting_dsk_card';
		$this->acting_flg_conv = 'acting_dsk_conv';
		$this->acting_flg_payeasy = 'acting_dsk_payeasy';
		$this->acting_flg_wallet = 'acting_dsk_wallet';
		$this->acting_flg_mobile = 'acting_dsk_mobile';

		$this->pay_method = array(
			'acting_dsk_card',
			'acting_dsk_conv',
			'acting_dsk_payeasy'
		);

		parent::__construct( 'dsk' );

		$this->initialize_data();
	}

	/**
	 * Return an instance of this class.
	 */
	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Initialize
	 */
	public function initialize_data() {

		$options = get_option( 'usces' );
		if( !array_key_exists( 'dsk', $options['acting_settings'] ) ) {
			$options['acting_settings']['dsk']['merchant_id'] = ( isset( $options['acting_settings']['dsk']['merchant_id'] ) ) ? $options['acting_settings']['dsk']['merchant_id'] : '';
			$options['acting_settings']['dsk']['service_id'] = ( isset( $options['acting_settings']['dsk']['service_id'] ) ) ? $options['acting_settings']['dsk']['service_id'] : '';
			$options['acting_settings']['dsk']['hash_key'] = ( isset( $options['acting_settings']['dsk']['hash_key'] ) ) ? $options['acting_settings']['dsk']['hash_key'] : '';
			$options['acting_settings']['dsk']['ope'] = ( isset( $options['acting_settings']['dsk']['ope'] ) ) ? $options['acting_settings']['dsk']['ope'] : '';
			$options['acting_settings']['dsk']['card_activate'] = ( isset( $options['acting_settings']['dsk']['card_activate'] ) ) ? $options['acting_settings']['dsk']['card_activate'] : 'off';
			$options['acting_settings']['dsk']['3d_secure'] = ( isset( $options['acting_settings']['dsk']['3d_secure'] ) ) ? $options['acting_settings']['dsk']['3d_secure'] : 'off';
			$options['acting_settings']['dsk']['conv_activate'] = ( isset( $options['acting_settings']['dsk']['conv_activate'] ) ) ? $options['acting_settings']['dsk']['conv_activate'] : 'off';
			$options['acting_settings']['dsk']['payeasy_activate'] = ( isset( $options['acting_settings']['dsk']['payeasy_activate'] ) ) ? $options['acting_settings']['dsk']['payeasy_activate'] : 'off';
			$options['acting_settings']['dsk']['wallet_activate'] = 'off';
			$options['acting_settings']['dsk']['mobile_activate'] = 'off';
			update_option( 'usces', $options );
		}

		$available_settlement = get_option( 'usces_available_settlement' );
		if( !in_array( 'dsk', $available_settlement ) ) {
			$available_settlement['dsk'] = $this->acting_formal_name;
			update_option( 'usces_available_settlement', $available_settlement );
		}

		$noreceipt_status = get_option( 'usces_noreceipt_status' );
		if( !in_array( 'acting_dsk_conv', $noreceipt_status ) || !in_array( 'acting_dsk_payeasy', $noreceipt_status ) ) {
			$noreceipt_status[] = 'acting_dsk_conv';
			$noreceipt_status[] = 'acting_dsk_payeasy';
			update_option( 'usces_noreceipt_status', $noreceipt_status );
		}

		$this->unavailable_method = array( 'acting_sbps_card', 'acting_sbps_conv', 'acting_sbps_payeasy', 'acting_sbps_wallet', 'acting_sbps_mobile' );
	}

	/**
	 * @fook   admin_print_footer_scripts
	 * @param  -
	 * @return -
	 * @echo   js
	 */
	public function admin_scripts() {

		$admin_page = ( isset( $_GET['page'] ) ) ? wp_unslash( $_GET['page'] ) : '';
		switch( $admin_page ):
		case 'usces_settlement':
			$settlement_selected = get_option( 'usces_settlement_selected' );
			if( in_array( $this->paymod_id, (array)$settlement_selected ) ):
				$acting_opts = $this->get_acting_settings();
?>
<script type="text/javascript">
jQuery( document ).ready( function( $ ) {

	var dsk_card_activate = "<?php echo $acting_opts['card_activate']; ?>";
	if( "on" == dsk_card_activate ) {
		$( ".card_link_dsk" ).css( "display", "" );
	} else {
		$( ".card_link_dsk" ).css( "display", "none" );
	}

	$( document ).on( "change", ".card_activate_dsk", function() {
		if( "on" == $( this ).val() ) {
			$( ".card_link_dsk" ).css( "display", "" );
		} else {
			$( ".card_link_dsk" ).css( "display", "none" );
		}
	});
});
</script>
<?php
			endif;
			break;
		endswitch;
	}

	/**
	 * 決済オプション登録・更新
	 * @fook   usces_action_admin_settlement_update
	 * @param  -
	 * @return -
	 */
	public function settlement_update() {
		global $usces;

		if( 'dsk' != $_POST['acting'] ) {
			return;
		}

		$this->error_mes = '';
		$options = get_option( 'usces' );
		$payment_method = usces_get_system_option( 'usces_payment_method', 'settlement' );

		unset( $options['acting_settings']['dsk'] );
		$options['acting_settings']['dsk']['merchant_id'] = ( isset( $_POST['merchant_id'] ) ) ? $_POST['merchant_id'] : '';
		$options['acting_settings']['dsk']['service_id'] = ( isset( $_POST['service_id'] ) ) ? $_POST['service_id'] : '';
		$options['acting_settings']['dsk']['hash_key'] = ( isset( $_POST['hash_key'] ) ) ? $_POST['hash_key'] : '';
		$options['acting_settings']['dsk']['ope'] = ( isset( $_POST['ope'] ) ) ? $_POST['ope'] : '';
		$options['acting_settings']['dsk']['card_activate'] = ( isset( $_POST['card_activate'] ) ) ? $_POST['card_activate'] : 'off';
		$options['acting_settings']['dsk']['3d_secure'] = ( isset( $_POST['3d_secure'] ) ) ? $_POST['3d_secure'] : 'off';
		$options['acting_settings']['dsk']['conv_activate'] = ( isset( $_POST['conv_activate'] ) ) ? $_POST['conv_activate'] : 'off';
		$options['acting_settings']['dsk']['payeasy_activate'] = ( isset( $_POST['payeasy_activate'] ) ) ? $_POST['payeasy_activate'] : 'off';
		$options['acting_settings']['dsk']['wallet_activate'] = 'off';
		$options['acting_settings']['dsk']['mobile_activate'] = 'off';

		if( 'on' == $options['acting_settings']['dsk']['card_activate'] || 'on' == $options['acting_settings']['dsk']['conv_activate'] || 'on' == $options['acting_settings']['dsk']['payeasy_activate'] ) {
			$unavailable_activate = false;
			foreach( $payment_method as $settlement => $payment ) {
				if( in_array( $settlement, $this->unavailable_method ) && 'activate' == $payment['use'] ) {
					$unavailable_activate = true;
					break;
				}
			}
			if( $unavailable_activate ) {
				$this->error_mes .= __( '* Settlement that can not be used together is activated.', 'usces' ).'<br />';
			} else {
				if( WCUtils::is_blank( $_POST['merchant_id'] ) ) {
					$this->error_mes .= '※マーチャントID を入力してください<br />';
				}
				if( WCUtils::is_blank( $_POST['service_id'] ) ) {
					$this->error_mes .= '※サービスID を入力してください<br />';
				}
				if( WCUtils::is_blank( $_POST['hash_key'] ) ) {
					$this->error_mes .= '※ハッシュキーを入力してください<br />';
				}
			}
		}

		if( WCUtils::is_blank( $this->error_mes ) ) {
			$usces->action_status = 'success';
			$usces->action_message = __( 'options are updated', 'usces' );
			if( 'on' == $options['acting_settings']['dsk']['card_activate'] || 'on' == $options['acting_settings']['dsk']['conv_activate'] || 'on' == $options['acting_settings']['dsk']['payeasy_activate'] ) {
				$options['acting_settings']['dsk']['activate'] = 'on';
				$options['acting_settings']['dsk']['send_url'] = 'https://fep.sps-system.com/f01/FepBuyInfoReceive.do';
				$options['acting_settings']['dsk']['send_url_check'] = 'https://stbfep.sps-system.com/Extra/BuyRequestAction.do';
				$options['acting_settings']['dsk']['send_url_test'] = 'https://stbfep.sps-system.com/f01/FepBuyInfoReceive.do';
				$options['acting_settings']['dsk']['token_url'] = '';
				$options['acting_settings']['dsk']['token_url_test'] = '';
				$options['acting_settings']['dsk']['api_url'] = '';
				$options['acting_settings']['dsk']['api_url_test'] = '';
				if( 'on' == $options['acting_settings']['dsk']['card_activate'] ) {
					$usces->payment_structure[$this->acting_flg_card] = 'カード決済（DSK）';
				} else {
					unset( $usces->payment_structure[$this->acting_flg_card] );
				}
				if( 'on' == $options['acting_settings']['dsk']['conv_activate'] ) {
					$usces->payment_structure[$this->acting_flg_conv] = 'コンビニ決済（DSK）';
				} else {
					unset( $usces->payment_structure[$this->acting_flg_conv] );
				}
				if( 'on' == $options['acting_settings']['dsk']['payeasy_activate'] ) {
					$usces->payment_structure[$this->acting_flg_payeasy] = 'ペイジー決済（DSK）';
				} else {
					unset( $usces->payment_structure[$this->acting_flg_payeasy] );
				}
				usces_admin_orderlist_show_wc_trans_id();
			} else {
				$options['acting_settings']['dsk']['activate'] = 'off';
			}
		} else {
			$usces->action_status = 'error';
			$usces->action_message = __( 'Data have deficiency.', 'usces' );
			$options['acting_settings']['dsk']['activate'] = 'off';
			unset( $usces->payment_structure[$this->acting_flg_card] );
			unset( $usces->payment_structure[$this->acting_flg_conv] );
			unset( $usces->payment_structure[$this->acting_flg_payeasy] );
		}
		ksort( $usces->payment_structure );
		update_option( 'usces', $options );
		update_option( 'usces_payment_structure', $usces->payment_structure );
	}

	/**
	 * クレジット決済設定画面フォーム
	 * @fook   usces_action_settlement_tab_body
	 * @param  -
	 * @return -
	 * @echo   html
	 */
	public function settlement_tab_body() {

		$acting_opts = $this->get_acting_settings();
		$settlement_selected = get_option( 'usces_settlement_selected' );
		if( in_array( 'dsk', (array)$settlement_selected ) ):
?>
	<div id="uscestabs_dsk">
	<div class="settlement_service"><span class="service_title"><?php echo $this->acting_formal_name; ?></span></div>
	<?php if( isset( $_POST['acting'] ) && 'dsk' == $_POST['acting'] ): ?>
		<?php if( '' != $this->error_mes ): ?>
		<div class="error_message"><?php echo $this->error_mes; ?></div>
		<?php elseif( isset( $acting_opts['activate'] ) && 'on' == $acting_opts['activate'] ): ?>
		<div class="message"><?php _e( 'Test thoroughly before use.', 'usces' ); ?></div>
		<?php endif; ?>
	<?php endif; ?>
	<form action="" method="post" name="dsk_form" id="dsk_form">
		<table class="settle_table">
			<tr>
				<th><a class="explanation-label" id="ex_merchant_id_dsk">マーチャントID</a></th>
				<td><input name="merchant_id" type="text" id="merchant_id_dsk" value="<?php echo esc_html( isset( $acting_opts['merchant_id'] ) ? $acting_opts['merchant_id'] : '' ); ?>" size="20" maxlength="5" /></td>
			</tr>
			<tr id="ex_merchant_id_dsk" class="explanation"><td colspan="2">契約時にDSKから発行されるマーチャントID（半角数字）</td></tr>
			<tr>
				<th><a class="explanation-label" id="label_ex_service_id_dsk">サービスID</a></th>
				<td><input name="service_id" type="text" id="service_id_dsk" value="<?php echo esc_html( isset( $acting_opts['service_id'] ) ? $acting_opts['service_id'] : '' ); ?>" size="20" maxlength="3" /></td>
			</tr>
			<tr id="ex_service_id_dsk" class="explanation"><td colspan="2">契約時にDSKから発行されるサービスID（半角数字）</td></tr>
			<tr>
				<th><a class="explanation-label" id="label_ex_hash_key_sk">ハッシュキー</a></th>
				<td><input name="hash_key" type="text" id="hash_key_dsk" value="<?php echo esc_html( isset( $acting_opts['hash_key'] ) ? $acting_opts['hash_key'] : '' ); ?>" size="40" maxlength="40" /></td>
			</tr>
			<tr id="ex_hash_key_dsk" class="explanation"><td colspan="2">契約時にDSKから発行されるハッシュキー（半角英数）</td></tr>
			<tr>
				<th><a class="explanation-label" id="label_ex_ope_dsk"><?php _e( 'Operation Environment', 'usces' ); ?></a></th>
				<td><label><input name="ope" type="radio" id="ope_dsk_1" value="check"<?php if( isset( $acting_opts['ope'] ) && $acting_opts['ope'] == 'check' ) echo ' checked="checked"'; ?> /><span>接続支援サイト</span></label><br />
					<label><input name="ope" type="radio" id="ope_dsk_2" value="test"<?php if( isset( $acting_opts['ope'] ) && $acting_opts['ope'] == 'test' ) echo ' checked="checked"'; ?> /><span>テスト環境</span></label><br />
					<label><input name="ope" type="radio" id="ope_dsk_3" value="public"<?php if( isset( $acting_opts['ope'] ) && $acting_opts['ope'] == 'public' ) echo ' checked="checked"'; ?> /><span>本番環境</span></label>
				</td>
			</tr>
			<tr id="ex_ope_dsk" class="explanation"><td colspan="2"><?php _e( 'Switch the operating environment.', 'usces' ); ?></td></tr>
		</table>
		<table class="settle_table">
			<tr>
				<th>クレジットカード決済</th>
				<td><label><input name="card_activate" type="radio" class="card_activate_dsk" id="card_activate_dsk_1" value="on"<?php if( isset( $acting_opts['card_activate'] ) && $acting_opts['card_activate'] == 'on' ) echo ' checked="checked"'; ?> /><span><?php _e( 'Use', 'usces' ); ?></span></label><br />
					<label><input name="card_activate" type="radio" class="card_activate_dsk" id="card_activate_dsk_0" value="off"<?php if( isset( $acting_opts['card_activate'] ) && $acting_opts['card_activate'] == 'off' ) echo ' checked="checked"'; ?> /><span><?php _e( 'Do not Use', 'usces' ); ?></span></label>
				</td>
			</tr>
			<tr class="card_link_dsk">
				<th>3Dセキュア</th>
				<td><label><input name="3d_secure" type="radio" id="3d_secure_dsk_1" value="on"<?php if( isset( $acting_opts['3d_secure'] ) && $acting_opts['3d_secure'] == 'on' ) echo ' checked="checked"'; ?> /><span><?php _e( 'Use', 'usces' ); ?></span></label><br />
					<label><input name="3d_secure" type="radio" id="3d_secure_dsk_2" value="off"<?php if( isset( $acting_opts['3d_secure'] ) && $acting_opts['3d_secure'] == 'off' ) echo ' checked="checked"'; ?> /><span><?php _e( 'Do not Use', 'usces' ); ?></span></label>
				</td>
			</tr>
		</table>
		<table class="settle_table">
			<tr>
				<th>コンビニ決済</th>
				<td><label><input name="conv_activate" type="radio" id="conv_activate_dsk_1" value="on"<?php if( isset( $acting_opts['conv_activate'] ) && $acting_opts['conv_activate'] == 'on' ) echo ' checked="checked"'; ?> /><span><?php _e( 'Use', 'usces' ); ?></span></label><br />
					<label><input name="conv_activate" type="radio" id="conv_activate_dsk_2" value="off"<?php if( isset( $acting_opts['conv_activate'] ) && $acting_opts['conv_activate'] == 'off' ) echo ' checked="checked"'; ?> /><span><?php _e( 'Do not Use', 'usces' ); ?></span></label>
				</td>
			</tr>
		</table>
		<table class="settle_table">
			<tr>
				<th>Pay-easy（ペイジー）決済</th>
				<td><label><input name="payeasy_activate" type="radio" id="payeasy_activate_dsk_1" value="on"<?php if( isset( $acting_opts['payeasy_activate'] ) && $acting_opts['payeasy_activate'] == 'on' ) echo ' checked="checked"'; ?> /><span><?php _e( 'Use', 'usces' ); ?></span></label><br />
					<label><input name="payeasy_activate" type="radio" id="payeasy_activate_dsk_2" value="off"<?php if( isset( $acting_opts['payeasy_activate'] ) && $acting_opts['payeasy_activate'] == 'off' ) echo ' checked="checked"'; ?> /><span><?php _e( 'Do not Use', 'usces' ); ?></span></label>
				</td>
			</tr>
		</table>
		<input name="acting" type="hidden" value="dsk" />
		<input name="usces_option_update" type="submit" class="button button-primary" value="DSK の設定を更新する" />
		<?php wp_nonce_field( 'admin_settlement', 'wc_nonce' ); ?>
	</form>
	<div class="settle_exp">
		<p><strong>DSK 株式会社電算システム</strong></p>
		<a href="https://www.welcart.com/wc-settlement/dsk_guide/" target="_blank">DSK の詳細はこちら 》</a>
		<p></p>
		<p>この決済は「外部リンク型」の決済システムです。</p>
		<p>「外部リンク型」とは、決済会社のページへ遷移してカード情報を入力する決済システムです。</p>
		<p>尚、本番環境では、正規SSL証明書のみでのSSL通信となりますのでご注意ください。</p>
	</div>
	</div><!-- uscestabs_dsk -->
<?php
		endif;
	}
}
