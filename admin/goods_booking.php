<?php
//旺旺ecshop2012  禁止倒卖 一经发现停止任何服务
function get_bookinglist()
{
	$adminru = get_admin_ru_id();
	$ruCat = '';

	if (0 < $adminru['ru_id']) {
		$ruCat = ' and g.user_id = \'' . $adminru['ru_id'] . '\'';
	}

	$filter['keywords'] = empty($_REQUEST['keyword']) ? '' : trim($_REQUEST['keyword']);
	if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
		$filter['keywords'] = json_str_iconv($filter['keywords']);
	}

	$filter['seller_list'] = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;
	$filter['dispose'] = empty($_REQUEST['dispose']) ? 0 : intval($_REQUEST['dispose']);
	$filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'sort_order' : trim($_REQUEST['sort_by']);
	$filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
	$filter['rs_id'] = empty($_REQUEST['rs_id']) ? 0 : intval($_REQUEST['rs_id']);

	if (0 < $adminru['rs_id']) {
		$filter['rs_id'] = $adminru['rs_id'];
	}

	$sql = 'select user_id from ' . $GLOBALS['ecs']->table('merchants_shop_information') . ' where shoprz_brandName LIKE \'%' . mysql_like_quote($filter['keywords']) . '%\' OR shopNameSuffix LIKE \'%' . mysql_like_quote($filter['keywords']) . '%\'';
	$row = $GLOBALS['db']->getAll($sql);

	if (empty($row)) {
		$user_id = 0;
	}
	else {
		foreach ($row as $v) {
			$user[] = $v['user_id'];
		}

		$user_id = implode(',', $user);
	}

	$where_user = '';

	if ($user_id) {
		$where_user = ' OR (g.user_id in(' . $user_id . '))';
	}

	$where = !empty($_REQUEST['keyword']) ? ' AND (g.goods_name LIKE \'%' . mysql_like_quote($filter['keywords']) . '%\' ' . $where_user . ')' : '';
	$where .= !empty($_REQUEST['dispose']) ? ' AND bg.is_dispose = \'' . $filter['dispose'] . '\' ' : '';

	if ($filter['seller_list']) {
		$where .= ' AND g.user_id > 0 ';
	}
	else {
		$where .= ' AND g.user_id = 0 ';
	}

	$where .= $ruCat;
	$where .= get_rs_null_where('g.user_id', $filter['rs_id']);
	$sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['ecs']->table('booking_goods') . ' AS bg, ' . $GLOBALS['ecs']->table('goods') . ' AS g ' . ('WHERE bg.goods_id = g.goods_id ' . $where);
	$filter['record_count'] = $GLOBALS['db']->getOne($sql);
	$filter = page_and_size($filter);
	$sql = 'SELECT bg.rec_id, bg.link_man, g.goods_id, g.goods_name, g.user_id, bg.goods_number, bg.booking_time, bg.is_dispose ' . 'FROM ' . $GLOBALS['ecs']->table('booking_goods') . ' AS bg, ' . $GLOBALS['ecs']->table('goods') . ' AS g ' . ('WHERE bg.goods_id = g.goods_id ' . $where . ' ') . ('ORDER BY ' . $filter['sort_by'] . ' ' . $filter['sort_order'] . ' ') . 'LIMIT ' . $filter['start'] . (', ' . $filter['page_size']);
	$row = $GLOBALS['db']->getAll($sql);

	foreach ($row as $key => $val) {
		$row[$key]['booking_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['booking_time']);
		$row[$key]['user_name'] = $rows['shop_name'] = get_shop_name($val['user_id'], 1);
	}

	$filter['keywords'] = stripslashes($filter['keywords']);
	$arr = array('item' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);
	return $arr;
}

function get_booking_info($id)
{
	global $ecs;
	global $db;
	global $_CFG;
	global $_LANG;
	$sql = 'SELECT bg.rec_id, bg.user_id, IFNULL(u.user_name, \'' . $_LANG['guest_user'] . '\') AS user_name, ' . 'bg.link_man, g.goods_name, bg.goods_id, bg.goods_number, ' . 'bg.booking_time, bg.goods_desc,bg.dispose_user, bg.dispose_time, bg.email, ' . 'bg.tel, bg.dispose_note ,bg.dispose_user, bg.dispose_time,bg.is_dispose  ' . 'FROM ' . $ecs->table('booking_goods') . ' AS bg ' . 'LEFT JOIN ' . $ecs->table('goods') . ' AS g ON g.goods_id=bg.goods_id ' . 'LEFT JOIN ' . $ecs->table('users') . ' AS u ON u.user_id=bg.user_id ' . ('WHERE bg.rec_id =\'' . $id . '\'');
	$res = $db->GetRow($sql);
	$res['booking_time'] = local_date($_CFG['time_format'], $res['booking_time']);

	if (!empty($res['dispose_time'])) {
		$res['dispose_time'] = local_date($_CFG['time_format'], $res['dispose_time']);
	}

	return $res;
}

