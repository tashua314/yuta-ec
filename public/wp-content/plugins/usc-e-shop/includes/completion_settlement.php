<?php
if( isset( $this ) ) {
	$usces = &$this;
}

$html = '';
$html = apply_filters( 'usces_filter_completion_settlement_message', $html, $usces_entries );
