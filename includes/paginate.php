<?php
	function doPaginate($url_format, $page=1, $num_pages=1) {
		// url_format should contain %PAGED%
		// eg: doPaginate("/search/boobs/%PAGED%/", 1, 5);
		
		$start = 1;
		$end = min(5, $num_pages);
		
		if($page > 3) {
			$start = ($page - 2);
			$end = min(($page + 2), $num_pages);
			
			if($page == ($num_pages - 2)) {
				$start = max(1, ($page - 2));
			} else if($page == ($num_pages - 1)) {
				$start = max(1, ($page - 3));
			} elseif($page == $num_pages) {
				$start = ($page - 4);
			}
		}
		
		if($start > $end) {
			$old_start = $start;
			$start = $end;
			$end = $old_start;
		}
		
		$start = max(1, min($num_pages, $start));
		$end = max(1, min($num_pages, $end));
		?>
		<div class="row pagelinks">
			<?php if($page > 1): ?><a href="<?=esc_attr( str_replace("%PAGED%", ($page - 1), $url_format) );?>"><i class="fa fa-angle-left"></i></a><?php endif; ?>
			<?php for($i = $start; $i <= $end; $i++): ?><a href="<?=esc_attr( str_replace("%PAGED%", $i, $url_format) );?>" <?php if($i == $page): ?> class="active"<?php endif; ?>><?=$i;?></a><?php endfor; ?>
			<?php if($page < $num_pages): ?><a href="<?=esc_attr(str_replace("%PAGED%", ($page + 1), $url_format));?>"><i class="fa fa-angle-right"></i></a><?php endif; ?>
		</div>
		<?php
	}