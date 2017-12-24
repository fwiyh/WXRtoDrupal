<?php
$filePath = "/Users/kitaz/Desktop/wordpress.2017-11-13.xml";
$createFile = "/Users/kitaz/Desktop/wordpress.2017-11-13_2.xml";

$viewXml = "/Users/kitaz/Desktop/xoops_xpress_views.xml";

// ファイル読み込み
$fileString = file_get_contents($filePath);

// $fileString = '
// <wp:post_id>1377</wp:post_id>
// <guid isPermaLink="false">http://kitaz.dyndns.org/default/modules/xpress/?p=1031</guid>
// <description></description>
// <content:encoded><![CDATA[ソースはgpara。<a href="hoe">ppep</a>
// <!--more-->

// 女性はゲームに何を求めるのか。ベルギーの調査結果
// <a href="http://www.gpara.com/kaigainews/eanda/2010070801/">http://www.gpara.com/kaigainews/eanda/2010070801/</a>

// これは良記事。
// 非常に参考になる。]]></content:encoded>
// <e->(￣д￣)</e->
// Wi2 Connectの新バージョンをリリースしました。ぜひお試しください。
// ダウンロードはこちらから。

// 今後も順次、エリアを拡大していきます。
// エリア拡大情報やメンテナンス情報などは、お知らせをご覧ください。
// <wp:post_id>999</wp:post_id>
// ';

$procpos = 0;

/**
 * summaryの作成
 */
for (;;){
    $beginpos = mb_strpos($fileString, "<content:encoded><![CDATA[", $procpos);
    if ($beginpos === false){
        break;
    }

    // 本文の締めになるタグを取得
    $endpos = mb_strpos($fileString, "</content:encoded>", $beginpos);

    $bodyoriginal = mb_substr($fileString, $beginpos, $endpos - $beginpos + mb_strlen("</content:encoded>"), "UTF-8");
    
    // summaryの取得
    $morepos = mb_strpos($bodyoriginal, "<!--more-->");

    if ($morepos !== false){
        $tmpsummary = mb_substr($bodyoriginal, 0, $morepos, "UTF-8");
        $tmpsummary = str_replace("<content:encoded><![CDATA[", "", $tmpsummary);
        $summary = mb_ereg_replace("<a(?: .+?)?>.*?<\/a>", "", $tmpsummary);
        
        $newbody = str_replace("<!--more-->", "", $bodyoriginal) ."\n"
                    ."<wp:summary>". $summary ."</wp:summary>\n";

        echo "[Generated Body]". $newbody ."\n";
        
        // 新しいデータを差し替える
        $fileString = mb_substr($fileString, 0, $beginpos) . $newbody . mb_substr($fileString, $endpos + mb_strlen("</content:encoded>"));
        // 次の処理位置を取得
        $procpos = $endpos + mb_strlen("</content:encoded>");
    }else {
        $procpos = $endpos;
    }

    echo "[next search position]". $procpos ."\n";
}

/**
 * アクセス数の取得
 */
$xmlData = simplexml_load_file($viewXml);
$procpos = 0;
for (;;){
    $beginpos = mb_strpos($fileString, "<wp:post_id>", $procpos);
    if ($beginpos === false){
        break;
    }
    // 締めになるタグを取得
    $endpos = mb_strpos($fileString, "</wp:post_id>", $beginpos);

    echo "[begin position]". $beginpos ."\n";
    echo "[end position]". $endpos ."\n";

    // post_id
    $postId = mb_substr($fileString, $beginpos + mb_strlen("<wp:post_id>"), $endpos - ($beginpos + mb_strlen("<wp:post_id>")));
    $viewCount = getPostId($xmlData, $postId);

    // 要素の追加
    $viewselement = "\n<wp:totalcount>". $viewCount ."</wp:totalcount>\n";
    $fileString = mb_substr($fileString, 0, $endpos + mb_strlen("</wp:post_id>"))
                . $viewselement
                . mb_substr($fileString, $endpos + mb_strlen("</wp:post_id>"));
    // 次の処理位置を取得
    $procpos = $endpos + mb_strlen("</wp:post_id>". $viewselement);

    // echo "[target post_id]". $postId ."\n";
    // echo "[next search position]". $procpos ."\n";
    // echo "[view count]". $viewCount ."\n";
    echo "\n";
}

file_put_contents($createFile, $fileString);


function getPostId($xmlData, $postId){
    $ret = 0;
    $data = $xmlData->database->table_data->row;
    foreach ($data as $r){
        if ($r->field[1] == $postId){
            $ret = $r->field[2];
            break;
        }
    }
    return $ret;
}
