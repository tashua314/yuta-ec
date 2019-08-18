<?php
class dataList
{
	var $table;			//テーブル名
	var $rows;			//データ
	var $action;		//アクション
	var $startRow;		//表示開始行番号
	var $maxRow;		//最大表示行数
	var $currentPage;	//現在のページNo
	var $firstPage;		//最初のページNo
	var $previousPage;	//前のページNo
	var $nextPage;		//次のページNo
	var $lastPage;		//最終ページNo
	var $naviMaxButton;	//ページネーション・ナビのボタンの数
	var $dataTableNavigation;	//ナヴィゲーションhtmlコード
	var $arr_period;	//表示データ期間
	var $arr_search;	//サーチ条件
	var $searchSql;		//簡易絞込みSQL
	var $searchSwitchStatus;	//サーチ表示スイッチ
	var $columns;		//データカラム
	var $sortColumn;	//現在ソート中のフィールド
	var $sortOldColumn;
	var $sortSwitchs;	//各フィールド毎の昇順降順スイッチ
	var $userHeaderNames;	//ユーザー指定のヘッダ名
	var $action_status, $action_message;
	var $pageLimit;		//ページ制限
	var $exportMode;	//IDのみ
		
	//Constructor
	function __construct($tableName, $arr_column)
	{
		$this->table = $tableName;
		$this->columns = $arr_column;
		$this->rows = array();

		$this->maxRow = apply_filters( 'usces_filter_itemlist_maxrow', 30);
		$this->naviMaxButton = 11;
		$this->firstPage = 1;
		$this->action_status = 'none';
		$this->action_message = '';
		$this->pageLimit = 'on';
		$this->exportMode = false;

		$this->SetParamByQuery();

		$this->arr_period = array(__('This month', 'usces'), __('Last month', 'usces'), __('The past one week', 'usces'), __('Last 30 days', 'usces'), __('Last 90days', 'usces'), __('All', 'usces'));


	}

	function MakeTable()
	{

		$this->SetParam();
		
		switch ($this->action){
		
			case 'searchIn':
				$this->SearchIn();
				$res = $this->GetRows();
				break;
				
			case 'searchOut':
				$this->SearchOut();
				$res = $this->GetRows();
				break;
			
			case 'changeSort':
				$res = $this->GetRows();
				break;
			
			case 'changePage':
				$res = $this->GetRows();
				break;
			
			case 'collective_zaiko':
				usces_all_change_zaiko($this);
				$res = $this->GetRows();
				break;
				
			case 'collective_display_status':
				usces_all_change_itemdisplay($this);
				$res = $this->GetRows();
				break;
				
			case 'collective_delete':
				usces_all_delete_itemdata($this);
				$this->SetTotalRow();
				$res = $this->GetRows();
				break;
				
			case 'refresh':
			default:
				$this->SetDefaultParam();
				$res = $this->GetRows();
				break;
		}
		
		if( ! $this->exportMode ){
			$this->SetNavi();
			$this->SetHeaders();
			$this->SetSESSION();
		}
		
		if($res){
		
			return TRUE;
			
		}else{
			return FALSE;
		}
	}

