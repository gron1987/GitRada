<?php
/**
 * Created by JetBrains PhpStorm.
 * User: gron
 * Date: 2/24/14
 * Time: 7:48 PM
 */

/**
 * Get page by link
 * @param string $link
 * @return string
 */
function getData($link = ""){
    $mainURL = "http://zakon4.rada.gov.ua";
    $curl = curl_init();

    curl_setopt($curl,CURLOPT_URL,$mainURL . $link);
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);

    $data = curl_exec($curl);
    $data = iconv('cp1251','utf-8',$data);
    // If error with access denied - retry in 1 sec
    if(strpos($data,"Доступ тимчасово обмежено!") > 0){
        sleep(1);
        $data = getData($link);
    }
    // If error with JS redirect - get data from link
    if( strpos($data,"conv?test=") > 0 ){
        preg_match('/location\.href=\"(.+?)\"/',$data,$redirect);
        $data = getData($redirect[1]);
    }
    // If error with permalink
    if( strpos($data,"Натисніть, будь-ласка, на це") ){
        preg_match('/<a href="(.+?)" target=_top><b>посилання<\/b><\/a>/',$data,$redirect);
        $data = getData($redirect[1]);
    }

    $result = cropMainText($data);

    // Check we have paginator.
    if( strpos($data,'наступна сторінка') > 0 ){
        preg_match('/<a href="(.*?)\s?" title="наступна сторінка">наступна сторінка<\/a>/',$data,$page);
        print_r("Get next page ...");
        $result .= getData($page[1]);
    }

    return $result;
}

/**
 * Get data. Start from container and end with search box.
 * @param string $body
 * @return string
 */
function cropMainText($body = "") {
    $body = substr($body,strpos($body,'<div class=txt>'));
    if(strpos($body,'Знайти слова на сторiнцi') > 0){
        return substr($body,0,strpos($body,'Знайти слова на сторiнцi'));
    }
    else if(strpos($body,'Пошук') > 0){
        return $body;
    }else{
        print_r($body);
        die();
    }
}

/**
 * Move tags to Github markup.
 * @param string $data
 * @return string
 */
function parse($data = "") {
    // bold, italic, h2 (Название)
    $data = preg_replace("/<span class=rvts78>(.+?)\s?<\/span>/i","##**_$1_**",$data);
    // bold. h3 (коротко)
    $data = preg_replace("/<span class=rvts23>(.+?)\s?<\/span>/i","###**$1**",$data);
    // bold (Статья)
    $data = preg_replace("/<span class=rvts(?:9|44)>(.+?)\s?<\/span>/i","**$1**",$data);
    // bold h4 (Раздел)
    $data = preg_replace("/<span class=rvts15>(.+?)\s?<\/span>/i","####**$1**",$data);

    $container = $data;

    $container = strip_tags($container);
    $container = preg_replace("/&nbsp;/s"," ",$container);
    $container = preg_replace("/(\r\n|\n|\r)+/s","\n\n",$container);
    return trim($container);
}

$data = getData("/laws/show/4495-17/conv");
preg_match("/<span class=rvts78>(.+?)\s?<\/span>/i",$data,$name);
print_r($name[1]);
$result = parse($data);

#print_r(strpos($data,'Знайти слова на сторiнцi'));

file_put_contents($name[1].".md",$result);

//echo $result;