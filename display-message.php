<?php
//Form for correcting input from user
require __DIR__ . '/vendor/autoload.php';
//include 'mda.php';
function write_to_fref($line, $position, $word){
    $filename = getcwd() . "/newfile.html";
    $line_looking_for = $line;
    $lines = file( $filename , FILE_IGNORE_NEW_LINES );
    $a1 = explode(' ', $lines[$line]);
    array_splice($a1, $position, count($word)-1, $word);
    $lines[$line_looking_for] = implode(' ', $a1);
    file_put_contents( $filename , implode( "\n", $lines ) );
}

function form_correct($tag_array, $tag, $line, $index, $index1){
    echo <<< END
   <script type="text/javascript">
        $(document).ready(function() {
            document.getElementById("b-error").className = "bub-danger";
            var size = $('.col-md-6 .form-container').length;
            document.getElementById("b-error").innerHTML = size;
            $('a.line$line').click(function() {
                $.smoothScroll({
                    offset: -200,
                    scrollElement: $('div.showcode-container'),
                    scrollTarget: '#line$line',
                    beforeScroll: function(options) {
                        $('.line').removeClass("active");
                    },
                    afterScroll: function(options) {
                        $('#line$line').addClass("active");
                    }
                });
            return false;
            });
        });
    </script>
END;

    $identifier= $tag."".$line.$index;
    echo "<div class='$identifier form-container'>";
    echo "<div class='info-detail'>";
    echo "<p> Fault in <a href='#' class='line$line'>line ".$line."</a></p>";
    $desc="";
    $info ="Without double quote";
    switch ($tag){
        case 'img':
            echo <<< END
            <p>Img tag doesn't have 'alt' properties | WCAG 2.0 level A Perceivable 
            <a href='#' id='open-button$line' class='btn btn-sm btn-info' onclick="revealInfo('open-button$line', '$tag')">
            More info <i class='glyphicon glyphicon-info-sign'></i></a>
            </p>
            <hr />
END;
            $desc = "Give alt value";
            $word = "<code>".htmlspecialchars('alt="..."')."</code>";
            echo "<p class='tag-info'>";
            echo substr($tag_array, 0, 80).'...';
            echo "</p>";
            break;

        case 'input':
            echo <<< END
            <p>Input tag needs aria-label properties | WCAG 2.0 level A Perceivable 
            <a href='#' id='open-button$line' class='btn btn-sm btn-info' onclick="revealInfo('open-button$line', '$tag')">
            More info <i class='glyphicon glyphicon-info-sign'></i></a>
            </p>
            <hr />
END;
            $desc = "Give aria-label value ";
            $word = "<code>".htmlspecialchars('aria-label="..."')."</code>";
            $a1 = array($tag_array[0], $word);
            array_splice($tag_array, 0,1,$a1);
            echo "<p class='tag-info'>";
            echo substr(join(' ', $tag_array), 0, 117);
            if (strlen(join(' ', $tag_array)) > 117){
                echo "... ";
            }
            echo "</p>";
            break;

        case 'label':
            $desc = "Give label's for and input's id value";
            $word = htmlspecialchars('<label')."<code>".htmlspecialchars('for="..."')."</code>".substr($tag_array[0], 9, strlen($tag_array[0]));
            $a1 = array($word);
            array_splice($tag_array, 0,1,$a1);
            $id_index = $index1-$index;
            if (substr($tag_array[$id_index], 0, 2) == 'id'){
                $tag_array[$id_index] = "<code>".$tag_array[$id_index]."</code>";
            } else {
                $word = "<code>".htmlspecialchars('id="..."')."</code>";
                $a2 = array($tag_array[$id_index], $word);
                array_splice($tag_array, $id_index,1,$a2);
            }
            echo "<p class='tag-info'>";
            foreach ($tag_array as $items){
                echo $items." ";
            }
            echo "</p>";

    }
    echo <<< END
    </div>
        <label for='correct'>$desc</label> <small>$info</small> 
        <div class='form-group'>
            <input type='text' class='form-control correct-text' id='correct_$identifier' placeholder='your text' required>
            <input type='hidden' id='position_$identifier' value='$line $index $index1 $tag'>
            <button class='btn btn-default' id='tiger' onclick='runAjax("$identifier")'>Edit</button>   
            <button class='btn btn-default' onclick='runIgnore("$identifier")'>Ignore</button>
        </div>
    </div>
END;
}
function doc_lang($all_text){
    $text = htmlspecialchars_decode($all_text);
    $find = '/<html (.*?)>/';
    preg_match_all($find, $text, $arr_lang);
    $a1 = count($arr_lang[1]);
    $message = 'HTML documents don\'t have specific language';
    if ($a1 == 0){
        display_alert($message, 'lang-info', 'langInfo');
    } else if ($a1 ==1){
        if (!(preg_match('/lang="(...?)"/', $arr_lang[1][0]))){
            display_alert($message, 'lang-info', 'langInfo');
        }else {
            $lang = 'Language = <strong> English</strong>';
            display_auto($lang, 'basic-list','lang-check', 'langInfoTrue');
        }
    } else {
    	$lang = 'Language = <strong> English</strong>';
        display_auto($lang, 'basic-list','lang-check', 'langInfoTrue');
    }
}
function check_onchange($all_text){
    $onchange_txt = array();
    $text = htmlspecialchars_decode($all_text);
    $find = '/<select (.+)? onchange=(.+?) (.*)?>/';
    preg_match_all($find, $text, $arr_onchange);
    $class = 'onchange-check';
    if (count($arr_onchange[0]) != 0){
        foreach ($arr_onchange[0] as $items){
            array_push($onchange_txt, '<li><samp>'.htmlspecialchars($items).'</samp></li>');
        }
        $message = '<code>select</code> element may cause extreme change in context due to <samp>onchange()</samp> javascript function';
        display_alert($message, $class, 'onchangeInfo');
        if (count($arr_onchange[0]) <= 2){
            display_child_few(implode(' ', $onchange_txt), 'alert-list', $class, 'panel-warning', 'onchange-list');
        }
    }
}
function check_orderedlist($all_text){
    $item_li = array();
    $text = htmlspecialchars_decode($all_text);
    $find = '/<ol(.*?)>(.*?)<\/ol>/s';
    preg_match_all($find, $text, $match);
    $a1 = htmlspecialchars('<ol>');
    $class = 'orderlist_check';
    if (count($match[0]) != 0){
        foreach ($match[2] as $items){
            $item_li = preg_split("/\\r\\n|\\r|\\n/", $items);
        }
        foreach ($item_li as $key=>$item){
            $value = trim($item);
//            if (!empty($value))
//                echo htmlspecialchars($item)."<br>";
        }
        $message = 'Ordered list <code>'.$a1.'</code> probably misused';
        display_alert($message, $class, 'olInfo');
    } else{
        $message = 'No ordered list found';
        display_auto($message, 'basic-list', $class, 'olInfoTrue');
    }
}
function check_id($line_with_tag){
    $id_array = array();
    $indicator=0; $message='';
    foreach ($line_with_tag as $line){
        if (preg_match('/id=&quot;(.*?)&quot;/', $line['tagname'])){
            $a1 = preg_replace('/id=&quot;(.*?)&quot;&gt;/', 'id=&quot;$1&quot;', $line['tagname']);
            array_push($id_array, $a1);
        }
    }
    //Check duplicate id
    foreach(array_count_values($id_array) as $key=>$items){
        if ($items > 1){
            $message = "<code>".$key."</code> Was used for ".$items." times";
            $indicator = 1;
        }
    }
    if ($indicator == 0){
        $message_no =  "No duplicate ID found";
        display_auto($message_no, 'basic-list','id-check', 'idInfoTrue');
    } else {
        display_alert($message, 'id-check-empty', 'idInfo');
    }
}
function fix_glyph_icon($all_text){
    $arr_icon=array();
    $class ='glyph-check';
    $text = htmlspecialchars_decode($all_text);
    $find = '/<(span|i) class="(glyph|fa|ico)(?!.*aria)(.+?)"> ?<\/(span|i)>/';
    $find1 ='/<(span|i) class="(glyph|fa)(.*)" (style=".+")> ?<\/(span|i)>/';
    preg_match_all($find, $text, $icon_arr);
    $item1 = preg_replace($find, '<$1 class="$2$3" aria-hidden="true"></$4>', $text);
    foreach ($icon_arr[0] as $items){
        $a1 = preg_replace($find1, '<$1 class="$2$3"></$5>', $items);
        $a2 = preg_replace($find, '<$1 class="$2$3" aria-hidden="true"></$4>', $a1);
        array_push($arr_icon, "<li>".htmlspecialchars($a2)."</li>");
    }
    if (count($icon_arr[0])==0){
        $message = "No icon found";
        display_auto($message, 'basic-list',$class, 'glyphInfoTrue');
    } else {
        $a = count($icon_arr[0]);
        $message = $a." icon/s have been added accessibility features";
        display_auto($message, 'auto-list', $class, 'glyphInfo');
        display_child_many(implode(' ',$arr_icon), 'auto-list', $class, 'panel-success', 'icon-list');
    }
    echo <<< END
    <script type="text/javascript">
        document.getElementById("info-intro").style.display = "none";
        document.getElementById("nav-tab").style.visibility = "visible";
        document.getElementById("info-auto").style.display = "block";
    </script>
END;
    return $item1;
}
function get_heading($all_text){
    $heading = array();
    $text = htmlspecialchars_decode($all_text);
    preg_match_all('/<h[1-6](.*?)>(.*?)<\/h[1-6]>/s', $text, $pat_array);
    foreach ($pat_array[0] as $keys=>$item){
        $h = $item[2];
        $item1 = preg_replace('/<\/?[a-z]?(.*?)>/', '', $item);
        $item2 = $h.' '.$item1;
        array_push($heading, $item2);
    }
    echo ' <div role="tabpanel" class="tab-pane" id="outline">';
    echo '<p class="bg-info">This is the structure of the content\'s header that understand by screen reader</p>';
    for ($i=0; $i<count($heading); $i++){
        echo '<p class="p-head p-'.$heading[$i][0].'"><span class="btn btn-head btn-sm btn-'.$heading[$i][0].'">H'.$heading[$i][0].'</span>'.substr($heading[$i], 1).'</p>';
    }
    echo '</div>';
}
function get_italic($all_text){
    $italic_txt = array();
    $find = '/<(i|em)(.*?)>(.*?)<\/(i|em)>/';
    $find1 = '/<(i|em)(.*?)>(.+)<\/(i|em)>/';
    $text = htmlspecialchars_decode($all_text);
    preg_match_all($find, $text, $italic_arr);
    foreach ($italic_arr[0] as $items){
        if (preg_match($find1, $items)){
            array_push($italic_txt, '<li><samp>'.htmlspecialchars($items).'</samp></li>');
        }
    }
    $class = 'italic-info';
    if (count($italic_txt) >= 1){
        $message = "Found ".count($italic_txt)." word/s in italic format";
        display_alert($message, $class, 'italicInfo');
        if (count($italic_txt) <=2){
            display_child_few(implode(' ', $italic_txt), 'alert-list', $class, 'panel-warning', 'italic-list');
        }else {
            display_italic(implode(' ', $italic_txt));
        }
    } else {
        $message = 'No italic style found';
        display_auto($message, 'basic-list',$class, 'italicInfoTrue');
    }
}
function display_auto($message, $id_type, $type, $detail_info){
    echo <<< END
    <script type="text/javascript">
        $(document).ready(function(){
            $("ul#$id_type").append("<li class='$type'>$message </li>");
            $("ul#$id_type li.$type").append($('<a/>', 
                {'text': "More info", 'class': 'btn btn-info btn-sm', 'id': '$type'}).on({'click': function() { revealInfo("$type", "$detail_info") }}));
        });
    </script>
END;
}
function display_alert($message, $type, $detail_info){
    echo <<< END
    <script type="text/javascript">
        $(document).ready(function(){
            $("ul#alert-list").append("<li class='$type'>$message</li>");
            $("ul#alert-list li.$type").append($('<a/>', 
                {'text': "More info", 'class': 'btn btn-info btn-sm', 'id': '$type'}).on({'click': function() { revealInfo("$type", "$detail_info") }}));
            var size = $("#alert-list >li").length;
            document.getElementById("b-alert").className = "bub-alert";
            document.getElementById("b-alert").innerHTML = size;
        });
    </script>
END;
}
function display_child_many($message, $id_container, $li_class, $level, $id_child){
    echo <<< END
    <script type="text/javascript">
        $(document).ready(function(){
            $("ul#$id_container li.$li_class").append("<a class='collapsed btn btn-success btn-sm' role='button' data-toggle='collapse' data-parent='#accordion' href='#collapseIco' aria-expanded='false' aria-controls='collapseThree'><i class=' glyphicon glyphicon-menu-down'></i></a> <br />");
            $("ul#$id_container li.$li_class").append("<div id='collapseIco' class='panel-collapse collapse' role='tabpanel' aria-labelledby='headingThree'><div class='panel-body $level'><ol id='$id_child'></ol></div></div>")
            $("ol#$id_child").append("$message");
        });
    </script>
END;
}
function display_italic($message){
    echo <<< END
    <script type="text/javascript">
        $(document).ready(function(){
            $("ul#alert-list li.italic-info").append("<a class='collapsed btn btn-success btn-sm' role='button' data-toggle='collapse' data-parent='#accordion' href='#collapseItalic' aria-expanded='false' aria-controls='collapseThree'><i class=' glyphicon glyphicon-menu-down'></i></a> <br />");
            $("ul#alert-list li.italic-info").append("<div id='collapseItalic' class='panel-collapse collapse' role='tabpanel' aria-labelledby='headingThree'><div class='panel-body panel-warning'><ul id='italic-list'></ul></div></div>")
            $("ul#italic-list").append("$message");
        });
    </script>
END;
}
function display_child_few($message, $id_container, $li_class, $level, $child_id){
    echo <<< END
    <script type="text/javascript">
        $(document).ready(function(){
            $("ul#$id_container li.$li_class").append("<div class='panel-body $level'><ol id='$child_id'></ul></div></div>")
            $("ol#$child_id").append("$message");
        });
    </script>
END;
}