	//DefaultParam
	function SetDefaultParam()
	{

		if(isset($_SESSION[$this->table]['startRow'])){
			$this->startRow = $_SESSION[$this->table]['startRow'];
		}else{
			$this->startRow = 0;
		}
		if(isset($_SESSION[$this->table]['currentPage'])){
			$this->currentPage = $_SESSION[$this->table]['currentPage'];
		}else{
			$this->currentPage = 1;
		}
		if(isset($_SESSION[$this->table]['arr_search'])){
			$this->arr_search = $_SESSION[$this->table]['arr_search'];
		}else{
			$this->arr_search = array('period'=>'3', 'column'=>'', 'word'=>array());
		}
		if(isset($_SESSION[$this->table]['searchSwitchStatus'])){
			$this->searchSwitchStatus = $_SESSION[$this->table]['searchSwitchStatus'];
		}else{
			$this->searchSwitchStatus = 'OFF';
		}
		if(isset($_SESSION[$this->table]['searchSql'])){
			$this->searchSql = $_SESSION[$this->table]['searchSql'];
		}else{
			$this->searchSql = '';
		}
		
		if(isset($_SESSION[$this->table]['sortColumn'])){
			$this->sortColumn = $_SESSION[$this->table]['sortColumn'];
		}else{
			$this->sortColumn = apply_filters( 'usces_filter_item_class_sortColumn', 'post.ID');
		}

		if(isset($_SESSION[$this->table]['sortSwitchs'])){
			$this->sortSwitchs = $_SESSION[$this->table]['sortSwitchs'];
		}else{
			foreach($this->columns as $value ){
				$this->sortSwitchs[$value] = 'ASC';
			}
			$this->sortSwitchs[$this->sortColumn] = apply_filters( 'usces_filter_item_class_sortSwitchs', 'DESC');
		}

		
		$this->SetTotalRow();

	}
	
	function SetParam()
	{
		$this->startRow = ($this->currentPage-1) * $this->maxRow;
	}
	
	function SetParamByQuery()
	{
	
		if(isset($_REQUEST['changePage'])){
		
			$this->action = 'changePage';
			$this->currentPage = (int)$_REQUEST['changePage'];
			
			$this->sortColumn = $_SESSION[$this->table]['sortColumn'];
			$this->sortSwitchs = $_SESSION[$this->table]['sortSwitchs'];
			$this->userHeaderNames = $_SESSION[$this->table]['userHeaderNames'];
			$this->searchSwitchStatus = $_SESSION[$this->table]['searchSwitchStatus'];
			$this->searchSql = $_SESSION[$this->table]['searchSql'];
			$this->arr_search = $_SESSION[$this->table]['arr_search'];
			$this->totalRow = $_SESSION[$this->table]['totalRow'];
			$this->selectedRow = $_SESSION[$this->table]['selectedRow'];
			
		}else if(isset($_REQUEST['changeSort'])){
		
			$this->action = 'changeSort';
			$this->sortOldColumn = $this->sortColumn;
			$this->sortColumn = str_replace('(', '', $_REQUEST['changeSort']);
			$this->sortColumn = str_replace(',', '', $this->sortColumn);
			$this->sortSwitchs = $_SESSION[$this->table]['sortSwitchs'];
			$this->sortSwitchs[$this->sortColumn] = str_replace('(', '', $_REQUEST['switch']);
			$this->sortSwitchs[$this->sortColumn] = str_replace(',', '', $this->sortSwitchs[$this->sortColumn]);
			
			$this->currentPage = $_SESSION[$this->table]['currentPage'];
			$this->userHeaderNames = $_SESSION[$this->table]['userHeaderNames'];
			$this->searchSql = $_SESSION[$this->table]['searchSql'];
			$this->arr_search = $_SESSION[$this->table]['arr_search'];
			$this->searchSwitchStatus = $_SESSION[$this->table]['searchSwitchStatus'];
			$this->totalRow = $_SESSION[$this->table]['totalRow'];
			$this->selectedRow = $_SESSION[$this->table]['selectedRow'];
			
		} else if(isset($_REQUEST['searchIn'])){
		
			$this->action = 'searchIn';
			$this->arr_search['column'] = isset($_REQUEST['search']['column']) ? str_replace(',', '', $_REQUEST['search']['column']) : '';
			$this->arr_search['word'] = isset($_REQUEST['search']['word']) ? $_REQUEST['search']['word'] : '';
			$this->arr_search['period'] = isset($_REQUEST['search']['period']) ? (int)$_REQUEST['search']['period'] : '';
			$this->searchSwitchStatus = 'ON';
			
			$this->currentPage = 1;
			$this->sortColumn = $_SESSION[$this->table]['sortColumn'];
			$this->sortSwitchs = $_SESSION[$this->table]['sortSwitchs'];
			$this->userHeaderNames = $_SESSION[$this->table]['userHeaderNames'];
			$this->totalRow = $_SESSION[$this->table]['totalRow'];

		}else if(isset($_REQUEST['searchOut'])){
		
			$this->action = 'searchOut';
			$this->arr_search['column'] = '';
			$this->arr_search['word'] = '';
			$this->arr_search['period'] = $_SESSION[$this->table]['arr_search']['period'];
			$this->searchSwitchStatus = 'OFF';
			
			$this->currentPage = 1;
			$this->sortColumn = $_SESSION[$this->table]['sortColumn'];
			$this->sortSwitchs = $_SESSION[$this->table]['sortSwitchs'];
			$this->userHeaderNames = $_SESSION[$this->table]['userHeaderNames'];
			$this->totalRow = $_SESSION[$this->table]['totalRow'];
			
			
		}else if(isset($_REQUEST['refresh'])){
		
			$this->action = 'refresh';

			$this->currentPage = $_SESSION[$this->table]['currentPage'];
			$this->sortColumn = $_SESSION[$this->table]['sortColumn'];
			$this->sortSwitchs = $_SESSION[$this->table]['sortSwitchs'];
			$this->userHeaderNames = $_SESSION[$this->table]['userHeaderNames'];
			$this->searchSql = $_SESSION[$this->table]['searchSql'];
			$this->arr_search = $_SESSION[$this->table]['arr_search'];
			$this->searchSwitchStatus = $_SESSION[$this->table]['searchSwitchStatus'];
			$this->totalRow = $_SESSION[$this->table]['totalRow'];
			$this->selectedRow = $_SESSION[$this->table]['selectedRow'];

		}else if(isset($_REQUEST['collective'])){
		
			$this->action = 'collective_' . str_replace(',', '', $_POST['allchange']['column']);
			$this->currentPage = $_SESSION[$this->table]['currentPage'];
			$this->sortColumn = $_SESSION[$this->table]['sortColumn'];
			$this->sortSwitchs = $_SESSION[$this->table]['sortSwitchs'];
			$this->userHeaderNames = $_SESSION[$this->table]['userHeaderNames'];
			$this->searchSql = $_SESSION[$this->table]['searchSql'];
			$this->arr_search = $_SESSION[$this->table]['arr_search'];
			$this->searchSwitchStatus = $_SESSION[$this->table]['searchSwitchStatus'];
			$this->totalRow = $_SESSION[$this->table]['totalRow'];
			$this->selectedRow = $_SESSION[$this->table]['selectedRow'];

		}else{
		
			$this->action = 'default';
		}
	}
	