define('IN_ECS', true);
require dirname(__FILE__) . '/includes/init.php';
admin_priv('booking');

if ($_REQUEST['act'] == 'list_all') {
	$seller_order = isset($_REQUEST['seller_list']) && !empty($_REQUEST['seller_list']) ? 1 : 0;
	$smarty->assign('ur_here', $_LANG['list_all']);
	$smarty->assign('full_page', 1);
	$list = get_bookinglist();
	$adminru = get_admin_ru_id();
	$ruCat = '';

	if ($adminru['ru_id'] == 0) {
		$smarty->assign('priv_ru', 1);
	}
	else {
		$smarty->assign('priv_ru', 0);
	}

	$smarty->assign('common_tabs', array('info' => $seller_order, 'url' => 'goods_booking.php?act=list_all'));
	$smarty->assign('booking_list', $list['item']);
	$smarty->assign('filter', $list['filter']);
	$smarty->assign('record_count', $list['record_count']);
	$smarty->assign('page_count', $list['page_count']);
	$sort_flag = sort_flag($list['filter']);
	$smarty->assign($sort_flag['tag'], $sort_flag['img']);
	assign_query_info();
	$smarty->display('booking_list.dwt');
}

if ($_REQUEST['act'] == 'query') {
	$list = get_bookinglist();
	$adminru = get_admin_ru_id();
	$ruCat = '';

	if ($adminru['ru_id'] == 0) {
		$smarty->assign('priv_ru', 1);
	}
	else {
		$smarty->assign('priv_ru', 0);
	}

	$smarty->assign('booking_list', $list['item']);
	$smarty->assign('filter', $list['filter']);
	$smarty->assign('record_count', $list['record_count']);
	$smarty->assign('page_count', $list['page_count']);
	$sort_flag = sort_flag($list['filter']);
	$smarty->assign($sort_flag['tag'], $sort_flag['img']);
	make_json_result($smarty->fetch('booking_list.dwt'), '', array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}

if ($_REQUEST['act'] == 'remove') {
	check_authz_json('booking');
	$id = intval($_GET['id']);
	$db->query('DELETE FROM ' . $ecs->table('booking_goods') . (' WHERE rec_id=\'' . $id . '\''));
	$url = 'goods_booking.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);
	ecs_header('Location: ' . $url . "\n");
	exit();
}

if ($_REQUEST['act'] == 'detail') {
	$id = intval($_REQUEST['id']);
	$smarty->assign('send_fail', !empty($_REQUEST['send_ok']));
	$smarty->assign('booking', get_booking_info($id));
	$smarty->assign('ur_here', $_LANG['detail']);
	$smarty->assign('action_link', array('text' => $_LANG['06_undispose_booking'], 'href' => 'goods_booking.php?act=list_all'));
	$smarty->display('booking_info.dwt');
}

if ($_REQUEST['act'] == 'update') {
	admin_priv('booking');
	$dispose_note = !empty($_POST['dispose_note']) ? trim($_POST['dispose_note']) : '';
	$sql = 'UPDATE  ' . $ecs->table('booking_goods') . (' SET is_dispose=\'1\', dispose_note=\'' . $dispose_note . '\', ') . 'dispose_time=\'' . gmtime() . '\', dispose_user=\'' . $_SESSION['admin_name'] . '\'' . (' WHERE rec_id=\'' . $_REQUEST['rec_id'] . '\'');
	$db->query($sql);
	if (!empty($_POST['send_email_notice']) || isset($_POST['remail'])) {
		$sql = 'SELECT bg.email, bg.link_man, bg.goods_id, g.goods_name ' . 'FROM ' . $ecs->table('booking_goods') . ' AS bg, ' . $ecs->table('goods') . ' AS g ' . ('WHERE bg.goods_id = g.goods_id AND bg.rec_id=\'' . $_REQUEST['rec_id'] . '\'');
		$booking_info = $db->getRow($sql);
		$template = get_mail_template('goods_booking');
		$goods_link = $ecs->url() . 'goods.php?id=' . $booking_info['goods_id'];
		$smarty->assign('user_name', $booking_info['link_man']);
		$smarty->assign('goods_link', $goods_link);
		$smarty->assign('goods_name', $booking_info['goods_name']);
		$smarty->assign('dispose_note', $dispose_note);
		$smarty->assign('shop_name', '<a href=\'' . $ecs->url() . '\'>' . $_CFG['shop_name'] . '</a>');
		$smarty->assign('send_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));
		$content = $smarty->fetch('str:' . $template['template_content']);

		if (send_mail($booking_info['link_man'], $booking_info['email'], $template['template_subject'], $content, $template['is_html'])) {
			$send_ok = 0;
		}
		else {
			$send_ok = 1;
		}
	}

	ecs_header('Location: ?act=detail&id=' . $_REQUEST['rec_id'] . ('&send_ok=' . $send_ok . "\n"));
	exit();
}

?>
