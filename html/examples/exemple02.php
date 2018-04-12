<?php

ob_start();
include dirname(__FILE__) . '/res/exemple02.php';
$content = ob_get_clean();
require_once dirname(__FILE__) . '/../html2pdf.class.php';

try {
	$html2pdf = new HTML2PDF('P', 'A4', 'fr', true, 'UTF-8', array(15, 5, 15, 5));
	$html2pdf->pdf->SetDisplayMode('fullpage');
	$html2pdf->writeHTML($content, isset($_GET['vuehtml']));
	$html2pdf->Output('exemple02.pdf');
}
catch (HTML2PDF_exception $e) {
	echo $e;
	exit();
}

?>
