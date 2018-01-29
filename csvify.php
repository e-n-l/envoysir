<?php
function csvify($content){
	$content = str_replace('"', "'", $content);

	$error = stripos($content,"class='error'");
	if ($error!=false){
		$error+=strlen(" class='error'");
		$content = strip_tags(substr( $content, $error,stripos($content,"</b>",$error)-$error));
	}

	$content = str_replace(array("\r", "\r\n", "\n"), '', $content);
	$content = preg_replace('[^\S ]',"",$content); //remove whitespace / newlines
	$content = str_replace("<img", "<span", $content);
	$content = str_replace("<link", "<span", $content);
	$content = str_replace("<script", "<span", $content);
	$content = str_replace("</script>", "</span>", $content);

	//mark the top for stripping out
	$content = str_replace("<!-- * Created by:","#A",$content); 
	$content = str_replace("<dl class='order-status-info order-status-summary'>","A#<dl class='order-status-info order-status-summary'>", $content);

	// mark the bottom for stripping out
	$content = str_replace("<div class='container footer-container'>","#A",$content);
	$content = str_replace("</span></body></html>","A#", $content);


	$content = preg_replace("/#A.+?A#/","",$content); //strip stuff out
	$content = preg_replace('~>\\s+<~m', '><',$content); // space remover
	$content = preg_replace('[^\S ]',"",$content); //remove whitespace / newlines

	// CSV style, pipe delimited.
	$content = str_replace("</table>","\n",$content);
	$content = str_replace(","," ",$content);
	$content = str_replace("</td></tr>","\n",$content);
	$content = str_replace("</td>","|",$content);
	$content = str_replace("</th>","|",$content);
	$content = str_replace("</dd>","|",$content);
	$content = str_replace("</dt>","|",$content);
	$content = str_replace("<dt>","\n",$content);
	$content = str_replace("<tr>","\n",$content);
	$content = str_replace("\n\n","\n",$content);
	$content = strip_tags($content);

	return $content;
}
?>