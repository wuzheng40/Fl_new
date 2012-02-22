<?php
//$text = file_get_contents("douban_home.text");
//require_once dirname(dirname(dirname(__FILE__))) . '/src/Fl.class.php';
//Fl::loadClass('Fl_Html_Token');
//$instance = new Fl_Html_Token($text);
//$instance->tpl = 'smarty';
//$instance->ld = '<&';
//$instance->rd = '&>';

//xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
$startTime = microtime(true);
//$output = $instance->getAllTokens();
//$endTime = microtime(true);


require_once 'htmltoken/HtmlTokenTest.class.php';
$test = new HtmlTokenTest();
$test->run(new HtmlReporter('utf-8'));
require_once 'tagtoken/TagTokenTest.class.php';
$test = new TagTokenTest();
$test->run(new HtmlReporter('utf-8'));
require_once 'htmlcompress/HtmlCompressTest.class.php';
$test = new HtmlCompressTest();
$test->run(new HtmlReporter('utf-8'));
require_once 'csstoken/CssTokenTest.class.php';
$test = new CssTokenTest();
$test->run(new HtmlReporter('utf-8'));
$startTime = microtime(true);
require_once 'jstoken/JsTokenTest.class.php';
$test = new JsTokenTest();
$test->run(new HtmlReporter('utf-8'));
$endTime = microtime(true);
//echo ($endTime - $startTime);

//$xhprof_data = xhprof_disable();
$path = "/home/welefen/Documents/www/";
include_once $path . "xhprof_lib/utils/xhprof_lib.php";  
include_once $path . "xhprof_lib/utils/xhprof_runs.php";  
//$xhprof_runs = new XHProfRuns_Default();  
//echo '<div>Time: '.($endTime - $startTime).'s</div>';
//$run_id = $xhprof_runs->save_run($xhprof_data, "sourcejoy"); 
//echo '<iframe src="http://www/xhprof_html/?run='.$run_id.'&source=sourcejoy" frameborder="0" width="100%" height="950px" border="0"></iframe>';
class  a{
	public function b(){
		$args = func_get_args();
		print_r($args);
	}
}
$a = new a();
//$a->b(1, 2, 4);
?>