	//GetRows
	function GetRows()
	{
		global $wpdb;
		$where = $this->GetWhere();
		$order = ' ORDER BY ' . $this->sortColumn . ' ' . $this->sortSwitchs[$this->sortColumn];
		
		if(USCES_MYSQL_VERSION >= 5){
			if( $this->exportMode ){
				$query = $wpdb->prepare("SELECT post.ID, mc.meta_value AS item_code, mn.meta_value AS item_name 
						FROM {$this->table} AS post 
						LEFT JOIN $wpdb->postmeta AS mc ON post.ID = mc.post_id AND %s = mc.meta_key 
						LEFT JOIN $wpdb->postmeta AS mn ON post.ID = mn.post_id AND %s = mn.meta_key 
						LEFT JOIN $wpdb->postmeta AS meta ON post.ID = meta.post_id AND %s = meta.meta_key 
						LEFT JOIN $wpdb->term_relationships AS tr ON post.ID = tr.object_id 
						LEFT JOIN $wpdb->term_taxonomy AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id "
						, '_itemCode', '_itemName', '_isku_');
			}else{		

				$query = $wpdb->prepare("SELECT post.ID, mc.meta_value AS item_code, mn.meta_value AS item_name 
						FROM {$this->table} AS post 
						LEFT JOIN $wpdb->postmeta AS mc ON post.ID = mc.post_id AND %s = mc.meta_key 
						LEFT JOIN $wpdb->postmeta AS mn ON post.ID = mn.post_id AND %s = mn.meta_key 
						LEFT JOIN $wpdb->postmeta AS meta ON post.ID = meta.post_id AND %s = meta.meta_key 
						LEFT JOIN $wpdb->term_relationships AS tr ON post.ID = tr.object_id 
						LEFT JOIN $wpdb->term_taxonomy AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id "
						, '_itemCode', '_itemName', '_isku_');

			}
		} else {
			if( $this->exportMode ){

			}else{
				$query = $wpdb->prepare("SELECT 
						'item_code' AS item_code, 
						post.post_title, 
						meta.meta_key AS sku_key, meta.meta_value AS sku_value, te.name AS category, post.post_status, 
						CASE post.post_status 
							WHEN 'publish' THEN '" . __('Published', 'usces') . "' 
							WHEN 'future' THEN '" . __('Scheduled', 'usces') . "' 
							WHEN 'draft' THEN '" . __('Draft', 'usces') . "' 
							WHEN 'pending' THEN '" . __('Pending Review', 'usces') . "' 
							WHEN 'trash' THEN '" . __('Trash', 'usces') . "' 
							ELSE '" . __('Closed', 'usces') . "' 
						END AS display_status, 
						post.post_type, post.post_mime_type, post.ID 
						FROM {$this->table} AS post 
						LEFT JOIN $wpdb->postmeta AS meta ON post.ID = meta.post_id AND meta.meta_key = %s 
						LEFT JOIN $wpdb->term_relationships AS tr ON tr.object_id = post.ID 
						LEFT JOIN $wpdb->term_taxonomy AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id 
						LEFT JOIN $wpdb->terms AS te ON te.term_id = tt.term_id ",
						'_isku_');
			}
		}

		$query .= $where . $order;// . $limit;

		$rows = $wpdb->get_results($query, ARRAY_A);
		$this->selectedRow = ( $rows && is_array( $rows ) ) ? count( $rows ) : 0;
		if($this->pageLimit == 'off') {
			$this->rows = $rows;
		} else {
			$this->rows = array_slice((array)$rows, $this->startRow, $this->maxRow);
		}

		return $this->rows;
	}
	
