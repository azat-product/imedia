<?php

/**
 *  - escape:
 *    - 'html' for HTML escaping, this is the default.
 *    - 'js' for javascript escaping.
 *    - 'url' for url escaping.
 *    - 'no'/'off'/0 - turns off escaping
 *  - c: контекст (обязательный параметр)
 * 
 *  пример:
 * {t c='' author='Bill'}text, created by [author]{/t}
 * {t c='users' n=$books}[n] книга|[n] книги|[n] книг{/t}
 * {t c='users' n=$books}одна книга|две книги|[n] книг{/t}
 * {t c='' escape=true}переведи меня и сделай escape{/t}
 * {t c=''}переведи меня{/t}
 */
function smarty_block_t($params, $text, &$smarty)
{
	$text = stripslashes($text);
	
	// set escape mode
	if (isset($params['escape'])) {
		$escape = $params['escape'];
		unset($params['escape']);
	}
    
    $context = '';
    if(isset($params['c'])) {
        $context = $params['c'];
        unset($params['c']);
    }
    
    $paramsRes = array();
    if(isset($params['n'])) {
        $paramsRes[0] = $params['n'];
        unset( $params['n'] );
    }
    
    foreach($params as $k=>$v) {
        $paramsRes['['.$k.']'] = $v;
    } unset($params);

    $text = _t($context, $text, $paramsRes);

	if(isset($escape)) 
    {
		switch ($escape) {
			case 'javascript':
			case 'js': // javascript escape
				$text = str_replace('\'', '\\\'', stripslashes($text));
				break;
			case 'url': // url escape
				$text = urlencode($text);
				break;
            default: // html
                $text = nl2br(htmlspecialchars($text)); 
		}
	}
	
	return $text;
}
