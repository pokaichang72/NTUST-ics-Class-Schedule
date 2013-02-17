<!DOCTYPE html>
<?php
$semester = 1012; //學期
$sd = 20130218; //開始上課日期

$ThisURL = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
if(substr($ThisURL, -1) != "/") {
  $ThisURL = dirname($ThisURL);
  $ThisURL = $ThisURL."/";
}

function getclassdata($class, $semester){ //取得多個課程資訊並輸出成陣列, 輸入值為課程代碼, 以空格分隔.
  $class_array = explode(" ", $class);
  $i=0;

  foreach($class_array as $index => $value){
    if($value != "" && strlen($value) == 9){
        $http="http://info.ntust.edu.tw/faith/edua/app/qry_linkoutline.aspx?semester=".$semester."&courseno=".$value;
        $content = file_get_contents($http);

        $contenta = explode('<span id="lbl_courseno"><font color="SlateGray">', $content);
        $contentb = explode('</font></span>', $contenta[1]);

      if($contentb[0] == $value){
        $data[$i]['code']=$contentb[0];

        $contenta = explode('<span id="lbl_coursename"><font color="SlateGray">', $content);
        $contentb = explode('</font></span>', $contenta[1]);
        $data[$i]['title']=$contentb[0];

        $contenta = explode('<span id="lbl_timenode"><font color="SlateGray">', $content);
        $contentb = explode('</font></span>', $contenta[1]);
        $i2 = 0;
        $te = explode(' ', $contentb[0]);
        foreach($te as $index2 => $value2){
            if($value2 != ""){
                $value2ea = explode('(', $value2);
                $value2eb = explode(')', $value2ea[1]);
                $data[$i]['time'][$i2] = $value2ea[0];
                $data[$i]['location'][$i2] = $value2eb[0];
                $i2++;
            }
        }

        $contenta = explode('<span id="lbl_teacher"><font color="SlateGray" size="3">', $content);
        $contentb = explode('</font></span>', $contenta[1]);
        $data[$i]['lecturer']=$contentb[0];

        $i++;
      }
    }
  }
    return $data;
}

$wd = array(
  "1" => "MO",
  "2" => "TU",
  "3" => "WE",
  "4" => "TH",
  "5" => "FR",
  "6" => "SA",
  "7" => "SU"
);

$t[1][0] = '0830';
$t[2][0] = '0930';
$t[3][0] = '1030';
$t[4][0] = '1130';
$t[5][0] = '1230';
$t[6][0] = '1330';
$t[7][0] = '1430';
$t[8][0] = '1530';
$t[9][0] = '1630';
$t[10][0] = '1730';
$t['A'][0] = '1825';
$t['B'][0] = '1920';
$t['C'][0] = '2015';
$t['D'][0] = '2110';

$t[1][1] = '0920';
$t[2][1] = '1020';
$t[3][1] = '1120';
$t[4][1] = '1220';
$t[5][1] = '1320';
$t[6][1] = '1420';
$t[7][1] = '1520';
$t[8][1] = '1620';
$t[9][1] = '1720';
$t[10][1] = '1820';
$t['A'][1] = '1915';
$t['B'][1] = '2010';
$t['C'][1] = '2105';
$t['D'][1] = '2200';

$d = array(
  "M" => "1",
  "F" => "5",
  "T" => "2",
  "S" => "6",
  "W" => "3",
  "U" => "7",
  "R" => "4"
);

//定義一些變數


