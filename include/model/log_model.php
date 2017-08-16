<?php
/**
 * 文章、页面管理
 *
 * @copyright (c) Emlog All Rights Reserved
 */

class Log_Model {

	private $db;

	function __construct() {
		$this->db = Database::getInstance();
	}

	/**
	 * 添加文章、页面
	 *
	 * @param array $logData
	 * @return int
	 */
function addlog($logData) {
    $kItem = array();
    $dItem = array();
    foreach ($logData as $key => $data) {
        $kItem[] = $key;
        $dItem[] = $data;
    }
    $field = implode(',', $kItem);
    $values = "'" . implode("','", $dItem) . "'";

    $gidarr[0]='0';
    $res = $this->db->query("SELECT gid From  " . DB_PREFIX . "blog ORDER BY gid ASC");
    while ($row = $this->db->fetch_array($res)) {
        $gidarr[] = $row['gid'];
    }
    foreach($gidarr as $key=>$val){
        if($key!=$val){
            $field = 'gid,'.$field;
            $values = "'".$key."',".$values;
            break;
        }
    }

    $this->db->query("INSERT INTO " . DB_PREFIX . "blog ($field) VALUES ($values)");
    $logid = $this->db->insert_id();
    return $logid;
}

	/**
	 * 更新文章内容
	 *
	 * @param array $logData
	 * @param int $blogId
	 */
	function updateLog($logData, $blogId) {
		$author = ROLE == ROLE_ADMIN ? '' : 'and author=' . UID;
		$Item = array();
		foreach ($logData as $key => $data) {
			$Item[] = "$key='$data'";
		}
		$upStr = implode(',', $Item);
		$this->db->query("UPDATE " . DB_PREFIX . "blog SET $upStr WHERE gid=$blogId $author");
	}