	function SetTotalRow()
	{
		global $wpdb;
		$query = "SELECT COUNT(ID) AS ct FROM {$this->table} WHERE post_mime_type = 'item' AND post_type = 'post' AND post_status <> 'trash'";
		$res = $wpdb->get_var($query);
		$this->totalRow = $res;
	}
	
	function GetWhere()
	{
		if( $this->searchSql != '' ){
			if( 'display_status' == $this->arr_search['column'] )
				$str = "WHERE post.post_mime_type = 'item' AND post.post_type = 'post' AND " . $this->searchSql . " GROUP BY post.ID";
			else
				$str = "WHERE post.post_mime_type = 'item' AND post.post_type = 'post' AND post.post_status <> 'trash' AND " . $this->searchSql . " GROUP BY post.ID";
		}else{
			$str = "WHERE post.post_mime_type = 'item' AND post.post_type = 'post' AND post.post_status <> 'trash' GROUP BY post.ID";
		}
		return $str;
	}
	
	function SearchIn()
	{
		switch ($this->arr_search['column']) {
			case 'item_code':
				$column = 'mc.meta_value';
				$this->searchSql =  $column . ' LIKE '."'%" . esc_sql($this->arr_search['word']['item_code']) . "%'";
				break;
			case 'item_name':
				$column = 'mn.meta_value';
				$this->searchSql = $column . ' LIKE '."'%" . esc_sql($this->arr_search['word']['item_name']) . "%'";
				break;
			case 'post_title':
				$column = 'post.post_title';
				$this->searchSql = $column . ' LIKE '."'%" . esc_sql($this->arr_search['word']['post_title']) . "%'";
				break;
			case 'zaiko_num':
				$column = 'meta.meta_value';
				$this->searchSql = '(' . $column . ' LIKE '."'%" . esc_sql('"stocknum";i:0') . "%' OR " . $column . ' LIKE '."'%" . esc_sql('"stocknum";s:1:"0"')."%')";
				break;
			case 'zaiko':
				$column = 'meta.meta_value';
				$this->searchSql = '(' . $column . ' LIKE '."'%" . esc_sql('"stock";i:'.$this->arr_search['word']['zaiko']) . "%' OR " . $column . ' LIKE '."'%" . esc_sql('"stock";s:1:"'.$this->arr_search['word']['zaiko'].'"') . "%')";
				break;
			case 'category':
				$column = 'tt.term_id';
				$this->searchSql = $column . " = '" . esc_sql($this->arr_search['word']['category']) . "'";
				break;
			case 'display_status':
				$column = 'post_status';
				$this->searchSql = $column . " = '" . esc_sql($this->arr_search['word']['display_status']) . "'";
				break;
		}
	}

