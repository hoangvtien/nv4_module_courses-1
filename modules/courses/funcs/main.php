<?php

/**
 * @Project NUKEVIET 4.x
 * @Author VINADES.,JSC (contact@vinades.vn)
 * @Copyright (C) 2016 VINADES.,JSC. All rights reserved
 * @License GNU/GPL version 2 or any later version
 * @Createdate Fri, 08 Apr 2016 05:26:39 GMT
 */

if( ! defined( 'NV_IS_MOD_COURSES' ) ) die( 'Stop!!!' );



	$page_title = $module_info['custom_title'];
	$key_words = $module_info['keywords'];

	$contents = '';
	$cache_file = '';

	$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name;
	$base_url_rewrite = nv_url_rewrite($base_url, true);
	$page_url_rewrite = ($page > 1) ? nv_url_rewrite($base_url . '/page-' . $page, true) : $base_url_rewrite;
	$request_uri = $_SERVER['REQUEST_URI'];
	if (! ($home or $request_uri == $base_url_rewrite or $request_uri == $page_url_rewrite or NV_MAIN_DOMAIN . $request_uri == $base_url_rewrite or NV_MAIN_DOMAIN . $request_uri == $page_url_rewrite)) {
		$redirect = '<meta http-equiv="Refresh" content="3;URL=' . $base_url_rewrite . '" />';
		nv_info_die($lang_global['error_404_title'], $lang_global['error_404_title'], $lang_global['error_404_content'] . $redirect, 404);
	}
	if (! defined('NV_IS_MODADMIN') and $page < 5) {
		$cache_file = NV_LANG_DATA . '_' . $module_info['template'] . '-' . $op . '-' . $page . '-' . NV_CACHE_PREFIX . '.cache';
		if (($cache = $nv_Cache->getItem($module_name, $cache_file)) != false) {
			$contents = $cache;
		}
	}
	
	if (empty($contents)) {
		$viewcat = $module_config[$module_name]['indexfile'];
		$show_no_image = $module_config[$module_name]['show_no_image'];
		$array_sciencecatpage = array();
		$array_sciencecat_other = array();

		if ($viewcat == 'viewcat_none') {
			$contents = '';
		} elseif ($viewcat == 'viewcat_page_new' or $viewcat == 'viewcat_page_old') {
			$order_by = ($viewcat == 'viewcat_page_new') ? 'publtime DESC' : 'publtime ASC';
			$db_slave->sqlreset()
				->select('COUNT(*)')
				->from(NV_PREFIXLANG . '_' . $module_data . '_courses')
				->where('status= 1 AND inhome=1');

			$num_items = $db_slave->query($db_slave->sql())->fetchColumn();

			$db_slave->select('*')
				->order($order_by)
				->limit($per_page)
				->offset(($page - 1) * $per_page);

			$end_publtime = 0;

			$result = $db_slave->query($db_slave->sql());
			while ($item = $result->fetch()) {
				if ($item['homeimgthumb'] == 1) {
					//image thumb
					$item['imghome'] = NV_BASE_SITEURL . NV_FILES_DIR . '/' . $module_upload . '/' . $item['homeimgfile'];
				} elseif ($item['homeimgthumb'] == 2) {
					//image file
					$item['imghome'] = NV_BASE_SITEURL . NV_UPLOADS_DIR . '/' . $module_upload . '/' . $item['homeimgfile'];
				} elseif ($item['homeimgthumb'] == 3) {
					//image url
					$item['imghome'] = $item['homeimgfile'];
				} elseif (! empty($show_no_image)) {
					//no image
					$item['imghome'] = NV_BASE_SITEURL . $show_no_image;
				} else {
					$item['imghome'] = '';
				}

				$item['newday'] = $global_array_sciencecat[$item['catid']]['newday'];
				$item['link'] = $global_array_sciencecat[$item['catid']]['link'] . '/' . $item['alias'] . '-' . $item['id'] . $global_config['rewrite_exturl'];
				$array_sciencecatpage[] = $item;
				$end_publtime = $item['publtime'];
			}

			if ($st_links > 0) {
				$db_slave->sqlreset()
					->select('*')
					->from(NV_PREFIXLANG . '_' . $module_data . '_courses');

				if ($viewcat == 'viewcat_page_new') {
					$db_slave->where('status= 1 AND inhome=1 AND publtime < ' . $end_publtime);
				} else {
					$db_slave->where('status= 1 AND inhome=1 AND publtime > ' . $end_publtime);
				}
				$db_slave->order($order_by)->limit($st_links);

				$result = $db_slave->query($db_slave->sql());
				while ($item = $result->fetch()) {
					$item['newday'] = $global_array_sciencecat[$item['catid']]['newday'];
					$item['link'] = $global_array_sciencecat[$item['catid']]['link'] . '/' . $item['alias'] . '-' . $item['id'] . $global_config['rewrite_exturl'];
					$array_sciencecat_other[] = $item;
				}
			}

			$viewcat = 'viewcat_page_new';
			$generate_page = nv_alias_page($page_title, $base_url, $num_items, $per_page, $page);
			$contents = call_user_func($viewcat, $array_sciencecatpage, $array_sciencecat_other, $generate_page);
		} elseif ($viewcat == 'viewcat_main_left' or $viewcat == 'viewcat_main_right' or $viewcat == 'viewcat_main_bottom') {
			$array_sciencecat = array();

			$key = 0;
			$db_slave->sqlreset()
			->select('*')
			->order('publtime DESC');
			
			foreach ($global_array_sciencecat as $_sciencecatid => $array_sciencecat_i) {
				if ($array_sciencecat_i['parentid'] == 0 and $array_sciencecat_i['inhome'] == 1) {
					$array_sciencecat[$key] = $array_sciencecat_i;
					$featured = 0;
					if ($array_sciencecat_i['featured'] != 0) {
						$result = $db_slave->query($db_slave->from(NV_PREFIXLANG . '_' . $module_data . '_courses')->where('id=' . $array_sciencecat_i['featured'] . ' and status= 1 AND inhome=1')->sql());
						
						if ($item = $result->fetch()) {
							if ($item['homeimgthumb'] == 1) {
								$item['imghome'] = NV_BASE_SITEURL . NV_FILES_DIR . '/' . $module_upload . '/' . $item['homeimgfile'];
							} elseif ($item['homeimgthumb'] == 2) {
								$item['imghome'] = NV_BASE_SITEURL . NV_UPLOADS_DIR . '/' . $module_upload . '/' . $item['homeimgfile'];
							} elseif ($item['homeimgthumb'] == 3) {
								$item['imghome'] = $item['homeimgfile'];
							} elseif (!empty($show_no_image)) {
								$item['imghome'] = NV_BASE_SITEURL . $show_no_image;
							} else {
								$item['imghome'] = '';
							}

							$item['newday'] = $array_sciencecat_i['newday'];
							$item['link'] = $array_sciencecat_i['link'] . '/' . $item['alias'] . '-' . $item['id'] . $global_config['rewrite_exturl'];
							$array_sciencecat[$key]['content'][] = $item;
							$featured = $item['id'];
						}
					}

					if ($featured) {
						$db_slave->from(NV_PREFIXLANG . '_' . $module_data . '_courses')->where('status= 1 AND inhome=1 AND id!=' . $featured)->limit($array_sciencecat_i['numlinks'] - 1);
					} else {
						$db_slave->from(NV_PREFIXLANG . '_' . $module_data . '_courses')->where('status= 1 AND inhome=1')->limit($array_sciencecat_i['numlinks']);
					}
					$result = $db_slave->query($db_slave->sql());
					while ($item = $result->fetch()) {
						if ($item['homeimgthumb'] == 1) {
							$item['imghome'] = NV_BASE_SITEURL . NV_FILES_DIR . '/' . $module_upload . '/' . $item['homeimgfile'];
						} elseif ($item['homeimgthumb'] == 2) {
							$item['imghome'] = NV_BASE_SITEURL . NV_UPLOADS_DIR . '/' . $module_upload . '/' . $item['homeimgfile'];
						} elseif ($item['homeimgthumb'] == 3) {
							$item['imghome'] = $item['homeimgfile'];
						} elseif (! empty($show_no_image)) {
							$item['imghome'] = NV_BASE_SITEURL . $show_no_image;
						} else {
							$item['imghome'] = '';
						}

						$item['newday'] = $array_sciencecat_i['newday'];
						$item['link'] = $array_sciencecat_i['link'] . '/' . $item['alias'] . '-' . $item['id'] . $global_config['rewrite_exturl'];
						$array_sciencecat[$key]['content'][] = $item;
					}

					++$key;
				}
			}

			$contents = viewsubcat_main($viewcat, $array_sciencecat);
		} elseif ($viewcat == 'viewcat_two_column') {
			// Cac bai viet phan dau
			$array_content = $array_sciencecatpage = array();

			// cac bai viet cua cac chu de con
			$key = 0;

			$db_slave->sqlreset()
				->select('*')
				->where('status= 1 AND inhome=1')
				->order('publtime DESC');
			foreach ($global_array_sciencecat as $_sciencecatid => $array_sciencecat_i) {
				if ($array_sciencecat_i['parentid'] == 0 and $array_sciencecat_i['inhome'] == 1) {
					$array_sciencecatpage[$key] = $array_sciencecat_i;
					$featured = 0;
					if ($array_sciencecat_i['featured'] != 0) {
						$result = $db_slave->query($db_slave->from(NV_PREFIXLANG . '_' . $module_data . '_courses')->where('id=' . $array_sciencecat_i['featured'] . ' and status= 1 AND inhome=1')->limit($array_sciencecat_i['numlinks'])->sql());
						while ($item = $result->fetch()) {
							if ($item['homeimgthumb'] == 1) {
								$item['imghome'] = NV_BASE_SITEURL . NV_FILES_DIR . '/' . $module_upload . '/' . $item['homeimgfile'];
							} elseif ($item['homeimgthumb'] == 2) {
								$item['imghome'] = NV_BASE_SITEURL . NV_UPLOADS_DIR . '/' . $module_upload . '/' . $item['homeimgfile'];
							} elseif ($item['homeimgthumb'] == 3) {
								$item['imghome'] = $item['homeimgfile'];
							} elseif (!empty($show_no_image)) {
								$item['imghome'] = NV_BASE_SITEURL . $show_no_image;
							} else {
								$item['imghome'] = '';
							}

							$item['newday'] = $array_sciencecat_i['newday'];
							$item['link'] = $array_sciencecat_i['link'] . '/' . $item['alias'] . '-' . $item['id'] . $global_config['rewrite_exturl'];
							$array_sciencecatpage[$key]['content'][] = $item;
							$featured = $item['id'];
						}
					}
					if ($featured) {
						$db_slave->from(NV_PREFIXLANG . '_' . $module_data . '_courses')->where('status= 1 AND inhome=1 AND id!=' . $featured)->limit($array_sciencecat_i['numlinks'] - 1);
					} else {
						$db_slave->from(NV_PREFIXLANG . '_' . $module_data . '_courses')->where('status= 1 AND inhome=1')->limit($array_sciencecat_i['numlinks']);
					}
					$result = $db_slave->query($db_slave->sql());

					while ($item = $result->fetch()) {
						if ($item['homeimgthumb'] == 1) {
							$item['imghome'] = NV_BASE_SITEURL . NV_FILES_DIR . '/' . $module_upload . '/' . $item['homeimgfile'];
						} elseif ($item['homeimgthumb'] == 2) {
							$item['imghome'] = NV_BASE_SITEURL . NV_UPLOADS_DIR . '/' . $module_upload . '/' . $item['homeimgfile'];
						} elseif ($item['homeimgthumb'] == 3) {
							$item['imghome'] = $item['homeimgfile'];
						} elseif (!empty($show_no_image)) {
							$item['imghome'] = NV_BASE_SITEURL . $show_no_image;
						} else {
							$item['imghome'] = '';
						}

						$item['newday'] = $array_sciencecat_i['newday'];
						$item['link'] = $array_sciencecat_i['link'] . '/' . $item['alias'] . '-' . $item['id'] . $global_config['rewrite_exturl'];
						$array_sciencecatpage[$key]['content'][] = $item;
					}
				}

				++$key;
			}
			unset($sql, $result);
			//Het cac bai viet cua cac chu de con
			$contents = viewcat_two_column($array_content, $array_sciencecatpage);
		} elseif ($viewcat == 'viewcat_grid_new' or $viewcat == 'viewcat_grid_old') {
			$order_by = ($viewcat == 'viewcat_grid_new') ? ' publtime DESC' : ' publtime ASC';
			$db_slave->sqlreset()
				->select('COUNT(*) ')
				->from(NV_PREFIXLANG . '_' . $module_data . '_courses')
				->where('status= 1 AND inhome=1');

			$num_items = $db_slave->query($db_slave->sql())->fetchColumn();

			$db_slave->select('*')
				->order($order_by)
				->limit($per_page)
				->offset(($page - 1) * $per_page);

			$result = $db_slave->query($db_slave->sql());
			while ($item = $result->fetch()) {
				if ($item['homeimgthumb'] == 1) {
					$item['imghome'] = NV_BASE_SITEURL . NV_FILES_DIR . '/' . $module_upload . '/' . $item['homeimgfile'];
				} elseif ($item['homeimgthumb'] == 2) {
					$item['imghome'] = NV_BASE_SITEURL . NV_UPLOADS_DIR . '/' . $module_upload . '/' . $item['homeimgfile'];
				} elseif ($item['homeimgthumb'] == 3) {
					$item['imghome'] = $item['homeimgfile'];
				} elseif (!empty($show_no_image)) {
					$item['imghome'] = NV_BASE_SITEURL . $show_no_image;
				} else {
					$item['imghome'] = '';
				}

				$item['newday'] = $global_array_sciencecat[$item['catid']]['newday'];
				$item['link'] = $global_array_sciencecat[$item['sciencecat']]['link'] . '/' . $item['alias'] . '-' . $item['id'] . $global_config['rewrite_exturl'];
				$array_sciencecatpage[] = $item;
			}

			$viewcat = 'viewcat_grid_new';
			$generate_page = nv_alias_page($page_title, $base_url, $num_items, $per_page, $page);
			$contents = call_user_func($viewcat, $array_sciencecatpage, 0, $generate_page);
		} elseif ($viewcat == 'viewcat_list_new' or $viewcat == 'viewcat_list_old') {
			// Xem theo tieu de

			$order_by = ($viewcat == 'viewcat_list_new') ? 'publtime DESC' : 'publtime ASC';

			$db_slave->sqlreset()
				->select('COUNT(*) ')
				->from(NV_PREFIXLANG . '_' . $module_data . '_rows')
				->where('status= 1 AND inhome=1');

			$num_items = $db_slave->query($db_slave->sql())->fetchColumn();

			$db_slave->select('*')
				->order($order_by)
				->limit($per_page)
				->offset(($page - 1) * $per_page);

			$result = $db_slave->query($db_slave->sql());
			while ($item = $result->fetch()) {
				if ($item['homeimgthumb'] == 1) {
					//image thumb
					$item['imghome'] = NV_BASE_SITEURL . NV_FILES_DIR . '/' . $module_upload . '/' . $item['homeimgfile'];
				} elseif ($item['homeimgthumb'] == 2) {
					//image file
					$item['imghome'] = NV_BASE_SITEURL . NV_UPLOADS_DIR . '/' . $module_upload . '/' . $item['homeimgfile'];
				} elseif ($item['homeimgthumb'] == 3) {
					//image url
					$item['imghome'] = $item['homeimgfile'];
				} elseif (! empty($show_no_image)) {
					//no image
					$item['imghome'] = NV_BASE_SITEURL . $show_no_image;
				} else {
					$item['imghome'] = '';
				}

				$item['newday'] = $global_array_sciencecat[$item['catid']]['newday'];
				$item['link'] = $global_array_sciencecat[$item['catid']]['link'] . '/' . $item['alias'] . '-' . $item['id'] . $global_config['rewrite_exturl'];
				$array_sciencecatpage[] = $item;
			}

			$viewcat = 'viewcat_list_new';
			$generate_page = nv_alias_page($page_title, $base_url, $num_items, $per_page, $page);
			$contents = call_user_func($viewcat, $array_sciencecatpage, 0, ($page - 1) * $per_page, $generate_page);
		}

		if (! defined('NV_IS_MODADMIN') and $contents != '' and $cache_file != '') {
			$nv_Cache->setItem($module_name, $cache_file, $contents);
		}
	}

	if ($page > 1) {
		$page_title .= ' ' . NV_TITLEBAR_DEFIS . ' ' . $lang_global['page'] . ' ' . $page;
	}

	include NV_ROOTDIR . '/includes/header.php';
	echo nv_site_theme($contents);
	include NV_ROOTDIR . '/includes/footer.php';
