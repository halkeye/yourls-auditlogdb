<?php
/* 
 * Admin Page
*/

// No direct call
if (!defined('YOURLS_ABSPATH')) die();


// Register our plugin admin page
yourls_add_action('plugins_loaded', 'auditlog_admin_page_add_page');
function auditlog_admin_page_add_page()
{
	yourls_register_plugin_page('auditlog', 'Audit Log', 'auditlog_admin_page_do_page');
}

// Display admin page
function auditlog_admin_page_do_page()
{
	// SQL behavior (sorting, searching...)
	$view_params = new YOURLS\Views\AdminParams();
	$page    = isset($_GET['audit_page']) ? intval($_GET['audit_page']) : 1;
	$perpage = $view_params->get_per_page(2);

	$ydb = yourls_get_db();

	// Determine $offset
	$offset = ($page - 1) * $perpage;

	$page_params = array(
		'page' => 'auditlog',
		'audit_page' => $page,
		'perpage' => $perpage,
	);
	$count = @$ydb->fetchCol("SELECT COUNT(*) FROM `" . AUDITLOGDB_DB_TABLE_LOG . "`;")[0];
	$total_pages = ceil($count / $perpage);
	$rows = $ydb->fetchObjects("SELECT `timestamp`, `actor`, `action`, `old_data`, `new_data` FROM `" . AUDITLOGDB_DB_TABLE_LOG . "` ORDER BY timestamp DESC limit $offset, $perpage;");

	echo yourls_apply_filter('table_head_start', '<table id="main_table" class="tblSorter" cellpadding="0" cellspacing="1"><thead><tr>' . "\n");

	$cells = yourls_apply_filter('table_head_cells', array(
		'timestamp' => yourls__('Timestamp'),
		'actor'     => yourls__('Actor'),
		'action'    => yourls__('Action'),
		'old_data'  => yourls__('Old Data'),
		'new_data'  => yourls__('New Data'),
	));

	foreach ($cells as $k => $v) {
		echo "<th id='table_head_$k'><span>$v</span></th>\n";
	}

	echo yourls_apply_filter('table_head_end', "</tr></thead>\n");

	echo yourls_table_tbody_start();

	foreach ($rows as $row) {
		echo "<tr>";
		echo "<td>" . $row->timestamp . "</td>";
		echo "<td>" . $row->actor . "</td>";
		echo "<td>" . $row->action . "</td>";
		echo "<td>" . $row->old_data . "</td>";
		echo "<td>" . $row->new_data . "</td>";
		echo "</tr>";
	}

	echo yourls_table_tbody_end();


?>
	<tfoot>
		<tr role="row">
			<th colspan="6" data-column="0">
				<div id="pagination">
					<span class="navigation">
						<?php if ($total_pages > 1) { ?>
							<span class="nav_total"><?php echo sprintf(yourls_n('1 page', '%s pages', $total_pages), $total_pages); ?></span>
							<br />
							<?php
							$base_page = yourls_admin_url('plugins.php?page=auditlog');
							// Pagination offsets: min( max ( zomg! ) );
							$p_start = max(min($total_pages - 4, $page - 2), 1);
							$p_end = min(max(5, $page + 2), $total_pages);
							if ($p_start >= 2) {
								$link = yourls_add_query_arg(array_merge($page_params, array('page' => 1)), $base_page);
								echo '<span class="nav_link nav_first"><a href="' . $link . '" title="' . yourls_esc_attr__('Go to First Page') . '">' . yourls__('&laquo; First') . '</a></span>';
								echo '<span class="nav_link nav_prev"></span>';
							}
							for ($i = $p_start; $i <= $p_end; $i++) {
								if ($i == $page) {
									echo "<span class='nav_link nav_current'>$i</span>";
								} else {
									$link = yourls_add_query_arg(array_merge($page_params, array('page' => $i)), $base_page);
									echo '<span class="nav_link nav_goto"><a href="' . $link . '" title="' . sprintf(yourls_esc_attr('Page %s'), $i) . '">' . $i . '</a></span>';
								}
							}
							if (($p_end) < $total_pages) {
								$link = yourls_add_query_arg(array_merge($page_params, array('page' => $total_pages)), $base_page);
								echo '<span class="nav_link nav_next"></span>';
								echo '<span class="nav_link nav_last"><a href="' . $link . '" title="' . yourls_esc_attr__('Go to Last Page') . '">' . yourls__('Last &raquo;') . '</a></span>';
							}
							?>
						<?php } ?>
					</span>
				</div>
			</th>
		</tr>
	</tfoot>
<?php
	echo yourls_table_end();
}

function auditlog_admin_page_get_links_from_logs($content_array = array())
{

	$content_array_new = array();
	foreach ($content_array as $line) {
		$matches = explode('Link inserted: ( ', $line);
		#preg_match('/Link inserted: \( [a-z0-9-]*, /', $line, $matches, PREG_UNMATCHED_AS_NULL);
		#var_dump('matches',$matches);
		if (!empty($matches[1])) {
			$matches = explode(', ', $matches[1]);
			if (!empty($matches[1])) {
				$matches = explode(' )', $matches[1]);
				if (!empty($matches[0])) {
					$content_array_new[] = $matches[0];
				}
			}
		}
	}

	return $content_array_new;
}
