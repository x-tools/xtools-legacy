<?php

require_once('/data/project/xtools/textdiff/textdiff/Diff.php');
require_once '/data/project/xtools/textdiff/textdiff/Diff/Renderer.php';

function getTextDiff($method, $diff1, $diff2) {
	switch ($method) {
		case 'unified':
			require_once '/data/project/xtools/textdiff/textdiff/Diff/Renderer/unified.php';
			$diff = new Text_Diff('auto', array(explode("\n",$diff1), explode("\n",$diff2)));

			$renderer = new Text_Diff_Renderer_unified();
			
			$diff = $renderer->render($diff);
			break;
		case 'inline':
			require_once '/data/project/xtools/textdiff/textdiff/Diff/Renderer/inline.php';
			$diff = new Text_Diff('auto', array(explode("\n",$diff1), explode("\n",$diff2)));

			$renderer = new Text_Diff_Renderer_inline();
			
			$diff = $renderer->render($diff);
			break;
	}
	unset($renderer);
	return $diff;
}