if($_GET['press']){

  $sdu = strtotime($sd);
  $swd = date('w', $sdu);
  //轉換開始日期

  $class = $_GET['content'];
  $class = ereg_replace("	", " ", $class);
  $data = getclassdata($class, $semester);

  if($data == "" || $data == null){
  	$error_no_data = 1;
  	$_GET['press'] = "";
  }else{

    $file = "ics/".$semester."-".$_GET['id'].".ics";
    $handle= fopen($file,'w');
    $txt = "BEGIN:VCALENDAR\nPRODID:ntust.pokaichang.com\nVERSION:2.0\nCALSCALE:GREGORIAN\nMETHOD:PUBLISH\nX-WR-CALNAME:課程表\nX-WR-TIMEZONE:Asia/Taipei\nX-WR-CALDESC:\nBEGIN:VTIMEZONE\nTZID:Asia/Taipei\nX-LIC-LOCATION:Asia/Taipei\nBEGIN:STANDARD\nTZOFFSETFROM:+0800\nTZOFFSETTO:+0800\nTZNAME:CST\nDTSTART:19700101T000000\nEND:STANDARD\nEND:VTIMEZONE";
    fwrite($handle,$txt);

    foreach($data as $index => $classinfo){
      foreach($classinfo[time] as $cn => $dat){
        $date = substr($dat, 0, 1);
        $time = substr($dat, 1, 2);
        $daysafter = $d[$date]-$swd;
        if($daysafter < 0) $daysafter+=7;

        $eyears = date("Y", $sdu);
        $emonths = date("m", $sdu);
        $edays = date("d", $sdu);
        $edate = date("Ymd", mktime(0,0,0,$emonths,$edays+$daysafter,$eyears));
        $dd = $d[$date];

        $txt = "\n\nBEGIN:VEVENT\n";
        fwrite($handle,$txt);

        $txt = "DTSTART;TZID=Asia/Taipei:".$edate."T".$t[$time][0]."00\n";
        fwrite($handle,$txt);

        $txt = "DTEND;TZID=Asia/Taipei:".$edate."T".$t[$time][1]."00\n";
        fwrite($handle,$txt);

        $txt = "RRULE:FREQ=WEEKLY;COUNT=18;BYDAY=".$wd[$dd]."\n";
        fwrite($handle,$txt);

        $txt = "SUMMARY:".$classinfo[title]."\nLOCATION:".$classinfo[location][$cn]."\nDESCRIPTION:授課教師: ".$classinfo[lecturer]."\n";
        fwrite($handle,$txt);

        $txt = "END:VEVENT\n";
        fwrite($handle,$txt);
      }
    }
 
    $txt = "END:VCALENDAR";
    fwrite($handle,$txt);

    fclose($handle);
  }
}
?>
<html lang="zh-tw">
  <head>
    <meta charset="utf-8">
    <title>NTUST 課表行事曆製作工具</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="把台科大課表做成 iCalender 格式的工具，任何支援匯入 .ics 檔的日曆程式，例如 Google Calender (Android 行事曆)、iOS 日曆 都可以用。會自動把上課地點和授課教師附註上去。">
    <meta name="author" content="Neson">

    <link href="css/bootstrap.css" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet">
    <style type="text/css">
      body {
        padding-top: 40px;
        padding-bottom: 40px;
        background-color: #f5f5f5;
      }

      label {
      	font-size: 16px;
      	margin-top: 20px;
      }

      input {
      	margin-top: 0 !important;
      	margin-bottom: 0 !important;
      }

      .form-signin {
        max-width: 380px;
        padding: 19px 29px 29px;
        margin: 0 auto 20px;
        background-color: #fff;
        border: 1px solid #e5e5e5;
        -webkit-border-radius: 5px;
           -moz-border-radius: 5px;
                border-radius: 5px;
        -webkit-box-shadow: 0 1px 2px rgba(0,0,0,.05);
           -moz-box-shadow: 0 1px 2px rgba(0,0,0,.05);
                box-shadow: 0 1px 2px rgba(0,0,0,.05);
      }
      .form-signin .form-signin-heading,
      .form-signin .checkbox {
        margin-bottom: 10px;
      }
      .form-signin input[type="text"],
      .form-signin input[type="password"] {
        font-size: 16px;
        height: auto;
        margin-bottom: 15px;
        padding: 7px 9px;
      }
      .btn {
      	margin-bottom: 12px; 
      }
      .modal-header {
      	border: 0;
      }
      .accordion-heading {
      	background-color: #f5f5f5;
      }
      .accordion-toggle {
      	color: black;
      }
      .data-group, .data-heading, .data-collapse, .data-heading > *, .data-collapse > * {
      	background-color: white;
      	padding: 0 !important;
      	border: 0 !important;
      }
      .load {
        -webkit-transition: 1s;
           -moz-transition: 1s;
             -o-transition: 1s;
                transition: 1s;
      }

    </style>
    <link href="css/bootstrap-responsive.css" rel="stylesheet">
    <script type="text/javascript" src="js/jquery.min.js"></script>
    <script type="text/javascript" src="js/bootstrap.min.js"></script>
    <script type="text/javascript">

      var _gaq = _gaq || [];
      _gaq.push(['_setAccount', 'UA-35493848-2']);
      _gaq.push(['_trackPageview']);

      (function() {
        var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
        ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
      })();

    </script>

  </head>

  <body>
    <div id="fb-root"></div>
      <script>(function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/zh_TW/all.js#xfbml=1&appId=132913846761101";
        fjs.parentNode.insertBefore(js, fjs);
      }(document, 'script', 'facebook-jssdk'));</script>

    <div class="container">
      <script type="text/javascript">
        function validate_required(field, c){
          with (field){
            if (value==null||value==""){
              $(c).addClass("error");
              $(c).addClass("animated");
              $(c).addClass("shake");
              setTimeout('$(".control-group").removeClass("shake")', 1000);
              return false;
            }else{
              return true;
            }
          }
        }

        function validate_form(thisform){
          with (thisform){
            if (validate_required(id, ".id") == false){
              id.focus();
              return false
            }
            if (validate_required(content, ".content") == false){
              content.focus();
              return false
            }
            $(".load").css("height","auto");
          }
          return true;
        }
      </script>

      <form action="#get" class="form-signin" method="get" onsubmit="return validate_form(this);">
        <h2 class="form-signin-heading">NTUST <br>課表行事曆製作工具</h2>
        <div class="accordion-group">
          <div class="accordion-heading">
            <a class="accordion-toggle collapsed" data-toggle="collapse" data-parent="#accordion2" href="#collapseOne">
              <i class="icon-question-sign"></i> 這啥？
            </a>
          </div>
          <div id="collapseOne" class="accordion-body collapse" style="height: 0px;">
            <div class="accordion-inner">
              <p>把台科大課表做成 iCalender 格式的工具，任何支援匯入 .ics 檔的日曆程式，例如 Google Calender (Android 行事曆)、iOS 日曆 都可以用。會自動把上課地點和授課教師附註上去。
              <a href="https://lh4.googleusercontent.com/-pxK0g-HjeMA/UHWMpWtdCZI/AAAAAAAAKEQ/p00vcE9pPSo/s2000/5.jpg" target="_blank">圖解</a></p>
              <p>有鑒於把課表一個一個手動 key-in 到行事曆會死掉所以做了這個東西。</p>
              <p>
                <iframe src="http://ghbtns.com/github-btn.html?user=pokaichang72&repo=NTUST-ics-Class-Schedule&type=watch" allowtransparency="true" frameborder="0" scrolling="0" width="62" height="22"></iframe>
                <iframe src="http://ghbtns.com/github-btn.html?user=pokaichang72&repo=NTUST-ics-Class-Schedule&type=fork" allowtransparency="true" frameborder="0" scrolling="0" width="55" height="22"></iframe>
              </p>

            </div>
          </div>
        </div>

        <hr>


        <div class="control-group id">
          <label class="control-label" for="id">1. 輸入你的學號：</label>
          <input name="id" id="id" type="text" class="input-block-level" placeholder="學號" value="<?php echo $_GET['id']; ?>">
        </div>

        <div class="control-group content">
          <label class="control-label" for="content">2. 進「<a href="https://stu255.ntust.edu.tw/ntust_stu/stu.aspx" target=_blank>學生資訊系統</a>」→「查詢選課狀態」，把「目前選課內容：」那一欄的內容複製貼到下面的框框。<a href="#how1" data-toggle="modal">圖。</a></label>
          <input name="content" id="content" type="text" class="input-block-level" placeholder="" style="height: 100px;" value="<?php echo $_GET['content']; ?>">
        </div>

        <label for="press">3.</label>
        <input type="submit" name="press" value="按下去" id="press" class="btn btn-large btn-block" <?php if($_GET['press']) echo "disabled=\"disabled\""; ?> >
        
        <div class="load" style="<?php if(!$_GET['press']) echo "height: 0;"; ?> overflow: hidden;">
          <label>4. 等</label>
          <div class="progress progress-info <?php if(!$_GET['press']) echo "progress-striped active"; ?>">
            <div class="bar" style="width: 100%;"></div>
          </div>
        </div>

        <?php if(!$_GET['press']) echo "<!--"; ?>


        <label id="get" for="get">5.</label>
        <a id="get" class="btn btn-large btn-block btn-primary" href="<?php echo "ics/".$semester."-".$_GET['id'].".ics"; ?>">取得日曆</a>
        <center>
          <br>
          <a class="collapsed" data-toggle="collapse" data-parent="#accordion2" href="#collapsedata">顯示數據</a>
          |
          <a href="?">再來一次</a>
        </center>

        <div class="accordion-group data-group">
          <div id="collapsedata" class="accordion-body collapse data-collapse" style="height: 0px;">
            <div class="accordion-inner">
              <br>
              <pre>
<?php print_r($data); ?>
              </pre>
            </div>
          </div>
        </div>

        <?php if(!$_GET['press']) echo "-->"; ?>
        <hr>
        <div class="fb-like" data-href="<?php echo $ThisURL; ?>" data-send="true" data-show-faces="true" style="max-width: 100%; "></div>
      </form>
      <script type="text/javascript">
        function loadbar(){
        }
        $(".id").keydown(function(){
          $(".id").removeClass("error");
        });
        $(".content").keydown(function(){
          $(".content").removeClass("error");
        });


      </script>

    </div>

 
<!-- Modal -->
    <div id="how1" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="myModalLabel"></h3>
      </div>
      <div class="modal-body">
        <img src="img/how1.jpg">
      </div>
    </div>

  </body>
</html>