	function SearchOut()
	{
		$this->searchSql = '';
	}

	function SetNavi()
	{
		
		$this->lastPage = ceil($this->selectedRow / $this->maxRow);
		$this->previousPage = ($this->currentPage - 1 == 0) ? 1 : $this->currentPage - 1;
		$this->nextPage = ($this->currentPage + 1 > $this->lastPage) ? $this->lastPage : $this->currentPage + 1;
		$box = array();

		for($i=0; $i<$this->naviMaxButton; $i++){
			if($i > $this->lastPage-1) break;
			if($this->lastPage <= $this->naviMaxButton) {
				$box[] = $i+1;
			}else{
				if($this->currentPage <= 6) {
					$label = $i + 1;
					$box[] = $label;
				}else{
					$label = $i + 1 + $this->currentPage - 6;
					$box[] = $label;
					if($label == $this->lastPage) break;
				}
			}
		}
		
		$html = '';
		$html .= '<ul class="clearfix">'."\n";
		$html .= '<li class="rowsnum">' . $this->selectedRow . ' / ' . $this->totalRow . ' '.__('cases', 'usces').'' . "\n";
		if(($this->currentPage == 1) || ($this->selectedRow == 0)){
			$html .= '<li class="navigationStr">first&lt;&lt;</li>' . "\n";
			$html .= '<li class="navigationStr">prev&lt;</li>'."\n";
		}else{
			$html .= '<li class="navigationStr"><a href="' . site_url() . '/wp-admin/admin.php?page=usces_itemedit&changePage=1">first&lt;&lt;</a></li>' . "\n";
			$html .= '<li class="navigationStr"><a href="' . site_url() . '/wp-admin/admin.php?page=usces_itemedit&changePage=' . $this->previousPage . '">prev&lt;</a></li>'."\n";
		}
		if($this->selectedRow > 0) {
			$box_count = count( $box );
			for($i=0; $i<$box_count; $i++){
				if($box[$i] == $this->currentPage){
					$html .= '<li class="navigationButtonSelected">' . $box[$i] . '</li>'."\n";
				}else{
					$html .= '<li class="navigationButton"><a href="' . site_url() . '/wp-admin/admin.php?page=usces_itemedit&changePage=' . $box[$i] . '">' . $box[$i] . '</a></li>'."\n";
				}
			}
		}
		if(($this->currentPage == $this->lastPage) || ($this->selectedRow == 0)){
			$html .= '<li class="navigationStr">&gt;next</li>'."\n";
			$html .= '<li class="navigationStr">&gt;&gt;last</li>'."\n";
		}else{
			$html .= '<li class="navigationStr"><a href="' . site_url() . '/wp-admin/admin.php?page=usces_itemedit&changePage=' . $this->nextPage . '">&gt;next</a></li>'."\n";
			$html .= '<li class="navigationStr"><a href="' . site_url() . '/wp-admin/admin.php?page=usces_itemedit&changePage=' . $this->lastPage . '">&gt;&gt;last</a></li>'."\n";
		}
		if($this->searchSwitchStatus == 'OFF'){
			$html .= '<li class="navigationStr"><a style="cursor:pointer;" id="searchVisiLink">' . __('Show the Operation field', 'usces') . '</a>'."\n";
		}else{
			$html .= '<li class="navigationStr"><a style="cursor:pointer;" id="searchVisiLink">' . __('Hide the Operation field', 'usces') . '</a>'."\n";
		}

		$html .= '<li class="refresh"><a href="' . site_url() . '/wp-admin/admin.php?page=usces_itemedit&refresh">' . __('updates it to latest information', 'usces') . '</a></li>' . "\n";
		$html .= '</ul>'."\n";

		$this->dataTableNavigation = $html;
	}
	