function check_contrast($all_text){
    $findcss = '/href="(.+?styles?\.css)"/';
    $text = htmlspecialchars_decode($all_text);
    preg_match_all($findcss, $text, $arr_find);
    if (!empty($arr_find[1][0])){
        $cssurl = $arr_find[1][0];
        $oCssParser = new Sabberworm\CSS\Parser(file_get_contents($cssurl));
        $oCssDocument = $oCssParser->parse();
        $find = '/(?=.+background-color: (.*?);)(?=.+;color: (.+?);).+/';
        $find1 = '/(.+?){(.+?)}/';
        $arr1 = array();
        foreach ($oCssDocument->getAllDeclarationBlocks() as $key=>$items){
            if (preg_match_all($find, $items, $arr)){
                preg_match_all($find1, implode(' ', $arr[0]), $arra);
                array_push($arr1, $arra[1][0].'*'.$arr[1][0].'*'.$arr[2][0]);
            }

        }
       echo implode(' ', $arr1);
    } else {
        $message = 'Can\'t parse css file ';
    }
   // display_contrast($message, 'cont', 'contrastInfo');
}
function display_contrast($message, $type, $detail_info){
    echo <<< END
    <script type="text/javascript">
        $(document).ready(function(){
            $("ul#contrast-list").append("<li class='$type'>$message</li>");
            $("ul#contrast-list li.$type").append($('<a/>', 
                {'text': "More info", 'class': 'btn btn-info btn-sm', 'id': '$type'}).on({'click': function() { revealInfo("$type", "$detail_info") }}));
        });
    </script>
END;
}


//function test_par($all_text){
//    $text = htmlspecialchars_decode($all_text);
//    $find = '/<([a-z]*)\b[^>]*>(.*?)</\1>/i';
//    preg_match_all($find, $text, $matches);
//}
?>