	/**
	 * 获取指定条件的文章条数
	 *
	 * @param int $spot 0:前台 1:后台
	 * @param string $hide
	 * @param string $condition
	 * @param string $type
	 * @return int
	 */
	function getLogNum($hide = 'n', $condition = '', $type = 'blog', $spot = 0) {
		$hide_state = $hide ? "and hide='$hide'" : '';

		if ($spot == 0) {
			$author = '';
		}else {
			$author = ROLE == ROLE_ADMIN ? '' : 'and author=' . UID;
		}

        $data = $this->db->once_fetch_array("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "blog WHERE type='$type' $hide_state $author $condition");
		return $data['total'];
	}

	/**
	 * 后台获取单篇文章
	 */
	function getOneLogForAdmin($blogId) {

		$author = ROLE == ROLE_ADMIN ? '' : 'AND author=' . UID;
		$sql = "SELECT * FROM " . DB_PREFIX . "blog WHERE gid=$blogId $author";
		$res = $this->db->query($sql);
		if ($this->db->affected_rows() < 1) {
			emMsg(langs('no_permission'), './');
		}
		$row = $this->db->fetch_array($res);
		if ($row) {

			$row['title'] = htmlspecialchars($row['title']);
			$row['content'] = htmlspecialchars($row['content']);
			$row['excerpt'] = htmlspecialchars($row['excerpt']);
			$row['thumbs'] = htmlspecialchars($row['thumbs']);
			$row['password'] = htmlspecialchars($row['password']);
            $row['template'] = !empty($row['template']) ? htmlspecialchars(trim($row['template'])) : 'page';
			$logData = $row;
			return $logData;
		} else {
			return false;
		}
	}

	/**
	 * 前台获取单篇文章
	 */
	function getOneLogForHome($blogId) {
		$sql = "SELECT * FROM " . DB_PREFIX . "blog WHERE gid=$blogId AND hide='n' AND checked='y'";
		$res = $this->db->query($sql);
		$row = $this->db->fetch_array($res);
		if ($row) {
			$logData = array(
				'log_title' => htmlspecialchars($row['title']),
				'timestamp' => $row['date'],
				'date' => $row['date'],
				'thumbs'=>$row['thumbs'],
				'logid' => intval($row['gid']),
				'sortid' => intval($row['sortid']),
				'type' => $row['type'],
				'author' => $row['author'],
				'log_content' => rmBreak($row['content']),
				'views' => intval($row['views']),
				'comnum' => intval($row['comnum']),
				'top' => $row['top'],
                'sortop' => $row['sortop'],
				'attnum' => intval($row['attnum']),
				'allow_remark' => Option::get('iscomment') == 'y' ? $row['allow_remark'] : 'n',
				'password' => $row['password'],
                'template' => $row['template'],
				);
			return $logData;
		} else {
			return false;
		}
	}
	

	/**
	 * 后台获取文章列表
	 *
	 * @param string $condition
	 * @param string $hide_state
	 * @param int $page
	 * @param string $type
	 * @return array
	 */
	function getLogsForAdmin($condition = '', $hide_state = '', $page = 1, $type = 'blog') {
		$perpage_num = Option::get('admin_perpage_num');
		$start_limit = !empty($page) ? ($page - 1) * $perpage_num : 0;
		$author = ROLE == ROLE_ADMIN ? '' : 'and author=' . UID;
		$hide_state = $hide_state ? "and hide='$hide_state'" : '';
		$limit = "LIMIT $start_limit, " . $perpage_num;
		$sql = "SELECT * FROM " . DB_PREFIX . "blog WHERE type='$type' $author $hide_state $condition $limit";
		$res = $this->db->query($sql);
		$logs = array();
		while ($row = $this->db->fetch_array($res)) {
			$row['date']	= date("Y-m-d H:i", $row['date']);
			$row['title'] 	= !empty($row['title']) ? htmlspecialchars($row['title']) :langs('no_title');
			//$row['gid'] 	= $row['gid'];
			//$row['comnum'] 	= $row['comnum'];
			//$row['top'] 	= $row['top'];
			//$row['attnum'] 	= $row['attnum'];
			$logs[] = $row;
		}
		return $logs;
	}

	/**
	 * 前台获取文章列表
	 *
	 * @param string $condition
	 * @param int $page
	 * @param int $perPageNum
	 * @return array
	 */
	function getLogsForHome($condition = '', $page = 1, $perPageNum) {
		$start_limit = !empty($page) ? ($page - 1) * $perPageNum : 0;
		$limit = $perPageNum ? "LIMIT $start_limit, $perPageNum" : '';
		$sql = "SELECT * FROM " . DB_PREFIX . "blog WHERE type='blog' and hide='n' and checked='y' $condition $limit";
		$res = $this->db->query($sql);
		$logs = array();
		while ($row = $this->db->fetch_array($res)) {
			
			$row['log_title'] = htmlspecialchars(trim($row['title']));
			$row['log_url'] = Url::log($row['gid']);
			$row['logid'] = $row['gid'];
			$cookiePassword = isset($_COOKIE['em_logpwd_' . $row['gid']]) ? addslashes(trim($_COOKIE['em_logpwd_' . $row['gid']])) : '';
			if (!empty($row['password']) && $cookiePassword != $row['password']) {
				$row['excerpt'] = '<p>['.langs('post_protected_by_password_click_title').']</p>';
			} else {
				if (!empty($row['excerpt'])) {
					$row['excerpt'] .= '<p class="readmore"><a href="' . Url::log($row['logid']) . '">'.langs('read_more').'</a></p>';
				}
			}
			$row['thumbs']=$row['thumbs'];
			$row['log_description'] = empty($row['excerpt']) ? breakLog($row['content'], $row['gid']) : $row['excerpt'];
			$row['attachment'] = '';
			$row['tag'] = '';
            $row['tbcount'] = 0;//兼容未删除引用的模板
			$logs[] = $row;
		}
		return $logs;
	}

	/**
	 * 获取全部页面列表
	 *
	 */
	function getAllPageList() {
		$sql = "SELECT * FROM " . DB_PREFIX . "blog WHERE type='page'";
		$res = $this->db->query($sql);
		$pages = array();
		while ($row = $this->db->fetch_array($res)) {
			$row['date']	= date("Y-m-d H:i", $row['date']);
			$row['title'] 	= !empty($row['title']) ? htmlspecialchars($row['title']) :langs('no_title');
			//$row['gid'] 	= $row['gid'];
			//$row['comnum'] 	= $row['comnum'];
			//$row['top'] 	= $row['top'];
			//$row['attnum'] 	= $row['attnum'];
			$pages[] = $row;
		}
		return $pages;
	}

	/**
	 * 删除文章
	 *
	 * @param int $blogId
	 */
	function deleteLog($blogId) {
		$author = ROLE == ROLE_ADMIN ? '' : 'and author=' . UID;
		$this->db->query("DELETE FROM " . DB_PREFIX . "blog where gid=$blogId $author");
		if ($this->db->affected_rows() < 1) {
			emMsg(langs('no_permission'), './');
		}
		// 评论
		$this->db->query("DELETE FROM " . DB_PREFIX . "comment where gid=$blogId");
		// 标签
		$this->db->query("UPDATE " . DB_PREFIX . "tag SET gid= REPLACE(gid,',$blogId,',',') WHERE gid LIKE '%" . $blogId . "%' ");
		$this->db->query("DELETE FROM " . DB_PREFIX . "tag WHERE gid=',' ");
		// 附件
		$query = $this->db->query("select filepath from " . DB_PREFIX . "attachment where blogid=$blogId ");
		while ($attach = $this->db->fetch_array($query)) {
			if (file_exists($attach['filepath'])) {
				$fpath = str_replace('thum-', '', $attach['filepath']);
				if ($fpath != $attach['filepath']) {
					@unlink($fpath);
				}
				@unlink($attach['filepath']);
			}
		}
		$this->db->query("DELETE FROM " . DB_PREFIX . "attachment where blogid=$blogId");
	}

	/**
	 * 隐藏/显示文章
	 *
	 * @param int $blogId
	 * @param string $state
	 */
	function hideSwitch($blogId, $state) {
        $author = ROLE == ROLE_ADMIN ? '' : 'and author=' . UID;
		$this->db->query("UPDATE " . DB_PREFIX . "blog SET hide='$state' WHERE gid=$blogId $author");
		$this->db->query("UPDATE " . DB_PREFIX . "comment SET hide='$state' WHERE gid=$blogId");
		$Comment_Model = new Comment_Model();
		$Comment_Model->updateCommentNum($blogId);
	}

    /**
	 * 审核/驳回作者文章
	 *
	 * @param int $blogId
	 * @param string $state
	 */
	function checkSwitch($blogId, $state) {
		$this->db->query("UPDATE " . DB_PREFIX . "blog SET checked='$state' WHERE gid=$blogId");
        $state = $state == 'y' ? 'n' : 'y';
		$this->db->query("UPDATE " . DB_PREFIX . "comment SET hide='$state' WHERE gid=$blogId");
		$Comment_Model = new Comment_Model();
		$Comment_Model->updateCommentNum($blogId);
	}

	/**
	 * 获取文章发布时间
	 *
	 * @param int $timezone
	 * @param string $postDate
	 * @param string $oldDate
	 * @return date
	 */
	function postDate($timezone = 8, $postDate = null, $oldDate = null) {
		$timezone = Option::get('timezone');
		$localtime = time();
		$logDate = $oldDate ? $oldDate : $localtime;
		$unixPostDate = '';
		if ($postDate) {
			$unixPostDate = emStrtotime($postDate);
			if ($unixPostDate === false) {
				$unixPostDate = $logDate;
			}
		} else {
			return $localtime;
		}
		return $unixPostDate;
	}

	/**
	 * 增加阅读次数
	 *
	 * @param int $blogId
	 */
	function updateViewCount($blogId) {
	if(ROLE != 'admin' && ROLE != 'writer'){
		$this->db->query("UPDATE " . DB_PREFIX . "blog SET views=views+1 WHERE gid=$blogId");
		}
	}

	/**
	 * 判断是否重复发文
	 */
	function isRepeatPost($title, $time) {
		$sql = "SELECT gid FROM " . DB_PREFIX . "blog WHERE title='$title' and date='$time' LIMIT 1";
		$res = $this->db->query($sql);
		$row = $this->db->fetch_array($res);
		return isset($row['gid']) ? (int)$row['gid'] : false;
	}

	/**
	 * 获取相邻文章
	 *
	 * @param int $date unix时间戳
	 * @return array
	 */
	function neighborLog($date) {
		$neighborlog = array();
		$neighborlog['nextLog'] = $this->db->once_fetch_array("SELECT title,gid FROM " . DB_PREFIX . "blog WHERE date < $date and hide = 'n' and checked='y' and type='blog' ORDER BY date DESC LIMIT 1");
		$neighborlog['prevLog'] = $this->db->once_fetch_array("SELECT title,gid FROM " . DB_PREFIX . "blog WHERE date > $date and hide = 'n' and checked='y' and type='blog' ORDER BY date LIMIT 1");
		if ($neighborlog['nextLog']) {
			$neighborlog['nextLog']['title'] = htmlspecialchars($neighborlog['nextLog']['title']);
		}
		if ($neighborlog['prevLog']) {
			$neighborlog['prevLog']['title'] = htmlspecialchars($neighborlog['prevLog']['title']);
		}
		return $neighborlog;
	}

	/**
	 * 随机获取指定数量文章
	 */
	function getRandLog($num) {
        global $CACHE;
        $sta_cache = $CACHE->readCache('sta');
        $lognum = $sta_cache['lognum'];
        $start = $lognum > $num ? mt_rand(0, $lognum - $num): 0;
		$sql = "SELECT gid,title FROM " . DB_PREFIX . "blog WHERE hide='n' and checked='y' and type='blog' LIMIT $start, $num";
		$res = $this->db->query($sql);
		$logs = array();
		while ($row = $this->db->fetch_array($res)) {
			$row['gid'] = intval($row['gid']);
			$row['title'] = htmlspecialchars($row['title']);
			$logs[] = $row;
		}
		return $logs;
	}

	/**
	 * 获取热门文章
	 */
	function getHotLog($num) {
		$sql = "SELECT gid,title FROM " . DB_PREFIX . "blog WHERE hide='n' and checked='y' and type='blog' ORDER BY views DESC, comnum DESC LIMIT 0, $num";
		$res = $this->db->query($sql);
		$logs = array();
		while ($row = $this->db->fetch_array($res)) {
			$row['gid'] = intval($row['gid']);
			$row['title'] = htmlspecialchars($row['title']);
			$logs[] = $row;
		}
		return $logs;
	}

	/**
	 * 处理文章别名，防止别名重复
	 *
	 * @param string $alias
	 * @param array $logalias_cache
	 * @param int $logid
	 */
	function checkAlias($alias, $logalias_cache, $logid) {
		static $i=2;
		$key = array_search($alias, $logalias_cache);
		if (false !== $key && $key != $logid) {
			if($i == 2) {
				$alias .= '-'.$i;
			}else{
				$alias = preg_replace("|(.*)-([\d]+)|", "$1-{$i}", $alias);
			}
			$i++;
			return $this->checkAlias($alias, $logalias_cache, $logid);
		}
		return $alias;
	}

	/**
	 * 加密文章访问验证
	 *
	 * @param string $pwd
	 * @param string $pwd2
	 */
	function authPassword($postPwd, $cookiePwd, $logPwd, $logid) {
		$url = BLOG_URL;
		$pwd = $cookiePwd ? $cookiePwd : $postPwd;
		if ($pwd !== addslashes($logPwd)) {
	       $msg_ps = langs('msg_pw');
		$page_pass = langs('page_password_enter');
              $submit_pass = langs('submit_password');
              $back = langs('back_home');
			echo <<<EOT
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<title>{$msg_ps}</title>
<link href="./admin/views/vendors/bower_components/jasny-bootstrap/dist/css/jasny-bootstrap.min.css" rel="stylesheet" type="text/css"/>
<link href="./admin/views/dist/css/style.css" rel="stylesheet" type="text/css">
</head>
</head>
<body>
<div class="preloader-it">
<div class="la-anim-1"></div>
</div>
<div class="wrapper pa-0">
<div class="page-wrapper pa-0 ma-0 auth-page">
<div class="container-fluid">
<div class="table-struct full-width full-height">
<div class="table-cell vertical-align-middle auth-form-wrap">
<div class="auth-form  ml-auto mr-auto no-float">
<div class="row">
<div class="col-sm-12 col-xs-12">
<div class="sp-logo-wrap text-center pa-0 mb-30">
<a href="./">
<img class="brand-img" src="./admin/views/dist/img/logo.png" alt="brand" style="width:24px;height:24px">
<span class="brand-text">EM<span style="color:red">LOG</span></span><sup>6.0</sup>
</a>
</div>
<div class="form-wrap">
<form action="" method="post">
<div class="form-group text-center">
 <h3 class="mt-10 txt-dark">{$page_pass}</h3>
</div>
<div class="form-group">
<input type="password" name="logpwd" class="form-control" required="" placeholder="password">
</div>
<div class="form-group text-center">
<button type="submit" class="btn btn-info btn-rounded">{$submit_pass}</button>
</div>
<div class="form-group mb-0 text-center">
<a href="$url" class="inline-block txt-primary">{$back}</a>
</div>
</form>
</div>
</div>	
</div>
</div>
</div>
</div>
</div>
</div>
</div>
<script type="text/javascript" src="./admin/views/js/jquery.js"></script>
<script src="./admin/views/vendors/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="./admin/views/vendors/bower_components/jasny-bootstrap/dist/js/jasny-bootstrap.min.js"></script>
<script src="./admin/views/dist/js/jquery.slimscroll.js"></script>
<script src="./admin/views/dist/js/init.js"></script>
</body>
</html>
EOT;
if ($cookiePwd) {
setcookie('em_logpwd_' . $logid, ' ', time() - 31536000);
}
exit;
} else {
setcookie('em_logpwd_' . $logid, $logPwd);
		}
	}
}