	function SetSESSION()
	{
	
		$_SESSION[$this->table]['startRow'] = $this->startRow;		//表示開始行番号
		$_SESSION[$this->table]['sortColumn'] = $this->sortColumn;	//現在ソート中のフィールド
		$_SESSION[$this->table]['totalRow'] = $this->totalRow;		//全行数
		$_SESSION[$this->table]['selectedRow'] = $this->selectedRow;	//絞り込まれた行数
		$_SESSION[$this->table]['currentPage'] = $this->currentPage;	//現在のページNo
		$_SESSION[$this->table]['previousPage'] = $this->previousPage;	//前のページNo
		$_SESSION[$this->table]['nextPage'] = $this->nextPage;		//次のページNo
		$_SESSION[$this->table]['lastPage'] = $this->lastPage;		//最終ページNo
		$_SESSION[$this->table]['userHeaderNames'] = $this->userHeaderNames;//全てのフィールド
		$_SESSION[$this->table]['headers'] = $this->headers;//表示するヘッダ文字列
		$_SESSION[$this->table]['rows'] = $this->rows;			//表示する行オブジェクト
		$_SESSION[$this->table]['sortSwitchs'] = $this->sortSwitchs;	//各フィールド毎の昇順降順スイッチ
		$_SESSION[$this->table]['dataTableNavigation'] = $this->dataTableNavigation;	
		$_SESSION[$this->table]['searchSql'] = $this->searchSql;
 		$_SESSION[$this->table]['arr_search'] = $this->arr_search;
		$_SESSION[$this->table]['searchSwitchStatus'] = $this->searchSwitchStatus;
	}
	
	function SetHeaders()
	{
		foreach ($this->columns as $key => $value){
			if($value == $this->sortColumn){
				if($this->sortSwitchs[$value] == 'ASC'){
					$str = __('[ASC]', 'usces');
					$switch = 'DESC';
				}else{
					$str = __('[DESC]', 'usces');
					$switch = 'ASC';
				}
				if( (USCES_MYSQL_VERSION >= 5 AND ($value == 'item_name' || $value == 'item_code')) || (USCES_MYSQL_VERSION < 5 AND $value == 'post_title') )
					$this->headers[$value] = '<a href="' . site_url() . '/wp-admin/admin.php?page=usces_itemedit&changeSort=' . $value . '&switch=' . $switch . '"><span class="sortcolumn">' . $key . ' ' . $str . '</span></a>';
				else
					$this->headers[$value] = '<span class="sortcolumn">' . $key . '</span>';
			}else{
				$switch = $this->sortSwitchs[$value];
				if( (USCES_MYSQL_VERSION >= 5 AND ($value == 'item_name' || $value == 'item_code')) || (USCES_MYSQL_VERSION < 5 AND $value == 'post_title') )
					$this->headers[$value] = '<a href="' . site_url() . '/wp-admin/admin.php?page=usces_itemedit&changeSort=' . $value . '&switch=' . $switch . '"><span>' . $key . '</span></a>';
				else
					$this->headers[$value] = '<span class="sortcolumn">' . $key . '</span>';
			}
		}
	}
	
	function GetSearchs()
	{
		return $this->arr_search;
	}
	
	function GetListheaders()
	{
		return $this->headers;
	}
	
	function GetDataTableNavigation()
	{
		return $this->dataTableNavigation;
	}
	
	function set_action_status($status, $message)
	{
		$this->action_status = $status;
		$this->action_message = $message;
	}
	function get_action_status()
	{
		return $this->action_status;
	}
	function get_action_message()
	{
		return $this->action_message;
	}
}

